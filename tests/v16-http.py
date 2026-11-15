"""Opaque formats must never acquire inline execution, public access or a size bypass."""
import hashlib
import os
import re
import urllib.parse
import uuid


def run(request, admin, reader, anon, nonce, fixtures, upload):
    assert os.environ.get('FILEWARDEN_TESTING') == '1'
    for extension, record in fixtures['v16_opaque'].items():
        query = {'action': 'filewarden_content', 'document': record['uuid'], 'download': '1'}
        path = '/wp-admin/admin-post.php?' + urllib.parse.urlencode(query)
        status, headers, body, _ = request(admin, path)
        assert status == 200 and headers.get_content_type() == 'application/octet-stream', extension
        assert headers.get('Content-Disposition', '').startswith('attachment;'), extension
        assert headers.get('X-Content-Type-Options') == 'nosniff'
        assert 'sandbox' in headers.get('Content-Security-Policy', '')
        assert 'no-store' in headers.get('Cache-Control', '')
        assert hashlib.sha256(body).hexdigest() == record['sha256']
        assert request(anon, path)[0] == 404, 'Opaque private attachment leaked'
        query.pop('download')
        query['preview'] = '1'
        assert request(admin, '/wp-admin/admin-post.php?' + urllib.parse.urlencode(query))[0] == 415

    def settings(client, fields, token=nonce):
        fields = dict(fields, action='filewarden_admin', operation='settings', _wpnonce=token)
        return request(client, '/wp-admin/admin-post.php', urllib.parse.urlencode(fields).encode())

    assert settings(admin, {'allow_all_types': '1'}, token='invalid')[0] == 403
    assert 'result=invalid' in settings(admin, {'allow_all_types': '1'})[3]
    assert 'result=invalid' in settings(admin, {'language': '../../private', 'notification_seconds': '30'})[3]
    assert 'result=denied' in settings(reader, {'allow_all_types': '1', 'all_types_confirmed': '1'})[3]
    name = 'fw-permissive-' + uuid.uuid4().hex + '.html'
    body = b'<html><body>Inert FileWarden HTTP fixture</body></html>'
    assert 'result=format' in upload(name, body)[3], 'Strict upload accepted HTML'
    assert 'result=success' in settings(admin, {'allow_all_types': '1', 'all_types_confirmed': '1'})[3]
    try:
        result = upload(name, body)
        assert 'result=success' in result[3], 'Explicit all-format upload failed'
        doc = urllib.parse.parse_qs(urllib.parse.urlsplit(result[3]).query)['created'][0]
        binary = '/wp-admin/admin-post.php?' + urllib.parse.urlencode({'action': 'filewarden_content', 'document': doc, 'download': '1'})
        status, headers, saved, _ = request(admin, binary)
        assert status == 200 and saved == body and headers.get_content_type() == 'application/octet-stream'
        assert headers.get('Content-Disposition', '').startswith('attachment;')
        add_page = request(admin, '/wp-admin/admin.php?page=filewarden&tab=add')[2].decode()
        assert 'class="fw-format-notice"' not in add_page
        assert re.search(r'id="fw-file"[^>]*\baccept=', add_page) is None
    finally:
        assert 'result=success' in settings(admin, {'allow_all_types': '0'})[3]
    assert 'result=format' in upload('strict-again-' + name, body)[3]
    print('V16 HTTP passed: confirmation/capabilities/CSRF, strict/permissive/strict uploads, opaque bytes, nosniff/sandbox/attachment, private ACL and rejected inline previews.')
