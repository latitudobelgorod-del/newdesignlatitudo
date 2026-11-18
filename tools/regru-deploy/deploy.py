"""Plan and apply file changes between two approved commits."""
import argparse
import base64
import json
import re
import shlex
import subprocess
from pathlib import Path, PurePosixPath


def git(*args):
    return subprocess.check_output(["git", *args])


def changed_paths(base, target):
    revisions = (base, target)
    for revision in revisions:
        if not re.fullmatch(r"[0-9a-f]{40}", revision):
            raise ValueError("Both revisions must be full lowercase commit SHAs.")
        if git("cat-file", "-t", revision).strip() != b"commit":
            raise ValueError(f"{revision} is not a commit.")
        git("merge-base", "--is-ancestor", revision, "HEAD")

    diff = git("diff", "--name-status", "--no-renames", "-z", base, target, "--", "local/")
    fields = diff.decode("utf-8").split("\0")[:-1]
    for status, name in zip(fields[::2], fields[1::2], strict=True):
        if status not in {"A", "D", "M"}:
            raise ValueError(f"Unsupported change {status} for {name!r}.")
        path = PurePosixPath(name)
        if path.is_absolute() or path.parts[0] != "local" or ".." in path.parts:
            raise ValueError(f"Invalid production path {name!r}.")
        yield status, name


def encode_file(revision, name):
    entry = git("ls-tree", "-z", revision, "--", f":(literal){name}")
    if not entry.startswith(b"100644 blob "):
        raise ValueError(f"Only regular non-executable files are supported: {name!r}.")
    object_id = entry.split(b" ", 2)[2].split(b"\t", 1)[0].decode("ascii")
    return base64.b64encode(git("cat-file", "blob", object_id)).decode("ascii")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("base")
    parser.add_argument("target")
    parser.add_argument("--ssh-files", nargs=2, metavar=("KEY", "KNOWN_HOSTS"))
    args = parser.parse_args()

    files = []
    for status, name in changed_paths(args.base, args.target):
        before = encode_file(args.base, name) if status != "A" else None
        after = encode_file(args.target, name) if status != "D" else None
        print(f"{status} {name!r}", flush=True)
        files.append({"path": name, "before": before, "after": after})
    print(f"{len(files)} changed file(s) under local/.", flush=True)
    if not args.ssh_files or not files:
        return

    key, known_hosts = args.ssh_files
    remote_script = Path(__file__).with_name("apply.py").read_text(encoding="utf-8")
    subprocess.run(
        [
            "ssh", "-F", "/dev/null", "-T", "-p", "22", "-i", key,
            "-o", "IdentitiesOnly=yes", "-o", "BatchMode=yes",
            "-o", "ConnectTimeout=10", "-o", "StrictHostKeyChecking=yes",
            "-o", f"UserKnownHostsFile={known_hosts}",
            "u0916578@vip125.hosting.reg.ru",
            "python3 -c " + shlex.quote(remote_script),
        ],
        input=json.dumps(files).encode("utf-8"),
        check=True,
    )


if __name__ == "__main__":
    main()
