"""
End-to-end tests of the public catalogue of sections (sm-weight-engine 0.8.0,
sm-seo 0.2.0, sm-products 0.23.1, theme 0.59.0): catalogue page, family table, page
of a size (weight, drawing, calculator, listings, neighbours, questions), 3 languages,
phone, search engines (title, description, canonical, structured data, sitemap),
not counted as a search in the Business report.
Usage: python3 tests/e2e-catalogue.py http://localhost:8080 /path/to/wp out_dir
"""
import json, os, re, subprocess, sys
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-catalogue-output'
os.makedirs(OUT, exist_ok=True)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def norm(t):
    return t.replace(' ', ' ').replace('\xa0', ' ')
def ld_types(pg):
    out = []
    for s in pg.locator('script[type="application/ld+json"]').all_text_contents():
        out += [n.get('@type') for n in json.loads(s).get('@graph', [])]
    return out

errors = []
searched = lambda: int(wp('global $wpdb; echo (int) $wpdb->get_var("SELECT SUM(hits) FROM {$wpdb->prefix}sm_search_counts WHERE day = \'" . gmdate("Y-m-d") . "\'");') or 0)
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1280, 'height': 900})
    pg.on('pageerror', lambda e: errors.append(str(e)))

    print('A. Catalogue page and family')
    pg.goto(BASE + '/catalogue/?lang=fr')
    check('Catalogue des profilés' in pg.locator('h1').inner_text(), 'catalogue: title in French')
    check(pg.locator('.smk-family').count() >= 15 and pg.locator('.smk-family[href*="/catalogue/plate"]').count() == 0, 'families of sections (%d), no page for the plates' % pg.locator('.smk-family').count())
    pg.click('.smk-family[href$="/catalogue/ipe/"]'); pg.wait_for_load_state()
    check(pg.url.endswith('/catalogue/ipe/') and pg.locator('.smk-table tbody tr').count() == 18, 'family IPE: table of the 18 sizes')
    row = norm(pg.locator('.smk-table tbody tr:has-text("IPE 200")').inner_text())
    check('22,36' in row and '268,3' in row, 'IPE 200 in the table: 22,36 kg/m, 268,3 kg the bar of 12 m')

    print('\nB. Page of a size')
    before = searched()
    pg.click('.smk-table a[href$="/catalogue/ipe-200/"]'); pg.wait_for_load_state()
    txt = norm(pg.locator('main, body').first.inner_text())
    check(pg.url.endswith('/catalogue/ipe-200/') and 'IPE 200 : poids et dimensions' in norm(pg.locator('h1').inner_text()), 'address /catalogue/ipe-200/, title « IPE 200 : poids et dimensions »')
    kp = norm(pg.locator('.smk-kpis').inner_text())
    check('22,36 kg/m' in kp and '134,2 kg' in kp and '268,3 kg' in kp and '28,48 cm²' in kp, 'weight per metre, bars of 6 and 12 m, area')
    check(pg.locator('.smk-drawing svg').count() == 1 and '200 mm' in norm(pg.locator('.smk-dims').inner_text()), 'drawing with dimensions')
    pg.fill('#smk-l', '6'); pg.fill('#smk-n', '10')
    check(norm(pg.locator('[data-smk-kg]').inner_text()) in ('1,34 t', '1,34 t'), 'calculator: 6 m × 10 bars = 1,34 t (%s)' % norm(pg.locator('[data-smk-kg]').inner_text()))
    check(pg.locator('.smk-offers .sm-card--listing').count() > 0 or pg.locator('.smk-empty').count() == 1, 'listings of IPE 200 in progress, or « no listing » with « Request a quote »')
    if pg.locator('.smk-empty').count():
        check(pg.locator('.smk-empty a:has-text("Demander un devis")').count() == 1, 'no listing: « Request a quote for IPE 200 »')
    check(searched() == before, 'viewing the page is not counted as a search (Business report)')
    check(pg.locator('.smk-near__prev').inner_text().find('IPE 180') >= 0 and pg.locator('.smk-near__next').inner_text().find('IPE 220') >= 0, 'neighbouring sizes IPE 180 and IPE 220')
    check(pg.locator('.smk-faq details').count() == 2 and '268,3' in norm(pg.locator('.smk-faq').inner_text()), 'two questions with the computed answers')
    check(not re.search(r'stad|ferpinta|arcelor|melinda|maurin', txt, re.I), 'no source of the data on the page')
    check(pg.locator('a[href*="calcul-poids"][href*="section="]').count() >= 1, 'link to the full calculator with the size')

    print('\nC. Search engines')
    check(norm(pg.title()).startswith('IPE 200 : poids au mètre et dimensions'), 'title: %s' % pg.title())
    desc = norm(pg.locator('meta[name=description]').get_attribute('content'))
    check('22,36 kg par mètre' in desc and '268,3 kg' in desc, 'description with the real values')
    check(pg.locator('link[rel=canonical]').get_attribute('href').endswith('/catalogue/ipe-200/'), 'canonical address')
    t = ld_types(pg)
    check('Product' in t and 'FAQPage' in t and 'BreadcrumbList' in t, 'structured data: Product, FAQ, breadcrumb')
    raw = ' '.join(pg.locator('script[type="application/ld+json"]').all_text_contents())
    check('offers' not in raw and 'aggregateRating' not in raw, 'no price and no rating in the data of the catalogue')
    sm = pg.request.get(BASE + '/wp-sitemap.xml').text()
    check('smcatalogue' in sm, 'sitemap of the catalogue in the index')
    s1 = pg.request.get(BASE + '/wp-sitemap-smcatalogue-1.xml').text()
    check('/catalogue/ipe/' in s1 and '/ar/catalogue/ipe/' in s1 and '/en/catalogue/' in s1, 'sitemap: pages in the 3 languages')
    r = pg.goto(BASE + '/catalogue/zz-999/')
    check(r.status == 404 and 'noindex' in (pg.locator('meta[name=robots]').get_attribute('content') or ''), 'unknown size: 404, out of the index')

    print('\nD. Languages and phone')
    m = b.new_page(viewport={'width': 390, 'height': 844})
    m.goto(BASE + '/ar/catalogue/ipe-200/')
    check(m.locator('html').get_attribute('dir') == 'rtl' and 'الوزن' in m.locator('h1').inner_text(), 'Arabic: title in Arabic, RTL')
    check(m.evaluate('document.documentElement.scrollWidth <= 390'), 'phone 390: no horizontal scroll')
    m.screenshot(path=os.path.join(OUT, 'catalogue-ar-390.png'), full_page=True)
    m.goto(BASE + '/catalogue/?lang=fr')
    check(m.evaluate('document.documentElement.scrollWidth <= 390'), 'phone 390: catalogue page, no horizontal scroll')
    m.goto(BASE + '/en/catalogue/shs-100x100x5/')
    check('SHS 100x100x5' in m.locator('h1').inner_text() and 'weight' in m.locator('h1').inner_text().lower(), 'English: a square tube')
    pg.goto(BASE + '/calcul-poids/?family=IPE&section=' + wp('echo SM\\WeightEngine\\Catalogue::find("IPE","IPE 200")->id;') + '&lang=fr')
    check(pg.locator('a[href$="/catalogue/ipe-200/"]').count() >= 1, 'weight calculator: link to the page of the size')
    check(pg.locator('footer a[href$="/catalogue/"]').count() == 1, 'footer: « Catalogue des profilés »')
    pg.goto(BASE + '/catalogue/ipe-200/?lang=fr'); pg.screenshot(path=os.path.join(OUT, 'catalogue-ipe200-1280.png'), full_page=True)
    b.close()
check(not errors, 'no JavaScript error' + (': ' + '; '.join(errors[:3]) if errors else ''))
ok = sum(1 for r in results if r[0])
print('\n%d/%d passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
