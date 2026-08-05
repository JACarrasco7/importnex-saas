# Documentación — ImportnexCore

## 📂 Estructura

```
docs/
├── README.md               ← este índice
├── BRAND.md                ← paleta estoril/asphalt/platinum + guía visual
├── PLAN_MARKETPLACE.md     ← lógica del marketplace público
├── ARQUITECTURA_VISTAS.md  ← reparto público/privado, rutas, layouts, flujos
├── claude/
│   ├── README.md           ← flujo Claude ↔ ImportnexCore
│   └── CONTRATO_JSON.md    ← formato exacto del JSON de valoración
├── deploy/
│   └── README.md           ← cómo desplegar y comando útiles
└── planes/
    └── *.md                ← planes antiguos (archivo histórico)
```

## 🔗 Enlaces rápidos

| Para | Archivo |
|---|---|
| Claude (cómo generar y subir) | [claude/README.md](claude/README.md) |
| Contrato JSON | [claude/CONTRATO_JSON.md](claude/CONTRATO_JSON.md) |
| Desplegar cambios | [deploy/README.md](deploy/README.md) |
| Arquitectura de vistas (público/privado) | [ARQUITECTURA_VISTAS.md](ARQUITECTURA_VISTAS.md) |
| Manual de marca | [BRAND.md](BRAND.md) |
| Plan del marketplace | [PLAN_MARKETPLACE.md](PLAN_MARKETPLACE.md) |

## 📋 Flujo resumido

1. **Claude investiga** coche → genera ZIP con `informe.json` + fotos + PDFs
2. **Usuario sube** ZIP en `https://jjimportmotors.on-forge.com/cars/import-valuation` → pestaña "Subir ZIP"
3. **Sistema importa** todo automáticamente: coche, fotos, documentos
