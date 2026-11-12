"""
End-to-end browser tests of partner engineers and engineering missions
(sm-engineering + theme 0.13.0), Phase 9.1.

Usage: python3 tests/e2e-engineering.py URL WP_PATH OUT

Creates a partner engineer (e2e-eng…, through the application page) and a
client (e2ee-cli…, through the registration page). Accounts of the previous
run are removed first; the ones of this run are kept for the visual tests.
Admin: admin / Admin-2026-test! (env SM_ADMIN="user:password").
"""
import json, os, re, subprocess, sys, time
from playwright.sync_api import sync_playwright
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from dashmenu import menu

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-engineering-output'
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
PDF = os.path.join(FIX, 'document.pdf')
JPG = os.path.join(FIX, 'photo.jpg')
os.makedirs(OUT, exist_ok=True)
stamp = str(int(time.time()))[-6:]
PWD = 'Acier-2026-test!'
ENG = f'e2e-eng{stamp}@example.test'
CLI = f'e2ee-cli{stamp}@example.test'
ADMIN = os.environ.get('SM_ADMIN', 'admin:Admin-2026-test!').split(':', 1)

results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def notice(pg):
    return ' '.join(pg.locator('.sm-notice, .sm-field-error').all_inner_texts()).replace('\xa0', ' ').replace(' ', ' ')
def shot(pg, name):
    pg.screenshot(path=os.path.join(OUT, name + '.png'), full_page=True)
def otp(email):
    return wp(f'$u=get_user_by("email","{email}"); echo get_user_meta($u->ID,"sm_otp_dev",true);')
def mails():
    f = os.path.join(WP, 'wp-content', 'sm-mails.jsonl')
    return [json.loads(l) for l in open(f, encoding='utf-8')] if os.path.exists(f) else []

# Remove the accounts of the previous run (engineer, client) with their missions.
wp('''require_once ABSPATH."wp-admin/includes/user.php"; global $wpdb;
foreach (array("e2e-eng*","e2ee-*") as $pat) { foreach (get_users(array("search"=>$pat,"search_columns"=>array("user_email"))) as $u) {
  $c = SM\\Core\\Accounts\\Companies::for_user($u->ID);
  if ($c) {
    foreach ($wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_missions WHERE client_id=%d OR engineer_id=%d", $c->id, $c->id)) as $m) {
      foreach (SM\\Engineering\\Files::for_mission($m) as $f) { SM\\Engineering\\Files::delete($f->id); }
      $wpdb->delete($wpdb->prefix."sm_mission_messages", array("mission_id"=>$m)); $wpdb->delete($wpdb->prefix."sm_missions", array("id"=>$m)); }
    foreach (array("engineers","company_members","company_activities","verifications") as $t) { $wpdb->delete($wpdb->prefix."sm_".$t, array("company_id"=>$c->id)); }
    $wpdb->delete($wpdb->prefix."sm_companies", array("id"=>$c->id)); }
  $wpdb->delete($wpdb->prefix."sm_notifications", array("user_id"=>$u->ID)); wp_delete_user($u->ID); } }
foreach($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE \\"_transient_sm_rl_%\\"") as $o){ delete_option($o); }''')
wp('update_option("sm_mail_settings", array_merge((array) get_option("sm_mail_settings", array()), array("admin_emails" => "contact@reusteel.com")));')
mail_start = len(mails())

with sync_playwright() as p:
    b = p.chromium.launch()
    errors = []
    def ctx(w=1280, h=900):
        c = b.new_context(viewport={'width': w, 'height': h}, reduced_motion='reduce')
        pg = c.new_page()
        pg.on('pageerror', lambda e: errors.append(str(e))); pg.on('dialog', lambda d: d.accept())
        return pg

    # ------------------------------------------------------------------
    print('\nA. An engineer applies (French, mobile)')
    e = ctx(390, 844)
    e.goto(BASE + '/inscription/')
    e.click('a[href*="devenir-ingenieur-partenaire"]'); e.wait_for_load_state()
    check('/devenir-ingenieur-partenaire/' in e.url and 'ingénieur partenaire' in e.inner_text('h1').lower(), 'registration page links to the partner engineer application')
    check(e.locator('input[name="specialties[]"]').count() == 9 and e.locator('input[name="doc_diploma"]').count() == 1 and e.locator('input[name="doc_id"]').count() == 1, 'application form: 9 specialties, diploma and identity documents')
    e.fill('#a-name', 'Karim Ingénieur')
    e.select_option('#a-wilaya', '25')
    e.fill('#a-phone', '07 72 ' + stamp[:2] + ' ' + stamp[2:4] + ' ' + stamp[4:6])
    e.fill('#a-email', ENG)
    e.fill('#a-pass', PWD)
    e.fill('#e-title', 'Ingénieur génie civil — charpente métallique')
    e.fill('#e-diploma', 'Ingénieur d’État en génie civil')
    e.fill('#e-school', 'Université de Constantine')
    e.fill('#e-year', '2010')
    e.fill('#e-exp', '15')
    e.check('input[name="specialties[]"][value="calculation"]')
    e.check('input[name="specialties[]"][value="assessment"]')
    e.fill('#e-bio', 'Quinze ans d’études de hangars et de bâtiments industriels, notes de calcul Eurocode 3 et expertises de structures d’occasion.')
    e.check('input[name="terms"]')
    e.evaluate('document.querySelectorAll("form [required]").forEach(function(i){i.removeAttribute("required")})')
    e.click('.sme-apply button[type="submit"]'); e.wait_for_load_state()
    check('diplôme d’ingénieur est obligatoire' in notice(e).lower() and wp(f'echo (int) email_exists("{ENG}");') == '0', 'without the diploma: refused, nothing created')
    check(e.input_value('#e-title').startswith('Ingénieur génie civil') and e.input_value('#a-pass') == '', 'form refilled after the error (except the password)')
    e.fill('#a-pass', PWD)
    e.check('input[name="terms"]')
    e.set_input_files('input[name="doc_diploma"]', PDF)
    e.set_input_files('input[name="doc_id"]', PDF)
    e.set_input_files('input[name="photo[]"]', JPG)
    e.click('.sme-apply button[type="submit"]'); e.wait_for_load_state()
    check(e.url.split('?')[0].rstrip('/').endswith('/compte') and 'candidature est envoyée' in notice(e), 'application sent, signed in, dashboard opened')
    check(e.locator('.sme-status').count() == 1 and 'examen' in e.inner_text('.sme-status'), 'dashboard shows "application being reviewed"')
    nav = menu(e)
    check('Mes missions' not in nav and 'Profil partenaire' in nav and 'Publier une annonce' not in nav, 'pending engineer: no missions yet, no trading menu')
    shot(e, 'engineer-pending-fr-390')
    row = wp(f'$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{ENG}")->ID); $e = SM\\Engineering\\Engineers::get($c->id); $v = SM\\Core\\Verification\\Verifications::get($e->verification_id); echo $c->account_class, "|", $e->status, "|", implode(",", array_keys($v->documents)), "|", (int) $c->logo_id, "|", $c->id;')
    cls, st, docs, logo, eng_id = row.split('|')
    check(cls == 'engineer' and st == 'pending' and docs == 'diploma,id' and logo != '0', 'separate engineer account, pending, documents and photo stored')
    path = wp(f'$v = SM\\Core\\Verification\\Verifications::latest({eng_id}); echo SM\\Core\\Verification\\Verifications::private_dir() . "/" . $v->documents["diploma"]["path"];')
    check(os.path.isfile(path) and not path.startswith(os.path.join(WP, 'wp-content', 'uploads')), 'diploma stored in the private folder, not in the public uploads')
    m = [x for x in mails()[mail_start:] if x['to'] in ('contact@reusteel.com', ENG) and 'partenaire' in x['subject'].lower()]
    check(any(x['to'] == 'contact@reusteel.com' for x in m) and any(x['to'] == ENG for x in m), 'e-mails: alert to the platform, confirmation to the engineer')
    e.goto(BASE + '/ingenierie/')
    check(e.locator('.sme-card:has-text("Karim Ingénieur")').count() == 0, 'a pending engineer is not on the public list')

    # ------------------------------------------------------------------
    print('\nB. The platform approves the engineer')
    a = ctx()
    a.goto(BASE + '/wp-login.php'); a.fill('#user_login', ADMIN[0]); a.fill('#user_pass', ADMIN[1]); a.click('#wp-submit'); a.wait_for_load_state()
    a.goto(BASE + '/wp-admin/admin.php?page=sm-engineers')
    check('Karim Ingénieur' in a.content(), 'application listed in SM → Partner engineers')
    a.goto(BASE + f'/wp-admin/admin.php?page=sm-engineers&id={eng_id}')
    check(a.locator('a[href*="action=sm_document"]').count() == 2, 'admin sees the two documents (private links)')
    shot(a, 'admin-engineer')
    a.click('button[name="status"][value="approved"]'); a.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Engineers::get({eng_id})->status, SM\\Core\\Accounts\\Companies::get({eng_id})->verification_level;') == 'approved3', 'engineer approved, verified level 3')
    e.goto(BASE + '/compte/')
    nav = menu(e)
    check('Mes missions' in nav and 'Facturation' in nav and 'Dossiers et plans' in nav, 'approved engineer: missions, invoicing, files and plans')
    g = ctx()
    g.goto(BASE + '/ingenierie/')
    card = g.locator('.sme-card:has-text("Karim Ingénieur")')
    check(card.count() == 1 and 'Notes de calcul' in card.inner_text() and '15 ans' in card.inner_text(), 'public list: card with specialties and experience')
    html = g.content()
    check(ENG not in html and ('0772' + stamp) not in html.replace(' ', '') and card.locator('a[href^="tel:"], a[href^="mailto:"]').count() == 0, 'no e-mail, phone or contact link on the public list')
    check(card.locator('.sm-avatar--img img').count() == 1, 'photo of the engineer shown')
    shot(g, 'engineers-list-fr-1280')
    g.goto(BASE + '/ingenierie/?specialty=design')
    check(g.locator('.sme-card:has-text("Karim Ingénieur")').count() == 0, 'specialty filter')
    g.goto(BASE + '/ar/ingenierie/')
    check(g.evaluate('document.documentElement.dir') == 'rtl' and 'مهندس شريك' in g.content(), 'Arabic engineering page, right-to-left')
    shot(g, 'engineers-list-ar-1280')

    # ------------------------------------------------------------------
    print('\nC. A client registers and asks for a study (French, desktop)')
    c = ctx()
    c.goto(BASE + '/ingenierie/')
    c.click('.sme-card:has-text("Karim Ingénieur") a:has-text("Demander une étude")'); c.wait_for_load_state()
    check('engineer=' + eng_id in c.url and c.locator('a[href*="connexion"]').count() >= 1, 'guest is asked to sign in or create an account')
    c.goto(BASE + '/inscription/?type=client')
    check(c.is_checked('input[value="client"]') and not c.is_visible('#g-company'), 'client account: no company name, no types')
    c.fill('#g-name', 'Nadia Cliente')
    c.select_option('#g-wilaya', '19')
    c.fill('#g-phone', '05 51 ' + stamp[:2] + ' ' + stamp[2:4] + ' ' + stamp[4:6])
    c.fill('#g-email', CLI)
    c.fill('#g-pass', PWD)
    c.check('input[name="terms"]')
    c.click('form[data-sm-register] button[type="submit"]'); c.wait_for_load_state()
    c.fill('#otp', otp(CLI)); c.click('.sm-otp__form button'); c.wait_for_load_state()
    check(wp(f'$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{CLI}")->ID); echo $c->account_class, "|", $c->name;') == 'client|Nadia Cliente', 'client account named after the person')
    nav = menu(c)
    check('Études d’ingénierie' in nav and 'Devis envoyés' not in nav and 'Publier une annonce' in nav, 'client menu: sells and buys, engineering studies, no quotations sent')
    cslug = wp(f'echo SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","{CLI}")->ID)->slug;')
    g.goto(BASE + '/entreprise/' + cslug + '/')
    check(g.locator('.sm-company-head').count() == 0, 'a client has no public page')
    c.goto(BASE + f'/demander-une-etude/?engineer={eng_id}')
    check(c.locator('.sme-request__aside .sme-card:has-text("Karim Ingénieur")').count() == 1 and c.input_value('#m-eng') == eng_id, 'the chosen engineer is pre-selected as a preference')
    c.select_option('#m-type', 'calculation')
    c.fill('#m-title', 'Hangar 20 × 40 m à Sétif ' + stamp)
    c.fill('#m-desc', 'Hangar de stockage, portée 20 m, longueur 40 m, hauteur 8 m, pont roulant 5 t, zone sismique IIa. Note de calcul et plans.')
    c.select_option('#m-wilaya', '19')
    c.fill('#m-budget', '200000')
    c.set_input_files('input[name="files[]"]', PDF)
    shot(c, 'mission-request-fr-1280')
    c.click('.sme-request__grid form button[type="submit"]'); c.wait_for_load_state()
    check('/compte/etudes/' in c.url and 'envoyée à la plateforme' in notice(c), 'request sent; the client lands on the request page')
    mid = re.search(r'id=(\d+)', c.url).group(1)
    check('À l’étude par la plateforme' in c.inner_text('.sme-head') and c.locator('.sme-files__list a').count() == 1, 'status "being studied", the document is listed')
    ref = c.inner_text('.sme-ref').strip()
    m = [x for x in mails()[mail_start:] if ref in x['subject']]
    check(any(x['to'] == 'contact@reusteel.com' for x in m) and any(x['to'] == CLI for x in m), 'e-mails: alert to the platform, confirmation to the client (' + ref + ')')
    c.fill('#sme-body', 'Le terrain est en pente de 2 %.')
    c.click('.sme-thread__form button'); c.wait_for_load_state()
    check('envoyé à la plateforme' in notice(c).lower(), 'client writes to the platform')

    # ------------------------------------------------------------------
    print('\nD. The platform sends the quote, the client accepts')
    a.goto(BASE + f'/wp-admin/admin.php?page=sm-missions&id={mid}')
    check(ref in a.inner_text('h1') and 'pente de 2' in a.content() and 'Nadia Cliente' in a.content(), 'admin sees the request, the client and the message')
    a.fill('input[name="amount"]', '150000'); a.fill('input[name="days"]', '15')
    a.fill('textarea[name="message"]', 'Note de calcul complète et plans d’exécution.')
    a.click('button[name="do"][value="quote"]'); a.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Missions::get({mid})->status;') == 'quoted', 'quote sent')
    a.fill('.sme-box:has-text("Messages avec le client") textarea, .sme-box:has-text("Messages with the client") textarea', 'Merci, c’est noté.')
    a.locator('.sme-box:has(input[name="channel"][value="client"]) button').click(); a.wait_for_load_state()
    c.goto(BASE + f'/compte/etudes/?id={mid}')
    check('150' in c.inner_text('.sme-quote') and c.locator('button[value="accept"]').count() == 1, 'client sees the quote (150 000 DA) and can accept')
    check(c.locator('.sme-msg.is-platform:has-text("noté")').count() == 1, 'client reads the answer of the platform')
    shot(c, 'study-quote-fr-1280')
    c.click('button[value="accept"]'); c.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Missions::get({mid})->status;') == 'accepted' and 'Devis accepté' in notice(c), 'client accepts the quote')

    # ------------------------------------------------------------------
    print('\nE. The platform gives the mission to the engineer')
    a.goto(BASE + f'/wp-admin/admin.php?page=sm-missions&id={mid}')
    check('choisi par le client' in a.inner_text('select[name="engineer_id"]') or 'chosen by the client' in a.inner_text('select[name="engineer_id"]'), 'the engineer chosen by the client comes first')
    a.select_option('select[name="engineer_id"]', eng_id)
    a.fill('input[name="fee"]', '100000')
    a.fill('textarea[name="note"]', 'Eurocode 3 et RPA 2003.')
    a.click('button[name="do"][value="assign"]'); a.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Missions::get({mid})->status;') == 'assigned' and '50' in a.inner_text('.sme-margin'), 'mission offered; margin 50 000 DA shown to the admin')
    shot(a, 'admin-mission')
    e2 = ctx()
    e2.goto(BASE + '/connexion/'); e2.fill('#l-user', ENG); e2.fill('#l-pass', PWD); e2.click('form:has(#l-user) button[type="submit"]'); e2.wait_for_load_state()
    e2.goto(BASE + '/compte/missions/')
    check(e2.locator('table a:has-text("Hangar 20")').count() == 1, 'engineer sees the offered mission')
    e2.goto(BASE + f'/compte/missions/?id={mid}')
    body = e2.inner_text('.sm-dash__main').replace(' ', ' ').replace('\xa0', ' ')
    check('100 000' in body and 'Eurocode 3' in body, 'engineer sees his fee and the instructions')
    check('150 000' not in body and 'Nadia' not in body and CLI not in e2.content(), 'engineer never sees the client nor the client price')
    brief = e2.locator('.sme-files__list a').first.get_attribute('href')
    r = e2.request.get(brief)
    check(r.status == 200 and r.body()[:4] == b'%PDF', 'engineer downloads the client document')
    e2.click('button[value="take"]'); e2.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Missions::get({mid})->status;') == 'in_progress', 'engineer accepts the mission')
    e2.set_input_files('.sm-dash__main form:has(button[value="upload"]) input[type="file"]', PDF)
    e2.click('button[value="upload"]'); e2.wait_for_load_state()
    check('Fichiers ajoutés' in notice(e2), 'deliverable uploaded')
    deliv = e2.locator('.sm-panel:has-text("Vos livrables") .sme-files__list a').first.get_attribute('href')
    c.goto(BASE + f'/compte/etudes/?id={mid}')
    r = c.request.get(deliv)
    check(r.status == 403, 'the client cannot open the deliverable before the platform delivers it')
    r = g.request.get(deliv)
    check(r.status == 403, 'a visitor cannot open a mission file')
    e2.click('button[value="submit"]'); e2.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Missions::get({mid})->status;') == 'review', 'engineer sends the work to the platform')
    shot(e2, 'engineer-mission-fr-1280')

    # ------------------------------------------------------------------
    print('\nF. Delivery, closing and payment')
    a.goto(BASE + f'/wp-admin/admin.php?page=sm-missions&id={mid}')
    a.click('button[name="do"][value="deliver"]'); a.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Missions::get({mid})->status, "|", SM\\Engineering\\Missions::get({mid})->fee_status;') == 'delivered|due', 'platform delivers; the fee is due')
    c.goto(BASE + f'/compte/etudes/?id={mid}')
    check(c.locator('.sme-delivered .sme-files__list a').count() == 1, 'client sees the delivered study')
    r = c.request.get(c.locator('.sme-delivered .sme-files__list a').first.get_attribute('href'))
    check(r.status == 200 and r.body()[:4] == b'%PDF', 'client downloads the study')
    check('Karim' not in c.inner_text('.sm-dash__main') and '100 000' not in c.inner_text('.sm-dash__main').replace(' ', ' ').replace('\xa0', ' '), 'client never sees the engineer nor his fee')
    shot(c, 'study-delivered-fr-1280')
    c.click('button[value="confirm"]'); c.wait_for_load_state()
    check(wp(f'echo SM\\Engineering\\Missions::get({mid})->status;') == 'closed', 'client confirms: mission closed')
    e2.goto(BASE + '/compte/facturation/')
    check('Dus' in e2.inner_text('.sm-table') or 'Due' in e2.inner_text('.sm-table'), 'engineer invoicing: fee due')
    a.goto(BASE + f'/wp-admin/admin.php?page=sm-missions&id={mid}')
    a.fill('input[name="reference"]', 'VIR-' + stamp)
    a.click('button[name="do"][value="fee_paid"]'); a.wait_for_load_state()
    e2.goto(BASE + '/compte/facturation/')
    check(('VIR-' + stamp) in e2.inner_text('.sm-table') and 'Payés' in e2.inner_text('.sm-table'), 'engineer invoicing: fee paid with the reference')
    shot(e2, 'engineer-billing-fr-1280')
    e2.goto(BASE + '/compte/dossiers/')
    check(e2.locator('.sme-files__list li').count() == 2, 'files and plans: client document and deliverable')
    e2.goto(BASE + '/ar/compte/missions/')
    check(e2.evaluate('document.documentElement.dir') == 'rtl' and 'مهامي' in e2.inner_text('.sm-dash__side'), 'engineer dashboard in Arabic')
    shot(e2, 'engineer-missions-ar-1280')
    g.goto(BASE + '/ingenierie/')
    check('1 mission réalisée' in g.inner_text('.sme-card:has-text("Karim Ingénieur")').replace('\xa0', ' '), 'completed missions shown on the public card')
    e2.goto(BASE + '/compte/publier/')
    check(e2.locator('.smp-form, .sm-stepper').count() == 0, 'engineer has no publication form')
    e2.goto(BASE + '/demander-une-etude/')
    check(e2.locator('.sme-request__grid form').count() == 0, 'engineer cannot request a study')

    check(not errors, 'no JavaScript error' + ((': ' + errors[0]) if errors else ''))
    b.close()

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} engineering checks passed')
for r in results:
    if not r[0]:
        print('FAIL', r[1])
json.dump([{'ok': r[0], 'check': r[1]} for r in results], open(os.path.join(OUT, 'e2e-engineering-report.json'), 'w'), ensure_ascii=False, indent=1)
sys.exit(0 if ok == len(results) else 1)
