#!/usr/bin/env node
/**
 * fix-i18n.cjs — Correcciones i18n detectadas por check-i18n-comprehensive.cjs
 *
 * Acciones:
 *  A) Borra claves "basura" (car_requests.{animal,tela}) de es.js y en.js
 *  B) Renombra en en.js: car_request_form.X → cars.X cuando cars.X ya existe en es.js
 *  C) Añade a es.js claves usadas en .vue que solo existen en en.js
 *  D) Añade claves NUEVAS para externalizar strings hardcoded en componentes públicos
 */

const fs = require('fs');
const path = require('path');
const util = require('util');

const ROOT = path.join(__dirname, '..');
const ES_PATH = path.join(ROOT, 'resources/js/i18n/es.js');
const EN_PATH = path.join(ROOT, 'resources/js/i18n/en.js');

function ensureBackup(file) {
    const bak = file + '.bak';
    if (!fs.existsSync(bak)) {
        fs.copyFileSync(file, bak);
        console.log(`  Backup creado: ${bak}`);
    }
}

const JUNK_KEYS = [
    'car_requests.silk', 'car_requests.satin', 'car_requests.velvet', 'car_requests.lace',
    'car_requests.cotton', 'car_requests.linen', 'car_requests.wool', 'car_requests.cashmere',
    'car_requests.mohair', 'car_requests.angora', 'car_requests.alpaca', 'car_requests.llama',
    'car_requests.vicuna', 'car_requests.guanaco', 'car_requests.camel', 'car_requests.horse',
    'car_requests.donkey', 'car_requests.mule', 'car_requests.zebra', 'car_requests.unicorn',
    'car_requests.pegasus', 'car_requests.dragon', 'car_requests.phoenix', 'car_requests.griffin',
    'car_requests.chimera', 'car_requests.hydra', 'car_requests.sphinx', 'car_requests.cerberus',
    'car_requests.cyclops', 'car_requests.troll', 'car_requests.orc', 'car_requests.goblin',
    'car_requests.elf', 'car_requests.dwarf', 'car_requests.gnome', 'car_requests.fairy',
    'car_requests.pixie', 'car_requests.sprite', 'car_requests.nymph', 'car_requests.siren',
    'car_requests.mermaid', 'car_requests.merman', 'car_requests.centaur', 'car_requests.satyr',
    'car_requests.minotaur', 'car_requests.manticore', 'car_requests.basilisk',
    'car_requests.cockatrice', 'car_requests.wyvern', 'car_requests.drake', 'car_requests.wyrm',
    'car_requests.lindworm', 'car_requests.leviathan', 'car_requests.behemoth',
    'car_requests.behemah', 'car_requests.behemoth_short', 'car_requests.behemot',
    'car_requests.behemot_short',
];

const NEW_ES_KEYS = {
    'cars.goodbye_label': 'Hasta pronto',
    'cars.subscribe_to_plan': 'Suscríbete a un plan para acceder al detalle',
    'subscription.monthly': 'Mensual',
    'subscription.annual': 'Anual',
    'subscription.annual_discount': 'Ahorra un 17% con el plan anual',
    'clients.add_title': 'Añadir cliente',
    'clients.add_subtitle_full': 'Registra un nuevo cliente en tu CRM',
    'marketplace_show.no_pros': 'No hay puntos a favor',
    'marketplace_show.no_cons': 'No hay puntos en contra',
    'marketplace_show.pros_label': 'A favor',
    'marketplace_show.cons_label': 'En contra',
    'marketplace_show.apply_what_relevant': 'Aplicar solo lo relevante',
    'marketplace_show.document_type_placeholder': 'Selecciona un tipo de documento',
    'marketplace_show.assigned_client': 'Cliente asignado',
    'marketplace_show.expenses_vs_estimated': 'Gastos vs. estimado',
};

const NEW_PUBLIC_KEYS = {
    es: {
        'cars.fuel_options': ['Diésel', 'Gasolina', 'Híbrido', 'Híbrido enchufable', 'Eléctrico', 'Gas'],
        'cars.transmission_options': ['Manual', 'Automático'],
        'cars.body_type_options': ['Berlina', 'SUV', 'Compacto', 'Monovolumen', 'Coupe', 'Cabrio', 'Pickup', 'Familiar'],
        'cars.engine_type_options': ['3 cilindros', '4 cilindros', '5 cilindros', '6 cilindros', '8 cilindros', 'Eléctrico'],
        'cars.color_options': ['Negro', 'Blanco', 'Gris', 'Plata', 'Azul', 'Rojo', 'Beige', 'Marrón', 'Verde'],
        'car_request_form.honeypot_label': 'Web (no rellenar)',
        'car_request_form.brochure_label': 'Folleto',
        'marketplace.newsletter_invalid_email': 'Email inválido',
    },
    en: {
        'cars.fuel_options': ['Diesel', 'Gasoline', 'Hybrid', 'Plug-in hybrid', 'Electric', 'Gas'],
        'cars.transmission_options': ['Manual', 'Automatic'],
        'cars.body_type_options': ['Sedan', 'SUV', 'Compact', 'Minivan', 'Coupe', 'Convertible', 'Pickup', 'Wagon'],
        'cars.engine_type_options': ['3 cylinders', '4 cylinders', '5 cylinders', '6 cylinders', '8 cylinders', 'Electric'],
        'cars.color_options': ['Black', 'White', 'Gray', 'Silver', 'Blue', 'Red', 'Beige', 'Brown', 'Green'],
        'car_request_form.honeypot_label': 'Website (do not fill)',
        'car_request_form.brochure_label': 'Brochure',
        'marketplace.newsletter_invalid_email': 'Invalid email',
    },
};

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

function loadObj(filePath) {
    const content = fs.readFileSync(filePath, 'utf8');
    const m = content.match(/export default ({[\s\S]*});/);
    if (!m) throw new Error('Cannot parse ' + filePath);
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

function renameKey(obj, fromKey, toKey) {
    const fromParts = fromKey.split('.');
    const toParts = toKey.split('.');
    let cur = obj;
    for (let i = 0; i < fromParts.length - 1; i++) {
        if (!cur[fromParts[i]] || typeof cur[fromParts[i]] !== 'object') return false;
        cur = cur[fromParts[i]];
    }
    if (!(fromParts[fromParts.length - 1] in cur)) return false;
    const value = cur[fromParts[fromParts.length - 1]];
    delete cur[fromParts[fromParts.length - 1]];

    let parent = obj;
    for (let i = 0; i < fromParts.length - 1; i++) {
        const k = fromParts[i];
        if (parent[k] && typeof parent[k] === 'object' && Object.keys(parent[k]).length === 0) {
            delete parent[k];
        }
        parent = parent[k] || {};
    }

    let target = obj;
    for (let i = 0; i < toParts.length - 1; i++) {
        if (!target[toParts[i]] || typeof target[toParts[i]] !== 'object') {
            target[toParts[i]] = {};
        }
        target = target[toParts[i]];
    }
    target[toParts[toParts.length - 1]] = value;
    return true;
}

function setKey(obj, dottedKey, value) {
    const parts = dottedKey.split('.');
    let cur = obj;
    for (let i = 0; i < parts.length - 1; i++) {
        if (!cur[parts[i]] || typeof cur[parts[i]] !== 'object') {
            cur[parts[i]] = {};
        }
        cur = cur[parts[i]];
    }
    cur[parts[parts.length - 1]] = value;
}

function toJsCode(obj) {
    return util.inspect(obj, { depth: null, breakLength: 120, quote: "'", sorted: false });
}

console.log('=== fix-i18n.cjs ===\n');

console.log('0) Backups:');
ensureBackup(ES_PATH);
ensureBackup(EN_PATH);

const es = loadObj(ES_PATH);
const en = loadObj(EN_PATH);
let stats = { junkEs: 0, junkEn: 0, renamedEn: 0, addedEs: 0, addedNewEs: 0, addedNewEn: 0 };

console.log('\n1) Borrando claves basura...');
JUNK_KEYS.forEach(k => {
    if (deleteKey(es, k)) stats.junkEs++;
    if (deleteKey(en, k)) stats.junkEn++;
});

console.log('2) Renombrando car_request_form.* → cars.* en en.js...');
const esKeys = new Set(getAllKeys(es));
const enKeysBefore = getAllKeys(en);
for (const k of enKeysBefore) {
    if (!k.startsWith('car_request_form.')) continue;
    const suffix = k.replace('car_request_form.', '');
    const candidate = 'cars.' + suffix;
    if (esKeys.has(candidate)) {
        if (renameKey(en, k, candidate)) {
            stats.renamedEn++;
        }
    }
}

console.log('3) Añadiendo claves faltantes a es.js...');
const enKeysAfter = new Set(getAllKeys(en));
for (const [key, val] of Object.entries(NEW_ES_KEYS)) {
    if (!esKeys.has(key)) {
        setKey(es, key, val);
        stats.addedEs++;
    }
}

for (const [key, val] of Object.entries(NEW_ES_KEYS)) {
    if (!enKeysAfter.has(key)) {
        setKey(en, key, val);
    }
}

console.log('4) Añadiendo claves nuevas para componentes públicos...');
for (const [k, v] of Object.entries(NEW_PUBLIC_KEYS.es)) {
    setKey(es, k, v);
    stats.addedNewEs++;
}
for (const [k, v] of Object.entries(NEW_PUBLIC_KEYS.en)) {
    setKey(en, k, v);
    stats.addedNewEn++;
}

console.log('\n5) Escribiendo archivos...');
const esContent = fs.readFileSync(ES_PATH, 'utf8');
const enContent = fs.readFileSync(EN_PATH, 'utf8');
const esHeader = esContent.match(/^([\s\S]*?export default )/)[1];
const enHeader = enContent.match(/^([\s\S]*?export default )/)[1];

fs.writeFileSync(ES_PATH, esHeader + toJsCode(es) + ';\n', 'utf8');
fs.writeFileSync(EN_PATH, enHeader + toJsCode(en) + ';\n', 'utf8');

console.log(`  ✓ ${ES_PATH}`);
console.log(`  ✓ ${EN_PATH}`);
console.log('\n=== Resumen ===');
console.log(JSON.stringify(stats, null, 2));
