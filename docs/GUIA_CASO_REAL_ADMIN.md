# 🧪 Guía práctica — Caso real completo (admin)

> Para probar TODO el flujo de hoy: QR de captación → tracking → contrato → PDF firmado.
> Tú eres el admin y sigues los pasos en orden. Marca cada casilla al verificar.
> Versión: 2026-08-21 · App local: `http://127.0.0.1:8000` · Producción: `https://jjimportmotors.on-forge.com`

## ✅ Estado de la validación (21-ago-2026)

Las **6 etapas se probaron de punta a punta** en local y todo funciona. Se corrigieron durante la prueba:

| Bug encontrado | Fix (commit `67a6e6e`) |
|---|---|
| Compartir tracking con email → **500 "No hint path defined for [mail]"** | `tracking-shared.blade.php` reescrito en HTML plano (sin `mail::`) |
| Ficha del coche mostraba el **contacto como JSON crudo** (`{"email":...}`) | `clientContactDisplay` parsea el JSON en `Show.vue` |
| Modal compartir no **auto-rellenaba el email** del cliente | `clientEmailFromContact` extrae de `contact_info` |

> ⚠️ Los 2 fixes de `Show.vue` necesitan `npm run build` (lánzalo tú). El del email (Blade) ya está activo.
> En producción ya está desplegado el commit `67a6e6e`.

---

## El caso

> 👤 Cliente pide: **"VW Golf 7.5 TCR, 25.000 € máx, 100.000 km, 2018+, full, blanco o gris."**

El flujo completo tiene **6 etapas**. Cada una termina con un **entregable** que debes comprobar.

---

## 🟦 ETAPA 1 — Captación (el QR de los folletos)

**Objetivo:** el cliente llega a la web y deja su solicitud.

1. Abre `GET /request/jj-import-motors` → debe salir el **formulario público** de solicitud.
   - ⚠️ Si sale 404 → la org no es pública (`is_public=false` en BD).
2. Rellénalo como cliente (nombre, email, presupuesto 25.000, modelo "Golf 7.5 TCR") y envía.
   - ✅ Verificado: te redirige a `/request/jj-import-motors/success`.
3. Comprueba que la solicitud entró en el panel: `GET /car-requests` → debe estar en estado `pendiente`.

**Entregable:** `car_request` creada. ✅

---

## 🟦 ETAPA 2 — Vinculación (coche ↔ cliente)

**Objetivo:** el coche queda asignado al cliente (necesario para el contrato).

1. Importa o crea el coche (ej. Golf 7.5 TCR) desde `GET /cars/create` o por ZIP.
2. Abre la ficha del coche `GET /cars/{car}`.
3. En la pestaña **Cliente/expediente**, usa "Vincular solicitud" (match con la `car_request`).
   - ✅ Verificado: el bloque cliente muestra nombre + solicitud vinculada.
4. El coche debe quedar en un estado del proceso: `Purchased` / `In_transit` / `Processing` / `Pending review` / `Verifying` / `Delivered`.
   - ⚠️ Sin cliente vinculado → el botón "Generar contrato" sale deshabilitado (a propósito).

**Entregable:** `cars.client_id` asignado + estado trackeable. ✅

---

## 🟦 ETAPA 3 — Compartir seguimiento (QR → /tracking/{token})

**Objetivo:** generar el enlace privado de seguimiento para el cliente.

1. En la ficha del coche, panel **"Compartir seguimiento con el cliente"** → botón "Compartir seguimiento".
2. En el modal: email del cliente (opcional) + fecha estimada de entrega → "Generar enlace".
   - ✅ Verificado: aparece la URL `http://.../tracking/XXXXX` con botón copiar.
3. Comprueba el panel: badge "Compartido" + contador de vistas + fecha.
4. Abre la URL `/tracking/{token}` (en ventana de incógnito para simular cliente):
   - ✅ Hero con foto del coche (solo `exterior/interior/engine`).
   - ✅ Timeline de 6 hitos ("Depósito pagado", "Transporte contratado"...).
   - ✅ Resumen de inspecciones (% global + por sección).
   - ✅ "Próximo paso" humanizado.
   - ✅ Gestor (owner de la organización) + teléfono/email.
   - ⚠️ Verifica que **NO** se vea: precio de compra, VIN, margen, comparables, IEDMT.
5. Página con `noindex` (no aparece en Google).
6. Si enviaste email → llega `TrackingSharedMail` con el botón "Seguir mi coche".

**Entregable:** URL `/tracking/{token}` pública y limpia. ✅

> **Admin extras:** botones "Rotar token" (invalida el enlace viejo) y "Revocar enlace" (deja de ser visible).

---

## 🟦 ETAPA 4 — Generar contrato (QR → /contrato/{token})

**Objetivo:** crear el contrato vinculante para que el cliente lo firme.

1. En la ficha del coche, panel **"Contrato de servicio"** → botón "Generar contrato".
   - ⚠️ Si no hay cliente vinculado → no se genera (error controlado).
2. ✅ Verificado: aparece la URL `http://.../contrato/XXXXX` con botón copiar (badge "Pendiente de firma").
3. El texto del contrato se genera con:
   - Cliente (nombre del `client`), vehículo (marca/modelo/año/VIN).
   - **Precio total real** (`calculateTotalCost()`: compra + gastos + honorarios).
   - **Honorarios reales** (`professional_fees` del coche), no 1.500 fijo.
4. Comprueba en BD (opcional): `contract_acceptances` con `contract_hash=''`, `accepted_at=NULL`.

**Entregable:** URL `/contrato/{token}` pendiente de firma. ✅

---

## 🟦 ETAPA 5 — El cliente firma (LSSI / eIDAS)

**Objetivo:** click vinculante con registro de IP + fecha + hash del texto exacto.

1. Abre `/contrato/{token}` en incógnito (simula cliente).
   - ✅ Verificado: 10 cláusulas legibles + checkbox + botón "Aceptar y firmar".
   - El texto muestra: Prestador, Cliente, Vehículo, Honorarios y Precio total reales.
2. Escribe nombre + DNI/NIE en el formulario y marca el checkbox.
   - ⚠️ Sin checkbox → el JS muestra error "Debes marcar la casilla".
3. Click **"Aceptar y firmar electrónicamente"** → botón "Generando documento firmado...".
4. ✅ Verificado: te redirige a `/contrato/{token}/pdf` (descarga el PDF).
5. El PDF incluye:
   - Datos del firmante (nombre/DNI tal y como los tecleó).
   - **Hash SHA256** del texto firmado (al pie).
   - IP + fecha de firma + versión del contrato.
   - QR al `/tracking/{token}` si el coche tiene tracking compartido.
6. Recarga `/contrato/{token}` → ahora muestra **"Contrato firmado correctamente"** + botón descargar.
   - ⚠️ Intentar firmar de nuevo → 409 (idempotente).
7. (Opcional) Verifica en BD: `accepted_at`, `accepted_ip`, `user_agent`, `contract_hash` de 64 chars, y que `snapshot` quedó congelado con los datos del firmante.

**Entregable:** `contract_acceptances` con hash + IP + fecha. ✅

---

## 🟦 ETAPA 6 — Seguimiento en marcha (admin actualiza)

**Objetivo:** el cliente ve el progreso real.

1. Admin marca hitos en la ficha del coche: pestaña **Checklist** → completar hitos (ej. "Depósito pagado").
2. El cliente recarga `/tracking/{token}` → ve el hito completado con fecha.
3. Cambia el estado del coche (ej. `In_transit`) → sigue siendo trackeable.
   - ⚠️ Si pasa a un estado NO trackeable (ej. `Located`) → la URL da 404.
4. Si quieres invalidar el enlace: "Revocar enlace" en el panel → la URL da 404.

**Entregable:** tracking refleja el progreso en tiempo real. ✅

---

## 🧾 Resumen de entregables (checklist final)

| # | Etapa | Entregable | URL |
|---|---|---|---|
| 1 | Captación | `car_request` creada | `/request/jj-import-motors` |
| 2 | Vinculación | `cars.client_id` + estado trackeable | `/cars/{car}` |
| 3 | Compartir | `/tracking/{token}` público | `/tracking/{token}` |
| 4 | Contrato | `/contrato/{token}` pendiente | `/contrato/{token}` |
| 5 | Firma | PDF firmado con hash + IP + QR | `/contrato/{token}/pdf` |
| 6 | Progreso | hitos actualizados en tracking | `/tracking/{token}` |

---

## ⚠️ Errores esperados (y su causa)

| Síntoma | Causa | Solución |
|---|---|---|
| `/request/jj-import-motors` → 404 | org `is_public=false` | `UPDATE organizations SET is_public=1 WHERE slug='jj-import-motors'` |
| Botón "Generar contrato" deshabilitado | coche sin `client_id` | vincula la solicitud (Etapa 2) |
| Compartir da "estado no trackeable" | status fuera de la lista | pon `Purchased`/`In_transit`/... |
| `/tracking` muestra datos internos | — | avísame (bug de fuga) |
| `/contrato/{token}/pdf` → 500 en local | falta Chrome/Browsershot | en producción Forge sí lo tiene; en local usa el test o Chrome instalado |
| El PDF no abre el QR | coche sin tracking compartido | comparte tracking primero (Etapa 3) |
| Compartir con email → 500 "No hint path [mail]" | ~~blade usaba `mail::` sin vendor:publish~~ | **ARREGLADO en `67a6e6e`** (HTML plano). Si lo ves de nuevo, avísame |

---

## 🧪 Datos demo (si no quieres crear nada)

En tu BD local quedaron:
- Coche `#383` Audi A3 · cliente "Cliente Demo" · status `Purchased` · con contrato firmado y tracking.
- URLs demo:
  - Tracking: `/tracking/hXRjblFyQ8eTaOnjfresRbKHNNBxysmTbSYRIGwG`
  - Contrato (firmado): `/contrato/DYke3c5nK6aAQ35dGFVpB9Dj3tGfOZ9PdZ1nJNQYm5koc6oC`

Bórralos cuando termines de practicar.
