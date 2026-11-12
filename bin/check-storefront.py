#!/usr/bin/env python3
"""Read-only storefront smoke checks. Run against an installed local/test store.

Usage: python3 bin/check-storefront.py http://localhost:8080
No third-party dependencies. Does not create users, orders, or enquiries.
"""
import sys
import hashlib
import io
import zipfile
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080').rstrip('/')
PAGES = [
    '/', '/shop', '/powder-coating', '/mission', '/benefits',
    '/installation-standard', '/french-porte', '/refer-friends', '/portfolio',
    '/contact', '/blog', '/size-guide', '/resources/instructions',
    '/sales-sheet', '/book-online',
]
REDIRECTS = {
    '/blank': '/powder-coating', '/blank-1': '/refer-friends',
    '/contact-4': '/contact', '/about-3': '/mission',
    '/about-5-1': '/installation-standard', '/s-projects-side-by-side': '/french-porte',
    '/thundermounts': '/benefits', '/thunder-mounts': '/shop',
    '/items-6': '/portfolio',
    '/thunder-mounts-garage-systems/landingpage': '/installation-standard',
}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def read(path):
    with urllib.request.urlopen(BASE + path, timeout=30) as response:
        assert response.status == 200, (path, response.status)
        return response.read().decode('utf-8')


for path in PAGES:
    html = read(path)
    assert '<h1' in html and '<title>' in html, path
    assert 'id="announce-bar"' in html, f'Announcement missing on {path}; ensure it is enabled in settings'
    assert 'data-toast-wrap' in html, f'Confirmation region missing on {path}'
    print('PASS page:', path)

opener = urllib.request.build_opener(NoRedirect)
for old, target in REDIRECTS.items():
    try:
        opener.open(BASE + old, timeout=30)
        raise AssertionError(f'{old} did not redirect')
    except urllib.error.HTTPError as error:
        assert error.code == 301, (old, error.code)
        assert error.headers['Location'].endswith(target), (old, error.headers['Location'])
    print('PASS legacy redirect:', old, '->', target)

sitemap = ET.fromstring(read('/sitemap.xml'))
locations = {node.text for node in sitemap.findall('{*}url/{*}loc')}
for path in PAGES[2:8]:
    assert any(loc.endswith(path) for loc in locations), f'Sitemap missing {path}'
assert 'tm_announce_hidden' not in read('/assets/js/app.js'), 'Announcement dismissal is still persisted'
assert 'Powder coating quote' in read('/contact?subject=Powder%20coating%20quote'), 'Quote subject lost'
print('PASS sitemap, announcement behavior, and powder-coating quote link')

# Supplied public resources must be available without Google Drive authentication.
with urllib.request.urlopen(BASE + '/assets/documents/thunder-mounts-track-instructions-2023.docx', timeout=30) as response:
    document = response.read()
assert hashlib.sha256(document).hexdigest() == 'bf755da1058c7aa1152a3c0104de17c9009205af253a531f5bb1a2fbf8fd5b0c', 'Instruction download changed unexpectedly'
with zipfile.ZipFile(io.BytesIO(document)) as archive:
    assert archive.testzip() is None, 'Instruction document is corrupt'
    assert 'word/document.xml' in archive.namelist(), 'Not a Word document'
for image in ['gustavo-estravit-owner.jpg', 'tm-installation-chart-2026.jpg']:
    with urllib.request.urlopen(BASE + '/assets/img/' + image, timeout=30) as response:
        assert response.read(2) == b'\xff\xd8', f'{image} is not an actual JPEG'
instructions = read('/resources/instructions')
assert 'data-instruction-download' in instructions and 'data-installation-chart' in instructions
assert 'not yet hosted' not in instructions
assert 'gustavo-estravit-owner.jpg' in read('/about/inventor')
print('PASS original Word download, chart, and owner photo')
