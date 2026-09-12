# HeidiSQL para Forge - Importnex

## 🔐 Conexión vía SSH Tunnel (RECOMENDADO)

### Paso 1: Iniciar el túnel SSH

Doble click en: **`forge-mysql-tunnel.bat`** (en el escritorio o en `C:\laragon\www\importnexcore\`)

Verás una ventana que dice:
```
Importnex Forge - MySQL Tunnel
Local: 127.0.0.1:3307
Remoto: 168.144.6.105:3306
```

**NO CIERRES esta ventana** mientras uses HeidiSQL.

### Paso 2: Configurar HeidiSQL

| Campo | Valor |
|-------|-------|
| **Network type** | MySQL (TCP/IP) |
| **Hostname / IP** | `127.0.0.1` |
| **User** | `forge` |
| **Password** | `z5sAhm2QZfCOYvIel0hU` |
| **Port** | `3307` |
| **Databases** | (dejar vacío para ver todas) |
| **Comment** | `Forge Production` |

Click **"Open"**

## 📊 Tablas importantes

Una vez conectado, las tablas principales son:

| Tabla | Contenido |
|-------|-----------|
| `users` | Usuarios (carra, jmepegounpeo) |
| `organizations` | JJ Import Motors |
| `cars` | Coches |
| `clients` | Clientes |
| `contacts` | Contactos |
| `client_contact_logs` | Logs de llamadas |
| `car_photos` | Fotos de coches |
| `alerts` | Alertas |
| `subscriptions` | Suscripciones de Stripe |

## ⚠️ Comandos útiles desde HeidiSQL

```sql
-- Ver usuarios
SELECT id, email, name, role FROM users;

-- Ver coches
SELECT id, brand, model, year, status FROM cars;

-- Reset password de un usuario
UPDATE users SET password = '$2y$12$...' WHERE email = 'carra@jjimportmotors.com';
```

(Para regenerar password usar: `php artisan tinker` con `Hash::make('newpassword')`)

## 🔄 Para cerrar el túnel

Click en la ventana del túnel → `Ctrl+C`

## ⚠️ Notas

- **NO** abrir el puerto 3306 públicamente - es inseguro
- El túnel usa SSH encryption - seguro
- Si recibes "Connection refused" - el túnel no está abierto, ejecuta el .bat
- Si recibes "Access denied" - password incorrecto o DB distinta
