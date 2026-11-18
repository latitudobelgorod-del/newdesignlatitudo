"""Verify and apply a deployment bundle received on standard input."""
import base64
import fcntl
import json
import os
import stat
import sys
import tempfile
from pathlib import Path, PurePosixPath


def file_version(root, name):
    relative = PurePosixPath(name)
    if relative.is_absolute() or len(relative.parts) < 2 or relative.parts[0] != "local":
        raise ValueError(f"Invalid production path {name!r}.")
    if ".." in relative.parts or relative.as_posix() != name:
        raise ValueError(f"Invalid production path {name!r}.")

    path = root
    for part in relative.parts[:-1]:
        path /= part
        if path.is_symlink() or (path.exists() and not path.is_dir()):
            raise ValueError(f"Invalid parent directory for {name!r}.")

    path /= relative.name
    try:
        mode = path.lstat().st_mode
    except FileNotFoundError:
        return path, None, 0o644

    if not stat.S_ISREG(mode):
        raise ValueError(f"Production path is not a regular file: {name!r}.")
    return path, path.read_bytes(), stat.S_IMODE(mode)


def decode(value):
    return None if value is None else base64.b64decode(value, validate=True)


def stage(directory, name, contents, mode):
    if contents is None:
        return None
    path = directory / name
    path.write_bytes(contents)
    path.chmod(mode)
    return path


def replace(path, staged):
    if staged is None:
        path.unlink()
    else:
        os.replace(staged, path)


def apply(root, incoming, temp_parent):
    if not (root / "local").is_dir() or (root / "local").is_symlink():
        raise ValueError("The production directory local/ is missing or is a symlink.")

    seen = set()
    with tempfile.TemporaryDirectory(prefix=".latitudo-deploy-", dir=temp_parent) as scratch:
        staging = Path(scratch)
        changes = []
        for index, record in enumerate(incoming):
            name = record["path"]
            if name in seen:
                raise ValueError(f"Duplicate production path {name!r}.")
            seen.add(name)
            path, current, mode = file_version(root, name)
            before = decode(record["before"])
            after = decode(record["after"])
            if current != before:
                raise ValueError(f"Production differs from base: {name!r}. No files changed.")
            if current == after:
                continue

            existing_parent = next(parent for parent in path.parents if parent.is_dir())
            if existing_parent.stat().st_dev != staging.stat().st_dev:
                raise ValueError(f"Cannot atomically replace {name!r} across filesystems.")
            previous = stage(staging, f"{index}.before", before, mode)
            replacement = stage(staging, f"{index}.after", after, mode)
            changes.append((path, previous, replacement))

        applied = []
        created_dirs = []
        try:
            for path, previous, replacement in changes:
                for parent in reversed(path.parents):
                    if not parent.exists():
                        parent.mkdir()
                        created_dirs.append(parent)
                replace(path, replacement)
                applied.append((path, previous))
        except Exception:
            for path, previous in reversed(applied):
                try:
                    replace(path, previous)
                except OSError as exc:
                    print(f"Rollback failed for {path.relative_to(root)!s}: {exc}", file=sys.stderr)
            for directory in reversed(created_dirs):
                try:
                    directory.rmdir()
                except OSError as exc:
                    print(f"Could not remove {directory.relative_to(root)!s}: {exc}", file=sys.stderr)
            raise

    print(f"Applied {len(changes)} change(s) under local/.")


def main():
    home = Path.home()
    root = (home / "www" / "latitudo.ru").resolve(strict=True)
    with (home / ".latitudo-deploy.lock").open("a") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        apply(root, json.load(sys.stdin), home)


if __name__ == "__main__":
    main()
