#!/usr/bin/env python3
"""Walidator data/miasta.csv wg schematu z sekcji 6.2 CLAUDE.md.

Uruchamiać przed KAŻDYM importem (analogicznie do przyszłego
scripts/dedup-gate.py, sekcja 11 CLAUDE.md) — wiersz niekompletny w
kolumnach unikalne_* nie powinien trafić do WP. Exit code 0 = wszystkie
wiersze przechodzą, 1 = są błędy (lista na stderr).

Reguła kompletności unikalne_* (synteza dwóch fragmentów CLAUDE.md, które
się częściowo pokrywają):
  - sekcja 6.2: "Wiersz bez kompletu pól unikalne_* nie przechodzi importu"
    (ogólna zasada — komplet, czyli wszystkie 5 pól).
  - sekcja 7: "Nawet tier 3 musi zawierać minimum 3 dane unikalne z kolumn
    unikalne_*. To jest twardy warunek importu, nie zalecenie." — czyli
    dla tier 3 dopuszczalne jest tylko 3 z 5 pól.
Przyjęta interpretacja: tier 1 i 2 wymagają wszystkich 5 pól unikalne_*
(spójne z tabelą warstw w sekcji 7, gdzie obie warstwy pokazują sądy,
gminy/powiat i dojazd), tier 3 wymaga minimum 3 z 5. Jeśli to złe
odczytanie zamysłu — do poprawienia; interpretacja jest tu świadomie
udokumentowana, żeby dało się ją łatwo zakwestionować.
"""

import argparse
import csv
import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent

EXPECTED_COLUMNS = [
    'slug', 'nazwa', 'nazwa_miejscownik', 'nazwa_dopelniacz',
    'wojewodztwo', 'powiat', 'ludnosc', 'tier',
    'unikalne_sad_okregowy', 'unikalne_sad_rejonowy', 'unikalne_gminy',
    'unikalne_dystans_km', 'unikalne_czas_dojazdu',
    'wspolpracownik_id', 'fala',
]

UNIKALNE_COLUMNS = [
    'unikalne_sad_okregowy', 'unikalne_sad_rejonowy', 'unikalne_gminy',
    'unikalne_dystans_km', 'unikalne_czas_dojazdu',
]

# Musi być zgodne z listą w olech_rewrite_wojewodztwo()
# (wp-content/themes/olech/inc/post-types.php) — inny slug tu oznacza,
# że hub wojewódzki /obszar-dzialania/{slug}/ zwróci 404 mimo poprawnego
# importu tego wiersza.
WOJEWODZTWA = {
    'dolnoslaskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
    'lodzkie', 'malopolskie', 'mazowieckie', 'opolskie',
    'podkarpackie', 'podlaskie', 'pomorskie', 'slaskie',
    'swietokrzyskie', 'warminsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie',
}

SLUG_RE = re.compile(r'^[a-z0-9]+(-[a-z0-9]+)*$')

GMINY_MIN = 3
# Konwencja tego repo dla list w jednej komórce CSV — ta sama co
# `uslugi_powiazane` w data/uslugi.csv. Docelowe pole ACF `unikalne_gminy`
# to textarea, jedna gmina na linię (inc/acf-pola.php) — przyszły importer
# (punkt 6) ma dzielić na GMINY_SEP i zapisywać jako "\n".join(...).
GMINY_SEP = '|'


def fail(errors, row_num, slug, message):
    errors.append(f"wiersz {row_num} ({slug or '(brak sluga)'}): {message}")


def validate_row(row, row_num, errors, seen_slugs):
    slug = (row.get('slug') or '').strip()

    if not slug:
        fail(errors, row_num, slug, "brak sluga")
    elif not SLUG_RE.match(slug):
        fail(errors, row_num, slug, f"slug '{slug}' niepoprawny (dozwolone: a-z, 0-9, myślniki, bez myślnika na końcu/początku)")
    elif slug in seen_slugs:
        fail(errors, row_num, slug, f"zduplikowany slug '{slug}'")
    else:
        seen_slugs.add(slug)

    for col in ('nazwa', 'nazwa_miejscownik', 'nazwa_dopelniacz', 'powiat'):
        if not (row.get(col) or '').strip():
            fail(errors, row_num, slug, f"puste pole '{col}'")

    wojewodztwo = (row.get('wojewodztwo') or '').strip()
    if not wojewodztwo:
        fail(errors, row_num, slug, "puste pole 'wojewodztwo'")
    elif wojewodztwo not in WOJEWODZTWA:
        fail(errors, row_num, slug, f"nieznane wojewodztwo '{wojewodztwo}' — sprawdź literówkę, musi być zgodne z olech_rewrite_wojewodztwo()")

    ludnosc = (row.get('ludnosc') or '').strip()
    if not ludnosc:
        fail(errors, row_num, slug, "puste pole 'ludnosc'")
    else:
        try:
            if int(ludnosc.replace(' ', '').replace(' ', '')) <= 0:
                fail(errors, row_num, slug, "'ludnosc' musi być liczbą dodatnią")
        except ValueError:
            fail(errors, row_num, slug, f"'ludnosc' nie jest liczbą całkowitą: '{ludnosc}'")

    tier = (row.get('tier') or '').strip()
    if tier not in ('1', '2', '3'):
        fail(errors, row_num, slug, f"'tier' musi być 1, 2 lub 3 (jest: '{tier}')")

    # Kompletność unikalne_* — patrz reguła w docstringu modułu.
    filled = [c for c in UNIKALNE_COLUMNS if (row.get(c) or '').strip()]
    if tier == '3':
        if len(filled) < 3:
            fail(errors, row_num, slug, f"tier 3 wymaga min. 3 z 5 pól unikalne_* (wypełnione: {len(filled)})")
    elif tier in ('1', '2'):
        missing = [c for c in UNIKALNE_COLUMNS if c not in filled]
        if missing:
            fail(errors, row_num, slug, f"tier {tier} wymaga wszystkich pól unikalne_* (brak: {', '.join(missing)})")
    # tier spoza {1,2,3} — błąd już zgłoszony wyżej, nie dublujemy komunikatu.

    gminy_raw = (row.get('unikalne_gminy') or '').strip()
    if gminy_raw:
        gminy = [g.strip() for g in gminy_raw.split(GMINY_SEP) if g.strip()]
        if len(gminy) < GMINY_MIN:
            fail(errors, row_num, slug, f"'unikalne_gminy' ma {len(gminy)} pozycji, wymagane min. {GMINY_MIN} (separator: '{GMINY_SEP}')")

    for col in ('unikalne_dystans_km', 'unikalne_czas_dojazdu'):
        val = (row.get(col) or '').strip()
        if val:
            try:
                if float(val.replace(',', '.')) <= 0:
                    fail(errors, row_num, slug, f"'{col}' musi być liczbą dodatnią")
            except ValueError:
                fail(errors, row_num, slug, f"'{col}' nie jest liczbą: '{val}'")

    fala = (row.get('fala') or '').strip()
    if not fala:
        fail(errors, row_num, slug, "puste pole 'fala'")
    else:
        try:
            fala_int = int(fala)
            if not (1 <= fala_int <= 10):
                fail(errors, row_num, slug, f"'fala' musi być w zakresie 1-10 (jest: {fala_int})")
        except ValueError:
            fail(errors, row_num, slug, f"'fala' nie jest liczbą całkowitą: '{fala}'")


def check_wspolpracownicy(rows, errors):
    """Sprawdza referencje wspolpracownik_id → data/wspolpracownicy.csv,
    jeśli ten plik już istnieje. Sekcja 17 CLAUDE.md: dane współpracowników
    czekają na klienta, więc brak pliku to informacja, nie błąd walidacji.
    """
    wspolpracownicy_path = REPO_ROOT / 'data' / 'wspolpracownicy.csv'
    referenced = {}
    for i, row in enumerate(rows, start=2):
        wid = (row.get('wspolpracownik_id') or '').strip()
        if wid:
            referenced.setdefault(wid, []).append(i)

    if not referenced:
        return

    if not wspolpracownicy_path.exists():
        print(
            f"UWAGA: {len(referenced)} unikalny(ch) wspolpracownik_id w CSV, ale "
            f"data/wspolpracownicy.csv jeszcze nie istnieje (sekcja 17 CLAUDE.md — "
            f"dane od klienta) — referencji nie sprawdzono.",
            file=sys.stderr,
        )
        return

    with wspolpracownicy_path.open(newline='', encoding='utf-8') as f:
        known_ids = {r['id'].strip() for r in csv.DictReader(f) if (r.get('id') or '').strip()}

    for wid, row_nums in referenced.items():
        if wid not in known_ids:
            for row_num in row_nums:
                errors.append(f"wiersz {row_num}: wspolpracownik_id '{wid}' nie istnieje w data/wspolpracownicy.csv")


def main():
    parser = argparse.ArgumentParser(
        description=__doc__,
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    parser.add_argument(
        '--csv',
        default=str(REPO_ROOT / 'data' / 'miasta.csv'),
        help='Ścieżka do miasta.csv (domyślnie data/miasta.csv w repo)',
    )
    args = parser.parse_args()

    csv_path = Path(args.csv)
    if not csv_path.exists():
        print(f"BŁĄD: plik nie istnieje: {csv_path}", file=sys.stderr)
        sys.exit(1)

    with csv_path.open(newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        header = reader.fieldnames or []
        rows = list(reader)

    errors = []

    missing_cols = [c for c in EXPECTED_COLUMNS if c not in header]
    extra_cols = [c for c in header if c not in EXPECTED_COLUMNS]
    if missing_cols:
        errors.append(f"nagłówek: brak kolumn: {', '.join(missing_cols)}")
    if extra_cols:
        errors.append(f"nagłówek: nieoczekiwane kolumny (literówka?): {', '.join(extra_cols)}")

    if not missing_cols:
        seen_slugs = set()
        for i, row in enumerate(rows, start=2):  # wiersz 1 to nagłówek
            validate_row(row, i, errors, seen_slugs)
        check_wspolpracownicy(rows, errors)

    print(f"Sprawdzono {csv_path}: {len(rows)} wiersz(y) danych.")

    if errors:
        print(f"\nBŁĘDY ({len(errors)}):", file=sys.stderr)
        for e in errors:
            print(f"  - {e}", file=sys.stderr)
        sys.exit(1)

    if rows:
        print("OK — wszystkie wiersze przechodzą walidację.")
    else:
        print("OK — plik zawiera same nagłówki, nic do zwalidowania (dane wypełniane etapami, sekcja 16 pkt 5).")
    sys.exit(0)


if __name__ == '__main__':
    main()
