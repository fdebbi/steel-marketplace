"""
End-to-end browser tests of orders and offers (sm-trade 0.1.0, sm-products 0.10.0,
theme 0.24.0): filters of the results pages, direct publication with an e-mail to the
administrators, deletion with a reason, "Buy" and "Make an offer", answers, stock,
contact details, history and CSV export in the administration.

Usage: python3 tests/e2e-trade.py URL WP_PATH OUT

Creates a seller company (e2et-sel…), a buyer client (e2et-buy…) and a second buyer
(e2et-two…), removed with their listings and deals at the next run.
Admin: admin / Admin-2026-test! (env SM_ADMIN="user:password").
"""
import os, re, subprocess, sys, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-trade-output'
os.makedirs(OUT, exist_ok=True)
stamp = str(int(time.time()))[-6:]
PWD = 'Acier-2026-test!'
SEL, BUY, TWO = f'e2et-sel{stamp}@example.test', f'e2et-buy{stamp}@example.test', f'e2et-two{stamp}@example.test'
ADMIN = os.environ.get('SM_ADMIN', 'admin:Admin-2026-test!').split(':', 1)

results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def norm(s):
    return ' '.join(s.split()).replace(' ', ' ').replace('\xa0', ' ')
def txt(pg, sel='body'):
    return norm(pg.inner_text(sel))
def shot(pg, name, full=True):
    pg.screenshot(path=os.path.join(OUT, name + '.png'), full_page=full)
def login(pg, user, pwd=PWD):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', pwd)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()
def count(url):
    m = re.search(r'<strong>([\d\s \xa0]+)</strong> annonce', subprocess.run(['curl', '-s', url], capture_output=True, text=True).stdout)
    return int(re.sub(r'\D', '', m.group(1))) if m else -1
def notes(email, ntype):
    return int(wp('global $wpdb; echo (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %%d AND type = %%s", get_user_by("email","%s")->ID, "%s"));' % (email, ntype)) or 0)
def admin_notes(ntype):
    return int(wp('global $wpdb; echo (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %%d AND type = %%s", get_user_by("login","%s")->ID, "%s"));' % (ADMIN[0], ntype)) or 0)

# Accounts of the previous run.
wp('''require_once ABSPATH."wp-admin/includes/user.php"; global $wpdb;
foreach (get_users(array("search"=>"e2et-*","search_columns"=>array("user_email"))) as $u) {
  $c = SM\\Core\\Accounts\\Companies::for_user($u->ID);
  if ($c) {
    foreach ($wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_trade_deals WHERE seller_company_id = %d OR buyer_company_id = %d", $c->id, $c->id)) as $d) { $wpdb->delete($wpdb->prefix."sm_trade_events", array("deal_id"=>$d)); $wpdb->delete($wpdb->prefix."sm_trade_deals", array("id"=>$d)); }
    foreach ($wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id = %d", $c->id)) as $p) SM\\Products\\Products::delete($p);
    foreach (array("sm_subscriptions","sm_company_members","sm_company_activities","sm_notifications") as $t) { $wpdb->delete($wpdb->prefix.$t, array("company_id"=>$c->id)); }
    $wpdb->delete($wpdb->prefix."sm_companies", array("id"=>$c->id)); }
  $wpdb->delete($wpdb->prefix."sm_notifications", array("user_id"=>$u->ID));
  wp_delete_user($u->ID); }''')
mk = '$u = wp_insert_user(array("user_login"=>"%s","user_email"=>"%s","user_pass"=>"%s","role"=>"sm_member","display_name"=>"%s")); update_user_meta($u, "sm_locale", "fr"); update_user_meta($u, "sm_phone", "%s"); $c = SM\\Core\\Accounts\\Companies::create(array("account_class"=>"%s","name"=>"%s","wilaya"=>"28","phone"=>"%s"), $u, array("%s")); SM\\Core\\Accounts\\Companies::raise_level($c, %d); echo $c;'
c_sel = wp(mk % (SEL, SEL, PWD, 'Samir Vendeur', '0661 22 33 44', 'company', f'E2ET Aciers {stamp}', '0661 22 33 44', 'supplier', 2))
wp(mk % (BUY, BUY, PWD, 'Karim Acheteur', '0550 12 34 56', 'client', f'E2ET Karim {stamp}', '0550 12 34 56', 'client', 1))
wp(mk % (TWO, TWO, PWD, 'Nadia Acheteuse', '0770 98 76 54', 'client', f'E2ET Nadia {stamp}', '0770 98 76 54', 'client', 1))
wp('SM\\Subscriptions\\Subscriptions::activate(%s, "pro", "month");' % c_sel)  # Pro: room for the test listings.
# Listings of the seller: 10 bars of IPE 160 per kg; 2 000 kg of HEA 200, lot or detail.
mkl = '$s = SM\\Products\\Spec::build(%s); echo SM\\Products\\Products::get(SM\\Products\\Products::save(0, %s, get_user_by("email","%s")->ID, $s, "published"))->listing_id;'
spec_bars = 'array("wilaya"=>"16","title"=>array("fr"=>"x"),"price"=>"165","category"=>"profiles","family"=>"IPE","section"=>"IPE 160","bars"=>"10","price_unit"=>"kg")'
spec_kg = 'array("wilaya"=>"28","title"=>array("fr"=>"x"),"price"=>"200","category"=>"profiles","family"=>"HEA","section"=>"HEA 200","qty_mode"=>"weight","total_kg"=>"2000","sale_mode"=>"both","lot_price"=>"380000")'
l_bars = wp(mkl % (spec_bars, c_sel, SEL))
l_kg = wp(mkl % (spec_kg, c_sel, SEL))
p_bars = wp('echo SM\\Products\\Products::by_listing(%s)->id;' % l_bars)
p_kg = wp('echo SM\\Products\\Products::by_listing(%s)->id;' % l_kg)

# Administration in French for the run (put back at the end).
admin_locale = wp('echo get_user_meta(get_user_by("login","%s")->ID, "locale", true);' % ADMIN[0])
wp('update_user_meta(get_user_by("login","%s")->ID, "locale", "fr_FR");' % ADMIN[0])

errors = []
with sync_playwright() as p:
    b = p.chromium.launch()

    print('A. Filters of the results pages')
    all_n = count(BASE + '/produits/')
    ver_n = count(BASE + '/produits/?verified=1')
    check(all_n >= 2 and 2 <= ver_n < all_n + 1, '"Verified sellers only" filters the listings (%d of %d)' % (ver_n, all_n))
    check(count(BASE + '/produits/?category=profiles') >= 2, 'category "Profiles" shows the listings')
    v = b.new_page(viewport={'width': 1280, 'height': 900})
    v.on('pageerror', lambda e: errors.append(str(e)))
    v.goto(BASE + '/produits/?lang=fr')
    before = txt(v, '[data-sm-count-label]')
    v.check('#sm-filters input[name="verified"]')
    v.wait_for_function('document.querySelector("[data-sm-count-label]").textContent.indexOf("%s") === -1 || true' % before)
    v.wait_for_timeout(1200)
    after = txt(v, '[data-sm-count-label]')
    check(re.search(r'Afficher %d r' % ver_n, after) is not None, 'button counts the results of the chosen filters at once ("%s")' % after)
    v.fill('#f-q', 'zzzz-introuvable'); v.wait_for_timeout(1300)
    check('Afficher 0 r' in txt(v, '[data-sm-count-label]'), 'typing a word updates the count (0)')
    v.fill('#f-q', '')
    v.click('#sm-filters [data-sm-count-label]'); v.wait_for_load_state()
    check('verified=1' in v.url and 'q=' not in v.url and 'price_min=' not in v.url and 'wilaya=' not in v.url, 'address keeps only the filters used (%s)' % v.url.split('?')[-1])
    b_all = count(BASE + '/batiments/')
    check(b_all > 0 and count(BASE + '/batiments/?area_min=1000000') == 0 and count(BASE + '/batiments/?height_min=1000') == 0 and count(BASE + '/batiments/?height_min=1') == b_all, 'buildings: minimum area and eaves height work')

    print('B. Listing page: Buy / Make an offer')
    v.goto(BASE + '/annonce/%s/?lang=fr' % l_bars)
    acts = txt(v, '.sm-detail__actions')
    check('Acheter' in acts and 'Faire une offre' in acts and 'Contacter le vendeur' in acts, 'buttons Buy, Make an offer, Contact the seller')
    check(v.locator('#sm-buy').is_hidden(), 'order form folded until asked')
    v.click('[data-sm-buy="order"]'); v.wait_for_timeout(400)
    check(v.locator('#sm-buy').is_visible() and 'Se connecter pour continuer' in txt(v, '#sm-buy'), 'visitor: form opens, asks to sign in')
    shot(v, 'b-visitor', False)
    bb = b.new_context(viewport={'width': 1280, 'height': 900}).new_page()
    bb.on('pageerror', lambda e: errors.append(str(e)))
    login(bb, BUY)
    bb.goto(BASE + '/annonce/%s/?lang=fr' % l_bars)
    bb.click('[data-sm-buy="order"]'); bb.wait_for_timeout(400)
    check(bb.input_value('#buy-phone') == '0550 12 34 56' and bb.input_value('#buy-name') == 'Karim Acheteur', 'buyer details prefilled from the account')
    check('Disponible : 10 pièces' in txt(bb, '#sm-buy'), 'bars: quantity in pieces, 10 available')
    bb.fill('#buy-qty', '4'); bb.wait_for_timeout(200)
    unit = float(wp('echo SM\\Products\\Stock::options(SM\\Products\\Products::get(%s))["detail"]["unit_price"];' % p_bars))
    tot = txt(bb, '[data-sm-buy-total]')
    check(re.sub(r'\D', '', tot) == str(round(4 * unit)), 'total at the listed price computed live (%s)' % tot)
    bb.fill('#buy-qty', '25'); bb.fill('#buy-msg', 'Livraison à Alger')
    bb.click('[data-sm-buy-submit]'); bb.wait_for_load_state()
    check('Seulement 10' in txt(bb, '#sm-buy') and bb.locator('#sm-buy').is_visible() and bb.input_value('#buy-msg') == 'Livraison à Alger', 'too much: error in the form, values kept')
    check(bb.locator('#sm-enquiry .sm-notice--error').count() == 0, 'the error is not repeated in the contact form')
    bb.fill('#buy-qty', '4'); bb.click('[data-sm-buy-submit]'); bb.wait_for_load_state()
    check('/compte/transaction/' in bb.url and 'Rien n’est payé' in txt(bb, '.sm-notice--success'), 'order sent: order page, nothing paid on the platform')
    ref1 = txt(bb, '.sm-subnow__kicker').split(' ')[0]
    check(ref1.startswith('ACH-') and 'En attente du vendeur' in txt(bb, '.sm-dealview') and bb.locator('.sm-dealview__contact').count() == 0, 'reference %s, waiting for the seller, seller contacts not shown yet' % ref1)
    shot(bb, 'c-order-buyer')
    check(notes(SEL, 'trade.order') == 1, 'seller notified (site and e-mail)')
    bb.goto(BASE + '/annonce/%s/?lang=fr' % l_bars)
    check(bb.locator('#sm-buy').is_visible() and ref1 in txt(bb, '#sm-buy'), 'back on the listing: "you already have %s waiting"' % ref1)

    print('C. Seller answers')
    sp = b.new_context(viewport={'width': 1280, 'height': 900}).new_page()
    sp.on('pageerror', lambda e: errors.append(str(e)))
    login(sp, SEL)
    sp.goto(BASE + '/compte/ventes/')
    check(ref1 in txt(sp, '.sm-deals') and sp.locator('.sm-deal.is-turn').count() == 1 and 'Votre réponse est attendue' in txt(sp, '.sm-deals'), '"Orders and offers received": %s marked "your answer is awaited"' % ref1)
    check(sp.locator('#sm-dash-nav .sm-count, .sm-dash__tabs .sm-count').count() >= 1, 'counter in the menu')
    shot(sp, 'd-sales-list')
    sp.click('.sm-deal__link'); sp.wait_for_load_state()
    check('0550 12 34 56' in txt(sp, '.sm-dealview__contact') and 'Livraison à Alger' in txt(sp, '.sm-dealview'), 'seller sees the buyer\'s name, phone, e-mail and message')
    shot(sp, 'e-order-seller')
    sp.fill('#deal-note-a', 'Disponible jeudi au dépôt')
    sp.click('.sm-dealview__form:has(input[value="accept"]) button'); sp.wait_for_load_state()
    check('Acceptée' in txt(sp, '.sm-dealview') and 'directement' in txt(sp, '.sm-notice--success'), 'accepted')
    check(wp('echo (float) SM\\Products\\Products::get(%s)->pieces;' % p_bars) == '6', 'stock: 4 bars taken, 6 left on the listing')
    bb.goto(BASE + '/compte/achats/')
    bb.click('.sm-deal__link'); bb.wait_for_load_state()
    cont = txt(bb, '.sm-dealview__contact')
    check('0661 22 33 44' in cont and 'E2ET Aciers' in cont and 'Disponible jeudi' in txt(bb, '.sm-timeline'), 'buyer gets the seller\'s contact details and message')
    check(notes(BUY, 'trade.accepted') == 1, 'buyer notified of the acceptance')
    shot(bb, 'f-accepted-buyer')
    bb.goto(BASE + '/annonce/%s/?lang=fr' % l_bars)
    bb.click('[data-sm-buy="order"]'); bb.wait_for_timeout(300)
    check('Disponible : 6 pièces' in txt(bb, '#sm-buy'), 'listing page shows the 6 bars left')

    print('D. Offer, counter-offer, acceptance by the buyer')
    tp = b.new_context(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True).new_page()
    tp.on('pageerror', lambda e: errors.append(str(e)))
    login(tp, TWO)
    tp.goto(BASE + '/annonce/%s/?lang=fr' % l_kg)
    tp.click('[data-sm-buy="offer"]'); tp.wait_for_timeout(400)
    check(tp.locator('#buy-amount').is_visible() and tp.locator('input[name="mode"][value="lot"]').count() == 1, 'offer: price field, choice whole lot / quantity')
    tp.check('input[name="mode"][value="lot"]', force=True); tp.wait_for_timeout(200)
    check('380 000' in txt(tp, '[data-sm-buy-total]') and tp.locator('#buy-qty').is_hidden(), 'whole lot: 380 000 DA, no quantity to type')
    tp.fill('#buy-amount', '330 000')
    check('Envoyer l’offre' in txt(tp, '[data-sm-buy-submit]'), 'button says "Send the offer"')
    shot(tp, 'g-offer-mobile')
    tp.click('[data-sm-buy-submit]'); tp.wait_for_load_state()
    ref2 = txt(tp, '.sm-subnow__kicker').split(' ')[0]
    check(ref2.startswith('OFR-') and '330 000' in txt(tp, '.sm-order__amount'), 'offer %s sent at 330 000 DA' % ref2)
    sp.goto(BASE + '/compte/ventes/?etat=open')
    sp.click('.sm-deal:has-text("%s") .sm-deal__link' % ref2); sp.wait_for_load_state()
    sp.fill('#deal-amount', '360000'); sp.fill('#deal-note-c', 'Prix ferme')
    sp.click('.sm-dealview__form:has(input[value="counter"]) button'); sp.wait_for_load_state()
    check('En attente de l’acheteur' in txt(sp, '.sm-dealview') and '360 000' in txt(sp, '.sm-order__amount'), 'seller proposes 360 000 DA: buyer\'s turn')
    check(notes(TWO, 'trade.countered') == 1, 'buyer notified of the new price')
    tp.goto(BASE + '/compte/achats/')
    check(tp.locator('.sm-deal.is-turn').count() == 1, 'buyer\'s list: answer awaited')
    tp.click('.sm-deal__link'); tp.wait_for_load_state()
    check('Prix ferme' in txt(tp, '.sm-timeline') and tp.locator('.sm-dealview__form:has(input[value="counter"])').count() == 1, 'buyer sees the message and may answer with a price')
    tp.click('.sm-dealview__form:has(input[value="accept"]) button'); tp.wait_for_load_state()
    check('Acceptée' in txt(tp, '.sm-dealview') and wp('echo SM\\Products\\Products::get(%s)->status;' % p_kg) == 'sold', 'buyer accepts 360 000 DA: whole lot sold, listing marked sold')
    shot(tp, 'h-offer-accepted-mobile')
    ar = b.new_context(viewport={'width': 390, 'height': 844}).new_page()
    login(ar, TWO); ar.goto(BASE + '/ar/compte/achats/')
    check(ar.evaluate('document.documentElement.dir') == 'rtl' and 'مقبول' in txt(ar, '.sm-deals'), 'Arabic: list of purchases translated')
    shot(ar, 'i-purchases-ar')

    print('E. Administration: history, export, publication, deletion')
    a = b.new_page(viewport={'width': 1366, 'height': 900})
    a.on('dialog', lambda d: d.accept())
    a.goto(BASE + '/wp-login.php'); a.fill('#user_login', ADMIN[0]); a.fill('#user_pass', ADMIN[1]); a.click('#wp-submit'); a.wait_for_load_state()
    a.goto(BASE + '/wp-admin/admin.php?page=sm-trade')
    check(ref1 in txt(a, '.widefat') and ref2 in txt(a, '.widefat'), 'history lists both deals')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-trade&kind=offer')
    check(ref2 in txt(a, '.widefat') and ref1 not in txt(a, '.widefat'), 'filter by type')
    a.click('a:has-text("%s")' % ref2); a.wait_for_load_state()
    steps = txt(a, '.widefat')
    check(steps.count('Autre prix proposé') == 1 and 'Prix ferme' in steps and 'Acceptée' in steps, 'detail: every step with prices and messages')
    shot(a, 'j-admin-detail')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-trade')
    with a.expect_download() as dl:
        a.click('a.button:has-text("Exporter")')
    path = dl.value.path()
    csv = open(path, encoding='utf-8-sig').read()
    check(csv.startswith('Référence;') and ref1 in csv and ref2 in csv and '360000,00' in csv, 'CSV export (Excel, ";"): both deals with prices')

    print('F. Direct publication, e-mail to the administrators, deletion with a reason')
    check(wp('echo (int) SM\\Products\\Settings::get("moderation"), (int) SM\\Products\\Settings::get("client_moderation");') == '00', 'checks before publication off by default')
    n0 = admin_notes('product.updated')
    sp.goto(BASE + '/compte/publier/?edit=' + p_bars)
    sp.wait_for_timeout(800)
    sp.fill('#smp-desc', 'Barres stockées sous abri, livraison possible.')
    sp.click('button[name="intent"][value="submit"]'); sp.wait_for_load_state()
    check(wp('echo SM\\Products\\Products::get(%s)->status;' % p_bars) == 'published' and admin_notes('product.updated') == n0 + 1, 'seller changes the text: online at once, administrators told by e-mail')
    v.goto(BASE + '/annonce/%s/?lang=fr' % l_bars)
    check('sous abri' in txt(v, 'main'), 'buyers see the change at once')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-products&status=published')
    row = 'tr:has(input[name="id"][value="%s"])' % p_bars
    a.fill(row + ' input[name="reason"]', 'Annonce de test retirée')
    a.click(row + ' button[value="delete"]'); a.wait_for_load_state()
    check(wp('echo SM\\Products\\Products::get(%s) ? "yes" : "no";' % p_bars) == 'no' and notes(SEL, 'product.deleted') == 1, 'administrator deletes with a reason: listing gone, seller told')

    check(not errors, 'no JavaScript error' + ('' if not errors else ': ' + '; '.join(errors[:3])))
    b.close()

wp('update_user_meta(get_user_by("login","%s")->ID, "locale", "%s");' % (ADMIN[0], admin_locale))
ok = sum(1 for r in results if r[0])
print(f'\n{ok} passed, {len(results) - ok} failed')
sys.exit(0 if ok == len(results) else 1)
