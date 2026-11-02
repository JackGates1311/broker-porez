#!/usr/bin/env python3
"""Hookovi za graphify (docs/graphify.txt): dozvola korisnika i provera zastarelog grafa.

UserPromptSubmit (argument --prompt): svaki novi prompt briše dozvolu za graphify, osim
kada korisnik sam kuca /graphify, što je već izričita dozvola.

PreToolUse: pre korišćenja graphify-ja (graphify skill, graphify query/path/..., čitanje
graph.json / GRAPH_REPORT.md) poziv se odbija dok korisnik u ovom promptu ne odobri graphify.
Claude ga tada pita (AskUserQuestion) i, ako je zastareo, odmah nudi i ažuriranje grafa.
Sa dozvolom se još proverava da li je graf zastareo u odnosu na graphify-out/manifest.json;
"ipak koristi" upisuje otisak razlika u graphify-out/.provera_prihvacena.

Za druge komande i pri bilo kakvoj grešci hook ne radi ništa (poziv prolazi).
"""

import hashlib
import json
import re
import subprocess
import sys
from pathlib import Path

# Komande koje čitaju graf; izgradnja i ažuriranje grafa (to_json, update) se ne proveravaju na zastarelost.
CITANJE = re.compile(r'\bgraphify\s+(query|path|explain|affected|god-nodes)\b')
IZGRADNJA = re.compile(r'to_json\(|build_from_json|save_manifest|\bgraphify\s+(update|cluster-only|export)\b|--update')
FAJLOVI_GRAFA = re.compile(r'graphify-out/(graph\.json|GRAPH_REPORT\.md)')
# Stvarno pokretanje graphify-ja: CLI komanda (i posle ;, &&, |, $( ) ili Python modul. Samo pominjanje
# (git add docs/graphify.txt, grep graphify) se ne računa.
POKRECE_GRAPHIFY = re.compile(r'(^|[;&|(/]|\$\()\s*graphify\s|\b(from|import)\s+graphify\b|-m\s+graphify\b')

# Graphify je instaliran samo u projektnom .venv (ne globalno).
VENV_PYTHON = Path('.venv/bin/python3')
LOKALNI_GRAPHIFY = '.venv/bin/graphify'


def vrsta_poziva(ulaz: dict) -> str | None:
    """'citanje' (koristi postojeći graf), 'graphify' (ostalo: skill, izgradnja) ili None."""
    alat = ulaz.get('tool_name')
    parametri = ulaz.get('tool_input') or {}

    if alat == 'Skill':
        return 'graphify' if parametri.get('skill', '').split(':')[-1] == 'graphify' else None

    if alat == 'Bash':
        komanda = parametri.get('command', '')
        if not IZGRADNJA.search(komanda) and (CITANJE.search(komanda) or FAJLOVI_GRAFA.search(komanda)):
            return 'citanje'
        return 'graphify' if POKRECE_GRAPHIFY.search(komanda) else None

    if alat in ('Read', 'Grep'):
        putanja = parametri.get('file_path') or parametri.get('path') or ''
        return 'citanje' if FAJLOVI_GRAFA.search(putanja) else None

    return None


def trenutni_fajlovi(koren: Path, graf: Path) -> set[str]:
    """Isti skup fajlova koji graphify obrađuje (detect sa njegovim izuzecima), relativne putanje."""
    python = str(koren / VENV_PYTHON)
    skripta = (
        'import json\n'
        'from pathlib import Path\n'
        'from graphify.detect import detect\n'
        'from graphify.paths import out_path\n'
        'from graphify.watch import _read_build_excludes, _read_build_gitignore\n'
        "r = detect(Path('.'), extra_excludes=_read_build_excludes(out_path()), gitignore=_read_build_gitignore(out_path()))\n"
        "print(json.dumps([f for v in r['files'].values() for f in v]))\n"
    )
    izlaz = subprocess.run([python, '-c', skripta], cwd=koren, capture_output=True, text=True, timeout=30, check=True)
    return {str(Path(f).resolve().relative_to(koren)) for f in json.loads(izlaz.stdout)}


def md5(putanja: Path) -> str:
    return hashlib.md5(putanja.read_bytes()).hexdigest()


def zastarelost(koren: Path, graf: Path) -> tuple[str, str] | None:
    """(opis razlika, otisak) ako se fajlovi razlikuju od manifesta; None ako je graf aktuelan."""
    manifest = json.loads((graf / 'manifest.json').read_text(encoding='utf-8'))
    sada = trenutni_fajlovi(koren, graf)

    izmenjeni = []
    for putanja, zapis in manifest.items():
        fajl = koren / putanja
        if putanja not in sada or not fajl.is_file():
            continue
        # mtime je brza provera; sadržaj se hešira samo kad se vreme promenilo.
        if abs(fajl.stat().st_mtime - zapis.get('mtime', 0)) < 1e-6:
            continue
        if md5(fajl) != (zapis.get('ast_hash') or zapis.get('semantic_hash')):
            izmenjeni.append(putanja)

    izmenjeni.sort()
    novi = sorted(sada - manifest.keys())
    obrisani = sorted(p for p in manifest if p not in sada)

    if not (izmenjeni or novi or obrisani):
        return None

    # Otisak uključuje i sadržaj, pa nova izmena već prijavljenog fajla opet traži odluku.
    sadrzaj = {p: md5(koren / p) for p in izmenjeni + novi if (koren / p).is_file()}
    otisak = hashlib.sha256(json.dumps([sadrzaj, obrisani], sort_keys=True).encode()).hexdigest()[:16]

    ukupno = len(izmenjeni) + len(novi) + len(obrisani)
    opis = (
        f'Graphify graf je ZASTAREO: {ukupno} fajlova se razlikuje od stanja kada je graf napravljen.'
        + spisak('izmenjeni', izmenjeni)
        + spisak('novi', novi)
        + spisak('obrisani', obrisani)
    )

    return opis, otisak


def spisak(naslov: str, putanje: list[str]) -> str:
    if not putanje:
        return ''
    prikaz = ', '.join(putanje[:5]) + (f' (+{len(putanje) - 5})' if len(putanje) > 5 else '')
    return f'\n  {naslov} ({len(putanje)}): {prikaz}'


def odbij(poruka: str, uputstvo: str) -> None:
    print(json.dumps({
        'systemMessage': poruka,
        'hookSpecificOutput': {
            'hookEventName': 'PreToolUse',
            'permissionDecision': 'deny',
            'permissionDecisionReason': poruka + '\n\n' + uputstvo,
        },
    }, ensure_ascii=False))


def na_prompt(ulaz: dict, dozvola: Path) -> None:
    if ulaz.get('prompt', '').lstrip().startswith('/graphify'):
        dozvola.parent.mkdir(parents=True, exist_ok=True)
        dozvola.write_text('korisnik je pokrenuo /graphify\n', encoding='utf-8')
    else:
        dozvola.unlink(missing_ok=True)


def pre_alata(ulaz: dict, koren: Path, dozvola: Path) -> None:
    vrsta = vrsta_poziva(ulaz)
    if vrsta is None:
        return

    graf = koren / 'graphify-out'
    prihvaceno = graf / '.provera_prihvacena'
    ima_graf = (graf / 'manifest.json').is_file() and (koren / VENV_PYTHON).is_file()
    # Greška u proveri zastarelosti ne sme da preskoči proveru dozvole ispod.
    try:
        stanje = zastarelost(koren, graf) if ima_graf and vrsta == 'citanje' else None
    except Exception:
        stanje = None

    azuriraj = f'`{LOKALNI_GRAPHIFY} update .` (samo kod, bez tokena) ili `/graphify . --update` (i dokumenti)'
    dozvoli = 'mkdir -p .claude && touch .claude/.graphify_dozvola'

    if not dozvola.is_file():
        poruka = 'Claude želi da koristi graphify.' + (f'\n{stanje[0]}' if stanje else '')
        opcije = (
            '  1. "Ne koristi graphify": odgovori bez grafa (Grep/Read nad kodom) i ne pokušavaj graphify '
            'ponovo u ovom promptu.\n'
        )
        if stanje:
            opcije += (
                f'  2. "Ažuriraj graf pa koristi": izvrši `{dozvoli}`, pokreni {azuriraj}, pa ponovi upit.\n'
                f'  3. "Koristi zastareo graf": izvrši `{dozvoli} && echo {stanje[1]} > graphify-out/.provera_prihvacena`, '
                'ponovi poziv i navedene fajlove proveri u kodu.'
            )
        else:
            opcije += f'  2. "Koristi graphify": izvrši `{dozvoli}`, pa ponovi isti poziv uz korišćenje `{LOKALNI_GRAPHIFY}`.'
        odbij(poruka, (
            'Graphify se koristi samo uz izričitu dozvolu korisnika u ovom promptu. Pre bilo kakvog '
            'korišćenja pitaj korisnika (AskUserQuestion) da li želi graphify i zašto bi pomogao; ne upisuj '
            f'dozvolu bez njegovog odgovora. Uvek koristi lokalnu putanju (`{LOKALNI_GRAPHIFY}`, '
            f'`{VENV_PYTHON}`), a nikada globalnu komandu niti pip install van .venv. Opcije:\n' + opcije
        ))
        return

    if stanje and not (prihvaceno.is_file() and prihvaceno.read_text(encoding='utf-8').strip() == stanje[1]):
        odbij(stanje[0], (
            'Ne koristi graf pre odluke korisnika. Pitaj ga (AskUserQuestion) šta želi:\n'
            f'  1. "Ažuriraj graf": pokreni {azuriraj}, pa ponovi upit.\n'
            f'  2. "Ipak koristi graf": izvrši `echo {stanje[1]} > graphify-out/.provera_prihvacena`, pa ponovi '
            f'isti poziv uz `{LOKALNI_GRAPHIFY}`; u odgovoru naglasi da graf ne vidi gore navedene izmene i proveri te fajlove u kodu.\n'
            '  3. "Preskoči graf": odgovori bez grafa (Grep/Read nad kodom).'
        ))


def main() -> None:
    ulaz = json.load(sys.stdin)
    koren = Path(ulaz.get('cwd') or '.').resolve()
    dozvola = koren / '.claude' / '.graphify_dozvola'

    if '--prompt' in sys.argv:
        na_prompt(ulaz, dozvola)
    else:
        pre_alata(ulaz, koren, dozvola)


if __name__ == '__main__':
    try:
        main()
    except Exception:
        # Provera nikad ne sme da blokira rad zbog sopstvene greške.
        pass
