"""Run: python tools/check-seo-legacy.py -- verifies production SEO mappings."""
import urllib.error
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


def get(url):
    try:
        return opener.open(url, timeout=30)
    except urllib.error.HTTPError as error:
        return error


opener = urllib.request.build_opener(NoRedirect)
base = 'https://pn-natuna.go.id'
mappings = {
    'about/profil-pengadilan/sejarah-pengadilan': '/profil-pengadilan/sejarah-pengadilan',
    'artikel-pn-natuna/uncategorised/mediasi': '/layanan-hukum/mediasi',
    'about/kepaniteraan': '/profil-pengadilan/profil-kepaniteraan',
}
for old, target in mappings.items():
    for prefix in ('/index.php/en/', '/index.php/', '/'):
        with get(base + prefix + old) as response:
            assert response.code == 301, (old, response.code)
            assert response.headers['Location'] == base + target, (old, response.headers['Location'])
    with get(base + target) as response:
        assert response.code == 200, (target, response.code)
    print('PASS', old, target)
with get(base + '/seo-nonexistent-probe') as response:
    assert response.code == 404, response.code
print('PASS unknown URL remains 404')
