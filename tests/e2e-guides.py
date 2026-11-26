"""
End-to-end tests of the guides (sm-guides 0.1.0, sm-seo 0.4.0, theme 0.61.0): list,
page of a guide (table of contents, tools, other guides), 3 languages, phone,
search engines (Article, canonical, sitemap), links from the tools, unknown name.
Usage: python3 tests/e2e-guides.py http://localhost:8080 /path/to/wp out_dir
"""
import json, os, re, sys, urllib.request
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-guides-output'
os.makedirs(OUT, exist_ok=True)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def get(path):
    try:
        r = urllib.request.urlopen(BASE + path)
        return r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()

G3 = '/guides/acheter-acier-reemploi/'
errors = []
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1280, 'height': 900})
    pg.on('pageerror', lambda e: errors.append(str(e)))

    print('A. List of the guides')
    pg.goto(BASE + '/guides/?lang=fr')
    check('Guides' in pg.locator('h1').inner_text(), 'title: %s' % pg.locator('h1').inner_text())
    cards = pg.locator('.sm-guidecard')
    check(cards.count() == 4, '4 guides (%d)' % cards.count())
    first = cards.nth(0).inner_text()
    check('réemploi' in first.lower() and 'sections' in first and 'Outils liés' in first, 'first card: guide 3, sections, tools')
    check('ni prix du marché' in pg.locator('.sm-guide-note').inner_text(), 'note: no market price, no statistics')
    pg.screenshot(path=OUT + '/guides-fr.png', full_page=True)

    print('B. A guide')
    cards.nth(0).click()
    pg.wait_for_load_state()
    check(pg.url.endswith(G3), 'card opens the guide')
    check('ce qu' in pg.locator('h1').inner_text(), 'title of the guide')
    check('cinq choses' in pg.locator('.sm-pagehead__intro').inner_text(), 'summary under the title')
    meta = pg.locator('.sm-guide__meta').inner_text()
    check('Mis à jour le' in meta and 'العربية' in meta and 'English' in meta, 'date and the 2 other languages')
    toc = pg.locator('.sm-guide__toc a')
    check(toc.count() == 7 and pg.locator('.sm-guide__text h2').count() == 7, 'table of contents = the 7 sections')
    toc.nth(3).click()
    pg.wait_for_timeout(300)
    check(pg.url.endswith('#s4') and pg.locator('#s4').is_visible(), 'table of contents link goes to its section')
    check(pg.locator('.sm-guide__text table').count() == 1 and pg.locator('.sm-guide__check li').count() == 8, 'table of the conditions, 8 points of checklist')
    tools = pg.locator('.sm-guide__tools a')
    check(tools.count() == 4, 'linked tools (%d)' % tools.count())
    cta = pg.locator('.sm-guide__aside .sm-btn--accent')
    check('réemploi' in cta.inner_text() and '/reemploi/' in cta.get_attribute('href'), 'main button: the reuse listings')
    check(pg.locator('.sm-guide__more .sm-guidecard').count() == 3, '3 other guides')
    check(not re.search(r'\d+\s*DA', pg.locator('.sm-guide__text').inner_text()), 'no price in the text')
    pg.screenshot(path=OUT + '/guide-fr.png', full_page=True)

    print('C. Other languages')
    pg.goto(BASE + '/ar/guides/reduire-chutes-barres-6-12-m/')
    check(pg.locator('html').get_attribute('dir') == 'rtl' and 'قضبان' in pg.locator('h1').inner_text(), 'Arabic: right to left, Arabic title')
    t = pg.locator('.sm-guide__text').inner_text()
    check('مثال' in t and pg.locator('.sm-guide__text h2').count() == 5 and 'mm' in t, 'Arabic: whole text, 5 sections, unit « mm » kept')
    check(pg.locator('.sm-guide__fallback').count() == 0, 'Arabic: no notice of French text')
    pg.screenshot(path=OUT + '/guide-ar.png', full_page=True)
    pg.goto(BASE + '/en/guides/batiment-metallique-occasion/')
    check('used steel building' in pg.locator('h1').inner_text().lower(), 'English: English title')
    check('Any ReuSteel account can list a building' in pg.locator('.sm-guide__text').inner_text(), 'English: correction « any account » present')

    print('D. Phone')
    ph = b.new_page(viewport={'width': 390, 'height': 844}, is_mobile=True)
    ph.goto(BASE + G3 + '?lang=fr')
    check(ph.locator('.sm-guide__toc-m').is_visible() and not ph.locator('.sm-guide__toc').is_visible(), 'phone: folded table of contents')
    ph.locator('.sm-guide__toc-m summary').click()
    check(ph.locator('.sm-guide__toc-m a').nth(0).is_visible(), 'phone: table of contents opens')
    over = ph.evaluate('document.documentElement.scrollWidth > window.innerWidth + 1')
    check(not over, 'phone: no sideways scrolling')
    ph.screenshot(path=OUT + '/guide-phone.png', full_page=True)

    print('E. Links from the tools')
    pg.goto(BASE + '/optimiseur-decoupe/?lang=fr')
    gl = pg.locator('.sm-guidelinks a')
    check(gl.count() == 1 and 'chutes' in gl.inner_text().lower(), 'cutting optimiser → guide 6')
    pg.goto(BASE + '/reemploi/?lang=fr')
    check(pg.locator('.sm-guidelinks a').count() == 2, 'reuse market → guides 3 and 4')
    check(pg.locator('footer a[href$="/guides/"]').count() >= 1, 'footer: link « Guides »')
    b.close()

print('F. Search engines')
s, h = get(G3)
check(s == 200 and '<link rel="canonical" href="%s%s"' % (BASE.replace('127.0.0.1', 'localhost'), G3) in h.replace('127.0.0.1', 'localhost'), 'canonical address')
lds = [json.loads(x) for x in re.findall(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', h, re.S)]
flat = json.dumps(lds)
check('"Article"' in flat and '"BreadcrumbList"' in flat and 'DA/kg' not in flat, 'Article and breadcrumb, without price')
check('hreflang="ar"' in h and 'hreflang="en"' in h, 'language alternates')
s, h = get('/guides/nexiste-pas/')
check(s == 404 and 'noindex' in h, 'unknown guide: 404, not indexed')
s, x = get('/wp-sitemap-smguides-1.xml')
locs = re.findall(r'<loc>([^<]+)', x)
check(s == 200 and len(locs) == 15 and any('/ar/guides/' in l for l in locs), 'sitemap of the guides: 15 addresses')
check(not errors, 'no JavaScript error' + (': ' + errors[0] if errors else ''))

ok = sum(1 for r in results if r[0])
print('\n%d/%d passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
