# Stan projektu — Olech & Partners

Ostatnia aktualizacja: 2026-08-14. Ten plik to migawka stanu prac, nie
dokumentacja — jeśli coś się rozjedzie z kodem, kod ma rację. Referencja
nadrzędna to zawsze `CLAUDE.md`.

## Środowisko (do wznowienia pracy)

- DDEV: `ddev start` w katalogu repo, jeśli kontenery nie działają.
- Adres: **https://olech.ddev.site/**
- Admin WP: `https://olech.ddev.site/wp-admin/` — login `admin` / hasło `admin123`
  (tylko lokalne DDEV, do zmiany).
- WP-CLI dostępny przez `ddev wp ...` (lokalnie w tej sesji: `ddev exec wp ...`).
- **Git: praca z punktu 6 (patrz niżej) nie jest jeszcze scommitowana.**
  Nowe/zmienione ścieżki: `scripts/import-locations.php`, `wp-cli.yml`
  (dopisany require). Punkty 1–5 są już scommitowane (`ccef682`, `a585196`,
  `f907a77`).

## Zrobione — punkty 1–3 z sekcji 16 CLAUDE.md

### Punkt 1: środowisko lokalne + szkielet motywu ✅

- DDEV skonfigurowany (WordPress, PHP 8.3, MariaDB, nginx-fpm), WP-CLI działa.
- Motyw `olech` to działający, minimalny motyw blokowy (FSE):
  `theme.json` (bez systemu kolorów/typografii — świadomie, czeka na branding
  klienta), `templates/index.html`, `parts/header.html`, `parts/footer.html`,
  `functions.php` z podstawowym theme setup i optymalizacjami wydajności
  (bez emoji-skryptów, bez zbędnych meta w `<head>`).
- Zainstalowane pluginy: **ACF (wersja darmowa)**, Rank Math, Redirection.
  **ACF Pro nie jest zainstalowane** — brak licencji klienta (sekcja 17).

### Punkt 2: CPT, taksonomie, pola ACF ✅

Pliki: `wp-content/themes/olech/inc/post-types.php`, `inc/acf-pola.php`,
`inc/ustawienia-firmy.php`.

- CPT: `lokalizacja` (`/obszar-dzialania/`), `usluga` (`/uslugi/`),
  `realizacja` (`/realizacje/`) — dokładnie wg sekcji 6.1.
- Taksonomie: `wojewodztwo`, `powiat` (na `lokalizacja`), `kategoria_uslugi`
  (na `usluga`).
- **Ważna decyzja techniczna**: `/obszar-dzialania/{miasto}/` (CPT) i
  `/obszar-dzialania/{województwo}/` (hub) dzielą ten sam prefiks URL
  (sekcja 5). Kolizję reguł przepisywania rozwiązano jawną regułą dla
  16 znanych slugów województw z priorytetem `top` (patrz
  `olech_rewrite_wojewodztwo()` w `post-types.php`) — wszystko inne pod tym
  prefiksem trafia do CPT. Przetestowane na żywo, działa poprawnie.
- Pola ACF (3 grupy — lokalizacja, usluga, realizacja), zbudowane na
  **darmowym ACF** (bez Repeater/Flexible Content — Pro-only). Nazwy pól
  w grupie „Lokalizacja” są identyczne z kolumnami `data/miasta.csv`
  (ułatwia mapowanie 1:1 w przyszłym imporcie, punkt 6).
- **ACF Options Page okazało się Pro-only** (w darmowej wersji to tylko
  podgląd/upsell — zweryfikowane w kodzie pluginu, nie założone). Dane
  firmowe (nazwa podmiotu, MSWiA, licencja, telefon, adres Radom) są zamiast
  tego natywną stroną ustawień WP: **Ustawienia → Dane firmy**
  (`inc/ustawienia-firmy.php`, funkcja odczytu: `olech_ustawienia_firmy('klucz')`).
  Wszystkie pola są teraz puste — czekają na dane klienta (sekcja 17).

### Punkt 3: szablony ✅

Pełna głębia, wszystkie 6 szablonów z sekcji 16 na raz (decyzja użytkownika).

- **11 własnych bloków serwerowych** w `wp-content/themes/olech/blocks/`
  (zwykłe `register_block_type()` + `render.php`, bez ACF Blocks Pro):
  `hero-lokalizacji`, `pasek-zaufania`, `dane-sadowe`, `lista-gmin`,
  `uslugi-karty`, `sasiednie-miasta`, `faq`, `formularz-kontaktowy`,
  `mapa-radom`, `cena-uslugi`, `cta-sticky`, `powiazane` (generyczny,
  do relacji ACF), `lokalizacje-wojewodztwa`.
- **7 szablonów** w `templates/`: `front-page`, `single-lokalizacja`
  (12 z 13 sekcji z 8.1 — „Realizacja z regionu” pominięta, bo case studies
  jeszcze nie istnieją, sekcja 17), `single-usluga` (8.2), `archive-usluga`
  (hub `/uslugi/`), `taxonomy-wojewodztwo` (hub wojewódzki), `home` (blog
  na `/poradnik/`), `single` (artykuł poradnika), `page-kontakt`.
- Wzorzec `patterns/jak-pracujemy.php` — 7 kroków procesu, wspólny dla
  wszystkich stron miast, edytowany w jednym miejscu.
- Natywny formularz kontaktowy (`inc/formularz.php`): nonce + honeypot +
  `wp_mail()`, przekierowanie na `/kontakt/dziekujemy/` — bez pluginu.
- Skonfigurowane strony i ustawienia WP: „Strona główna” (front page),
  „Poradnik” (posts page, `/poradnik/`), „Kontakt”, „Dziękujemy”
  (dziecko Kontaktu → `/kontakt/dziekujemy/`), permalink structure
  `/poradnik/%postname%/`.
- **Odkrycie techniczne**: darmowy ACF wspiera Block Bindings API
  (`acf/field`, po kluczu pola) — zweryfikowane na żywo, działa. Finalnie
  większość dynamicznej treści i tak oparta o własne bloki PHP, bo sekcja
  8.1 wymaga wstawiania odmienionej nazwy miasta w środek zdań, czego
  bindings nie robi (podmieniają całą zawartość bloku, nie fragment tekstu).
- **FAQ czyta się z `data/faq/*.json`, nie `.yml`** — PHP/WP nie ma
  wbudowanego parsera YAML, a CLAUDE.md nigdzie nie narzuca formatu (sekcja 4
  mówi tylko „FAQ per usługa i szablon miejski”, bez rozszerzenia pliku).
  Konwencja nazw: `data/faq/usluga-{slug}.json`,
  `data/faq/miasto-tier-{1|2|3}.json`. **Katalog `data/faq/` jest pusty** —
  blok FAQ renderuje wtedy `{{LOREM: ...}}` z nazwą brakującego pliku.
- Wszystko zweryfikowane end-to-end na żywo: testowe posty `lokalizacja` +
  `usluga` + wpis poradnika, wszystkie 9 typów URL zwracały HTTP 200, zero
  błędów PHP, dane ACF poprawnie renderowały się w blokach, formularz
  poprawnie broni się przed brakiem nonce (403) i botami (honeypot cichy
  redirect), sticky CTA poprawnie chowa się przy braku danych firmowych.
  Dane testowe posprzątane po weryfikacji — baza jest czysta.

## Zrobione — punkt 4 z sekcji 16 CLAUDE.md

`data/uslugi.csv` + treści usług → import ✅

- **5 usług jako CPT `usluga`** (nie 7 z tabeli cennika sekcji 9 — decyzja
  z użytkownikiem): Badanie wariografem, Obserwacja, Ustalenie miejsca
  pobytu, Wywiad personalny, Wykrywanie podsłuchów. „Konsultacja" i „Dojazd"
  zostają wierszami przyszłego `/cennik/`, nie mają własnego landing page'a.
- `data/uslugi.csv` — źródło prawdy: `slug`, `nazwa`, `kategoria_uslugi`,
  `cena_od`, `cena_do`, `jednostka_ceny`, `menu_order`, `uslugi_powiazane`
  (slugi rozdzielone `|`), `excerpt`. Puste `cena_*` dla wszystkich poza
  wariografem (2000/2000/zl) — blok `cena-uslugi` sam renderuje wtedy
  `{{LOREM: zakres cenowy tej usługi}}`, nie trzeba tego wpisywać ręcznie.
- **Taksonomia `kategoria_uslugi`** — 3 terminy utworzone przez importer:
  „Sprawy rodzinne i osobiste", „Badania i technika", „Obserwacja".
- **Treść** — `content/uslugi/{slug}.md`, 5 plików, 1200+ słów każdy
  (struktura z sekcji 8.2: Problem klienta / Jak działamy / Przebieg
  współpracy / Wartość dowodowa), zwykły Markdown bez front matter.
  `{{LOREM}}` wyłącznie dla: modelu/producenta sprzętu (wariograf,
  wykrywanie podsłuchów), numerów licencji, zakresów cenowych 4 usług
  poza wariografem — zero zmyślonych faktów/opinii/statystyk (audyt
  `grep {{LOREM` przeszedł czysto, bez przypadkowych trafień).
- **FAQ** — `data/faq/usluga-{slug}.json`, 5 plików po 6 pytań, pisane
  ręcznie (schemat `{"pytanie","odpowiedz"}`). Każde „Ile kosztuje…" ma
  albo realną cenę (wariograf: 2000 zł), albo jawny `{{LOREM: zakres}}`
  — nigdy samo „wyceniamy indywidualnie" (zakaz z sekcji 9).
- **Importer** — `scripts/import-uslugi.php`, komenda `wp olech import-uslugi`
  (`--dry-run`, `--csv=`, `--only=`, `--user=`), zarejestrowana przez
  `wp-cli.yml` (`require:`), **nie** przez `functions.php` — importer nie
  ładuje się przy requestach frontowych. Idempotentny: dopasowanie po
  `post_name`+`post_type`, `wp_set_object_terms` z replace, pola ACF zawsze
  nadpisywane z CSV. Wymusza zgodność zapisanego `post_name` z CSV i
  przerywa błędem przy kolizji sluga — krytyczne, bo blok FAQ buduje ścieżkę
  pliku z `post_name` w momencie renderu.
- **Odkrycie techniczne (ważne dla punktu 6)**: pliki z `wp-cli.yml`
  `require:` ładują się **przed** pełnym bootstrapem WP — `ABSPATH` jeszcze
  nie istnieje na tym etapie. Guard `defined('ABSPATH') || exit;` na
  początku takiego pliku cicho ubija **cały** proces WP-CLI dla każdej
  komendy (nie tylko własnej) bez żadnego komunikatu błędu. Jedyny
  potrzebny guard to `defined('WP_CLI') && WP_CLI`. Ten sam wzorzec
  (`require:` w `wp-cli.yml`, bez ABSPATH-guard) obowiązuje przy pisaniu
  `scripts/import-locations.php` w punkcie 6.
- Zweryfikowane end-to-end na żywo: import (insert + re-run jako update,
  bez duplikatów — 5 postów, 3 terminy po obu przebiegach), wszystkie
  5 `/uslugi/{slug}/` i `/uslugi/` zwracają HTTP 200 bez ręcznego
  `rewrite flush`, cena wariografu renderuje się jako „2 000 zł", pozostałe
  4 usługi poprawnie pokazują LOREM, FAQ (6 pozycji + schema `FAQPage`)
  renderuje się poprawnie, hub `/uslugi/` pokazuje dokładnie 5 kart
  w kolejności `menu_order`, relacje `uslugi_powiazane` zapisane poprawnie
  (drugi przebieg importera, po tym jak wszystkie posty już istnieją).

## Zrobione — punkt 5 z sekcji 16 CLAUDE.md

`data/miasta.csv` — struktura i walidator (dane wypełniane etapami) ✅

- **`data/miasta.csv`** — wyłącznie nagłówek (15 kolumn dokładnie wg
  sekcji 6.2 CLAUDE.md), zero wierszy danych. Świadoma decyzja z
  użytkownikiem: dane realne (TERYT/GUS, BIP sądów) dla miast fali 1 to
  osobny etap/sesja — sekcja 6.2 zabrania zgadywania („Nie zgaduj — jeśli
  nie ma danych, wiersz czeka”), a research per miasto to odrębny nakład
  pracy wymagający weryfikacji źródeł.
- **`scripts/validate-miasta.py`** — samodzielny skrypt Python (bez
  zależności od WP, spójny z konwencją `dedup-gate.py`/`indexation-report.py`
  z sekcji 4), do uruchamiania przed każdym importem (analogicznie do
  `dedup-gate.py`, sekcja 11). Sprawdza: nagłówek (brakujące/nieoczekiwane
  kolumny), unikalność i format sluga, wymagane pola tekstowe, `wojewodztwo`
  względem listy 16 slugów z `olech_rewrite_wojewodztwo()`
  (`inc/post-types.php`) — żeby literówka nie psuła huba wojewódzkiego,
  `ludnosc`/`fala`/`tier` jako liczby w poprawnych zakresach, komplet pól
  `unikalne_*`, min. 3 gminy w `unikalne_gminy` (separator `|`, ta sama
  konwencja co `uslugi_powiazane` w `data/uslugi.csv`), oraz opcjonalnie
  referencje `wspolpracownik_id` → `data/wspolpracownicy.csv` (jeśli ten
  plik już istnieje — na razie nie istnieje, więc tylko ostrzeżenie, nie błąd).
  Exit code 0/1, błędy na stderr — nadaje się jako bramka w CI/przed importem.
- **Zamysłowa decyzja udokumentowana w kodzie**: sekcja 6.2 CLAUDE.md mówi
  ogólnie „wiersz bez kompletu pól unikalne_* nie przechodzi importu”, ale
  sekcja 7 wprost dopuszcza tier 3 z „minimum 3 dane unikalne”. Walidator
  wymaga wszystkich 5 pól `unikalne_*` dla tier 1/2, a dla tier 3 — minimum
  3 z 5. To synteza obu fragmentów, nie dosłowny cytat żadnego — jeśli
  odczytanie jest błędne, do poprawienia (patrz docstring modułu).
- Zweryfikowany na testowych danych (nie w repo): łapie literówkę w
  województwie, niekompletne `unikalne_*` wg reguły tier, duplikat sluga,
  brak sluga, złą liczbę gmin, zły zakres `tier`/`fala`, zły/nieznany
  nagłówek, brakujące i nieistniejące `wspolpracownik_id`. Pojedynczy
  poprawny wiersz i plik z samym nagłówkiem przechodzą czysto (exit 0).
- `.gitignore` — dodano `__pycache__/` i `*.pyc` (nie było wpisu, artefakty
  Pythona pojawiły się dopiero przy pisaniu tego skryptu).

## Zrobione — punkt 6 z sekcji 16 CLAUDE.md

Importer WP-CLI, idempotentny ✅

- **`scripts/import-locations.php`** — komenda `wp olech import-locations`
  (`--dry-run`, `--csv=`, `--validator=`, `--skip-validate`, `--only=`,
  `--fala=`, `--publish`, `--user=`), zarejestrowana przez `wp-cli.yml`
  (drugi wpis obok `import-uslugi.php`). Przed importem automatycznie
  uruchamia `scripts/validate-miasta.py` (shell_exec) i przerywa import,
  jeśli walidacja nie przejdzie — nie trzeba pamiętać o ręcznym uruchomieniu
  bramki osobno, choć da się to pominąć świadomie przez `--skip-validate`.
- **Ważna decyzja projektowa: draft domyślnie, nie publish.** W
  przeciwieństwie do `import-uslugi.php` (usługi = fala 0, publikacja od
  razu), lokalizacje podlegają ścisłej kontroli falami z twardymi progami
  STOP (sekcja 12.1 CLAUDE.md — „nie kontynuuj automatycznie”). Import bez
  `--publish` zapisuje jako `draft`; `--publish` jawnie przełącza na
  `publish`. Re-import bez `--publish` **nie cofa** już opublikowanej
  strony do draftu (status nadpisywany tylko przy tworzeniu nowego posta
  albo gdy `--publish` podano jawnie) — odwracalny, bezpieczny domyślny
  wybór.
- Mapowanie CSV → ACF identyczne z nazwami pól w `inc/acf-pola.php`
  (zgodnie z komentarzem w tym pliku o mapowaniu 1:1). `unikalne_gminy`
  konwertowane z konwencji CSV (`|`-separated) na format oczekiwany przez
  pole ACF textarea (jedna gmina na linię). Taksonomie `wojewodztwo`
  (slug wymuszony zgodnie z listą w `olech_rewrite_wojewodztwo()`) i
  `powiat` tworzone on-demand, tak jak `kategoria_uslugi` w punkcie 4.
- Zweryfikowane end-to-end na syntetycznym, testowym wierszu (nie w repo,
  posprzątane po teście — usunięty testowy post, taksonomie
  „Mazowieckie”/„radomski” zostawione, bo to realne, wielokrotnego użytku
  wartości administracyjne, nie fikcja): bramka walidacji blokuje/przepuszcza
  poprawnie, insert → draft, re-import bez `--publish` zostaje draftem,
  `--publish` przełącza na publish, kolejny re-import nie cofa statusu,
  wszystkie pola ACF i obie taksonomie zapisane poprawnie, URL
  `/obszar-dzialania/{slug}/` zwraca HTTP 200, liczba postów po 3
  przebiegach: nadal 1 (idempotencja potwierdzona).
- Uruchomienie na obecnym `data/miasta.csv` (sam nagłówek, punkt 5) kończy
  się poprawnie komunikatem „nic do zaimportowania” — importer gotowy,
  czeka na dane.

## Świadomie NIE zrobione / odłożone

- **ACF Pro** — brak licencji klienta. Gdy dojdzie: zamiana pól textarea
  (`unikalne_gminy`) na Repeater, bez utraty danych.
- **Kolory, typografia, fonty** — czeka na branding klienta.
- **Cennik, O firmie, Daniel Olech, Realizacje** — te szablony nie były
  w zakresie punktu 3 (sekcja 16 wymienia explicite tylko: strona główna,
  usługa, lokalizacja, hub wojewódzki, poradnik, kontakt).
- **Sekcja „Realizacja z regionu”** na stronie miasta (tier 1) — pominięta,
  bo case studies nie istnieją (czeka na 8–12 realnych spraw od klienta,
  sekcja 17).
- **Treść FAQ dla lokalizacji** — pliki `data/faq/miasto-tier-{1,2,3}.json`
  nie istnieją (te dla usług już są, patrz punkt 4 wyżej), czekają na
  punkt 5/6.
- **Dane firmowe** (MSWiA, licencja, telefon, adres) — puste w
  Ustawienia → Dane firmy, czekają na dane klienta.
- **`/cennik/`** — szablon i treść nie istnieją; „Konsultacja" i „Dojazd"
  z sekcji 9 mają tam trafić jako wiersze, nie osobne CPT `usluga`.
- **`poradniki_powiazane`** na wszystkich 5 usługach — puste, bo CPT/wpisy
  poradnika jeszcze nie istnieją (nie fabrykowaliśmy powiązań).
- **Dane w `data/miasta.csv`** — plik ma tylko nagłówek, zero miast (patrz
  punkt 5 wyżej). Wypełnianie realnymi danymi (TERYT/GUS, BIP sądów) to
  osobny etap, per fala z sekcji 12, zaczynając od fali 1 (Pionki, Kozienice,
  Zwoleń, Białobrzegi, Szydłowiec, Przysucha, Iłża, Skaryszew, Warka, Lipsko).
- **`data/wspolpracownicy.csv`** — nie istnieje, czeka na dane klienta
  (sekcja 17). Walidator `validate-miasta.py` obsługuje ten brak (ostrzega,
  nie blokuje).

## Następne kroki wg kolejności z sekcji 16 CLAUDE.md

7. `dedup-gate.py`
8. Schema, sitemapy, linkowanie wewnętrzne, breadcrumbs
9. Wydajność i Core Web Vitals
10. Mapa 301 z baseline + test przekierowań — **zablokowane**: wymaga
    `seo/baseline/` (eksport GSC 16 mies., crawl starego
    olechpartners.com, sekcja 13), do którego nie mamy dostępu w tej sesji.
11. `indexation-report.py` — da się zbudować jako narzędzie, ale bez
    realnego dostępu do GSC API nie da się przetestować na żywo.
12. Deploy + GSC — **zablokowane**: wymaga realnych dostępów (hosting,
    domena, GSC) z sekcji 17.

Pamiętać: „Osobna sesja na każdy punkt. Nie łącz.” (sekcja 16 CLAUDE.md).

## Otwarte pytania / czeka na klienta (pełna lista: sekcja 17 CLAUDE.md)

Najbardziej blokujące na najbliższe punkty: dane współpracowników
(miasto → osoba → licencja), cennik (zakresy poza wariografem — teraz też
konkretnie w treściach i FAQ 4 z 5 usług jako `{{LOREM}}`), polisa OC,
case studies, opinie/dostęp do GBP, dostępy (domena, hosting, GSC),
model i producent wariografu oraz sprzętu do wykrywania podsłuchów,
numery licencji Daniela Olecha / detektywów prowadzących badania.
