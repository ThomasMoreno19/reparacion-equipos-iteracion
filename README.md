# Sistema de reparación en Laravel

Migración del proyecto PHP original a Laravel con responsabilidades separadas:

- `app/Domain`: casos de uso y contratos de negocio.
- `app/Infrastructure/Persistence`: implementación Eloquent de repositorios.
- `app/Http/Controllers`: entrada HTTP y validación.
- `resources/views`: interfaz Blade.
- `public/css`: estética oscura y dorada responsive.

## Funcionalidades

- Consulta pública por CUIL/DNI o dominio/número de serie.
- Consulta por empresa y URL directa `/{empresa}/{movimiento}/{equipo}`.
- Contraseña por equipo usando hashes bcrypt, sin exponerlos.
- Login administrativo con sesión Laravel y protección CSRF.
- Alta de empresas y subida validada de logos.
- Importación CSV transaccional con reporte de filas inválidas.

## Base de datos existente

El proyecto reutiliza las tablas actuales `Empresa`, `Equipo` y `Usuario`. Copia `.env.example` a `.env` y completa la conexión MySQL. No se copian credenciales del proyecto anterior.

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

No ejecutes migraciones destructivas contra la base existente. Primero realiza una copia de seguridad y verifica columnas y tipos.

## Hostinger

Configura el document root del dominio hacia `laravel-app/public` o coloca el contenido de esa carpeta en `public_html` y conserva el resto del proyecto fuera de `public_html`. Configura PHP 8.3 o superior, extensión PDO MySQL, Mbstring, Fileinfo, OpenSSL y permisos de escritura en `storage` y `bootstrap/cache`.

En producción usa `APP_DEBUG=false` y credenciales nuevas. La contraseña que existía en los archivos PHP originales debe rotarse.
