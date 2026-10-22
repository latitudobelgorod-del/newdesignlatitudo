undef $/; $_ = <>;
s#<br />\n<b>(?:Warning|Deprecated|Notice)</b>:.*?<br />\n##gs;
s#^(?:Set-Cookie|Content-[Tt]ype|Content-Length|Expires|X-[\w-]+|P3P):.*\n##gm;
s#bitrix_sessid[^,;]*##g; s#[0-9a-f]{32}##g; s#\?\d{9,}##g; s#\d{10}##g; s#nd_stage/latitudo\.org/##g;
print;
