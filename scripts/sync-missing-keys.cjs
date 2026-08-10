#!/usr/bin/env node
/**
 * sync-missing-keys.cjs
 *
 * Recorre el código y extrae TODAS las llamadas t('X.Y') con su fallback
 * (segundo/tercer argumento string). Las claves que NO estén en es.js/en.js
 * se añaden automáticamente usando el fallback del código fuente.
 *
 * Backup previo en <archivo>.bak si no existe.
 */

const fs = require('fs');
const path = require('path');
const util = require('util');

const ROOT = process.cwd();
const ES_PATH = path.join(ROOT, 'resources/js/i18n/es.js');
const EN_PATH = path.join(ROOT, 'resources/js/i18n/en.js');

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

function setKey(obj, dottedKey, value) {
    const parts = dottedKey.split('.');
    let cur = obj;
    for (let i = 0; i < parts.length - 1; i++) {
        if (!cur[parts[i]] || typeof cur[parts[i]] !== 'object') cur[parts[i]] = {};
        cur = cur[parts[i]];
    }
    cur[parts[parts.length - 1]] = value;
}

function cleanupEmpty(obj) {
    for (const key in obj) {
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            cleanupEmpty(obj[key]);
            if (Object.keys(obj[key]).length === 0) delete obj[key];
        }
    }
}

function toJsCode(obj) {
    return util.inspect(obj, { depth: null, breakLength: 120, quote: "'", sorted: false });
}

const VALID_KEY = /^[a-z][a-z0-9_]*(\.[a-z0-9_]+)+$/;

function extractFromCode() {
    const esMap = new Map();
    const enMap = new Map();

    function walk(dir) {
        for (const entry of fs.readdirSync(dir)) {
            if (entry === 'node_modules') continue;
            const full = path.join(dir, entry);
            const stat = fs.statSync(full);
            if (stat.isDirectory()) walk(full);
            else if (/\.(vue|js)$/.test(entry)) {
                const content = fs.readFileSync(full, 'utf8');
                const re = /t\(\s*'([\w.]+)'(?:\s*,\s*([^,)]*))?(?:\s*,\s*'((?:[^'\\]|\\.)*)')?\s*\)/g;
                let m;
                while ((m = re.exec(content)) !== null) {
                    const key = m[1];
                    const arg2 = m[2]?.trim();
                    const arg3 = m[3];
                    if (!VALID_KEY.test(key)) continue;

                    let fallback = null;
                    if (arg3 !== undefined) {
                        fallback = arg3.replace(/\\'/g, "'");
                    } else if (arg2 && /^'/.test(arg2)) {
                        const m2 = arg2.match(/^'((?:[^'\\]|\\.)*)'$/);
                        if (m2) fallback = m2[1].replace(/\\'/g, "'");
                    }

                    if (fallback !== null) {
                        const norm = full.replace(/\//g, '\\');
                        const isEn = /\\i18n\\en\.js$|\\lang\\en\\/.test(norm);
                        const target = isEn ? enMap : esMap;
                        target.set(key, fallback);
                    }
                }
            }
        }
    }
    walk(path.join(ROOT, 'resources/js'));

    return { esMap, enMap };
}

function translate(esVal) {
    return esVal
        .replace(/vehículos?/gi, m => /s$/.test(m) ? 'vehicles' : 'vehicle')
        .replace(/coches?/gi, m => /s$/.test(m) ? 'vehicles' : 'vehicle')
        .replace(/clientes?/gi, m => /s$/.test(m) ? 'clients' : 'client')
        .replace(/marcas?/gi, m => /s$/.test(m) ? 'brands' : 'brand')
        .replace(/ación\b/gi, 'ation')
        .replace(/imiento\b/gi, 'ment')
        .replace(/Ver\s/gi, 'See ')
        .replace(/Buscar\s/gi, 'Search ')
        .replace(/Cerrar\s/gi, 'Close ')
        .replace(/Enviar\s/gi, 'Send ')
        .replace(/Cancelar\s/gi, 'Cancel ')
        .replace(/Guardar\s/gi, 'Save ')
        .replace(/Eliminar\s/gi, 'Delete ')
        .replace(/Editar\s/gi, 'Edit ')
        .replace(/Añadir\s/gi, 'Add ')
        .replace(/Todos\b/gi, 'All')
        .replace(/Sí/gi, 'Yes')
        .replace(/Mostrar/gi, 'Show');
}

console.log('=== sync-missing-keys.cjs ===\n');

function ensureBackup(file) {
    const bak = file + '.bak';
    if (!fs.existsSync(bak)) {
        fs.copyFileSync(file, bak);
        console.log(`  Backup: ${bak}`);
    }
}
ensureBackup(ES_PATH);
ensureBackup(EN_PATH);

const es = loadObj(ES_PATH);
const en = loadObj(EN_PATH);
const i18nKeys = new Set([...getAllKeys(es), ...getAllKeys(en)]);

const { esMap, enMap } = extractFromCode();
console.log(`Fallbacks ES extraídos: ${esMap.size}`);
console.log(`Fallbacks EN extraídos: ${enMap.size}`);

let addedEs = 0, addedEn = 0, bothAdded = 0;

for (const [key, fallback] of esMap.entries()) {
    if (!i18nKeys.has(key)) {
        setKey(es, key, fallback);
        addedEs++;
    }
}
for (const [key, fallback] of enMap.entries()) {
    if (!i18nKeys.has(key)) {
        setKey(en, key, fallback);
        addedEn++;
    }
}
const enCurrentKeys = new Set(getAllKeys(en));
for (const key of esMap.keys()) {
    if (!enCurrentKeys.has(key) && !enMap.has(key)) {
        const esVal = esMap.get(key);
        setKey(en, key, translate(esVal));
        bothAdded++;
    }
}

cleanupEmpty(es);
cleanupEmpty(en);

console.log(`\nAñadidas a ES: ${addedEs}`);
console.log(`Añadidas a EN (fallback EN): ${addedEn}`);
console.log(`Añadidas a EN (auto desde ES): ${bothAdded}`);

const esHeader = fs.readFileSync(ES_PATH, 'utf8').match(/^([\s\S]*?export default )/)[1];
const enHeader = fs.readFileSync(EN_PATH, 'utf8').match(/^([\s\S]*?export default )/)[1];

fs.writeFileSync(ES_PATH, esHeader + toJsCode(es) + ';\n', 'utf8');
fs.writeFileSync(EN_PATH, enHeader + toJsCode(en) + ';\n', 'utf8');

console.log(`\n✓ ${ES_PATH}`);
console.log(`✓ ${EN_PATH}`);
