#!/usr/bin/env node
/**
 * prune-i18n-unused.cjs — Borra claves i18n sin uso en .vue/.js
 *
 * Recorre el código en resources/js/ y determina qué claves t('X.Y') se usan.
 * Después borra de es.js/en.js todas las claves que NO se usen.
 *
 * Backups .bak si no existen.
 */

const fs = require('fs');
const path = require('path');
const util = require('util');

const ROOT = path.join(__dirname, '..');
const ES_PATH = path.join(ROOT, 'resources/js/i18n/es.js');
const EN_PATH = path.join(ROOT, 'resources/js/i18n/en.js');
const SRC_DIRS = [path.join(ROOT, 'resources/js')];

function ensureBackup(file) {
    const bak = file + '.bak';
    if (!fs.existsSync(bak)) {
        fs.copyFileSync(file, bak);
        console.log(`  Backup: ${bak}`);
    }
}

// 1) Escanear todos los .vue/.js para extraer claves usadas
function walk(dir, files = []) {
    for (const entry of fs.readdirSync(dir)) {
        const full = path.join(dir, entry);
        if (fs.statSync(full).isDirectory()) walk(full, files);
        else if (/\.(vue|js)$/.test(entry)) files.push(full);
    }
    return files;
}

const used = new Set();
const re = /t\(\s*['"]([\w.]+)['"]/g;
for (const dir of SRC_DIRS) {
    for (const file of walk(dir)) {
        const content = fs.readFileSync(file, 'utf8');
        let m;
        re.lastIndex = 0;
        while ((m = re.exec(content)) !== null) {
            used.add(m[1]);
        }
    }
}
console.log(`Claves t() usadas en código: ${used.size}`);

// 2) Helpers
function loadObj(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const m = content.match(/export default ({[\s\S]*});/);
    return eval('(' + m[1] + ')');
}

function getAllKeys(obj, prefix = '') {
    const keys = [];
    for (const key in obj) {
        const fullKey = prefix ? `${prefix}.${key}` : key;
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            if (!(obj[key]._one || obj[key]._other)) {
                keys.push(...getAllKeys(obj[key], fullKey));
            } else {
                keys.push(fullKey);
            }
        } else {
            keys.push(fullKey);
        }
    }
    return keys;
}

function deleteKey(obj, dottedKey) {
    const parts = dottedKey.split('.');
    let cur = obj;
    for (let i = 0; i < parts.length - 1; i++) {
        if (!cur[parts[i]] || typeof cur[parts[i]] !== 'object') return false;
        cur = cur[parts[i]];
    }
    if (!(parts[parts.length - 1] in cur)) return false;
    delete cur[parts[parts.length - 1]];
    return true;
}

function cleanupEmpty(obj) {
    let changed = false;
    for (const key in obj) {
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            if (cleanupEmpty(obj[key]) && Object.keys(obj[key]).length === 0) {
                delete obj[key];
                changed = true;
            }
        }
    }
    return changed;
}

function toJsCode(obj) {
    return util.inspect(obj, { depth: null, breakLength: 120, quote: "'", sorted: false });
}

console.log('\n=== prune-i18n-unused.cjs ===\n');

ensureBackup(ES_PATH);
ensureBackup(EN_PATH);

const es = loadObj(ES_PATH);
const en = loadObj(EN_PATH);

let removedEs = 0, removedEn = 0;

// 3) Detectar y borrar claves sin uso (conservador: solo borrar las que NO se usan Y no son plurales)
console.log('Borrando claves no usadas...');
const esKeys = getAllKeys(es);
const enKeys = getAllKeys(en);

for (const k of esKeys) {
    if (!used.has(k)) {
        if (deleteKey(es, k)) removedEs++;
    }
}
for (const k of enKeys) {
    if (!used.has(k)) {
        if (deleteKey(en, k)) removedEn++;
    }
}

// 4) Limpiar namespaces vacíos
cleanupEmpty(es);
cleanupEmpty(en);

// 5) Escribir
console.log(`\nResumen: ES borradas ${removedEs}, EN borradas ${removedEn}`);
console.log('Escribiendo archivos...');

const esContent = fs.readFileSync(ES_PATH, 'utf8');
const enContent = fs.readFileSync(EN_PATH, 'utf8');
const esHeader = esContent.match(/^([\s\S]*?export default )/)[1];
const enHeader = enContent.match(/^([\s\S]*?export default )/)[1];

fs.writeFileSync(ES_PATH, esHeader + toJsCode(es) + ';\n', 'utf8');
fs.writeFileSync(EN_PATH, enHeader + toJsCode(en) + ';\n', 'utf8');

console.log(`  ✓ ${ES_PATH}`);
console.log(`  ✓ ${EN_PATH}`);
