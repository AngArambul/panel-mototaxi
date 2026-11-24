# Aramovil · Panel Laravel de mototaxis

Panel web para consultar la flotilla, solicitar viajes e invitar conductores mediante el backend de Aramovil. Laravel administra las cuentas web y sus permisos. El backend Node valida las cuentas de flotilla y conserva los datos de unidades, viajes y WhatsApp.

## Requisitos

| Componente | Versión verificada |
| --- | --- |
| Laravel / Sanctum / Breeze | 12.72.0 / 4.4.0 / 2.4.2 |
| PHP / Composer | 8.2.12 / 2.10.3. Extensiones PDO y pdo_sqlite o pdo_pgsql |
| Node.js / npm / Vite | 26.13.0 / 11.20.0 / 7.3.8 |
| Base de datos | PostgreSQL 18.6 en la instalación preexistente. SQLite aislada para pruebas y demostración |
| Despacho | El proyecto vecino aramovil2-server, con Node 22.13 o posterior |

## Instalación en una carpeta nueva

Si ya tienes la carpeta `panel-mototaxi`, ábrela directamente. Con acceso al repositorio privado, puedes clonarlo con `git clone https://github.com/AngArambul/panel-mototaxi.git`.

```powershell
Set-Location 'C:/Users/angel/AndroidStudioProjects/panel-mototaxi'
composer install
npm.cmd ci
npm.cmd run build
```

Crea el archivo de configuración únicamente si es una instalación nueva. Genera la clave una sola vez. Para usar SQLite, crea una base nueva y escribe su ruta absoluta en el archivo de entorno. Esto evita usar el archivo SQLite antiguo que quedó del esqueleto Laravel.

Como alternativa a la instalación manual de dependencias y recursos, `composer setup` ejecuta Composer y npm desde los archivos de bloqueo. Inicializa la clave si crea el archivo de entorno. Configura la base de datos antes de ejecutar las migraciones por separado.

```powershell
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    php artisan key:generate
}
if (-not (Test-Path -LiteralPath 'database/local-panel.sqlite')) {
    New-Item -Path 'database/local-panel.sqlite' -ItemType File
}
Resolve-Path 'database/local-panel.sqlite'
```

En el archivo de entorno configura `DB_CONNECTION=sqlite` y `DB_DATABASE` con la ruta anterior. Usa barras `/` en Windows. Si ya usas PostgreSQL, conserva su configuración y realiza un respaldo antes de migrar. Usa una cuenta de base de datos con los permisos requeridos. Mantén separadas las credenciales de desarrollo, demostración y producción.

```powershell
php artisan migrate:status
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Abre [el panel local](http://127.0.0.1:8000). Para habilitar la flotilla, configura `DISPATCH_API_URL` con la URL base del servidor Node. En local corresponde normalmente a `http://127.0.0.1:3000`. Comprueba su endpoint `/health`. Para un despliegue, usa la URL HTTPS del backend. El panel envía las credenciales de flotilla sólo a ese servidor. Cambiar la URL de despacho durante una sesión requiere volver a autenticar la flotilla.

## Demostración en una base aislada

Usa una consola nueva en la raíz del panel. La variable `DB_URL` se reemplaza por una URL SQLite de demostración para que una conexión heredada de PostgreSQL no controle las pruebas. El seeder comprueba que el entorno y el archivo sean de demostración y solicita, sin mostrarla, una contraseña sintética de 12 a 72 bytes.

```powershell
$env:APP_ENV = 'local'
$env:APP_LOCALE = 'es'
$env:APP_URL = 'http://127.0.0.1:8000'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = (Join-Path (Get-Location).Path 'database/mototaxi-demostracion.sqlite').Replace('\', '/')
$env:DB_URL = "sqlite:///$($env:DB_DATABASE)"
$env:SESSION_DRIVER = 'file'
$env:CACHE_STORE = 'file'
$env:QUEUE_CONNECTION = 'sync'
$env:MAIL_MAILER = 'array'
if (-not (Test-Path -LiteralPath $env:DB_DATABASE)) {
    New-Item -Path $env:DB_DATABASE -ItemType File
}
php artisan config:clear
php artisan migrate
php artisan db:seed --class=DemonstrationSeeder
php artisan serve --host=127.0.0.1 --port=8000
```

Las cuentas sintéticas son `admin@mototaxi.test`, `conductor@mototaxi.test` y `pasajero@mototaxi.test`. Usa la contraseña que elegiste al sembrar. El correo de demostración se conserva en memoria. Para inspeccionar un enlace manualmente, configura un buzón SMTP de captura local, con una cuenta sintética. Las pruebas de recuperación simulan las notificaciones. No se necesita un proceso de cola para estas funciones síncronas.

## Roles, API e integraciones

- **Administrador** administra cuentas Laravel de pasajero y conductor. La baja deshabilita el acceso y conserva relaciones.
- **Conductor y pasajero** administran el perfil propio y pueden emitir tokens propios. Los tres roles necesitan una **cuenta de propietario de Node** para abrir una flotilla en el panel.
- **Sanctum** emite tokens de lectura del perfil con vigencia de 30 días. Se crean en **Tokens de acceso** validando la contraseña actual. [API y colección Postman](docs/API.md)
- **Servicios de Laravel**. El cliente HTTP transmite operaciones al backend con tiempos de espera y sin reintentar escrituras. Las notificaciones de recuperación usan el broker y el canal de correo. [Diseño técnico](docs/DOCUMENTO_TECNICO.md)
- **WhatsApp**. Mantén las credenciales y la validación de firma en Node. El webhook Laravel conserva una ruta de compatibilidad y reenvía la petición a Node. La URL de Meta puede apuntar directamente a Node.
- **Mapas**. El panel abre enlaces de Google Maps para ubicaciones conocidas. La visualización embebida, el GPS de unidades y Google Routes dependen de la aplicación Android y el backend. [Manual](docs/MANUAL_USUARIO_Y_DEMOSTRACION.md)

## Comprobar la API en local

Con el panel iniciado en el puerto 8000, consulta la ruta de perfil sin token. Debe devolver HTTP `401`.

```powershell
curl.exe -i -H "Accept: application/json" http://127.0.0.1:8000/api/user
php artisan test --filter=ProfileTokenTest
```

Para comprobar el `200`, abre [Tokens de acceso](http://127.0.0.1:8000/perfil/tokens) con una cuenta sintética activa y genera un token. En Postman, importa la colección y el entorno local enlazados abajo. Coloca el valor en la variable local `perfil_token`, ejecuta el caso de perfil autorizado y revócalo desde la misma pantalla al terminar. La respuesta autorizada contiene `id_usuario`, `nombre` y `rol`. Si iniciaste el servidor en 8001, ajusta el puerto de la URL y de `base_url` en Postman. [Contrato completo, colección y resultados HTTP](docs/API.md)

## Verificación y entrega

```powershell
php artisan route:list -v
php artisan view:cache
php artisan test
npm.cmd run build
git log --oneline
git tag --list
```

La suite define SQLite en memoria y transporte de correo de pruebas en `phpunit.xml`. Si tu consola tiene variables de base de datos definidas, abre una consola nueva o fija explícitamente `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` y un `DB_URL` vacío. En Windows, los procesos secundarios de PHPUnit requieren acceso de lectura a `vendor`.

- [Diagnóstico y trazabilidad](docs/DIAGNOSTICO_Y_TRAZABILIDAD.md)
- [Inventario de rutas](docs/RUTAS.md) y [diccionario de datos](docs/DICCIONARIO_DATOS.md)
- [Plan de pruebas](docs/PLAN_PRUEBAS.md) y [demostración](docs/MANUAL_USUARIO_Y_DEMOSTRACION.md)
- [Resultados y cierre](docs/RESULTADOS_Y_CIERRE.md), [bitácoras](docs/BITACORA_Y_ENTREGA.md) y [publicación en GitHub](docs/PUBLICAR_GITHUB.md)
- [Instalación limpia verificada desde el código versionado](docs/evidencias/instalacion-limpia.txt)

## Problemas habituales

| Síntoma | Comprueba |
| --- | --- |
| Vite no encuentra el manifiesto | Ejecuta `npm.cmd ci` y `npm.cmd run build`. |
| No existe la tabla `usuarios` | Revisa la base seleccionada y corre `php artisan migrate:status` antes de aplicar migraciones. |
| PHP busca `index.php` en la raíz | Ejecuta `php artisan serve --host=127.0.0.1 --port=8000` desde la raíz. El punto de entrada web es `public/index.php`. |
| El seeder rechaza la longitud de la contraseña | Usa de 12 a 72 bytes. Con caracteres ASCII, cada carácter ocupa un byte. |
| Despacho devuelve 401 | Inicia sesión web y conecta la flotilla con su cuenta propietaria de Node. |
| Despacho devuelve 503 | Comprueba `DISPATCH_API_URL` y el endpoint `/health` del backend. |
| La recuperación no entrega correo | Usa SMTP o un buzón de captura. El panel evita emitir enlaces cuando el mailer es `log`. |
| La API devuelve 403 | Revisa que el token tenga `perfil:leer` y la cuenta esté activa. |
| Se modificó el archivo de entorno y no cambia la configuración | Ejecuta `php artisan config:clear` antes de reiniciar. |

Para producción sirve únicamente `public/` con un servidor web y PHP adecuados, usa TLS, `APP_ENV=production`, `APP_DEBUG=false`, cookies seguras y un proveedor de correo autorizado. Conserva `.env`, bases, volcados y respaldos fuera del repositorio. Protege el endpoint público de WhatsApp con la comprobación de firma del backend.
