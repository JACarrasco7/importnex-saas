#!/usr/bin/env node
/**
 * Auditor de strings hardcoded en archivos .vue y .blade.php
 *
 * Detecta:
 *   1. Texto entre > y < que NO sea solo whitespace o símbolos (text nodes literales)
 *   2. Atributos title=, alt=, placeholder=, aria-label= hardcoded en inglés
 *   3. option>Literal</option> sin bind dinámico
 *
 * EXCLUYE:
 *   - Comentarios y directivas Vue
 *   - Iconos/emojis sueltos
 *   - Strings dentro de t('...') (ya traducidos)
 *   - Strings dentro de {{ ... }} con bindings Vue
 *   - URLs/rutas/route names
 *   - Clases CSS, Tailwind, hex colors, números
 *   - Keys dentro de bloques <script setup>
 *   - Atributos HTML no traducibles (type=, name=, id=, class=, etc.)
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const TARGET_DIRS = [
    path.join(ROOT, 'resources/js/Pages'),
    path.join(ROOT, 'resources/js/Components'),
    path.join(ROOT, 'resources/js/Layouts'),
    path.join(ROOT, 'resources/views'),
];

// Regex simple para detectar palabras en inglés en un string (palabras comunes UI)
const ENGLISH_HINTS = [
    /\b(?:the|and|or|for|to|with|from|by|of|in|on|at|as|is|are|was|were|be|been)\b/gi,
    /\b(?:save|cancel|delete|edit|create|update|new|all|none|view|show|hide|back|next|prev)\b/gi,
    /\b(?:click|here|loading|error|success|warning|info|submit|reset|search|filter)\b/gi,
    /\b(?:cars?|vehicles?|clients?|users?|contacts?|alerts?|settings?|profile)\b/gi,
    /\b(?:name|email|phone|address|city|country|status|active|inactive|completed|pending)\b/gi,
    /\b(?:yes|no|total|amount|date|price|value|count|score|average|average)\b/gi,
];

// Excluir bloques <script setup>...</script>
function stripScriptBlocks(content) {
    return content.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, ' ');
}

function findHardcodedText(content) {
    const findings = [];
    const stripped = stripScriptBlocks(content);

    // 1. Buscar text nodes literales: >...<
    const textNodeRegex = />([^<>{}]+)</g;
    let match;
    while ((match = textNodeRegex.exec(stripped)) !== null) {
        const text = match[1].trim();
        if (!text) continue;
        if (text.length < 3) continue;
        // Filtrar símbolos puros
        if (/^[\s\W]+$/.test(text)) continue;
        // Filtrar emojis
        if (/[\u{1F300}-\u{1FAFF}]/u.test(text)) continue;
        // Filtrar si contiene algún binding Vue {{ ... }} o v-if etc.
        // (el regex ya filtra esos porque no permite { dentro de la captura)
        // Detectar inglés
        const hasEnglish = ENGLISH_HINTS.some(re => re.test(text));
        if (hasEnglish) {
            findings.push({ type: 'text', value: text });
        }
    }

    // 2. Buscar atributos placeholder=, title=, aria-label=, alt= hardcoded
    const attrRegex = /\b(?:placeholder|title|alt|aria-label)=["']([^"']{3,})["']/g;
    while ((match = attrRegex.exec(stripped)) !== null) {
        const text = match[1].trim();
        if (!text) continue;
        if (/^[\s\W]+$/.test(text)) continue;
        const hasEnglish = ENGLISH_HINTS.some(re => re.test(text));
        if (hasEnglish) {
            findings.push({ type: match[0].split('=')[0], value: text });
        }
    }

    // 3. option>Literal</option> hardcoded
    const optionRegex = /<option[^>]*>([^<>{]+)<\/option>/g;
    while ((match = optionRegex.exec(stripped)) !== null) {
        const text = match[1].trim();
        if (!text) continue;
        // Excluir si es dinámico (contiene {{ o v-bind)
        if (text.startsWith('{{')) continue;
        const hasEnglish = ENGLISH_HINTS.some(re => re.test(text));
        if (hasEnglish && text.length > 2) {
            findings.push({ type: 'option', value: text });
        }
    }

    return findings;
}

function walk(dir, results = []) {
    if (!fs.existsSync(dir)) return results;
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            if (entry.name === 'node_modules' || entry.name === '.git') continue;
            walk(full, results);
        } else if (/\.(vue|blade\.php)$/.test(entry.name)) {
            const content = fs.readFileSync(full, 'utf-8');
            const findings = findHardcodedText(content);
            if (findings.length > 0) {
                results.push({ file: path.relative(ROOT, full), findings });
            }
        }
    }
    return results;
}

console.log('=== Auditor de strings hardcoded (ES) ===\n');
const all = TARGET_DIRS.flatMap(d => walk(d));

let totalFindings = 0;
const byType = {};
for (const { file, findings } of all) {
    totalFindings += findings.length;
    for (const f of findings) {
        byType[f.type] = (byType[f.type] || 0) + 1;
    }
    console.log(`\n📄 ${file} (${findings.length})`);
    for (const f of findings.slice(0, 15)) {
        console.log(`   [${f.type}] ${f.value.substring(0, 80)}${f.value.length > 80 ? '...' : ''}`);
    }
    if (findings.length > 15) {
        console.log(`   ... y ${findings.length - 15} más`);
    }
}

console.log(`\n=== Resumen ===`);
console.log(`Total archivos con hardcoded: ${all.length}`);
console.log(`Total findings: ${totalFindings}`);
console.log(`Por tipo:`);
for (const [type, count] of Object.entries(byType)) {
    console.log(`  ${type}: ${count}`);
}
