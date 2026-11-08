"""
End-to-end tests of e-mail alerts, editable templates, contact form and activity journal (sm-core 0.4.0), Phase 8.1.
Usage: python3 tests/e2e-mail.py URL WP_PATH OUT
Needs the test mail catcher (tests/fixtures/mu-sm-test-mail.php in wp-content/mu-plugins): e-mails are
written to wp-content/sm-mails.jsonl instead of being sent. Admin login: admin / Admin-2026-test!.
"""
import os, sys, json, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '.'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-mail-output'
os.makedirs(OUT, exist_ok=True)
MAILS = os.path.join(WP, 'wp-content', 'sm-mails.jsonl')
TAG = 'M%d' % (int(time.time()) % 100000)
PASS = 'Acier-2026-test!'
EMAIL = 'e2em-%s@example.test' % TAG.lower()
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    import subprocess
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def mails():
    out = []
    if os.path.exists(MAILS):
        for line in open(MAILS, encoding='utf-8'):
            try: out.append(json.loads(line))
            except Exception: pass
    open(MAILS, 'w').close()
    return out
def hdr(m):
    return '\n'.join(m.get('headers') or []) if isinstance(m.get('headers'), list) else str(m.get('headers'))

before_settings = wp('echo wp_json_encode(get_option("sm_mail_settings", null));')
wp('delete_option("sm_mail_settings"); delete_option("sm_mail_templates");')
wp('$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; SM\\Core\\Support\\Forms::reset_rate_limit("contact"); SM\\Core\\Support\\Forms::reset_rate_limit("register");')

with sync_playwright() as p:
    b = p.chromium.launch()
    errors = []
    def page(w=1280, h=900):
        pg = b.new_context(viewport={'width': w, 'height': h}, reduced_motion='reduce').new_page()
        pg.on('pageerror', lambda e: errors.append(str(e))); pg.on('dialog', lambda d: d.accept())
        return pg

    print('\nA. Administrator sets the e-mails')
    a = page()
    a.goto(BASE + '/wp-login.php'); a.fill('#user_login', 'admin'); a.fill('#user_pass', 'Admin-2026-test!'); a.click('#wp-submit'); a.wait_for_load_state()
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail&tab=settings')
    check(a.input_value('#smm-admins') == 'contact@reusteel.com' and a.input_value('#smm-from-email') == 'contact@reusteel.com', 'defaults: alerts and sender contact@reusteel.com')
    a.fill('#smm-admins', 'contact@reusteel.com, direction@example.test')
    a.click('form:has(input[value="sm_mail_settings"]) #submit'); a.wait_for_load_state()
    check(a.input_value('#smm-admins') == 'contact@reusteel.com, direction@example.test', 'two administrators saved')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail')
    rows = a.locator('table.widefat tbody tr:not(.smm-group)').count()
    check(rows >= 14 and a.locator('select[name="events[account.registered][admin]"]').input_value() == 'now' and a.locator('select[name="events[enquiry.sent][admin]"]').input_value() == 'digest', '%d events; registration immediate, enquiries in the summary' % rows)
    check(a.locator('select[name="events[product.new][admin]"]').count() == 1 and a.locator('select[name="events[service.request][admin]"]').count() == 1 and a.locator('select[name="events[rfq.new][admin]"]').count() == 1, 'events of listings, requests for quotation and services listed')
    a.screenshot(path=os.path.join(OUT, 'admin-mail-events.png'), full_page=True)
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail&edit=event:account.registered:user')
    check('Bienvenue' in a.input_value('input[name="tpl[fr][subject]"]') and 'مرحبًا' in a.input_value('input[name="tpl[ar][subject]"]'), 'welcome template in French and Arabic')
    a.fill('input[name="tpl[fr][subject]"]', 'Bienvenue {nom} chez {site} !')
    a.click('#do_save'); a.wait_for_load_state()
    prev = a.frame_locator('iframe[title="fr"]').locator('body').inner_text()
    check('Karim Benali' in prev and 'Merci pour votre inscription' in prev and 'Bienvenue Karim Benali chez ReuSteel' in a.inner_text('.wrap'), 'template saved, preview filled with example values')
    a.screenshot(path=os.path.join(OUT, 'admin-mail-editor.png'), full_page=True)
    mails()
    a.click('#do_test'); a.wait_for_load_state()
    m = mails()
    check(len(m) == 2 and all(x['subject'].startswith('[TEST]') for x in m) and 'admin@' in m[0]['to'], 'test e-mail sent to me in both languages')

    print('\nB. Registration: administrator alert and welcome e-mail')
    mails()
    c = page(390, 844)
    c.goto(BASE + '/inscription/?lang=fr')
    c.fill('#g-company', 'Atelier Mail ' + TAG); c.fill('#g-name', 'Yacine Test'); c.select_option('#g-wilaya', '31')
    d = TAG[1:].zfill(5) + '7'
    c.fill('#g-phone', '0662 %s %s %s' % (d[0:2], d[2:4], d[4:6]))
    c.fill('#g-email', EMAIL); c.fill('#g-pass', PASS)
    c.check('input[name="activities[]"][value="supplier"]'); c.check('input[name="terms"]')
    c.click('form[data-sm-register] button[type="submit"]'); c.wait_for_load_state()
    m = mails()
    admin = [x for x in m if x['to'] == 'contact@reusteel.com' and TAG in x['subject']]
    welcome = [x for x in m if x['to'] == EMAIL and 'Bienvenue Yacine Test chez ReuSteel' in x['subject']]
    check('/compte/' in c.url and len([x for x in m if TAG in x['subject']]) == 2, 'registration done; both administrators alerted (%d e-mails)' % len(m))
    check(admin and all(s in admin[0]['message'] for s in ('Yacine Test', EMAIL, 'Oran', 'Compte entreprise')) and 'Reply-To: ' + EMAIL in hdr(admin[0]), 'alert: name, e-mail, phone, wilaya, account type; reply goes to the new member')
    check(welcome and 'Atelier Mail ' + TAG in welcome[0]['message'] and 'From: "ReuSteel" <contact@reusteel.com>' in hdr(welcome[0]), 'welcome e-mail with the edited subject, sent in the name of the platform')
    c.context.close()

    print('\nC. Contact form (Arabic)')
    mails()
    v = page(390, 844)
    v.goto(BASE + '/ar/contact/')
    check(v.evaluate('document.documentElement.dir') == 'rtl' and 'اتصل بنا' in v.inner_text('h1'), 'Arabic contact page')
    v.click('.smc-form button[type="submit"]'); v.wait_for_load_state()
    check(v.locator('#err-name').count() == 1 and v.locator('#err-email').count() == 1 and v.locator('#err-message').count() == 1, 'empty form: errors explained')
    v.fill('#smc-name', 'Mourad ' + TAG); v.fill('#smc-email', 'mourad-%s@example.test' % TAG.lower()); v.fill('#smc-phone', '0770 11 22 33')
    v.fill('#smc-subject', 'شراكة'); v.fill('#smc-message', 'مرحبًا، نريد التعاون معكم في توريد الحديد.')
    v.click('.smc-form button[type="submit"]'); v.wait_for_load_state()
    check(v.locator('.smc-done').count() == 1, 'message sent, confirmation shown')
    v.screenshot(path=os.path.join(OUT, 'contact-ar-390.png'), full_page=True)
    m = mails()
    al = [x for x in m if x['to'] == 'contact@reusteel.com']
    ack = [x for x in m if x['to'].startswith('mourad-')]
    check(al and 'Mourad ' + TAG in al[0]['message'] and 'Reply-To: mourad-' in hdr(al[0]) and 'Contact' in al[0]['subject'], 'administrators alerted in French, reply goes to the visitor')
    check(ack and 'استلمنا رسالتك' in ack[0]['subject'] and 'dir="rtl"' in ack[0]['message'], 'visitor acknowledged in Arabic')

    print('\nD. Activity journal and e-mail log')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-audit&group=messages')
    j = a.inner_text('.sm-journal')
    check('Mourad ' + TAG in j and ('formulaire de contact' in j or 'contact form' in j), 'journal: contact message, readable sentence')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-audit&group=accounts&q=' + TAG)
    check(('Yacine Test' in a.inner_text('.sm-journal')) and a.locator('.sm-journal tbody tr').count() >= 1, 'journal: registration found by search')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-audit')
    check('auth.login' not in a.inner_text('.sm-journal'), 'sign-ins kept out of the main list')
    a.screenshot(path=os.path.join(OUT, 'admin-journal.png'), full_page=True)
    with a.expect_download() as dl:
        a.click('a:has-text("CSV")')
    path = dl.value.path()
    csv = open(path, encoding='utf-8-sig').read()
    check('Mourad ' + TAG in csv and 'contact.sent' in csv, 'journal exported to CSV')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail&tab=log&q=' + EMAIL)
    check(a.locator('table.widefat tbody tr').count() >= 1 and a.locator('table.widefat .smm-pill.ok').count() >= 1, 'e-mail log lists the welcome e-mail as sent')
    a.locator('table.widefat tr:has-text("account.registered:user") a[href*="view="]').first.click(); a.wait_for_load_state()
    check('Atelier Mail ' + TAG in a.frame_locator('iframe.smm-preview').locator('body').inner_text(), 'logged e-mail can be read')
    mails()
    a.click('form:has(input[value="sm_mail_resend"]) input[type="submit"]'); a.wait_for_load_state()
    check(any(x['to'] == EMAIL for x in mails()), 'e-mail sent again from the log')

    print('\nE. Switch off, restore')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail')
    a.select_option('select[name="events[contact.sent][admin]"]', 'off')
    a.uncheck('input[type="checkbox"][name="events[contact.sent][user]"]')
    a.click('form:has(input[value="sm_mail_events"]) #submit'); a.wait_for_load_state()
    wp('$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; SM\\Core\\Support\\Forms::reset_rate_limit("contact");')
    mails()
    v.goto(BASE + '/contact/?lang=fr')
    v.fill('#smc-name', 'Silence ' + TAG); v.fill('#smc-email', 'silence-%s@example.test' % TAG.lower()); v.fill('#smc-subject', 'Test'); v.fill('#smc-message', 'Ce message ne doit pas partir.')
    v.click('.smc-form button[type="submit"]'); v.wait_for_load_state()
    check(v.locator('.smc-done').count() == 1 and not mails(), 'alert turned off: message kept, no e-mail')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail&edit=event:account.registered:user')
    a.click('#do_reset'); a.wait_for_load_state()
    check('Bienvenue sur' in a.input_value('input[name="tpl[fr][subject]"]'), 'default welcome text restored')
    print('\nF. Header, signature and social links')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail&tab=layout')
    a.click('[data-smm-pick] >> nth=0'); a.wait_for_timeout(800)
    check(a.locator('.media-modal:visible').count() == 1, 'header image chosen from the media library')
    a.keyboard.press('Escape')
    a.fill('#smm-b-phone', '+213 550 00 %s' % TAG[-2:]); a.fill('textarea[name="brand[closing][fr]"]', 'Cordialement ' + TAG); a.fill('textarea[name="brand[closing][ar]"]', 'مع التحية ' + TAG)
    a.fill('#smm-s-linkedin', 'https://linkedin.com/company/reusteel'); a.fill('#smm-s-whatsapp', '0550 12 34 56')
    a.click('form:has(input[value="sm_mail_brand"]) #submit'); a.wait_for_load_state()
    fr = a.frame_locator('iframe[title="fr"]').locator('body')
    check('Cordialement ' + TAG in fr.inner_text() and fr.locator('a[href="https://wa.me/213550123456"]').count() == 1 and 'LinkedIn' in fr.inner_text(), 'signature and social links in the preview')
    check('مع التحية ' + TAG in a.frame_locator('iframe[title="ar"]').locator('body').inner_text(), 'Arabic signature')
    wp('$_SERVER["REMOTE_ADDR"] = "127.0.0.1"; SM\\Core\\Support\\Forms::reset_rate_limit("contact");')
    a.goto(BASE + '/wp-admin/admin.php?page=sm-mail&tab=settings')
    mails()
    a.click('form:has(input[value="sm_mail_test"]) input[type="submit"]'); a.wait_for_load_state()
    m = mails()
    check(m and all('Cordialement ' + TAG in x['message'] and 'wa.me/213550123456' in x['message'] for x in m), 'every e-mail sent carries the signature and the links')
    check(not errors, 'no JavaScript errors' + (': ' + errors[0] if errors else ''))
    b.close()

# Clean up.
wp('foreach (get_users(array("search"=>"e2em-*","search_columns"=>array("user_email"))) as $u) { $c = SM\\Core\\Accounts\\Companies::for_user($u->ID); global $wpdb; if ($c) { foreach (array("company_members","company_activities") as $t) $wpdb->delete($wpdb->prefix."sm_".$t, array("company_id"=>$c->id)); $wpdb->delete($wpdb->prefix."sm_companies", array("id"=>$c->id)); } require_once ABSPATH."wp-admin/includes/user.php"; wp_delete_user($u->ID); }')
wp('delete_option("sm_mail_templates");' + ('delete_option("sm_mail_settings");' if before_settings in ('', 'null') else 'update_option("sm_mail_settings", json_decode(%s, true), false);' % json.dumps(before_settings)))
ok = sum(1 for r in results if r[0])
print(f'\n{ok}/{len(results)} checks passed')
sys.exit(0 if ok == len(results) else 1)
