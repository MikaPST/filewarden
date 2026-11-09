"""HTTP integration against a disposable WordPress fixture; never use on a real site."""
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request
import uuid
from html import unescape

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
assert request(anon, content("private", False) + "&preview=1")[0] == 404
status, headers, body, _ = request(anon, content("public", False) + "&preview=1")
assert status == 200 and body.startswith(b"%PDF-")
assert headers["Content-Type"] == "application/pdf" and headers["Content-Disposition"].startswith("attachment")
assert "sandbox" in headers["Content-Security-Policy"] and "no-store" in headers["Cache-Control"]
status, headers, body, _ = request(anon, content("public", False))
assert status == 200 and b"viewer.mjs" in body and b'id="fw-page"' in body
assert "frame-ancestors 'self'" in headers["Content-Security-Policy"]
assert request(anon, content("public", False) + "&preview=1", headers={"Range": "bytes=0-4"})[2] == b"%PDF-"
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
folder_view = "/wp-admin/admin-post.php?action=filewarden_folder&folder_id=" + str(fixtures["folder_id"])
assert request(anon, folder_view)[0] == 404
status, headers, listing, _ = request(editor, folder_view)
assert status == 200 and b"private.pdf" in listing and "no-store" in headers["Cache-Control"]
reader = login("fw_subscriber")
assert request(reader, content("private"))[0] == 200
assert request(reader, "/?rest_route=/filewarden/v1/documents", data=b"")[0] in (401, 403)

admin = login("fw_admin")
status, _, page, _ = request(admin, "/wp-admin/admin.php?page=filewarden&tab=add")
assert status == 200
assert page.count(b'data-filewarden-upload="true"') == 1, "Expected one unified document form"
assert b'name="operation" value="add_document"' in page
assert b'value="upload"' in page and b'value="import"' in page
assert page.count(b'id="document-parent"') == 1, "Expected one shared document destination"
match = re.search(rb'name="_wpnonce" value="([^"]+)"', page)
assert match, "Admin form nonce not found"
nonce = match.group(1).decode()
assert request(admin, "/wp-admin/admin-post.php", data=urllib.parse.urlencode(
    {"action": "filewarden_admin", "operation": "settings", "upload_mb": "25", "audit_days": "30"}
).encode())[0] == 403, "Mutation without nonce was accepted"
for source in ("", "settings", "folder", "unknown"):
    result = request(admin, "/wp-admin/admin-post.php", data=urllib.parse.urlencode({
        "action": "filewarden_admin", "operation": "add_document", "source": source, "_wpnonce": nonce
    }).encode())
    assert "result=invalid" in result[3], "Invalid source must not dispatch another operation"
assert "result=denied" in request(reader, "/wp-admin/admin-post.php", data=urllib.parse.urlencode({
    "action": "filewarden_admin", "operation": "add_document", "source": "import", "_wpnonce": nonce,
    "folder_id": str(fixtures["folder_id"]), "policy_type": "authenticated", "media_id": str(fixtures["media_id"])
}).encode())[3], "Reader must not be allowed to import"


def upload(name, payload, folder=None):
    if folder is None:
        folder = fixtures["public_folder_id"]
    boundary = "fw-" + uuid.uuid4().hex
    chunks = []
    fields = {"action": "filewarden_admin", "operation": "add_document", "source": "upload", "_wpnonce": nonce,
              "folder_id": str(folder), "policy_type": "authenticated"}
    for key, value in fields.items():
        chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{name}"\r\nContent-Type: application/pdf\r\n\r\n'.encode() + payload + b"\r\n")
    chunks.append(f'--{boundary}--\r\n'.encode())
    return request(admin, "/wp-admin/admin-post.php", b"".join(chunks), {"Content-Type": "multipart/form-data; boundary=" + boundary})


pdf = b"%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n"
assert "result=invalid" in upload("root-refused.pdf", pdf, 0)[3]
assert "result=success" in upload("upload-test.pdf", pdf)[3]
assert "result=duplicate" in upload("upload-test.pdf", pdf)[3]
assert "result=format" in upload("malicious.php", b"<?php echo 'blocked';")[3]
assert "result=format" in upload("fake.pdf", b"not a PDF")[3]
large_pdf = pdf + b" " * 100000
status, _, listing, url = upload("large-upload.pdf", large_pdf, fixtures["folder_id"])
assert status == 200 and "result=success" in url and b"large-upload.pdf saved and verified" in listing
created = urllib.parse.parse_qs(urllib.parse.urlsplit(url).query)["created"][0]
download = "/wp-admin/admin-post.php?" + urllib.parse.urlencode({
    "action": "filewarden_content", "document": created, "download": "1"
})
assert request(admin, download)[2] == large_pdf, "Uploaded bytes were not preserved"
assert request(anon, download)[0] == 404
assert b"large-upload.pdf" in request(editor, folder_view)[2], "Uploaded document absent from publication"
status, _, page, url = request(admin, "/wp-admin/admin-post.php", data=urllib.parse.urlencode({
    "action": "filewarden_admin", "operation": "add_document", "source": "import", "_wpnonce": nonce,
    "folder_id": str(fixtures["folder_id"]), "policy_type": "authenticated", "media_id": str(fixtures["media_id"])
}).encode())
assert status == 200 and "result=success" in url and "folder_id=" + str(fixtures["folder_id"]) in url
assert b"filewarden-fixture.png saved and verified in Restricted / Public child" in page
match = re.search(rb'href="([^"]+)"[^>]*>filewarden-fixture\.png</a>', page)
assert match, "Imported image missing from destination listing"
detail = urllib.parse.urlsplit(unescape(match.group(1).decode()))
preview = detail.path + "?" + detail.query + "&preview=1"
assert request(anon, preview)[0] == 404
status, headers, pixels, _ = request(editor, preview)
assert status == 200 and headers["Content-Type"] == "image/png" and pixels.startswith(b"\x89PNG")
print("HTTP assertions passed: ACL, publication, protected preview, media import, ranges, CSRF and uploads.")

# Policy mutations must be nonce-protected and immediately affect every read path.
policy_view = '/wp-admin/admin-post.php?' + urllib.parse.urlencode({'action': 'filewarden_folder', 'folder_id': fixtures['policy_folder_uuid']})
policy_binary = '/wp-admin/admin-post.php?' + urllib.parse.urlencode({'action': 'filewarden_content', 'document': fixtures['policy_inherit'], 'download': '1'})
assert b'policy-inherit.pdf' in request(anon, policy_view)[2]
assert b'policy-authenticated.pdf' not in request(anon, policy_view)[2]
assert request(anon, policy_binary)[0] == 200

def policy_change(operation, policy_type, document=None, include_nonce=True):
    fields = {'action': 'filewarden_admin', 'operation': operation, 'folder_id': fixtures['policy_folder'], 'policy_type': policy_type}
    if include_nonce: fields['_wpnonce'] = nonce
    if document: fields['document'] = document
    return request(admin, '/wp-admin/admin-post.php', data=urllib.parse.urlencode(fields).encode())

assert policy_change('document_policy', 'authenticated', fixtures['policy_inherit'], False)[0] == 403
assert request(anon, policy_binary)[0] == 200
assert 'result=policy_saved' in policy_change('document_policy', 'authenticated', fixtures['policy_inherit'])[3]
assert request(anon, policy_binary)[0] == 404
assert b'policy-inherit.pdf' not in request(anon, policy_view)[2]
assert request(editor, policy_binary)[0] == 200
assert 'result=policy_saved' in policy_change('document_policy', 'inherit', fixtures['policy_inherit'])[3]
assert request(anon, policy_binary)[0] == 200
assert 'result=policy_saved' in policy_change('folder_policy', 'authenticated')[3]
assert request(anon, policy_view)[0] == 404
assert request(anon, policy_binary)[0] == 404
assert 'result=policy_saved' in policy_change('document_policy', 'public', fixtures['policy_inherit'])[3]
assert request(anon, policy_binary)[0] == 404, 'Explicit public bypassed folder restriction'
assert 'result=policy_saved' in policy_change('folder_policy', 'public')[3]
assert 'result=policy_saved' in policy_change('document_policy', 'inherit', fixtures['policy_inherit'])[3]
assert request(anon, policy_binary)[0] == 200
print('Policy editing HTTP checks passed: inheritance, file restriction, folder ceiling, immediate revocation and CSRF.')

# New audit exports and private recovery/publication boundaries.
import html
root_view = '/wp-admin/admin-post.php?' + urllib.parse.urlencode({'action': 'filewarden_folder', 'folder_id': fixtures['root_uuid']})
assert request(admin, root_view)[0] == 404
assert request(anon, root_view)[0] == 404
for style in ('compact', 'centered', 'editorial'):
    status, headers, body, _ = request(anon, folder_view + '&denied=' + style)
    assert status == 404 and ('fw-denied-' + style).encode() in body
    assert b'target="_top"' in body and b'Private child' not in body
    assert 'no-store' in headers['Cache-Control']
status, _, audit_page, _ = request(admin, '/wp-admin/admin.php?page=filewarden-audit&event=document_read')
assert status == 200 and b'fw-terminal' in audit_page
links = [html.unescape(v) for v in re.findall(r'href="([^"]+)"', audit_page.decode())]
for fmt in ('csv', 'txt'):
    url = next(v for v in links if 'action=filewarden_audit' in v and 'format=' + fmt in v)
    url = url.removeprefix(base)
    status, headers, body, _ = request(admin, url)
    assert status == 200 and ('filewarden-audit.' + fmt) in headers['Content-Disposition']
    assert b'created_at_UTC' in body
    assert request(editor, url)[0] != 200
    assert request(admin, re.sub(r'([?&])_wpnonce=[^&]+', r'\1_wpnonce=invalid', url))[0] != 200
print('Navigation denial styles, root quarantine and authorized CSV/TXT export checks passed.')

