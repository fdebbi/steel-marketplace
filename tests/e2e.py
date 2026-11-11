"""
End-to-end browser tests of accounts (sm-core + theme) on a LOCAL test site:
registration of a company (French, mobile) and a professional (Arabic,
desktop), verification, profile and photo, moderation, language and sign-in.

Requires: WordPress with sm-core + sm-marketplace, WP_ENVIRONMENT_TYPE=local
(verification codes are then readable in user meta "sm_otp_dev"), WP-CLI,
Python Playwright. Creates accounts "e2e-*@example.test" and removes them first.

Usage: python3 tests/e2e.py http://localhost:8080 /path/to/wordpress out_dir
"""
import json, os, subprocess, sys, time
from urllib.parse import urlparse
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-output'
os.makedirs(OUT, exist_ok=True)
PDF = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures', 'document.pdf')

results = []
def check(cond, label):
    results.append((bool(cond), label))
    print(('  ✓ ' if cond else '  ✗ ') + label)

def wp(php):
    r = subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', php], capture_output=True, text=True)
    return r.stdout.strip()

def otp(email):
    return wp(f'$u=get_user_by("email","{email}"); echo get_user_meta($u->ID,"sm_otp_dev",true);')

def shot(page, name):
    page.screenshot(path=os.path.join(OUT, name + '.png'), full_page=True)

def path(page):
    return urlparse(page.url).path.rstrip('/')

def notice(page):
    return ' '.join(page.locator('.sm-notice, .sm-field-error').all_inner_texts())

# Clean previous runs.
wp('''require_once ABSPATH."wp-admin/includes/user.php"; global $wpdb;
foreach(get_users(array("search"=>"e2e-*","search_columns"=>array("user_email"))) as $u){
  $c=SM\\Core\\Accounts\\Companies::for_user($u->ID);
  if($c){ $wpdb->delete($wpdb->prefix."sm_engineers",array("company_id"=>$c->id)); foreach(array("company_members","company_activities","verifications") as $t){$wpdb->delete($wpdb->prefix."sm_".$t,array("company_id"=>$c->id));} $wpdb->delete($wpdb->prefix."sm_companies",array("id"=>$c->id)); $wpdb->delete($wpdb->prefix."sm_enquiries",array("to_company_id"=>$c->id)); }
  $wpdb->delete($wpdb->prefix."sm_enquiries",array("from_user_id"=>$u->ID)); $wpdb->delete($wpdb->prefix."sm_notifications",array("user_id"=>$u->ID)); wp_delete_user($u->ID); }
foreach($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE \\"_transient_sm_rl_%\\"") as $o){ delete_option($o); }''')

stamp = str(int(time.time()))[-6:]
COM = f'e2e-com{stamp}@example.test'
ENG = f'e2e-pro{stamp}@example.test'  # professional (partner engineers: tests/e2e-engineering.py)
PASS = 'Acier-2026-test!'

with sync_playwright() as p:
    browser = p.chromium.launch()

    # ------------------------------------------------------------------
    print('\nA. Guest')
    g = browser.new_context(viewport={'width': 390, 'height': 844}).new_page()
    g.goto(BASE + '/annonce/101/')
    check(g.locator('#sm-enquiry a[href*="redirect_to"]').count() == 1, 'guest sees "sign in to contact" on a listing')
    g.goto(BASE + '/compte/')
    check('/connexion/' in g.url and 'redirect_to' in g.url, 'dashboard redirects guests to sign-in')
    g.goto(BASE + '/produits/?lang=ar')
    check(g.url.rstrip('/').endswith('/ar/produits'), '?lang=ar redirects to /ar/produits/')
    check(g.evaluate('document.documentElement.dir') == 'rtl', '/ar/ pages are RTL')
    g.goto(BASE + '/produits/')
    check('/ar/produits' in g.url, 'saved Arabic preference sends a plain URL to /ar/')
    g.goto(BASE + '/?lang=fr')
    check(g.url.rstrip('/') == BASE and g.evaluate('document.documentElement.lang').startswith('fr'), 'switching back to French works')
    g.context.close()

    # ------------------------------------------------------------------
    print('\nB. Business account (French, mobile)')
    ctx = browser.new_context(viewport={'width': 390, 'height': 844})
    c = ctx.new_page()
    c.goto(BASE + '/inscription/')
    c.fill('#g-company', 'E2E Acier Test')
    c.fill('#g-name', 'Samir Test')
    c.select_option('#g-wilaya', '28')
    c.fill('#g-phone', '0661 00 12')  # invalid on purpose (too short)
    c.fill('#g-email', COM)
    c.fill('#g-pass', PASS)
    c.check('input[name="terms"]')
    c.click('form[data-sm-register] button[type="submit"]')
    c.wait_for_load_state()
    check('mobile' in notice(c).lower() and 'type d’entreprise' in notice(c).lower(), 'validation errors shown in French (phone, type of company)')
    check(c.input_value('#g-company') == 'E2E Acier Test' and c.input_value('#g-email') == COM, 'form refilled after an error (except password)')
    check(c.input_value('#g-pass') == '', 'password is never refilled')
    shot(c, 'register-errors-fr-390')
    c.check('input[name="activities[]"][value="manufacturer"]')
    c.check('input[name="activities[]"][value="recycler"]')
    c.fill('#g-phone', '06 61 ' + stamp[:2] + ' ' + stamp[2:4] + ' ' + stamp[4:6])
    c.fill('#g-pass', PASS)
    c.check('input[name="terms"]')
    c.click('form[data-sm-register] button[type="submit"]')
    c.wait_for_load_state()
    check('/compte/verifier/' in c.url, 'registration signs in and opens the code step')
    check('Bienvenue' in notice(c), 'welcome message in French')
    shot(c, 'verify-code-fr-390')
    c.fill('#otp', '000000')
    c.click('.sm-otp__form button')
    c.wait_for_load_state()
    check('incorrect' in notice(c).lower(), 'wrong code refused')
    c.fill('#otp', otp(COM))
    c.click('.sm-otp__form button')
    c.wait_for_load_state()
    check(path(c).endswith('/compte') and 'vérifié' in notice(c), 'right code verifies the account')
    check(wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID)->verification_level;') == '1', 'company at level 1')
    shot(c, 'dashboard-business-fr-390')

    c.goto(BASE + '/compte/entreprise/')
    c.fill('#c-about-fr', 'Fabrication de charpente et revente de chutes. Test E2E.')
    c.fill('#c-name-ar', 'شركة اختبار للفولاذ')
    c.fill('#c-rc', '28/00-1234567B19')
    c.fill('#c-nif', '000128012345678')
    c.click('.sm-form--dash button[type="submit"]')
    c.wait_for_load_state()
    check('enregistrée' in notice(c), 'company profile saved')
    slug = wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID)->slug;')
    nif_raw = wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID)->nif;')
    check('000128012345678' not in nif_raw and nif_raw.startswith(('s1:', 'o1:')), 'NIF encrypted in the database')

    # Company location map ------------------------------------------------
    js_errors = []
    c.on('pageerror', lambda err: js_errors.append(str(err)))
    def db_pos():
        return wp(f'$c=SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID); echo $c->lat===null?"null":round($c->lat,4).",".round($c->lng,4);')
    def marker_pos(pg):
        return pg.evaluate('''() => { const m = document.querySelector('[data-sm-map="picker"] .leaflet-marker-icon');
            if (!m) return null; const r = m.getBoundingClientRect(); return [Math.round(r.x), Math.round(r.y)]; }''')
    c.goto(BASE + '/compte/entreprise/')
    c.wait_for_function('window.L && document.querySelector("[data-sm-map=picker].leaflet-container")')
    check(c.input_value('[data-sm-map-lat]') == '' and c.locator('[data-sm-map="picker"] .leaflet-marker-icon').count() == 1, 'map opens on the wilaya with a suggested marker, nothing saved yet')
    c.select_option('#c-wilaya', '19')
    c.wait_for_timeout(400)
    centre = c.evaluate('(() => { const m = document.querySelector("[data-sm-map=picker]").smMap.getCenter(); return [m.lat, m.lng]; })()')
    check(abs(centre[0] - 36.191) < 0.01 and abs(centre[1] - 5.414) < 0.01 and c.input_value('[data-sm-map-lat]') == '', 'changing the wilaya recentres the map (Sétif) without saving a position')
    c.locator('[data-sm-map="picker"]').scroll_into_view_if_needed()
    box = c.locator('[data-sm-map="picker"]').bounding_box()
    c.locator('[data-sm-map="picker"]').click(position={'x': box['width'] * 0.3, 'y': box['height'] * 0.75})
    lat_click = c.input_value('[data-sm-map-lat]')
    check(lat_click != '' and 35.0 < float(lat_click) < 37.5 and 'placée' in c.inner_text('[data-sm-map-status]'), 'clicking the map places the marker and fills the position')
    # Drag the marker.
    m = c.locator('[data-sm-map="picker"] .leaflet-marker-icon').bounding_box()
    c.mouse.move(m['x'] + m['width'] / 2, m['y'] + m['height'] - 4)
    c.mouse.down()
    c.mouse.move(m['x'] + m['width'] / 2 + 40, m['y'] + m['height'] - 34, steps=8)
    c.mouse.up()
    check(c.input_value('[data-sm-map-lat]') not in ('', lat_click), 'dragging the marker updates the position')
    # Current location (browser geolocation).
    ctx.grant_permissions(['geolocation'], origin=BASE)
    ctx.set_geolocation({'latitude': 36.1912, 'longitude': 5.4137, 'accuracy': 25})
    c.click('[data-sm-map-locate]')
    c.wait_for_function('document.querySelector("[data-sm-map-lat]").value.startsWith("36.1912")')
    check(abs(float(c.input_value('[data-sm-map-lng]')) - 5.4137) < 0.0001, '"use my current location" places the marker on the device position')
    c.click('.sm-form--dash button[type="submit"]')
    c.wait_for_load_state()
    check('enregistrée' in notice(c) and db_pos() == '36.1912,5.4137', 'position saved with the company profile')
    c.wait_for_function('window.L && document.querySelector("[data-sm-map=picker] .leaflet-marker-icon")')
    check(c.input_value('[data-sm-map-lat]').startswith('36.1912') and c.locator('[data-sm-map-clear]').is_visible(), 'saved position shown again when the form reopens')
    # A forged position outside Algeria is refused by the server.
    c.evaluate('document.querySelector("[data-sm-map-lat]").value = "48.8566"; document.querySelector("[data-sm-map-lng]").value = "2.3522";')
    c.click('.sm-form--dash button[type="submit"]')
    c.wait_for_load_state()
    check('Algérie' in notice(c) and db_pos() == '36.1912,5.4137', 'position outside Algeria refused, previous position kept')
    c.goto(BASE + '/entreprise/' + wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID)->slug;') + '/')
    c.wait_for_function('document.querySelector("[data-sm-map=view].leaflet-container")')
    href = c.get_attribute('.sm-map--view a[href*="google.com/maps/dir"]', 'href') or ''
    check('36.1912' in href and '5.4137' in href, 'public page shows the map with a directions link')
    shot(c, 'company-map-fr-390')
    rest = c.request.get(BASE + '/wp-json/sm/v1/companies/' + wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID)->slug;')).json()
    check(rest.get('location', {}).get('lat') == 36.1912, 'REST exposes the public position')
    c.goto(BASE + '/compte/entreprise/')
    c.uncheck('input[name="map_public"]')
    c.click('.sm-form--dash button[type="submit"]')
    c.wait_for_load_state()
    c.goto(BASE + '/entreprise/' + wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID)->slug;') + '/')
    check(c.locator('.sm-map--view').count() == 0 and db_pos() == '36.1912,5.4137', 'hidden map: position kept but not shown publicly')
    c.goto(BASE + '/compte/entreprise/')
    c.check('input[name="map_public"]')
    c.click('.sm-form--dash button[type="submit"]')
    c.wait_for_load_state()
    check(not js_errors, 'no JavaScript errors on map pages' + (': ' + js_errors[0] if js_errors else ''))

    c.goto(BASE + '/compte/verification/')
    c.set_input_files('input[name="doc_rc"]', PDF)
    c.click('form[enctype="multipart/form-data"] button[type="submit"]')
    c.wait_for_load_state()
    check('reçus' in notice(c), 'verification documents uploaded')
    files = wp('global $wpdb; echo $wpdb->get_var("SELECT documents FROM {$wpdb->prefix}sm_verifications ORDER BY id DESC LIMIT 1");')
    check('rc' in files and '.pdf' in files, 'document stored with a random private path')
    up = wp('echo wp_upload_dir()["baseurl"];')
    doc_path = json.loads(files)['rc']['path']
    r = c.request.get(up + '/sm-private/' + doc_path)
    check(r.status in (403, 404) or b'%PDF' not in r.body(), 'private document not reachable by URL')

    c.goto(BASE + '/annonce/101/')
    c.fill('#sm-enquiry-msg', 'Bonjour, je cherche 10 barres IPE 200 livrées à Sétif.')
    c.fill('#sm-enquiry-qty', '10 barres')
    c.click('#sm-enquiry button[type="submit"]')
    c.wait_for_load_state()
    check('envoyée' in notice(c), 'enquiry on a (demo) listing sent')
    check(wp('global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_enquiries WHERE quantity=\'10 barres\'");') == '1', 'enquiry stored')

    c.goto(BASE + '/compte/publier/')
    check((c.locator('.sm-stepper').count() == 1 or c.locator('.smp-cats').count() == 1) and c.locator('.sm-notice--warn').count() == 0, 'verified business account can open the publish form')

    # ------------------------------------------------------------------
    print('\nC. Professional account (Arabic, desktop)')
    ctx2 = browser.new_context(viewport={'width': 1280, 'height': 900})
    e = ctx2.new_page()
    e.goto(BASE + '/ar/inscription/')
    check(e.locator('input[name="account_class"]').count() == 3 and e.locator('input[value="engineering"]').count() == 0, 'three kinds of account: company, professional, client (no engineer account here)')
    check(e.locator('a[href*="devenir-ingenieur-partenaire"]').count() == 1, 'link to the partner engineer application')
    e.click('input[value="professional"] + .sm-choice__card')
    check(not e.is_visible('[data-sm-show-for="company"] .sm-checkgrid') and e.is_visible('[data-sm-show-for="professional"] .sm-checkgrid'), 'trades shown instead of company types')
    check(e.locator('[data-sm-show-for="professional"] input[name="activities[]"]').count() == 8, 'eight trades offered')
    e.check('[data-sm-show-for="professional"] input[value="trade_welder"]')
    e.check('[data-sm-show-for="professional"] input[value="trade_erector"]')
    e.fill('#g-company', 'ورشة E2E للحام')
    e.fill('#g-name', 'كريم اختبار')
    e.select_option('#g-wilaya', '25')
    e.fill('#g-phone', '07 71 ' + stamp[:2] + ' ' + stamp[2:4] + ' ' + stamp[4:6])
    e.fill('#g-email', ENG)
    e.fill('#g-pass', PASS)
    e.check('input[name="terms"]')
    e.click('form[data-sm-register] button[type="submit"]')
    e.wait_for_load_state()
    check('/ar/compte/verifier/' in e.url, 'Arabic registration stays in /ar/')
    check('مرحبا' in notice(e), 'welcome message in Arabic')
    mails = [json.loads(l) for l in open(os.path.join(WP, 'wp-content', 'sm-mails.jsonl'), encoding='utf-8')] if os.path.exists(os.path.join(WP, 'wp-content', 'sm-mails.jsonl')) else []
    m = [x for x in mails if x['to'] == ENG]
    check(m and 'dir="rtl"' in m[-1]['message'] and 'رمز' in m[-1]['subject'], 'verification e-mail sent in Arabic, right-to-left')
    check(wp(f'$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{ENG}")->ID); echo $c->account_class, ":", implode(",", $c->activities);') == 'professional:trade_erector,trade_welder', 'professional account with its two trades')
    e.fill('#otp', otp(ENG))
    e.click('.sm-otp__form button')
    e.wait_for_load_state()
    check(path(e).endswith('/ar/compte') and 'توثيق' in notice(e), 'professional verified, Arabic dashboard and message')
    nav = ' '.join(e.locator('.sm-dash__side nav').all_inner_texts())
    check('نشر إعلان' in nav and 'خدماتي' in nav and 'المشاريع المنجزة' in nav, 'professional menu: publish a listing (sells), services, completed projects')
    shot(e, 'dashboard-professional-ar-1280')
    e.goto(BASE + '/ar/compte/publier/')
    check(e.locator('.sm-notice--warn:has-text("مهندس")').count() == 0, 'publish form open to professionals (they sell)')
    e.goto(BASE + '/ar/compte/entreprise/')
    check(e.locator('input[name="logo[]"]').count() == 1 and e.locator('#c-title-fr').count() == 1, 'profile form: photo / logo and professional title')
    e.wait_for_function('window.L && document.querySelector("[data-sm-map=picker].leaflet-container")')
    check(e.get_attribute('[data-sm-map="picker"]', 'dir') == 'ltr' and ('خريطة' in e.inner_text('[data-sm-map-picker]') or 'الخريطة' in e.inner_text('[data-sm-map-picker]')), 'Arabic profile form: map shown, labels in Arabic, map canvas kept LTR')
    e.set_input_files('input[name="logo[]"]', os.path.join(os.path.dirname(PDF), 'photo.jpg'))
    e.click('.sm-form--dash button[type="submit"]')
    e.wait_for_load_state()
    check(e.locator('.sm-dash__who .sm-avatar--img img').count() == 1, 'photo uploaded and shown as the account avatar')
    shot(e, 'company-map-ar-1280')
    eslug = wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{ENG}")->ID)->slug;')

    # The company contacts the professional from his public page.
    c.goto(BASE + '/entreprise/' + eslug + '/')
    check(c.locator('.smc-head__logo img').count() == 1, 'public page shows the photo of the professional')
    check(c.locator('.smc-badge--account').count() == 1, 'account-verified badge once the e-mail is verified')
    c.click('.smc-side [data-smc-open="smc-message"]')
    c.fill('#sm-contact-msg', 'Bonjour, nous cherchons un soudeur pour le montage d’un hangar 40 x 20 m.')
    c.click('#sm-contact button[type="submit"]')
    c.wait_for_load_state()
    check('envoyée' in notice(c), 'service request sent to the professional')
    e.goto(BASE + '/ar/compte/demandes/')
    check(e.locator('.sm-enquiry').count() == 1 and 'soudeur' in e.inner_text('.sm-enquiry'), 'professional sees the request in the Arabic dashboard')
    shot(e, 'professional-requests-ar-1280')
    e.goto(BASE + '/ar/compte/')
    check(e.locator('.sm-dot').count() == 1 or e.locator('#sm-notifications .is-unread').count() >= 1, 'professional has an unread notification')

    # ------------------------------------------------------------------
    print('\nD. Moderator approves the business documents')
    a = browser.new_context(viewport={'width': 1280, 'height': 900}).new_page()
    a.goto(BASE + '/wp-login.php')
    a.fill('#user_login', 'admin')
    a.fill('#user_pass', os.environ.get('SM_ADMIN', 'admin:Admin-2026-test!').split(':', 1)[1])
    a.click('#wp-submit')
    a.wait_for_load_state()
    a.goto(BASE + '/wp-admin/admin.php?page=sm-verifications')
    check('E2E Acier Test' in a.content(), 'request listed in the verification queue')
    shot(a, 'admin-verifications')
    a.locator('form:has(input[name="action"][value="sm_admin_decide"]) button[value="approve"]').first.click()
    a.wait_for_load_state()
    check(wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{COM}")->ID)->verification_level;') == '2', 'business verified (level 2)')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-rights')
    check(a.locator('.sm-matrix input[name="rights[engineer][]"][value="buy"]').count() == 0, 'admin matrix locks trading rights for engineers')
    shot(a, 'admin-rights')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-audit')
    check('verification.approved' in a.content() and 'account.registered' in a.content(), 'audit log records registration and approval')
    a.context.close()

    c.goto(BASE + '/entreprise/' + slug + '/')
    check(c.locator('.smc-head .smc-badge--quality').count() == 1, 'quality-verified badge on the public company page')
    share = c.locator('.smc-share a[href*="facebook.com/sharer"]').get_attribute('href') or ''
    check(slug in share and c.locator('.smc-share a').count() >= 5, 'share links point to the company page')
    shot(c, 'company-verified-fr-390')

    # ------------------------------------------------------------------
    print('\nE. Language preference and sign-in')
    c.goto(BASE + '/compte/parametres/')
    c.check('input[name="language"][value="ar"]')
    c.click('.sm-form--dash button[type="submit"]')
    c.wait_for_load_state()
    check('/ar/compte/parametres/' in c.url, 'choosing Arabic in settings switches to /ar/')
    ctx.clear_cookies()
    c.goto(BASE + '/connexion/')
    c.fill('#l-user', COM)
    c.fill('#l-pass', 'wrong-password')
    c.click('form:has(#l-user) button[type="submit"]')
    c.wait_for_load_state()
    check('incorrect' in notice(c).lower(), 'wrong password refused')
    c.fill('#l-user', '0661' + stamp[:2] + stamp[2:4] + stamp[4:6])  # sign in with the phone number
    c.fill('#l-pass', PASS)
    c.click('form:has(#l-user) button[type="submit"]')
    c.wait_for_load_state()
    check('/ar/compte' in c.url, 'sign-in by phone number, lands in the saved language (Arabic)')
    c.goto(BASE + '/ar/connexion/')
    check('/ar/compte' in c.url, 'signed-in users skip the sign-in page')
    c.goto(BASE + '/wp-admin/')
    check('/compte' in c.url, 'members are kept out of wp-admin')
    shot(c, 'dashboard-business-ar-390')
    ctx2.close()
    ctx.close()
    browser.close()

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} end-to-end checks passed')
for r in results:
    if not r[0]:
        print('FAIL', r[1])
json.dump([{'ok': r[0], 'check': r[1]} for r in results], open(os.path.join(OUT, 'e2e-report.json'), 'w'), ensure_ascii=False, indent=1)
sys.exit(0 if ok == len(results) else 1)
