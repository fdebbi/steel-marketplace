"""Cross-check the steel section tables before they enter the catalogue.

For every section:
  1. dimensions from the primary source (manufacturer / stockist sheet reproducing the EN standard)
     are compared with an independent compilation (structuralcodes, Apache-2.0; eurocodepy, LGPL);
  2. the cross-section area is recomputed from the dimensions (exact geometry, fillets included)
     and compared with the published area;
  3. the mass per metre is recomputed (A x 7850 kg/m3) and compared with the published mass.
Rows that disagree are reported and are NOT accepted.
"""
import csv, json, math, sys, os
sys.path.insert(0, os.path.dirname(__file__))
import sc_geom as G  # geometry helpers from structuralcodes (Apache-2.0)

RHO = 7850e-6  # kg per mm2 per metre (7850 kg/m3)
HERE = os.path.dirname(__file__)
SC = json.load(open(os.path.join(HERE, 'structuralcodes.json')))
# eurocodepy (pip download eurocodepy; unzip): path given by the EUROCODEPY_I env var, optional.
_ec = os.environ.get('EUROCODEPY_I', '')
EC = {r['Section']: r for r in json.load(open(_ec))} if _ec and os.path.exists(_ec) else {}

def rows(name):
    with open(os.path.join(HERE, 'raw', name)) as f:
        return [{k: (v if k == 'designation' else float(v) if v not in ('', None) else None) for k, v in r.items()} for r in csv.DictReader(f)]

def key(d):
    return d.replace(' ', '')

report, accepted = [], {}

def check(family, desig, src, area_mm2, extra_dims=None, tol_area=0.006, tol_mass=0.012):
    """area check: published A (cm2) vs computed; mass check: published G vs computed from computed area."""
    issues = []
    a_pub = src.get('A')
    g_pub = src.get('G')
    a_cm2 = area_mm2 / 100
    g = area_mm2 * RHO
    if a_pub and abs(a_cm2 - a_pub) > max(a_pub * tol_area, 0.07 if float(a_pub).is_integer() else 0):
        issues.append(f'area computed {a_cm2:.2f} vs published {a_pub}')
    # Published masses are rounded to 0.1 kg/m (or 1 kg/m above 100): allow rounding + tolerance.
    if g_pub:
        step = 1.0 if g_pub >= 100 and float(g_pub).is_integer() else 0.1
        if abs(g - g_pub) > max(step / 2 + 0.02, g_pub * tol_mass):
            issues.append(f'mass computed {g:.2f} vs published {g_pub}')
    if extra_dims:
        issues += extra_dims
    report.append((family, desig, 'OK' if not issues else 'REJECT', '; '.join(issues), round(a_cm2, 2), round(g, 2), g_pub))
    if not issues:
        accepted.setdefault(family, []).append(dict(src, A_calc=round(a_cm2, 3), G_calc=round(g, 3)))

def cmp_dims(src, ref, fields, label):
    out = []
    if ref is None:
        return [f'missing in {label}']
    for f, rf in fields:
        if src.get(f) is None or ref.get(rf) is None:
            continue
        if abs(float(src[f]) - float(ref[rf])) > 0.051:
            out.append(f'{f}={src[f]} vs {label} {ref[rf]}')
    return out

def i_area(h, b, tw, tf, r):
    return 2 * b * tf + (h - 2 * tf) * tw + (4 - math.pi) * r * r

# ---- Parallel flange I/H (EN 10365) -------------------------------------------------
for fam, fname, ecpref in (('IPE', 'ipe_stad.csv', 'IPE'), ('HEA', 'hea_stad.csv', 'HEA'), ('HEB', 'heb_stad.csv', 'HEB'), ('HEM', 'hem_stad.csv', 'HEM')):
    for s in rows(fname):
        n = s['designation'].split()[1]
        scref = SC['IPE'].get('IPE' + n) if fam == 'IPE' else SC['HE'].get(fam + n)
        ecref = EC.get(ecpref + n)
        dims = cmp_dims(s, scref, [('h', 'h'), ('b', 'b'), ('tw', 'tw'), ('tf', 'tf'), ('r', 'r')], 'structuralcodes')
        if ecref:  # eurocodepy stores cm
            e = {k: ecref[k] * 10 for k in ('h', 'b', 'tw', 'tf', 'r')}
            dims += cmp_dims(s, e, [('h', 'h'), ('b', 'b'), ('tw', 'tw'), ('tf', 'tf'), ('r', 'r')], 'eurocodepy')
        check(fam, s['designation'], s, i_area(s['h'], s['b'], s['tw'], s['tf'], s['r']), dims)

# ---- Taper flange I (IPN, EN 10365; 14 % slope) -------------------------------------
for s in rows('ipn_am.csv'):
    n = s['designation'].split()[1]
    scref = SC['IPN'].get('IPN' + n)
    dims = cmp_dims(s, scref, [('h', 'h'), ('b', 'b'), ('tw', 'tw'), ('tf', 'tf'), ('r1', 'r1'), ('r2', 'r2')], 'structuralcodes')
    poly = G._create_taper_I_section(h=s['h'], b=s['b'], tw=s['tw'], tf=s['tf'], r1=s['r1'], r2=s['r2'], slope=0.14)
    check('IPN', s['designation'], s, poly.area, dims)

# ---- Taper flange channels (UPN, EN 10365; 8 % slope h<=300, 5 % above) ------------
mirror = {r['designation']: r for r in rows('upn_ammirror.csv')}
stad = {r['designation']: r for r in rows('upn_stad.csv')}
for desig, s in sorted(mirror.items(), key=lambda kv: kv[1]['h']):
    n = desig.split()[1]
    scref = SC['UPN'].get('UPN' + n)
    dims = cmp_dims(s, scref, [('h', 'h'), ('b', 'b'), ('tw', 'tw'), ('tf', 'tf'), ('r1', 'r1'), ('r2', 'r2')], 'structuralcodes')
    if desig in stad:
        dims += cmp_dims(s, stad[desig], [('h', 'h'), ('b', 'b'), ('tw', 'tw'), ('tf', 'tf'), ('r1', 'r1'), ('A', 'A')], 'STAD')
    slope, u = (0.08, s['b'] / 2) if s['h'] <= 300 else (0.05, (s['b'] - s['tw']) / 2)
    poly = G._create_taper_U_section(h=s['h'], b=s['b'], tw=s['tw'], tf=s['tf'], r1=s['r1'], r2=s['r2'], slope=slope, u=u)
    check('UPN', desig, s, poly.area, dims)

# ---- Parallel flange channels (UPE) --------------------------------------------------
for s in rows('upe_stad.csv'):
    n = s['designation'].split()[1]
    scref = SC['UPE'].get('UPE' + n)
    dims = cmp_dims(s, scref, [('h', 'h'), ('b', 'b'), ('tw', 'tw'), ('tf', 'tf'), ('r', 'r')], 'structuralcodes')
    area = 2 * s['b'] * s['tf'] + (s['h'] - 2 * s['tf']) * s['tw'] + 2 * (1 - math.pi / 4) * s['r'] ** 2
    check('UPE', s['designation'], s, area, dims)

# ---- Angles (EN 10056-1): only sizes also in structuralcodes' EN 10056-1 list ------
for s in rows('l_stad.csv'):
    h, b, t = int(s['h']) if s['h'].is_integer() else s['h'], int(s['b']) if s['b'].is_integer() else s['b'], s['t']
    tt = int(t) if t.is_integer() else t
    fam = 'L' if s['h'] == s['b'] else 'LI'
    scref = SC[fam].get(f'L{h}x{b}x{tt}')
    if scref is None:
        report.append((fam, s['designation'], 'SKIP', 'not in EN 10056-1 reference list', None, None, s['G']))
        continue
    dims = cmp_dims(s, scref, [('h', 'h'), ('b', 'b'), ('t', 't')], 'structuralcodes')
    r1, r2 = scref['r1'], scref['r2']
    if abs(s['r'] - r1) > 0.05:  # root radius: EN 10056-1 value kept; noted
        report.append((fam, s['designation'], 'NOTE', f"root radius {s['r']} in STAD sheet, {r1} in EN 10056-1 list: EN value kept", None, None, None))
    area = t * (s['h'] + s['b'] - t) + (1 - math.pi / 4) * (r1 * r1 - 2 * r2 * r2)
    src = dict(s, r1=r1, r2=r2)
    check(fam, s['designation'], src, area, dims)

# ---- Hollow sections (EN 10219-2 formula), checked against Ferpinta rows --------------
def shs_area(B, H, t):
    ro = 2 * t if t <= 6 else (2.5 * t if t <= 10 else 3 * t)
    ri = ro - t
    return 2 * t * (B + H - 2 * t) - (4 - math.pi) * (ro * ro - ri * ri)
for s in rows('shs_ferpinta_check.csv'):
    s = dict(s, designation=f"SHS {s['B']:g}x{s['B']:g}x{s['t']:g}")
    check('SHS-formula', s['designation'], s, shs_area(s['B'], s['B'], s['t']), tol_area=0.004, tol_mass=0.002)
for s in rows('chs_ferpinta_check.csv'):
    s = dict(s, designation=f"CHS {s['D']:g}x{s['t']:g}")
    check('CHS-formula', s['designation'], s, math.pi * s['t'] * (s['D'] - s['t']), tol_area=0.004, tol_mass=0.002)

ok = sum(1 for r in report if r[2] == 'OK'); rej = [r for r in report if r[2] == 'REJECT']; skip = [r for r in report if r[2] == 'SKIP']
print(f'OK {ok}  REJECT {len(rej)}  SKIP {len(skip)}')
for r in rej:
    print('REJECT', r)
for r in skip:
    print('SKIP  ', r[1])
with open(os.path.join(HERE, 'verification-report.csv'), 'w', newline='') as f:
    w = csv.writer(f); w.writerow(['family', 'designation', 'result', 'issues', 'A_calc_cm2', 'G_calc_kg_m', 'G_published']); w.writerows(report)
json.dump(accepted, open(os.path.join(HERE, 'accepted.json'), 'w'), indent=1)
