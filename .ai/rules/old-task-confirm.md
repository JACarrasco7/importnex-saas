---
glob: 'app/Models/Task.php,database/migrations/**tasks*'
title: 'old_task=1 requiere confirmación'
---

NUNCA marcar `old_task=1` en tasks visibles en calendario aunque `date_end` esté en pasado. Preguntar siempre antes de cambios en DB que afecten `old_task`.
