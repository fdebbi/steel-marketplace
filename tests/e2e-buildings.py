"""
End-to-end browser tests of the steel buildings market (sm-buildings on the sm-products engine), Phase 6.
Usage: python3 tests/e2e-buildings.py URL WP_PATH OUT
Needs the seller and engineer accounts created by tests/e2e.py (e2e-com…, e2e-eng…).
Expected weights are read from the weight engine, never typed here.
"""
import os, sys, re, subprocess, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-buildings-output'
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
os.makedirs(OUT, exist_ok=True)
TAG = 'B%d' % (int(time.time()) % 100000)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def txt(pg, sel):
    return pg.inner_text(sel).replace(' ', ' ').replace('\xa0', ' ')
def fr(v, d=0):
    return f'{v:,.{d}f}'.replace(',', ' ').replace('.', ',')
def settle(pg):
    pg.wait_for_timeout(800)
    pg.wait_for_function('!document.querySelector("[data-smp-tech][aria-busy]")')
    pg.wait_for_timeout(150)
def pick(pg, sel, label):
    pg.select_option(sel, pg.evaluate('([s, p]) => [...document.querySelectorAll(s + " option")].find(o => o.textContent.trim() === p).value', [sel, label]))
def login(pg, user, pwd):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', pwd)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()
def kgm(fam, sec):
    return float(wp('$r = SM\\Core\\Modules::service("weight")->calculate(array("family"=>"%s","section"=>"%s","bars"=>1,"length"=>1000)); echo $r["total_kg"];' % (fam, sec)))
def publish(pg, title):
    pg.fill('#smp-title-fr', title)
    pg.set_input_files('input[name="photos[]"]', os.path.join(FIX, 'photo.jpg'))
    pg.select_option('#smp-wilaya', '31')
    pg.click('button[name="intent"][value="submit"]'); pg.wait_for_load_state()
    lid = wp('global $wpdb; echo $wpdb->get_var("SELECT listing_id FROM {$wpdb->prefix}sm_products WHERE market = \'buildings\' ORDER BY id DESC LIMIT 1");')
    wp('global $wpdb; SM\\Products\\Products::set_status($wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_products WHERE listing_id = %s"), "published");' % lid)
    return lid

seller = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
eng = wp('echo get_users(array("search"=>"e2e-eng*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
for u in (seller, eng):
    wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % u)
wp('$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID); foreach (SM\\Products\\Products::query(array("status"=>"any","company"=>$c->id,"market"=>"buildings","per_page"=>60))["items"] as $p) SM\\Products\\Products::delete($p->id);' % seller)
w1 = 22 * 4.5 * kgm('HEA', 'HEA 160'); w2 = 22 * 6.2 * kgm('IPE', 'IPE 220')

with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={'width': 390, 'height': 844}, reduced_motion='reduce'); pg = ctx.new_page()
    errors = []; pg.on('pageerror', lambda e: errors.append(str(e)))
    login(pg, seller, 'Acier-2026-test!')

    print('\nA. Assembled hangar (mobile)')
    pg.goto(BASE + '/compte/publier/?market=buildings')
    check(pg.locator('.smp-market').count() == 3 and pg.locator('.smp-market.is-current:has-text("Bâtiments")').count() == 1, 'market chooser: new products, buildings, reuse')
    check(pg.locator('.smp-cat').count() == 6, 'six kinds of buildings')
    pg.click('.smp-cat:has-text("Hangars et bâtiments industriels")'); pg.wait_for_load_state()
    pg.click('.smb-mode-card:has(input[value="assembled"])'); settle(pg)
    for k, v in (('length_m', '40'), ('width_m', '20'), ('height_m', '8'), ('portals', '9'), ('slope', '10')):
        pg.fill('#smb-' + k, v)
    settle(pg)
    check('800 m²' in txt(pg, '.smb-area'), 'area 40 × 20 = 800 m² shown live')
    pg.click('.smp-cond:has(input[value="good"])'); settle(pg)
    pg.select_option('#smp-a-system', 'portal'); pg.select_option('#smp-a-roof', 'duo')
    pg.select_option('#smp-a-roof_cover', 'sandwich'); pg.select_option('#smp-a-cladding', 'ribbed'); settle(pg)
    pg.click('.smp-include:has(#smp-a-inc_dismantling)'); pg.click('.smp-include:has(#smp-a-inc_foundations)'); settle(pg)
    pg.fill('#smp-total', '32000'); pg.fill('#smp-price', '9800000'); settle(pg)
    summ = txt(pg, '.smp-summary')
    check('Prix demandé' in summ and '9 800 000' in summ and '306 DA/kg' in summ and '12 250 DA/m²' in summ, 'asking price → 306 DA/kg and 12 250 DA/m²')
    check(pg.locator('.smp-advice-note').count() == 1, 'seller told about the engineer advice for a used building')
    pg.screenshot(path=os.path.join(OUT, 'publish-assembled-390.png'), full_page=True)
    la = publish(pg, 'Hangar industriel 40 × 20 monté ' + TAG)
    check('/compte/annonces/' in pg.url and la, 'assembled hangar submitted')

    print('\nB. Dismantled building with inventory')
    pg.goto(BASE + '/compte/publier/?market=buildings&cat=bld-livestock')
    pg.click('.smb-mode-card:has(input[value="dismantled"])'); settle(pg)
    for k, v in (('length_m', '60'), ('width_m', '12'), ('height_m', '4,5'), ('portals', '11')):
        pg.fill('#smb-' + k, v)
    settle(pg)
    check(pg.locator('#smp-a-inc_dismantling').count() == 0, '"dismantling included" not offered for a dismantled building')
    pg.click('.smp-cond:has(input[value="excellent"])'); settle(pg)
    pg.select_option('#smp-a-system', 'portal'); pg.select_option('#smp-a-roof', 'duo')
    pg.select_option('#smp-a-roof_cover', 'sandwich'); pg.select_option('#smp-a-cladding', 'none'); settle(pg)
    pg.select_option('#smp-l0-e', 'column'); pg.select_option('#smp-l0-f', 'HEA'); settle(pg)
    pick(pg, '#smp-l0-s', 'HEA 160'); pg.fill('#smp-l0-q', '22'); pg.fill('#smp-l0-l', '4,5'); settle(pg)
    pg.select_option('#smp-l1-e', 'rafter'); pg.select_option('#smp-l1-f', 'IPE'); settle(pg)
    pick(pg, '#smp-l1-s', 'IPE 220'); pg.fill('#smp-l1-q', '22'); pg.fill('#smp-l1-l', '6,2'); settle(pg)
    check(pg.locator('[data-smp-line].is-done').count() == 2 and pg.input_value('#smp-l1-e') == 'rafter', 'two element rows weighed, element types kept')
    pg.fill('#smp-price', '170'); settle(pg)
    summ = txt(pg, '.smp-summary')
    tot = round(w1 + w2, 1)
    check(fr(tot, 0) in summ and '720 m²' in summ and pg.input_value('#smp-punit') == 'kg', 'weight ≈ %s kg from the engine, 720 m², priced per kg by default' % fr(tot, 0))
    pg.screenshot(path=os.path.join(OUT, 'publish-dismantled-390.png'), full_page=True)
    ld = publish(pg, 'Bâtiment d’élevage démonté 60 × 12 ' + TAG)

    print('\nC. Rules')
    pg.goto(BASE + '/compte/publier/?market=buildings&cat=bld-kit')
    pg.click('.smb-mode-card:has(input[value="assembled"])'); settle(pg)
    pg.select_option('#smp-punit', 'kg'); settle(pg)
    check(pg.locator('#err-price_unit').count() == 1, 'an assembled building cannot be priced per kg (live message)')

    print('\nD. Public pages')
    pub = b.new_page(viewport={'width': 1280, 'height': 900}, reduced_motion='reduce')
    pub.goto(BASE + '/batiments/')
    ca = '.sm-card--listing:has-text("monté %s")' % TAG; cd = '.sm-card--listing:has-text("démonté 60 × 12 %s")' % TAG
    check(pub.locator(ca).count() == 1 and pub.locator(cd).count() == 1, 'both buildings listed in the buildings market')
    check('800 m²' in txt(pub, ca) and '40 × 20 × 8 m' in txt(pub, ca), 'card: dimensions and area')
    pub.goto(BASE + '/batiments/?mode=dismantled')
    check(pub.locator(ca).count() == 0 and pub.locator(cd).count() == 1, 'filter assembled / dismantled')
    pub.goto(BASE + '/recherche/?q=' + TAG)
    check(pub.locator(ca).count() == 1, 'found by the general search')
    pub.goto(BASE + '/annonce/%s/' % la)
    spec = txt(pub, '.sm-spec')
    check('Monté' in spec and '40 × 20 × 8 m' in spec and '800 m²' in spec and 'Portiques à âme pleine' in spec and 'Poids d’acier estimé' in spec, 'product page: state, dimensions, area, structural system, estimated weight')
    inc = txt(pub, '.sm-includes')
    check(pub.locator('.sm-includes li.is-yes').count() == 2 and pub.locator('.sm-includes li.is-no').count() == 2, 'what the price includes: dismantling and foundations yes, transport and reinstallation no')
    pb = txt(pub, '.sm-priceblock')
    check('9 800 000' in pb and '306' in pb and '12 250' in pb, 'price block: asking price, per kg, per m²')
    check(pub.locator('.smp-drawing--wide .smb-drawing').count() == 1 and 'pas une étude' in txt(pub, '.smp-drawing figcaption'), 'indicative drawing, marked as not a structural design')
    check(pub.locator('.sm-advice').count() == 1 and 'ingénieur' in txt(pub, '.sm-advice'), 'engineer advice before reassembling')
    pub.screenshot(path=os.path.join(OUT, 'building-fr-1280.png'), full_page=True)
    pub.goto(BASE + '/annonce/%s/' % ld)
    check(pub.locator('.sm-table--stack tbody tr').count() == 2 and 'Poteaux' in txt(pub, '.sm-table--stack') and 'HEA 160' in txt(pub, '.sm-table--stack'), 'dismantled: element inventory with types and profiles')
    ar = b.new_page(viewport={'width': 390, 'height': 844}, reduced_motion='reduce')
    ar.goto(BASE + '/ar/annonce/%s/' % la)
    check(ar.evaluate('document.documentElement.dir') == 'rtl' and 'مهندس' in ar.inner_text('.sm-advice') and 'إطارات بجذع مصمت' in ar.inner_text('.sm-spec'), 'Arabic product page translated')
    ar.screenshot(path=os.path.join(OUT, 'building-ar-390.png'), full_page=True)

    print('\nE. Edit and engineering account')
    pid = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_products WHERE listing_id = %s");' % ld)
    pg.goto(BASE + '/compte/publier/?edit=' + pid)
    check(pg.is_checked('.smb-mode-card input[value="dismantled"]') and pg.input_value('#smb-length_m') == '60' and pg.locator('[data-smp-line].is-done').count() == 2, 'edit reloads state, dimensions and inventory')
    ectx = b.new_context(viewport={'width': 390, 'height': 844}); ep = ectx.new_page()
    login(ep, eng, 'Acier-2026-test!')
    ep.goto(BASE + '/compte/publier/?market=buildings')
    check(ep.locator('.smp-form').count() == 0 and ep.locator('.smp-cats').count() == 0, 'an engineering account gets no publication form')
    ectx.close()
    check(not errors, 'no JavaScript errors' + (': ' + errors[0] if errors else ''))
    b.close()

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
