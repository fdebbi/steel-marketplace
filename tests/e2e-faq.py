"""
End-to-end tests of the frequently asked questions (sm-guides 0.2.0, sm-seo 0.5.0,
theme 0.62.0): page /faq/ (themes, questions opened with a click or the keyboard,
links to the guides and pages, contact box), 3 languages, phone, search engines
(FAQPage, canonical, sitemap), blocks at the bottom of the reuse, buildings,
engineering and requests for quotation pages, link in the footer.
Usage: python3 tests/e2e-faq.py http://localhost:8080 /path/to/wp out_dir
"""
import json, os, re, sys, urllib.request
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
OUT = sys.argv[3] if len(sys.argv) > 3 else 'e2e-faq-output'
os.makedirs(OUT, exist_ok=True)
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def get(path):
    try:
        r = urllib.request.urlopen(BASE + path)
        return r.status, r.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()
def ld(html, kind):
    out = []
    for b in re.findall(r'<script type="application/ld\+json"[^>]*>(.*?)</script>', html, re.S):
        d = json.loads(b)
        out += [x for x in (d.get('@graph') or [d]) if x.get('@type') == kind]
    return out

errors = []
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1280, 'height': 900})
    pg.on('pageerror', lambda e: errors.append(str(e)))

    print('A. Page /faq/ in French')
    pg.goto(BASE + '/faq/?lang=fr')
    check(pg.locator('h1').inner_text() == 'Questions fréquentes', 'title: %s' % pg.locator('h1').inner_text())
    themes = pg.locator('.sm-faq__themes a')
    check(themes.count() == 6 and themes.nth(0).inner_text() == 'Compte', '6 themes, Compte first')
    qs = pg.locator('.sm-faq__q')
    check(qs.count() == 23, '23 questions (%d)' % qs.count())
    check(pg.locator('.sm-faq__q[open]').count() == 0, 'all closed at first')
    check(not re.search(r'réglage|\[page|relecture', pg.locator('.sm-faq__main').inner_text()), 'no review note, no marker')
    check('Aucun prix du marché' in pg.locator('.sm-faq__note').inner_text(), 'note: no market price')
    themes.nth(3).click()
    pg.wait_for_timeout(400)
    check(pg.url.endswith('#faq-engineering') and pg.locator('#faq-engineering').is_visible(), 'theme link goes to its part')
    q = pg.locator('#q-declarer-etat-reemploi')
    q.locator('summary').click()
    pg.wait_for_timeout(300)
    a = q.locator('.sm-faq__a')
    check(q.get_attribute('open') is not None and 'Ferraille' in a.inner_text(), 'a click opens the answer')
    link = a.locator('a')
    check(link.count() == 1 and link.get_attribute('href').endswith('/guides/acheter-acier-reemploi/'), 'link to the guide')
    q.locator('summary').click()
    pg.wait_for_timeout(200)
    check(q.get_attribute('open') is None, 'a second click closes it')
    s = pg.locator('#q-prix-da-kg summary')
    s.focus(); pg.keyboard.press('Enter'); pg.wait_for_timeout(200)
    check(pg.locator('#q-prix-da-kg').get_attribute('open') is not None, 'opened with the keyboard (Enter)')
    st = pg.locator('#q-annonce-pas-encore-visible')
    st.locator('summary').click(); pg.wait_for_timeout(200)
    check(st.locator('li').count() == 4, 'statuses of a listing: list of 4')
    c = pg.locator('#q-contacter-equipe'); c.locator('summary').click(); pg.wait_for_timeout(200)
    check(c.locator('a').get_attribute('href').endswith('/contact/'), 'link to the contact page')
    box = pg.locator('.sm-faq__aside')
    check('Contacter' in box.locator('.sm-btn').inner_text() and box.locator('.sm-btn').get_attribute('href').endswith('/contact/'), 'box: contact the team')
    check(box.locator('.sm-guide__tools a').count() == 4, 'box: the 4 guides')
    check('العربية' in pg.locator('.sm-guide__meta').inner_text() and 'English' in pg.locator('.sm-guide__meta').inner_text(), 'the 2 other languages')
    pg.goto(BASE + '/faq/?lang=fr#q-qui-voit-offres')
    check(pg.locator('#q-qui-voit-offres').is_visible(), 'address with #question goes to it')
    pg.screenshot(path=OUT + '/faq-fr.png', full_page=True)

    print('B. Arabic and English')
    pg.goto(BASE + '/ar/faq/')
    check(pg.locator('html').get_attribute('dir') == 'rtl' and pg.locator('h1').inner_text() == 'الأسئلة الشائعة', 'Arabic: rtl, title')
    check(pg.locator('.sm-faq__themes a').nth(0).inner_text() == 'الحساب', 'Arabic: themes in Arabic')
    s = pg.locator('#q-prix-da-kg summary')
    check(s.inner_text().strip() == 'لماذا الأسعار بـ DA/kg؟' and s.locator('bdi').count() == 1, 'Arabic: DA/kg isolated (<bdi>)')
    q = pg.locator('#q-declarer-etat-reemploi'); q.locator('summary').click(); pg.wait_for_timeout(300)
    check('/ar/guides/acheter-acier-reemploi/' in q.locator('a').get_attribute('href'), 'Arabic: link to the Arabic guide')
    pg.screenshot(path=OUT + '/faq-ar.png', full_page=True)
    pg.goto(BASE + '/en/faq/')
    check(pg.locator('h1').inner_text() == 'Frequently asked questions' and pg.locator('.sm-faq__q').count() == 23, 'English: title, 23 questions')
    check(pg.locator('#q-qui-peut-sinscrire summary').inner_text().strip() == 'Who can sign up on ReuSteel?', 'English: question in English')

    print('C. Phone')
    ph = b.new_page(viewport={'width': 390, 'height': 844})
    ph.on('pageerror', lambda e: errors.append(str(e)))
    for path in ('/faq/?lang=fr', '/ar/faq/'):
        ph.goto(BASE + path)
        w = ph.evaluate('document.documentElement.scrollWidth')
        check(w <= 390, '%s: no horizontal scroll (%d)' % (path, w))
    asid = ph.locator('.sm-faq__aside').bounding_box(); main = ph.locator('.sm-faq__main').bounding_box()
    check(asid['y'] > main['y'], 'phone: the boxes go under the questions')
    ph.screenshot(path=OUT + '/faq-ar-phone.png', full_page=True)

    print('D. Blocks at the bottom of the pages')
    pages = {
        '/reemploi/?lang=fr': ('réemploi', ['q-qui-peut-publier', 'q-declarer-etat-reemploi', 'q-verifier-lot']),
        '/batiments/?lang=fr': ('bâtiments', ['q-qui-peut-publier', 'q-verifier-lot', 'q-demander-etude-inspection']),
        '/ingenierie/?lang=fr': ("l'ingénierie", ['q-demander-etude-inspection', 'q-ingenieur-sans-coordonnees', 'q-ingenieur-verifie']),
        '/demandes-de-devis/?lang=fr': ('demandes de devis', ['q-demander-devis', 'q-qui-voit-offres', 'q-prix-da-kg']),
    }
    for path, (word, ids) in pages.items():
        pg.goto(BASE + path)
        blk = pg.locator('.sm-faqblock')
        ok = blk.count() == 1 and word in blk.locator('h2').inner_text()
        got = [blk.locator('.sm-faq__q').nth(i).get_attribute('id') for i in range(blk.locator('.sm-faq__q').count())] if blk.count() else []
        check(ok and got == ids, '%s: block « %s », %s' % (path, blk.locator('h2').inner_text() if blk.count() else '-', ', '.join(got)))
        check(blk.count() == 1 and blk.locator('.sm-faqblock__head a').get_attribute('href').endswith('/faq/'), '%s: link « Toutes les questions »' % path)
    pg.goto(BASE + '/reemploi/?lang=fr')
    pg.locator('.sm-faqblock .sm-faq__q summary').nth(0).click(); pg.wait_for_timeout(400)
    check('Tout compte ReuSteel' in pg.locator('.sm-faqblock .sm-faq__a').nth(0).inner_text(), 'block: a click opens the answer')
    pg.locator('.sm-faqblock').screenshot(path=OUT + '/block-reuse.png')
    pg.goto(BASE + '/calcul-poids/?lang=fr')
    check(pg.locator('.sm-faqblock').count() == 0, 'no block on a page without questions')
    foot = pg.locator('.sm-footer a', has_text='Questions fréquentes')
    check(foot.count() == 1 and foot.get_attribute('href').endswith('/faq/'), 'footer: link « Questions fréquentes »')
    b.close()

print('E. Search engines')
st, h = get('/faq/?lang=fr')
fq = ld(h, 'FAQPage')
check(st == 200 and fq and len(fq[0]['mainEntity']) == 23, 'FAQPage with the 23 questions')
check('<link rel="canonical" href="%s/faq/"' % BASE in h and 'hreflang="ar" href="%s/ar/faq/"' % BASE in h, 'canonical and hreflang')
check(re.search(r'<title>Questions fréquentes', h) is not None, 'title of the page')
st, h = get('/ar/faq/')
fq = ld(h, 'FAQPage')
check(fq and fq[0]['inLanguage'] == 'ar' and '<bdi>' not in json.dumps(fq, ensure_ascii=False), 'Arabic FAQPage in Arabic, without HTML')
st, x = get('/wp-sitemap-smpages-1.xml')
check(x.count('/faq/</loc>') == 3, 'sitemap: /faq/ in the 3 languages')
st, t = get('/llms.txt')
check('/faq/' in t, 'llms.txt: the questions')
check(not errors, 'no script error %s' % errors[:2])

n = sum(1 for ok, _ in results if ok)
print('\n%d/%d passed' % (n, len(results)))
sys.exit(0 if n == len(results) else 1)
