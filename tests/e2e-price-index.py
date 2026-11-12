"""
End-to-end browser tests of the steel price curve (sm-products 0.9.0 + theme 0.23.0)
and of the plans page by kind of account.

Usage: python3 tests/e2e-price-index.py URL WP_PATH OUT

The price index settings and reference prices are saved before the run and put
back at the end. Creates a professional (e2ep-pro…) and a client (e2ep-cli…);
accounts of the previous run are removed first. Admin: admin / Admin-2026-test!
(env SM_ADMIN="user:password").
"""
import json, os, subprocess, sys, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-price-index-output'
os.makedirs(OUT, exist_ok=True)
stamp = str(int(time.time()))[-6:]
PWD = 'Acier-2026-test!'
PRO = f'e2ep-pro{stamp}@example.test'
CLI = f'e2ep-cli{stamp}@example.test'
ADMIN = os.environ.get('SM_ADMIN', 'admin:Admin-2026-test!').split(':', 1)

results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def norm(s):
    return ' '.join(s.split()).replace(' ', ' ').replace('\xa0', ' ')
def txt(pg, sel='body'):
    return norm(pg.inner_text(sel))
def shot(pg, name, full=True):
    pg.screenshot(path=os.path.join(OUT, name + '.png'), full_page=full)
def trend(pg):
    return pg.locator('.sm-trend').first
def login(pg, user, pwd=PWD):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', pwd)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

# Save the index, start from "automatic off, no reference price".
saved = wp('echo wp_json_encode(array("s"=>get_option("sm_products_price_index",null),"r"=>get_option("sm_products_price_refs",null)));')
wp('update_option("sm_products_price_index", array("auto"=>0,"min_listings"=>5,"min_weeks"=>4), false); delete_option("sm_products_price_refs"); SM\\Products\\PriceTrend::flush();')

# Accounts of the previous run, then a professional and a client.
wp('''require_once ABSPATH."wp-admin/includes/user.php"; global $wpdb;
foreach (get_users(array("search"=>"e2ep-*","search_columns"=>array("user_email"))) as $u) {
  $c = SM\\Core\\Accounts\\Companies::for_user($u->ID);
  if ($c) { foreach (array("sm_subscriptions","sm_company_members","sm_company_activities","sm_notifications") as $t) { $wpdb->delete($wpdb->prefix.$t, array("company_id"=>$c->id)); }
    $wpdb->delete($wpdb->prefix."sm_companies", array("id"=>$c->id)); }
  wp_delete_user($u->ID); }''')
for mail, cls, act in ((PRO, 'professional', 'trade_welder'), (CLI, 'client', 'client')):
    wp('$u = wp_insert_user(array("user_login"=>"%s","user_email"=>"%s","user_pass"=>"%s","role"=>"sm_member")); update_user_meta($u, "sm_locale", "fr"); $c = SM\\Core\\Accounts\\Companies::create(array("account_class"=>"%s","name"=>"E2EP %s %s","wilaya"=>"28","phone"=>"0550 12 34 56"), $u, array("%s")); SM\\Core\\Accounts\\Companies::raise_level($c, 1);' % (mail, mail, PWD, cls, cls, stamp, act))

errors = []
with sync_playwright() as p:
    b = p.chromium.launch()
    v = b.new_page(viewport={'width': 1440, 'height': 900})
    v.on('pageerror', lambda e: errors.append(str(e)))

    print('A. No price yet')
    v.goto(BASE + '/?lang=fr')
    t = trend(v)
    box0 = t.bounding_box()
    check(t.count() == 1 and 'Prix bientôt disponible' in txt(v, '.sm-trend') and 'DA/kg' not in txt(v, '.sm-trend'), 'home: the curve is shown, "Price coming soon", no invented price')
    check(v.locator('.sm-trend__guide').count() == 1 and v.locator('.sm-trend__line').count() == 0, 'no data: dashed guide only, no price line')
    check(t.get_attribute('data-anim') in ('wait', 'go'), 'entrance animation armed')
    time.sleep(2.6)
    box1 = t.bounding_box()
    check(t.get_attribute('data-anim') == 'go' and abs(box0['height'] - box1['height']) < 0.5 and abs(box0['y'] - box1['y']) < 0.5, 'animation played without moving the page (same box before and after)')
    shot(v, 'a-none', False)

    print('B. Administrator: reference prices')
    a = b.new_page(viewport={'width': 1280, 'height': 900})
    a.goto(BASE + '/wp-login.php'); a.fill('#user_login', ADMIN[0]); a.fill('#user_pass', ADMIN[1]); a.click('#wp-submit'); a.wait_for_load_state()
    a.goto(BASE + '/wp-admin/admin.php?page=sm-products&tab=prices')
    check('Prix de référence' in txt(a) or 'Reference prices' in txt(a), 'admin: Price index tab')
    check(a.locator('input[name="auto"]').count() == 1 and a.locator('#smp-min-listings').count() == 1 and a.locator('#smp-min-weeks').count() == 1, 'admin: automatic average switch and both thresholds')
    a.fill('#smp-ref-date', '2026-11-02'); a.fill('#smp-ref-price', '0'); a.click('form:has(#smp-ref-price) [type="submit"]'); a.wait_for_load_state()
    check(a.locator('.notice-error').count() == 1, 'admin: price 0 refused with a message')
    a.fill('#smp-ref-date', '2026-11-02'); a.fill('#smp-ref-price', '125'); a.fill('#smp-ref-source', 'Relevé test 3 négociants'); a.click('form:has(#smp-ref-price) [type="submit"]'); a.wait_for_load_state()
    check(a.locator('.notice-success').count() == 1 and '125' in txt(a, 'table.widefat:last-of-type'), 'admin: reference price saved and listed')

    v.goto(BASE + '/?lang=fr'); time.sleep(2.6)
    tt = txt(v, '.sm-trend')
    check('125 DA/kg' in tt and 'Prix de référence ReuSteel' in tt and 'Relevé le' in tt and 'Relevé test 3 négociants' in tt, 'home: price of the week, labelled as a ReuSteel reference price, with date and source')
    check(v.locator('.sm-trend__pt').count() == 1 and v.locator('.sm-trend__guide').count() == 1 and 'La courbe se construit' in tt, 'one price: one point at the end of a dashed guide, "the curve builds up"')
    shot(v, 'b-one', False)

    a.fill('#smp-ref-date', '2026-11-09'); a.fill('#smp-ref-price', '131,5'); a.fill('#smp-ref-source', ''); a.click('form:has(#smp-ref-price) [type="submit"]'); a.wait_for_load_state()
    v.goto(BASE + '/?lang=fr'); time.sleep(3.4)
    tt = txt(v, '.sm-trend')
    check(v.locator('.sm-trend__line').count() == 1 and v.locator('.sm-trend__pt').count() == 2 and '131,50 DA/kg' in tt and '+5,2 %' in tt, 'two prices: solid curve, latest price on the card, change shown')
    d = v.get_attribute('.sm-trend__line', 'd') or ''
    check(' C' in d, 'smooth curve (cubic path)')
    box = v.locator('.sm-trend__tag').bounding_box(); plot = v.locator('.sm-trend__plot').bounding_box(); last = v.locator('.sm-trend__pt--last').bounding_box()
    check(box['y'] >= plot['y'] - 1 and box['y'] + box['height'] <= last['y'] + 2 and box['x'] + box['width'] <= plot['x'] + plot['width'] + 2, 'price card attached above the last point, inside the chart')
    shot(v, 'c-two', False)

    print('C. Price index page, Arabic, phone, reduced motion')
    v.goto(BASE + '/indice-prix/?lang=fr')
    check(v.locator('figure.sm-trend--page').count() == 1 and '131,50 DA/kg' in txt(v, '.sm-trend'), 'price index page: same price, same source')
    m = b.new_page(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True)
    m.on('pageerror', lambda e: errors.append(str(e)))
    m.goto(BASE + '/?lang=ar'); time.sleep(2.6)
    tm = txt(m, '.sm-trend')
    check('السعر المرجعي' in tm and 'دج/' in tm and 'رُصد في' in tm, 'Arabic: label, currency and date translated')
    check(m.evaluate('document.documentElement.scrollWidth') <= 391, 'phone: no horizontal scroll')
    lp = m.locator('.sm-trend__pt--last').bounding_box(); pl = m.locator('.sm-trend__plot').bounding_box()
    check(lp['x'] > pl['x'] + pl['width'] * 0.8, 'Arabic: time still runs left to right, latest point on the right')
    shot(m, 'd-ar-mobile', False)
    rm = b.new_context(viewport={'width': 1280, 'height': 900}, reduced_motion='reduce').new_page()
    rm.goto(BASE + '/?lang=fr')
    check(rm.locator('.sm-trend').first.get_attribute('data-anim') is None and rm.locator('.sm-trend__tag').evaluate('e => getComputedStyle(e).opacity') == '1', 'reduced motion: no animation, everything shown at once')

    print('D. Plans page by kind of account')
    v.goto(BASE + '/tarifs/?lang=fr')
    check(v.locator('#tab-company[aria-selected="true"]').count() == 1 and v.locator('.sm-pricing__you').count() == 0 and v.locator('.sm-panel--lead').count() == 0, 'visitor: Companies tab first, both tabs, no account badge')
    pc = b.new_context(viewport={'width': 1280, 'height': 900}).new_page()
    login(pc, PRO); pc.goto(BASE + '/tarifs/?lang=fr')
    check(pc.locator('#tab-professional[aria-selected="true"]').count() == 1 and pc.locator('#tab-professional .sm-pricing__you').count() == 1, 'professional: own tab opens first, marked "your account"')
    check(pc.locator('#plans-professional .sm-plan.is-current .sm-plan__name').inner_text().strip() == 'Gratuit' and 'Gérer ma formule' in txt(pc, '#plans-professional .sm-plan.is-current'), 'professional: plan in force marked, "Manage my plan"')
    check('Pour les comptes entreprises' in pc.locator('#plans-company').inner_text(), 'professional: company plans still visible, without order button')
    pc.click('#tab-company')
    check(pc.locator('#plans-company').is_visible(), 'professional: the other tab still opens')
    shot(pc, 'e-pro-pricing', False)
    cc = b.new_context(viewport={'width': 1280, 'height': 900}).new_page()
    login(cc, CLI); cc.goto(BASE + '/tarifs/?lang=fr')
    check(cc.locator('.sm-panel--lead').count() == 1 and cc.locator('.sm-pricing__mine').evaluate('e => e.compareDocumentPosition(document.querySelector(".sm-pricing__tabs")) & 4') == 4, 'client: AI credit packs first, before the plans')
    check('/credits-ia' in (cc.get_attribute('.sm-panel--lead a.sm-btn', 'href') or ''), 'client: "Buy AI credits" leads to the AI credits page')
    shot(cc, 'f-client-pricing', False)

    check(not errors, 'no JavaScript error' + ('' if not errors else ': ' + '; '.join(errors[:3])))
    b.close()

# Put the index back, remove the accounts.
data = json.loads(saved or '{}')
open(os.path.join(OUT, '.index.json'), 'w').write(json.dumps(data))
wp('$d = json_decode(file_get_contents("%s"), true); null === $d["s"] ? delete_option("sm_products_price_index") : update_option("sm_products_price_index", $d["s"], false); null === $d["r"] ? delete_option("sm_products_price_refs") : update_option("sm_products_price_refs", $d["r"], false); SM\\Products\\PriceTrend::flush();' % os.path.abspath(os.path.join(OUT, '.index.json')))

ok = sum(1 for r in results if r[0])
print(f'\n{ok} passed, {len(results) - ok} failed')
sys.exit(0 if ok == len(results) else 1)
