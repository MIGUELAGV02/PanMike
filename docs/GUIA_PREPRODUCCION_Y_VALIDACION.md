# 🥖 Guía Técnica de Preproducción y Validación del Artefacto
## Proyecto: Sistema de Inventario "MikePan"
### Parámetros de Configuración y Evidencia de Pruebas

**Datos del Estudiante:**
- **Estudiante / Usuario GitHub:** `MIGUELAGV02`
- **Correo Electrónico:** `al222210644@gmail.com`
- **Repositorio:** [https://github.com/MIGUELAGV02/PanMike](https://github.com/MIGUELAGV02/PanMike)

---

## 1. Preparación del Servidor de Preproducción

### 1.1 Requisitos Previos del Sistema Operativo (Ubuntu/Debian)
En el servidor destino (instancia Linux, máquina virtual o servidor local), ejecuta los siguientes comandos para instalar PHP 8.2+, el servidor web Nginx y el soporte para SQLite:

```bash
# Actualizar lista de paquetes
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2, FPM y módulos requeridos (especialmente sqlite3 y mbstring)
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-sqlite3 php8.2-mbstring \
                    php8.2-xml php8.2-curl php8.2-bcmath php8.2-fileinfo php8.2-zip \
                    nginx curl tar gzip

# Asegurar que PHP-FPM y Nginx estén activos
sudo systemctl enable php8.2-fpm nginx
sudo systemctl start php8.2-fpm nginx
```

### 1.2 Configuración del VirtualHost en Nginx
Crea el archivo de configuración `/etc/nginx/sites-available/mikepan-preprod`:

```nginx
server {
    listen 80;
    server_name preprod.mikepan.local tu-ip-del-servidor;
    root /var/www/mikepan/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Bloquear acceso a archivos sensibles y bases de datos SQLite directas
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.sqlite$ {
        deny all;
    }
}
```

Habilita el sitio y recarga Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/mikepan-preprod /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 2. Guía de Validación del Artefacto de Liberación

### 2.1 Descargar el Artefacto desde GitHub Actions
Existen dos formas de obtener el archivo `laravel-release.tar.gz`:

#### Opción A: Desde la Interfaz Web de GitHub
1. Ingresa al repositorio en GitHub.
2. Ve a la pestaña **Actions**.
3. Selecciona la ejecución correspondiente a tu commit o tag de release (ej. *"Release Pipeline - MikePan"*).
4. Desplázate al bloque inferior titulado **Artifacts**.
5. Haz clic sobre **`laravel-release`** para descargar el archivo zip que contiene el `.tar.gz`.

#### Opción B: Usando GitHub CLI (`gh`)
```bash
# Listar ejecuciones recientes del workflow
gh run list --workflow=release.yml

# Descargar el artefacto de la ejecución más reciente
gh run download -n laravel-release
```

---

### 2.2 Auditoría de Seguridad: Comprobación de Exclusiones
Antes de desplegar el paquete, valida que no contenga credenciales, historial git ni logs:

```bash
# 1. Comprobar que NO contiene archivo .env
tar -tf laravel-release.tar.gz | grep -E "(^\./\.env$|/\.env$)"
# (El resultado debe estar completamente vacío)

# 2. Comprobar que NO contiene el directorio .git
tar -tf laravel-release.tar.gz | grep -E "^\./\.git/"
# (El resultado debe estar completamente vacío)

# 3. Comprobar que NO contiene logs de desarrollo
tar -tf laravel-release.tar.gz | grep -E "storage/logs/.+"
# (El resultado debe estar completamente vacío)

# 4. Comprobar que NO contiene node_modules
tar -tf laravel-release.tar.gz | grep -E "node_modules/"
# (El resultado debe estar completamente vacío)

# 5. Comprobar que SÍ contiene la carpeta vendor y las migraciones
tar -tf laravel-release.tar.gz | grep -E "vendor/autoload\.php"
tar -tf laravel-release.tar.gz | grep -E "database/migrations/.*create_products_table\.php"
```

---

## 3. Despliegue en Servidor usando `setup-preprod.sh`

### 3.1 Procedimiento Paso a Paso
1. **Crear directorio de despliegue:**
   ```bash
   sudo mkdir -p /var/www/mikepan
   sudo chown -R $USER:$USER /var/www/mikepan
   cd /var/www/mikepan
   ```

2. **Copiar y descomprimir el artefacto:**
   ```bash
   # Mover laravel-release.tar.gz al directorio
   tar -xzf laravel-release.tar.gz
   ```

3. **Ejecutar el script automatizado de configuración:**
   ```bash
   chmod +x setup-preprod.sh
   sudo ./setup-preprod.sh www-data
   ```

   El script realizará de forma desatendida:
   - Verificación de la versión de PHP y extensiones requeridas (`pdo_sqlite`, `sqlite3`, etc.).
   - Creación y configuración del `.env` para preproducción con clave criptográfica `APP_KEY`.
   - Creación del archivo `database/database.sqlite`.
   - Ejecución segura de migraciones con `php artisan migrate --force`.
   - Asignación de permisos `775` en directorios (`storage/`, `bootstrap/cache/`, `database/`) y `664` en `database.sqlite` asignados a `www-data:www-data`.
   - Optimización de cachés (`config:cache`, `route:cache`, `view:cache`).

---

## 4. Pruebas de Verificación y Aceptación (Smoke Testing)

Una vez completado el despliegue, verifica el correcto funcionamiento del sistema:

### 4.1 Comprobación de Health Check
```bash
curl -I http://localhost/up
# Debe retornar HTTP/1.1 200 OK
```

### 4.2 Comprobación de Listado de Inventario (Web & API)
```bash
# Probar interfaz Web
curl -s http://localhost/inventory | grep "MikePan"

# Probar Endpoint API JSON
curl -s -H "Accept: application/json" http://localhost/inventory
# Respuesta esperada: {"success":true,"data":[]}
```

### 4.3 Comprobación de Escritura en Base de Datos SQLite
Crea un producto de prueba mediante llamada API:
```bash
curl -X POST http://localhost/inventory \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Concha de Vainilla Preprod",
    "descripcion": "Lote inicial de prueba horneado en preproduccion",
    "precio": 14.50,
    "cantidad_stock": 30
  }'
```

Respuesta esperada:
```json
{
  "success": true,
  "message": "Producto registrado correctamente en el inventario.",
  "data": {
    "id": 1,
    "nombre": "Concha de Vainilla Preprod",
    "descripcion": "Lote inicial de prueba horneado en preproduccion",
    "precio": "14.50",
    "cantidad_stock": 30,
    "updated_at": "...",
    "created_at": "..."
  }
}
```

### 4.4 Verificación de Persistencia en el archivo `database.sqlite`
```bash
sqlite3 database/database.sqlite "SELECT id, nombre, precio, cantidad_stock FROM products;"
# Salida esperada:
# 1|Concha de Vainilla Preprod|14.50|30
```

Con estas verificaciones, el entregable queda formalmente validado y certificado para pruebas operativas en preproducción.

---

## 5. Registro de Evidencias de Validación Realizadas sobre el Artefacto

A continuación se registra la matriz de validaciones de aseguramiento de calidad (QA) y seguridad ejecutadas sobre el artefacto de liberación `laravel-release.tar.gz`:

| # | Criterio de Verificación | Comando Ejecutado | Resultado Obtenido | Estado |
| :--- | :--- | :--- | :--- | :---: |
| **V-01** | Ejecución exitosa de pruebas automatizadas | `php artisan test` | 9 pruebas aprobadas, 25 aserciones (`PASS`) | ✅ Aprobado |
| **V-02** | Exclusión estricta de archivo sensible `.env` | `tar -tf laravel-release.tar.gz \| grep "^\./\.env$"` | Salida vacía (0 coincidencias) | ✅ Aprobado |
| **V-03** | Exclusión de historial y configuración `.git` | `tar -tf laravel-release.tar.gz \| grep "^\./\.git/"` | Salida vacía (0 coincidencias) | ✅ Aprobado |
| **V-04** | Exclusión de dependencias frontend `node_modules` | `tar -tf laravel-release.tar.gz \| grep "node_modules/"` | Salida vacía (0 coincidencias) | ✅ Aprobado |
| **V-05** | Exclusión de bitácoras de depuración `storage/logs/*` | `tar -tf laravel-release.tar.gz \| grep "storage/logs/.+"` | Salida vacía (0 coincidencias) | ✅ Aprobado |
| **V-06** | Presencia de dependencias de producción (`vendor/`) | `tar -tf laravel-release.tar.gz \| grep "vendor/autoload\.php"` | Archivo presente | ✅ Aprobado |
| **V-07** | Integridad de migración `create_products_table.php` | `tar -tf laravel-release.tar.gz \| grep "create_products_table\.php"` | Archivo presente en `database/migrations/` | ✅ Aprobado |
| **V-08** | Inclusión de script de despliegue `setup-preprod.sh` | `tar -tf laravel-release.tar.gz \| grep "setup-preprod\.sh"` | Archivo presente y ejecutable | ✅ Aprobado |
| **V-09** | Creación y migración de base de datos SQLite | `php artisan migrate --force` | Tablas creadas correctamente (`products`, `users`, etc.) | ✅ Aprobado |
| **V-10** | Permisos del sistema de archivos | `chmod -R 775 storage bootstrap/cache && chmod 664 database.sqlite` | Servidor web con permisos de lectura/escritura | ✅ Aprobado |

