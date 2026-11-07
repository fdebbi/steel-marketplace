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
    """'2 683,2 kg' -> 2683.2"""
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
    b.close()

ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
