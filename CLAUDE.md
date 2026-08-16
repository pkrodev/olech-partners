# CLAUDE.md — Olech & Partners

Specyfikacja projektu. Czytaj w całości przed pierwszą zmianą w kodzie.
Ten plik jest nadrzędny wobec pojedynczych poleceń w czacie. Jeżeli polecenie
stoi w sprzeczności z sekcją „Reguły twarde" — zatrzymaj się i zapytaj.

---

## 1. Czym jest ten projekt

Serwis WordPress dla firmy detektywistycznej. Docelowo ok. **1000 podstron
lokalizacyjnych** plus dział usług, poradnik i strony zaufania.

Baza operacyjna: **Radom**. Sprawy prowadzone w całej Polsce przez sieć
licencjonowanych współpracowników.

Cele w kolejności:
1. Zapytania z fraz wariografowych (najkrótszy zwrot, najcieńsza konkurencja)
2. Widoczność lokalna w Radomiu i okolicy
3. Długi ogon lokalizacyjny w mniejszych ośrodkach
4. Marka eksperta — Daniel Olech

---

## 2. Reguły twarde

Naruszenie którejkolwiek z nich unieważnia deliverable. Nie ma wyjątków
„na potrzeby przykładu", „do wypełnienia później" ani „bo tak jest u konkurencji".

### 2.1 Integralność treści

- **Nie generuj opinii, referencji ani liczb społecznego dowodu.** Żadnych
  „Anna K., Warszawa", żadnych „ponad 800 spraw", żadnych ocen gwiazdkowych.
  Miejsce na opinie zostaje puste z placeholderem.
- **Nie wymyślaj danych faktograficznych**: numerów licencji, polis, nazw
  laboratoriów, dat wystąpień medialnych, nazw sprzętu. Brak danych = placeholder.
- **Nie kopiuj treści z pozaroszczyk.pl, stegienko.pl, detektywbernadetta.pl**
  ani żadnego innego serwisu. Z materiałów konkurencji korzystamy wyłącznie jako
  z listy tematów i pytań.
- **Nie używaj sformułowań** „100% skuteczności", „nr 1", „gwarantujemy wynik",
  „najlepsi w Polsce".

### 2.2 Placeholdery

Wszystko, czego nie wiemy, oznaczamy jednolicie, żeby dało się to wygrepować:

```
{{LOREM: krótki opis czego brakuje}}
```

Przykłady:
```
{{LOREM: numer polisy OC i nazwa ubezpieczyciela}}
{{LOREM: 3 opinie klientów z Google Business Profile}}
{{LOREM: model i producent wariografu}}
```

Skrypt `scripts/check-placeholders.sh` listuje wszystkie wystąpienia.
Przed publikacją każdej fali placeholdery w publikowanych stronach muszą być zerowe.

### 2.3 Zgodność prawna

- Usługi detektywistyczne w PL wykonuje wyłącznie osoba z licencją. Jeśli
  strona miasta wskazuje prowadzącego, musi podać numer jego licencji.
- „Mediacje sądowe" tylko przy wpisie na listę stałych mediatorów SO. W innym
  wypadku: „mediacje i negocjacje".
- „Poradnictwo prawne" formułujemy jako współpracę z kancelariami, nie jako
  świadczenie pomocy prawnej.
- Nazwa podmiotu w stopce, danych strukturalnych i kontakcie musi być tym
  podmiotem, który ma wpis do rejestru MSWiA.

---

## 3. Stos techniczny

| Element | Wybór | Uzasadnienie |
|---|---|---|
| CMS | WordPress (najnowszy stabilny) | wymóg klienta |
| Motyw | custom, blokowy, bez frameworka | 1000 stron — liczy się każdy ms |
| Page builder | **ZAKAZANY** (Elementor, WPBakery, Divi) | wydajność i generowanie z CSV |
| Pola | ACF Pro | |
| SEO | Rank Math albo SEOPress | lżejsze od Yoasta przy dużej liczbie wpisów |
| Cache | obiektowy Redis + cache stron | |
| Import | WP-CLI, własna komenda | |
| Środowisko lokalne | DDEV albo wp-env | Claude Code musi móc uruchamiać WP-CLI |
| Wersjonowanie | git; motyw i skrypty w repo, uploads poza | |

Zakaz: `localStorage`/`sessionStorage` w komponentach frontowych, zapytania
`meta_query` w pętli, `WP_Query` bez `posts_per_page`, ładowanie Google Fonts
z CDN (self-host).

---

## 4. Struktura repozytorium

```
/theme/olech/              motyw
/data/miasta.csv           źródło prawdy — lokalizacje
/data/uslugi.csv           źródło prawdy — usługi
/data/wspolpracownicy.csv  miasto → osoba → licencja
/data/faq/                 FAQ per usługa i szablon miejski
/content/                  teksty długie (md), importowane do WP
/scripts/
  import-locations.php     komenda WP-CLI
  dedup-gate.py            bramka antyduplikatowa
  check-placeholders.sh
  indexation-report.py     raport z GSC API
/seo/
  baseline/                eksport przed migracją
  redirects.csv            mapa 301
CLAUDE.md
```

---

## 5. Architektura URL

Ustalona raz. Nie zmieniamy po pierwszej publikacji.

```
/                                    strona główna (Radom + marka)
/uslugi/                             hub usług
/uslugi/{usluga}/                    landing page usługi
/obszar-dzialania/                   hub lokalizacji + wyjaśnienie modelu pracy
/obszar-dzialania/{wojewodztwo}/     hub wojewódzki
/obszar-dzialania/{miasto}/          podstrona miasta
/o-firmie/                           firma, licencje, wpis MSWiA
/daniel-olech/                       strona eksperta
/poradnik/                           blog
/poradnik/{slug}/
/realizacje/                         case studies (placeholder do czasu danych)
/kontakt/
/cennik/
```

Radom **nie ma** własnej podstrony — obsługuje go strona główna.
Kanoniczna forma: `https://` bez `www`.

---

## 6. Model danych

### 6.1 CPT i taksonomie

```
CPT: lokalizacja       → /obszar-dzialania/{slug}/
CPT: usluga            → /uslugi/{slug}/
CPT: realizacja        → /realizacje/{slug}/
tax: wojewodztwo       (hierarchiczna, przypisana do lokalizacja)
tax: powiat            (hierarchiczna, przypisana do lokalizacja)
tax: kategoria_uslugi  (przypisana do usluga)
```

### 6.2 Schemat `data/miasta.csv`

Kolumny obowiązkowe. Wiersz bez kompletu pól `unikalne_*` **nie przechodzi importu**.

| Kolumna | Opis |
|---|---|
| `slug` | np. `piaseczno` |
| `nazwa` | Piaseczno |
| `nazwa_miejscownik` | w Piasecznie |
| `nazwa_dopelniacz` | Piaseczna |
| `wojewodztwo` | mazowieckie |
| `powiat` | piaseczyński |
| `ludnosc` | 48 000 |
| `tier` | 1 / 2 / 3 |
| `unikalne_sad_okregowy` | Sąd Okręgowy w Warszawie |
| `unikalne_sad_rejonowy` | Sąd Rejonowy w Piasecznie, ul. ..., wydział rodzinny |
| `unikalne_gminy` | lista obsługiwanych gmin, min. 3 |
| `unikalne_dystans_km` | odległość od Radomia |
| `unikalne_czas_dojazdu` | realny czas w minutach |
| `wspolpracownik_id` | odniesienie do `wspolpracownicy.csv` albo puste |
| `fala` | numer fali publikacji 1–10 |

Źródła danych publicznych: TERYT/GUS (ludność, powiaty), BIP sądów
(właściwość miejscowa). Nie zgaduj — jeśli nie ma danych, wiersz czeka.

### 6.3 Dobre sformułowania o modelu obsługi

> Bazę operacyjną mamy w Radomiu oraz każdym mieście wojewódzkim. Sprawy w {MIEŚCIE} prowadzimy dojazdowo — start działań zwykle w ciągu 24 h od potwierdzenia zlecenia.


---

## 7. Warstwy jakości wewnątrz 1000 stron

Liczba stron jest ustalona. Głębokość treści — nie. Rozkład:

| Tier | Ile | Długość | Zawartość |
|---|---|---|---|
| 1 | ~25 | 900–1500 słów | pełna: sądy, powiaty, dojazd, FAQ 6+, case study, sekcja wariograf, opinie |
| 2 | ~200 | 500–700 słów | sądy, gminy, dojazd, FAQ 6, odesłania do usług |
| 3 | ~775 | 300–450 słów | sądy, powiat, dojazd, FAQ 4, odesłania |

Nawet tier 3 musi zawierać **minimum 3 dane unikalne** z kolumn `unikalne_*`.
To jest twardy warunek importu, nie zalecenie.

---

## 8. Szablony stron

### 8.1 Podstrona miasta

Kolejność sekcji:

1. H1: `Detektyw {MIASTO} — {wyróżnik}` + lead + CTA (telefon + formularz)
2. Pasek zaufania: licencja, wpis MSWiA, tajemnica zawodowa
3. H2: Zakres obsługi {MIASTA} — model pracy wg sekcji 6.3
4. H2: Usługi — karty z linkami do `/uslugi/{usluga}/`
5. H2: Sądy właściwe dla {MIASTA} — dane z `unikalne_sad_*`
6. H2: Jak pracujemy — 7 kroków procesu
7. H2: Obszar obsługi — lista gmin z `unikalne_gminy`
8. H2: Badania wariografem dla mieszkańców {MIASTA} — krótka sekcja + link
9. H2: FAQ — wg szablonu z `data/faq/miasto.yml`
10. Realizacja z regionu (tier 1) albo pominięte
11. Opinie — `{{LOREM}}` do czasu danych
12. H2: Sąsiednie miasta — 4–6 linków z tego samego powiatu/województwa
13. Formularz + CTA

CTA musi wystąpić minimum 3 razy: nad foldem, w połowie, na końcu.
Plus sticky pasek mobilny (telefon + WhatsApp).

### 8.2 Landing page usługi

H1 → problem klienta → jak działamy → przebieg współpracy → wartość dowodowa →
zakres cenowy → FAQ własne dla tej usługi → opinie `{{LOREM}}` → usługi
powiązane → poradniki powiązane → formularz.

Minimum 1200 słów. Każda usługa ma **własne** FAQ, nie współdzielone.

### 8.3 Zasada braku ślepych stron

Każda opublikowana strona linkuje do minimum: 2 usług, 1 artykułu poradnika,
1 innej lokalizacji, strony kontaktu. Skrypt walidujący sprawdza to przed importem.

---

## 9. Cennik

Klient zdecydował: **publikujemy zakresy, nie sztywne stawki.**

**Zmiana (2026-08-14, po rozmowie właściciela z klientem):** badanie
wariografem **nie** ma już ceny podanej wprost. Cena jest znana (2000 zł)
i wewnętrznie ustalona, ale świadomie nie publikujemy jej na stronie —
CTA kieruje do kontaktu telefonicznego. To świadomy wyjątek od zasady
„sama fraza „wyceniamy indywidualnie" nie odpowiada na pytanie i nie
zarankuje" (niżej) — dotyczy tylko wariografu, nie pozostałych usług.

```
Badanie wariografem            {{LOREM: nieujawniana publicznie — CTA kontaktowe, nie liczba/zakres}}
Obserwacja                     {{LOREM: zakres zł/h + minimum godzin}}
Ustalenie miejsca pobytu       {{LOREM: zakres}}
Wywiad personalny              {{LOREM: zakres}}
Wykrywanie podsłuchów          {{LOREM: zakres}}
Windykacja                     {{LOREM: zakres — % odzyskanej kwoty czy stawka ryczałtowa}}
Konsultacja                    {{LOREM: bezpłatna czy płatna, ile trwa}}
Dojazd                         {{LOREM: zasada rozliczania}}
```

Każde FAQ „Ile kosztuje detektyw w {MIEŚCIE}?" musi zawierać liczbę albo zakres.
Sama fraza „wyceniamy indywidualnie" nie odpowiada na pytanie i nie zarankuje.
Wyjątek: FAQ „Ile kosztuje badanie wariografem?" — tu jawnie *nie* podajemy
liczby (patrz wyżej), odpowiedź kieruje do kontaktu telefonicznego.

---

## 10. Dane strukturalne

| Strona | Schema |
|---|---|
| Strona główna | `Organization` + `LocalBusiness` **wyłącznie z adresem w Radomiu** |
| `/daniel-olech/` | `Person` z `hasCredential` |
| `/uslugi/{x}/` | `Service` + `FAQPage` |
| `/obszar-dzialania/{miasto}/` | `Service` z `areaServed` + `FAQPage` + `BreadcrumbList` |
| `/poradnik/{x}/` | `Article` z `author` → Person |
| Opinie | `Review`/`AggregateRating` **tylko z realnych opinii GBP** |

**Zakazane:** `LocalBusiness` z adresem lub telefonem innym niż Radom na
podstronach miast. To najczęściej wychwytywane naruszenie i najprostsze do zgłoszenia.

Breadcrumbs na każdej stronie, także wizualnie.

---

## 11. Bramka antyduplikatowa

`scripts/dedup-gate.py` uruchamiany **przed każdym importem**, obowiązkowo.

Działanie:
- liczy podobieństwo shingle (n=5) między każdą nową stroną a wszystkimi istniejącymi
- próg twardy: **65%** — powyżej import się zatrzymuje i zwraca listę kolizji
- próg ostrzegawczy: 50% — import przechodzi, ale loguje
- raport do `reports/dedup-fala-{N}.txt`

Import bez przejścia bramki jest błędem krytycznym. Nie obchodź jej flagą.

---

## 12. Publikacja falami

Klient zdecydował: **100 stron tygodniowo, 10 fal.**

Kolejność fal — nie według wielkości miasta, tylko według szansy na wejście:

| Fala | Zakres |
|---|---|
| 0 | Fundament: strona główna, usługi, o firmie, kontakt, cennik, 5 poradników. **Bez lokalizacji.** |
| 1 | Okolice Radomia — Pionki, Kozienice, Zwoleń, Białobrzegi, Szydłowiec, Przysucha, Iłża, Skaryszew, Warka, Lipsko + reszta powiatów ościennych |
| 2–3 | Miasta satelickie wokół Warszawy, Lublina, Kielc, Łodzi |
| 4–7 | Miasta powiatowe w całym kraju |
| 8–9 | Pozostałe miejscowości z listy |
| 10 | Stolice województw — **na końcu**, po zbudowaniu kontekstu regionalnego |

Fala 0 musi być w indeksie i mieć pierwsze wyświetlenia w GSC, zanim ruszy fala 1.

### 12.1 Progi kontrolne między falami

`scripts/indexation-report.py` po każdej fali, na danych z GSC API:

| Wskaźnik | Próg | Reakcja |
|---|---|---|
| Odsetek zaindeksowanych z poprzedniej fali | < 60% | **STOP.** Nie publikuj kolejnej fali. Diagnoza. |
| Odsetek zaindeksowanych | 60–75% | Publikuj, ale zmniejsz falę do 50 stron |
| Spadek wyświetleń strony głównej lub stron usług m/m | > 20% | **STOP.** Sprawdź kanibalizację i linkowanie. |
| Wzrost „Discovered — currently not indexed" | > 30% fali | Ostrzeżenie, popraw linkowanie wewnętrzne |

Progi są mechanizmem odwracalności. Jeżeli któryś zadziała, raportuj i czekaj
na decyzję — nie kontynuuj automatycznie.

---

## 13. Migracja z olechpartners.com

Stary serwis: Joomla, jedna strona + `/ru`. Dorobek niewielki, ale przenosimy w całości.

Kolejność:
1. Eksport baseline do `seo/baseline/` — GSC 16 mies., crawl, linki przychodzące
2. Rejestracja `olechpartners.pl`, serwis pod nią
3. `olechpartners.com` → 301 adres-w-adres, bezterminowo
4. `www` → forma kanoniczna, 301
5. Wersja `/ru` przeniesiona z poprawnym `hreflang` (`pl-PL`, `ru`, `x-default`)
6. Zgłoszenie zmiany adresu w Search Console
7. Weryfikacja GSC przeniesiona na DNS
8. Monitoring 8 tygodni

Staging: subdomena, `noindex`, HTTP basic auth. Przed przełączeniem na produkcję
skrypt musi sprawdzić, że `noindex` **nie** trafił na produkcję.

---

## 14. Wydajność

Cel: Core Web Vitals w zielonym na mobile.

- LCP < 2,5 s, INP < 200 ms, CLS < 0,1
- Obrazy WebP/AVIF, `loading="lazy"` poza pierwszym ekranem, wymiary w HTML
- Fonty self-hosted, `font-display: swap`, max 2 rodziny
- Brak jQuery, jeśli da się bez
- Mapa Google ładowana dopiero po kliknięciu (facade)
- Sitemapy: indeks + pliki po 200 URL, osobno dla każdego CPT

---

## 15. Checklista przedodbiorowa

Każdy punkt sprawdzany skryptem albo ręcznie przed publikacją fali:

1. Własna strona 404 z linkami do usług i wyszukiwarką
2. CTA nad foldem na każdym szablonie
3. Linkowanie wewnętrzne — brak ślepych stron
4. Strona podziękowania po wysłaniu formularza (osobny URL, cel w analityce)
5. Breadcrumbs wizualne + schema
6. Case studies — sekcja gotowa, treść od klienta
7. Minimum 5 pytań FAQ na każdej stronie usługi
8. Deklarowany czas odpowiedzi widoczny przy formularzu
9. Sticky CTA mobilne (telefon + WhatsApp)
10. `robots.txt` poprawny, staging zablokowany
11. Unikalne `<title>` na każdej stronie — walidacja skryptem
12. Unikalne meta description — walidacja skryptem
13. Obrazy OG dla social share
14. Mapa + wyznaczanie trasy (tylko Radom)
15. Opinie wyłącznie realne, z GBP
16. `alt` na każdym obrazie niedekoracyjnym
17. Schema lokalna wg sekcji 10
18. Polityka prywatności zgodna z RODO + obowiązki informacyjne detektywa
19. Google Analytics 4 + Search Console + Consent Mode v2
20. Zdjęcie zespołu / Daniela Olecha zamiast stocku

Dodatkowo dla tej branży:
21. Formularz z informacją o tajemnicy zawodowej przy przycisku wysyłki
22. Baner cookies z Consent Mode v2, zgoda przed skryptami śledzącymi
23. Numer wpisu MSWiA i licencji w stopce, na każdej stronie

---

## 16. Kolejność zadań

Osobna sesja na każdy punkt. Nie łącz.

1. Środowisko lokalne (DDEV/wp-env) + szkielet motywu
2. CPT, taksonomie, pola ACF
3. Szablony: strona główna, usługa, lokalizacja, hub wojewódzki, poradnik, kontakt
4. `data/uslugi.csv` + treści usług → import
5. `data/miasta.csv` — struktura i walidator (dane wypełniane etapami)
6. Importer WP-CLI, idempotentny (ponowne uruchomienie aktualizuje, nie duplikuje)
7. `dedup-gate.py`
8. Schema, sitemapy, linkowanie wewnętrzne, breadcrumbs
9. Wydajność i Core Web Vitals
10. Mapa 301 z baseline + test wszystkich przekierowań
11. `indexation-report.py`
12. Deploy (rsync albo GitHub Actions) + GSC

---

## 17. Dane oczekiwane od klienta

Do czasu otrzymania — `{{LOREM}}`.

**Otrzymane 2026-08-14** (plik „o firmie.txt" od klienta) — już w użyciu na
stronie, nie `{{LOREM}}`:
- Podmiot: MSWiA RD-13/2026 i licencja 0004178 (wydana przez Komendanta
  Wojewódzkiego Policji z siedzibą w Radomiu) potwierdzone jako dotyczące
  tego podmiotu/Daniela Olecha.
- Dane rejestrowe: KRS 0001096988, NIP 9482645495, REGON 528198884,
  adres siedziby: Szczęsna 26, 02-454 Warszawa. **Uwaga**: to adres
  REJESTROWY (KRS), nie adres operacyjny w Radomiu z sekcji 10 — nie
  używać go w schema `LocalBusiness` na stronie głównej, tylko w danych
  rejestrowych stopki/strony prawnej.
- Wariograf: badanie prowadzi Daniel Olech, uprawnienia uzyskane w Rosji,
  bieżące doszkalanie przez Polskie Stowarzyszenie Poligraferów oraz
  American Polygraph Association, sprzęt certyfikowany („uznawany przez
  międzynarodowe środowiska", bez konkretnego modelu), badanie 2–4 h
  zależnie od złożoności i celu, umawianie telefoniczne.
- Stowarzyszenia: członek Polskiego Stowarzyszenia Poligraferów oraz
  białoruskiego „Общественное объединение «Полиграфолог»" (Stowarzyszenie
  Poligrafolog).
- Fundacja: nazwa „Nowy Start", profil — przeciwdziałanie alienacji
  rodzicielskiej.
- Media: potwierdzony gość programu „Pytania na śniadanie" jako ekspert ds.
  detekcji kłamstwa (bez dokładnej daty/tytułu odcinka — patrz niżej).
- Zakres geograficzny: klient chce podkreślać też obsługę całej UE i
  krajów wschodnich, nie tylko Polski (do uwzględnienia w treściach o
  zasięgu działania, nie zmienia bazy operacyjnej z Radomia, sekcja 1).
- **Odrzucone jako sprzeczne z sekcją 2.1** (nie wdrożone): klient
  zaproponował „wygenerowanie jakichś historii z czapy" na case studies
  i „zerżnięcie" pytań FAQ wprost z konkurencji. Fabrykacji doświadczenia
  nie robimy — case studies nadal `{{LOREM}}`. Pytania FAQ: wolno użyć
  konkurencji jako listy *tematów*, odpowiedzi piszemy własne (sekcja 2.1
  już to dopuszczała).

**Otrzymane 2026-08-16** (podane wprost w sesji roboczej, nie z pliku
klienta) — już w użyciu, nie `{{LOREM}}`:
- Telefon kontaktowy: **+48 695 575 715** — ustawiony w Ustawienia →
  Dane firmy (`olech_ustawienia_firmy('telefon')`). Zasila: sticky CTA
  mobilne, schema `LocalBusiness.telephone`, blok `olech/cta-telefon`
  (nagłówek, hero, sekcje CTA).

Nadal brakuje:

| Obszar | Czego brakuje |
|---|---|
| Podmiot | Dokładna pełna nazwa prawna (mamy KRS/NIP/REGON, nie mamy nazwy z odpisu) |
| Współpracownicy | Tabela: miasto → osoba → nr licencji → forma umowy |
| Polisa OC | Numer, ubezpieczyciel, suma (klient: „doślę, teraz nie mam przy sobie") |
| Lokal w Radomiu | Adres pod wizytówkę GBP — klient: „musimy obgadać temat" |
| Cennik | Zakresy dla wszystkich usług, **łącznie z wariografem** (sekcja 9 — decyzja 2026-08-14: cena wariografu też nieujawniana) |
| Wariograf | Konkretny model i producent sprzętu, dokładne miejsce badania |
| Case studies | 8–12 realnych spraw do anonimizacji — **nie fabrykować** (patrz wyżej) |
| Opinie | Dostęp do GBP, proces zbierania |
| DNA | Laboratorium partnerskie i akredytacja |
| Mediacje | Wpis na listę stałych mediatorów — tak/nie (do czasu potwierdzenia: „mediacje i negocjacje", nie „mediacje sądowe", sekcja 2.3) |
| Media | Dokładna data i tytuł odcinka „Pytania na śniadanie" (program już potwierdzony) |
| Fundacja | KRS fundacji „Nowy Start", forma powiązania z firmą |
| Zdjęcia | Daniel Olech, sprzęt, miejsce badania (do czasu realnych — stockowe, za zgodą klienta) |
| Dostępy | Rejestrator domeny, hosting, GBP, GSC |

---

## 18. Czego nie robimy

- Nie tworzymy osobnych domen dla miast
- Nie zakładamy profili GBP poza Radomiem
- Nie dodajemy fraz do nazwy profilu GBP
- Nie publikujemy wszystkich lokalizacji naraz
- Nie używamy page buildera
- Nie generujemy treści, których nie da się potwierdzić
