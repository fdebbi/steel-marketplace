"""
End-to-end browser tests of the page-cache protections (theme 0.30.1).
Usage: python3 tests/e2e-cache.py URL WP_PATH OUT
A page cache is simulated: the page made for a visitor is handed to a signed-in member
(and the reverse). Uses the client account created by tests/e2e.py (e2e-com…).
"""
import os, sys, subprocess, urllib.request
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-cache-output'
os.makedirs(OUT, exist_ok=True)
PWD = 'Acier-2026-test!'
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def get(url):
    r = urllib.request.urlopen(url); return r.read().decode('utf-8'), r.headers

client = wp('echo get_users(array("search"=>"e2e-com*","search_columns"=>array("user_email"),"orderby"=>"ID","order"=>"DESC"))[0]->user_email;')
wp('update_user_meta(get_user_by("email","%s")->ID, "sm_locale", "fr");' % client)

print('\nA. What the server sends')
guest_home, headers = get(BASE + '/')
check('data-sm-user="0"' in guest_home, 'visitor page: marked as made for a visitor')
check('wordpress_logged_in_' in (headers.get('X-LiteSpeed-Vary') or ''), 'cacheable page: LiteSpeed asked to keep a separate version for signed-in members')
check(not headers.get('Set-Cookie'), 'cacheable page: no cookie written')
guest_ar, _ = get(BASE + '/ar/')

with sync_playwright() as p:
    b = p.chromium.launch()
    errors = []
    def ctx(w=390, h=800):
        c = b.new_context(viewport={'width': w, 'height': h}, reduced_motion='reduce'); pg = c.new_page()
        pg.on('pageerror', lambda e: errors.append(str(e)))
        return c, pg
    def cookies(c):
        return {k['name']: k for k in c.cookies()}
    def cached(pg, path, html):
        pg.route(BASE + path, lambda r: r.fulfill(status=200, content_type='text/html; charset=UTF-8', body=html))
    def drawer(pg):
        pg.click('[data-sm-drawer-open]'); pg.wait_for_selector('.sm-drawer__foot', state='visible')
        return pg.inner_text('.sm-drawer__foot')

    print('\nB. Signed-in member, visitor page handed by a cache (phone)')
    c, pg = ctx()
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', client); pg.fill('#l-pass', PWD)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()
    ck = cookies(c)
    check('sm_in' in ck and ck['sm_in']['value'] == '1' and not ck['sm_in']['httpOnly'], 'sign-in: hint cookie written, readable by the page')
    cached(pg, '/', guest_home)
    with pg.expect_response(lambda r: 'admin-ajax.php' in r.url) as resp:
        pg.goto(BASE + '/')
    check(resp.value.ok, 'the page notices the mismatch and asks for the right menu')
    pg.wait_for_function('document.body.getAttribute("data-sm-user") === "1"')
    foot = drawer(pg)
    check('Mon compte' in foot and 'Se déconnecter' in foot, 'mobile menu: "My account" and "Sign out"')
    check('Se connecter' not in foot and 'Créer un compte' not in foot, 'mobile menu: no "Sign in" / "Create an account"')
    pg.screenshot(path=os.path.join(OUT, 'drawer-corrected-fr-390.png'))
    c.close()

    print('\nC. Same on a desktop, in Arabic: account and bell')
    c, pg = ctx(1280, 900)
    pg.goto(BASE + '/connexion/?lang=fr'); pg.fill('#l-user', client); pg.fill('#l-pass', PWD)
    pg.click('form:has(#l-user) button[type="submit"]'); pg.wait_for_load_state()
    cached(pg, '/ar/', guest_ar)
    pg.goto(BASE + '/ar/')
    pg.wait_for_function('document.body.getAttribute("data-sm-user") === "1"')
    head = pg.inner_text('.sm-header__actions')
    check('حسابي' in head and 'تسجيل الدخول' not in head, 'header in Arabic: "My account" instead of "Sign in"')
    check('/ar/' in (pg.get_attribute('.sm-header__account', 'href') or ''), 'links of the corrected menu stay in Arabic')
    pg.click('[data-sm-notif-toggle]')
    check(pg.is_visible('[data-sm-notif-panel]'), 'the bell of the corrected header opens its panel')
    pg.screenshot(path=os.path.join(OUT, 'header-corrected-ar-1280.png'))

    print('\nD. After sign-out, a member page handed by a cache')
    pg.unroute(BASE + '/ar/')
    pg.goto(BASE + '/'); member_home = pg.content()
    check('data-sm-user="1"' in member_home, 'member page: marked as made for a member')
    pg.goto(BASE + '/compte/'); pg.goto(pg.get_attribute('a[href*="action=logout"] >> nth=0', 'href')); pg.wait_for_load_state()
    check('sm_in' not in cookies(c), 'sign-out: hint cookie removed')
    cached(pg, '/', member_home)
    pg.goto(BASE + '/')
    pg.wait_for_function('document.body.getAttribute("data-sm-user") === "0"')
    head = pg.inner_text('.sm-header__actions')
    check('Se connecter' in head and 'Mon compte' not in head, 'header goes back to "Sign in"')
    c.close()

    print('\nE. Late cookie on a right page: nothing changes')
    c, pg = ctx(1280, 900)
    c.add_cookies([{'name': 'sm_in', 'value': '1', 'url': BASE}])
    with pg.expect_response(lambda r: 'admin-ajax.php' in r.url):
        pg.goto(BASE + '/')
    pg.wait_for_timeout(300)
    check(pg.get_attribute('body', 'data-sm-user') == '0' and 'Se connecter' in pg.inner_text('.sm-header__actions'), 'visitor with an old hint cookie keeps the visitor menu')
    check('sm_in' not in cookies(c), 'the old hint cookie is removed')
    reqs = []
    pg.on('request', lambda r: reqs.append(r.url) if 'admin-ajax.php' in r.url else None)
    pg.goto(BASE + '/'); pg.wait_for_timeout(300)
    check(not reqs, 'normal case (page and browser agree): no extra request')
    c.close()
    check(not errors, 'no JavaScript error' + (': ' + '; '.join(errors[:3]) if errors else ''))
    b.close()

ok = sum(1 for r in results if r[0])
print('\n%d/%d passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
