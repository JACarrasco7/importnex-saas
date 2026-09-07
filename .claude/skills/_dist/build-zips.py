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


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser(description='Build ZIPs portables de las skills.')
    parser.add_argument('--skill-only', choices=['importacion-vehiculos', 'estudio-mercado'],
                        help='Solo regenerar esta skill.')
    parser.add_argument('--validate-only', action='store_true',
                        help='Solo auditar paths, sin regenerar ZIPs.')
    args = parser.parse_args()

    skills = [
        (r'.claude/skills/importacion-vehiculos',
         r'.claude/skills/_dist/skills-importacion-vehiculos-v3.7.1-20260906.zip'),
        (r'.claude/skills/estudio-mercado',
         r'.claude/skills/_dist/skills-estudio-mercado-v0.3.12-20260906.zip'),
    ]

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
