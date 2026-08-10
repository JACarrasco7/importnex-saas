#!/usr/bin/env node
/**
 * replace-auto-with-vehicle.cjs
 *
 * Sustituye "Auto" / "Auto(s)" / "coche" / "coches" / "car" / "cars" por
 * "Vehículo" / "Vehículos" en archivos .vue, .php, .js, .json del proyecto,
 * EXCLUYENDO:
 *   - public/build/**  (JS compilado, se regenera con npm run build)
 *   - docs/, .ai/, tests/  (documentación interna y fixtures)
 *   - Logs técnicos y strings de debug
 *   - URLs externas (milanuncios.com/coches, autoscout24, etc.)
 *   - Variables PHP ($car, $coche), nombres de métodos/clases en backend
 *   - Seeds de mensajes en alemán (placeholders {{coche}})
 *   - Comentarios de código en inglés
 *   - Términos "AutoScout", "Automatic", "autocompletar", etc.
 *
 * Backup previo en scripts/_autos-backup/<path_relativo>.
 */

const fs = require('fs');
const path = require('path');

const ROOT = process.cwd();
const EXTS = new Set(['.vue', '.php', '.js', '.json']);

// Directorios excluidos (no tocar)
const EXCLUDE_DIRS = new Set([
    'node_modules', 'vendor', 'storage', '_tmp', 'bootstrap/cache',
    '.git', 'public/build', 'docs', '.ai', 'tests', 'reports',
    'JJ Import Motors',  // docs internas
]);

// EXCLUIR TODO el backend PHP. Solo cambiaremos:
//   - resources/js/i18n/{es,en}.js
//   - resources/lang/{es,en}/**/*.php
//   - resources/js/Pages/**.vue
//   - resources/js/Components/**.vue
//   - resources/js/Layouts/**.vue
//   - resources/js/Composables/**.js  (texto visible)
//   - resources/views/**.blade.php
//   - resources/views/**/*.php
// NO tocar: app/**, database/**, routes/**, scripts/**, config/**, public/build/**
const PROCESS_DIRS = new Set([
    'resources',
]);

// Patrones que significan "coche/vehículo" y deben cambiarse.
// Aplican SOLO en líneas que NO sean URLs, variables PHP, strings de log técnicos, etc.
// Reglas:
//   - "Auto" / "Autos"  → "Vehículo" / "Vehículos"
//   - "coche" / "coches" → "vehículo" / "vehículos"
//   - "Car" / "Cars" (en .vue, .blade) → "Vehicle" / "Vehicles" (en EN)
//   - Placeholders {{coche}} / {{cars}} → {{vehiculo}} / {{vehiculos}}

// Por idioma del archivo: cómo traducir el reemplazo
// ES: Auto→Vehículo, AutoS→VehículoS, coche→vehículo, coches→vehículos, car→vehículo, cars→vehículos
// EN: Auto→Vehicle, AutoS→Vehicles, Car→Vehicle, Cars→Vehicles, coche→coche (no tocar en EN)
//     cars→vehicles, Car→Vehicle (case-sensitive)

// Lista de líneas que NO se tocan (URLs, marcas, etc.)
const SAFE_SKIP_PATTERNS = [
    /https?:\/\/[^\s'"]*coches/,
    /https?:\/\/[^\s'"]*\/car[s]?\//,  // /cars/ en URLs
    /AutoScout/i,
    /autoscout/i,
    /\bAutomático\b/,
    /\bAutomatic\b/,
    /\bautocomplete\b/i,
    /\bautocompletar\b/i,
    /\bautonomous\b/i,
    /\bautopilot\b/i,
    /\bautopilot\b/i,
    /\bautomotor(es)?\b/i,
    /\bautom[aá]tic[ao]?\b/i,  // automático/a
    /\bautocr[oó]nit/i,
    /\bautosuficiente\b/i,
    /\bautoestima\b/i,
    /\bauto-?ayuda\b/i,
    /\bauto-?gesti[oó]n\b/i,
    /piloto\s+autom[aá]tico/i,
    /\bself-?driving\b/i,
    /\/coches[\/\?]/,  // URL path
    /\$car\b/,         // PHP $car variable
    /\$cars\b/,
    /\$coche\b/,
    /\$coches\b/,
    /->\w*[Cc]ar\b/,    // PHP method/variable
    /['"]\w*[Cc]ar['"]/,  // string literal 'Car' en backend
    /\bclass\s+\w*[Cc]ar\b/,  // PHP class
    /\{[\{\s]*\{coche\}[\s\}\}]*\}/,  // placeholder {{coche}}
    /'coche_net'|"coche_net"/,  // config keys
    /\bcoches_net\b/,
    /CarController\b/,
    /CarScrapingService\b/,
    /CarMarketingService\b/,
    /CarRequest\b/,
    /CarChecklistDefinitions\b/,
    /CarDocumentDefinitions\b/,
    /ValuationImporter\b/,
    /ValuationImportController\b/,
    /ValuationPackageIngestor\b/,
    /ImportValuation\b/,
    /ImportRealCars\b/,
    /\bcar\b\.\w+/,    // car.method (backend)
    /\bcars\b\.\w+/,
    /on\(['"]scroll['"],\s*onScroll/,  // código
    /window\.scroll/,  // código JS
];

function shouldSkipLine(line) {
    return SAFE_SKIP_PATTERNS.some(re => re.test(line));
}

// Detectar idioma del archivo para aplicar traducciones correctas
function detectLanguage(file) {
    const normalized = file.replace(/\\/g, '/');
    if (/\/i18n\/en(\.js)?$/.test(normalized) || /\/lang\/en\//.test(normalized)) return 'en';
    return 'es';  // por defecto ES
}

// Reemplazar en una línea, preservando exclusiones
function transformLine(line, lang) {
    if (shouldSkipLine(line)) return { text: line, changed: false };

    let result = line;
    let changed = false;

    // 1) 'Auto' / 'Autos' como palabras completas (case-sensitive, fuera de camelCase)
    // Solo si no es parte de un identificador (var/method) — los identificadores ya quedan fuera por SAFE_SKIP
    const before = result;

    if (lang === 'es') {
        // Auto → Vehículo, Autos → Vehículos
        // coche → vehículo, coches → vehículos
        result = result.replace(/\bAuto\b/g, 'Vehículo');
        result = result.replace(/\bAutos\b/g, 'Vehículos');
        result = result.replace(/\bcoche\b/g, 'vehículo');
        result = result.replace(/\bcoches\b/g, 'vehículos');
    } else {
        // EN
        result = result.replace(/\bCar\b/g, 'Vehicle');
        result = result.replace(/\bCars\b/g, 'Vehicles');
        // En EN, "coche/coches" puede aparecer como "vehicle" mal traducido → corregir
        result = result.replace(/\bcoche\b/g, 'vehicle');
        result = result.replace(/\bcoches\b/g, 'vehicles');
    }

    return { text: result, changed: result !== before };
}

function walk(dir, files = []) {
    for (const entry of fs.readdirSync(dir)) {
        if (EXCLUDE_DIRS.has(entry)) continue;
        const full = path.join(dir, entry);
        // Solo procesar bajo PROCESS_DIRS (resources/)
        const rel = path.relative(ROOT, full).replace(/\\/g, '/');
        const topLevel = rel.split('/')[0];
        if (!PROCESS_DIRS.has(topLevel)) continue;

        const stat = fs.statSync(full);
        if (stat.isDirectory()) walk(full, files);
        else if (EXTS.has(path.extname(full))) files.push(full);
    }
    return files;
}

const BACKUP_ROOT = path.join(ROOT, 'scripts', '_autos-backup');
if (!fs.existsSync(BACKUP_ROOT)) fs.mkdirSync(BACKUP_ROOT, { recursive: true });

function backup(file) {
    const rel = path.relative(ROOT, file).replace(/\\/g, '/');
    const dest = path.join(BACKUP_ROOT, rel);
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.copyFileSync(file, dest);
}

const files = walk(ROOT);
console.log('Archivos a procesar:', files.length);

let totalFilesChanged = 0;
let totalLinesChanged = 0;
const changes = [];

for (const file of files) {
    const lang = detectLanguage(file);
    const content = fs.readFileSync(file, 'utf8');
    const lines = content.split('\n');
    let fileChanged = 0;
    const newLines = lines.map(line => {
        const { text, changed } = transformLine(line, lang);
        if (changed) {
            fileChanged++;
            changes.push({
                file: path.relative(ROOT, file).replace(/\\/g, '/'),
                line: line.substring(0, 200),
                newLine: text.substring(0, 200),
            });
        }
        return text;
    });
    if (fileChanged > 0) {
        backup(file);
        fs.writeFileSync(file, newLines.join('\n'), 'utf8');
        totalFilesChanged++;
        totalLinesChanged += fileChanged;
    }
}

console.log('\n=== Resultado ===');
console.log('Archivos modificados:', totalFilesChanged);
console.log('Líneas modificadas:', totalLinesChanged);
console.log('Backup en:', BACKUP_ROOT);

// Guardar log de cambios
fs.writeFileSync(
    path.join(ROOT, 'scripts/_autos-changes.json'),
    JSON.stringify(changes.slice(0, 500), null, 2)
);
console.log('Log detallado: scripts/_autos-changes.json');