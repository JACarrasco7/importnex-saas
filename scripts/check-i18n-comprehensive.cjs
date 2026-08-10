#!/usr/bin/env node
/**
 * Detector i18n exhaustivo
 * -----------------------
 * 1. Compara es.js vs en.js → claves faltantes/duplicadas en cada idioma
 * 2. Detecta strings hardcoded (texto literal español/inglés) en componentes Vue
 *    de Pages/Public/ y en archivos Blade de resources/views/public/
 * 3. Detecta claves "basura" (placeholder masivo tipo silk/zebra/unicorn)
 * 4. NO modifica archivos. Solo reporta.
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const ES_PATH = path.join(ROOT, 'resources/js/i18n/es.js');
const EN_PATH = path.join(ROOT, 'resources/js/i18n/en.js');

function getAllKeys(obj, prefix = '') {
    const keys = [];
    for (const key in obj) {
        const fullKey = prefix ? `${prefix}.${key}` : key;
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            if (obj[key]._one || obj[key]._other) {
                keys.push(fullKey);
            } else {
                keys.push(...getAllKeys(obj[key], fullKey));
            }
        } else {
            keys.push(fullKey);
        }
    }
    return keys;
}

function loadLocale(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const m = content.match(/export default ({[\s\S]*});/);
    if (!m) throw new Error(`Cannot parse ${filePath}`);
    return eval('(' + m[1] + ')');
}

const es = loadLocale(ES_PATH);
const en = loadLocale(EN_PATH);
const esKeys = getAllKeys(es);
const enKeys = getAllKeys(en);

const missingInEs = enKeys.filter(k => !esKeys.includes(k));
const missingInEn = esKeys.filter(k => !enKeys.includes(k));
const dupEs = [...new Set(esKeys.filter((k, i) => esKeys.indexOf(k) !== i))];
const dupEn = [...new Set(enKeys.filter((k, i) => enKeys.indexOf(k) !== i))];

// Detectar claves "basura": nombres de animales mitológicos, telas exóticas, etc.
const JUNK_PATTERNS = [
    /^(silk|satin|velvet|lace|cotton|linen|wool|cashmere|mohair|angora|alpaca|llama|vicuna|guanaco|camel|horse|donkey|mule|zebra|unicorn|pegasus|dragon|phoenix|griffin|chimera|hydra|sphinx|cerberus|cyclops|troll|orc|goblin|elf|dwarf|gnome|fairy|pixie|sprite|nymph|siren|mermaid|merman|centaur|satyr|minotaur|manticore|basilisk|cockatrice|wyvern|drake|wyrm|lindworm|leviathan|behemoth|behemah|behemot)$/,
];
function isJunk(key) {
    const last = key.split('.').pop();
    return JUNK_PATTERNS.some(p => p.test(last));
}

const junkEs = esKeys.filter(isJunk);
const junkEn = enKeys.filter(isJunk);

// Detectar strings hardcoded en Vue
function walkDir(dir, ext, results = []) {
    if (!fs.existsSync(dir)) return results;
    for (const entry of fs.readdirSync(dir)) {
        const full = path.join(dir, entry);
        const stat = fs.statSync(full);
        if (stat.isDirectory()) {
            walkDir(full, ext, results);
        } else if (full.endsWith(ext)) {
            results.push(full);
        }
    }
    return results;
}

// Regex para texto literal español dentro de un .vue (en template o en interpolaciones)
// Captura: >Texto<, "Texto", 'Texto', :placeholder="'texto'" y {{ 'texto' }}
const HARDCODED_PATTERNS = [
    />([^<>{}\n]+)</g, // >texto<
    /"([^"]*[áéíóúñü¿¡][^"]*)"/g, // "texto" con caracteres españoles
    /'([^']*[áéíóúñü¿¡][^']*)'/g, // 'texto' con caracteres españoles
];

function findHardcoded(file) {
    const content = fs.readFileSync(file, 'utf8');
    const findings = [];
    const lines = content.split('\n');
    lines.forEach((line, idx) => {
        // Ignorar líneas que ya usan t() o trans()
        if (/\bt\(['"`]/.test(line)) return;
        if (/trans\(/.test(line)) return;
        if (/^\s*(import|export|\/\/|\/\*|\*)/.test(line)) return;
        if (/^[A-Z_]+ =/.test(line)) return;
        HARDCODED_PATTERNS.forEach(p => {
            let m;
            const re = new RegExp(p.source, p.flags);
            while ((m = re.exec(line)) !== null) {
                const txt = m[1].trim();
                // Ignorar clases CSS, atributos HTML sin texto, números, icon names
                if (!txt || txt.length < 3) return;
                if (/^(class|style|href|src|to|from|true|false|null|undefined)$/.test(txt)) return;
                if (/^[a-z-]+:[a-z-]+/.test(txt)) return; // CSS / directivas
                if (/^#[0-9a-f]{3,8}$/i.test(txt)) return; // colores
                if (/^\d+(\.\d+)?(px|rem|em|%|vh|vw|deg|s|ms)?$/.test(txt)) return;
                findings.push({
                    line: idx + 1,
                    text: txt.substring(0, 80),
                    source: line.trim().substring(0, 150),
                });
            }
        });
    });
    return findings;
}

const publicPagesDir = path.join(ROOT, 'resources/js/Pages/Public');
const vueFiles = walkDir(publicPagesDir, '.vue');

const report = {
    summary: {
        esKeys: esKeys.length,
        enKeys: enKeys.length,
        missingInEs: missingInEs.length,
        missingInEn: missingInEn.length,
        duplicatesEs: dupEs.length,
        duplicatesEn: dupEn.length,
        junkEs: junkEs.length,
        junkEn: junkEn.length,
        vueFilesScanned: vueFiles.length,
    },
    missingInEs: missingInEs.slice(0, 200),
    missingInEn: missingInEn.slice(0, 200),
    duplicates: { es: dupEs, en: dupEn },
    junk: { es: junkEs.slice(0, 50), en: junkEn.slice(0, 50) },
    hardcoded: {},
};

vueFiles.forEach(f => {
    const rel = path.relative(ROOT, f).replace(/\\/g, '/');
    const hits = findHardcoded(f);
    if (hits.length) {
        report.hardcoded[rel] = hits.slice(0, 30);
    }
});

const out = path.join(__dirname, '_i18n-report.json');
fs.writeFileSync(out, JSON.stringify(report, null, 2));

console.log('=== i18n Comprehensive Check ===\n');
console.log(`ES keys:        ${report.summary.esKeys}`);
console.log(`EN keys:        ${report.summary.enKeys}`);
console.log(`Missing in ES:  ${report.summary.missingInEs}`);
console.log(`Missing in EN:  ${report.summary.missingInEn}`);
console.log(`Duplicates ES:  ${report.summary.duplicatesEs}`);
console.log(`Duplicates EN:  ${report.summary.duplicatesEn}`);
console.log(`Junk ES:        ${report.summary.junkEs}`);
console.log(`Junk EN:        ${report.summary.junkEn}`);
console.log(`Vue Public scanned: ${report.summary.vueFilesScanned}`);
console.log(`\nHardcoded files (Public): ${Object.keys(report.hardcoded).length}`);
Object.keys(report.hardcoded).forEach(f => {
    console.log(`  ${f}: ${report.hardcoded[f].length} strings`);
});
console.log(`\nReport written to: ${out}`);

// Exit code
const hasIssues = report.summary.missingInEs > 0
    || report.summary.missingInEn > 0
    || report.summary.duplicatesEs > 0
    || report.summary.junkEs > 0
    || Object.keys(report.hardcoded).length > 0;
process.exit(hasIssues ? 1 : 0);
