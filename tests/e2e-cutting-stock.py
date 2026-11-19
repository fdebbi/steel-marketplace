"""
End-to-end browser tests of « My warehouse » in the cutting optimiser
(sm-weight-engine 0.6.0, sm-stock 0.2.0, theme 0.49.0).
Usage: python3 tests/e2e-cutting-stock.py http://localhost:8080 /path/to/wp out_dir
Uses the business account of tests/e2e.py (e2e-com…, plan Pro).
"""
import os, sys, subprocess, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-cutting-stock-output'
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
TAG = 'CS%d' % (int(time.time()) % 100000)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()

com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
cid = wp('echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id;' % com)
# IPE 270 (section 10): not used by the other tests. New steel kept in stock only, and a reused lot published.
SID = wp('foreach(SM\\WeightEngine\\Catalogue::sections("IPE") as $s){ if("IPE 270"===$s->designation){ echo $s->id; } }') or '10'
START = wp('global $wpdb; echo (int) $wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}sm_products");')
ins = 'global $wpdb; $d=current_time("mysql",true); $wpdb->insert($wpdb->prefix."sm_products", %s); echo $wpdb->insert_id;'
p1 = wp(ins % ('array("listing_id"=>"T%s1","company_id"=>%s,"user_id"=>0,"market"=>"products","category"=>"profiles","status"=>"stock","family"=>"IPE","section_id"=>%s,"designation"=>"IPE 270","grade"=>"S275JR","qty_mode"=>"bars","length_mm"=>12000,"pieces"=>4,"unit_weight"=>433.2,"total_weight"=>1732.8,"wilaya"=>"28","created_at"=>$d,"updated_at"=>$d)' % (TAG, cid, SID)))
p2 = wp(ins % ('array("listing_id"=>"T%s2","company_id"=>%s,"user_id"=>0,"market"=>"reuse","category"=>"reuse-offcuts","status"=>"published","item_condition"=>"good","designation"=>"IPE 270","qty_mode"=>"bars","pieces"=>3,"total_weight"=>223.8,"price"=>140,"price_unit"=>"kg","wilaya"=>"28","inventory"=>wp_json_encode(array(array("family"=>"IPE","section_id"=>%s,"designation"=>"IPE 270","qty"=>3,"length_mm"=>2060,"kg_m"=>36.1,"total"=>223.1))),"created_at"=>$d,"updated_at"=>$d)' % (TAG, cid, SID)))
URL = BASE + '/optimiseur-decoupe/?lang=fr&rows[0][family]=IPE&rows[0][section]=%s&rows[0][length]=5,40&rows[0][qty]=4&rows[1][family]=IPE&rows[1][section]=%s&rows[1][length]=2,00&rows[1][qty]=3' % (SID, SID)

errors = []
with sync_playwright() as p:
    b = p.chromium.launch()
    v = b.new_page(viewport={'width': 1280, 'height': 900})
    v.goto(URL)
    check(v.locator('.smcut-chk.is-off:has-text("Mon magasin") a[href*="connexion"]').count() == 1 and v.locator('.smcut-src.is-st').count() == 0, 'visitor: « My warehouse » off, with a link to log in')

    pg = b.new_page(viewport={'width': 1280, 'height': 900})
    pg.on('pageerror', lambda e: errors.append(str(e)))
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', com); pg.fill('#l-pass', PWD)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

    print('\nA. The warehouse first')
    pg.goto(URL)
    check(pg.locator('input[name="mine"]').is_checked() and pg.locator('[data-smcut-minebox]').is_visible(), 'company: « My warehouse » ticked by default, with its choices')
    src = pg.locator('.smcut-src.is-st')
    check(src.count() == 1 and 'N-' in src.inner_text() and 'R-' in src.inner_text(), 'result « My warehouse » first, with the references (new and reuse)')
    check(pg.locator('.smcut-k.is-st').count() == 1 and pg.locator('.smcut-lbl.is-st').count() >= 2, 'figure « In my warehouse » and bars of the plan labelled « My warehouse · ref »')
    first = pg.locator('.smcut-result > section, .smcut-result > .smcut-totals').first
    check(pg.evaluate("[...document.querySelectorAll('.smcut-src')].map(s=>s.className)[0]").find('is-st') >= 0, 'warehouse listed before the marketplace and the bars to buy')
    check(pg.locator('.smcut-src.is-off .smcut-off').count() >= 1, 'reusable offcuts listed, ready to add to the warehouse')
    pg.add_style_tag(content='.smcut-form{position:static !important}')
    pg.screenshot(path=os.path.join(OUT, 'optimiser-stock.png'), full_page=True)

    print('\nB. Options')
    pg.goto(URL + '&src=1&market=1&mine=1&mine_new=1')
    t = pg.locator('.smcut-src.is-st').inner_text() if pg.locator('.smcut-src.is-st').count() else ''
    check('N-' in t and 'R-' not in t, 'new steel only: the reused lot is left out')
    pg.goto(URL + '&src=1&market=1&mine=1&mine_new=1&mine_reuse=1&mine_unpublished=1')
    t = pg.locator('.smcut-src.is-st').inner_text() if pg.locator('.smcut-src.is-st').count() else ''
    check('N-' in t and 'R-' not in t, '« do not take the published items »: the published lot is left out')
    pg.goto(URL + '&src=1&market=1')
    check(pg.locator('.smcut-src.is-st').count() == 0, 'box unticked: the warehouse is not searched')

    print('\nC. Take the bars out, add the offcuts')
    pg.goto(URL)
    n = int(pg.locator('.smcut-k.is-st b').inner_text().split()[0])
    pg.click('.smcut-out button[type="submit"]')
    if pg.locator('.sm-dialog [data-sm-dialog-ok], .sm-dialog__ok').count():
        pg.locator('.sm-dialog [data-sm-dialog-ok], .sm-dialog__ok').first.click()
    pg.wait_for_load_state()
    left = float(wp('echo SM\\Products\\Products::get(%s)->pieces;' % p1)) + float(wp('echo SM\\Products\\Products::get(%s)->pieces;' % p2))
    check('magasin-mouvements' in pg.url and left == 7 - n, 'confirmed: %d bars taken out (left %d), back to the movements' % (n, left))
    check(pg.locator('.smst-mv:has-text("Sortie pour découpe")').count() >= 1 or 'Sortie pour découpe' in pg.inner_text('body'), 'movement « Taken for a cutting » with the reference')
    pg.goto(URL)
    before = wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_products WHERE company_id=%s AND market=\'reuse\' AND status=\'stock\'");' % cid)
    if pg.locator('.smcut-offform').count():
        pg.fill('.smcut-offform input[name="location"]', 'Hangar %s' % TAG)
        pg.click('.smcut-offform button[type="submit"]'); pg.wait_for_load_state()
    after = wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_products WHERE company_id=%s AND market=\'reuse\' AND status=\'stock\'");' % cid)
    check(int(after) > int(before) and 'magasin-reemploi' in pg.url and pg.locator('.smst-loc', has_text='Hangar %s' % TAG).count() >= 1, 'offcuts added to the reuse warehouse, in stock only, with their location')
    newp = wp('global $wpdb; echo $wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}sm_products WHERE company_id=%s AND market=\'reuse\' AND status=\'stock\'");' % cid)

    print('\nD. Arabic on a phone')
    m = b.new_page(viewport={'width': 390, 'height': 844})
    m.goto(BASE + '/connexion/?lang=ar'); m.fill('#l-user', com); m.fill('#l-pass', PWD)
    m.click('form:has(#l-user) button[type="submit"]'); m.wait_for_load_state()
    m.goto(URL.replace('lang=fr', 'lang=ar'))
    check(m.evaluate('document.documentElement.scrollWidth') <= 390 and m.locator('.smcut-k').count() >= 3, 'Arabic on a phone: no side scroll')
    m.add_style_tag(content='.smcut-form{position:static !important}')
    m.screenshot(path=os.path.join(OUT, 'optimiser-stock-ar-390.png'), full_page=True)
    check(not errors, 'no JavaScript error' + (': ' + errors[0] if errors else ''))
    b.close()

wp('global $wpdb; foreach($wpdb->get_col("SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id=%s AND id > %s") as $id){ $wpdb->delete($wpdb->prefix."sm_products", array("id"=>$id)); $wpdb->delete($wpdb->prefix."sm_stock_refs", array("product_id"=>$id)); $wpdb->delete($wpdb->prefix."sm_stock_moves", array("product_id"=>$id)); }' % (cid, START))
ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
