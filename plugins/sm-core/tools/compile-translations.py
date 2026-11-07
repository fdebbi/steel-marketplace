"""Builds languages/fr_FR.po|mo and languages/ar.po|mo from the POT and tools/translations.py.
Usage: python3 tools/compile-translations.py   (requires: pip install polib)"""
import os, sys, polib
here = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, here)
from translations import T, P
root = os.path.dirname(here)
pot = polib.pofile(os.path.join(root, 'languages', 'sm-core.pot'))
langs = {
    'fr_FR': (0, 'nplurals=2; plural=(n > 1);', 'French'),
    'ar': (1, 'nplurals=6; plural=(n == 0 ? 0 : n == 1 ? 1 : n == 2 ? 2 : n % 100 >= 3 && n % 100 <= 10 ? 3 : n % 100 >= 11 ? 4 : 5);', 'Arabic'),
}
missing = set()
for code, (idx, plural, name) in langs.items():
    po = polib.POFile()
    po.metadata = {'Project-Id-Version': 'SM Core 0.1.0', 'Language': code, 'MIME-Version': '1.0',
                   'Content-Type': 'text/plain; charset=UTF-8', 'Content-Transfer-Encoding': '8bit', 'Plural-Forms': plural,
                   'X-Domain': 'sm-core'}
    for e in pot:
        if e.msgid_plural:
            val = P.get((e.msgid, e.msgid_plural))
            if not val: missing.add(e.msgid); continue
            forms = val[idx]
            po.append(polib.POEntry(msgid=e.msgid, msgid_plural=e.msgid_plural, msgstr_plural={i: f for i, f in enumerate(forms)}, occurrences=e.occurrences, comment=e.comment))
        else:
            val = T.get(e.msgid)
            if not val: missing.add(e.msgid); continue
            po.append(polib.POEntry(msgid=e.msgid, msgstr=val[idx], occurrences=e.occurrences, comment=e.comment))
    po.save(os.path.join(root, 'languages', f'sm-core-{code}.po'))
    po.save_as_mofile(os.path.join(root, 'languages', f'sm-core-{code}.mo'))
    print(code, len(po), 'entries,', f'{po.percent_translated()}% translated')
if missing:
    print('MISSING:', *sorted(missing), sep='\n  ')
