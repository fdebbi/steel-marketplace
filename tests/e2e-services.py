"""
End-to-end browser tests of fabrication and engineering services (sm-services), Phase 8.
Usage: python3 tests/e2e-services.py URL WP_PATH OUT
Uses the client account created by tests/e2e.py (e2e-com…) and the engineer (e2e-eng…);
creates one fabrication workshop for the run and removes it, its pages and requests at the end.
"""
import os, sys, re, subprocess, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-services-output'
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
os.makedirs(OUT, exist_ok=True)
TAG = 'S%d' % (int(time.time()) % 100000)
PWD = 'Acier-2026-test!'
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def txt(pg, sel):
    return pg.inner_text(sel).replace(' ', ' ').replace('\xa0', ' ')
def login(pg, user):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', PWD)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()
def notified(email, kind):
    return wp('global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %%d AND type = \'%s\'", get_user_by("email","%s")->ID));' % (kind, email)) not in ('', '0')

client = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
eng = wp('echo get_users(array("search"=>"e2e-eng*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
for u in (client, eng):
    wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % u)
wp('$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; SM\\Core\\Support\\Forms::reset_rate_limit("service_request|" . SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id);' % client)
fab = 'e2es-fab-%s@example.test' % TAG.lower()
wp('$u = wp_insert_user(array("user_login"=>"%s","user_email"=>"%s","user_pass"=>"%s","role"=>"sm_member")); update_user_meta($u, "sm_locale", "fr"); $c = SM\\Core\\Accounts\\Companies::create(array("account_class"=>"commercial","name"=>"Atelier %s","wilaya"=>"31","phone"=>"0550 12 34 56"), $u, array("fabrication")); SM\\Core\\Accounts\\Companies::raise_level($c, 2);' % (fab, fab, PWD, TAG))

with sync_playwright() as p:
    b = p.chromium.launch()
    errors = []
    def page(user=None, w=1280, h=900):
        c = b.new_context(viewport={'width': w, 'height': h}, reduced_motion='reduce'); pg = c.new_page()
        pg.on('pageerror', lambda e: errors.append(str(e))); pg.on('dialog', lambda d: d.accept())
        if user: login(pg, user)
        return pg

    print('\nA. Workshop publishes a fabrication service')
    fp = page(fab)
    fp.goto(BASE + '/compte/')
    check(fp.locator('.sm-dash__side a[href*="/compte/services/"]').count() >= 1 and fp.locator('.sm-dash__side a[href*="demandes-de-service"]').count() >= 1, 'business account with fabrication: "My services" and "Service requests" in the menu')
    fp.goto(BASE + '/compte/services/')
    fp.click('.sm-toolbar a:has-text("Nouvelle page de service")'); fp.wait_for_load_state()
    check(fp.locator('input[name="kind"][value="fabrication"]').count() == 1 and fp.locator('input[name="categories[]"]').count() == 13, 'form opens on the fabrication catalogue (13 services)')
    check(fp.locator('#sms-unit option[value="kg"]').count() == 1, 'fabrication can be priced per kg')
    fp.click('.sms-form button[type="submit"]'); fp.wait_for_load_state()
    check(fp.locator('#err-categories').count() == 1 and fp.locator('#err-title').count() == 1 and fp.locator('#err-description').count() == 1, 'empty form: services, title and description explained')
    for c in ('cutting', 'welding', 'structural'):
        fp.check('input[name="categories[]"][value="%s"]' % c, force=True)
    fp.fill('#sms-title-fr', 'Charpente métallique et soudage ' + TAG)
    fp.fill('#sms-title-ar', 'تصنيع الهياكل المعدنية ' + TAG)
    fp.fill('#sms-desc', 'Atelier de 2 000 m² à Oran : fabrication de charpentes, poutres PRS et montage sur site.')
    fp.fill('#sms-caps', 'Poutres PRS jusqu’à 1 200 mm\nPortées jusqu’à 30 m')
    fp.fill('#sms-equip', 'Scie à ruban\nPont roulant 10 t')
    fp.check('input[name="coverage"][value="list"]', force=True)
    check(fp.locator('.sms-wilayas').is_visible(), 'choosing "Some wilayas" shows the wilaya list')
    for w in ('16', '35'):
        fp.check('.sms-wilayas input[value="%s"]' % w, force=True)
    fp.fill('#sms-lead', '15')
    check(not fp.locator('#sms-price').is_visible(), '"On quotation" hides the starting price')
    fp.check('input[name="price_mode"][value="from"]', force=True)
    fp.fill('#sms-price', '120'); fp.select_option('#sms-unit', 'kg')
    fp.set_input_files('input[name="photos[]"]', os.path.join(FIX, 'photo.jpg'))
    fp.click('.sms-form button[type="submit"]'); fp.wait_for_load_state()
    m = re.search(r'/service/(\d+)/', fp.url); sid = m.group(1) if m else ''
    check(sid and 'enregistrée' in txt(fp, '.sm-notice--success') and fp.locator('.sms-detail .smq-actions a:has-text("Modifier")').count() == 1, 'service saved; provider taken to the page, with his own actions')
    fp.goto(BASE + '/compte/services/')
    check(TAG in txt(fp, '.smp-list') and 'Publié' in txt(fp, '.smp-list'), 'listed as published in "My services"')
    fp.screenshot(path=os.path.join(OUT, 'services-mine-1280.png'), full_page=True)

    print('\nB. Public board and service page')
    pub = page()
    pub.goto(BASE + '/services/?category=welding')
    card = '.sm-card--service:has-text("%s")' % TAG
    check(pub.locator(card).count() == 1 and '120' in txt(pub, card), 'listed under "Welding" with its starting price')
    pub.goto(BASE + '/services/?wilaya=16&q=' + TAG)
    check(pub.locator(card).count() == 1, 'found for a wilaya it serves (16)')
    pub.goto(BASE + '/services/?wilaya=13&q=' + TAG)
    check(pub.locator(card).count() == 0, 'not shown for a wilaya it does not serve (13)')
    pub.goto(BASE + '/ingenierie/?q=' + TAG)
    check(pub.locator(card).count() == 0, 'fabrication services are not mixed with engineering')
    pub.goto(BASE + '/service/%s/' % sid)
    body = txt(pub, '.sms-detail')
    check('Pont roulant 10 t' in body and 'Portées jusqu’à 30 m' in body and 'Soudage' in body, 'service page: capabilities, equipment and services offered')
    check('Alger' in txt(pub, '.sms-facts') and '15 jours' in txt(pub, '.sms-facts') and 'Atelier ' + TAG in txt(pub, '.sms-provider'), 'coverage, lead time and provider shown')
    check(pub.locator('.sms-request__form').count() == 0 and pub.locator('.sms-request a[href*="connexion"]').count() == 1, 'visitor is asked to sign in before requesting')
    pub.screenshot(path=os.path.join(OUT, 'service-public-1280.png'), full_page=True)

    print('\nC. Engineer publishes an engineering service and a reference')
    ep = page(eng)
    ep.goto(BASE + '/compte/services/?new=fabrication')
    check(ep.locator('input[name="kind"][value="engineering"]').count() == 1 and ep.locator('input[name="categories[]"][value="welding"]').count() == 0, 'engineering account only gets the engineering catalogue')
    check(ep.locator('#sms-unit option[value="kg"]').count() == 0, 'no price per kg for engineering')
    ep.check('input[name="categories[]"][value="calculation"]', force=True)
    ep.check('input[name="categories[]"][value="assessment"]', force=True)
    ep.fill('#sms-title-fr', 'Notes de calcul et expertise ' + TAG)
    ep.fill('#sms-desc', 'Notes de calcul Eurocode 3 et expertise de structures d’occasion avant réemploi.')
    ep.fill('#sms-caps', 'Eurocode 3\nRobot Structural Analysis')
    ep.click('.sms-form button[type="submit"]'); ep.wait_for_load_state()
    check('/service/' in ep.url and 'enregistrée' in txt(ep, '.sm-notice--success') and 'Notes de calcul' in txt(ep, '.sms-detail'), 'engineering service published')
    ep.goto(BASE + '/compte/references/')
    ep.click('.sm-toolbar a:has-text("Ajouter une référence")'); ep.wait_for_load_state()
    ep.fill('#pf-title-fr', 'Hangar 40 × 20 m ' + TAG); ep.fill('#pf-role', 'Notes de calcul')
    ep.select_option('#pf-year', '2024'); ep.select_option('#pf-wilaya', '16')
    ep.fill('#pf-desc', 'Étude complète et plans d’exécution.')
    ep.set_input_files('input[name="photo[]"]', os.path.join(FIX, 'photo.jpg'))
    ep.click('.sms-pform button[type="submit"]'); ep.wait_for_load_state()
    check('enregistrée' in txt(ep, '.sm-notice--success') and ep.locator('.sms-ref:has-text("%s") img' % TAG).count() == 1, 'reference saved with its photo')
    slug = wp('echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->slug;' % eng)
    pub.goto(BASE + '/ingenierie/?q=' + TAG)
    check(pub.locator('.sm-card--service:has-text("Notes de calcul et expertise")').count() == 1, 'engineering service on the engineering page')
    pub.goto(BASE + '/entreprise/%s/' % slug)
    check(pub.locator('#tab-references .sms-ref:has-text("%s")' % TAG).count() == 1 and pub.locator('.sm-tabs a[href="#tab-references"]').count() == 1, 'engineer profile shows the references')
    pub.screenshot(path=os.path.join(OUT, 'engineer-profile-1280.png'), full_page=True)

    print('\nD. Client requests a quote (mobile)')
    cp = page(client, 390, 844)
    cp.goto(BASE + '/service/%s/' % sid)
    check(cp.locator('.sms-request__form').count() == 1, 'signed-in client sees the request form')
    cp.fill('#sms-msg', 'court')
    cp.click('.sms-request__form button[type="submit"]'); cp.wait_for_load_state()
    check(cp.locator('#err-message').count() == 1, 'too short description explained')
    cp.fill('#sms-msg', 'Charpente d’un hangar 30 × 15 m, IPE 300, montage à Rouiba. Galvanisation souhaitée.')
    cp.select_option('#sms-w', '16')
    if cp.locator('#sms-budget').count():
        cp.fill('#sms-budget', '900000')
    cp.set_input_files('.sms-request__form input[type="file"]', os.path.join(FIX, 'document.pdf'))
    cp.click('.sms-request__form button[type="submit"]'); cp.wait_for_load_state()
    check('envoyée' in txt(cp, '.sm-notice--success'), 'request sent')
    cp.goto(BASE + '/service/%s/' % sid)
    check(cp.locator('.sms-request__form').count() == 0 and 'déjà' in txt(cp, '.sms-request'), 'a second request is refused while the first is open')
    cp.goto(BASE + '/compte/mes-demandes-de-service/')
    check(TAG in txt(cp, '.sms-reqs') and 'Nouvelle' in txt(cp, '.sms-reqs'), '"My service requests" lists it as new')
    check(notified(fab, 'service.request_received'), 'workshop notified')

    print('\nE. Workshop answers with a quote')
    fp.goto(BASE + '/compte/demandes-de-service/')
    row = '.sms-req:has-text("%s")' % TAG
    check(fp.locator(row).count() == 1 and 'IPE 300' in txt(fp, row) and fp.locator(row + ' .sms-files a[href$=".pdf"]').count() == 1, 'request received with the project and the drawing')
    fp.screenshot(path=os.path.join(OUT, 'service-requests-1280.png'), full_page=True)
    fp.click(row + ' button[value="quote"]'); fp.wait_for_load_state()
    check(fp.locator(row + ' #err-quote_amount').count() == 1 and fp.locator(row + ' details.sms-req__answer[open]').count() == 1, 'empty quote refused, explained in that request')
    fp.fill(row + ' input[name="quote_amount"]', '850000'); fp.fill(row + ' input[name="quote_days"]', '20')
    fp.fill(row + ' textarea[name="quote_message"]', 'Galvanisation à chaud comprise.')
    fp.click(row + ' button[value="quote"]'); fp.wait_for_load_state()
    check('envoyé' in txt(fp, '.sm-notice--success') and 'Devis envoyé' in txt(fp, row), 'quote sent')

    print('\nF. Client accepts')
    cp.goto(BASE + '/compte/mes-demandes-de-service/')
    crow = '.sms-req:has-text("%s")' % TAG
    t = txt(cp, crow)
    check('850 000' in t and '20 jours' in t, 'client sees the amount and the time')
    check(cp.locator(crow + ' a[href^="tel:"]').count() == 0, 'provider phone hidden until the quote is accepted')
    cp.click(crow + ' button:has-text("Accepter le devis")'); cp.wait_for_load_state()
    check('accepté' in txt(cp, '.sm-notice--success') and cp.locator(crow + '.sms-req--accepted').count() == 1, 'quote accepted')
    check(cp.locator(crow + ' a[href^="tel:"]').count() == 1, 'provider phone shown after acceptance')
    check(notified(fab, 'service.quote_accepted'), 'workshop notified of the acceptance')
    cp.screenshot(path=os.path.join(OUT, 'my-service-requests-390.png'), full_page=True)

    print('\nG. Arabic, separation, no errors')
    ar = page(None, 390, 844)
    ar.goto(BASE + '/ar/service/%s/' % sid)
    check(ar.evaluate('document.documentElement.dir') == 'rtl' and 'تصنيع الهياكل المعدنية' in ar.inner_text('h1') and 'اللحام' in txt(ar, '.sms-detail'), 'Arabic service page with Arabic title and services')
    ar.screenshot(path=os.path.join(OUT, 'service-ar-390.png'), full_page=True)
    ar.goto(BASE + '/ar/ingenierie/')
    check(ar.evaluate('document.documentElement.dir') == 'rtl', 'Arabic engineering page')
    ep.goto(BASE + '/compte/')
    menu = txt(ep, '.sm-dash__side')
    check('Publier' not in menu and 'Mes annonces' not in menu and 'Mes services' in menu and 'Références' in menu, 'engineer menu: services and references, no trading')
    check(not errors, 'no JavaScript errors' + (': ' + errors[0] if errors else ''))
    b.close()

# Clean up: the workshop, its service and requests; the engineer's service and reference of this run.
wp('global $wpdb; foreach ($wpdb->get_col("SELECT entity_id FROM {$wpdb->prefix}sm_translations WHERE entity_type = \'service\' AND field = \'title\' AND value LIKE \'%% S%%\' AND (value LIKE \'Charpente métallique et soudage S%%\' OR value LIKE \'Notes de calcul et expertise S%%\')") as $id) { $wpdb->delete($wpdb->prefix."sm_service_requests", array("service_id"=>$id)); SM\\Services\\Services::delete($id); } foreach ($wpdb->get_col("SELECT entity_id FROM {$wpdb->prefix}sm_translations WHERE entity_type = \'portfolio\' AND field = \'title\' AND value LIKE \'Hangar 40 × 20 m S%%\'") as $id) SM\\Services\\Portfolio::delete($id);')
wp('foreach (get_users(array("search"=>"e2es-*-%s@example.test","search_columns"=>array("user_email"))) as $u) { $c = SM\\Core\\Accounts\\Companies::for_user($u->ID); global $wpdb; if ($c) { $wpdb->delete($wpdb->prefix."sm_company_members", array("company_id"=>$c->id)); $wpdb->delete($wpdb->prefix."sm_company_activities", array("company_id"=>$c->id)); $wpdb->delete($wpdb->prefix."sm_companies", array("id"=>$c->id)); } require_once ABSPATH."wp-admin/includes/user.php"; wp_delete_user($u->ID); }' % TAG.lower())
ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
