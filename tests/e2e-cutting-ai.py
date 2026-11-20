"""
End-to-end browser tests of the DQE IA in the cutting optimiser
(sm-ai 0.3.0, sm-weight-engine 0.7.0, theme 0.50.0). Needs the simulation provider.
Usage: python3 tests/e2e-cutting-ai.py http://localhost:8080 /path/to/wp out_dir
"""
import os, sys, subprocess
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-cutting-ai-output'
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
FX = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'plugins', 'sm-ai', 'tests', 'fixtures')
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()

saved = wp('echo wp_json_encode(get_option("sm_ai_settings", null));')
wp('update_option("sm_ai_settings", array("default_provider"=>"simulation"));')
com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
cid = wp('echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id;' % com)
# A few bought AI credits, so that the test does not depend on the credits of the month.
wp('class_exists("SM\\Subscriptions\\Credits") && SM\\Subscriptions\\Credits::add(%s, 6, "test");' % cid)
URL = BASE + '/optimiseur-decoupe/?lang=fr'

errors = []
try:
    with sync_playwright() as p:
        b = p.chromium.launch()
        v = b.new_page(viewport={'width': 1280, 'height': 900})
        v.goto(URL)
        check(v.locator('#smcut-import a[href*="connexion"]').count() == 1 and v.locator('#smcut-ai-form').count() == 0, 'visitor: « Import a document » asks to log in')

        pg = b.new_page(viewport={'width': 1280, 'height': 900})
        pg.on('pageerror', lambda e: errors.append(str(e)))
        pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', com); pg.fill('#l-pass', PWD)
        pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

        print('\nA. Import an Excel document')
        pg.goto(URL + '&src=1&market=1&rows[0][family]=HEB&rows[0][section]=' + wp('echo SM\\WeightEngine\\Catalogue::find("HEB","HEB 100")->id;') + '&rows[0][length]=2,50&rows[0][qty]=2')
        check(pg.locator('#smcut-import input[name="document"][form="smcut-ai-form"]').count() == 1, 'company: import box with file and photo')
        with pg.expect_navigation():
            pg.set_input_files('#smcut-import input[name="document"]', os.path.join(FX, 'dqe-test.xlsx'))
        pg.wait_for_load_state()
        rev = pg.locator('#smcut-ai')
        check(rev.count() == 1 and 'dqe-test.xlsx' in rev.inner_text() and 'ai=' in pg.url, 'the file is read at once; the review opens with its name')
        bars = rev.locator('table').first.locator('tbody tr')
        check(bars.count() == 5 and bars.locator('input[type="checkbox"]:checked').count() == 2, '5 lines of bars: 2 sure (ticked), 3 to check (not ticked)')
        uap = bars.filter(has_text='UAP')
        check(uap.locator('.smcut-ai__why').count() == 1 and uap.locator('select[data-smcut-section]').input_value() == '', 'UAP not in the catalogue: to choose')
        check(rev.locator('.smcut-off').count() == 1 and rev.locator('table').nth(1).locator('tbody tr').count() == 3, 'one quantity in metres and three plate lines')
        check(pg.locator('.smcut-form input[name="rows[0][qty]"]').input_value() == '2', 'the list already typed is kept')
        pg.screenshot(path=os.path.join(OUT, 'dqe-review.png'), full_page=True)

        print('\nB. Check and add')
        uap.locator('input[type="checkbox"]').check()
        uap.locator('select[data-smcut-family]').select_option('UPN')
        uap.locator('select[data-smcut-section]').select_option(label='UPN 100')
        rev.locator('button[type="submit"]').click(); pg.wait_for_load_state()
        rows = [pg.locator('.smcut-form select[name="rows[%d][section]"]' % i).evaluate('e => e.options[e.selectedIndex] ? e.options[e.selectedIndex].text : ""') for i in range(4)]
        check(rows[:4] == ['HEB 100', 'HEA 200', 'IPE 120', 'UPN 100'], 'list: the typed line, then the 3 lines ticked (%s)' % ', '.join(rows))
        check(pg.locator('#smcut-ai').count() == 0 and pg.locator('.smcut-prof').count() == 4, 'review closed, plan computed for the 4 profiles')
        x = pg.locator('.smcut-src.is-x')
        check(x.count() == 1 and 'IPE 160' in x.inner_text() and '7 barres' in x.inner_text() and '106' in x.inner_text(), 'without a cutting plan: 84 m of IPE 160 (7 bars) and the plates with their weight')
        rfq = pg.locator('.smcut-actions a[href*="sm_weight_cut_rfq"]').get_attribute('href')
        check('plates' in rfq and 'lin' in rfq, 'the request for quotation carries the plates and the metres')
        pg.screenshot(path=os.path.join(OUT, 'dqe-added.png'), full_page=True)
        pg.goto(rfq); pg.wait_for_load_state()
        body = ' '.join(pg.locator('textarea').evaluate_all('els => els.map(e => e.value)'))
        check('Tôle 15 mm' in body and '300 × 250' in body, 'request for quotation pre-filled: the plates are in the description')

        print('\nC. Photo')
        pg.goto(URL)
        with pg.expect_navigation():
            pg.set_input_files('#smcut-import input[name="photo"]', os.path.join(FX, 'flou.png'))
        pg.wait_for_load_state()
        check('pu être lu' in pg.inner_text('#smcut-ai'), 'unreadable photo: « take it again flat, with more light »')

        m = b.new_page(viewport={'width': 390, 'height': 844})
        m.goto(BASE + '/connexion/?lang=ar'); m.fill('#l-user', com); m.fill('#l-pass', PWD)
        m.click('form:has(#l-user) button[type="submit"]'); m.wait_for_load_state()
        m.goto(BASE + '/optimiseur-decoupe/?lang=ar')
        with m.expect_navigation():
            m.set_input_files('#smcut-import input[name="document"]', os.path.join(FX, 'dqe-test.xlsx'))
        m.wait_for_load_state()
        check(m.evaluate('document.documentElement.scrollWidth') <= 390 and m.locator('#smcut-ai').count() == 1, 'Arabic on a phone: review without side scroll')
        m.screenshot(path=os.path.join(OUT, 'dqe-ar-390.png'), full_page=True)
        check(not errors, 'no JavaScript error' + (': ' + errors[0] if errors else ''))
        b.close()
finally:
    wp('$v = json_decode(%r, true); null === $v ? delete_option("sm_ai_settings") : update_option("sm_ai_settings", $v);' % saved)

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
