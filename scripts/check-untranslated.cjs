#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const VIEWS_DIR = path.join(__dirname, '../resources/js/Pages');
const I18N_DIR = path.join(__dirname, '../resources/js/i18n');

// Cargar todas las claves de traducción
function loadKeys() {
    const keys = new Set();
    ['es.js', 'en.js'].forEach(file => {
        const content = fs.readFileSync(path.join(I18N_DIR, file), 'utf8');
        const match = content.match(/export default ({[\s\S]*});/);
        if (match) {
            try {
                const obj = eval('(' + match[1] + ')');
                extractKeys(obj, '', keys);
            } catch (e) {}
        }
    });
    return keys;
}

function extractKeys(obj, prefix, keys) {
    for (const key in obj) {
        const fullKey = prefix ? `${prefix}.${key}` : key;
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
            extractKeys(obj[key], fullKey, keys);
        } else {
            keys.add(fullKey);
        }
    }
}

// Buscar texto hardcodeado en español
const SPANISH_PATTERNS = [
    /\b(Añadir|Editar|Eliminar|Guardar|Cancelar|Buscar|Cargar|Cargando|Vista|Mapa|Cliente|Clientes|Auto|Autos|Carro|Carrocería|Marca|Modelo|Año|Precio|Estado|Kilometraje|Combustible|Transmisión|Puertas|Plazas|Color|VIN|Notas|Requisitos|Solicitud|Solicitudes|Pendiente|Pendientes|Contactado|En proceso|Completada|Completado|Cancelada|Cancelado|Error|Información|Detalles|Volver|Siguiente|Anterior|Cerrar|Abrir|Confirmar|Sí|No|Todos|Filtrar|Exportar|Importar|Archivo|Nuevo|Activo|Inactivo|Éxito|Hola|Bienvenido|Gracias|Mensaje|Tipo|Referencia|Creado|Creada|Resuelto|Resuelta|Marcar|Reunión|Llamada|Email|WhatsApp|Registro|Verificar|Verificado|Calidad|Factura|Facturación|Pagos|Pagar|Plan|Plan|Suscripción|Actualizar|Finalizar|Iniciar|Cerrar|Sesión|Registrarse|Contraseña|Correo|Recordarme|Olvidaste|Restablecer|Enlace|Ventas|Ventas|Resumen|Total|Subtotal|Importe|Fecha|Cantidad|Disponible|Capacidad|Libre|Ocupado|Asignar|Asignado|Responsable|Propietario|Operador|Miembros|Organización|Perfil|Cerrar)\b/g
];

const SPANISH_PHRASES = [
    'Aún no hay', 'Crear', 'Editar', 'Eliminar', 'Guardar cambios', 'Cargando datos',
    'No se pudo', 'Se ha', 'Error al', 'Por favor', '¿Estás seguro', 'Confirmar eliminación',
    'Datos de contacto', 'Información adicional', 'Preferencias del coche', 'Datos del',
    'Todos los', 'Sin resultados', 'Página', 'de', 'Anterior', 'Siguiente', 'Cerrar',
    'Mostrando', 'resultados', 'No hay datos', 'Aún no', 'Tu', 'Mis', 'El', 'La', 'Los', 'Las',
    'Iniciar sesión', 'Cerrar sesión', 'Registrarse', 'Recordar', 'Contraseña', 'Verificación',
    'Cargando', 'Guardando', 'Enviando', 'Procesando', 'Completado', 'Cancelado'
];

function checkVueFile(filePath, validKeys) {
    const content = fs.readFileSync(filePath, 'utf8');
    const issues = [];
    const lines = content.split('\n');

    lines.forEach((line, idx) => {
        const lineNum = idx + 1;

        // Detectar texto en atributos :placeholder, :title, etc. que no use t()
        const attrMatch = line.match(/(?:placeholder|title|label|alt|content)=["']([^"']+)["']/);
        if (attrMatch) {
            const text = attrMatch[1].trim();
            if (text.length > 2 && !text.startsWith(':') && !text.startsWith('t(') &&
                SPANISH_PATTERNS.some(p => p.test(text)) || SPANISH_PHRASES.some(p => text.includes(p))) {
                issues.push({
                    line: lineNum,
                    type: 'Atributo hardcodeado',
                    text: text,
                    fullLine: line.trim()
                });
            }
        }

        // Detectar texto entre etiquetas >...< que no use {{ t() }}
        const textMatch = line.match(/>\s*([^<>{]+?)\s*</);
        if (textMatch) {
            const text = textMatch[1].trim();
            if (text.length > 2 && !text.includes('{{') &&
                (SPANISH_PATTERNS.some(p => p.test(text)) || SPANISH_PHRASES.some(p => text.includes(p)))) {
                issues.push({
                    line: lineNum,
                    type: 'Texto hardcodeado',
                    text: text,
                    fullLine: line.trim()
                });
            }
        }

        // Detectar texto en interpolación {{ "..." }} o {{ '...' }}
        const interpMatch = line.match(/\{\{\s*["']([^"']+)["']/);
        if (interpMatch) {
            const text = interpMatch[1].trim();
            if (text.length > 2 &&
                (SPANISH_PATTERNS.some(p => p.test(text)) || SPANISH_PHRASES.some(p => text.includes(p)))) {
                issues.push({
                    line: lineNum,
                    type: 'Interpolación hardcodeada',
                    text: text,
                    fullLine: line.trim()
                });
            }
        }
    });

    return issues;
}

// Obtener todos los archivos Vue
function getVueFiles(dir) {
    const files = [];
    const items = fs.readdirSync(dir, { withFileTypes: true });
    for (const item of items) {
        const fullPath = path.join(dir, item.name);
        if (item.isDirectory()) {
            files.push(...getVueFiles(fullPath));
        } else if (item.name.endsWith('.vue')) {
            files.push(fullPath);
        }
    }
    return files;
}

// Main
const validKeys = loadKeys();
const vueFiles = getVueFiles(VIEWS_DIR);

console.log('=== REPORTE PÁGINA POR PÁGINA ===\n');
console.log(`Claves de traducción cargadas: ${validKeys.size}`);
console.log(`Archivos Vue a revisar: ${vueFiles.length}\n`);

let totalIssues = 0;
let filesWithIssues = 0;

vueFiles.sort().forEach(file => {
    const relativePath = path.relative(VIEWS_DIR, file);
    const issues = checkVueFile(file, validKeys);

    if (issues.length > 0) {
        filesWithIssues++;
        totalIssues += issues.length;
        console.log(`\n📄 ${relativePath}`);
        console.log(`   ${issues.length} texto(s) sin traducir:`);
        issues.forEach(issue => {
            console.log(`   L${issue.line} [${issue.type}]: "${issue.text}"`);
        });
    }
});

console.log('\n' + '='.repeat(60));
console.log(`RESUMEN:`);
console.log(`  Archivos revisados: ${vueFiles.length}`);
console.log(`  Archivos con problemas: ${filesWithIssues}`);
console.log(`  Total textos sin traducir: ${totalIssues}`);
console.log('='.repeat(60));

if (totalIssues > 0) {
    process.exit(1);
} else {
    console.log('\n✅ No se detectaron textos sin traducir');
}
