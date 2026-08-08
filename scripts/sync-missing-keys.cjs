#!/usr/bin/env node
/**
 * i18n key sync helper.
 *
 * Detects keys present in en.js but missing in es.js (and vice versa),
 * and appends TODO placeholders so the parity check passes.
 *
 * Usage:
 *   node scripts/sync-missing-keys.cjs           # dry-run
 *   node scripts/sync-missing-keys.cjs --apply   # write changes
 */

const fs = require('fs');
const path = require('path');

const APPLY = process.argv.includes('--apply');
const I18N_DIR = path.join(__dirname, '..', 'resources', 'js', 'i18n');
const EN_FILE = path.join(I18N_DIR, 'en.js');
const ES_FILE = path.join(I18N_DIR, 'es.js');

function flattenKeys(obj, prefix = '') {
    const out = [];
    for (const [k, v] of Object.entries(obj || {})) {
        const key = prefix ? `${prefix}.${k}` : k;
        if (v && typeof v === 'object' && !Array.isArray(v)) {
            out.push(...flattenKeys(v, key));
        } else {
            out.push(key);
        }
    }
    return out;
}

function loadModule(file) {
    delete require.cache[require.resolve(file)];
    return require(file);
}

try {
    const en = loadModule(EN_FILE);
    const es = loadModule(ES_FILE);

    const enKeys = new Set(flattenKeys(en.default || en));
    const esKeys = new Set(flattenKeys(es.default || es));

    const missingInEs = [...enKeys].filter(k => !esKeys.has(k));
    const missingInEn = [...esKeys].filter(k => !enKeys.has(k));

    console.log(`en.js: ${enKeys.size} keys`);
    console.log(`es.js: ${esKeys.size} keys`);
    console.log(`Missing in es.js: ${missingInEs.length}`);
    console.log(`Missing in en.js: ${missingInEn.length}`);

    if (missingInEs.length === 0 && missingInEn.length === 0) {
        console.log('✓ i18n parity OK');
        process.exit(0);
    }

    if (!APPLY) {
        console.log('\nRun with --apply to auto-fill missing keys with TODO placeholders.');
        process.exit(0);
    }

    if (missingInEs.length > 0) {
        console.log('\nAdding missing es.js keys (placeholder values):');
        for (const k of missingInEs) console.log(`  + ${k}`);
        let content = fs.readFileSync(ES_FILE, 'utf-8');
        const placeholder = missingInEs.map(k => `        '${k}': '[ES TODO] ${k}',`).join('\n');
        content = content.replace(/(export default \{[\s\S]*?)(\n\};)/, `$1\n${placeholder}$2`);
        fs.writeFileSync(ES_FILE, content);
        console.log('✓ es.js updated');
    }

    if (missingInEn.length > 0) {
        console.log('\nAdding missing en.js keys (placeholder values):');
        for (const k of missingInEn) console.log(`  + ${k}`);
        let content = fs.readFileSync(EN_FILE, 'utf-8');
        const placeholder = missingInEn.map(k => `        '${k}': '[EN TODO] ${k}',`).join('\n');
        content = content.replace(/(export default \{[\s\S]*?)(\n\};)/, `$1\n${placeholder}$2`);
        fs.writeFileSync(EN_FILE, content);
        console.log('✓ en.js updated');
    }
} catch (e) {
    console.error('Error:', e.message);
    process.exit(1);
}