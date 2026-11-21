"""
End-to-end tests of SM SEO 0.1.0 (theme 0.57.0) and of phase 2 (sm-core 0.9.0, sm-products
0.23.0, sm-services 0.4.0, theme 0.58.0) over HTTP, as a search engine sees the site: one title, description and canonical per page in each language, pages out of the
index, structured data that parses (no rating, no review, no contact), link previews,
sitemaps, robots.txt (choice A: AI search allowed, training refused) and llms.txt;
readable addresses with 301 from the old ones, first photos loaded first, security
headers, user names not given away.
Usage: python3 tests/e2e-seo.py http://localhost:8080 /path/to/wp
"""
import json, re, subprocess, sys, urllib.request, urllib.error

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(code):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', code], capture_output=True, text=True).stdout.strip()
def get(path, ua='Mozilla/5.0 (compatible; Googlebot/2.1)'):
    req = urllib.request.Request(BASE + path, headers={'User-Agent': ua})
    try:
        with urllib.request.urlopen(req) as r:
            return r.status, r.headers.get('Content-Type', ''), r.read().decode('utf-8')
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Content-Type', ''), e.read().decode('utf-8')
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None
def nofollow(path):
    """Status, Location and headers, without following a redirect."""
    op = urllib.request.build_opener(NoRedirect)
    try:
        r = op.open(BASE + path)
        return r.status, '', r.headers
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Location', ''), e.headers
def head(html):
    return html.split('</head>')[0]
def tags(h):
    return {
        'title': re.findall(r'<title>(.*?)</title>', h, re.S),
        'desc': re.findall(r'<meta name="description" content="([^"]*)"', h),
        'canonical': re.findall(r'<link rel=["\']canonical["\'] href=["\']([^"\']+)', h),
        'robots': re.findall(r"<meta name='robots' content='([^']*)'", h),
        'og_image': re.findall(r'<meta property="og:image" content="([^"]+)"', h),
        'og_title': re.findall(r'<meta property="og:title"', h),
        'ld': [json.loads(x) for x in re.findall(r'<script type="application/ld\+json">(.*?)</script>', h, re.S)],
        'hreflang': dict(re.findall(r'hreflang="([a-z-]+)" href="([^"]+)"', h)),
    }
def types(ld):
    out = []
    for blob in ld:
        for n in blob.get('@graph', [blob]):
            out.append(n.get('@type'))
    return out
def node(ld, t):
    for blob in ld:
        for n in blob.get('@graph', [blob]):
            if n.get('@type') == t:
                return n
    return None

saved = wp('echo wp_json_encode(get_option("sm_seo_settings", null));')
wp('delete_option("sm_seo_settings");')
slug = lambda i: wp('echo SM\\Core\\Listings\\Index::slug(%s);' % i)
lid = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_listing_index WHERE module=\'products\' AND status=\'published\' ORDER BY id LIMIT 1");')
sold = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_listing_index WHERE status=\'sold\' ORDER BY id LIMIT 1");')
reuse = wp('global $wpdb; echo $wpdb->get_var("SELECT id FROM {$wpdb->prefix}sm_listing_index WHERE module=\'reuse\' AND status=\'published\' ORDER BY id LIMIT 1");')
eng = wp('global $wpdb; echo $wpdb->get_var("SELECT slug FROM {$wpdb->prefix}sm_companies WHERE account_class=\'engineer\' LIMIT 1");')
try:
    LS = slug(lid)
    LP = '/annonce/%s-%s/' % (LS, lid)
    print('A. Listing of new steel, 3 languages')
    for lang, prefix, word in (('fr', '', 'Prix demandé par le vendeur'), ('ar', '/ar', 'السعر الذي يطلبه البائع'), ('en', '/en', 'Price asked by the seller')):
        st, _, html = get(prefix + LP)
        t = tags(head(html))
        check(st == 200 and len(t['title']) == 1 and len(t['desc']) == 1 and len(t['canonical']) == 1, '%s: one title, one description, one canonical' % lang)
        check(t['canonical'] and t['canonical'][0].endswith(prefix + LP), '%s: canonical in the language (%s)' % (lang, t['canonical'][0] if t['canonical'] else '-'))
        check(word in t['desc'][0] and 'noindex' not in ''.join(t['robots']), '%s: description with the asked price, indexed' % lang)
        check(len(t['og_title']) == 1 and len(t['og_image']) <= 1, '%s: one link preview (no double og: with the theme)' % lang)
        p = node(t['ld'], 'Product')
        check(p and p['offers']['priceCurrency'] == 'DZD' and 'BreadcrumbList' in types(t['ld']), '%s: Product + Offer in DZD + breadcrumb' % lang)
        raw = json.dumps(t['ld'])
        check(not re.search(r'aggregateRating|"review"|telephone|"email"|streetAddress', raw), '%s: no rating, review or contact in the data' % lang)
        if lang == 'fr':
            check(set(['fr', 'ar', 'en', 'x-default']) <= set(t['hreflang']) and t['hreflang']['ar'].endswith('/ar' + LP), 'hreflang fr / ar / en / x-default')
    st, _, html = get('/annonce/%s/' % reuse)
    p = node(tags(head(html))['ld'], 'Product')
    offer = (p or {}).get('offers', {})
    check(p is not None and (not offer or offer.get('itemCondition', '').endswith('Condition')), 'reuse: Product, condition from the seller when stated')

    print('\nB. Out of the index')
    for path, label in (('/annonce/%s/' % sold, 'sold listing'), ('/annonce/99999999/', 'unknown listing'), ('/produits/?q=ipe', 'text search'),
                        ('/produits/?category=profiles&sort=price', 'sorted list'), ('/connexion/', 'login'), ('/demander-une-etude/', 'study request form')):
        st, _, html = get(path)
        check('noindex' in ''.join(tags(head(html))['robots']), '%s: noindex' % label)
    for path, label in (('/', 'home'), ('/produits/?category=profiles', 'type of product'), ('/produits/?category=profiles&family=IPE', 'family IPE'),
                        ('/ingenierie/', 'engineering services'), ('/calcul-poids/', 'weight calculator'), ('/produits/?utm_source=fb', 'tracking argument')):
        st, _, html = get(path)
        t = tags(head(html))
        check(st == 200 and 'noindex' not in ''.join(t['robots']) and t['canonical'], '%s: indexed, canonical %s' % (label, t['canonical'][0].replace(BASE, '') if t['canonical'] else '-'))
    st, _, html = get('/produits/?utm_source=fb')
    check(tags(head(html))['canonical'][0].endswith('/produits/'), 'canonical drops the tracking argument')

    print('\nC. Home and engineering')
    st, _, html = get('/')
    t = tags(head(html))
    check('WebSite' in types(t['ld']) and 'Organization' in types(t['ld']), 'home: WebSite (search box) + Organization')
    check('nouvelle vie' in t['title'][0], 'home title: « Nous donnons une nouvelle vie à l’acier »')
    st, _, html = get('/ingenierie/')
    t = tags(head(html))
    il = node(t['ld'], 'ItemList')
    check(il and all(x['item']['@type'] == 'Service' for x in il['itemListElement']) and len(il['itemListElement']) >= 1, 'engineering: the services, as services of the platform (%d)' % (len(il['itemListElement']) if il else 0))
    check('"Person"' not in json.dumps(t['ld']), 'engineering: no engineer named (no Person)')

    print('\nD. Sitemaps, robots.txt, llms.txt')
    st, _, idx = get('/wp-sitemap.xml')
    check(st == 200 and 'smlistings-products' in idx and 'smcompanies' in idx and 'users' not in idx, 'sitemap index: pages, listings, companies; no users')
    st, _, sm = get('/wp-sitemap-smlistings-products-1.xml')
    check(LP in sm and '/ar' + LP in sm and '/en' + LP in sm, 'listing in the 3 languages, readable address')
    check(re.search(r'/annonce/([a-z0-9-]*-)?%s/' % sold, sm) is None, 'sold listing not in the sitemap')
    st, _, pg = get('/wp-sitemap-smpages-1.xml')
    check('/compte/' not in pg and '/connexion/' not in pg and '/ingenierie/' in pg and 'category=profiles' in pg, 'pages: engineering and types of product, no account or login')
    st, _, co = get('/wp-sitemap-smcompanies-1.xml')
    check(not eng or '/%s/' % eng not in co, 'no partner engineer in the sitemap')
    st, ct, rb = get('/robots.txt')
    check('User-agent: OAI-SearchBot' in rb and re.search(r'User-agent: GPTBot.*?Disallow: /\n', rb, re.S) and 'Disallow: /compte/' in rb and 'Sitemap: ' in rb, 'robots.txt: AI search allowed, training refused, account closed, sitemap')
    st, ct, ll = get('/llms.txt')
    check(st == 200 and 'text/plain' in ct and ll.startswith('# ') and '/ingenierie/' in ll, 'llms.txt served as text, with the engineering services')

    print('\nE. Readable addresses (phase 2)')
    check(bool(re.match(r'^[a-z0-9]+(-[a-z0-9]+)+$', LS or '')), 'listing named from its profile, grade and wilaya (%s)' % LS)
    for old in ('/annonce/%s/' % lid, '/annonce/?id=%s' % lid, '/annonce/old-name-%s/' % lid):
        st, loc, _ = nofollow(old)
        check(st == 301 and loc.endswith(LP), '%s → 301 %s' % (old, loc.replace(BASE, '')))
    st, loc, _ = nofollow('/ar/annonce/%s/' % lid)
    check(st == 301 and loc.endswith('/ar' + LP), 'Arabic: 301 to the Arabic readable address')
    st, loc, _ = nofollow(LP)
    check(st == 200, 'readable address: 200, no redirect')
    st, _, html = get('/produits/')
    links = re.findall(r'href="([^"]*/annonce/[^"]*)"', html)
    good = [l for l in links if re.search(r'/annonce/[a-z0-9-]+-\d+/$', l)]
    check(links and not [l for l in links if '?id=' in l and '?id=0' not in l] and len(good) >= len(links) - 2, 'cards link to the readable address, without ?id= (%d/%d)' % (len(good), len(links)))
    imgs = re.findall(r'<article class="sm-card sm-card--listing.*?<img ([^>]*)>', html, re.S)
    check(len(imgs) >= 4 and 'fetchpriority="high"' in imgs[0] and 'loading="lazy"' not in imgs[0] and 'loading="lazy"' in imgs[3], 'first cards: photo loaded first; the next ones when they come into view')
    st, _, html = get(LP)
    check('fetchpriority="high"' in re.search(r'<img [^>]*data-sm-gallery-main[^>]*>', html).group(0), 'listing: main photo loaded first')

    print('\nF. Security')
    st, _, hd = nofollow('/')
    check(hd.get('X-Content-Type-Options') == 'nosniff' and hd.get('X-Frame-Options') == 'SAMEORIGIN' and 'strict-origin' in (hd.get('Referrer-Policy') or '') and 'camera=(self)' in (hd.get('Permissions-Policy') or ''), 'security headers on the pages')
    st, _, _ = nofollow('/wp-json/wp/v2/users')
    check(st == 404, 'REST list of the users closed to visitors (%d)' % st)
    st, _, _ = nofollow('/?author=1')
    check(st == 404, '?author=1 does not give a user name (%d)' % st)
    st, _, home = get('/')
    check('name="generator"' not in home, 'WordPress version not printed')
finally:
    if saved and saved != 'null':
        wp("update_option('sm_seo_settings', json_decode('%s', true));" % saved.replace("'", "\\'"))
ok = sum(1 for r in results if r[0])
print('\n%d/%d passed' % (ok, len(results)))
sys.exit(0 if ok == len(results) else 1)
