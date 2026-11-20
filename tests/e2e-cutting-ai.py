"""
End-to-end browser tests of « Read a document » (DQE IA): sm-ai 0.4.0, sm-stock 0.3.0,
sm-weight-engine 0.7.0, theme 0.51.0. One reading, three destinations (cutting optimiser,
request for quotation, warehouse), the weight column, and the common AI waiting screen.
Needs the simulation provider. Usage: python3 tests/e2e-cutting-ai.py http://localhost:8080 /path/to/wp out_dir
"""
import os, sys, subprocess
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-cutting-ai-output'
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
FX = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'plugins', 'sm-ai', 'tests', 'fixtures')
XLSX = os.path.join(FX, 'dqe-test.xlsx')
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()

saved = wp('echo wp_json_encode(get_option("sm_ai_settings", null));')
wp('update_option("sm_ai_settings", array("default_provider"=>"simulation"));')
com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
cid = wp('echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id;' % com)
wp('class_exists("SM\\Subscriptions\\Credits") && SM\\Subscriptions\\Credits::add(%s, 8, "test");' % cid)
START = wp('global $wpdb; echo (int) $wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}sm_products");')
URL = BASE + '/optimiseur-decoupe/?lang=fr'
ACC = BASE + '/compte/assistant-ia/?lang=fr&tool=document'
def norm(t):
    return t.replace('\u202f', ' ').replace('\xa0', ' ')
def row(rev, text):
    return rev.locator('tbody tr', has_text=text).first

errors = []
try:
    with sync_playwright() as p:
        b = p.chromium.launch()
        v = b.new_page(viewport={'width': 1280, 'height': 900})
        v.goto(URL)
        check(v.locator('#smcut-import a[href*="connexion"]').count() == 1, 'visitor: « Import a document » asks to log in')

        pg = b.new_page(viewport={'width': 1280, 'height': 900})
        pg.on('pageerror', lambda e: errors.append(str(e)))
        pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', com); pg.fill('#l-pass', PWD)
        pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

        print('\nA. Waiting screen, reading, weight column')
        heb = wp('echo SM\\WeightEngine\\Catalogue::find("HEB","HEB 100")->id;')
        pg.goto(URL + '&src=1&market=1&rows[0][family]=HEB&rows[0][section]=' + heb + '&rows[0][length]=2,50&rows[0][qty]=2')
        pg.evaluate("() => { const f = document.getElementById('smcut-ai-form'); f.addEventListener('submit', e => e.preventDefault()); f.requestSubmit(); }")
        w = pg.locator('.smcut-form > .sm-panel .sm-aiwait')
        check(w.count() == 1 and 'lit votre document' in w.inner_text() and ' s' in pg.locator('.sm-aiwait__c').inner_text(), 'waiting screen: animated mark, « The AI is reading your document… », seconds')
        check(pg.locator('#smcut-ai-form').get_attribute('aria-busy') == 'true', 'a second sending is blocked (one credit)')
        pg.goto(URL + '&src=1&market=1&rows[0][family]=HEB&rows[0][section]=' + heb + '&rows[0][length]=2,50&rows[0][qty]=2')
        with pg.expect_navigation():
            pg.set_input_files('#smcut-import input[name="document"]', XLSX)
        pg.wait_for_load_state()
        rev = pg.locator('#smcut-ai')
        check(rev.count() == 1 and 'dqe-test.xlsx' in rev.inner_text(), 'the document is read; the check opens in the optimiser')
        bars = rev.locator('table').first.locator('tbody tr')
        check(bars.count() == 7 and bars.locator('input[type="checkbox"]:checked').count() == 2, '7 lines of bars: 2 sure (ticked), the others to check')
        wr = rev.locator('tbody tr[data-dockg="3500"]').first
        check('13,04' in wr.inner_text() and wr.locator('[data-smdoc-qty]').input_value() == '14' and '3 500 kg' in norm(wr.locator('.smdoc-kg').inner_text()), 'IPE 200 : 3 500 kg → 12 m, 13,04 bars → 14 (warehouse 13), weight of the document')
        wr.locator('[data-smdoc-length]').fill('6')
        check(wr.locator('[data-smdoc-qty]').input_value() == '27' and wr.locator('[data-smdoc-stock]').input_value() == '26', 'length 6 m: the number follows (26,09 → 27 to buy, 26 for the warehouse)')
        wr.locator('[data-smdoc-length]').fill('12')
        check(rev.locator('.smdoc-d').count() == 3 and rev.locator('.smdoc-d.is-for button[value="cutting"]').count() == 1, 'three destinations; « Optimise the cutting » first from the optimiser')
        pg.screenshot(path=os.path.join(OUT, 'doc-review.png'), full_page=True)

        print('\nB. To the cutting optimiser')
        uap = row(rev, 'UAP')
        uap.locator('input[type="checkbox"]').check()
        uap.locator('select[data-smdoc-family]').select_option('UPN')
        uap.locator('select[data-smdoc-section]').select_option(label='UPN 100')
        wr.locator('input[type="checkbox"]').check()
        rev.locator('button[value="cutting"]').click(); pg.wait_for_load_state()
        names = [pg.locator('.smcut-form select[name="rows[%d][section]"]' % i).evaluate('e => e.options[e.selectedIndex] ? e.options[e.selectedIndex].text : ""') for i in range(5)]
        check(names == ['HEB 100', 'HEA 200', 'IPE 120', 'UPN 100', 'IPE 200'], 'list: the typed line, then the ticked ones (%s)' % ', '.join(names))
        check(pg.locator('.smcut-form input[name="rows[4][qty]"]').input_value() == '14', 'the line from the weight: 14 bars of 12 m (rounded up to buy)')
        x = pg.locator('.smcut-src.is-x')
        check(x.count() == 1 and '106' in x.inner_text(), 'plates: without a cutting plan, to the request for quotation')

        print('\nC. To a request for quotation')
        pg.goto(BASE + '/compte/mes-devis/?lang=fr&new=1')
        check(pg.locator('.smai-callout a[href*="for=rfq"]').count() == 1, 'request form: « Fill from a document »')
        pg.goto(ACC + '&for=rfq')
        with pg.expect_navigation():
            pg.set_input_files('form:has(input[name="for"]) input[name="document"]', XLSX)
            pg.click('form:has(input[name="for"]) button[type="submit"]')
        pg.wait_for_load_state()
        rev = pg.locator('#smcut-ai')
        check(rev.count() == 1 and rev.locator('.smdoc-d.is-for button[value="rfq"]').count() == 1, 'from the account: check, « Request for quotation » first')
        rev.locator('button[value="rfq"]').click(); pg.wait_for_load_state()
        desc = ' '.join(pg.locator('textarea').evaluate_all('els => els.map(e => e.value)'))
        check('/mes-devis/' in pg.url and 'Tôle 15 mm' in desc and pg.locator('select[name^="lines"][name$="[section]"]').count() >= 2, 'request pre-filled: bars as lines, plates in the description (not published)')

        print('\nD. To the warehouse')
        pg.goto(BASE + '/compte/magasin/?lang=fr')
        check(pg.locator('.smst-actions a[href*="for=stock"]').count() == 1, 'warehouse: « Add from a document »')
        pg.goto(ACC + '&for=stock')
        with pg.expect_navigation():
            pg.set_input_files('form:has(input[name="for"]) input[name="document"]', XLSX)
            pg.click('form:has(input[name="for"]) button[type="submit"]')
        pg.wait_for_load_state()
        rev = pg.locator('#smcut-ai')
        rev.locator('tbody tr[data-dockg="3500"]').first.locator('input[type="checkbox"]').check()
        rev.locator('input[name="location"]').fill('Hangar DQE')
        rev.locator('button[value="stock"]').click(); pg.wait_for_load_state()
        got = wp('global $wpdb; $id=$wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id=%s AND id > %s AND designation=\'IPE 200\' AND status=\'stock\' ORDER BY id DESC LIMIT 1"); $p=SM\\Products\\Products::get($id); echo $p ? $p->pieces." ".$p->total_weight." ".$p->weight_source : "";' % (cid, START))
        check(got == '13 3500 actual', 'IPE 200 in the warehouse: 13 bars (nearest), 3 500 kg of the document kept (%s)' % got)
        check('/magasin/' in pg.url and pg.locator('.smst-loc', has_text='Hangar DQE').count() >= 1, 'in stock only, with references and the location')
        n = int(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_products WHERE company_id=%s AND id > %s AND status=\'stock\'");' % (cid, START)))
        check(n == 5, 'the 3 sure lines + the line from the weight + the plates: %d items' % n)

        print('\nE. Photo')
        pg.goto(URL)
        with pg.expect_navigation():
            pg.set_input_files('#smcut-import input[name="photo"]', os.path.join(FX, 'flou.png'))
        pg.wait_for_load_state()
        check('pu être lu' in pg.inner_text('#smcut-ai'), 'unreadable photo: « take it again flat, with more light »')

        m = b.new_page(viewport={'width': 390, 'height': 844})
        m.goto(BASE + '/connexion/?lang=ar'); m.fill('#l-user', com); m.fill('#l-pass', PWD)
        m.click('form:has(#l-user) button[type="submit"]'); m.wait_for_load_state()
        m.goto(BASE + '/compte/assistant-ia/?lang=ar&tool=document')
        with m.expect_navigation():
            m.set_input_files('form:has(input[name="for"]) input[name="document"]', XLSX)
            m.click('form:has(input[name="for"]) button[type="submit"]')
        m.wait_for_load_state()
        check(m.evaluate('document.documentElement.scrollWidth') <= 390 and m.locator('#smcut-ai').count() == 1, 'Arabic on a phone: check without side scroll')
        m.screenshot(path=os.path.join(OUT, 'doc-ar-390.png'), full_page=True)
        check(not errors, 'no JavaScript error' + (': ' + errors[0] if errors else ''))
        b.close()
finally:
    wp('$v = json_decode(%r, true); null === $v ? delete_option("sm_ai_settings") : update_option("sm_ai_settings", $v);' % saved)
    wp('global $wpdb; foreach($wpdb->get_col("SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id=%s AND id > %s") as $id){ $wpdb->delete($wpdb->prefix."sm_products", array("id"=>$id)); $wpdb->delete($wpdb->prefix."sm_stock_refs", array("product_id"=>$id)); $wpdb->delete($wpdb->prefix."sm_stock_moves", array("product_id"=>$id)); }' % (cid, START))

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
