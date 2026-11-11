"""
End-to-end tests of Phase 9.2 (theme 0.15.0, sm-core 0.7.0, sm-services 0.3.0): featured companies and
professionals on the home page, the two directories, the "Types and categories" administration
(add, describe, hide, delete with a replacement) and the "featured" setting of a company.
Usage: python3 tests/e2e-directory.py URL WP_PATH OUT
Admin login: admin / Admin-2026-test! (env SM_ADMIN="user:password").
"""
import os, sys, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-directory-output'
os.makedirs(OUT, exist_ok=True)
ADMIN = os.environ.get('SM_ADMIN', 'admin:Admin-2026-test!').split(':', 1)
TAG = 'D%d' % (int(time.time()) % 100000)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    import subprocess
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()

# A verified company with a logo, used as the featured one.
cid = wp('list($c)=SM\\Core\\Accounts\\Companies::query(array("class"=>"company","min_level"=>2,"has_logo"=>true,"per_page"=>1))["items"]; echo $c ? $c->id : "";')
if not cid:
    cid = wp('global $wpdb; list($c)=SM\\Core\\Accounts\\Companies::query(array("class"=>"company","min_level"=>2,"per_page"=>1))["items"]; $a=(int)$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type=\\"attachment\\" AND post_mime_type LIKE \\"image/%\\" LIMIT 1"); $wpdb->update($wpdb->prefix."sm_companies",array("logo_id"=>$a),array("id"=>$c->id)); echo $c->id;')
cname = wp('echo SM\\Core\\Accounts\\Companies::get(%s)->name;' % cid)
featured_before = wp('$c=SM\\Core\\Accounts\\Companies::get(%s); echo wp_json_encode(array($c->featured_until,$c->featured_label,$c->featured_priority));' % cid)

with sync_playwright() as p:
    b = p.chromium.launch()
    def page(w=1280, h=900):
        return b.new_context(viewport={'width': w, 'height': h}).new_page()
    errors = []

    print('\nA. Visitor: home page and directories')
    g = page()
    g.on('pageerror', lambda e: errors.append(str(e)))
    g.goto(BASE + '/')
    check(g.locator('.sm-cta').count() == 1, 'visitor sees the "create an account" band')
    check(g.locator('.smf-section--company').count() == 1 and g.locator('.smf-section--company a[href*="/entreprises/"]').count() == 1, 'companies section with "See all companies"')
    g.goto(BASE + '/entreprises/')
    check('Annuaire des entreprises' in g.inner_text('h1') and g.locator('.smd-card').count() >= 1, 'company directory lists companies')
    check(g.locator('.smd-card .smc-badge--quality:has-text("Entreprise vérifiée")').count() >= 1, 'badge "Entreprise vérifiée" on verified companies')
    first_url = g.get_attribute('.smd-card .smd-card__name a', 'href') or ''
    g.goto(BASE + '/professionnels/')
    check('Annuaire des professionnels' in g.inner_text('h1') and g.locator('.smd-card__logo--round').count() == g.locator('.smd-card').count(), 'professionals directory, round photos')
    g.goto(BASE + '/entreprises/?class=professional')
    check('/professionnels/' in g.url, 'old "?class=professional" link opens the professionals directory')
    g.goto(BASE + '/entreprises/?verified=1&sort=name')
    names = g.locator('.smd-card__name').all_inner_texts()
    check(names == sorted(names, key=str.casefold) or len(names) < 2, 'sort by name A → Z')
    a390 = page(390, 844)
    a390.goto(BASE + '/ar/professionnels/')
    check(a390.evaluate('document.documentElement.dir') == 'rtl' and 'دليل المهنيين' in a390.inner_text('h1'), 'Arabic professionals directory, right-to-left')
    a390.screenshot(path=os.path.join(OUT, 'professionals-ar-390.png'), full_page=True)

    print('\nB. Administrator: types and categories')
    a = page()
    a.goto(BASE + '/wp-login.php'); a.fill('#user_login', ADMIN[0]); a.fill('#user_pass', ADMIN[1]); a.click('#wp-submit'); a.wait_for_load_state()
    a.goto(BASE + '/wp-admin/admin.php?page=sm-types')
    tabs = a.locator('.nav-tab').all_inner_texts()
    check(len(tabs) >= 3 and any('Services' in t for t in tabs), 'three lists: company types, trades, services (%s)' % ', '.join(tabs))
    a.fill('#sm-term-fr', 'Galvanisation ' + TAG); a.fill('#sm-term-ar', 'غلفنة ' + TAG)
    a.fill('#sm-term-desc_fr', 'Protection anticorrosion à chaud ' + TAG)
    a.set_input_files('.sm-term-form input[name="image"]', os.path.join(os.path.dirname(__file__), 'fixtures', 'photo.jpg'))
    a.click('.sm-term-form #submit'); a.wait_for_load_state()
    row = a.locator('tr:has-text("Galvanisation %s")' % TAG)
    check(row.count() == 1 and row.locator('img').count() == 1, 'company type added with its image')
    code = row.get_attribute('data-code')
    a.screenshot(path=os.path.join(OUT, 'admin-types-1280.png'), full_page=True)
    g.goto(BASE + '/entreprises/?activity=' + code)
    check(('Protection anticorrosion à chaud ' + TAG) in g.inner_text('.smd-type') and g.locator('.smd-type img').count() == 1, 'directory filtered by the type shows its description and image')
    g.goto(BASE + '/inscription/')
    check(g.locator('input[name="activities[]"][value="%s"]' % code).count() == 1, 'new type offered at registration')
    a.click('tr[data-code="%s"] a:has-text("Hide")' % code); a.wait_for_load_state()
    g.goto(BASE + '/inscription/')
    check(g.locator('input[name="activities[]"][value="%s"]' % code).count() == 0, 'hidden type no longer offered')
    # Give it to the featured company, then delete it with a replacement.
    wp('global $wpdb; $wpdb->insert($wpdb->prefix."sm_company_activities", array("company_id"=>%s, "activity_code"=>"%s", "enabled_at"=>current_time("mysql", true)));' % (cid, code))
    a.click('tr[data-code="%s"] .sm-term-delete' % code); a.wait_for_load_state()
    check(a.locator('select[name="replacement"]').count() == 1, 'deleting a used type asks for a replacement')
    a.click('input#submit'); a.wait_for_load_state()
    check(a.locator('select[name="replacement"]').count() == 1, 'cannot delete without choosing it')
    a.select_option('select[name="replacement"]', 'supplier')
    a.click('input#submit'); a.wait_for_load_state()
    acts = wp('echo implode(",", SM\\Core\\Accounts\\Companies::get(%s)->activities);' % cid)
    check(a.locator('tr[data-code="%s"]' % code).count() == 0 and code not in acts and 'supplier' in acts, 'type deleted, the company now has the replacement')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-types&tab=services_fabrication')
    check(a.locator('tr[data-code="welding"]').count() == 1, 'services list editable in the same screen')

    print('\nC. Administrator: featured company')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-companies&company=' + cid)
    a.check('input[name="featured"]'); a.select_option('select[name="featured_label"]', 'sponsored')
    a.fill('input[name="featured_until"]', '2099-12-31'); a.fill('input[name="featured_priority"]', '0')
    a.click('form:has(input[name="featured"]) #submit'); a.wait_for_load_state()
    check(wp('echo substr(SM\\Core\\Accounts\\Companies::get(%s)->featured_until,0,10);' % cid) == '2099-12-31', 'featured until the chosen date')
    g.goto(BASE + '/')
    card = g.locator('.smf-section--company .smf-card').first
    check(cname in card.inner_text() and 'Sponsorisé' in card.inner_text(), 'home: the featured company comes first with "Sponsorisé"')
    check(g.locator('.smf-section--company .smf-eyebrow:has-text("À la une")').count() == 1, 'section titled "À la une"')
    g.locator('.smf-section--company').screenshot(path=os.path.join(OUT, 'home-featured-1280.png'))
    g.goto(BASE + '/entreprises/')
    check(cname in g.inner_text('.smd-card >> nth=0') and g.locator('.smd-card >> nth=0').locator('.smd-flag').count() == 1, 'directory: featured company first, flagged')
    g.screenshot(path=os.path.join(OUT, 'companies-1280.png'), full_page=True)

    print('\nD. Signed-in user')
    a.goto(BASE + '/')
    check(a.locator('.sm-cta').count() == 0, 'signed-in user does not see the "create an account" band')
    check(not errors, 'no JavaScript errors')
    b.close()

# Restore the featured setting of the company.
wp('$v=json_decode(\'%s\'); SM\\Core\\Accounts\\Companies::set_admin_fields(%s, array("featured_until"=>$v[0]?substr($v[0],0,10):"","featured_label"=>$v[1]?:"featured","featured_priority"=>(int)$v[2]));' % (featured_before, cid))

ok = sum(1 for r in results if r[0])
print('\n%d/%d checks passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
