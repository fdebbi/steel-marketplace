"""
End-to-end browser tests of requests for quotation (sm-rfq), Phase 7.
Usage: python3 tests/e2e-rfq.py URL WP_PATH OUT
Uses the buyer account created by tests/e2e.py (e2e-com…) and the engineer (e2e-eng…);
creates two supplier companies for the run and removes them at the end.
"""
import os, sys, re, subprocess, time, datetime
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-rfq-output'
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
os.makedirs(OUT, exist_ok=True)
TAG = 'Q%d' % (int(time.time()) % 100000)
PWD = 'Acier-2026-test!'
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def txt(pg, sel):
    return pg.inner_text(sel).replace(' ', ' ').replace('\xa0', ' ')
def settle(pg):
    pg.wait_for_timeout(800)
    pg.wait_for_function('!document.querySelector("[data-smp-tech][aria-busy]")')
    pg.wait_for_timeout(150)
def pick(pg, sel, label):
    pg.select_option(sel, pg.evaluate('([s, p]) => [...document.querySelectorAll(s + " option")].find(o => o.textContent.trim() === p).value', [sel, label]))
def login(pg, user):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', PWD)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()
def supplier(key, activity, name):
    mail = 'e2eq-%s-%s@example.test' % (key, TAG.lower())
    wp('$u = wp_insert_user(array("user_login"=>"%s","user_email"=>"%s","user_pass"=>"%s","role"=>"sm_member")); update_user_meta($u, "sm_locale", "fr"); $c = SM\\Core\\Accounts\\Companies::create(array("account_class"=>"commercial","name"=>"%s","wilaya"=>"31","phone"=>"0550 00 00 0%d"), $u, array("%s")); SM\\Core\\Accounts\\Companies::raise_level($c, 2);' % (mail, mail, PWD, name, len(key), activity))
    return mail
def kgm(fam, sec):
    return float(wp('$r = SM\\Core\\Modules::service("weight")->calculate(array("family"=>"%s","section"=>"%s","bars"=>1,"length"=>1000)); echo $r["total_kg"];' % (fam, sec)))
def fr(v, d=0):
    return f'{v:,.{d}f}'.replace(',', ' ').replace('.', ',')

buyer = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
eng = wp('echo get_users(array("search"=>"e2e-eng*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
for u in (buyer, eng):
    wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % u)
# Each run publishes requests: start with a fresh daily limit for the buyer.
wp('$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; SM\\Core\\Support\\Forms::reset_rate_limit("rfq|" . SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID)->id);' % buyer)
s1 = supplier('sup', 'supplier', 'Fournisseur A ' + TAG)
s2 = supplier('man', 'manufacturer', 'Fabricant B ' + TAG)
w = 48 * 12 * kgm('IPE', 'IPE 270') + 10 * 6 * kgm('HEA', 'HEA 200')
deadline = (datetime.date.today() + datetime.timedelta(days=10)).isoformat()

with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={'width': 390, 'height': 844}, reduced_motion='reduce'); pg = ctx.new_page()
    errors = []; pg.on('pageerror', lambda e: errors.append(str(e)))
    login(pg, buyer)

    print('\nA. Buyer publishes a request (mobile)')
    pg.goto(BASE + '/compte/mes-devis/?new=1')
    check(pg.locator('.smq-form').count() == 1, 'request form opens from "My requests"')
    pg.select_option('#smq-market', 'products'); pg.select_option('#smq-cat', 'profiles')
    pick(pg, '#smp-l0-s', 'IPE 270'); pg.fill('#smp-l0-q', '48'); pg.fill('#smp-l0-l', '12'); settle(pg)
    pg.select_option('#smp-l1-f', 'HEA'); settle(pg)
    pick(pg, '#smp-l1-s', 'HEA 200'); pg.fill('#smp-l1-q', '10'); pg.fill('#smp-l1-l', '6'); settle(pg)
    tot = txt(pg, '.smq-total')
    kg = float(re.search(r'([\d ]+,\d) kg', tot).group(1).replace(' ', '').replace(',', '.')) if re.search(r'([\d ]+,\d) kg', tot) else 0
    check('58' in tot and abs(kg - w) < 1 and pg.locator('.smq-free').count() == 0, 'two profile rows: 58 pieces, ≈ %s kg, free description hidden' % fr(w, 0))
    pg.fill('#smq-title-fr', 'Poutrelles hangar ' + TAG)
    pg.click('button[name="intent"][value="submit"]'); pg.wait_for_load_state()
    check(pg.locator('#err-description').count() == 1 and pg.locator('#err-wilaya').count() == 1 and pg.locator('[data-smp-line].is-done').count() == 2, 'missing description and wilaya explained; profile rows kept')
    pg.fill('#smq-desc', 'S275JR, barres droites, certificat 3.1 souhaité.')
    pg.select_option('#smq-wilaya', '16'); pg.fill('#smq-place', 'Rouiba')
    pg.fill('#smq-deadline', deadline)
    pg.set_input_files('input[name="files[]"]', os.path.join(FIX, 'document.pdf'))
    pg.click('button[name="intent"][value="submit"]'); pg.wait_for_load_state()
    m = re.search(r'/demandes-de-devis/(\d+)/', pg.url)
    rid = m.group(1) if m else ''
    check(rid and 'publiée' in txt(pg, '.sm-notice--success'), 'request published, buyer taken to its page')
    check(pg.locator('.smq-owner').count() == 1 and 'Aucune offre' in txt(pg, '.smq-compare'), 'buyer sees his actions and an empty comparison')
    pg.screenshot(path=os.path.join(OUT, 'rfq-buyer-390.png'), full_page=True)

    print('\nB. Public board')
    pub = b.new_page(viewport={'width': 1280, 'height': 900}, reduced_motion='reduce')
    pub.goto(BASE + '/demandes-de-devis/?q=IPE270')
    card = '.sm-card--rfq:has-text("%s")' % TAG
    check(pub.locator(card).count() == 1 and '58' in txt(pub, card) and 'Alger' in txt(pub, card), 'listed on the board, found by profile, with quantity and wilaya')
    pub.goto(BASE + '/demandes-de-devis/%s/' % rid)
    check('S275JR' in txt(pub, '.smq-main') and pub.locator('.smq-main .sm-table--stack tbody tr').count() == 2, 'visitor sees the need and the profile table')
    check(pub.locator('.smp-docs a').count() == 0 and 'connectez-vous' in txt(pub, '.smq-main').lower() and pub.locator('.smq-offer').count() == 0, 'visitor: no drawing download, no offer form')
    pub.screenshot(path=os.path.join(OUT, 'rfq-public-1280.png'), full_page=True)

    print('\nC. Suppliers answer')
    sctx = b.new_context(viewport={'width': 390, 'height': 844}, reduced_motion='reduce'); sp = sctx.new_page()
    sp.on('pageerror', lambda e: errors.append(str(e)))
    login(sp, s1)
    sp.goto(BASE + '/demandes-de-devis/%s/' % rid)
    check(sp.locator('.smq-offer').count() == 1 and sp.locator('.smp-docs a[href$=".pdf"]').count() == 1, 'supplier sees the offer form and can download the drawing')
    sp.check('input[name="price_mode"][value="kg"]', force=True)
    sp.fill('#smq-price', '235'); sp.fill('#smq-days', '7'); sp.check('input[name="delivery_included"]', force=True)
    sp.fill('#smq-pay', '30 % à la commande')
    sp.click('.smq-offer button[type="submit"]'); sp.wait_for_load_state()
    check('envoyée' in txt(sp, '.sm-notice--success') and pg is not None, 'offer sent')
    mine = txt(sp, '.smq-mine')
    check(fr(round(w, 3) * 235, 0)[:-2] in mine and '235' in mine, 'own offer shown: total = weight × 235 DA/kg')
    sctx.close()
    sctx = b.new_context(viewport={'width': 1280, 'height': 900}, reduced_motion='reduce'); sp = sctx.new_page()
    login(sp, s2)
    sp.goto(BASE + '/demandes-de-devis/%s/' % rid)
    sp.check('input[name="price_mode"][value="total"]', force=True)
    sp.fill('#smq-price', '4500000'); sp.fill('#smq-weight', '19500'); sp.fill('#smq-days', '3'); sp.fill('#smq-valid', '10')
    sp.click('.smq-offer button[type="submit"]'); sp.wait_for_load_state()
    check(sp.locator('.smq-mine').count() == 1 and sp.locator('.smq-compare').count() == 0, 'second supplier answered; he does not see the other offer')
    sp.goto(BASE + '/compte/devis-envoyes/')
    check(TAG in txt(sp, '.smp-list') and 'Envoyée' in txt(sp, '.smp-list'), '"Quotations sent" lists the offer')
    sctx.close()

    print('\nD. Buyer compares and decides')
    pgd = b.new_context(viewport={'width': 1280, 'height': 900}, reduced_motion='reduce').new_page()
    pgd.on('pageerror', lambda e: errors.append(str(e)))
    pgd.on('dialog', lambda d: d.accept())
    login(pgd, buyer)
    check(wp('global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %%d AND type = \'rfq.offer_received\'", get_user_by("email","%s")->ID));' % buyer) not in ('', '0'), 'buyer notified of the offers')
    pgd.goto(BASE + '/demandes-de-devis/%s/' % rid)
    check(pgd.locator('.smq-o').count() == 2, 'two offers side by side')
    first = txt(pgd, '.smq-o:nth-child(1)')
    check('Fabricant B' in first and 'Le moins cher' in first and 'Le plus rapide' in first, 'sorted by total: cheapest and fastest flagged')
    pgd.click('.smq-compare a:has-text("Prix au kg")'); pgd.wait_for_load_state()
    check(pgd.locator('.smq-o:nth-child(1) .is-best').count() >= 1, 'sort by price per kg')
    check(pgd.locator('.smq-o a[href^="tel:"]').count() == 2, 'suppliers can be called directly')
    pgd.screenshot(path=os.path.join(OUT, 'rfq-compare-1280.png'), full_page=True)
    pgd.click('.smq-o:has-text("Fabricant B") button:has-text("Accepter cette offre")'); pgd.wait_for_load_state()
    check('acceptée' in txt(pgd, '.sm-notice--success') and 'Attribuée' in txt(pgd, '.smq-status'), 'offer accepted, request awarded')
    check(pgd.locator('.smq-o--accepted').count() == 1 and pgd.locator('.smq-o--declined').count() == 1, 'other offer declined automatically')
    check(wp('global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %%d AND type = \'rfq.offer_accepted\'", get_user_by("email","%s")->ID));' % s2) not in ('', '0'), 'winning supplier notified')
    pub.goto(BASE + '/demandes-de-devis/?q=' + TAG)
    check(pub.locator(card).count() == 0, 'awarded request leaves the board')

    print('\nE. Arabic and engineering account')
    ar = b.new_page(viewport={'width': 390, 'height': 844}, reduced_motion='reduce')
    ar.goto(BASE + '/ar/demandes-de-devis/')
    check(ar.evaluate('document.documentElement.dir') == 'rtl' and 'طلبات عروض الأسعار' in ar.inner_text('h1'), 'Arabic board')
    ar.screenshot(path=os.path.join(OUT, 'rfq-board-ar-390.png'), full_page=True)
    ectx = b.new_context(viewport={'width': 390, 'height': 844}); ep = ectx.new_page()
    login(ep, eng)
    rid2 = wp('$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID); $s = SM\\Rfq\\Spec::build(array("market"=>"other","designation"=>"Platines","quantity"=>"20","unit"=>"pieces","title"=>array("fr"=>"Platines %s"),"description"=>"Platines 300x300x20 percées.","wilaya"=>"16","deadline"=>"%s")); echo SM\\Rfq\\Rfqs::save(0, $c->id, get_user_by("email","%s")->ID, $s, "open");' % (buyer, TAG, deadline, buyer))
    ep.goto(BASE + '/demandes-de-devis/%s/' % rid2)
    check(ep.locator('.smq-offer').count() == 0 and 'ingénierie' in txt(ep, '.smq-blocked').lower(), 'an engineering account cannot answer, and is told why')
    ectx.close()
    check(not errors, 'no JavaScript errors' + (': ' + errors[0] if errors else ''))
    b.close()

wp('foreach (get_users(array("search"=>"e2eq-*-%s@example.test","search_columns"=>array("user_email"))) as $u) { $c = SM\\Core\\Accounts\\Companies::for_user($u->ID); global $wpdb; if ($c) { $wpdb->delete($wpdb->prefix."sm_rfq_offers", array("company_id"=>$c->id)); $wpdb->delete($wpdb->prefix."sm_company_members", array("company_id"=>$c->id)); $wpdb->delete($wpdb->prefix."sm_company_activities", array("company_id"=>$c->id)); $wpdb->delete($wpdb->prefix."sm_companies", array("id"=>$c->id)); } require_once ABSPATH."wp-admin/includes/user.php"; wp_delete_user($u->ID); }' % TAG.lower())
# Remove the requests of this run (and of earlier runs of this test).
wp('global $wpdb; foreach ($wpdb->get_col("SELECT entity_id FROM {$wpdb->prefix}sm_translations WHERE entity_type = \'rfq\' AND field = \'title\' AND (value LIKE \'Poutrelles hangar Q%\' OR value LIKE \'Platines Q%\')") as $id) SM\\Rfq\\Rfqs::delete($id);')
ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
