#!/usr/bin/env python3
"""Walidator "braku ślepych stron" — sekcja 8.3 CLAUDE.md.

"Każda opublikowana strona linkuje do minimum: 2 usług, 1 artykułu
poradnika, 1 innej lokalizacji, strony kontaktu."

Sprawdza już OPUBLIKOWANE strony na żywym adresie (domyślnie DDEV), nie
pliki źródłowe przed importem — w tym motywie większość linków wewnętrznych
pochodzi z bloków renderowanych dynamicznie w zależności od tego, co
akurat istnieje w bazie (uslugi-karty, powiazane, sasiednie-miasta,
lokalizacje-przyklad), a nie z samej treści .md, więc realny policzalny
wynik istnieje dopiero po wyrenderowaniu strony.

Pobiera listę opublikowanych usług/lokalizacji/artykułów przez WP REST API
(CPT-y mają `show_in_rest => true`), dla każdej strony pobiera HTML i liczy
unikalne linki wewnętrzne w czterech kategoriach: /uslugi/, /poradnik/,
/obszar-dzialania/, /kontakt/.

Użycie:
  python3 scripts/check-internal-links.py --base-url https://olech.ddev.site
"""

import argparse
import json
import re
import sys
import urllib.error
import urllib.request

REQUIRED = {
    'uslugi': 2,
    'poradnik': 1,
    'lokalizacje': 1,
    'kontakt': 1,
}

HREF_RE = re.compile(r'href="([^"]+)"')


def fetch(url: str) -> str:
    req = urllib.request.Request(url, headers={'User-Agent': 'olech-check-internal-links/1.0'})
    with urllib.request.urlopen(req, timeout=15) as resp:
        return resp.read().decode('utf-8', errors='replace')


def fetch_json(url: str):
    return json.loads(fetch(url))


def collect_permalinks(base_url: str):
    """Zwraca listę (typ, permalink) dla wszystkich opublikowanych usług,
    lokalizacji i artykułów poradnika, przez REST API."""
    items = []
    endpoints = {
        'usluga': f'{base_url}/wp-json/wp/v2/usluga?per_page=100&_fields=link',
        'lokalizacja': f'{base_url}/wp-json/wp/v2/lokalizacja?per_page=100&_fields=link',
        'poradnik': f'{base_url}/wp-json/wp/v2/posts?per_page=100&_fields=link',
    }
    for typ, url in endpoints.items():
        try:
            data = fetch_json(url)
        except urllib.error.HTTPError as e:
            if e.code == 404:
                continue
            raise
        for item in data:
            items.append((typ, item['link']))
    return items


def classify_links(html: str, base_url: str) -> dict:
    counts = {'uslugi': set(), 'poradnik': set(), 'lokalizacje': set(), 'kontakt': set()}
    for href in HREF_RE.findall(html):
        path = href
        if href.startswith(base_url):
            path = href[len(base_url):]
        if not path.startswith('/'):
            continue
        if path.startswith('/uslugi/') and path != '/uslugi/':
            counts['uslugi'].add(path)
        elif path.startswith('/poradnik/') and path != '/poradnik/':
            counts['poradnik'].add(path)
        elif path.startswith('/obszar-dzialania/'):
            counts['lokalizacje'].add(path)
        elif path.rstrip('/').endswith('/kontakt'):
            counts['kontakt'].add(path)
    return counts


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--base-url', default='https://olech.ddev.site', help='Adres bazowy serwisu (bez / na końcu)')
    args = parser.parse_args()
    base_url = args.base_url.rstrip('/')

    try:
        items = collect_permalinks(base_url)
    except (urllib.error.URLError, urllib.error.HTTPError) as e:
        print(f"BŁĄD: nie udało się pobrać listy stron z {base_url}: {e}", file=sys.stderr)
        sys.exit(2)

    if not items:
        print("Brak opublikowanych usług/lokalizacji/artykułów do sprawdzenia.")
        sys.exit(0)

    failures = []
    for typ, url in items:
        try:
            html = fetch(url)
        except (urllib.error.URLError, urllib.error.HTTPError) as e:
            failures.append(f"{url}: nie udało się pobrać strony ({e})")
            continue

        counts = classify_links(html, base_url)
        missing = []
        for kategoria, minimum in REQUIRED.items():
            if len(counts[kategoria]) < minimum:
                missing.append(f"{kategoria} ({len(counts[kategoria])}/{minimum})")
        if missing:
            failures.append(f"[{typ}] {url}: brak minimum — {', '.join(missing)}")
        else:
            print(f"OK  [{typ}] {url}")

    print(f"\nSprawdzono {len(items)} stron(y).")

    if failures:
        print(f"\nBRAKI ({len(failures)}):", file=sys.stderr)
        for f in failures:
            print(f"  - {f}", file=sys.stderr)
        print(
            "\nUWAGA: braki w kategoriach 'poradnik'/'lokalizacje' są obecnie "
            "OCZEKIWANE na całym serwisie — nie istnieją jeszcze żadne "
            "opublikowane artykuły poradnika ani lokalizacje (patrz "
            "STAN-PROJEKTU.md). To realny, udokumentowany stan, nie błąd "
            "tego skryptu.",
            file=sys.stderr,
        )
        sys.exit(1)

    print("OK — wszystkie strony spełniają minima linkowania wewnętrznego (sekcja 8.3).")
    sys.exit(0)


if __name__ == '__main__':
    main()
