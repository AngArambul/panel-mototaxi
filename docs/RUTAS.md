# Inventario de rutas Laravel

Inventario obtenido con php artisan route:list --json. El grupo web aplica sesión y CSRF en las escrituras. El grupo api atiende el perfil por token y el webhook. Las rutas GET admiten HEAD.

| Método | URI | Nombre | Controlador y método | Middleware | Función | Usuarios autorizados |
| --- | --- | --- | --- | --- | --- | --- |
| GET / HEAD | `/` | `home` | `ViewController` | `web` | Portada | Público |
| GET / HEAD | `/api/user` | `api.profile.show` | `Api\ProfileController@show` | `api`, `auth:sanctum`, `active.account`, `throttle:60,1` | Identidad vinculada al token | Cuenta activa con habilidad perfil:leer |
| GET / HEAD | `/api/webhook/whatsapp` | `api.whatsapp.verify` | `Api\ChatbotController@verifyWebhook` | `api` | Verificar webhook por desafío | Meta o administrador con token de verificación válido en Node |
| POST | `/api/webhook/whatsapp` | `api.whatsapp.receive` | `Api\ChatbotController@webhook` | `api` | Reenviar un evento de Meta a Node | Meta con firma válida |
| GET / HEAD | `/confirm-password` | `password.confirm` | `Auth\ConfirmablePasswordController@show` | `web`, `auth`, `active.account` | Mostrar confirmación de contraseña | Cuenta web activa |
| POST | `/confirm-password` | `password.confirm.store` | `Auth\ConfirmablePasswordController@store` | `web`, `auth`, `active.account` | Confirmar contraseña actual | Cuenta web activa |
| GET / HEAD | `/dashboard` | `dashboard` | `PanelController@show` | `web`, `auth`, `active.account`, `verified` | Panel y formularios de despacho | Cuenta web activa |
| GET / HEAD | `/dispatch/data` | `dispatch.data` | `DispatchController@data` | `web`, `auth`, `active.account` | Consultar unidades y viajes | Cuenta web activa con sesión Node de propietario |
| GET / HEAD | `/dispatch/integration` | `dispatch.integration` | `DispatchController@integration` | `web`, `auth`, `active.account` | Consultar disponibilidad de integraciones | Cuenta web activa con sesión Node de propietario |
| POST | `/dispatch/invitation` | `dispatch.invitations.store` | `DispatchController@invitation` | `web`, `auth`, `active.account` | Invitar a un conductor a una unidad libre | Cuenta web activa con sesión Node de propietario de la unidad |
| POST | `/dispatch/login` | `dispatch.login` | `DispatchController@login` | `web`, `auth`, `active.account`, `throttle:6,1` | Conectar sesión de despacho | Cuenta web activa con credenciales Node de propietario |
| POST | `/dispatch/trips` | `dispatch.trips.store` | `DispatchController@createTrip` | `web`, `auth`, `active.account` | Solicitar un viaje | Cuenta web activa con sesión Node de propietario de la unidad |
| POST | `/email/verification-notification` | `verification.send` | `Auth\EmailVerificationNotificationController@store` | `web`, `auth`, `active.account`, `throttle:6,1` | Solicitar la notificación de verificación de correo | Cuenta web activa |
| GET / HEAD | `/forgot-password` | `password.request` | `Auth\PasswordResetLinkController@create` | `web`, `guest` | Mostrar solicitud de recuperación | Invitado |
| POST | `/forgot-password` | `password.email` | `Auth\PasswordResetLinkController@store` | `web`, `guest`, `throttle:6,1` | Solicitar enlace de recuperación | Invitado. Se envía enlace para cuenta activa |
| GET / HEAD | `/legal` | `legal` | `ViewController` | `web` | Aviso de privacidad y eliminación de datos | Público |
| GET / HEAD | `/login` | `login` | `Auth\AuthenticatedSessionController@create` | `web`, `guest` | Mostrar formulario de acceso | Invitado |
| POST | `/login` | `login.store` | `Auth\AuthenticatedSessionController@store` | `web`, `guest` | Validar credenciales y abrir sesión | Invitado con cuenta activa |
| POST | `/logout` | `logout` | `Auth\AuthenticatedSessionController@destroy` | `web`, `auth` | Cerrar sesión Laravel y solicitar revocación de la sesión Node | Usuario con sesión web |
| PUT | `/password` | `password.update` | `Auth\PasswordController@update` | `web`, `auth`, `active.account` | Cambiar contraseña desde perfil | Cuenta web activa con contraseña actual |
| GET / HEAD | `/perfil/tokens` | `profile.tokens.index` | `ProfileTokenController@index` | `web`, `auth`, `active.account` | Listar tokens propios y mostrar recién emitido una vez | Cuenta web activa |
| POST | `/perfil/tokens` | `profile.tokens.store` | `ProfileTokenController@store` | `web`, `auth`, `active.account`, `throttle:6,1` | Crear token propio con habilidad perfil:leer | Cuenta web activa con contraseña actual |
| DELETE | `/perfil/tokens/{token}` | `profile.tokens.destroy` | `ProfileTokenController@destroy` | `web`, `auth`, `active.account` | Revocar token propio | Cuenta web activa titular del token |
| GET / HEAD | `/profile` | `profile.edit` | `ProfileController@edit` | `web`, `auth`, `active.account` | Mostrar opciones del perfil propio | Cuenta web activa |
| PATCH | `/profile` | `profile.update` | `ProfileController@update` | `web`, `auth`, `active.account` | Actualizar datos del perfil propio | Cuenta web activa |
| DELETE | `/profile` | `profile.destroy` | `ProfileController@destroy` | `web`, `auth`, `active.account` | Desactivar perfil con confirmación | Pasajero o conductor activo |
| GET / HEAD | `/register` | `register` | `Auth\RegisteredUserController@create` | `web`, `guest` | Mostrar formulario de registro | Invitado |
| POST | `/register` | `register.store` | `Auth\RegisteredUserController@store` | `web`, `guest`, `throttle:6,1` | Crear cuenta de pasajero | Invitado |
| POST | `/reset-password` | `password.store` | `Auth\NewPasswordController@store` | `web`, `guest`, `throttle:6,1` | Cambiar contraseña por recuperación | Invitado con token vigente de cuenta activa |
| GET / HEAD | `/reset-password/{token}` | `password.reset` | `Auth\NewPasswordController@create` | `web`, `guest` | Mostrar formulario de nueva contraseña | Invitado |
| GET / HEAD | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` | `web` | Emitir cookie CSRF; infraestructura de Sanctum | Público; el perfil por Bearer no la utiliza |
| GET / HEAD | `/storage/{path}` | `storage.local` | `Closure` | — | Servir archivo del disco privado | URL firmada según configuración de Laravel |
| PUT | `/storage/{path}` | `storage.local.upload` | `Closure` | — | Recibir archivo del disco privado | URL firmada según configuración de Laravel |
| GET / HEAD | `/up` | — | `Closure` | — | Diagnóstico HTTP de Laravel | Público |
| GET / HEAD | `/usuarios` | `accounts.index` | `AccountController@index` | `web`, `auth`, `active.account`, `can:viewAny,User` | Consultar listado y estado de cuentas | Administrador activo |
| POST | `/usuarios` | `accounts.store` | `AccountController@store` | `web`, `auth`, `active.account`, `can:viewAny,User`, `can:create,User` | Crear una cuenta pasajero o conductor | Administrador activo |
| GET / HEAD | `/usuarios/crear` | `accounts.create` | `AccountController@create` | `web`, `auth`, `active.account`, `can:viewAny,User`, `can:create,User` | Mostrar formulario de alta | Administrador activo |
| PUT | `/usuarios/{account}` | `accounts.update` | `AccountController@update` | `web`, `auth`, `active.account`, `can:viewAny,User`, `can:update,account` | Actualizar una cuenta | Administrador activo sobre pasajero o conductor |
| DELETE | `/usuarios/{account}` | `accounts.destroy` | `AccountController@deactivate` | `web`, `auth`, `active.account`, `can:viewAny,User`, `can:update,account` | Desactivar cuenta conservando relaciones | Administrador activo sobre pasajero o conductor |
| PATCH | `/usuarios/{account}/activar` | `accounts.activate` | `AccountController@activate` | `web`, `auth`, `active.account`, `can:viewAny,User`, `can:update,account` | Reactivar cuenta | Administrador activo sobre pasajero o conductor |
| GET / HEAD | `/usuarios/{account}/editar` | `accounts.edit` | `AccountController@edit` | `web`, `auth`, `active.account`, `can:viewAny,User`, `can:update,account` | Mostrar edición de cuenta administrada | Administrador activo sobre pasajero o conductor |
| GET / HEAD | `/verify-email` | `verification.notice` | `Auth\EmailVerificationPromptController` | `web`, `auth`, `active.account` | Mostrar aviso de verificación de correo | Cuenta web activa |
| GET / HEAD | `/verify-email/{id}/{hash}` | `verification.verify` | `Auth\VerifyEmailController` | `web`, `auth`, `active.account`, `signed`, `throttle:6,1` | Verificar correo mediante enlace firmado | Cuenta web activa titular del enlace |

## Comprobaciones

El inventario comprobó que la combinación de método y URI y cada nombre de ruta son únicos.

El parámetro account valida un UUID y usa el binding de User. Un UUID inválido o una cuenta inexistente produce 404. Una cuenta destino existente fuera de la Policy produce 403. El parámetro token del perfil exige un ID numérico y la búsqueda se limita a los tokens de la cuenta en sesión.

El inicio de sesión de despacho autentica un propietario de Node. Las demás rutas de despacho requieren el token Node cifrado en sesión y mantienen el alcance de la flotilla. El tablero y las mutaciones de despacho serializan solicitudes de una misma sesión. Los formularios web de viaje e invitación utilizan un identificador de un solo uso.

Ejecuta php artisan route:list -v para verificar la lista en tu instalación. Los contratos HTTP y la colección están en [API](API.md).
