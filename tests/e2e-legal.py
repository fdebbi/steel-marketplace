"""
End-to-end tests of the legal pages (sm-core 0.10.0, theme 0.63.0, sm-seo 0.5.1):
terms of use and privacy policy in 3 languages (company information from
SM → Legal information, table of contents, switch between the two texts, phone),
the required checkbox of the sign-up (links in a new tab, error without it,
acceptance kept in the account), the information lines under the forms, the
footer links and the search engines.
Usage: python3 tests/e2e-legal.py http://localhost:8080 /path/to/wp out_dir
"""
import json, os, re, subprocess, sys, time, urllib.request
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-legal-output'
os.makedirs(OUT, exist_ok=True)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(php):
    r = subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', php], capture_output=True, text=True)
    return r.stdout.strip()
def get(path):
    try:
        r = urllib.request.urlopen(BASE + path)
        return r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()

saved = wp('echo wp_json_encode( get_option( "sm_legal", null ) );')
wp('SM\\Core\\Legal\\Legal::save_info( array( "version" => "1", "date" => "2026-12-01", "host" => "Hostinger" ) );')
stamp = str(int(time.time()))[-6:]
EMAIL = 'e2e-legal-%s@example.test' % stamp
errors = []
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1280, 'height': 900})
    pg.on('pageerror', lambda e: errors.append(str(e)))

    print('A. Terms of use (French)')
    pg.goto(BASE + '/conditions-generales/?lang=fr')
    check(pg.locator('h1').inner_text().replace('’', "'") == "Conditions générales d'utilisation", 'title')
    check('Version 1' in pg.locator('.sm-legal__meta').first.inner_text() and '1 déc. 2026' in pg.locator('.sm-legal__meta').first.inner_text(), 'version and date in force')
    check(pg.locator('.sm-legal__main h2').count() == 15 and pg.locator('.sm-legal__toc-d li').count() == 15, '15 articles and their table of contents')
    card = pg.locator('.sm-legal__card')
    check(card.count() == 1 and 'ReuSteel' in card.inner_text() and 'contact@reusteel.com' in card.inner_text() and 'Registre' not in card.inner_text(), 'empty fields: ReuSteel and contact@reusteel.com only')
    check(not re.search(r'\{[a-z_]+\}', pg.locator('.sm-legal__main').inner_text()), 'no word left between braces')
    check("tribunaux du lieu du siège" in pg.locator('.sm-legal__main').inner_text(), 'article 15 without city: courts of the registered office')
    sw = pg.locator('.sm-legal__switch a')
    check(sw.count() == 1 and sw.inner_text() == 'Confidentialité' and sw.get_attribute('href').endswith('/confidentialite/'), 'switch to the privacy policy')
    pg.locator('.sm-legal__toc-d a').nth(14).click(); pg.wait_for_timeout(400)
    check(pg.url.endswith('#s15') and pg.locator('#s15').is_visible(), 'table of contents goes to the article')
    links = pg.locator('.sm-legal__main a')
    check(any(links.nth(i).get_attribute('href').endswith('/devenir-ingenieur-partenaire/') for i in range(links.count())), 'link to the partner engineer page')
    pg.screenshot(path=OUT + '/terms-fr.png', full_page=True)

    print('B. Company information filled in the administration')
    wp('SM\\Core\\Legal\\Legal::save_info( array( "company" => "ReuSteel SARL", "legal_form" => "SARL", "rc" => "28/00-0012345B26", "nif" => "002628000000000", "address" => "M’Sila", "court_city" => "M’Sila", "publisher" => "Le gérant", "contact_email" => "contact@reusteel.com", "dpo" => "Le gérant", "dpo_email" => "dpo@reusteel.com", "anpdp" => "R-2026-001", "transfer" => "T-2026-002", "host" => "Hostinger", "version" => "1", "date" => "2026-12-01" ) );')
    pg.goto(BASE + '/conditions-generales/?lang=fr')
    t = pg.locator('.sm-legal__card').inner_text()
    check(all(x in t for x in ('ReuSteel SARL', 'SARL', '28/00-0012345B26', '002628000000000', 'Le gérant')), 'the box shows the filled lines')
    check('tribunaux de M’Sila' in pg.locator('.sm-legal__main').inner_text(), 'article 15: the city of the court')
    pg.goto(BASE + '/confidentialite/?lang=fr')
    m = pg.locator('.sm-legal__main').inner_text()
    check(pg.locator('.sm-legal__main h2').count() == 12 and 'dpo@reusteel.com' in m and 'R-2026-001' in m and 'T-2026-002' in m and 'Hostinger' in m, 'policy: 12 articles, officer, ANPDP receipt, transfer, host')
    check(pg.locator('.sm-legal__switch a').inner_text() == 'Conditions générales', 'switch back to the terms')
    check('sur vos données' in pg.locator('.sm-legal__aside').inner_text(), 'box: a question about your data')

    print('C. Arabic, English, phone')
    ph = b.new_page(viewport={'width': 390, 'height': 844})
    ph.on('pageerror', lambda e: errors.append(str(e)))
    ph.goto(BASE + '/ar/confidentialite/')
    check(ph.locator('html').get_attribute('dir') == 'rtl' and ph.locator('h1').inner_text() == 'سياسة الخصوصية', 'Arabic: rtl, title')
    check(ph.locator('.sm-legal__main bdi', has_text='18-07').count() >= 1, 'Arabic: law number isolated (<bdi>)')
    check(ph.locator('.sm-legal__card dt').first.inner_text() == 'الشركة', 'Arabic: box in Arabic')
    check(ph.evaluate('document.documentElement.scrollWidth') <= 390, 'phone: no horizontal scroll')
    check(ph.locator('.sm-legal__toc-m').is_visible() and not ph.locator('.sm-legal__toc-d').is_visible(), 'phone: table of contents folded at the top')
    ph.locator('.sm-legal__toc-m summary').click(); ph.wait_for_timeout(200)
    check(ph.locator('.sm-legal__toc-m li').count() == 12, 'phone: table of contents opens')
    ph.screenshot(path=OUT + '/privacy-ar-phone.png', full_page=True)
    pg.goto(BASE + '/en/conditions-generales/')
    check(pg.locator('h1').inner_text() == 'Terms of use' and 'courts of M’Sila' in pg.locator('.sm-legal__main').inner_text(), 'English: title, text')

    print('D. Sign-up: the required checkbox')
    pg.goto(BASE + '/inscription/?lang=fr')
    lab = pg.locator('.sm-legal__consent')
    check('18 ans' in lab.inner_text() and lab.locator('a').count() == 2, 'checkbox: two links and 18 years')
    check(all(lab.locator('a').nth(i).get_attribute('target') == '_blank' for i in range(2)) and lab.locator('a').nth(0).get_attribute('href').endswith('/conditions-generales/') and lab.locator('a').nth(1).get_attribute('href').endswith('/confidentialite/'), 'links to the two pages, in a new tab')
    check(not pg.locator('input[name="terms"]').is_checked(), 'not ticked in advance')
    pg.check('input[name="account_class"][value="client"]', force=True)
    pg.fill('#g-name', 'Legal Test')
    pg.select_option('#g-wilaya', '28')
    pg.fill('#g-phone', '07 7' + stamp[0] + ' ' + stamp[1:3] + ' ' + stamp[3:5] + ' ' + stamp[5] + '1')
    pg.fill('#g-email', EMAIL)
    pg.fill('#g-pass', 'Legal-Test-2026!')
    pg.evaluate("document.querySelector('input[name=terms]').removeAttribute('required')")
    pg.click('form[data-sm-register] button[type="submit"]'); pg.wait_for_load_state()
    check('Cochez la case' in pg.content(), 'without the box: error message')
    check(wp('echo email_exists("%s") ? 1 : 0;' % EMAIL) == '0', 'without the box: no account')
    pg.fill('#g-pass', 'Legal-Test-2026!')
    pg.check('input[name="terms"]')
    pg.click('form[data-sm-register] button[type="submit"]'); pg.wait_for_load_state()
    acc = wp('$a = SM\\Core\\Legal\\Legal::acceptance( email_exists("%s") ); echo $a ? $a["version"] . "|" . $a["at"] : "";' % EMAIL)
    check(acc.startswith('1|') and acc[2:12] == time.strftime('%Y-%m-%d', time.gmtime()), 'account created, acceptance kept (version 1, today)')
    ar = b.new_page(viewport={'width': 390, 'height': 844})
    ar.goto(BASE + '/ar/inscription/')
    check('18 سنة' in ar.locator('.sm-legal__consent').inner_text() and ar.locator('.sm-legal__consent a').nth(0).get_attribute('href').endswith('/ar/conditions-generales/'), 'Arabic: checkbox and links to the Arabic pages')
    eng = b.new_page()
    eng.goto(BASE + '/devenir-ingenieur-partenaire/?lang=fr')
    check(eng.locator('input[name="terms"]').count() == 1 and eng.locator('label:has(input[name="terms"]) a').count() == 2, 'partner engineer form: the same checkbox with the two links')

    print('E. Information lines and footer')
    pg.goto(BASE + '/contact/?lang=fr')
    n = pg.locator('.sm-legal__notice')
    check(n.count() == 1 and n.locator('a').get_attribute('href').endswith('/confidentialite/'), 'contact form: information line')
    foot = pg.locator('.sm-footer')
    check(foot.locator('a', has_text='Conditions générales').get_attribute('href').endswith('/conditions-generales/') and foot.locator('a', has_text='Confidentialité').get_attribute('href').endswith('/confidentialite/'), 'footer: Conditions générales · Confidentialité')
    b.close()

print('F. Search engines and administration')
st, h = get('/conditions-generales/?lang=fr')
check(st == 200 and re.search(r"<title>Conditions générales d(&#8217;|’|')utilisation", h) and 'hreflang="ar" href="%s/ar/conditions-generales/"' % BASE in h and 'noindex' not in h, 'title, hreflang, indexed')
st, x = get('/wp-sitemap-smpages-1.xml')
check(x.count('/conditions-generales/</loc>') == 3 and x.count('/confidentialite/</loc>') == 3, 'sitemap: the two pages in 3 languages')
adm = wp('wp_set_current_user(1); ob_start(); SM\\Core\\Admin\\LegalAdmin::page(); echo substr_count(ob_get_clean(), "name=\\"legal[");')
check(adm == '15', 'SM → Legal information: 15 fields')
box = wp('wp_set_current_user(1); ob_start(); SM\\Core\\Admin\\LegalAdmin::texts(get_post(SM\\Core\\Legal\\Legal::page_id("privacy"))); echo substr_count(ob_get_clean(), "<textarea");')
check(box == '3', 'page editor: the text in 3 languages')
check(not errors, 'no script error %s' % errors[:2])

wp('$u = email_exists("%s"); if ( $u ) { require_once ABSPATH . "wp-admin/includes/user.php"; $c = SM\\Core\\Accounts\\Companies::for_user($u); if ($c) { global $wpdb; $wpdb->delete( sm_table("companies"), array("id" => $c->id) ); } wp_delete_user($u); }' % EMAIL)
if saved and saved != 'null':
    wp('update_option( "sm_legal", json_decode( %s, true ), false );' % json.dumps(saved))
else:
    wp('delete_option("sm_legal");')
n = sum(1 for ok, _ in results if ok)
print('\n%d/%d passed' % (n, len(results)))
sys.exit(0 if n == len(results) else 1)
