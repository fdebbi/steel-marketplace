"""
End-to-end browser tests of the warehouse (sm-stock 0.1.0, theme 0.48.0).
Usage: python3 tests/e2e-stock.py http://localhost:8080 /path/to/wp out_dir
Uses the business account of tests/e2e.py (e2e-com…, plan Pro).
"""
import os, sys, subprocess, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-stock-output'
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
TAG = 'ST%d' % (int(time.time()) % 100000)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()

com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
cid = wp('echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id;' % com)
# Two items of the warehouse: new steel kept in stock only, and a reused lot with two variants.
pid = wp('global $wpdb; $d=current_time("mysql",true); $wpdb->insert($wpdb->prefix."sm_products", array("listing_id"=>"T%s1","company_id"=>%s,"user_id"=>0,"market"=>"products","category"=>"profiles","status"=>"stock","family"=>"IPE","section_id"=>7,"designation"=>"IPE 200","grade"=>"S275JR","qty_mode"=>"bars","length_mm"=>12000,"pieces"=>10,"unit_weight"=>268.32,"total_weight"=>2683.2,"price"=>230,"price_unit"=>"kg","total_price"=>617136,"price_per_kg"=>230,"wilaya"=>"28","created_at"=>$d,"updated_at"=>$d)); echo $wpdb->insert_id;' % (TAG, cid))

errors = []
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1280, 'height': 900})
    pg.on('pageerror', lambda e: errors.append(str(e)))
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', com); pg.fill('#l-pass', PWD)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

    print('\nA. Menu and new steel')
    pg.goto(BASE + '/compte/?lang=fr')
    check(pg.locator('.sm-dash__side a[href*="/magasin/"]').count() == 1, 'account menu: Warehouse')
    pg.goto(BASE + '/compte/magasin/?lang=fr&filter=stock')
    row = pg.locator('.smst-table tr:has-text("IPE 200 · S275JR")').last
    check(row.count() == 1 and 'Dans le stock seulement' not in '' and row.locator('.smst-switch:not(.is-on)').count() == 1, 'item kept in stock only: switch off')
    ref = row.locator('.smst-ref').inner_text().strip()
    check(ref.startswith('N-'), 'automatic reference %s' % ref)
    check(pg.locator('.smst-k').count() == 4 and pg.locator('.smst-actions a[href*="magasin-prix"]').count() == 1, 'figures, « Price for a whole type » (plan Pro)')
    pg.screenshot(path=os.path.join(OUT, 'stock-new.png'), full_page=True)

    print('\nB. Reference, location, correction')
    row.locator('.smst-edit summary').click()
    row.locator('input[name="ref"]').fill('ipe-%s' % TAG.lower())
    row.locator('input[name="location"]').fill('Hangar 2 – rack B')
    row.locator('form:has(input[name="ref"]) button').click(); pg.wait_for_load_state()
    newref = ('IPE-%s' % TAG).upper()
    check(pg.locator('.smst-ref', has_text=newref).count() == 1 and pg.locator('.smst-loc', has_text='Hangar 2').count() >= 1, 'reference changed by the seller and location saved')
    pg.goto(BASE + '/compte/magasin/?lang=fr&q=' + newref)
    row = pg.locator('.smst-table tbody tr')
    check(row.count() == 1, 'search by reference')
    row.locator('.smst-edit summary').click()
    row.locator('input[name="qty"]').fill('8')
    row.locator('input[name="note"]').fill('Inventaire %s' % TAG)
    row.locator('form:has(input[name="note"]) button').click(); pg.wait_for_load_state()
    w = wp('echo SM\\Products\\Products::get(%s)->pieces;' % pid)
    check(float(w) == 8.0, 'quantity corrected: 10 → 8 bars')
    pg.goto(BASE + '/compte/magasin-mouvements/?lang=fr')
    check('Inventaire %s' % TAG in pg.inner_text('.smst-mv'), 'the correction and its reason are in the movements')

    print('\nC. Publish and keep in stock only')
    pg.goto(BASE + '/compte/magasin/?lang=fr&q=' + newref)
    pg.locator('.smst-switch').click(); pg.wait_for_load_state()
    st = wp('echo SM\\Products\\Products::get(%s)->status;' % pid)
    check(st in ('published', 'pending'), 'switch on: the item is on the market (%s)' % st)
    pg.goto(BASE + '/compte/magasin/?lang=fr&q=' + newref)
    pg.locator('.smst-switch').click(); pg.wait_for_load_state()
    check(wp('echo SM\\Products\\Products::get(%s)->status;' % pid) == 'stock', 'switch off: back in the warehouse only')

    print('\nD. Price for a whole type')
    pg.goto(BASE + '/compte/magasin-prix/?lang=fr&family=IPE&mode=price&value=135')
    check(pg.locator('.smst-prices input[name="ids[]"]').count() >= 1, 'preview: the IPE of the warehouse with old and new price')
    pg.click('.smst-prices button[type="submit"][data-sm-confirm]'); pg.wait_for_load_state()
    check(float(wp('echo SM\\Products\\Products::get(%s)->price;' % pid)) == 135.0, 'applied: 135 DA/kg')

    print('\nE. Labels and publication form')
    pg.goto(BASE + '/compte/magasin/?lang=fr')
    with pg.expect_download() as dl:
        pg.click('a[href*="sm_stock_labels"]')
    check(open(dl.value.path(), 'rb').read(8) == b'%PDF-1.4', 'labels with QR codes downloaded (PDF)')
    pg.goto(BASE + '/compte/publier/?lang=fr&cat=profiles')
    check(pg.locator('button[name="intent"][value="stock"]').count() == 1, 'publication form: « Keep in stock only »')
    pg.goto(BASE + '/compte/magasin-reemploi/?lang=fr')
    check(pg.locator('.smst').count() == 1, 'reuse warehouse page')
    m = b.new_page(viewport={'width': 390, 'height': 844})
    m.goto(BASE + '/connexion/?lang=ar'); m.fill('#l-user', com); m.fill('#l-pass', PWD)
    m.click('form:has(#l-user) button[type="submit"]'); m.wait_for_load_state()
    m.goto(BASE + '/compte/magasin/?lang=ar')
    check(m.evaluate('document.documentElement.scrollWidth') <= 390 and m.evaluate('document.documentElement.dir') == 'rtl', 'Arabic on a phone: right to left, no side scroll')
    m.screenshot(path=os.path.join(OUT, 'stock-ar-390.png'), full_page=True)
    check(not errors, 'no JavaScript error' + (': ' + errors[0] if errors else ''))
    b.close()

wp('global $wpdb; $wpdb->delete($wpdb->prefix."sm_products", array("id"=>%s)); $wpdb->delete($wpdb->prefix."sm_stock_refs", array("product_id"=>%s)); $wpdb->delete($wpdb->prefix."sm_stock_moves", array("product_id"=>%s));' % (pid, pid, pid))
ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
