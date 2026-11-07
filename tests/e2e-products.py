"""
End-to-end browser tests of the new products market (sm-products), Phase 4.
Usage: python3 tests/e2e-products.py URL WP_PATH OUT
Needs the seller account created by tests/e2e.py (e2e-com…) and the admin password in SM_ADMIN ("login:password").
"""
import os, sys, re, subprocess
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-products-output'
ADMIN = os.environ.get('SM_ADMIN', 'admin:Admin-2026-test!').split(':', 1)
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
os.makedirs(OUT, exist_ok=True)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def num(t):
    t = re.sub(r'[^\d,.-]', '', t.replace(' ', '').replace(' ', '')).replace(',', '.')
    return float(t) if t else None
def txt(pg, sel):
    return pg.inner_text(sel).replace('\u202f', ' ').replace('\xa0', ' ')
def settle(pg):
    pg.wait_for_timeout(700)
    pg.wait_for_function('!document.querySelector("[data-smp-tech][aria-busy]")')
    pg.wait_for_timeout(100)
def pick(pg, sel, prefix):
    pg.select_option(sel, pg.evaluate('([s, p]) => [...document.querySelectorAll(s + " option")].find(o => o.textContent.startsWith(p)).value', [sel, prefix]))
def login(pg, user, pwd):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', pwd)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()

seller = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % seller)
# Start clean: remove the products left by a previous run of this seller.
wp('$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID); foreach (SM\\Products\\Products::query(array("status"=>"any","company"=>$c->id,"per_page"=>60))["items"] as $p) SM\\Products\\Products::delete($p->id);' % seller)

with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={'width': 390, 'height': 844}); pg = ctx.new_page()
    errors = []; pg.on('pageerror', lambda e: errors.append(str(e)))
    pg.on('dialog', lambda d: d.accept())
    login(pg, seller, 'Acier-2026-test!')

    print('\nA. Publish a profile (mobile)')
    pg.goto(BASE + '/compte/publier/')
    check(pg.locator('.smp-cat').count() == 7, '7 categories offered')
    pg.click('.smp-cat:has-text("Profilés")'); pg.wait_for_load_state()
    check(pg.locator('.smp-types .smw-type').count() == 9, '9 profile types with their drawing')
    pg.click('.smw-type:has(input[value="HEA"])'); settle(pg)
    pick(pg, '#smp-section', 'HEA 200 '); settle(pg)
    check(pg.locator('.smp-profile .smw-profile__drawing svg').count() == 1 and '42,26' in txt(pg, '.smp-kgm'), 'exact drawing and weight per metre of HEA 200')
    pg.click('.smw-mode:has(input[value="weight"])'); settle(pg)
    pg.fill('#smp-total', '3000'); settle(pg)
    pg.fill('#smp-price', '230'); settle(pg)
    summ = txt(pg, '.smp-summary')
    check('≈ 5,9 × 12 m' in summ and '690 000' in summ, 'by weight: 3 000 kg ≈ 5,9 bars of 12 m, total 690 000 DA')
    # Submit without title and photo: errors, values kept.
    pg.select_option('#smp-wilaya', '28')
    pg.set_input_files('input[name="sheets[]"]', os.path.join(FIX, 'document.pdf'))
    pg.click('button[name="intent"][value="submit"]'); pg.wait_for_load_state()
    check(pg.locator('#err-title').count() == 1 and pg.locator('#err-photos').count() == 1, 'missing title and photo explained under the fields')
    check(pg.input_value('#smp-total') == '3000' and pg.input_value('#smp-price') == '230' and pg.locator('input[name="keep_sheets[]"]').count() == 1, 'entered values and the uploaded sheet are kept')
    pg.fill('#smp-title-fr', 'HEA 200 S275JR neuves')
    pg.set_input_files('input[name="photos[]"]', os.path.join(FIX, 'photo.jpg'))
    pg.click('button[name="intent"][value="submit"]'); pg.wait_for_load_state()
    check('/compte/annonces/' in pg.url and 'vérification' in pg.inner_text('.sm-notice--success'), 'sent for review, back to "My listings"')
    check('En attente de vérification' in pg.inner_text('.smp-list'), 'listed as waiting for review')
    pg.screenshot(path=os.path.join(OUT, 'my-listings-390.png'), full_page=True)
    lid = wp('global $wpdb; echo $wpdb->get_var("SELECT listing_id FROM {$wpdb->prefix}sm_products ORDER BY id DESC LIMIT 1");')

    print('\nB. Roofing (own fields, price per m²)')
    pg.goto(BASE + '/compte/publier/?cat=roofing')
    pg.select_option('#smp-s-roof_type', 'sandwich_ps'); pg.fill('#smp-s-maker_type', 'TL 40 trapézoïdal')
    pg.fill('#smp-s-width_mm', '1000'); pg.fill('#smp-s-length_min_m', '2'); pg.fill('#smp-s-length_max_m', '12')
    pg.fill('#smp-s-sheet_mm', '0,4'); pg.fill('#smp-s-insulation_mm', '40')
    pg.fill('#smp-qty', '800'); pg.fill('#smp-price', '2900'); settle(pg)
    check('2 320 000' in txt(pg, '.smp-summary'), '800 m² × 2 900 DA = 2 320 000 DA')
    pg.fill('#smp-title-fr', 'Panneaux sandwich 40 mm'); pg.set_input_files('input[name="photos[]"]', os.path.join(FIX, 'photo.jpg'))
    pg.click('button[name="intent"][value="draft"]'); pg.wait_for_load_state()
    check('Brouillon enregistré' in pg.inner_text('.sm-notice--success') and pg.input_value('#smp-s-maker_type') == 'TL 40 trapézoïdal', 'saved as draft, form reopened with its values')
    check(pg.locator('input[name="keep_photos[]"]').count() == 1, 'draft photo kept')

    print('\nC. Moderation')
    adm = b.new_page(viewport={'width': 1280, 'height': 900})
    adm.goto(BASE + '/wp-login.php'); adm.fill('#user_login', ADMIN[0]); adm.fill('#user_pass', ADMIN[1]); adm.click('#wp-submit'); adm.wait_for_load_state()
    adm.goto(BASE + '/wp-admin/admin.php?page=sm-products&status=pending')
    check('HEA 200 S275JR neuves' in adm.inner_text('#wpbody'), 'product in the moderation queue')
    adm.click('tr:has-text("HEA 200 S275JR neuves") button[value="approve"]'); adm.wait_for_load_state()
    check(wp('global $wpdb; echo $wpdb->get_var("SELECT status FROM {$wpdb->prefix}sm_products WHERE listing_id = %s");' % lid) == 'published', 'approved -> published')
    check(wp('global $wpdb; echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %%d AND type = \'product.approved\'", get_user_by("email","%s")->ID));' % seller) != '0', 'seller notified')

    print('\nD. Public pages')
    pub = b.new_page(viewport={'width': 1280, 'height': 900})
    pub.goto(BASE + '/produits/?category=profiles')
    check(pub.locator('.sm-card--listing:has-text("HEA 200 S275JR neuves")').count() == 1, 'listed in the products market (category filter)')
    pub.goto(BASE + '/annonce/%s/' % lid)
    spec = pub.inner_text('.sm-spec')
    check('HEA 200' in spec and 'EN 10365:2017' in spec and '≈ 5,9 barres' in spec, 'product page: designation, standard, quantity')
    check(pub.locator('.smp-drawing svg').count() == 1 and pub.locator('.smp-docs a[href$=".pdf"]').count() == 1, 'exact drawing and downloadable technical sheet')
    check('230' in txt(pub, '.sm-priceblock') and '690 000' in txt(pub, '.sm-priceblock'), 'price block: 230 DA/kg, total 690 000 DA')
    pub.screenshot(path=os.path.join(OUT, 'product-fr-1280.png'), full_page=True)
    ar = b.new_page(viewport={'width': 390, 'height': 844})
    ar.goto(BASE + '/ar/annonce/%s/' % lid)
    check(ar.evaluate('document.documentElement.dir') == 'rtl' and 'نوع المنتج' in ar.inner_text('.sm-spec'), 'Arabic product page translated')
    ar.screenshot(path=os.path.join(OUT, 'product-ar-390.png'), full_page=True)

    print('\nE. Seller actions')
    pg.goto(BASE + '/compte/annonces/?status=published')
    pg.click('.smp-row:has-text("HEA 200") button:has-text("Marquer vendu")'); pg.wait_for_load_state()
    check('vendu' in pg.inner_text('.sm-notice--success').lower(), 'marked as sold')
    pub.goto(BASE + '/annonce/%s/' % lid)
    check('a été vendu' in pub.inner_text('.sm-detail'), 'buyers see that it was sold')
    pg.goto(BASE + '/compte/annonces/?status=draft')
    pg.click('.smp-row:has-text("Panneaux sandwich") button:has-text("Supprimer")'); pg.wait_for_load_state()
    check('supprimé' in pg.inner_text('.sm-notice--success'), 'draft deleted after confirmation')
    check(not errors, 'no JavaScript errors' + (': ' + errors[0] if errors else ''))

    print('\nF. Arabic form and no JavaScript')
    wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "ar");' % seller)
    pg.goto(BASE + '/ar/compte/publier/?cat=tubes')
    check('أنابيب مربعة' in pg.inner_text('.smp-types') and pg.evaluate('document.documentElement.dir') == 'rtl', 'Arabic form, right-to-left')
    pg.screenshot(path=os.path.join(OUT, 'publish-ar-390.png'), full_page=True)
    nj_ctx = b.new_context(java_script_enabled=False, viewport={'width': 390, 'height': 844}); nj = nj_ctx.new_page()
    login(nj, seller, 'Acier-2026-test!')
    nj.goto(BASE + '/compte/publier/?cat=tubes&lang=fr')
    nj.check('input[name="family"][value="CHS"]', force=True)
    nj.click('noscript button, button[name="intent"][value="refresh"]') if nj.locator('button[name="intent"][value="refresh"]').count() else None
    nj.wait_for_load_state()
    check(nj.locator('#smp-section option').count() > 100, 'without JavaScript: "Update" shows the round tube sizes')
    nj_ctx.close()
    wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % seller)
    b.close()

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
