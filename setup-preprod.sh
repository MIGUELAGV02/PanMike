#!/usr/bin/env bash
# ==============================================================================
# Script de Despliegue y Configuración de Preproducción - Panadería "MikePan"
# ==============================================================================
# Este script prepara y configura el entorno de pruebas/preproducción para la
# aplicación Laravel 11 con base de datos SQLite.
#
# Uso:
#   chmod +x setup-preprod.sh
#   sudo ./setup-preprod.sh [USUARIO_WEB]
#   Ejemplo: sudo ./setup-preprod.sh www-data
# ==============================================================================

set -euo pipefail

# Colores para salida de terminal
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # Sin color

echo -e "${BLUE}====================================================${NC}"
echo -e "${BLUE}   🥖 MikePan - Setup de Ambiente de Preproducción  ${NC}"
echo -e "${BLUE}====================================================${NC}"

# 1. Definición de usuario del servidor web (por defecto www-data en Ubuntu/Debian)
WEB_USER="${1:-www-data}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

cd "$APP_DIR"
echo -e "${YELLOW}📁 Directorio de la aplicación:${NC} $APP_DIR"
echo -e "${YELLOW}👤 Usuario del servidor web:${NC} $WEB_USER"

# 2. Verificación de Requisitos de Sistema
echo -e "\n${BLUE}[1/7] Verificando requisitos del sistema...${NC}"

if ! command -v php >/dev/null 2>&1; then
    echo -e "${RED}❌ Error: PHP no está instalado en el servidor.${NC}"
    echo "Instala PHP 8.2 o superior: sudo apt install -y php8.2-cli php8.2-fpm php8.2-sqlite3 php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo -e "${GREEN}✓ PHP instalado: v${PHP_VERSION}${NC}"

# Verificar extensiones requeridas de PHP para SQLite y Laravel
REQUIRED_EXTENSIONS=("pdo_sqlite" "sqlite3" "mbstring" "xml" "curl" "fileinfo" "bcmath")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -qi "^${ext}$"; then
        echo -e "${RED}❌ Falta la extensión PHP requerida: ${ext}${NC}"
        echo "Por favor instálala mediante el gestor de paquetes de tu distribución."
        exit 1
    fi
done
echo -e "${GREEN}✓ Todas las extensiones de PHP requeridas están presentes.${NC}"

# 3. Configuración de Variables de Entorno (.env)
echo -e "\n${BLUE}[2/7] Configurando archivo de variables de entorno (.env)...${NC}"
if [ ! -f .env ]; then
    if [ -f .env.preprod.example ]; then
        echo -e "${YELLOW}Creando .env a partir de .env.preprod.example...${NC}"
        cp .env.preprod.example .env
    elif [ -f .env.example ]; then
        echo -e "${YELLOW}Creando .env a partir de .env.example...${NC}"
        cp .env.example .env
        sed -i 's/APP_ENV=local/APP_ENV=preproduction/g' .env
        sed -i 's/APP_DEBUG=true/APP_DEBUG=false/g' .env
    else
        echo -e "${RED}❌ No se encontró .env.preprod.example ni .env.example.${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ Archivo .env creado para preproducción.${NC}"
else
    echo -e "${GREEN}✓ Archivo .env existente detectado. Conservando configuración.${NC}"
fi

# Generar APP_KEY si aún no está asignada
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY= " .env || ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${YELLOW}Generando clave de cifrado de aplicación (APP_KEY)...${NC}"
    php artisan key:generate --force
fi

# 4. Creación y Aseguramiento de la Base de Datos SQLite
echo -e "\n${BLUE}[3/7] Preparando base de datos SQLite...${NC}"
mkdir -p database
if [ ! -f database/database.sqlite ]; then
    echo -e "${YELLOW}Creando archivo database/database.sqlite...${NC}"
    touch database/database.sqlite
    echo -e "${GREEN}✓ Archivo database.sqlite creado vacio.${NC}"
else
    echo -e "${GREEN}✓ Archivo database.sqlite existente detectado.${NC}"
fi

# 5. Ejecución de Migraciones de Base de Datos
echo -e "\n${BLUE}[4/7] Ejecutando migraciones de base de datos...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migraciones aplicadas con éxito.${NC}"

# 6. Optimización de Caché de Laravel
echo -e "\n${BLUE}[5/7] Optimizando cachés de Laravel para Preproducción...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Configuración, rutas y vistas cacheadas para alto rendimiento.${NC}"

# 7. Asignación de Permisos del Servidor Web
echo -e "\n${BLUE}[6/7] Ajustando permisos de almacenamiento, caché y SQLite...${NC}"
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Cambiar propiedad al usuario del servidor web si se ejecuta con privilegios sudo
if [ "$(id -u)" -eq 0 ]; then
    echo -e "${YELLOW}Asignando propiedad al usuario '${WEB_USER}'...${NC}"
    chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache database
    
    # 775 en directorios para que tanto el usuario como el grupo puedan escribir
    find storage bootstrap/cache database -type d -exec chmod 775 {} \;
    
    # 664 en archivos para permitir lectura/escritura (esencial para SQLite y logs)
    find storage bootstrap/cache database -type f -exec chmod 664 {} \;
    chmod 664 database/database.sqlite
    echo -e "${GREEN}✓ Permisos de sistema asignados correctamente.${NC}"
else
    echo -e "${YELLOW}⚠️ Advertencia: No se ejecutó como superusuario (root).${NC}"
    echo "Asegurando permisos locales para lectura/escritura:"
    chmod -R 775 storage bootstrap/cache database
    chmod 664 database/database.sqlite
fi

# 8. Verificación de Estado
echo -e "\n${BLUE}[7/7] Verificando estado final...${NC}"
php artisan about || true

echo -e "\n${GREEN}================================================================${NC}"
echo -e "${GREEN}  🎉 ¡Despliegue de MikePan en Preproducción completado con éxito! ${NC}"
echo -e "${GREEN}================================================================${NC}"
echo -e "Puedes probar la aplicación en tu navegador web o mediante curl:"
echo -e "  - Web UI:    http://tu-servidor-preprod/inventory"
echo -e "  - Health:    http://tu-servidor-preprod/up"
echo -e "  - API JSON:  curl -H 'Accept: application/json' http://tu-servidor-preprod/inventory"
echo -e "================================================================\n"
