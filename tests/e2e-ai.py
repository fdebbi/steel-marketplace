"""
End-to-end browser tests of the AI assistant (sm-ai 0.1.0), Phase 9.
Usage: python3 tests/e2e-ai.py URL WP_PATH OUT
Needs SM_AI_SIMULATION in wp-config.php (simulation provider: no real AI is called).
Uses the accounts created by tests/e2e.py (e2e-com…, e2e-eng…). Admin: admin / Admin-2026-test!.
"""
import os, sys, re, json, subprocess
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-ai-output'
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def login(pg, user, pwd=PWD):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', pwd)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

if wp('echo SM\\Ai\\Settings::simulation_allowed() ? "yes" : "no";') != 'yes':
    print('SM_AI_SIMULATION must be true in wp-config.php'); sys.exit(1)
before = wp('echo wp_json_encode(get_option("sm_ai_settings", null));')
wp('delete_option("sm_ai_settings"); global $wpdb; $wpdb->query("DELETE FROM {$wpdb->prefix}sm_ai_usage WHERE ip = \'127.0.0.1\'");')
com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
eng = wp('echo get_users(array("search"=>"e2e-eng*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
# The tests use AI credits: the business account gets enough of them for this run (sm-subscriptions).
wp('if ( class_exists("SM\\Subscriptions\\Credits") ) { $u = get_user_by("email", "%s"); $c = SM\\Core\\Accounts\\Companies::for_user($u->ID); $c = is_array($c) ? reset($c) : $c; $id = is_object($c) ? (int) $c->id : (int) $c; $st = SM\\Subscriptions\\Credits::status($id); if ( $st["total_left"] < 20 ) { SM\\Subscriptions\\Credits::add($id, 30, "test"); } }' % com)
for u in (com, eng):
    wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % u)
ipe = json.loads(wp('$s = SM\\Ai\\Catalog::section("IPE","IPE 200"); echo wp_json_encode(array("id"=>$s->id));'))

with sync_playwright() as p:
    b = p.chromium.launch()
    errors = []
    def page(w=1280, h=900):
        pg = b.new_context(viewport={'width': w, 'height': h}, reduced_motion='reduce').new_page()
        pg.on('pageerror', lambda e: errors.append(str(e))); pg.on('dialog', lambda d: d.accept())
        return pg

    print('\nA. Administration')
    a = page()
    login(a, 'admin', 'Admin-2026-test!')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-ai')
    check(a.locator('select[name="default_provider"]').count() == 1 and 'Not available' in a.inner_text('.wrap') or 'Indisponible' in a.inner_text('.wrap'), 'AI off until a provider is configured')
    check(a.locator('input[type="password"][name="providers[anthropic][key]"]').count() == 1 and a.locator('input[name="providers[openai][base_url]"]').count() == 1, 'key fields (password type) for Anthropic and OpenAI-compatible APIs')
    a.select_option('select[name="default_provider"]', 'simulation')
    a.click('form:has(input[value="sm_ai_settings"]) #submit'); a.wait_for_load_state()
    check(a.locator('select[name="default_provider"]').input_value() == 'simulation' and a.locator('.wrap td span[style*="065f46"]').count() == 8, 'simulation chosen: the 8 services available (DQE IA since 0.3.0, condition since 0.5.0, report summary since 0.6.0)')
    a.click('form:has(input[value="simulation"]) button'); a.wait_for_load_state()
    check(a.locator('.notice-success').count() >= 1 and 'test=ok' in a.url, 'connection test')
    a.screenshot(path=os.path.join(OUT, 'admin-ai-settings.png'), full_page=True)

    print('\nB. Listing assistant (business account)')
    s = page()
    login(s, com)
    s.goto(BASE + '/compte/')
    check(s.locator('.sm-dash__side a[href*="assistant-ia"]').count() == 1, 'dashboard menu: AI assistant')
    s.goto(BASE + '/compte/publier/')
    check(s.locator('.smai-callout a[href*="tool=listing"]').count() == 1, 'publish form offers to prepare the listing with the AI')
    s.goto(BASE + '/compte/assistant-ia/')
    check(s.locator('.smai-tab').count() == 3 and s.locator('.smai-badge--sim').count() >= 1 and s.locator('.smai-disclaimer').count() == 1, 'three tools, simulation marked, warning shown')
    s.click('.smai-form button[type="submit"]'); s.wait_for_load_state()
    check(s.locator('#err-notes').count() == 1, 'nothing given: asks for photos or notes')
    s.set_input_files('input[name="photos[]"]', os.path.join(FIX, 'photo.jpg'))
    s.fill('#smai-notes', '12 barres HEA 160 S355JR neuves, 12 m, à Rouiba')
    s.click('.smai-form button[type="submit"]'); s.wait_for_load_state()
    res = s.inner_text('.smai-result') if s.locator('.smai-result').count() else ''
    check('HEA 160' in res and 'S355JR' in res and 'Profilés' in res, 'suggestion: category, profile and grade')
    check('kg/m (catalogue)' in res and s.locator('.smai-text[lang="ar"]').count() == 1, 'mass per metre from the catalogue, texts in French and Arabic')
    s.screenshot(path=os.path.join(OUT, 'ai-listing-1280.png'), full_page=True)
    s.click('.smai-use button'); s.wait_for_load_state()
    check('/compte/publier/' in s.url and s.locator('input[name="title[fr]"]').count() == 0 and s.locator('.smp-autotitle').count() == 1 and 'pré-rempli' in s.inner_text('.sm-notice--success'), 'listing form opened pre-filled, with the warning (title generated for a new profile)')
    check(s.locator('select[name="grade"]').input_value() == 'S355JR' and 'HEA 160' in s.inner_text('select[name="section"] option:checked'), 'grade and size selected; the engine weighs on the form')
    s.screenshot(path=os.path.join(OUT, 'ai-prefilled-listing.png'), full_page=True)
    # « Write with the AI » (0.45.0): the description comes from the form, the seller can undo it.
    before = s.input_value('#smp-desc')
    check(s.locator('button.smai-desc-btn .sm-ai-badge').count() == 1, 'description field: « Write with the AI » with the AI mark')
    s.click('button.smai-desc-btn')
    s.wait_for_function('document.querySelector("#smp-desc").value.indexOf("Annonce préparée") === 0', timeout=20000)
    desc = s.input_value('#smp-desc')
    check('HEA 160' in desc and 'S355JR' in desc, 'the AI wrote the description from the facts of the form')
    s.locator('#smp-desc').scroll_into_view_if_needed(); s.wait_for_timeout(400)
    s.screenshot(path=os.path.join(OUT, 'ai-describe.png'))
    note = s.inner_text('[data-smai-desc-note]')
    check('crédit' in note and s.locator('#smp-desc.is-ai').count() == 1, 'note under the field: text to check, 1 credit used')
    s.click('.smai-desc-note__undo')
    check(s.input_value('#smp-desc') == before, '« Undo » brings back the previous text')

    print('\nC. Profile recognition')
    s.goto(BASE + '/compte/assistant-ia/?tool=recognize')
    s.click('.smai-form button[type="submit"]'); s.wait_for_load_state()
    check(s.locator('#err-photos').count() == 1, 'no photo: refused')
    s.set_input_files('input[name="photos[]"]', os.path.join(FIX, 'photo.jpg'))
    s.click('.smai-form button[type="submit"]'); s.wait_for_load_state()
    check(s.locator('.smai-cand').count() == 2 and 'IPE 200' in s.inner_text('.smai-cand.is-first'), 'possible profiles; unknown family dropped')
    check('certifiée' in s.inner_text('.smai-result') and s.locator('.smai-ask li').count() == 2, 'presented as a suggestion to confirm, with questions')
    s.screenshot(path=os.path.join(OUT, 'ai-recognize-1280.png'), full_page=True)
    s.click('.smai-cand.is-first a[href*="calcul-poids"]'); s.wait_for_load_state()
    check('section=%s' % ipe['id'] in s.url and 'IPE 200' in s.inner_text('main'), '"Weigh it" opens the weight calculator on that profile')

    print('\nD. Read a document → request for quotation (DQE IA since sm-ai 0.4.0)')
    s.goto(BASE + '/compte/mes-devis/?new=1')
    check(s.locator('.smai-callout a[href*="tool=document"]').count() == 1, 'request form offers to fill from a document')
    s.goto(BASE + '/compte/assistant-ia/?tool=document&for=rfq')
    s.set_input_files('input[name="document"]', os.path.join(FIX, 'document.pdf'))
    s.click('.smai-form button[type="submit"]'); s.wait_for_load_state()
    rev = s.locator('#smcut-ai')
    check(rev.locator('table').first.locator('tbody tr').count() == 7 and rev.locator('table').first.locator('input[type="checkbox"]:checked').count() == 2, 'lines read; the sure ones ticked')
    w = wp('$e = SM\\Core\\Modules::service("weight"); $s = SM\\Ai\\Catalog::section("HEA","HEA 200"); $r = $e->calculate(array("family"=>"HEA","section"=>$s->id,"length"=>6500,"bars"=>12)); echo number_format($r["total_kg"], 1, ",", "\\u{202F}");')
    check(w and w in rev.inner_text(), 'weights from the weight engine (%s kg for 12 × HEA 200 × 6,50 m)' % w)
    s.screenshot(path=os.path.join(OUT, 'ai-document-1280.png'), full_page=True)
    s.click('#smcut-ai button[value="rfq"]'); s.wait_for_load_state()
    secs = s.locator('select[name^="lines["][name$="[section]"]')
    vals = [secs.nth(i).input_value() for i in range(secs.count())]
    hea = wp('echo SM\\Ai\\Catalog::section("HEA","HEA 200")->id;')
    check('/mes-devis/' in s.url and hea in vals and 'Tôle 15 mm' in s.input_value('textarea[name="description"]'), 'request for quotation pre-filled: bar rows + plates in the description')

    print('\nE. Engineering account')
    e = page()
    login(e, eng)
    e.goto(BASE + '/compte/assistant-ia/?tool=listing')
    check(e.locator('.smai-tab.is-locked').count() >= 1 and e.locator('.smai-tool .smai-gate').count() == 1 and e.locator('.smai-form').count() == 0, 'listing assistant locked (engineering does not trade)')
    e.goto(BASE + '/compte/assistant-ia/?tool=recognize')
    check(e.locator('.smai-form').count() == 1, 'recognition available to engineers')

    print('\nF0. Home "Tools" row and the AI mark (0.44.0)')
    h = page(1280, 900)
    h.goto(BASE + '/?lang=fr')
    check(h.locator('.sm-tools .sm-tool--rfq').count() == 1 and h.locator('.sm-tool--ai').count() == 1 and h.locator('.sm-tool--index').count() == 1 and h.locator('.sm-split').count() == 0, 'home: tools row with the request for quotation, the AI assistant and the price index')
    rfq_bg = h.evaluate('getComputedStyle(document.querySelector(".sm-tool--rfq")).backgroundColor')
    check(rfq_bg == 'rgb(245, 247, 248)', 'home: the request for quotation card is light (%s)' % rfq_bg)
    check(h.locator('.sm-tool--ai .sm-ai-badge').inner_text().strip() == 'IA', 'home: the AI card carries the "IA" mark')
    href = h.get_attribute('.sm-tool--ai .sm-btn--ai', 'href') or ''
    check('/assistant-ia/' in href and '/compte/' not in href, '« Try the assistant » opens the public page of the AI tools')
    h.goto(BASE + '/assistant-ia/?lang=fr')
    st = h.eval_on_selector_all('.smai-card', 'e => e.map(x => x.id + ":" + x.dataset.state)')
    check('ai-search:ok' in st and 'ai-describe:login' in st and 'ai-recognize:login' in st, 'AI page, visitor: the smart search is free, the other tools ask to sign in (%s)' % ', '.join(st))
    check(h.locator('#ai-business .is-soon').count() == 0 and h.locator('#ai-business .is-free').count() == 0 and h.locator('#ai-business a[href*="tarifs"]').count() == 1 and h.locator('#ai-business .sm-btn--ai').count() == 0, 'Business report (0.56.0), visitor: Business plan (no « No credit »), « See the Business plan »')
    check(h.locator('#ai-recognize .smai-gate a[href*="connexion"]').count() == 1 and h.locator('#ai-search .is-free').count() == 1, 'visitor: « Sign in to use this tool », search marked free')
    h.goto(BASE + '/recherche/?lang=fr')
    check(h.locator('.smai-search__form .sm-ai-badge').count() == 1 and h.locator('.smai-search__form .sm-btn--ai').count() == 1, 'smart search box carries the AI mark and the AI button')
    h.close()

    print('\nF. Smart search (visitor, FR and AR)')
    v = page(390, 844)
    v.goto(BASE + '/recherche/')
    v.fill('#smai-q', 'Je cherche des IPE 200 d’occasion à Oran, 6 m minimum')
    v.click('.smai-search__form button'); v.wait_for_load_state()
    chips = v.inner_text('.smai-chips') if v.locator('.smai-chips').count() else ''
    check('wilaya=31' in v.url and 'q=IPE' in v.url and 'Oran' in chips and 'IPE 200' in chips and '6 m' in chips, 'request understood: market, profile, wilaya, length')
    v.screenshot(path=os.path.join(OUT, 'ai-search-390.png'), full_page=True)
    v.goto(BASE + '/ar/recherche/')
    check(v.evaluate('document.documentElement.dir') == 'rtl' and v.locator('.smai-search__form').count() == 1 and 'البحث الذكي' in v.inner_text('.smai-search__form'), 'Arabic page: smart search box')
    v.fill('#smai-q', 'IPE 200 مستعمل في وهران')
    v.click('.smai-search__form button'); v.wait_for_load_state()
    check('wilaya=31' in v.url and 'فُهم على أنه' in v.inner_text('.smai-understood'), 'Arabic request understood')
    s.goto(BASE + '/ar/compte/assistant-ia/')
    check(s.evaluate('document.documentElement.dir') == 'rtl' and 'المساعد الذكي' in s.inner_text('h1, .sm-dash__title'), 'assistant in Arabic')
    s.screenshot(path=os.path.join(OUT, 'ai-assistant-ar-1280.png'), full_page=True)

    print('\nG. Limits and consumption')
    if wp('echo class_exists("SM\\Subscriptions\\Credits") ? "yes" : "no";') == 'yes':
        # With sm-subscriptions (Phase 10) the credits of the plan replace the quotas per tool: use them all.
        wp('$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID); $st = SM\\Subscriptions\\Credits::status($c); global $wpdb; for ($i = 0; $i < $st["monthly_left"]; $i++) { $wpdb->insert($wpdb->prefix."sm_ai_usage", array("feature"=>"listing","company_id"=>$c->id,"status"=>"ok","credits"=>1,"created_at"=>current_time("mysql", true))); } if ($st["bought"]) { SM\\Subscriptions\\Credits::add($c->id, -$st["bought"], "test"); }' % com)
        s.goto(BASE + '/compte/assistant-ia/?tool=listing')
        check(s.locator('.smai-form').count() == 0 and 'crédits IA' in s.inner_text('.smai-tool'), 'AI credits used: tool locked with the reason')
    else:
        wp('$s = get_option("sm_ai_settings"); $s["features"]["listing"]["per_company_month"] = 1; update_option("sm_ai_settings", $s);')
        s.goto(BASE + '/compte/assistant-ia/?tool=listing')
        check(s.locator('.smai-form').count() == 0 and 'ce mois' in s.inner_text('.smai-tool'), 'monthly quota used: tool locked with the reason')
    wp('$s = get_option("sm_ai_settings"); $s["features"]["search"]["visitors_per_ip_day"] = 2; update_option("sm_ai_settings", $s);')
    v.goto(BASE + '/recherche/')
    check(v.locator('.smai-search__form').count() == 0 and v.locator('.sm-filters, #sm-filters').count() >= 1, 'visitor limit reached: smart search box hidden, normal search still there')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-ai&tab=usage')
    rows = a.locator('.sm-ai-log tbody tr').count()
    log = a.inner_text('.sm-ai-log')
    check(rows >= 6 and 'simulation' in log and ('Smart search' in log or 'Recherche intelligente' in log) and ('Read a document' in log or 'Lire un document' in log), 'consumption: every call logged with service, provider and tokens (%d rows)' % rows)
    a.screenshot(path=os.path.join(OUT, 'admin-ai-usage.png'), full_page=True)
    check(not errors, 'no JavaScript errors' + (': ' + errors[0] if errors else ''))
    b.close()

wp(('delete_option("sm_ai_settings");' if before in ('', 'null') else 'update_option("sm_ai_settings", json_decode(%s, true), false);' % json.dumps(before)) + ' global $wpdb; $wpdb->query("DELETE FROM {$wpdb->prefix}sm_ai_usage WHERE ip = \'127.0.0.1\'");')
ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
