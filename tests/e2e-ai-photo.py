"""
End-to-end browser tests of the AI photo tools of the publication form (sm-ai 0.5.0,
theme 0.52.0): « Recognise from my photos » (engine categories, not plates) and
« Assess the condition from my photos » (reuse, not scrap). Nothing is filled without a
click; one credit per call; the common AI wait screen. Needs the simulation provider.
Usage: python3 tests/e2e-ai-photo.py http://localhost:8080 /path/to/wp out_dir
"""
import os, sys, subprocess
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-ai-photo-output'
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
PHOTO = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'plugins', 'sm-ai', 'tests', 'fixtures', 'flou.png')
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def settle(pg):
    pg.wait_for_timeout(750)
    pg.wait_for_function('!document.querySelector("[data-smp-tech][aria-busy]")')
    pg.wait_for_timeout(150)

saved = wp('echo wp_json_encode(get_option("sm_ai_settings", null));')
wp('update_option("sm_ai_settings", array("default_provider"=>"simulation"));')
com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
cid = wp('echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id;' % com)
wp('class_exists("SM\\Subscriptions\\Credits") && SM\\Subscriptions\\Credits::add(%s, 6, "test");' % cid)
PUB = BASE + '/compte/publier/?lang=fr'
errors = []
try:
    with sync_playwright() as p:
        b = p.chromium.launch()
        pg = b.new_page(viewport={'width': 1280, 'height': 900})
        pg.on('pageerror', lambda e: errors.append(str(e)))
        pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', com); pg.fill('#l-pass', PWD)
        pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

        print('\nA. Where the tools are offered')
        pg.goto(PUB + '&market=reuse&cat=reuse-profiles')
        check(pg.locator('[data-smaip-run="recognize"]').count() == 1 and pg.locator('[data-smaip-run="condition"]').count() == 1, 'reuse profiles: recognise and condition')
        check('1 crédit' in pg.locator('[data-smaip-run="recognize"]').inner_text(), 'the button says the price: 1 credit')
        check(pg.locator('.smaip__warn a[href*="ingenierie"]').count() == 1, 'condition: visual estimate, link to an engineer inspection')
        pg.goto(PUB + '&market=reuse&cat=reuse-sheets')
        check(pg.locator('[data-smaip-run="recognize"]').count() == 0 and pg.locator('[data-smaip-run="condition"]').count() == 1, 'reuse plates: no recognition, condition yes')
        pg.goto(PUB + '&market=reuse&cat=reuse-scrap')
        check(pg.locator('[data-smaip]').count() == 0, 'scrap: no AI photo tool')
        pg.goto(PUB + '&market=products&cat=profiles')
        check(pg.locator('[data-smaip-run="recognize"]').count() == 1 and pg.locator('[data-smaip-run="condition"]').count() == 0, 'new steel profiles: recognition only')

        print('\nB. Recognise from my photos')
        pg.goto(PUB + '&market=reuse&cat=reuse-profiles')
        pg.click('[data-smaip-run="recognize"]')
        out = pg.locator('[data-smaip-tool="recognize"] [data-smaip-out]')
        pg.wait_for_selector('[data-smaip-tool="recognize"] .smaip__msg')
        check('photos' in out.inner_text().lower(), 'no photo: « Add your photos first », no call')
        pg.set_input_files('input[type="file"][name="photos[]"]', PHOTO)
        before = wp('echo (int) SM\\Ai\\Usage::company_month(%s, "recognize");' % cid)
        pg.evaluate("() => { window.__f = window.fetch; window.fetch = (...a) => new Promise(r => setTimeout(() => r(window.__f(...a)), 900)); }")
        pg.click('[data-smaip-run="recognize"]')
        check(pg.locator('[data-smaip-tool="recognize"] .sm-aiwait').count() == 1, 'common AI wait screen on the tool')
        check(pg.locator('[data-smaip-run="recognize"]').is_disabled(), 'the button is blocked during the call (one credit)')
        pg.wait_for_selector('[data-smaip-tool="recognize"] .smaip__cand')
        check(pg.locator('[data-smaip-tool="recognize"] .sm-aiwait').count() == 0, 'wait screen gone')
        after = wp('echo (int) SM\\Ai\\Usage::company_month(%s, "recognize");' % cid)
        check(int(after) == int(before) + 1, 'one use counted (%s → %s)' % (before, after))
        cards = pg.locator('.smaip__cand')
        check(cards.count() == 2, 'only catalogue profiles shown (unknown family dropped): %d' % cards.count())
        check('IPE 200' in cards.nth(0).inner_text() and 'Probable' in cards.nth(0).inner_text(), 'first: IPE 200, likely')
        check('dimension' in cards.nth(1).inner_text().lower() or 'HEA' in cards.nth(1).inner_text(), 'second: HEA, size to choose')
        check(pg.locator('.smaip__q li').count() == 2, 'questions to remove the doubt')
        check(pg.locator('input[name="family"]:checked').count() == 0 or pg.locator('input[name="family"][value="IPE"]:checked').count() == 0, 'nothing filled before the click')
        cards.nth(0).locator('button').click(); settle(pg)
        check(pg.locator('input[name="family"][value="IPE"]:checked').count() == 1, '« Choose »: type IPE set')
        sel = pg.evaluate('() => { const s = document.querySelector("#smp-section"); return s ? s.options[s.selectedIndex].text : ""; }')
        check(sel.startswith('IPE 200'), '« Choose »: size IPE 200 set (weight from the engine): ' + sel)
        check('is-chosen' in (cards.nth(0).get_attribute('class') or ''), 'the card shows « Chosen »')
        pg.screenshot(path=os.path.join(OUT, 'recognize-1280.png'), full_page=False)

        print('\nC. Assess the condition')
        pg.click('[data-smaip-run="condition"]')
        pg.wait_for_selector('.smaip__sugg')
        sug = pg.locator('.smaip__sugg')
        check(sug.locator('.smaip__chip.is-on').first.inner_text().startswith('Bon') or 'bon' in sug.locator('.smaip__chip.is-on').first.inner_text().lower(), 'suggestion: good')
        check('Rouille' in sug.inner_text() and 'Épaisseur restante' in sug.inner_text(), 'surface seen, what the AI sees and cannot see')
        check(pg.locator('input[name="condition"][value="good"]:checked').count() == 0, 'nothing filled before « Apply »')
        sug.locator('.sm-btn--primary').click(); settle(pg)
        check(pg.locator('input[name="condition"][value="good"]:checked').count() == 1, '« Apply »: condition good')
        check(pg.evaluate('() => (document.querySelector(\'select[name="attrs[surface]"]\')||{}).value') == 'light_rust', '« Apply »: surface light rust')
        check(pg.locator('input[name="family"][value="IPE"]:checked').count() == 1, 'the chosen profile is kept')
        pg.screenshot(path=os.path.join(OUT, 'condition-1280.png'), full_page=True)
        m = b.new_page(viewport={'width': 390, 'height': 844}, device_scale_factor=2)
        ctx_cookies = pg.context.cookies(); m.context.add_cookies(ctx_cookies)
        m.goto(PUB + '&market=reuse&cat=reuse-profiles')
        check(m.evaluate('() => document.documentElement.scrollWidth <= 390'), 'phone 390: no horizontal scroll')
        m.locator('.smaip').screenshot(path=os.path.join(OUT, 'tools-390.png'))
        b.close()
finally:
    wp('update_option("sm_ai_settings", json_decode(\'%s\', true));' % (saved or 'null').replace("'", "\\'"))
check(not errors, 'no JavaScript error' + (': ' + '; '.join(errors[:3]) if errors else ''))
ok = sum(1 for r in results if r[0])
print('\n%d/%d passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
