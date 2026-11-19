"""
End-to-end browser tests of the weight calculator (sm-weight-engine + theme), Phase 3.
Usage: python3 tests/e2e-weight.py http://localhost:8080 out_dir
"""
import os, sys, re
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
OUT = sys.argv[2] if len(sys.argv) > 2 else 'e2e-weight-output'
os.makedirs(OUT, exist_ok=True)
results = []
def check(cond, label):
    results.append((bool(cond), label)); print(('  ✓ ' if cond else '  ✗ ') + label)
def num(text):
    """'2 683,2 kg' -> 2683.2 ; '2,68 t 2 683,2 kg' (theme 0.20.0, tonnes from 1 000 kg) -> 2683.2"""
    kg = re.findall(r'(\d[\d \u202f\xa0]*(?:,\d+)?)\s?kg', text)
    if kg:
        text = kg[-1]
    t = re.sub(r'[^\d,.-]', '', text.replace(' ', '').replace(' ', '')).replace(',', '.')
    return float(t) if t else None
def pick(pg, prefix):
    val = pg.evaluate('(p) => [...document.querySelectorAll("#smw-section option")].find(o => o.textContent.startsWith(p)).value', prefix)
    pg.select_option('#smw-section', val)
def settle(pg):
    pg.wait_for_timeout(600)  # typing debounce (450 ms) before the refresh starts
    pg.wait_for_function('!document.querySelector("[data-smw][aria-busy]")')
    pg.wait_for_timeout(150)

with sync_playwright() as p:
    b = p.chromium.launch()

    print('\nA. Live calculator (French, mobile)')
    pg = b.new_page(viewport={'width': 390, 'height': 844})
    errors = []; pg.on('pageerror', lambda e: errors.append(str(e)))
    pg.goto(BASE + '/calcul-poids/')
    check(pg.locator('.smw-type').count() == 16 and pg.locator('.smw-type svg').count() == 16, '16 product types, each with its drawing')
    pg.click('.smw-type:has(input[value="IPE"])'); settle(pg)
    check(pg.locator('#smw-section option').count() == 19, 'IPE: 18 sizes of the European catalogue')
    check('family=IPE' in pg.url, 'the choice is kept in the address')
    pick(pg, 'IPE 200 '); settle(pg)
    check(pg.locator('.smw-profile svg path.smw-shape').count() == 1 and 'IPE 200' in pg.inner_text('.smw-profile__name'), 'exact drawing of IPE 200 shown')
    check('22,36' in pg.inner_text('.smw-dims .is-key'), 'weight per metre 22,36 kg/m')
    pg.fill('#smw-bars', '10'); settle(pg)
    check(abs(num(pg.inner_text('.smw-result__value')) - 2683.2) < 0.05, '10 bars of 12 m = 2 683,2 kg')
    check('Poids théorique' in pg.inner_text('.smw-result'), 'result marked "theoretical weight"')
    pg.select_option('#smw-length', '6000'); settle(pg)
    check(abs(num(pg.inner_text('.smw-result__value')) - 1341.6) < 0.05, '6 m bars: half the weight')
    pg.click('.smw-mode:has(input[value="weight"])'); settle(pg)
    pg.fill('#smw-total', '5000'); settle(pg)
    pg.select_option('#smw-length', '12000'); settle(pg)
    check(abs(num(pg.inner_text('.smw-result__value')) - 18.63) < 0.01, '5 000 kg = ≈ 18,63 bars of 12 m')
    lst = pg.inner_text('.smw-result__list')
    check('18 barres entières' in lst and '19 barres entières' in lst, 'whole-bar options 18 and 19 shown')
    pg.screenshot(path=os.path.join(OUT, 'weight-fr-390.png'), full_page=True)
    shared = pg.url

    # Typed tube.
    pg.click('.smw-type:has(input[value="RHS"])'); settle(pg)
    pg.fill('#smw-d-h', '120'); pg.fill('#smw-d-b', '80'); pg.fill('#smw-d-t', '4'); settle(pg)
    check('RHS 120x80x4' in pg.inner_text('.smw-profile__name'), 'typed tube RHS 120x80x4 drawn')
    pg.fill('#smw-bars', '2'); settle(pg)
    check(abs(num(pg.inner_text('.smw-result__value')) - 281.6) < 0.05, '2 × RHS 120x80x4 × 12 m = 281,6 kg')
    pg.fill('#smw-d-t', '45'); settle(pg)
    check(pg.locator('.smw-profile').count() == 0 and pg.locator('#smw-size .sm-notice--warn').count() == 1, 'impossible thickness: explained, no drawing, no result')
    # Plate.
    pg.click('.smw-type:has(input[value="PLATE"])'); settle(pg)
    pg.fill('#smw-d-t', '10'); settle(pg)
    pg.fill('#smw-width', '1500'); pg.fill('#smw-plen', '3000'); pg.fill('#smw-bars', '4'); settle(pg)
    check(abs(num(pg.inner_text('.smw-result__value')) - 1413) < 0.05 and '78,50' in pg.inner_text('.smw-result__list'), 'plate 10 mm 1500×3000 ×4 = 1 413 kg (78,5 kg/m²)')
    check(not errors, 'no JavaScript errors')

    # Shared link reopens the same calculation.
    pg2 = b.new_page(viewport={'width': 1280, 'height': 900})
    pg2.goto(shared)
    check(abs(num(pg2.inner_text('.smw-result__value')) - 18.63) < 0.01, 'a shared link reopens the same calculation')
    pg2.screenshot(path=os.path.join(OUT, 'weight-fr-1280.png'), full_page=True)

    print('\nB. Without JavaScript')
    ctx = b.new_context(java_script_enabled=False, viewport={'width': 390, 'height': 844})
    nj = ctx.new_page()
    nj.goto(BASE + '/calcul-poids/')
    nj.goto(BASE + '/calcul-poids/?family=UPN')
    check(nj.locator('#smw-section option').count() == 19, 'sizes listed without JavaScript')
    pick(nj, 'UPN 200 ')
    nj.evaluate('document.querySelector("[data-smw]").submit()'); nj.wait_for_load_state()
    nj.fill('#smw-bars', '3')
    with nj.expect_navigation():
        nj.press('#smw-bars', 'Enter')
    check(abs(num(nj.inner_text('.smw-result__value')) - 3 * 12 * 25.3) < 2, 'calculation works without JavaScript (3 × UPN 200)')
    ctx.close()

    print('\nC. Arabic')
    ar = b.new_page(viewport={'width': 390, 'height': 844})
    ar.goto(BASE + '/ar/calcul-poids/?family=HEA')
    check(ar.evaluate('document.documentElement.dir') == 'rtl' and 'نوع المنتج' in ar.inner_text('.smw'), 'Arabic page, right-to-left, translated')
    pick(ar, 'HEA 300 '); settle(ar)
    check(ar.get_attribute('.smw-profile__drawing', 'class') and ar.evaluate('getComputedStyle(document.querySelector(".smw-profile__drawing")).direction') == 'ltr', 'drawing kept left-to-right')
    ar.fill('#smw-bars', '2'); settle(ar)
    check('kg' in ar.inner_text('.smw-result__value') and 'الوزن النظري' in ar.inner_text('.smw-result'), 'Arabic result with Western digits and "theoretical weight"')
    ar.fill('#smw-bars', '0'); settle(ar)
    check('عدد' in ar.inner_text('.smw-result'), 'error message in Arabic')
    ar.screenshot(path=os.path.join(OUT, 'weight-ar-390.png'), full_page=True)

    print('\nD. Cutting optimiser (0.46.0)')
    import subprocess
    def wp(c):
        return subprocess.run(['wp', '--allow-root', '--path=/home/claude/wpenv/wp', 'eval', c], capture_output=True, text=True).stdout.strip()
    ipe = wp('echo SM\\WeightEngine\\Catalogue::find("IPE","IPE 200")->id;')
    upn = wp('echo SM\\WeightEngine\\Catalogue::find("UPN","UPN 100")->id;')
    c = b.new_page(viewport={'width': 1280, 'height': 900})
    cerr = []; c.on('pageerror', lambda e: cerr.append(str(e)))
    c.goto(BASE + '/?lang=fr')
    check(c.locator('.sm-header__utility-links a[href*="optimiseur-decoupe"]').count() == 1, 'header: link to the cutting optimiser')
    c.goto(BASE + '/optimiseur-decoupe/?lang=fr')
    check(c.locator('.smcut-empty').count() == 1 and c.locator('.smcut-free').count() == 1, 'optimiser page: free, nothing computed yet')
    rows = c.locator('[data-smcut-table="rows"] [data-smcut-row]')
    r0 = rows.nth(0); r0.locator('[data-smcut-family]').select_option('IPE')
    check(r0.locator('[data-smcut-section] option').count() == 19, 'the size list follows the profile (18 IPE sizes)')
    c.click('[data-smcut-paste]')
    c.fill('#smcut-paste', 'IPE 200\t5,10\t4\nIPE 200;3,30;6\nIPE 200;2,70;2\nUPN 100;2,40;8\nXYZ 1;2;3')
    c.click('[data-smcut-pasteok]'); c.wait_for_timeout(300)
    filled = [rows.nth(i).locator('[data-smcut-section]').input_value() for i in range(4)]
    check(filled == [ipe, ipe, ipe, upn], 'list pasted from Excel: 4 lines filled, the unknown one left out')
    check(c.locator('[data-smcut-market]').is_checked() and c.locator('[data-smcut-filters]').is_visible(), 'marketplace ticked by default, its filters shown')
    c.uncheck('[data-smcut-market]')
    check(not c.locator('[data-smcut-filters]').is_visible(), 'marketplace not ticked: filters hidden')
    c.click('.smcut-go'); c.wait_for_load_state()
    tot = c.inner_text('.smcut-totals')
    check(tot.startswith('Barres à acheter\n6') and '7,9 %' in tot, 'plan: 6 bars to buy, 7,9 %% of offcuts (%s)' % tot.split('\n')[1])
    check(c.locator('.smcut-prof').count() == 2 and c.locator('.smcut-sc.is-re').count() >= 2, 'two profiles drawn bar by bar, reusable offcuts marked')
    check('rows%5B0%5D%5Bsection%5D=' in c.url or 'rows[0][section]=' in c.url, 'the calculation is in the address (link to share)')
    c.screenshot(path=os.path.join(OUT, 'cutting-1280.png'), full_page=True)
    with c.expect_download() as dl:
        c.click('a[href*="sm_weight_cut_pdf"]')
    path = dl.value.path()
    check(open(path, 'rb').read(8) == b'%PDF-1.4', 'PDF of the cutting plan downloaded')
    # Offcuts in stock.
    c.goto(BASE + '/optimiseur-decoupe/?lang=fr&src=1&rows[0][section]=%s&rows[0][length]=2,40&rows[0][qty]=8&stock[0][section]=%s&stock[0][length]=4,90&stock[0][qty]=2' % (upn, upn))
    check('1' == c.inner_text('.smcut-k b').strip() and c.locator('.smcut-stock[open]').count() == 1, 'offcuts in stock used first: 1 new bar only')
    # Marketplace (0.47.0): published IPE 200 bars of the test site.
    c.goto(BASE + '/optimiseur-decoupe/?lang=fr&rows[0][section]=%s&rows[0][length]=2,40&rows[0][qty]=4&rows[1][section]=%s&rows[1][length]=5,10&rows[1][qty]=2' % (ipe, ipe))
    check(c.locator('.smcut-src.is-mk .smcut-item').count() >= 1 and c.locator('.smcut-k.is-mk').count() == 1, 'marketplace: listings found for the pieces, shown first')
    item = c.inner_text('.smcut-src.is-mk .smcut-item') if c.locator('.smcut-src.is-mk .smcut-item').count() else ''
    check('Annonce «' in item and 'DA' in item and c.locator('.smcut-src.is-mk a[href*="/annonce/"]').count() >= 1, 'each listing: title, price shown, estimated cost, link to buy or make an offer')
    check(c.locator('.smcut-snap').count() >= 1 and 'rien n’est réservé' in c.inner_text('.smcut-snap'), 'picture of the marketplace: nothing is reserved')
    check(c.locator('.smcut-lbl.is-mk').count() >= 1, 'plan: the bars of the marketplace are labelled')
    with c.expect_download() as dl2:
        c.click('a[href*="sm_weight_cut_pdf"]')
    check(open(dl2.value.path(), 'rb').read(8) == b'%PDF-1.4', 'PDF with the bars of the marketplace')
    # Request for quotation pre-filled (business account).
    com = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
    c.goto(BASE + '/connexion/?lang=fr'); c.fill('#l-user', com); c.fill('#l-pass', 'Acier-2026-test!')
    c.click('form:has(#l-user) button[type="submit"]'); c.wait_for_load_state()
    c.goto(BASE + '/optimiseur-decoupe/?lang=fr&src=1&rows[0][section]=%s&rows[0][length]=5,10&rows[0][qty]=4' % ipe)
    c.click('a[href*="sm_weight_cut_rfq"]'); c.wait_for_load_state()
    secs = c.locator('select[name^="lines["][name$="[section]"]')
    vals = [secs.nth(i).input_value() for i in range(secs.count())]
    check('/mes-devis/' in c.url and ipe in vals and '4 × 5,10 m' in c.input_value('textarea[name="description"]'), 'request for quotation pre-filled: the bars to buy, the pieces in the description')
    check(not cerr, 'no JavaScript error' + (': ' + cerr[0] if cerr else ''))
    b.close()

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
