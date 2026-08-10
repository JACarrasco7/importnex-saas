#!/usr/bin/env node
/**
 * finalize-i18n.cjs — Últimos retoques i18n:
 *  A) Añade a EN las 5 claves car_request_form.* que fueron renombradas
 *     por error en fix-i18n.cjs (car_request_form.title, etc.) — vienen del PHP lang
 *  B) Borra de ES las 5 claves que faltan en EN y NO se usan
 *     (cars.import_csv, cars.actions, clients.contact_logs_title, etc.)
 */

const fs = require('fs');
const path = require('path');
const util = require('util');

const ROOT = path.join(__dirname, '..');
const ES_PATH = path.join(ROOT, 'resources/js/i18n/es.js');
const EN_PATH = path.join(ROOT, 'resources/js/i18n/en.js');

function loadObj(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const m = content.match(/export default ({[\s\S]*});/);
    return eval('(' + m[1] + ')');
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

console.log('=== finalize-i18n.cjs ===\n');

// Traducciones de car_request_form.* desde resources/lang/en/car_request_form.php
const CRF_EN_FIXES = {
    'car_request_form.title': 'Request your car',
    'car_request_form.any_option': 'Any',
    'car_request_form.sending': 'Sending...',
    'car_request_form.submit': 'Send request',
    'car_request_form.select_option': 'Select an option',
};

// Claves sin uso que hay que borrar de ES (también)
const UNUSED_IN_ES = [
    'cars.import_csv',
    'cars.actions',
    'clients.contact_logs_title',
    'clients.delete_log',
    'clients.delete_log_message',
];

const en = loadObj(EN_PATH);
const es = loadObj(ES_PATH);

// A) Añadir a EN
let added = 0;
for (const [k, v] of Object.entries(CRF_EN_FIXES)) {
    setKey(en, k, v);
    added++;
}
console.log(`A) EN: añadidas ${added} claves car_request_form.*`);

// B) Borrar de ES las no usadas
let removed = 0;
for (const k of UNUSED_IN_ES) {
    if (deleteKey(es, k)) removed++;
}
console.log(`B) ES: borradas ${removed} claves sin uso`);

cleanupEmpty(es);
cleanupEmpty(en);

// Escribir
const esHeader = fs.readFileSync(ES_PATH, 'utf8').match(/^([\s\S]*?export default )/)[1];
const enHeader = fs.readFileSync(EN_PATH, 'utf8').match(/^([\s\S]*?export default )/)[1];

fs.writeFileSync(ES_PATH, esHeader + toJsCode(es) + ';\n', 'utf8');
fs.writeFileSync(EN_PATH, enHeader + toJsCode(en) + ';\n', 'utf8');

console.log(`\n✓ ${ES_PATH}`);
console.log(`✓ ${EN_PATH}`);
