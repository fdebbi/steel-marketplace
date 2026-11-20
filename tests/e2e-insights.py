"""
End-to-end browser tests of the Business report (sm-insights 0.1.0, sm-ai 0.6.0,
sm-subscriptions 0.5.0, sm-products 0.22.0, theme 0.53.0): menu, offer of the Business
plan, filters, AI summary next to the circle of the steel on sale, prices, rankings
(no sales count), searches counted, print to PDF, phone and Arabic.
The plan of the test company is switched to Business during the run and put back.
Usage: python3 tests/e2e-insights.py http://localhost:8080 /path/to/wp out_dir
"""
import os, re, sys, subprocess
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-insights-output'
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def norm(t):
    return t.replace(' ', ' ').replace('\xa0', ' ')

saved = wp('echo wp_json_encode(get_option("sm_ai_settings", null));')
wp('update_option("sm_ai_settings", array("default_provider"=>"simulation"));')
com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
cid = wp('echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id;' % com)
sub = wp('$s = SM\\Subscriptions\\Subscriptions::current(%s); echo $s ? $s->id . "|" . $s->plan : "";' % cid)
SUB_ID, PLAN = (sub.split('|') + [''])[:2] if sub else ('', '')
def set_plan(code):
    if SUB_ID:
        wp('global $wpdb; $wpdb->update($wpdb->prefix."sm_subscriptions", array("plan"=>"%s"), array("id"=>%s));' % (code, SUB_ID))
    else:
        wp('SM\\Subscriptions\\Subscriptions::activate(%s, "%s", 1);' % (cid, code))
    wp('SM\\Insights\\Report::flush();')
REP = BASE + '/compte/rapport-business/?lang=fr'
errors = []
try:
    set_plan('pro')
    with sync_playwright() as p:
        b = p.chromium.launch()
        ctx = b.new_context(viewport={'width': 1280, 'height': 900})
        pg = ctx.new_page()
        pg.on('pageerror', lambda e: errors.append(str(e)))
        v = b.new_page()
        v.goto(REP)
        check('connexion' in v.url, 'visitor: asked to sign in')

        pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', com); pg.fill('#l-pass', PWD)
        pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

        print('\nA. Pro plan: the Business plan is offered')
        pg.goto(REP)
        check(pg.locator('.sm-dash__side a[href*="rapport-business"]').count() == 1, 'menu: « Rapport Business »')
        check(pg.locator('.smbr-upgrade').count() == 1 and 'formule Business' in pg.locator('.smbr-upgrade').inner_text(), 'Pro plan: « included in the Business plan », no figure')
        check(pg.locator('.smbr-upgrade a[href*="abonnement"]').count() == 1 and pg.locator('.smbr-donut').count() == 0, 'button « Move to the Business plan »')

        print('\nB. Business plan')
        set_plan('business')
        before = int(wp('global $wpdb; echo (int) $wpdb->get_var("SELECT SUM(hits) FROM {$wpdb->prefix}sm_search_counts WHERE term = \'IPE 200\' AND day = \'" . gmdate("Y-m-d") . "\'");') or 0)
        s = b.new_page(user_agent='Mozilla/5.0 (X11; Linux x86_64) e2e-insights')
        s.goto(BASE + '/produits/?lang=fr&family=IPE&designation=IPE+200')
        s.goto(BASE + '/produits/?lang=fr&family=IPE&designation=IPE+200')
        after = int(wp('global $wpdb; echo (int) $wpdb->get_var("SELECT SUM(hits) FROM {$wpdb->prefix}sm_search_counts WHERE term = \'IPE 200\' AND day = \'" . gmdate("Y-m-d") . "\'");') or 0)
        check(after == before + 1, 'a search of « IPE 200 » in the catalogue is counted once for a visitor (%d → %d)' % (before, after))
        wp('SM\\Insights\\Report::flush();')
        pg.goto(REP)
        txt = norm(pg.locator('.smbr').inner_text())
        check(pg.locator('.smbr-tag.is-on').count() == 3, 'filters: 30 days, All, Algeria chosen')
        check(pg.locator('.smbr-sum').count() == 1 and pg.locator('.smbr-sum .sm-ai-badge').count() == 1 and pg.locator('.smbr-sum li').count() >= 2, '« What to remember » written by the AI (✦ IA)')
        check(not re.search(r'\d+\s*ventes?', txt, re.I), 'no number of sales anywhere on the page')
        arcs = pg.locator('.smbr-donut__arc').count()
        check(arcs >= 2 and pg.locator('.smbr-donut__lg li').count() == 3, 'circle of the steel on sale: %d arcs, new / reuse / buildings' % arcs)
        lg = norm(pg.locator('.smbr-donut__lg').inner_text())
        check('Acier neuf' in lg and 'Réemploi' in lg and 'Bâtiments' in lg and lg.count(' t') >= 3 and lg.count('%') == 3, 'legend: weight in t and share in % of each market')
        check(pg.evaluate("getComputedStyle(document.querySelector('.smbr-donut__arc')).animationName") == 'smbr-arc', 'the circle is drawn with an animation')
        pg.wait_for_timeout(1200)  # the panels slide in.
        box_s = pg.locator('.smbr-sum').bounding_box(); box_d = pg.locator('.smbr-donut').bounding_box()
        check(abs(box_s['y'] - box_d['y']) < 4 and box_d['x'] > box_s['x'], 'the circle stands next to the summary')
        kp = norm(pg.locator('.smbr-kpis').inner_text())
        check(all(x in kp for x in ['Annonces en ligne', 'Recherches', 'Demandes de devis', 'Bâtiments en vente']) and 'Ventes' not in kp, 'figures: listings, searches, requests, buildings — no « accepted sales »')
        check(pg.locator('.smbr-table tbody tr.smbr-row').count() >= 1 and 'DA/kg' in txt, 'average asked prices (DA/kg) per type')
        if pg.locator('.smbr-more').count():
            pg.locator('.smbr-more').first.click()
            check(pg.locator('.smbr-detail:not([hidden])').count() == 1, 'a type opens its sizes')
        rank = pg.locator('.smbr-rank').first.inner_text()
        check(not re.search(r'\d+\s*(ventes?|sales?)', rank, re.I), 'best-selling: ranking only')
        check('IPE 200' in norm(pg.locator('.smbr-rank.is-bars').inner_text()), 'most searched: IPE 200 listed')
        check('Prix demandés par les vendeurs' in txt or 'prix demandés par les vendeurs' in txt, 'says: prices asked by the sellers, not sale prices')
        pg.screenshot(path=os.path.join(OUT, 'report-1280.png'), full_page=True)

        print('\nC. Filters, PDF')
        pg.click('.smbr-tag:has-text("Neuf")'); pg.wait_for_load_state()
        heads = norm(pg.locator('.smbr-table thead').inner_text()) if pg.locator('.smbr-table').count() else ''
        check('market=new' in pg.url and 'Réemploi' not in heads, 'market New: no reuse column')
        pg.click('.smbr-tag:has-text("Ouest")'); pg.wait_for_load_state()
        check('region=west' in pg.url and pg.locator('.smbr-tag.is-on:has-text("Ouest")').count() == 1 and pg.locator('.smbr-tag.is-on:has-text("Neuf")').count() == 1, 'region West, the market choice kept')
        check(pg.locator('[data-smbr-print]').count() == 1, '« Download as PDF » button')
        pg.goto(REP)
        pg.emulate_media(media='print')
        check(not pg.locator('.sm-dash__side').is_visible() and not pg.locator('.smbr-bar').is_visible() and pg.locator('.smbr-donut').is_visible(), 'PDF (print): menu and filters hidden, the report kept')
        pg.emulate_media(media='screen')

        print('\nD. AI page, phone, Arabic')
        pg.goto(BASE + '/assistant-ia/?lang=fr')
        if pg.locator('#ai-business').count():
            check(pg.locator('#ai-business a[href*="rapport-business"]').count() == 1, 'AI page: « Open the report »')
        m = ctx.new_page(); m.set_viewport_size({'width': 390, 'height': 844})
        m.goto(REP)
        check(m.evaluate('document.documentElement.scrollWidth <= 390'), 'phone 390: no horizontal scroll')
        m.screenshot(path=os.path.join(OUT, 'report-390.png'), full_page=True)
        m.goto(BASE + '/ar/compte/rapport-business/')
        check(m.locator('.smbr-donut').count() == 1 and 'تقرير' in m.locator('h1').inner_text() and m.evaluate('document.documentElement.scrollWidth <= 390'), 'Arabic: the report in RTL')
        b.close()
finally:
    set_plan(PLAN or 'free')
    wp('update_option("sm_ai_settings", json_decode(\'%s\', true));' % (saved or 'null').replace("'", "\\'"))
check(not errors, 'no JavaScript error' + (': ' + '; '.join(errors[:3]) if errors else ''))
ok = sum(1 for r in results if r[0])
print('\n%d/%d passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
