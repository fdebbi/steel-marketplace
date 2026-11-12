"""
End-to-end browser tests of the plans and payments (sm-subscriptions + theme), Phase 10.

Usage: python3 tests/e2e-subscriptions.py URL WP_PATH OUT

Creates a company (e2es-co…, code confirmed only: free plan) and a client
(e2es-cli…). Accounts of the previous run are removed first. The payment
details of the platform are set to obvious test values during the run and put
back at the end. Admin: admin / Admin-2026-test! (env SM_ADMIN="user:password").
"""
import os, re, subprocess, sys, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-subscriptions-output'
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
os.makedirs(OUT, exist_ok=True)
stamp = str(int(time.time()))[-6:]
PWD = 'Acier-2026-test!'
CO = f'e2es-co{stamp}@example.test'
CLI = f'e2es-cli{stamp}@example.test'
ADMIN = os.environ.get('SM_ADMIN', 'admin:Admin-2026-test!').split(':', 1)

results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def txt(pg, sel='body'):
    return ' '.join(pg.inner_text(sel).split()).replace(' ', ' ').replace('\xa0', ' ')
def notice(pg):
    return ' '.join(pg.locator('.sm-notice, .sm-field-error').all_inner_texts()).replace('\xa0', ' ').replace(' ', ' ')
def shot(pg, name):
    pg.screenshot(path=os.path.join(OUT, name + '.png'), full_page=True)
def login(pg, user, pwd=PWD):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', pwd)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

# Previous run: accounts, orders, plans.
wp('''require_once ABSPATH."wp-admin/includes/user.php"; global $wpdb;
foreach (get_users(array("search"=>"e2es-*","search_columns"=>array("user_email"))) as $u) {
  $c = SM\\Core\\Accounts\\Companies::for_user($u->ID);
  if ($c) { foreach (array("sm_products","sm_subscriptions","sm_sub_orders","sm_sub_credits","sm_company_members","sm_company_activities","sm_verifications","sm_notifications") as $t) { $wpdb->delete($wpdb->prefix.$t, array("company_id"=>$c->id)); }
    $wpdb->delete($wpdb->prefix."sm_companies", array("id"=>$c->id)); }
  wp_delete_user($u->ID); }
foreach($wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE \\"_transient_sm_rl_%\\"") as $o){ delete_option($o); }''')
before = wp('echo wp_json_encode(get_option("sm_sub_settings", null));')
wp('$s = SM\\Subscriptions\\Settings::all(); $s["legal_name"] = "TEST PLATEFORME SARL"; $s["bank_name"] = "Banque de test"; $s["rib"] = "000 00000 0000000000 00"; $s["ccp"] = "0000000000 clé 00"; $s["baridimob"] = "00799999000000000000"; $s["cheque_to"] = "TEST PLATEFORME SARL"; $s["instructions"] = "Indiquez la référence de commande."; SM\\Subscriptions\\Settings::save($s);')
co_id = wp('$u = wp_insert_user(array("user_login"=>"%s","user_email"=>"%s","user_pass"=>"%s","role"=>"sm_member")); update_user_meta($u, "sm_locale", "fr"); $c = SM\\Core\\Accounts\\Companies::create(array("account_class"=>"company","name"=>"Aciers E2ES %s","wilaya"=>"31","phone"=>"0550 98 76 54","email"=>"contact-e2es@example.test"), $u, array("supplier")); SM\\Core\\Accounts\\Companies::raise_level($c, 1); echo $c;' % (CO, CO, PWD, stamp))
cli_id = wp('$u = wp_insert_user(array("user_login"=>"%s","user_email"=>"%s","user_pass"=>"%s","role"=>"sm_member")); update_user_meta($u, "sm_locale", "fr"); $c = SM\\Core\\Accounts\\Companies::create(array("account_class"=>"client","name"=>"Client E2ES %s","wilaya"=>"16","phone"=>"0660 11 22 33"), $u, array("client")); SM\\Core\\Accounts\\Companies::raise_level($c, 1); echo $c;' % (CLI, CLI, PWD, stamp))
co_slug = wp('echo SM\\Core\\Accounts\\Companies::get(%s)->slug;' % co_id)
# One listing online for the options: a copy of a published listing of another account.
pid = wp('''global $wpdb; $t = $wpdb->prefix."sm_products"; $r = $wpdb->get_row("SELECT * FROM $t WHERE status IN ('published','sold') AND market = 'products' AND company_id <> %s ORDER BY id DESC LIMIT 1", ARRAY_A);
unset($r["id"]); $r["status"] = "published"; $r["published_at"] = $r["updated_at"] = current_time("mysql", true); $r["company_id"] = %s; $r["listing_id"] = "E2ES%s"; $r["total_weight"] = 1200; $r["featured_until"] = null; $r["urgent_until"] = null; $r["bumped_at"] = null; $wpdb->insert($t, $r); echo $wpdb->insert_id;''' % (co_id, co_id, stamp))
check(co_id.isdigit() and cli_id.isdigit() and pid.isdigit(), 'test accounts and one listing online prepared')

with sync_playwright() as p:
    b = p.chromium.launch()
    errors = []
    def ctx(w=1280, h=900):
        c = b.new_context(viewport={'width': w, 'height': h}, reduced_motion='reduce')
        pg = c.new_page()
        pg.on('pageerror', lambda e: errors.append(str(e))); pg.on('dialog', lambda d: d.accept())
        return pg

    print('\nA. Public pricing page')
    v = ctx()
    v.goto(BASE + '/tarifs/?lang=fr')
    t = txt(v)
    check(v.locator('#plans-company .sm-plan').count() == 3 and all(n in t for n in ('Découverte', 'Pro', 'Business')), 'three plans for companies')
    check('3 500 DA' in t and '35 000 DA' in t and '9 000 DA' in t and '7 000 DA d’économie' in t, 'prices from the settings, yearly saving shown')
    check(v.locator('#plans-professional').is_hidden(), 'professional plans in the second tab')
    v.click('[data-sm-tab="professional"]')
    t = txt(v, '#plans-professional')
    check(v.locator('#plans-professional .sm-plan').count() == 3 and 'Gratuit' in t and 'Artisan Pro' in t and 'Artisan Premium' in t and '1 500 DA' in t, 'tab switch: Free, Artisan Pro, Artisan Premium')
    check('Bâtiments en ligne' not in t and 'Illimité' in t, 'no building row for professionals; unlimited values')
    t = txt(v)
    check('50 crédits IA' in t and '1 000 DA' in t and '150 crédits IA' in t and '2 500 DA' in t, 'AI credit packs')
    check('Remontée en tête de liste' in t and '300 DA' in t and 'Badge « Urgent », 7 jours' in t and '2 000 DA' in t, 'listing options')
    check('Offre membres fondateurs' in t, 'founding members offer shown')
    shot(v, 'a-pricing-fr')
    v.goto(BASE + '/tarifs/?lang=ar')
    check(v.locator('html[dir="rtl"]').count() == 1 and 'اكتشاف' in txt(v) and 'باقات رصيد الذكاء الاصطناعي' in txt(v), 'Arabic pricing page (RTL)')
    shot(v, 'a-pricing-ar')
    v.goto(BASE + '/tarifs/?lang=en')
    check('Discovery' in txt(v) and 'AI credit packs' in txt(v), 'English pricing page')

    print('\nB. A free company: limits and hidden contacts')
    v.goto(BASE + '/entreprise/' + co_slug + '/?lang=fr')
    t = txt(v)
    check('0550 98' not in t and 'contact-e2es@example.test' not in t and 'Utilisez le formulaire' in t, 'visitors do not see the phone and e-mail of a free account')
    c = ctx()
    login(c, CO)
    c.goto(BASE + '/compte/abonnement/')
    t = txt(c)
    check('Découverte' in t and 'Gratuit, sans limite de durée' in t, 'plan page: Discovery, free without time limit')
    check('Annonces en ligne' in t and '1 / 5' in t and 'Poids total en ligne' in t and '/ 10 t' in t, 'use of the limits: 1 listing of 5, tonnage against 10 t')
    check('Offre membres fondateurs' in t and 'Vérifier mon compte' in t, 'founding members banner for an account not yet checked')
    check(c.locator('.sm-plan.is-current[data-plan="free"]').count() == 1 and c.locator('[data-plan="pro"] form').count() == 2, 'current plan marked; month and year buttons on Pro')
    shot(c, 'b-plan-free')

    print('\nC. Order Pro for a year, pay, send the proof')
    c.click('[data-plan="pro"] input[name="period"][value="year"] ~ button')
    c.wait_for_load_state()
    t = txt(c)
    ref = (re.search(r'CMD-\d{4}-\d{5}', t) or [''])[0]
    check('/commande/' in c.url and ref and '35 000 DA' in t and 'En attente de paiement' in t, 'order page: reference, 35 000 DA, waiting for payment')
    check('Banque de test' in t and '000 00000 0000000000 00' in t and 'TEST PLATEFORME SARL' in t and 'Indiquez la référence de commande.' in t, 'payment details of the platform shown')
    shot(c, 'c-order')
    c.click('form:has([name="order_id"]) button.sm-btn--accent'); c.wait_for_load_state()
    check('Indiquez comment vous avez payé' in notice(c) or c.locator('input[name="proof[]"]:invalid').count() == 1, 'proof form: method and file required')
    c.check('input[name="method"][value="transfer"]', force=True)
    c.set_input_files('input[name="proof[]"]', os.path.join(FIX, 'document.pdf'))
    c.fill('#sub-note', 'Virement fait ce matin')
    c.click('form:has([name="order_id"]) button.sm-btn--accent'); c.wait_for_load_state()
    t = txt(c)
    check('Paiement en vérification' in t and 'preuve de paiement est reçue' in t, 'proof sent: payment being checked')
    file = wp('$o = SM\\Subscriptions\\Orders::query(array("company"=>%s))[0]; echo $o->proof_file, "|", (int) $o->proof_id;' % co_id)
    check('payments' in file and 'proof-' in file and file.endswith('|0'), 'proof stored in the private folder, not in the media library')
    c.goto(BASE + '/compte/abonnement/')
    check('preuve de paiement est en vérification' in txt(c), 'plan page reminds the open order')
    # Another account cannot open the order.
    k = ctx()
    login(k, CLI)
    oid = wp('echo SM\\Subscriptions\\Orders::query(array("company"=>%s))[0]->id;' % co_id)
    k.goto(BASE + '/compte/commande/?order=' + oid)
    check('Commande introuvable' in txt(k) and 'Banque de test' not in txt(k), 'another account cannot open the order')

    print('\nD. The platform team confirms the payment')
    a = ctx()
    a.goto(BASE + '/wp-login.php'); a.fill('#user_login', ADMIN[0]); a.fill('#user_pass', ADMIN[1]); a.click('#wp-submit'); a.wait_for_load_state()
    a.goto(BASE + '/wp-admin/admin.php?page=sm-subscriptions&order=' + oid)
    t = txt(a)
    check(ref in t and 'Virement fait ce matin' in t and a.locator('a:has-text("proof")').count() + a.locator('a:has-text("preuve")').count() >= 1, 'admin sees the order, the note and the proof link')
    href = a.locator('a[href*="smsub_proof"]').get_attribute('href')
    r = a.request.get(href)
    check(r.status == 200 and r.headers.get('content-type', '').startswith('application/pdf'), 'the proof opens for the team (PDF)')
    r = k.request.get(href)
    check(r.status in (403, 404) or b'%PDF' not in r.body(), 'another account cannot open the proof')
    shot(a, 'd-admin-order')
    a.click('button[name="do"][value="approve"]'); a.wait_for_load_state()
    inv_no = wp('echo SM\\Subscriptions\\Orders::get(%s)->invoice_no;' % oid)
    check(re.match(r'FAC-\d{4}-\d{5}', inv_no or ''), 'payment confirmed: invoice number ' + inv_no)

    print('\nE. Pro is active: limits, contacts, invoice')
    c.goto(BASE + '/compte/abonnement/')
    t = txt(c)
    check('Pro' in txt(c, '.sm-subnow__name') and 'Payé jusqu’au' in t and '1 / 40' in t, 'plan page: Pro paid until next year, 1 listing of 40')
    check('Renouveler 1 an' in t and 'Offre membres fondateurs' not in t, 'renew buttons; founders banner gone')
    check('Payée' in t and c.locator('.sm-orders a[href*="/facture/"]').count() == 1, 'order history: paid, invoice link')
    shot(c, 'e-plan-pro')
    c.click('.sm-orders a[href*="/facture/"]'); c.wait_for_load_state()
    t = txt(c)
    check(inv_no in t and 'TEST PLATEFORME SARL' in t and 'Aciers E2ES' in t, 'invoice: number, seller and buyer')
    check('29 411,76 DA' in t and '5 588,24 DA' in t and '35 000,00 DA' in t and 'TVA 19 %' in t, 'invoice: 29 411,76 HT + TVA 19 % 5 588,24 = 35 000,00 TTC')
    shot(c, 'e-invoice')
    k.goto(c.url)
    check('Facture introuvable' in txt(k), 'another account cannot open the invoice')
    v.goto(BASE + '/entreprise/' + co_slug + '/?lang=fr')
    check('0550' in txt(v) and 'contact-e2es@example.test' in txt(v), 'with Pro, visitors see the phone and e-mail')

    print('\nF. Options on a listing')
    c.goto(BASE + '/compte/annonces/')
    check(c.locator('.smp-row__boost button').count() == 3, 'three options on the listing online')
    shot(c, 'f-listings-options')
    c.click('.smp-row__boost form:has(input[value="urgent"]) button'); c.wait_for_load_state()
    t = txt(c)
    check('/commande/' in c.url and 'Badge « Urgent », 7 jours' in t and '500 DA' in t, 'option order: Urgent badge 7 days, 500 DA')
    ooid = re.search(r'order=(\d+)', c.url).group(1)
    wp('SM\\Subscriptions\\Orders::approve(SM\\Subscriptions\\Orders::get(%s));' % ooid)
    c.goto(BASE + '/compte/annonces/')
    check('Urgent jusqu’au' in txt(c) and c.locator('.smp-row__boost form:has(input[value="urgent"])').count() == 0, 'after payment: Urgent until …, option no longer offered')
    lid = 'E2ES' + stamp
    v.goto(BASE + '/produits/?lang=fr')
    check(v.locator('.sm-chip--urgent').count() >= 1, 'Urgent badge on the card in the catalogue')

    print('\nG. A client buys AI credits')
    k.goto(BASE + '/compte/credits-ia/')
    t = txt(k)
    check('5 crédits restants' in t or 'crédits restants' in t, 'client: credits of the month')
    check(k.locator('.sm-pack').count() == 2, 'two packs to buy')
    shot(k, 'g-ai-credits')
    k.click('.sm-pack:first-child button'); k.wait_for_load_state()
    t = txt(k)
    check('/commande/' in k.url and '50 crédits IA' in t and '1 000 DA' in t, 'pack order: 50 credits, 1 000 DA')
    k.click('.sm-order__cancel button'); k.wait_for_load_state()
    check('Commande annulée' in notice(k), 'an order waiting for payment can be cancelled')

    print('\nH. Mobile and Arabic')
    m = ctx(390, 844)
    login(m, CO)
    m.goto(BASE + '/compte/abonnement/?lang=ar')
    check(m.locator('html[dir="rtl"]').count() == 1 and 'احترافي' in txt(m) and m.evaluate('document.documentElement.scrollWidth') <= 391, 'plan page in Arabic on a phone, no side scroll')
    shot(m, 'h-plan-ar-mobile')

    check(not errors, 'no JavaScript error' + ('' if not errors else ': ' + '; '.join(errors[:3])))
    b.close()

# Put the payment settings back.
if before and before != 'null':
    open(os.path.join(OUT, '.settings.json'), 'w').write(before)
    wp('update_option("sm_sub_settings", json_decode(file_get_contents("%s"), true), false);' % os.path.abspath(os.path.join(OUT, '.settings.json')))
else:
    wp('delete_option("sm_sub_settings");')

ok = sum(1 for r in results if r[0])
print(f'\n{ok} passed, {len(results) - ok} failed')
sys.exit(0 if ok == len(results) else 1)
