"""
Responsive + bilingual visual test.

For every page x language (fr, ar) x width (360…1440 px) it:
  - loads the page and waits for fonts,
  - checks <html dir> matches the language,
  - detects horizontal overflow (document wider than the viewport) and names the
    widest offending elements,
  - saves a full-page screenshot.
Writes report.json and prints a summary. Exit code 1 if any check fails.

Usage: python3 tools/visual-test.py [base_url] [out_dir]
Dashboard pages need accounts: set SM_VT_COM="email:password" (company account),
SM_VT_PRO (professional) and SM_VT_ENG (approved partner engineer); without them those pages
show the sign-in page and are still checked.
Requires: pip install playwright && playwright install chromium
"""
import json, os, sys
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://localhost:8080'
OUT = sys.argv[2] if len(sys.argv) > 2 else 'visual-test-output'
WIDTHS = [360, 390, 430, 768, 1024, 1280, 1440]
# name: (path, account) — account None = visitor, 'com' = business, 'eng' = engineering.
PAGES = {
    'home': '/',
    'products': '/produits/',
    'reuse': '/reemploi/',
    'buildings': '/batiments/',
    'product-detail': '/annonce/?id=101',
    'reuse-detail': '/annonce/?id=202',
    'building-dismantled': '/annonce/?id=302',
    'building-assembled': '/annonce/?id=301',
    'company': '/entreprise/?c=acier-hodna',
    'professional-profile': '/entreprise/?c=benali-soudure',
    'companies': '/entreprises/',
    'professionals': '/professionnels/',
    'engineer-apply': '/devenir-ingenieur-partenaire/',
    'mission-request': '/demander-une-etude/',
    'register': '/inscription/',
    'login': '/connexion/',
    'rfq': '/demandes-de-devis/',
    'contact': '/contact/',
    'services': '/services/',
    'engineering': '/ingenierie/',
    'weight-calculator': '/calcul-poids/?family=IPE&section=7&mode=weight&total_kg=5000',
    'dashboard': ('/compte/', 'com'),
    'dashboard-company': ('/compte/?view=company', 'com'),
    'dashboard-verification': ('/compte/?view=verification', 'com'),
    'dashboard-messages': ('/compte/?view=messages', 'com'),
    'dashboard-publish': ('/compte/?view=publish', 'com'),
    'dashboard-publish-profiles': ('/compte/?view=publish&cat=profiles', 'com'),
    'dashboard-publish-roofing': ('/compte/?view=publish&cat=roofing', 'com'),
    'dashboard-engineer': ('/compte/', 'eng'),
    'dashboard-engineer-missions': ('/compte/?view=missions', 'eng'),
    'dashboard-engineer-billing': ('/compte/?view=billing', 'eng'),
    'dashboard-engineer-files': ('/compte/?view=files', 'eng'),
    'dashboard-engineer-partner': ('/compte/?view=partner', 'eng'),
    'dashboard-studies': ('/compte/?view=studies', 'com'),
    'dashboard-services': ('/compte/?view=services', 'pro'),
    'dashboard-service-form': ('/compte/?view=services&new=1', 'pro'),
    'dashboard-my-service-requests': ('/compte/?view=my-service-requests', 'com'),
    'dashboard-professional': ('/compte/', 'pro'),
    'dashboard-professional-company': ('/compte/?view=company', 'pro'),
    'dashboard-portfolio': ('/compte/?view=portfolio', 'pro'),
    'dashboard-portfolio-form': ('/compte/?view=portfolio&new=1', 'pro'),
    'dashboard-ai': ('/compte/?view=ai', 'com'),
    'dashboard-ai-document': ('/compte/?view=ai&tool=document', 'com'),
    'dashboard-engineer-ai': ('/compte/?view=ai&tool=document', 'eng'),
}
ACCOUNTS = {k: os.environ.get(v) for k, v in (('com', 'SM_VT_COM'), ('eng', 'SM_VT_ENG'), ('pro', 'SM_VT_PRO'))}

def login(page, who):
    cred = ACCOUNTS.get(who)
    if not cred:
        return
    email, password = cred.split(':', 1)
    page.goto(BASE + '/connexion/?lang=fr')
    page.fill('#l-user', email)
    page.fill('#l-pass', password)
    page.click('form:has(#l-user) button[type="submit"]')
    page.wait_for_load_state()
OVERFLOW_JS = """() => {
  const vw = document.documentElement.clientWidth;
  const sw = document.documentElement.scrollWidth;
  const offenders = [];
  if (sw > vw + 1) {
    for (const el of document.querySelectorAll('body *')) {
      const r = el.getBoundingClientRect();
      if (!r.width || getComputedStyle(el).position === 'fixed') continue;
      if (r.right > vw + 1 || r.left < -1) {
        let p = el.parentElement, clipped = false;
        while (p && p !== document.body) { const o = getComputedStyle(p).overflowX; if (o === 'auto' || o === 'hidden' || o === 'scroll') { clipped = true; break; } p = p.parentElement; }
        if (!clipped) offenders.push((el.className && el.className.baseVal === undefined ? '.' + String(el.className).split(' ').join('.') : el.tagName) + ' ' + Math.round(r.left) + '→' + Math.round(r.right));
      }
      if (offenders.length > 6) break;
    }
  }
  return { vw, sw, dir: document.documentElement.dir, lang: document.documentElement.lang, offenders };
}"""

def main():
    os.makedirs(OUT, exist_ok=True)
    results, failures = [], 0
    with sync_playwright() as p:
        browser = p.chromium.launch()
        for lang in ('fr', 'ar', 'en'):
            for width in WIDTHS:
                pages_by_account = {}
                for name, spec in PAGES.items():
                    path, who = spec if isinstance(spec, tuple) else (spec, None)
                    pages_by_account.setdefault(who, []).append((name, path))
                for who, entries in pages_by_account.items():
                  ctx = browser.new_context(viewport={'width': width, 'height': 900}, device_scale_factor=1, reduced_motion='reduce')
                  page = ctx.new_page()
                  login(page, who)
                  for name, path in entries:
                    url = BASE + path + ('&' if '?' in path else '?') + 'lang=' + lang
                    page.goto(url, wait_until='networkidle')
                    page.evaluate('document.fonts.ready')
                    info = page.evaluate(OVERFLOW_JS)
                    expected_dir = 'rtl' if lang == 'ar' else 'ltr'
                    ok = info['sw'] <= info['vw'] + 1 and info['dir'] == expected_dir
                    failures += 0 if ok else 1
                    shot = os.path.join(OUT, f'{name}-{lang}-{width}.png')
                    page.screenshot(path=shot, full_page=True)
                    results.append({**info, 'page': name, 'lang': lang, 'width': width, 'ok': ok, 'screenshot': shot})
                  ctx.close()
        browser.close()
    with open(os.path.join(OUT, 'report.json'), 'w') as f:
        json.dump(results, f, indent=1, ensure_ascii=False)
    print(f'{len(results) - failures}/{len(results)} checks passed')
    for r in results:
        if not r['ok']:
            print('FAIL', r['page'], r['lang'], r['width'], 'dir=' + r['dir'], f"scroll {r['sw']}>{r['vw']}", r['offenders'][:4])
    sys.exit(1 if failures else 0)

if __name__ == '__main__':
    main()
