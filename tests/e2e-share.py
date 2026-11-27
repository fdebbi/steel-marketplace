"""
End-to-end tests of the share image per language (sm-seo 0.6.0) and of the footer
links (theme 0.64.0): image of the language of the page, own photo kept on a
listing, the three fields of SM → SEO; a footer link only when its page is published,
a column without links not shown.
Usage: python3 tests/e2e-share.py http://localhost:8080 /path/to/wp out_dir
"""
import re, subprocess, sys, urllib.request

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
WP = sys.argv[2] if len(sys.argv) > 2 else '/home/claude/wpenv/wp'
results = []
def check(c, label):
    results.append((bool(c), label)); print(('  ✓ ' if c else '  ✗ ') + label)
def wp(php):
    return subprocess.run(['wp', '--allow-root', '--path=' + WP, 'eval', php], capture_output=True, text=True).stdout.strip()
def get(path):
    with urllib.request.urlopen(BASE + path) as r:
        return r.read().decode()
def og(html):
    m = re.search(r'<meta property="og:image" content="([^"]+)"', html)
    return m.group(1) if m else ''
def footer(html):
    i = html.index('<footer class="sm-footer"')
    f = html[i:html.index('</footer>', i)]
    return re.findall(r'sm-footer__title">([^<]+)', f), re.findall(r'<li><a href="([^"]+)">', f)

saved = wp('echo wp_json_encode( get_option( "sm_seo_settings" ) );')
wp('$s = get_option("sm_seo_settings", array()); foreach (array("fr"=>"default_image","ar"=>"default_image_ar","en"=>"default_image_en") as $l => $k) { $s[$k] = SM\\Seo\\Settings::bundled_image($l); } update_option("sm_seo_settings", $s, false);')

print('A. Share image per language')
for path, lang in (('/?lang=fr', 'fr'), ('/ar/', 'ar'), ('/en/', 'en'), ('/ar/faq/', 'ar'), ('/en/guides/', 'en'), ('/catalogue/?lang=fr', 'fr')):
    img = og(get(path))
    check(img.endswith('/assets/share/reusteel-partage-%s.jpg' % lang), '%s: image %s' % (path, lang))
check(urllib.request.urlopen(og(get('/ar/'))).headers.get('Content-Type', '').startswith('image/jpeg'), 'the image is served (JPEG)')
links = re.findall(r'href="%s(/annonce/[a-z0-9-]+-[0-9]+/)"' % re.escape(BASE), get('/produits/?lang=fr') + get('/reemploi/?lang=fr'))
check(links, 'a published listing to share')
imgs = [og(get('/ar' + l)) for l in list(dict.fromkeys(links))[:15]]
own = [i for i in imgs if i and 'assets/share' not in i]
check(own, 'a listing with photos keeps its own photo (Arabic page too)')
check(all(i for i in imgs), 'every listing has a share image (its photo or the image of its language)')
wp('$s = get_option("sm_seo_settings"); $s["default_image_en"] = ""; update_option("sm_seo_settings", $s, false);')
check(og(get('/en/')).endswith('reusteel-partage-fr.jpg'), 'no English image: the French one')
adm = wp('wp_set_current_user(1); ob_start(); SM\\Seo\\Admin\\Admin::page(); $h = ob_get_clean(); echo substr_count($h, "data-smseo-pick"), "|", substr_count($h, "data-smseo-default"), "|", substr_count($h, "smseo-img__preview\\"><img");')
check(adm == '3|3|2', 'SM → SEO: three images with « Choose an image », « Image of ReuSteel » and a preview (%s)' % adm)

print('B. Footer links')
titles, links = footer(get('/?lang=fr'))
check(titles == ['Marchés', 'Services', 'Plateforme'] and len(links) == 19, 'three columns, 19 links')
wp('$m = get_option("sm_theme_pages"); wp_update_post(array("ID" => $m["price-index"], "post_status" => "draft"));')
titles, links = footer(get('/?lang=fr'))
check(len(links) == 18 and not any('/indice-prix/' in l for l in links), 'unpublished page: its link is not shown')
check(not any(l.rstrip('/') == BASE for l in links), 'no link to the home page instead')
wp('$m = get_option("sm_theme_pages"); foreach (array("products","reuse","buildings","rfq") as $k) { wp_update_post(array("ID" => $m[$k], "post_status" => "draft")); }')
titles, links = footer(get('/?lang=fr'))
check(titles == ['Services', 'Plateforme'], 'a column without links is not shown')
wp('$m = get_option("sm_theme_pages"); foreach (array("products","reuse","buildings","rfq","price-index") as $k) { wp_update_post(array("ID" => $m[$k], "post_status" => "publish")); }')
titles, links = footer(get('/ar/'))
check(len(links) == 19 and titles[0] == 'الأسواق', 'everything back (Arabic page)')

wp('update_option("sm_seo_settings", json_decode(%r, true), false);' % saved)
n = sum(1 for ok, _ in results if ok)
print('\n%d/%d passed' % (n, len(results)))
sys.exit(0 if n == len(results) else 1)
