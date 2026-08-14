#!/usr/bin/env python3
"""Bramka antyduplikatowa — scripts/dedup-gate.py (sekcja 11 CLAUDE.md).

Liczy podobieństwo shingle (n=5, na poziomie słów) między każdą "nową"
stroną a wszystkimi "istniejącymi". W obecnym modelu treści (sekcja 4:
teksty długie jako .md pod content/, importowane do WP) korpusem są
wszystkie pliki content/**/*.md.

Progi (sekcja 11):
  - >= 65% (HARD_THRESHOLD): kolizja twarda — exit 1, import ma się
    zatrzymać i zwrócić listę kolizji. "Import bez przejścia bramki jest
    błędem krytycznym. Nie obchodź jej flagą."
  - >= 50% (WARN_THRESHOLD) i < 65%: ostrzeżenie — loguje, ale przechodzi
    (exit 0, o ile nie ma też kolizji twardych).
  - < 50%: nie trafia do raportu.

Raport zapisywany do reports/dedup-fala-{N}.txt (parametr --fala).

Użycie:
  python3 scripts/dedup-gate.py --fala 1
      Traktuje WSZYSTKIE content/**/*.md jako "nowe" i porównuje każdy
      z każdym — self-check całego bieżącego korpusu.
  python3 scripts/dedup-gate.py --fala 1 --new content/uslugi/wykrywanie-podsluchow.md
      Traktuje podane pliki jako "nowe", reszta content/**/*.md to
      "istniejące" — tryb docelowy przed importem konkretnej partii treści.
"""

import argparse
import re
import sys
from itertools import combinations
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
CONTENT_DIR = REPO_ROOT / 'content'
REPORTS_DIR = REPO_ROOT / 'reports'

SHINGLE_N = 5
HARD_THRESHOLD = 0.65
WARN_THRESHOLD = 0.50

# Usuwamy znaczniki Markdown i treść placeholderów {{LOREM: ...}}, żeby
# porównywać rzeczywistą treść, nie formatowanie — inaczej wspólna
# struktura nagłówków (np. "## Problem klienta" powtórzone w każdym pliku
# usługi, sekcja 8.2) fałszywie zawyżałaby podobieństwo.
MD_NOISE_RE = re.compile(r'\{\{LOREM:[^}]*\}\}|[#*\[\]()|_`>-]')
WORD_RE = re.compile(r'[a-ząćęłńóśźż0-9]+', re.IGNORECASE)


def tokenize(text: str) -> list:
    text = MD_NOISE_RE.sub(' ', text.lower())
    return WORD_RE.findall(text)


def shingles(words: list, n: int = SHINGLE_N) -> set:
    if len(words) < n:
        return {tuple(words)} if words else set()
    return {tuple(words[i:i + n]) for i in range(len(words) - n + 1)}


def jaccard(a: set, b: set) -> float:
    if not a or not b:
        return 0.0
    inter = len(a & b)
    union = len(a | b)
    return inter / union if union else 0.0


def load_shingles(paths):
    cache = {}
    for p in paths:
        text = p.read_text(encoding='utf-8')
        cache[p] = shingles(tokenize(text))
    return cache


def rel(p: Path) -> Path:
    try:
        return p.resolve().relative_to(REPO_ROOT)
    except ValueError:
        return p


def main():
    parser = argparse.ArgumentParser(
        description=__doc__,
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    parser.add_argument(
        '--fala', required=True, type=int,
        help='Numer fali publikacji — do nazwy pliku raportu (reports/dedup-fala-{N}.txt)',
    )
    parser.add_argument(
        '--new', nargs='*', default=None,
        help='Pliki "nowe" do sprawdzenia. Domyślnie: wszystkie content/**/*.md '
             '(self-check całego korpusu).',
    )
    parser.add_argument(
        '--content-dir', default=str(CONTENT_DIR),
        help='Katalog z treścią (domyślnie content/ w repo)',
    )
    args = parser.parse_args()

    content_dir = Path(args.content_dir)
    all_files = sorted(content_dir.rglob('*.md'))
    if not all_files:
        print(f"Brak plików .md w {content_dir} — nic do sprawdzenia.")
        sys.exit(0)

    all_resolved = {f.resolve() for f in all_files}

    if args.new:
        new_files = [Path(p).resolve() for p in args.new]
        for f in new_files:
            if f not in all_resolved:
                print(f"UWAGA: {f} nie jest pod {content_dir} — porównuję mimo to.", file=sys.stderr)
        existing_files = [f for f in all_files if f.resolve() not in {nf for nf in new_files}]
        compare_pairs = [(nf, ef) for nf in new_files for ef in existing_files]
        compare_pairs += list(combinations(new_files, 2))
    else:
        new_files = all_files
        compare_pairs = list(combinations(all_files, 2))

    corpus_paths = set(new_files) | set(all_files)
    corpus = load_shingles(corpus_paths)

    hard_collisions = []
    warnings = []
    seen_pairs = set()

    for a, b in compare_pairs:
        a, b = Path(a), Path(b)
        key = tuple(sorted((str(a.resolve()), str(b.resolve()))))
        if key in seen_pairs or a.resolve() == b.resolve():
            continue
        seen_pairs.add(key)
        sim = jaccard(corpus[a], corpus[b])
        if sim >= HARD_THRESHOLD:
            hard_collisions.append((a, b, sim))
        elif sim >= WARN_THRESHOLD:
            warnings.append((a, b, sim))

    hard_collisions.sort(key=lambda x: -x[2])
    warnings.sort(key=lambda x: -x[2])

    lines = []
    lines.append(f"Bramka antyduplikatowa — fala {args.fala}")
    lines.append(f"Próg twardy: {HARD_THRESHOLD:.0%} | próg ostrzegawczy: {WARN_THRESHOLD:.0%} | shingle n={SHINGLE_N}")
    lines.append(f"Sprawdzonych nowych plików: {len(new_files)} | porównań: {len(seen_pairs)}")
    lines.append("")

    if hard_collisions:
        lines.append(f"KOLIZJE TWARDE (>= {HARD_THRESHOLD:.0%}) — {len(hard_collisions)}:")
        for a, b, sim in hard_collisions:
            lines.append(f"  {sim:.1%}  {rel(a)}  <->  {rel(b)}")
        lines.append("")
    else:
        lines.append("Brak kolizji twardych.")
        lines.append("")

    if warnings:
        lines.append(f"OSTRZEŻENIA ({WARN_THRESHOLD:.0%}-{HARD_THRESHOLD:.0%}) — {len(warnings)}:")
        for a, b, sim in warnings:
            lines.append(f"  {sim:.1%}  {rel(a)}  <->  {rel(b)}")
        lines.append("")
    else:
        lines.append("Brak ostrzeżeń.")
        lines.append("")

    report = "\n".join(lines)
    print(report)

    REPORTS_DIR.mkdir(exist_ok=True)
    report_path = REPORTS_DIR / f"dedup-fala-{args.fala}.txt"
    report_path.write_text(report, encoding='utf-8')
    print(f"Raport zapisany: {rel(report_path)}")

    if hard_collisions:
        print("\nBŁĄD KRYTYCZNY: znaleziono kolizje >= progu twardego. Import zatrzymany.", file=sys.stderr)
        sys.exit(1)

    sys.exit(0)


if __name__ == '__main__':
    main()
