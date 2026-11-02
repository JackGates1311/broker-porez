#!/usr/bin/env python3
"""PreToolUse hook: pre čitanja graphify grafa proverava da li je zastareo (docs/graphify.txt).

Graf je zastareo ako se fajlovi projekta razlikuju od graphify-out/manifest.json (izmenjen
sadržaj, nov ili obrisan fajl). Tada se poziv odbija, a Claude pita korisnika: ažuriraj graf,
ipak koristi graf ili preskoči graf. "Ipak koristi" upisuje otisak trenutnih razlika u
graphify-out/.provera_prihvacena, pa se isto pitanje ne ponavlja dok se kod opet ne promeni.

Bez grafa, za druge komande i pri bilo kakvoj grešci hook ne radi ništa (poziv prolazi).
"""

import hashlib
import json
import re
import subprocess
import sys
from pathlib import Path

GRAF = Path('graphify-out')
PRIHVACENO = GRAF / '.provera_prihvacena'

# Komande koje čitaju graf; izgradnja i ažuriranje grafa (to_json, update) se ne proveravaju.
CITANJE = re.compile(r'\bgraphify\s+(query|path|explain|affected|god-nodes)\b')
IZGRADNJA = re.compile(r'to_json\(|build_from_json|save_manifest|\bgraphify\s+(update|cluster-only|export)\b|--update')
FAJLOVI_GRAFA = re.compile(r'graphify-out/(graph\.json|GRAPH_REPORT\.md)')


def cita_graf(ulaz: dict) -> bool:
    alat = ulaz.get('tool_name')
    parametri = ulaz.get('tool_input') or {}

    if alat == 'Bash':
        komanda = parametri.get('command', '')
        if IZGRADNJA.search(komanda):
            return False
        return bool(CITANJE.search(komanda) or FAJLOVI_GRAFA.search(komanda))

    if alat in ('Read', 'Grep'):
        putanja = parametri.get('file_path') or parametri.get('path') or ''
        return bool(FAJLOVI_GRAFA.search(putanja))

    return False


def trenutni_fajlovi(koren: Path) -> set[str]:
    """Isti skup fajlova koji graphify obrađuje (detect sa njegovim izuzecima), relativne putanje."""
    python = (GRAF / '.graphify_python').read_text(encoding='utf-8').strip()
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


def razlike(koren: Path) -> tuple[list[str], list[str], list[str]]:
    manifest = json.loads((GRAF / 'manifest.json').read_text(encoding='utf-8'))
    sada = trenutni_fajlovi(koren)

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

    novi = sorted(sada - manifest.keys())
    obrisani = sorted(p for p in manifest if p not in sada)

    return sorted(izmenjeni), novi, obrisani


def spisak(naslov: str, putanje: list[str]) -> str:
    if not putanje:
        return ''
    prikaz = ', '.join(putanje[:5]) + (f' (+{len(putanje) - 5})' if len(putanje) > 5 else '')
    return f'\n  {naslov} ({len(putanje)}): {prikaz}'


def main() -> None:
    ulaz = json.load(sys.stdin)
    koren = Path(ulaz.get('cwd') or '.').resolve()
    global GRAF, PRIHVACENO
    GRAF = koren / 'graphify-out'
    PRIHVACENO = GRAF / '.provera_prihvacena'

    if not cita_graf(ulaz) or not (GRAF / 'manifest.json').is_file() or not (GRAF / '.graphify_python').is_file():
        return

    izmenjeni, novi, obrisani = razlike(koren)
    if not (izmenjeni or novi or obrisani):
        return

    # Otisak uključuje i sadržaj, pa nova izmena već prijavljenog fajla opet traži odluku.
    sadrzaj = {p: md5(koren / p) for p in izmenjeni + novi if (koren / p).is_file()}
    otisak = hashlib.sha256(json.dumps([sadrzaj, obrisani], sort_keys=True).encode()).hexdigest()[:16]
    if PRIHVACENO.is_file() and PRIHVACENO.read_text(encoding='utf-8').strip() == otisak:
        return

    ukupno = len(izmenjeni) + len(novi) + len(obrisani)
    opis = (
        f'Graphify graf je ZASTAREO: {ukupno} fajlova se razlikuje od stanja kada je graf napravljen.'
        + spisak('izmenjeni', izmenjeni)
        + spisak('novi', novi)
        + spisak('obrisani', obrisani)
    )
    uputstvo = (
        '\n\nNe koristi graf pre odluke korisnika. Prikaži korisniku ovo upozorenje i pitaj ga '
        '(AskUserQuestion) šta želi:\n'
        '  1. "Ažuriraj graf": pokreni /graphify . --update (kod se čita besplatno, tokene troše samo '
        'izmenjeni dokumenti), pa ponovi upit.\n'
        f'  2. "Ipak koristi graf": izvrši `echo {otisak} > graphify-out/.provera_prihvacena`, pa ponovi '
        'isti poziv; u odgovoru naglasi da graf ne vidi gore navedene izmene i proveri te fajlove u kodu.\n'
        '  3. "Preskoči graf": odgovori bez grafa (Grep/Read nad kodom).'
    )

    print(json.dumps({
        'systemMessage': opis,
        'hookSpecificOutput': {
            'hookEventName': 'PreToolUse',
            'permissionDecision': 'deny',
            'permissionDecisionReason': opis + uputstvo,
        },
    }, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except Exception:
        # Provera nikad ne sme da blokira rad zbog sopstvene greške.
        pass
