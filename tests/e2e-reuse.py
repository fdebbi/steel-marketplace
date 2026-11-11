"""
End-to-end browser tests of the reuse market (sm-reuse on the sm-products engine), Phase 5.
Usage: python3 tests/e2e-reuse.py URL WP_PATH OUT
Needs the seller and engineer accounts created by tests/e2e.py (e2e-com…, e2e-eng…).
Expected weights are read from the weight engine, never typed here.
"""
import os, sys, re, subprocess
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-reuse-output'
FIX = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fixtures')
os.makedirs(OUT, exist_ok=True)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def txt(pg, sel):
    return pg.inner_text(sel).replace(' ', ' ').replace('\xa0', ' ')
def fr(v, d=1):
    s = f'{v:,.{d}f}'.replace(',', ' ').replace('.', ',')
    return s
def settle(pg):
    pg.wait_for_timeout(750)
    pg.wait_for_function('!document.querySelector("[data-smp-tech][aria-busy]")')
    pg.wait_for_timeout(120)
def pick(pg, sel, prefix):
    pg.select_option(sel, pg.evaluate('([s, p]) => [...document.querySelectorAll(s + " option")].find(o => o.textContent.trim().startsWith(p)).value', [sel, prefix]))
def login(pg, user, pwd):
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', user); pg.fill('#l-pass', pwd)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()
def kgm(fam, sec):
    return float(wp('$r = SM\\Core\\Modules::service("weight")->calculate(array("family"=>"%s","section"=>"%s","bars"=>1,"length"=>1000)); echo $r["total_kg"];' % (fam, sec)))

seller = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
eng = wp('echo get_users(array("search"=>"e2e-eng*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
for u in (seller, eng):
    wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % u)
wp('$c = SM\\Core\\Accounts\\Companies::for_user(get_user_by("email","%s")->ID); foreach (SM\\Products\\Products::query(array("status"=>"any","company"=>$c->id,"market"=>"reuse","per_page"=>60))["items"] as $p) SM\\Products\\Products::delete($p->id);' % seller)
import time
TAG = 'R%d' % (int(time.time()) % 100000)
w1 = 6 * 2.5 * kgm('IPE', 'IPE 200'); w2 = 3 * 4 * kgm('HEA', 'HEA 160')

with sync_playwright() as p:
    b = p.chromium.launch()
    ctx = b.new_context(viewport={'width': 390, 'height': 844}, reduced_motion='reduce'); pg = ctx.new_page()
    errors = []; pg.on('pageerror', lambda e: errors.append(str(e)))
    login(pg, seller, 'Acier-2026-test!')

    print('\nA. Publish an inventory lot (mobile)')
    pg.goto(BASE + '/compte/publier/?market=reuse')
    check(pg.locator('.smp-market').count() >= 2 and pg.locator('.smp-market.is-current:has-text("Réemploi")').count() == 1, 'market chooser offers the markets, reuse selected')
    check(pg.locator('.smp-cat').count() == 6, '6 kinds of reuse lots offered')
    pg.click('.smp-cat:has-text("Chutes de profilés")'); pg.wait_for_load_state()
    check(pg.locator('.smp-cond').count() == 5, 'five condition classes')
    pg.click('.smp-cond:has(input[value="good"])'); settle(pg)
    pg.select_option('#smp-a-origin', 'dismantling'); settle(pg)
    pg.check('#smp-a-cut'); settle(pg)
    pick(pg, '#smp-l0-s', 'IPE 200'); settle(pg)
    pg.fill('#smp-l0-q', '6'); pg.fill('#smp-l0-l', '2,5'); settle(pg)
    check(pg.locator('[data-smp-line]').count() == 2 and fr(w1) in txt(pg, '[data-smp-line]:nth-child(1) .smp-line__result'), 'row 1 computed (%s kg) and a new empty row added' % fr(w1))
    pg.select_option('#smp-l1-f', 'HEA'); settle(pg)
    pick(pg, '#smp-l1-s', 'HEA 160'); settle(pg)
    pg.fill('#smp-l1-q', '3'); pg.fill('#smp-l1-l', '4'); settle(pg)
    summ = txt(pg, '.smp-summary')
    check(pg.locator('[data-smp-line].is-done').count() == 2 and fr(w1 + w2) in summ and '9' in summ and '2 profils' in summ, 'two sizes: %s kg, 9 pieces' % fr(w1 + w2))
    pick(pg, '#smp-l2-s', 'HEA 160'); pg.fill('#smp-l2-q', '1'); pg.fill('#smp-l2-l', '1'); settle(pg)
    check(pg.locator('[data-smp-line].is-done').count() == 3, 'a third row added')
    pg.click('[data-smp-line]:nth-child(3) [data-smp-line-remove]'); settle(pg)
    check(pg.locator('[data-smp-line].is-done').count() == 2 and pg.locator('[data-smp-line]').count() == 3, 'row removed with its button')
    pg.check('input[name="sale_mode"][value="lot"]', force=True); settle(pg); pg.fill('#smp-lot', '250000'); settle(pg)
    summ = txt(pg, '.smp-summary')
    check('Prix du lot' in summ and '250 000' in summ and 'kg' in summ, 'price for the whole lot, with its per-kg equivalent')
    check(pg.locator('.smp-advice-note').count() == 1, 'seller told that buyers will see the engineer advice')
    pg.screenshot(path=os.path.join(OUT, 'publish-reuse-390.png'), full_page=True)
    pg.fill('#smp-title-fr', 'Chutes IPE et HEA de démontage ' + TAG)
    pg.set_input_files('input[name="photos[]"]', os.path.join(FIX, 'photo.jpg'))
    pg.select_option('#smp-wilaya', '28')
    pg.click('button[name="intent"][value="submit"]'); pg.wait_for_load_state()
    check('/compte/annonces/' in pg.url, 'lot submitted')
    lid = wp('global $wpdb; echo $wpdb->get_var("SELECT listing_id FROM {$wpdb->prefix}sm_products WHERE market = \'reuse\' ORDER BY id DESC LIMIT 1");')
    wp('global $wpdb; SM\\Products\\Products::set_status($wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_products WHERE listing_id = %s"), "published");' % lid)

    print('\nB. Edit keeps the inventory')
    pg.goto(BASE + '/compte/publier/?edit=' + wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_products WHERE listing_id = %s");' % lid))
    check(pg.locator('[data-smp-line].is-done').count() == 2 and pg.input_value('#smp-l1-l') == '4' and pg.is_checked('.smp-cond input[value="good"]') and pg.is_checked('#smp-a-cut'), 'rows, condition and cutting reloaded')

    print('\nC. Rules')
    pg.goto(BASE + '/compte/publier/?market=reuse&cat=reuse-scrap')
    pg.click('.smp-cond:has(input[value="good"])'); settle(pg)
    check('Ferraille / recyclage uniquement' in txt(pg, '#err-condition'), 'scrap cannot be presented as reusable (live message)')
    pg.click('.smp-cond:has(input[value="scrap"])'); settle(pg)
    check(pg.locator('#err-condition').count() == 0 and pg.locator('.smp-advice-note').count() == 0, 'scrap condition accepted, no engineer note')
    pg.select_option('#smp-s-scrap_type', 'heavy'); settle(pg)
    pg.fill('#smp-total', '8000'); pg.fill('#smp-price', '45'); settle(pg)
    check('360 000' in txt(pg, '.smp-summary') and 'Poids estimé' in txt(pg, '.smp-summary'), 'scrap by weight: 8 000 kg × 45 DA, estimated weight')

    print('\nD. Public pages')
    pub = b.new_page(viewport={'width': 1280, 'height': 900}, reduced_motion='reduce')
    pub.goto(BASE + '/reemploi/')
    card = '.sm-card--listing:has-text("démontage %s")' % TAG
    check(pub.locator(card).count() == 1 and '9 pcs' in txt(pub, card), 'listed in the reuse market with its piece count')
    pub.goto(BASE + '/reemploi/?condition=excellent')
    check(pub.locator(card).count() == 0, 'condition filter')
    pub.goto(BASE + '/reemploi/?length_min=5')
    check(pub.locator(card).count() == 0, 'minimum length filter')
    pub.goto(BASE + '/reemploi/?q=HEA160')
    check(pub.locator(card).count() == 1, 'found by the second size (HEA160)')
    pub.goto(BASE + '/produits/')
    check(pub.locator(card).count() == 0, 'not in the new products market')
    pub.goto(BASE + '/annonce/%s/' % lid)
    check(pub.locator('.sm-table--stack tbody tr').count() == 2 and 'Inventaire du lot' in pub.inner_text('.sm-detail'), 'product page: lot inventory table')
    check('Bon état' in pub.inner_text('.sm-spec') or 'bon état' in pub.inner_text('.sm-spec'), 'condition shown in the specification')
    check(pub.locator('.sm-advice').count() == 1 and 'ingénieur' in pub.inner_text('.sm-advice') and pub.locator('.sm-advice a').count() == 1, 'engineer advice with a link to the engineers')
    check('Prix du lot entier' in txt(pub, '.sm-priceblock') and '250 000' in txt(pub, '.sm-priceblock'), 'price block: price of the whole lot')
    pub.screenshot(path=os.path.join(OUT, 'reuse-fr-1280.png'), full_page=True)
    ar = b.new_page(viewport={'width': 390, 'height': 844}, reduced_motion='reduce')
    ar.goto(BASE + '/ar/annonce/%s/' % lid)
    check(ar.evaluate('document.documentElement.dir') == 'rtl' and 'مهندس' in ar.inner_text('.sm-advice') and 'جرد الدفعة' in ar.inner_text('.sm-detail'), 'Arabic product page: advice and inventory translated')
    ar.screenshot(path=os.path.join(OUT, 'reuse-ar-390.png'), full_page=True)

    print('\nE. Engineering account')
    ectx = b.new_context(viewport={'width': 390, 'height': 844}); ep = ectx.new_page()
    login(ep, eng, 'Acier-2026-test!')
    ep.goto(BASE + '/compte/publier/?market=reuse')
    check(ep.locator('.smp-form').count() == 0 and ep.locator('.smp-cats').count() == 0, 'an engineering account gets no publication form')
    ectx.close()
    check(not errors, 'no JavaScript errors' + (': ' + errors[0] if errors else ''))
    b.close()

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
