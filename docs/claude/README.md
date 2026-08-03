# Claude ↔ ImportnexCore

## Flujo completo

```
Claude investiga coche → genera ZIP → usuario sube en navegador → sistema importa todo
```

## Para Claude — ZIP

Genera un ZIP con esta estructura y guárdalo en `JJImportMotors\laravel\export\`:

```
coche_[marca]-[modelo]-[id].zip
├── informe.json          ← contrato schema_version:1 (OBLIGATORIO)
├── manifest.json         ← opcional: títulos, orden, tipos
├── fotos/*.jpg           ← opcional: se cargan a la galería
├── documentos/*.pdf      ← opcional: se adjuntan al expediente
└── publicidad/*.pdf      ← opcional: PDFs para cliente
```

El contrato JSON completo está en `CONTRATO_JSON.md`.

## Para Claude — JSON suelto (alternativa)

Si solo generas el JSON del informe, el usuario puede:
1. Abrir https://jjimportmotors.on-forge.com/cars/import-valuation
2. Pestaña **"Pegar JSON"** → pegar → Importar
3. O pestaña **"Subir archivo"** → seleccionar .json
4. O pestaña **"Leer del servidor"** → si ya está en `storage/app/importnex/import/`

## Emparejamiento

El sistema decide si CREA o ACTUALIZA:
1. Si el JSON trae **VIN** y existe → ACTUALIZA
2. Si no, busca por **URL del anuncio** → ACTUALIZA si coincide
3. Si no → CREA nuevo

## Endpoint API (backup)

```
POST https://jjimportmotors.on-forge.com/api/import-valuation
Header: X-Import-Token: <token>
```

## Al final de cada investigación, dile al usuario

> "ZIP listo en `laravel\export\[nombre].zip`.
> Súbelo en https://jjimportmotors.on-forge.com/cars/import-valuation → pestaña Subir ZIP"

## Bugs conocidos resueltos

- **IEDMT**: el contrato debe incluir `costes.pvp_nuevo` (precio del coche nuevo sin depreciar)
- **Honorarios**: `professional_fees` ahora suma `honorarios + gestoria`
- **Fotos**: se descargan con User-Agent y se guardan en `storage/app/public/cars/{id}/photos/`
