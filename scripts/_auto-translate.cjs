#!/usr/bin/env node
/**
 * _auto-translate.cjs
 *
 * Para cada clave i18n donde es === en (texto idéntico en ambos idiomas),
 * genera una traducción al inglés automática usando reglas de reemplazo.
 *
 * Backup previo si no existe.
 */

const fs = require('fs');
const path = require('path');
const util = require('util');

const ROOT = process.cwd();
const ES_PATH = path.join(ROOT, 'resources/js/i18n/es.js');
const EN_PATH = path.join(ROOT, 'resources/js/i18n/en.js');

function loadObj(p) {
    const c = fs.readFileSync(p, 'utf8');
    return eval('(' + c.match(/export default ({[\s\S]*});/)[1] + ')');
}

function getKeys(o, p = '', k = []) {
    for (const x in o) {
        const f = p ? p + '.' + x : x;
        if (typeof o[x] === 'object' && o[x] !== null && !Array.isArray(o[x])) {
            if (!o[x]._one) getKeys(o[x], f, k);
            else k.push(f);
        } else k.push(f);
    }
    return k;
}

function getV(o, k) { return k.split('.').reduce((a, x) => a && a[x], o); }
function setV(o, k, v) {
    const parts = k.split('.');
    let cur = o;
    for (let i = 0; i < parts.length - 1; i++) {
        if (!cur[parts[i]] || typeof cur[parts[i]] !== 'object') cur[parts[i]] = {};
        cur = cur[parts[i]];
    }
    cur[parts[parts.length - 1]] = v;
}

function toJsCode(obj) {
    return util.inspect(obj, { depth: null, breakLength: 120, quote: "'", sorted: false });
}

// Diccionario de traducciones ES → EN (orden importa: más largo primero)
const DICT = [
    // Frases largas
    [/Seria una pena perderte\. Esto es lo que pasaria si cancelas:/g, "It would be a shame to lose you. Here is what would happen if you cancel:"],
    [/Sería una pena perderte\. Esto es lo que pasaría si cancelas:/g, "It would be a shame to lose you. Here is what would happen if you cancel:"],
    [/4 pasos para empezar a usar JJ Import Motors/g, "4 steps to get started with JJ Import Motors"],
    [/Bienvenido a JJ Import Motors/g, "Welcome to JJ Import Motors"],
    [/Los límites vuelven al plan Free \(3 vehículos, 1 usuario\)/g, "Limits return to the Free plan (3 vehicles, 1 user)"],
    [/Mantienes acceso completo a todas las funciones/g, "You keep full access to all features"],
    [/No se realizarán más cargos a tu tarjeta/g, "No further charges will be made to your card"],
    [/Puedes reactivar en cualquier momento/g, "You can reactivate anytime"],
    [/En los próximos 7 días/g, "In the next 7 days"],
    [/Después de 7 días/g, "After 7 days"],
    [/Tus datos se conservan 30 días por si decides volver/g, "Your data is kept for 30 days in case you decide to come back"],
    [/¿Hay algo que podamos mejorar\?/g, "Is there anything we can improve?"],
    [/Tu feedback nos ayuda\. Si cancelas, te preguntaremos el motivo\./g, "Your feedback helps us. If you cancel, we will ask you why."],
    [/Mantener mi suscripción/g, "Keep my subscription"],
    [/Antes de cancelar/g, "Before you cancel"],
    [/¿Seguro que quieres cancelar\?/g, "Are you sure you want to cancel?"],
    [/Webhook URL \(Slack \/ Discord \/ Teams\)/g, "Webhook URL (Slack / Discord / Teams)"],
    // Palabras sueltas
    [/^Importar CSV$/g, 'Import CSV'],
    [/^Importar CSV\s?$/g, 'Import CSV'],
    [/^Compleción$/g, 'Completion'],
    [/^completados$/g, 'completed'],
    [/^Completar$/g, 'Complete'],
    [/^Continuar asistente$/g, 'Continue wizard'],
    [/^Paso$/g, 'Step'],
    [/^Plan$/g, 'Plan'],
    [/^Color$/g, 'Color'],
    [/^Email$/g, 'Email'],
    [/^CRM$/g, 'CRM'],
    [/^Marketing$/g, 'Marketing'],
    [/^Kanban$/g, 'Kanban'],
    [/^Marketplace$/g, 'Marketplace'],
    [/^Total$/g, 'Total'],
    [/^Subtotal$/g, 'Subtotal'],
    [/^Hashtags$/g, 'Hashtags'],
    [/^Leads$/g, 'Leads'],
    [/^VIN$/g, 'VIN'],
    [/^vip, dealer, transport$/g, 'vip, dealer, transport'],
    [/^General$/g, 'General'],
    [/^starter$/g, 'starter'],
    [/^No$/g, 'No'],
    [/^$5\/mes$/g, '$5/month'],
    [/^Plan :name$/g, 'Plan :name'],
    [/^Actualizar método de pago$/g, 'Update payment method'],
    [/^Último intento fallido:$/g, 'Last failed attempt:'],
    [/^Confirmar cancelación$/g, 'Confirm cancellation'],
    [/^Pierdes acceso a funciones premium \(verificación AI, integraciones\)$/g, 'You lose access to premium features (AI verification, integrations)'],
    [/^Tu suscripción sigue activa temporalmente, pero reducirán las funciones si no lo solucionas\.$/g, 'Your subscription remains temporarily active, but features will be reduced if you do not fix it.'],
    [/^No pudimos procesar tu último pago$/g, 'We could not process your last payment'],
];

function translate(esText) {
    if (typeof esText !== 'string') return esText;
    let out = esText;
    for (const [re, replacement] of DICT) {
        out = out.replace(re, replacement);
    }
    return out;
}

console.log('=== _auto-translate.cjs ===\n');

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

let translated = 0;
const allKeys = new Set([...getKeys(es), ...getKeys(en)]);
allKeys.forEach(k => {
    const vEs = getV(es, k);
    const vEn = getV(en, k);
    if (typeof vEs === 'string' && typeof vEn === 'string' && vEs === vEn) {
        const newEn = translate(vEs);
        if (newEn !== vEn) {
            setV(en, k, newEn);
            translated++;
        }
    }
});

console.log(`Traducidas automáticamente: ${translated}`);

const enHeader = fs.readFileSync(EN_PATH, 'utf8').match(/^([\s\S]*?export default )/)[1];
fs.writeFileSync(EN_PATH, enHeader + toJsCode(en) + ';\n', 'utf8');
console.log(`✓ ${EN_PATH}`);
