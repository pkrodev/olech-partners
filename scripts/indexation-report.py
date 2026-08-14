#!/usr/bin/env python3
"""Raport indeksacji między falami — scripts/indexation-report.py
(sekcja 12.1 CLAUDE.md).

Progi kontrolne (tabela z sekcji 12.1):
  - odsetek zaindeksowanych z poprzedniej fali < 60%  -> STOP
  - odsetek zaindeksowanych 60-75%                    -> publikuj, ale
                                                          zmniejsz kolejną
                                                          falę do 50 stron
  - spadek wyświetleń strony głównej lub usług m/m > 20% -> STOP
  - wzrost "Discovered — currently not indexed" > 30% fali -> ostrzeżenie

"Progi są mechanizmem odwracalności. Jeżeli któryś zadziała, raportuj i
czekaj na decyzję — nie kontynuuj automatycznie." — ten skrypt tylko
raportuje i ustawia exit code; NIC nie publikuje ani nie cofa samodzielnie.

DWA TRYBY WEJŚCIA:

1. --input plik.json — dane już zebrane (ręczny eksport z GSC albo wynik
   wcześniejszego --fetch zapisany do pliku). To jest tryb w pełni
   przetestowany w tej sesji (na syntetycznych danych — patrz
   STAN-PROJEKTU.md, bo brak dostępu do prawdziwego Search Console).
   Schemat pliku:
   {
     "fala": 1,
     "opublikowane_poprzednia_fala": 100,
     "zaindeksowane_poprzednia_fala": 55,
     "discovered_not_indexed_fala": 20,
     "wyswietlenia": {
       "strona_glowna": {"poprzedni_okres": 500, "biezacy_okres": 380},
       "uslugi":        {"poprzedni_okres": 300, "biezacy_okres": 250}
     }
   }

2. --fetch --token TOKEN --property URL --wave-urls plik.txt — próba
   pobrania danych bezpośrednio z Google Search Console API (URL
   Inspection API dla statusu indeksacji per URL, Search Analytics API dla
   wyświetleń m/m), surowe wywołania REST przez urllib (bez zależności
   google-api-python-client). WAŻNE: ta ścieżka jest NAPISANA, ale
   NIEPRZETESTOWANA na żywym koncie GSC — dostęp do Search Console czeka
   na klienta (sekcja 17 CLAUDE.md). Traktować jako szkic do weryfikacji,
   gdy dostęp się pojawi, nie jako gotowe, sprawdzone narzędzie.

Użycie:
  python3 scripts/indexation-report.py --input dane-fala-1.json
  python3 scripts/indexation-report.py --fetch --token "$GSC_TOKEN" \
      --property "https://olechpartners.pl/" --wave-urls fala-1-urls.txt \
      --fala 1 --poprzednia-fala-urls fala-0-urls.txt
"""

import argparse
import json
import sys
import urllib.error
import urllib.parse
import urllib.request

STOP_INDEXATION_THRESHOLD = 0.60
WARN_INDEXATION_THRESHOLD = 0.75
IMPRESSIONS_DROP_THRESHOLD = 0.20
DISCOVERED_NOT_INDEXED_THRESHOLD = 0.30


def gsc_request(url: str, token: str, method: str = 'GET', body: dict = None):
    data = json.dumps(body).encode('utf-8') if body is not None else None
    req = urllib.request.Request(
        url,
        data=data,
        method=method,
        headers={
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json',
        },
    )
    with urllib.request.urlopen(req, timeout=30) as resp:
        return json.loads(resp.read().decode('utf-8'))


def fetch_indexation(property_url: str, token: str, urls: list) -> dict:
    """URL Inspection API — per URL, wolne (rate-limited przez Google),
    ale to jedyny publiczny sposób na realny status indeksacji per URL.
    NIEPRZETESTOWANE na żywo (brak dostępu do GSC w tej sesji)."""
    indexed = 0
    discovered_not_indexed = 0
    for url in urls:
        result = gsc_request(
            'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',
            token,
            method='POST',
            body={'inspectionUrl': url, 'siteUrl': property_url},
        )
        verdict = result.get('inspectionResult', {}).get('indexStatusResult', {}).get('verdict', '')
        coverage_state = result.get('inspectionResult', {}).get('indexStatusResult', {}).get('coverageState', '')
        if verdict == 'PASS':
            indexed += 1
        if 'Discovered' in coverage_state and 'not indexed' in coverage_state.lower():
            discovered_not_indexed += 1
    return {'zaindeksowane': indexed, 'opublikowane': len(urls), 'discovered_not_indexed': discovered_not_indexed}


def fetch_impressions(property_url: str, token: str, path_prefix: str, period_days: int = 28) -> dict:
    """Search Analytics API — wyświetlenia dla grupy stron (prefiks ścieżki)
    w dwóch kolejnych okresach. NIEPRZETESTOWANE na żywo."""
    import datetime
    today = datetime.date.today()
    end_a = today - datetime.timedelta(days=1)
    start_a = end_a - datetime.timedelta(days=period_days - 1)
    end_b = start_a - datetime.timedelta(days=1)
    start_b = end_b - datetime.timedelta(days=period_days - 1)

    def query(start, end):
        body = {
            'startDate': str(start),
            'endDate': str(end),
            'dimensions': ['page'],
            'dimensionFilterGroups': [{
                'filters': [{'dimension': 'page', 'operator': 'contains', 'expression': path_prefix}]
            }],
            'rowLimit': 25000,
        }
        result = gsc_request(
            f'https://searchconsole.googleapis.com/webmasters/v3/sites/{urllib.parse.quote(property_url, safe="")}/searchAnalytics/query',
            token, method='POST', body=body,
        )
        return sum(row.get('impressions', 0) for row in result.get('rows', []))

    return {'biezacy_okres': query(start_a, end_a), 'poprzedni_okres': query(start_b, end_b)}


def evaluate(dane: dict) -> tuple:
    """Zwraca (stop: bool, komunikaty: list[str])."""
    komunikaty = []
    stop = False

    opublikowane = dane.get('opublikowane_poprzednia_fala', 0)
    zaindeksowane = dane.get('zaindeksowane_poprzednia_fala', 0)

    if opublikowane > 0:
        rate = zaindeksowane / opublikowane
        komunikaty.append(f"Indeksacja poprzedniej fali: {zaindeksowane}/{opublikowane} = {rate:.1%}")
        if rate < STOP_INDEXATION_THRESHOLD:
            stop = True
            komunikaty.append(
                f"STOP: indeksacja {rate:.1%} < {STOP_INDEXATION_THRESHOLD:.0%}. "
                "Nie publikuj kolejnej fali. Diagnoza."
            )
        elif rate < WARN_INDEXATION_THRESHOLD:
            komunikaty.append(
                f"Indeksacja {rate:.1%} w przedziale {STOP_INDEXATION_THRESHOLD:.0%}-"
                f"{WARN_INDEXATION_THRESHOLD:.0%}: publikuj, ale zmniejsz kolejną falę do 50 stron."
            )
        else:
            komunikaty.append(f"Indeksacja {rate:.1%} >= {WARN_INDEXATION_THRESHOLD:.0%}: kolejna fala bez zmian.")
    else:
        komunikaty.append("Brak danych o poprzedniej fali (opublikowane_poprzednia_fala=0) — pomijam próg indeksacji.")

    for etykieta, klucz in (('strony głównej', 'strona_glowna'), ('stron usług', 'uslugi')):
        wysw = dane.get('wyswietlenia', {}).get(klucz)
        if not wysw:
            continue
        poprzedni = wysw.get('poprzedni_okres', 0)
        biezacy = wysw.get('biezacy_okres', 0)
        if poprzedni <= 0:
            continue
        zmiana = (biezacy - poprzedni) / poprzedni
        komunikaty.append(f"Wyświetlenia {etykieta}: {poprzedni} -> {biezacy} ({zmiana:+.1%} m/m)")
        if zmiana < -IMPRESSIONS_DROP_THRESHOLD:
            stop = True
            komunikaty.append(
                f"STOP: spadek wyświetleń {etykieta} {zmiana:.1%} > "
                f"{IMPRESSIONS_DROP_THRESHOLD:.0%}. Sprawdź kanibalizację i linkowanie."
            )

    discovered = dane.get('discovered_not_indexed_fala', 0)
    if opublikowane > 0 and discovered > 0:
        discovered_pct = discovered / opublikowane
        komunikaty.append(f"Discovered — currently not indexed: {discovered}/{opublikowane} = {discovered_pct:.1%} fali")
        if discovered_pct > DISCOVERED_NOT_INDEXED_THRESHOLD:
            komunikaty.append(
                f"OSTRZEŻENIE: Discovered-not-indexed {discovered_pct:.1%} > "
                f"{DISCOVERED_NOT_INDEXED_THRESHOLD:.0%} fali — popraw linkowanie wewnętrzne."
            )

    return stop, komunikaty


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--input', help='Plik JSON z zebranymi danymi (patrz schemat w opisie modułu)')
    parser.add_argument('--fetch', action='store_true', help='Pobierz dane na żywo z GSC API (NIEPRZETESTOWANE)')
    parser.add_argument('--token', help='Bearer token do GSC API (wymagane z --fetch)')
    parser.add_argument('--property', help='Adres property w GSC, np. https://olechpartners.pl/ (wymagane z --fetch)')
    parser.add_argument('--wave-urls', help='Plik z listą URL bieżącej/poprzedniej fali, jeden na linię (wymagane z --fetch)')
    parser.add_argument('--fala', type=int, help='Numer fali (do raportu)')
    args = parser.parse_args()

    if args.fetch:
        if not (args.token and args.property and args.wave_urls):
            parser.error('--fetch wymaga --token, --property i --wave-urls')
        with open(args.wave_urls, encoding='utf-8') as f:
            urls = [line.strip() for line in f if line.strip()]
        print("UWAGA: tryb --fetch jest niesprawdzony na żywym koncie GSC (brak dostępu w tej sesji, sekcja 17).", file=sys.stderr)
        try:
            idx = fetch_indexation(args.property, args.token, urls)
            impressions_home = fetch_impressions(args.property, args.token, '/')
            impressions_uslugi = fetch_impressions(args.property, args.token, '/uslugi/')
        except (urllib.error.URLError, urllib.error.HTTPError) as e:
            print(f"BŁĄD: nie udało się pobrać danych z GSC API: {e}", file=sys.stderr)
            sys.exit(2)
        dane = {
            'fala': args.fala,
            'opublikowane_poprzednia_fala': idx['opublikowane'],
            'zaindeksowane_poprzednia_fala': idx['zaindeksowane'],
            'discovered_not_indexed_fala': idx['discovered_not_indexed'],
            'wyswietlenia': {'strona_glowna': impressions_home, 'uslugi': impressions_uslugi},
        }
    elif args.input:
        with open(args.input, encoding='utf-8') as f:
            dane = json.load(f)
    else:
        parser.error('Podaj --input plik.json albo --fetch (patrz --help)')

    stop, komunikaty = evaluate(dane)

    fala = dane.get('fala', args.fala or '?')
    print(f"=== Raport indeksacji — fala {fala} ===\n")
    for k in komunikaty:
        print(k)

    print()
    if stop:
        print("WYNIK: STOP — nie publikuj kolejnej fali bez decyzji. Progi są mechanizmem odwracalności, nie kontynuuj automatycznie.", file=sys.stderr)
        sys.exit(1)

    print("WYNIK: brak twardego STOP. Sprawdź powyższe ostrzeżenia przed decyzją o kolejnej fali.")
    sys.exit(0)


if __name__ == '__main__':
    main()
