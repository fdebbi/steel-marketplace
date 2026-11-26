"""
End-to-end tests of the pages « type × wilaya » (sm-products 0.24.0, sm-seo 0.3.0,
theme 0.60.0): page of a type in a wilaya and of a whole market in a wilaya, real
figures, links (sizes, other wilayas, other types, listing → page), 3 languages,
phone, search engines (indexed only from 3 listings, sitemap), unknown names.
Usage: python3 tests/e2e-landing.py http://localhost:8080 /path/to/wp out_dir
"""
import json, os, re, subprocess, sys
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-landing-output'
os.makedirs(OUT, exist_ok=True)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def norm(t):
    return t.replace(' ', ' ').replace('\xa0', ' ')

# The biggest group with a type, from the data of the site (no figure written here).
g = json.loads(wp('$L = SM\\Products\\Landing::class; foreach ($L::groups() as $g) { if ("" !== $g[1] && isset(SM\\Products\\Categories::labels($g[0])[$g[1]])) { $d = apply_filters("sm_theme_data_landing", null, array("market"=>$g[0],"category"=>$g[1],"wilaya"=>$L::wilaya_slug($g[2]))); echo wp_json_encode(array("url"=>$d["url"],"count"=>$d["count"],"prices"=>$d["prices"],"market"=>$g[0],"wslug"=>$L::wilaya_slug($g[2]),"wilaya"=>$g[2],"cat"=>$g[1])); break; } }'))
small = wp('$L = SM\\Products\\Landing::class; foreach ($L::groups(1) as $g) { if ("" !== $g[1] && $g[3] < 3 && "" !== $L::wilaya_slug($g[2]) && isset(SM\\Products\\Categories::labels($g[0])[$g[1]])) { echo $L::url($g[0], $g[1], $g[2]); break; } }')
path = re.sub(r'^https?://[^/]+', '', g['url'])
errors = []
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1280, 'height': 900})
    pg.on('pageerror', lambda e: errors.append(str(e)))

    print('A. Page of a type in a wilaya: ' + path)
    pg.goto(BASE + path + '?lang=fr')
    head = norm(pg.locator('.sm-pagehead').inner_text())
    check(' à ' in pg.locator('h1').inner_text(), 'title « … à … »: %s' % pg.locator('h1').inner_text())
    check(str(g['count']) in head and 'annonce' in head, 'intro with the real number of listings (%d)' % g['count'])
    kp = norm(pg.locator('.smk-kpis').inner_text())
    check(str(g['count']) in kp and ('Prix demandés' in kp) == bool(g['prices']), 'figures: listings, weight, price range only from 3 prices')
    check(pg.locator('.sm-grid .sm-card--listing').count() == min(12, g['count']), 'listings shown (%d)' % pg.locator('.sm-grid .sm-card--listing').count())
    check(pg.locator('a:has-text("avec les filtres")').count() == 1 and 'wilaya=' in pg.locator('a:has-text("avec les filtres")').get_attribute('href'), '« See the listings with the filters »')
    check(pg.locator('.smk-actions a:has-text("Demander un devis")').count() == 1, '« Request a quote »')
    if g['market'] != 'buildings':
        check(pg.locator('.sml-chips a[href*="/catalogue/"]').count() >= 1, 'sizes offered → pages of the catalogue')
    check('noindex' not in (pg.locator('meta[name=robots]').get_attribute('content') or ''), 'indexed (3 listings or more)')
    check(pg.locator('link[rel=canonical]').get_attribute('href').endswith(path), 'canonical address')
    t = []
    for s in pg.locator('script[type="application/ld+json"]').all_text_contents():
        t += [n.get('@type') for n in json.loads(s).get('@graph', [])]
    check('ItemList' in t and 'BreadcrumbList' in t, 'structured data: list of the listings, breadcrumb')
    check(not re.search(r'aggregateRating|march[ée] officiel', pg.content(), re.I), 'no rating, no market price')
    pg.screenshot(path=os.path.join(OUT, 'landing-1280.png'), full_page=True)

    print('\nB. Links and sitemap')
    lid = wp('global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_listing_index WHERE module=%%s AND category=%%s AND wilaya=%%s AND status=\'published\' LIMIT 1", "%s", "%s", "%s"));' % (g['market'], g['cat'], g['wilaya']))
    pg.goto(BASE + '/annonce/%s/?lang=fr' % lid)
    check(pg.locator('a[href$="%s"]' % path).count() >= 1, 'listing → « N listings of this type in … »')
    sm = pg.request.get(BASE + '/wp-sitemap-smlanding-1.xml').text()
    check(path in sm and '/ar' + path in sm and '/en' + path in sm, 'sitemap: page in the 3 languages')
    if small:
        sp = re.sub(r'^https?://[^/]+', '', small)
        check(sp not in sm, 'fewer than 3 listings: not in the sitemap')
        pg.goto(BASE + sp)
        check('noindex' in (pg.locator('meta[name=robots]').get_attribute('content') or ''), 'fewer than 3 listings: out of the index (%s)' % sp)
    r = pg.goto(BASE + '/produits/zzz/' + g['wslug'] + '/')
    check(r.status == 404 and 'noindex' in (pg.locator('meta[name=robots]').get_attribute('content') or ''), 'unknown type: 404, out of the index')
    mp = '/' + path.strip('/').split('/')[0] + '/' + g['wslug'] + '/'
    r = pg.goto(BASE + mp + '?lang=fr')
    check(r.status == 200 and ' à ' in pg.locator('h1').inner_text(), 'whole market in the wilaya: %s' % mp)

    # 0.60.1: elision before a vowel, and a listing whose type has fewer than 3 listings → page of the whole market.
    vw = wp('$L = SM\\Products\\Landing::class; foreach ($L::groups() as $g) { $n = SM\\Core\\Accounts\\Wilayas::all(true)[$g[2]]["fr"] ?? ""; if (preg_match("/^[aeiouy]/i", remove_accents($n))) { echo $L::url($g[0], $g[1], $g[2]); break; } }')
    if vw:
        pg.goto(BASE + re.sub(r'^https?://[^/]+', '', vw) + '?lang=fr')
        intro = norm(pg.locator('.sm-pagehead__intro').inner_text()) + ' ' + norm(pg.locator('meta[name=description]').get_attribute('content'))
        check('wilaya d’' in intro and 'wilaya de A' not in intro and 'wilaya de O' not in intro and 'wilaya de E' not in intro, 'elision: « dans la wilaya d’… » (%s)' % vw)
    fb = wp('$L = SM\\Products\\Landing::class; foreach ($L::groups(1) as $g) { if ("" !== $g[1] && $g[3] < 3) { $m = $L::url($g[0], "", $g[2]); global $wpdb; $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_listing_index WHERE module=%s AND category=%s AND wilaya=%s AND status=\'published\' LIMIT 1", $g[0], $g[1], $g[2])); if ($id && $m) { echo $id . " " . $m; break; } } }')
    if fb:
        fid, furl = fb.split(' ', 1)
        pg.goto(BASE + '/annonce/%s/?lang=fr' % fid)
        check(pg.locator('a[href$="%s"]' % re.sub(r'^https?://[^/]+', '', furl)).count() >= 1, 'type with fewer than 3 listings: listing → page of the whole market (%s)' % furl)

    print('\nC. Arabic, phone')
    m = b.new_page(viewport={'width': 390, 'height': 844})
    m.goto(BASE + '/ar' + path)
    check(m.locator('html').get_attribute('dir') == 'rtl' and ' في ' in m.locator('h1').inner_text(), 'Arabic: « … في … », RTL')
    check(m.evaluate('document.documentElement.scrollWidth <= 390'), 'phone 390: no horizontal scroll')
    m.screenshot(path=os.path.join(OUT, 'landing-ar-390.png'), full_page=True)
    b.close()
check(not errors, 'no JavaScript error' + (': ' + '; '.join(errors[:3]) if errors else ''))
ok = sum(1 for r in results if r[0])
print('\n%d/%d passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
