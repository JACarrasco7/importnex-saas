---
paths:
  - public/sw.js
---

# Public

## PWA Service Worker — nunca cachear rutas autenticadas
El fetch handler SOLO cachea assets estáticos (/build/, /img/, /manifest.json) y la página /offline. Rutas autenticadas (/cars/*, /billing/*, /admin/*, /subscriptions/*, /valuations/*, /imports/*, /clients/*, /settings/*) pasan directo sin SW para evitar 404/503 servidos desde cache local cuando el server real responde distinto. Bumpear CACHE_NAME al modificar sw.js y usar `?v=N` en el register para forzar reload.
