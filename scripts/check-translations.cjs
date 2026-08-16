#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Helper to get all keys from a nested object
function getAllKeys(obj, prefix = '') {
    const keys = [];
    for (const key in obj) {
        const fullKey = prefix ? `${prefix}.${key}` : key;
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            keys.push(...getAllKeys(obj[key], fullKey));
        } else {
            keys.push(fullKey);
        }
    }
    return keys;
}

// Load translation files
const esPath = path.join(__dirname, '../resources/js/i18n/es.js');
const enPath = path.join(__dirname, '../resources/js/i18n/en.js');

// Parse the export default objects
const esContent = fs.readFileSync(esPath, 'utf8');
const enContent = fs.readFileSync(enPath, 'utf8');

// Extract the object from export default
const esMatch = esContent.match(/export default ({[\s\S]*});/);
const enMatch = enContent.match(/export default ({[\s\S]*});/);

if (!esMatch || !enMatch) {
    console.error('Could not parse translation files');
    process.exit(1);
}

// Parse the objects
let esTranslations, enTranslations;
try {
    esTranslations = eval('(' + esMatch[1] + ')');
    enTranslations = eval('(' + enMatch[1] + ')');
} catch (e) {
    console.error('Error parsing translation files:', e.message);
    process.exit(1);
}

const esKeys = getAllKeys(esTranslations);
const enKeys = getAllKeys(enTranslations);

// Claves que son objetos plurales (p.ej. app.inventory_count → _one/_other):
// se usan con t('app.inventory_count', {count}) y no deben reportarse missing.
function isPluralObjectKey(obj, key) {
    const parts = key.split('.');
    let cur = obj;
    for (let i = 0; i < parts.length; i++) {
        if (cur === null || typeof cur !== 'object') return false;
        cur = cur[parts[i]];
        if (cur === undefined) return false;
    }
    return cur !== null && typeof cur === 'object' && !Array.isArray(cur) && ('_one' in cur || '_other' in cur);
}

// Una clave usada t('cars.fuel_options') puede existir como OBJETO (no hoja).
// getAllKeys solo produce hojas, así que "existe" si es hoja o prefix de alguna
// clave existente.
function keyExists(obj, key) {
    const parts = key.split('.');
    let cur = obj;
    for (let i = 0; i < parts.length; i++) {
        if (cur === null || typeof cur !== 'object' || cur[parts[i]] === undefined) return false;
        cur = cur[parts[i]];
    }
    return true; // hoja o objeto intermedio
}

// Find missing keys
const missingInEs = enKeys.filter(k => !esKeys.includes(k));
const missingInEn = esKeys.filter(k => !enKeys.includes(k));

console.log('=== Translation Check Report ===\n');

console.log(`Spanish keys: ${esKeys.length}`);
console.log(`English keys: ${enKeys.length}`);
console.log(`Missing in Spanish: ${missingInEs.length}`);
console.log(`Missing in English: ${missingInEn.length}`);

if (missingInEs.length > 0) {
    console.log('\n--- Missing in Spanish (es.js) ---');
    missingInEs.forEach(k => console.log(`  ${k}`));
}

if (missingInEn.length > 0) {
    console.log('\n--- Missing in English (en.js) ---');
    missingInEn.forEach(k => console.log(`  ${k}`));
}

// Check for duplicate keys
const duplicates = esKeys.filter((k, i) => esKeys.indexOf(k) !== i);
if (duplicates.length > 0) {
    console.log('\n--- Duplicate keys in Spanish ---');
    duplicates.forEach(k => console.log(`  ${k}`));
}

const enDuplicates = enKeys.filter((k, i) => enKeys.indexOf(k) !== i);
if (enDuplicates.length > 0) {
    console.log('\n--- Duplicate keys in English ---');
    enDuplicates.forEach(k => console.log(`  ${k}`));
}

// M4 (auditoría 15-ago-2026): detectar claves usadas en los .vue que no
// existen en los ficheros de traducción (se renderizarían como literal), y
// claves definidas sin uso (informativo — hay claves usadas dinámicamente).
const vueDir = path.join(__dirname, '../resources/js/Pages');
const usedKeys = new Set();
const usedInFile = {};

function walkVue(dir) {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) walkVue(full);
        else if (entry.name.endsWith('.vue')) {
            const content = fs.readFileSync(full, 'utf8');
            // t('literal') / t("literal") — ignora concat (t('x.'+k)) y casos
            // como photoForm.reset('photos') (el `t` de `reset(` no es t()).
            // Lookbehind negativo: la `t` no puede ir precedida de identificador.
            const re = /(?<![A-Za-z0-9_$])t\(\s*['"]([^'"$]+)['"]/g;
            let m;
            while ((m = re.exec(content)) !== null) {
                const key = m[1].trim();
                if (!key) continue;
                usedKeys.add(key);
                if (!usedInFile[key]) usedInFile[key] = [];
                usedInFile[key].push(path.relative(path.join(__dirname, '..'), full));
            }
        }
    }
}
walkVue(vueDir);

const usedButMissing = [...usedKeys]
    // Excluye concat dinámicas tipo t('cars.status.' + s) y t('x.'+k)
    .filter(k => !k.endsWith('.'))
    // Excluye claves plurales (t('app.inventory_count', {count}))
    .filter(k => !isPluralObjectKey(esTranslations, k))
    .filter(k => !isPluralObjectKey(enTranslations, k))
    // Excluye claves que existen (hoja u objeto) en al menos un idioma
    .filter(k => !keyExists(esTranslations, k) && !keyExists(enTranslations, k));
// WARNING informativo: no rompe el exit code (hay páginas fuera del alcance
// de la sesión con claves pendientes). Reporta para corregirlas poco a poco.
if (usedButMissing.length > 0) {
    console.log('\n⚠️  Keys used in .vue but MISSING in es/en (would render literal):');
    usedButMissing.forEach(k => {
        const where = [...new Set(usedInFile[k])].join(', ');
        console.log(`  ${k}  (${where})`);
    });
    console.log(`  → ${usedButMissing.length} keys pendientes (no bloquea, pero revisa)`);
}

// Huérfanas: definidas en ambos pero sin uso literal en .vue. Informativo:
// pueden ser usadas dinámicamente (t('cars.status.'+s)) o por otros scripts.
const orphans = esKeys.filter(k => enKeys.includes(k) && !usedKeys.has(k));
if (orphans.length > 0) {
    console.log('\n--- Keys defined but not referenced literally in Pages (info only) ---');
    console.log(`  (${orphans.length} keys — muchas son dinámicas, no es un error)`);
}

// Summary — usadas-inexistentes es WARNING (no rompe); solo falla la paridad
// o duplicados reales.
const allOk = missingInEs.length === 0 && missingInEn.length === 0 &&
    duplicates.length === 0 && enDuplicates.length === 0;
if (allOk) {
    console.log('\n✅ All translations are complete and consistent!');
    process.exit(0);
} else {
    console.log('\n❌ Translation issues found');
    process.exit(1);
}
