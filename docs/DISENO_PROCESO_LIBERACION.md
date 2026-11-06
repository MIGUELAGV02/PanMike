# 🥖 Documento de Diseño del Proceso de Liberación CI/CD
## Proyecto: Sistema de Inventario "MikePan" (Laravel 11 & SQLite)
### Actividad: Proceso de Entrega Continua y Liberación de Versiones (Unidad II)

**Datos del Estudiante:**
- **Estudiante / Usuario GitHub:** `MIGUELAGV02`
- **Correo Electrónico:** `al222210644@gmail.com`
- **Repositorio:** [https://github.com/MIGUELAGV02/PanMike](https://github.com/MIGUELAGV02/PanMike)

---

## 1. Introducción y Objetivo
El propósito de este documento es definir y formalizar el flujo de **Integración Continua y Entrega Continua (CI/CD)** para el sistema de inventario de la panadería **"MikePan"**. 

El objetivo es automatizar la validación del código fuente, asegurar la calidad mediante pruebas unitarias y de integración sobre SQLite, y generar un **artefacto de liberación comprimido (`laravel-release.tar.gz`)** listo para ser desplegado en el ambiente de preproducción sin intervención manual ni inclusión de credenciales sensibles.

---

## 2. Requerimientos del Proyecto y Dependencias
El sistema **MikePan** se basa en una arquitectura ligera y autocontenida:

### 2.1 Requisitos de Runtime
- **PHP**: 8.2 o superior (con extensiones `pdo_sqlite`, `sqlite3`, `mbstring`, `curl`, `xml`, `bcmath`, `fileinfo`, `zip`).
- **Motor de Base de Datos**: SQLite 3 (almacenado localmente en `database/database.sqlite`).
- **Servidor Web**: Nginx o Apache con soporte para PHP-FPM.
- **Herramientas de empaquetado**: `tar`, `gzip`.

### 2.2 Componentes del Proyecto en la Versión Liberable
Para que el artefacto funcione correctamente en preproducción, debe contener:
- **Código de la aplicación**: `app/`, `bootstrap/`, `config/`, `database/` (migraciones y seeders), `public/`, `resources/`, `routes/`.
- **Dependencias de producción (`vendor/`)**: Generadas exclusivamente con `composer install --no-dev --optimize-autoloader`.
- **Archivos de arranque**: `artisan`.
- **Scripts de despliegue**: `setup-preprod.sh`, `.env.preprod.example`.

### 2.3 Exclusiones Críticas de Seguridad
Bajo ninguna circunstancia el artefacto empaquetado debe incluir:
- Repositorio Git (`.git/`, `.gitignore`, `.github/`).
- Archivos de entorno y secretos (`.env`, `.env.*`, credenciales).
- Archivos de desarrollo frontend (`node_modules/`).
- Registros y logs temporales (`storage/logs/*`).
- Base de datos local o de pruebas (`database/database.sqlite` con datos dummy de CI).

---

## 3. Estrategia de Versionado Semántico (SemVer)
El proyecto adopta la convención internacional **Semantic Versioning 2.0.0** (`vMAJOR.MINOR.PATCH`):

| Componente | Formato | Criterio de Incremento | Ejemplo en MikePan |
| :--- | :--- | :--- | :--- |
| **MAJOR** | `vX.0.0` | Cambios incompatibles con versiones anteriores (breaking changes en base de datos o arquitectura). | `v1.0.0` (Lanzamiento inicial de MikePan), `v2.0.0` (Cambio estructural de BD). |
| **MINOR** | `v1.X.0` | Nuevas funcionalidades retrocompatibles (ej. módulo de categorías de pan, reportes de ventas). | `v1.1.0` (Módulo de categorías de panadería). |
| **PATCH** | `v1.0.X` | Corrección de errores y bugs sin alterar funcionalidad existente (hotfixes, parches). | `v1.0.1` (Ajuste en validación de precio decimal). |

### 3.1 Procedimiento de Etiquetado (Git Tagging)
Para disparar el workflow de liberación oficial, el desarrollador o release manager crea una etiqueta anotada firmada en la rama `main`:

```bash
# Asegurar rama principal sincronizada
git checkout main
git pull origin main

# Crear tag anotado con descripción del release
git tag -a v1.0.0 -m "Release v1.0.0: Módulo inicial de inventario de MikePan"

# Publicar el tag hacia GitHub para activar el pipeline
git push origin v1.0.0
```

---

## 4. Criterios de Aceptación y Calidad (Quality Gates)
Antes de que el artefacto sea empaquetado y publicado, el pipeline de GitHub Actions evalúa obligatoriamente los siguientes **Quality Gates**:

1. **Validación de Integridad de Dependencias**:
   - `composer validate --strict` debe retornar código 0, verificando que `composer.json` y `composer.lock` se encuentren perfectamente sincronizados.
2. **Entorno de Pruebas Aislado**:
   - La base de datos de pruebas utiliza SQLite en memoria (`DB_DATABASE=:memory:`), garantizando pruebas idempotentes e independientes del almacenamiento físico.
3. **Ejecución Exitosa del 100% de Pruebas Automatizadas**:
   - `php artisan test` debe aprobar todas las pruebas unitarias y funcionales (`Unit` y `Feature`).
   - Se validan endpoints de vista web (`/inventory`), respuestas API JSON, validaciones de campo y operaciones Eloquent del modelo `Product`.
4. **Purga de Dependencias de Desarrollo**:
   - Reinstalación forzada con `--no-dev` para erradicar herramientas de test (`phpunit`, `faker`, `mockery`) del artefacto final.
5. **Generación del Paquete Comprimido**:
   - Empaquetamiento estricto mediante `tar` con exclusiones explícitas de seguridad.

---

## 5. Diagrama de Flujo del Pipeline CI/CD (Mermaid)

```mermaid
flowchart TD
    subgraph DESARROLLADOR["👨‍💻 Fase de Desarrollo & Versionado"]
        A[Commit & Push a 'main' o Creación de Git Tag 'v*.*.*'] --> B[GitHub Repository]
    end

    subgraph CI_PIPELINE["⚙️ GitHub Actions Workflow (release.yml)"]
        B --> C[Disparo del Evento push / tag]
        C --> D["1. Checkout del Código (actions/checkout@v4)"]
        D --> E["2. Configurar PHP 8.2 & SQLite (shivammathur/setup-php@v2)"]
        E --> F["3. Validar composer.json (--strict)"]
        F --> G["4. Cache & composer install (con dependencias de test)"]
        G --> H["5. Preparar ambiente (cp .env.example, key:generate)"]
        H --> I["6. Ejecutar pruebas automatizadas (php artisan test)"]
        
        I -->|Fallo en pruebas| J[❌ Pipeline Abortado / Notificación]
        I -->|Pruebas Exitosas| K["7. Limpieza de archivos sensibles (.env) & composer install --no-dev"]
        K --> L["8. Generar artefacto de liberacion (tar -czf laravel-release.tar.gz)"]
        L --> M["9. Publicar artefacto (actions/upload-artifact@v4, retención 7 días)"]
    end

    subgraph PREPRODUCCION["🚀 Despliegue en Ambiente de Preproducción"]
        M --> N[Descarga de laravel-release.tar.gz]
        N --> O[Extracción en directorio web del servidor]
        O --> P[Ejecución de ./setup-preprod.sh]
        P --> Q[Configuración de .env de Preproducción]
        P --> R[touch database.sqlite & php artisan migrate --force]
        P --> S[Asignación de permisos www-data 775/664]
        P --> T[Optimización de cachés: config, route, view]
        T --> U[✅ MikePan Disponible para Pruebas de Aceptación (QA)]
    end

    style DESARROLLADOR fill:#fef3c7,stroke:#d97706,stroke-width:2px;
    style CI_PIPELINE fill:#e0f2fe,stroke:#0284c7,stroke-width:2px;
    style PREPRODUCCION fill:#dcfce7,stroke:#16a34a,stroke-width:2px;
```
