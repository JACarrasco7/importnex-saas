"""Build ZIP ultra-defensivo para Claude Skills.

Reglas:
- ASCII puro en filename (Claude JS validator rechaza tildes/enyes/espacios).
- No incluye fixtures/, __tests__/, .git/, __pycache__/.
- No incluye .pyc, .zip, .bak, .tmp, archivos ocultos.
- ZIP_DEFLATED compatible con cualquier extractor JS.
- Pre + post validacion de cada entry.
"""
import os
import sys
import zipfile

SKIP_NAMES = {
    '__pycache__', '.DS_Store', 'Thumbs.db', '__MACOSX',
    '.git', '.gitignore', '.idea', '.vscode',
}
SKIP_EXTS = {'.pyc', '.pyo', '.pyd', '.zip', '.bak', '.tmp', '.swp'}
SKIP_DIRS = {'fixtures', '__tests__', 'tests', 'node_modules'}

# Caracteres prohibidos en ZIP filename (POSIX + Windows).
BAD_CHARS = set(':*?"<>|\\')
# Caracteres de control ASCII (< 0x20) y DEL.
BAD_CTRL = set(chr(c) for c in range(0x20) if chr(c) != '\t') | {chr(0x7f)}


def is_safe_path(p: str):
    if not p or p.endswith('/'):
        return False, 'empty o trailing slash'
    if p.startswith('/') or p.startswith('\\'):
        return False, 'absolute path'
    if any(seg == '..' for seg in p.split('/')):
        return False, 'path traversal'
    for c in p:
        if c in BAD_CHARS:
            return False, f'caracter invalido {c!r}'
        if c in BAD_CTRL:
            return False, f'control char {hex(ord(c))}'
        if ord(c) > 127:
            return False, f'no-ASCII char {c!r} ({hex(ord(c))})'
    if len(p) > 240:
        return False, f'path demasiado largo ({len(p)})'
    return True, ''


def build(skill_dir, out_path):
    skill_name = os.path.basename(os.path.normpath(skill_dir))
    planned = []
    for root, dirs, files in os.walk(skill_dir):
        dirs[:] = [d for d in dirs if d not in SKIP_NAMES and d not in SKIP_DIRS]
        for fn in files:
            if fn in SKIP_NAMES:
                continue
            if fn.startswith('.'):
                continue
            if any(fn.endswith(ext) for ext in SKIP_EXTS):
                continue
            full = os.path.join(root, fn)
            rel = os.path.relpath(full, skill_dir).replace(os.sep, '/')
            entry = f'{skill_name}/{rel}'
            ok, why = is_safe_path(entry)
            if not ok:
                print(f'  SKIP (unsafe): {entry}  -> {why}')
                continue
            planned.append((full, entry))

    if not planned:
        raise SystemExit(f'{skill_name}: sin archivos para empaquetar')

    if os.path.exists(out_path):
        os.remove(out_path)

    with zipfile.ZipFile(out_path, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        for full, entry in planned:
            zf.write(full, arcname=entry)

    with zipfile.ZipFile(out_path) as zf:
        names = zf.namelist()
        if len(names) != len(planned):
            raise SystemExit(f'{skill_name}: plan {len(planned)} != real {len(names)}')
        for info in zf.infolist():
            ok, why = is_safe_path(info.filename)
            if not ok:
                raise SystemExit(f'{skill_name}: entry invalido {info.filename!r}: {why}')

    print(f'  {skill_name}: {len(planned)} entries, {os.path.getsize(out_path):,} bytes -> {out_path}')


def read_skill_version(skill_dir):
    """Lee la version del SKILL.md de la skill (frontmatter YAML)."""
    skill_md = os.path.join(skill_dir, 'SKILL.md')
    if not os.path.exists(skill_md):
        raise SystemExit(f'{skill_dir}: falta SKILL.md')
    with open(skill_md, encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if line.startswith('version:'):
                return line.split(':', 1)[1].strip()
    raise SystemExit(f'{skill_dir}: no encuentro "version:" en SKILL.md')


def build_dst_path(skill_dir):
    """Calcula el nombre del ZIP desde la version REAL del SKILL.md + fecha del dia."""
    from datetime import date
    skill_name = os.path.basename(os.path.normpath(skill_dir))
    version = read_skill_version(skill_dir)
    today = date.today().strftime('%Y%m%d')
    return os.path.join(
        '.claude/skills/_dist',
        f'skills-{skill_name}-v{version}-{today}.zip',
    )


if __name__ == '__main__':
    import argparse
    import glob as _glob

    # Autodetectar skills (12-sep-2026): cualquier carpeta bajo .claude/skills/
    # que tenga un SKILL.md con frontmatter `version:`. Mantiene retro-compat:
    # si una skill NO tiene version: falla con mensaje claro (antes del fix pasaba
    # silencioso). Tambien excluimos _dist/ (no es una skill) y las skills que NO
    # son del negocio JJ Import Motors (cashier-stripe, inertia-vue, etc.) -
    # esas son de Copilot/Claude Code, no se publican como ZIP portable.
    SKILLS_ROOT = '.claude/skills'
    EXCLUDED = {'_dist', 'cashier-stripe-development', 'inertia-vue-development',
                'tailwindcss-development', 'infer-conventions', 'vehicle-listings',
                'laravel-best-practices'}
    all_skill_dirs = sorted([
        os.path.dirname(p) for p in _glob.glob(os.path.join(SKILLS_ROOT, '*', 'SKILL.md'))
        if os.path.basename(os.path.dirname(p)) not in EXCLUDED
    ])
    if not all_skill_dirs:
        raise SystemExit(f'No se encontraron skills de negocio en {SKILLS_ROOT}')

    parser = argparse.ArgumentParser(description='Build ZIPs portables de las skills.')
    parser.add_argument('--skill-only', choices=[os.path.basename(d) for d in all_skill_dirs],
                        help='Solo regenerar esta skill.')
    parser.add_argument('--validate-only', action='store_true',
                        help='Solo auditar paths, sin regenerar ZIPs.')
    args = parser.parse_args()

    skills = [(d, build_dst_path(d)) for d in all_skill_dirs]

    if args.skill_only:
        skills = [s for s in skills if args.skill_only in s[0]]

    if args.validate_only:
        errors = 0
        for src, _ in skills:
            skill_name = os.path.basename(os.path.normpath(src))
            print(f'\n=== {skill_name} ===')
            for root, dirs, files in os.walk(src):
                dirs[:] = [d for d in dirs if d not in SKIP_NAMES and d not in SKIP_DIRS]
                for fn in files:
                    if fn in SKIP_NAMES or fn.startswith('.') or any(fn.endswith(e) for e in SKIP_EXTS):
                        continue
                    full = os.path.join(root, fn)
                    rel = os.path.relpath(full, src).replace(os.sep, '/')
                    entry = f'{skill_name}/{rel}'
                    ok, why = is_safe_path(entry)
                    if not ok:
                        print(f'  !! {entry}  -> {why}')
                        errors += 1
            if errors == 0:
                print('  OK')
        print(f'\nTOTAL ERRORS: {errors}')
        sys.exit(1 if errors else 0)

    for src, dst in skills:
        print(f'\n=== {src} ===')
        build(src, dst)
    print('\nDone.')
