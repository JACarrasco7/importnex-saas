# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| ** (regla transversal: TODO) | .ai/rules/business-model.md |
| ** (cambio en contexto cross-agent) | .ai/rules/context-gate.md |
| **/*.{php,vue,js} | .ai/rules/no-deps-without-approval.md |
| **/*.{php,vue,js,md} | .ai/rules/search-hygiene.md |
| .claude/skills/**, docs/SKILLS.md, docs/DOCS-INDEX.md, scripts/build-skill-zips.ps1 | .ai/rules/skills-sync.md |
| app/Console/Commands/Market*.php, app/Http/Controllers/Api/MarketApiController.php, app/Http/Controllers/Api/PublicMarketController.php, app/Http/Controllers/MercadoController.php, app/Models/Market*.php, resources/js/Pages/Mercado/**, resources/js/Pages/Public/MercadoIndex.vue, tests/Feature/MarketMercadoTest.php | .ai/rules/mercado.md |
| app/Events/**, app/Listeners/**, app/Observers/** | .ai/rules/events.md |
| app/Http/Controllers/CarController.php, app/Http/Controllers/CarKanbanController.php, app/Http/Controllers/CarMapController.php, app/Http/Controllers/PublicMarketplaceController.php, app/Http/Controllers/TripPlannerController.php, resources/js/Pages/Cars/**, resources/js/Pages/Public/MarketplaceIndex.vue, resources/js/Pages/Public/MarketplaceShow.vue | .ai/rules/vehicles.md |
| app/Http/Controllers/PublicMarketplaceController.php, resources/js/Components/{CompareBar,FinancingCalculator,WishlistButton}.vue, resources/js/Composables/useWishlist.js, resources/js/Pages/Public/** | .ai/rules/marketplace.md |
| app/Http/Middleware/*, app/Models/** | .ai/rules/multitenancy-org.md |
| app/Models/Alert.php, app/Mail/*, app/Observers/AlertObserver.php, app/Services/Alert*Dispatcher.php | .ai/rules/notifications.md |
| app/Models/Task.php, database/migrations/**tasks* | .ai/rules/old-task-confirm.md |
| app/Models/User.php, app/Http/Middleware/TelescopeAccess.php, config/permission.php | .ai/rules/auth-roles.md |
| app/Support/** | .ai/rules/support.md |
| database/migrations/** | .ai/rules/db-backup.md |
| docs/**, .github/copilot-instructions.md, .ai/rules/index.md, AGENTS.md, CLAUDE.md, app/Http/Controllers/**, app/Models/**, app/Services/**, routes/**, config/** | .ai/rules/doc-sync.md |
| package.json, vite.config.js | .ai/rules/no-frontend-build.md |
| public/sw.js | .ai/rules/public.md |
| resources/js/**, resources/views/**, tailwind.config.js | .ai/rules/brand-jj-import-motors.md |
| resources/js/i18n/**, resources/lang/** | .ai/rules/i18n-sync.md |
| resources/js/Pages/** | .ai/rules/pages.md |
| resources/views/** | .ai/rules/views.md |
| routes/web.php | .ai/rules/routes.md |

> **Sin glob en esta tabla todavía** (existen en `.ai/rules/` pero no están mapeados a un
> path): `backend`, `billing`, `deployment`, `design-system`, `frontend`, `i18n`,
> `migrations`, `multitenancy`, `testing`. Antes de fiarse solo de la tabla, correr
> `grep -rin 'glob:' .ai/rules`.
>
> ⚠️ La herramienta Boost `record-rule` **reescribe este archivo** y solo conserva las
> filas que conoce: al añadir una regla nueva, revisar `git diff .ai/rules/index.md` y
> volver a poner las filas que se hayan perdido (pasó el 13-sep-2026).
