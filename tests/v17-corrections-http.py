"""Read-only final-ZIP checks for the changed public renderers and same-version cache keys."""
import hashlib
import html
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request


def run(request, admin, reader, anon, fixtures):
    assert os.environ.get('FILEWARDEN_TESTING') == '1'
    base = os.environ['FILEWARDEN_URL']
    assert urllib.parse.urlsplit(base).hostname in ('localhost', '127.0.0.1', '::1')
    for route, stylesheet in (
        ('?action=filewarden_folder&folder_id=' + fixtures['public_folder_uuid'], 'publication'),
        ('?action=filewarden_folder&folder_id=11111111-1111-4111-8111-111111111111', 'publication'),
        ('?action=filewarden_content&document=' + fixtures['public'], 'viewer'),
    ):
        status, headers, body, _ = request(anon, '/wp-admin/admin-post.php' + route)
        assert status == (404 if '11111111' in route else 200)
        assert 'no-store' in headers['Cache-Control']
        assert 'unsafe-inline' not in headers['Content-Security-Policy']
        match = re.search(rb'<link rel="stylesheet" href="([^"]+)"', body)
        assert match, 'Public renderer failed to emit its stylesheet'
        address = urllib.parse.urlsplit(html.unescape(match[1].decode()))
        assert address.path.endswith('/assets/' + stylesheet + '.css')
        status, _, css, _ = request(anon, address.path + '?' + address.query)
        assert status == 200
        expected = os.environ.get('FILEWARDEN_TARGET_VERSION', '0.1.17-dev') + '-' + hashlib.sha256(css).hexdigest()[:12]
        assert urllib.parse.parse_qs(address.query)['ver'] == [expected], 'Same-version style cache key does not identify the installed CSS'
    private = '/wp-admin/admin-post.php?action=filewarden_content&document=' + fixtures['private'] + '&download=1'
    assert request(anon, private)[0] == 404
    assert request(reader, private)[0] == 200
    public = '/wp-admin/admin-post.php?action=filewarden_content&document=' + fixtures['public'] + '&download=1'
    status, headers, body, _ = request(anon, public, headers={'Range': 'bytes=0-4'})
    assert status == 206 and body == b'%PDF-' and headers['Content-Disposition'].startswith('attachment')
    form = urllib.parse.urlencode({'action': 'filewarden_admin', 'operation': 'publication_login', '_wpnonce': 'invalid', 'publication_login[mode]': 'wordpress'}).encode()
    assert request(admin, '/wp-admin/admin-post.php', data=form)[0] == 403
    print('Final corrective HTTP passed: actual publication/denied/viewer CSS digests, CSP/no-store, private read boundary, PDF range bytes and sign-in CSRF refusal.')


if __name__ == '__main__':
    assert os.environ.get('FILEWARDEN_TESTING') == '1'
    base = os.environ['FILEWARDEN_URL']
    assert urllib.parse.urlsplit(base).hostname in ('localhost', '127.0.0.1', '::1')
    with open(os.environ['FILEWARDEN_FIXTURES'], encoding='utf-8') as handle:
        fixture = json.load(handle)

    def request(client, path, data=None, headers=None):
        try:
            with client.open(urllib.request.Request(base + path, data=data, headers=headers or {})) as response:
                return response.status, response.headers, response.read(), response.url
        except urllib.error.HTTPError as error:
            return error.code, error.headers, error.read(), error.url

    def login(name):
        client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        request(client, '/wp-login.php')
        result = request(client, '/wp-login.php', urllib.parse.urlencode({'log': name, 'pwd': 'disposable-test-password', 'testcookie': '1'}).encode())
        assert '/wp-admin/' in result[3]
        return client

    run(request, login('fw_admin'), login('fw_subscriber'), urllib.request.build_opener(), fixture)
