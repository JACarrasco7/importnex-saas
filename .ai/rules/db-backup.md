---
glob: 'database/migrations/**'
title: 'Backup antes de migraciones destructivas'
---

SIEMPRE hacer backup antes de cualquier migración que altere/drop columnas o haga DELETE. Confirmar con el usuario antes de ejecutar `php artisan migrate` en producción.
