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

// Summary
if (missingInEs.length === 0 && missingInEn.length === 0 && duplicates.length === 0 && enDuplicates.length === 0) {
    console.log('\n✅ All translations are complete and consistent!');
    process.exit(0);
} else {
    console.log('\n❌ Translation issues found');
    process.exit(1);
}