# 08 · Repositorio, instalación y despliegue

**Estado:** 🟡 necesita una decisión tuya

| ID | Prio | Hallazgo | Estado |
| --- | --- | --- | --- |
| 8.1 | P1 | `README.md` era el de Laravel por defecto | ✅ README del producto (instalación, configuración, producción) |
| 8.2 | P1 | `.env.example` sin las variables del SaaS y con `APP_NAME` de JJ | ✅ |
| 8.3 | P0 | Seeder con usuarios y contraseñas reales | ✅ (ver 02) |
| 8.4 | P1 | El repositorio mezcla el producto con material interno de JJ: estudios de mercado (`untitled/`, `docs/informes`, `JJ Import Motors/`), memoria y prompts de IA (`docs/memoria-desktop`, `docs/claude*`, `.ai/memory`), scripts de Windows (`subir-informe.*`, `forge-mysql-tunnel.bat`) | ⏳ **Decisión**: moverlo a un repositorio privado aparte antes de enseñar el código a un comprador. No lo he movido porque lo usan tus skills y su sincronización (`sync.manifest.json`) |
| 8.5 | P1 | Tres formas de desplegar a medias (Forge, Docker, Railway) | ⏳ Recomendado: dejar Forge (es el que se usa) y quitar `railway.json`; Docker solo si se ofrece autoalojado |
| 8.6 | P2 | 25 auditorías y planes antiguos en `docs/` | Aparcado: archivar en `docs/_archivo/` junto con 8.4 |
