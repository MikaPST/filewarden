"""HTTP integration against a disposable WordPress fixture; never use on a real site."""
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request
import uuid

assert os.environ.get("FILEWARDEN_TESTING") == "1"
base = os.environ.get("FILEWARDEN_URL", "http://127.0.0.1:8080")
with open(os.environ["FILEWARDEN_FIXTURES"], encoding="utf-8") as handle:
    fixtures = json.load(handle)


def request(client, path, data=None, headers=None, method=None):
    req = urllib.request.Request(base + path, data=data, headers=headers or {}, method=method)
    try:
        with client.open(req) as response:
            return response.status, response.headers, response.read(), response.url
    except urllib.error.HTTPError as error:
        return error.code, error.headers, error.read(), error.url


def content(kind, download=True):
    return "/wp-admin/admin-post.php?" + urllib.parse.urlencode(
        {"action": "filewarden_content", "document": fixtures[kind], "download": "1" if download else "0"}
    )


def login(user):
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    request(client, "/wp-login.php")
    request(client, "/wp-login.php", urllib.parse.urlencode(
        {"log": user, "pwd": "disposable-test-password", "testcookie": "1"}
    ).encode())
    return client


anon = urllib.request.build_opener()
assert request(anon, content("private"))[0] == 404
assert request(anon, content("private", False))[0] == 404
assert request(anon, content("private"), headers={"Range": "bytes=0-4"})[0] == 404
status, headers, body, _ = request(anon, content("public"))
assert status == 200 and body.startswith(b"%PDF-")
assert "no-store" in headers["Cache-Control"] and headers["Content-Disposition"].startswith("attachment")
assert headers["X-Content-Type-Options"] == "nosniff"
status, headers, body, _ = request(anon, content("public"), headers={"Range": "bytes=0-4"})
assert status == 206 and body == b"%PDF-" and headers["Content-Range"].startswith("bytes 0-4/")
assert request(anon, content("public"), headers={"Range": "bytes=999999-"})[0] == 416
assert request(anon, content("public"), headers={"Range": "bytes=0-1,5-6"})[0] == 416
status, headers, body, _ = request(anon, content("public"), method="HEAD")
assert status == 200 and body == b"" and int(headers["Content-Length"]) > 0
assert request(anon, content("public"), data=b"")[0] == 405

editor = login("fw_editor")
assert request(editor, content("private"))[0] == 200
reader = login("fw_subscriber")
assert request(reader, content("private"))[0] == 200
assert request(reader, "/?rest_route=/filewarden/v1/documents", data=b"")[0] in (401, 403)

admin = login("fw_admin")
status, _, page, _ = request(admin, "/wp-admin/admin.php?page=filewarden")
assert status == 200
match = re.search(rb'name="_wpnonce" value="([^"]+)"', page)
assert match, "Admin form nonce not found"
nonce = match.group(1).decode()
assert request(admin, "/wp-admin/admin-post.php", data=urllib.parse.urlencode(
    {"action": "filewarden_admin", "operation": "settings", "upload_mb": "25", "audit_days": "30"}
).encode())[0] == 403, "Mutation without nonce was accepted"


def upload(name, payload):
    boundary = "fw-" + uuid.uuid4().hex
    chunks = []
    fields = {"action": "filewarden_admin", "operation": "upload", "_wpnonce": nonce,
              "folder_id": "0", "policy_type": "authenticated"}
    for key, value in fields.items():
        chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{name}"\r\nContent-Type: application/pdf\r\n\r\n'.encode() + payload + b"\r\n")
    chunks.append(f'--{boundary}--\r\n'.encode())
    return request(admin, "/wp-admin/admin-post.php", b"".join(chunks), {"Content-Type": "multipart/form-data; boundary=" + boundary})


pdf = b"%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n"
assert "result=success" in upload("upload-test.pdf", pdf)[3]
assert "result=duplicate" in upload("upload-test.pdf", pdf)[3]
assert "result=failed" in upload("malicious.php", b"<?php echo 'blocked';")[3]
assert "result=failed" in upload("fake.pdf", b"not a PDF")[3]
print("HTTP assertions passed: authentication, ACL, HEAD/ranges, CSRF, uploads and duplicates.")
