# Riesgos mecánicos por motor

> **Cargar cuando:** Se investiga un modelo con motor conocido por tener problemas recurrentes.
> **No cargar para:** Modelos nuevos (<5 años en mercado) o motores sin historial de fallos.

---

## ⚠️ Tabla de riesgos por motor

| Motor | Problema conocido | Coste reparación | Verificación |
|---|---|---:|---|
| **DQ200** (DSG 7 velocidades seco) | Desgaste prematuro mecatrónica | 2.000-3.500 € | Historial cambio aceite cada 60k km. Si >100k km sin cambio, descartar. |
| **EA888 Gen1-2** (2.0 TSI/TFSI 2008-2014) | Consumo excesivo aceite (pistones defectuosos) | 3.000-4.500 € | Verificar recall 2011-2014. Si no se aplicó, descartar. |
| **N47/N57** (BMW diésel 2007-2015) | Cadena distribución trasera (rotura) | 2.000-3.000 € | Si >120k km sin cambio, presupuestar reparación. |
| **1.6 THP** (PSA/BMW 2007-2015) | Cadena distribución prematura | 1.500-2.500 € | Cambiar cada 80k km o 6 años. Si no hay historial, presupuestar. |
| **PHEV antiguos** (>8 años) | Degradación batería alta tensión | 5.000-12.000 € | Test capacidad (SOH >70%). Si <70%, descuento de 3.000-5.000 €. |
| **1.4 TSI** (EA111 2008-2015) | Cadena distribución elongación | 1.200-2.000 € | Si >80k km sin cambio, presupuestar. |
| **2.0 TDI** (EA189 2008-2015) | Dieselgate (software emisiones) | 0-1.500 € | Verificar si se aplicó recall. Si no, descartar. |
| **1.5 TSI** (EA211 Evo 2018+) | Consumo aceite (algunas unidades) | 1.000-2.000 € | Verificar nivel aceite en revisión. Si >0,5L/1.000km, reclamar garantía. |

---

## 🔍 Cómo verificar riesgos

**Fuentes fiables:**
1. **km77.com** → Buscar modelo + motor + "problemas" o "averías"
2. **Foros especializados** → Club奥迪, BMWFAQ, Forocoches, etc.
3. **Boletines técnicos** → Buscar TSB (Technical Service Bulletin) del fabricante
4. **Recalls oficiales** → Consultar DGT o fabricante con VIN

**Qué anotar en §8 (Riesgos y banderas):**
```
⚠️ MOTOR: EA888 Gen2 (2.0 TSI 2012). Riesgo conocido: consumo excesivo aceite.
   Verificación: Historial de mantenimiento muestra cambio de aceite cada 15k km.
   Recall 2011-2014: NO aplica (coche de 2012, fuera de rango).
   Coste preventivo: 0 € (no hay síntomas).
   
⚠️ TRANSMISIÓN: DQ200 (DSG 7 seco). Riesgo: desgaste mecatrónica.
   Verificación: Historial cambio aceite a 58k km (correcto).
   Kilometraje actual: 62k km (próximo cambio a 120k km).
   Coste preventivo: 300 € (cambio aceite a 120k km).
```

---

## 🚫 Reglas de descarte automático

**Descartar SIEMPRE si:**
- Motor **EA888 Gen1-2** sin historial de recall aplicado
- Motor **N47/N57** con >120k km sin cambio de cadena
- Motor **1.6 THP** con >80k km sin cambio de cadena
- **PHEV** con SOH batería <70%
- **DQ200** con >100k km sin cambio de aceite

**Presupuestar reparación si:**
- Motor con riesgo conocido pero historial incompleto
- Kilometraje cercano al umbral de reparación (±10k km)
- Sin síntomas actuales pero preventive maintenance due

---

## 💡 Ejemplo de uso en informe §8

```
8. RIESGOS Y BANDERAS

   ⚠️ MOTOR: 2.0 TSI EA888 Gen2 (2012)
      Riesgo: consumo excesivo aceite (pistones defectuosos)
      Verificación: Recall 2011-2014 NO aplica (coche 2012)
      Historial: Cambio aceite cada 15k km ✅
      Coste preventivo: 0 € (sin síntomas)
      → PENDIENTE: Verificar nivel aceite en revisión pre-compra

   ⚠️ TRANSMISIÓN: DSG DQ200 (7 velocidades seco)
      Riesgo: desgaste mecatrónica (fallo conocido)
      Verificación: Cambio aceite a 58k km ✅
      Kilometraje: 62k km (próximo cambio a 120k km)
      Coste preventivo: 300 € (cambio aceite a 120k km)
      → CONFIRMAR: Estado actual de la transmisión (prueba de conducción)

   ✅ RESTO: Sin riesgos mecánicos conocidos
```
