# Documento técnico de Mototaxi

Se conservan los números de apartados proporcionados en la consigna. Los anexos quedan sin numeración a la espera del índice completo del estudiante.

## 6. Alcance y limitaciones

### 6.1 Alcance funcional

`panel-mototaxi` es el panel Laravel de la plataforma existente. Gestiona cuentas web y conecta al usuario web habilitado que también se autentica como propietario de una flotilla en Node. La operación de conductor, la ubicación de unidades, la asignación y el historial de viajes pertenecen al backend operativo y su cliente Android.

### 6.2 Funcionalidades incluidas

- Registro web de pasajeros, inicio y cierre de sesión, edición de perfil, cambio y recuperación de contraseña.
- Administración de cuentas web por un administrador habilitado y baja lógica.
- Acceso web al despacho desde una cuenta web activa mediante credenciales de propietario Node y consulta de las unidades y viajes autorizados por esa cuenta. Registro de viaje manual y creación de invitaciones desde el panel.
- Consulta de perfil mediante Sanctum con un permiso limitado. Compatibilidad con la ruta previa de webhook mediante reenvío a Node.
- Enlaces hacia Google Maps para ubicaciones recibidas de Node cuando están disponibles. La disponibilidad del SDK Android, Google Routes y el envío real de WhatsApp corresponden a sus entornos y credenciales.

Los tres roles web y los dos usos de servicios Laravel se adoptan como criterio de la consigna entregada. La carga de archivos y el alta de clientes con OTP web quedan fuera de este alcance. El panel usa su sesión web y el dueño se identifica por separado en el servicio de despacho.

## 7. Análisis de requerimientos

### 7.1 Requerimientos funcionales

Administrar identidad web, permitir sólo permisos acordes al rol, conservar la operación externa desde su fuente, validar formularios y ofrecer una consulta de perfil para clientes autorizados de API. La [matriz de trazabilidad](DIAGNOSTICO_Y_TRAZABILIDAD.md) identifica cada requisito, su origen y la evidencia asociada.

### 7.2 Requerimientos no funcionales

Control de acceso al servidor, datos personales acotados en respuestas, compatibilidad con las rutas existentes, tiempos de espera definidos para el backend, migraciones reproducibles, interfaz adaptable y pruebas separadas de los datos reales. Los datos sintéticos deben levantarse en un entorno desechable.

### 7.3 Historias de usuario o casos de uso

| ID | Actor | Tarea | Resultado |
|---|---|---|---|
| CU01 | Pasajero | Registrarse y gestionar su perfil | Cuenta web propia con rol inicial de pasajero |
| CU02 | Administrador | Crear, consultar, editar y deshabilitar una cuenta | Cambios persistidos con restricciones sobre administradores y sesiones |
| CU03 | Cuenta web activa con propietario Node | Conectar flotilla, consultar unidades y solicitar viaje | Node evalúa operación y propietario y conserva el resultado |
| CU04 | Conductor | Consultar su perfil web | Acceso sólo a acciones autorizadas. La operación de la unidad sigue en Node y Android |
| CU05 | Titular de un token personal | Consultar su perfil desde un cliente HTTP | Respuesta JSON limitada a su cuenta |
| CU06 | Usuario registrado | Recuperar acceso mediante enlace | Notificación con token temporal y establecimiento de contraseña nueva |

### 7.4 Criterios de aceptación

Un registro público siempre resulta en pasajero. Las operaciones privadas requieren sesión activa y el permiso del recurso. Un endpoint tokenizado sólo devuelve el perfil de su titular. El despacho nunca expone el token Node al navegador. Un viaje se guarda en el servicio autorizado y un error de conexión se presenta como fallo. Los pasos reproducibles y pruebas se vinculan en la [matriz de trazabilidad](DIAGNOSTICO_Y_TRAZABILIDAD.md).

## 9. Análisis y diseño del sistema

### 9.1 Arquitectura general

```text
Navegador ──sesión Laravel y CSRF──> Laravel ──Eloquent──> DB web
                                            ├──HTTP Client con token de propietario──> Node ──> SQLite operativa
                                            └──Notifications──> Transporte de correo configurado

Cliente HTTP ──Bearer Sanctum──> API Laravel ──> Perfil del titular
Meta ──HTTPS al receptor configurado──> Node ──> mensajes y bot
Ruta Laravel de compatibilidad ──reenvío del cuerpo firmado──> Node
```

PostgreSQL 18.6 se comprobó como base local de partida. El respaldo precedió la migración aditiva de `auth_version`. Se verificó el conteo de cuentas por rol después de aplicarla. SQLite se usa en pruebas automatizadas y es una opción para un entorno de práctica aislado. Consulte el [diccionario](DICCIONARIO_DATOS.md) para diferenciar los datos web de los registros de operación.

### 9.2 Patrón MVC

`User` es el modelo autenticable y mapea `usuarios.id_usuario` UUID. Los controladores coordinan la petición y los Form Requests validan datos. Blade presenta formularios, navegación y mensajes. `app/Services/DispatchClient.php` encapsula la comunicación de despacho. Las reglas de recurso y las condiciones de cuenta activa se aplican en servidor. [MVC, rutas y Form Requests de Laravel 12](https://laravel.com/docs/12.x/routing)

### 9.4 Flujo funcional

1. El usuario crea una cuenta pública de pasajero o usa una cuenta previamente administrada.
2. Laravel comprueba las credenciales, regenera la sesión y verifica que la cuenta siga activa.
3. La navegación refleja los permisos. El servidor vuelve a aplicarlos ante una URL directa.
4. El titular de una cuenta web activa conecta sus credenciales de propietario de Node. Laravel guarda el token de despacho cifrado en la sesión.
5. Las lecturas y mutaciones de despacho se envían a Node. Los formularios web muestran errores o vuelven al panel con un mensaje de resultado.
6. Cerrar sesión invalida la sesión web y solicita al servicio cerrar su token. El servicio controla el vencimiento de sus tokens si está inaccesible.

### 9.5 Diseño de navegación

Rutas públicas de inicio, acceso, registro, recuperación y aviso legal. Tras el login, panel y perfil. El administrador habilitado puede abrir administración de usuarios. El despacho exige una cuenta web habilitada que también se autentique como propietario de Node. Todas las acciones directas vuelven a pasar por autenticación y autorización.

### 9.6 Wireframes o prototipos

Estos esquemas describen la organización para la demostración. Las capturas reales son evidencias separadas en el [guion](MANUAL_USUARIO_Y_DEMOSTRACION.md).

```text
Visitante              Usuario habilitado        Administrador habilitado
Inicio                 Navegación                Navegación
  Acceder / Registro     Panel / Perfil            Panel / Usuarios / Perfil
  Recuperar acceso      Cerrar sesión             Conectar flotilla
                                                   Unidades, invitación y viaje
```

## 11. Arquitectura e implementación

### 11.2 Rutas

`routes/web.php` y `routes/auth.php` definen rutas de vistas, formularios y sesiones. `routes/api.php` registra el perfil JSON y el webhook de compatibilidad. La raíz, panel y formularios utilizan métodos HTTP conforme a la acción. Consulte [inventario](RUTAS.md) y [contrato API](API.md).

### 11.3 Controladores

Los controladores Auth de Breeze gestionan sesión, registro y recuperación. `ProfileController` permite editar el perfil. `AccountController` coordina el CRUD con `AccountRequest` y `UserPolicy`. `DispatchController` recibe `DispatchTripRequest` y `DispatchInvitationRequest` para mutaciones. `Api/ChatbotController` conserva la URL compatible y reenvía cuerpo y firma. `Api/ProfileController` publica sólo identificador, nombre y rol. `ProfileTokenController` genera y revoca tokens propios.

### 11.5 Vistas y plantillas Blade

Los componentes de `resources/views/layouts` y `resources/views/components` se comparten entre pantallas. Las vistas aprovechan el escape de Blade para datos de usuario y muestran avisos de sesión y errores de validación cerca de los campos. [Blade de Laravel 12](https://laravel.com/docs/12.x/blade)

### 11.6 Formularios

| Formulario | Usuario | Obligatorios | Opcionales | Resultado |
|---|---|---|---|---|
| Alta y edición de cuenta web | Administrador habilitado | Nombre hasta 100 caracteres. En el alta también correo hasta 150, rol permitido y contraseña confirmada de 8 a 72 caracteres | Teléfono de 10 a 15 dígitos con prefijo `+` opcional. Correo opcional al editar una cuenta legada sin correo | Alta o actualización y regreso a usuarios. Rol fijo tras la creación |
| Viaje manual | Cuenta web activa con propietario de Node | Origen y destino hasta 300 caracteres. Número de unidad hasta 4 | Nombre visible del pasajero hasta 120 | Node confirma o rechaza y se vuelve al panel |
| Invitación | Cuenta web activa con propietario de Node | Número de unidad hasta 4 | Ninguno | Código asociado a una unidad permitida por Node |
| Perfil propio | Usuario habilitado | Nombre y correo | Contraseña nueva en formulario independiente | Perfil actualizado o cambio con contraseña actual |

### 11.7 Validaciones

Las entradas se validan en todo punto de entrada con reglas de Laravel. La unicidad de correo y teléfono se comprueba en validación y se respalda con restricciones de la tabla. Los formularios de viaje e invitación consumen un identificador de envío de un solo uso en la sesión. El contrato JSON de compatibilidad permanece aparte. Las restricciones de unidad, viaje y disponibilidad se comprueban en Node. La sesión define el usuario autorizado. [Validación en Laravel 12](https://laravel.com/docs/12.x/validation)

### 11.8 Sesiones y mensajes

Se renueva la sesión al iniciar sesión y se invalida al salir. Formularios web comunican éxito o error tras redirección y preservan los campos no sensibles válidos. Los endpoints JSON devuelven el código HTTP correspondiente. La contraseña y el token de despacho se excluyen de la interfaz y de la evidencia.

### 11.9 Middleware

`active.account` se registra en `bootstrap/app.php` y se ejecuta tras la autenticación en rutas privadas. Un usuario activo continúa a autorización. Una cuenta deshabilitada recibe 403 aunque su sesión todavía exista. Se permite cerrar la sesión. La Policy de cuentas regula acciones concretas después de este filtro. [Middleware de Laravel 12](https://laravel.com/docs/12.x/middleware)

## 13. Seguridad

### 13.1 Registro y autenticación

El formulario público asigna `PASAJERO` en servidor. Laravel usa la comprobación de credenciales y limitación de intentos del flujo Breeze. La aplicación vuelve a exigir sesión después de cierre. Cambios sensibles actualizan la versión de autenticación y revocan tokens para cerrar accesos anteriores.

### 13.2 Roles y permisos

| Rol | Recurso | Acción | Condición | Límite sobre registros |
|---|---|---|---|---|
| Visitante | Identidad | Registrar, iniciar sesión, solicitar recuperación | Datos válidos y límites del formulario | Sólo puede registrarse como pasajero |
| PASAJERO | Perfil web | Ver y modificar identidad o contraseña | Cuenta habilitada | Sólo su cuenta |
| CONDUCTOR | Perfil web | Ver y modificar identidad o contraseña | Cuenta habilitada | Sólo su cuenta. La asignación real vive en Node |
| ADMINISTRADOR | Usuarios web | Consultar todas las cuentas, crear, editar, deshabilitar o reactivar cuentas permitidas | Cuenta habilitada y `UserPolicy` | Altas de pasajero o conductor, sin modificar rol posterior. Sin editarse a sí mismo ni intervenir cuentas de otros administradores desde el CRUD |
| Cuenta web activa + PROPIETARIO Node | Despacho | Consultar unidades y viajes, crear un viaje o invitación | Cuenta web activa y sesión del propietario de Node | Alcance de la flotilla que Node asigna al propietario |
| Titular de token Sanctum | Perfil JSON | Consultar | Cuenta habilitada, token válido y habilidad `perfil:leer` | Sólo la cuenta asociada al token |

### 13.3 Middleware de autorización

`auth`, `auth:sanctum` y `active.account` comprueban sesión o token y el estado habilitado. Los grupos de rutas de administración y despacho aplican autorización de servidor. Los controles visuales se basan en los mismos permisos.

### 13.4 Policies

La Policy de usuarios aplica las acciones sobre cuentas. El acceso a operaciones de despacho requiere una cuenta web activa y ser propietario en Node. Los límites de propiedad, cambios de estado y asignación se resuelven en esa API. [Autorización en Laravel 12](https://laravel.com/docs/12.x/authorization)

### 13.5 Recuperación de contraseña

Breeze aporta solicitud de enlace, broker con expiración configurada a 60 minutos y actualización tras validar el token. El perfil ofrece cambio de contraseña con comprobación de la actual. El canal de correo en pruebas se simula. El controlador bloquea el envío de enlaces con el transporte `log` para evitar que queden en registros. La configuración local original usa ese transporte. La entrega externa requiere un proveedor autorizado y verificación independiente. [Restablecimiento de contraseña](https://laravel.com/docs/12.x/passwords)

### 13.6 Protección CSRF

Los formularios web con efectos usan token CSRF y, cuando corresponde, el método semántico con `@method`. La API de tokens usa Bearer. El webhook público se autentica por el mecanismo de Meta y Node verifica la firma sobre el cuerpo original. [Protección CSRF](https://laravel.com/docs/12.x/csrf)

### 13.7 Hashing y encriptación

El hash permite verificar contraseñas sin recuperar su valor original. El token de despacho se cifra porque el servidor necesita recuperarlo para hacer solicitudes autorizadas. Sanctum almacena una huella del token personal y su valor en claro se emplea al entregarlo al titular. [Hashing](https://laravel.com/docs/12.x/hashing) · [Cifrado](https://laravel.com/docs/12.x/encryption) · [Sanctum](https://laravel.com/docs/12.x/sanctum)

### 13.8 Validación y protección de datos

Se controlan los campos admitidos y los cambios de rol se restringen a la administración. Las respuestas API se construyen con campos autorizados y las vistas escapan contenido generado por usuarios. Los listados de evidencia se limitan a datos de prueba.

### 13.9 Consideraciones de seguridad

Las credenciales se configuran por entorno, la producción requiere HTTPS y `APP_DEBUG=false`. El historial Git local comienza con el respaldo de esta copia. La revisión de publicaciones de versiones anteriores depende de su procedencia. Si un secreto se compartió, se rota en su proveedor y se revisa la configuración de consumo. Mantenga estables las claves de una instalación usada y respalde su base antes de migrar.

## 17. Interfaz de usuario

### 17.1 Criterios de diseño

El panel presenta sus formularios en español con `APP_LOCALE=es`, nombre Aramovil, nombres de acción claros, estados vacíos y confirmación para operaciones sensibles. El [guion](MANUAL_USUARIO_Y_DEMOSTRACION.md) registra los tamaños previstos y el estado de cada evidencia.

### 17.2 Estructura visual

Las pantallas reutilizan encabezado, navegación, botones, campos y avisos del layout común. Las tarjetas de unidades y viajes se distribuyen con el ancho disponible. Las pantallas de administración presentan estados y acciones sobre sus registros.

### 17.3 Navegación

La navegación por rol limita los enlaces disponibles. El perfil propio sigue disponible para cuentas activas. En despacho, el navegador actualiza datos de la flotilla mediante peticiones servidor a servidor iniciadas por Laravel.

## 18.3 Pruebas de validación

Las pruebas Feature ejercitan formularios válidos, inválidos y vacíos, formatos, longitudes, duplicidad y códigos de error. SQLite en memoria permite comprobar efectos en persistencia sin usar datos operativos. La [matriz](DIAGNOSTICO_Y_TRAZABILIDAD.md) y el índice de E02 indican dónde consultar los resultados ejecutados.

## 18.4 Pruebas de autenticación y autorización

La cobertura contempla invitado, cuenta activa, cuenta deshabilitada, rol insuficiente, manipulación de rol y acceso directo a rutas protegidas. También valida el alcance de tokens personales. Las pruebas de correo y Node emplean simulación cuando corresponde.

## Anexos

### Servicios Laravel

| Servicio | Necesidad | Configuración y flujo | Fallos y evidencia |
|---|---|---|---|
| Notifications/Mail | Recuperación de la contraseña web | Controladores Auth y broker generan un token temporal. La notificación usa el transporte `MAIL_MAILER` | Prueba con notificación simulada. Correo real queda pendiente de proveedor externo |
| HTTP Client | Llamar a Node desde despacho y conservar el puente | `DISPATCH_API_URL` y token de propietario almacenado en sesión. Tiempos de conexión y solicitud delimitados | Probar pérdida de conexión, sesión externa vencida y respuesta inválida mediante simulaciones. Webhook propaga fallo para que el proveedor pueda reintentar |

### Paquete adicional y justificación

Sanctum 4.4.0 (MIT) ya figuraba en el archivo de bloqueo y se reutiliza para tokens de consulta de perfil. Es compatible con Laravel 12 y con el PHP 8.2.12 del entorno. Composer conserva su instalación bloqueada, y la migración existente usa UUID para el titular. La habilidad `perfil:leer` limita el acceso. **Tokens de acceso** solicita contraseña actual al emitir, muestra el valor una vez y lo vence en 30 días. Cada titular puede revocar sus propios tokens. Se comparó con sesión web para navegadores y Passport para OAuth completo. La consulta controlada de perfil necesita un token personal de alcance limitado. Un token expuesto debe revocarse. El cliente lo comparte sólo mediante HTTPS. [Sanctum de Laravel 12](https://laravel.com/docs/12.x/sanctum) · [Licencia y dependencias](https://github.com/laravel/sanctum/tree/v4.4.0)

### Material complementario

- [Instalación y README](../README.md)
- [Diagnóstico y matriz](DIAGNOSTICO_Y_TRAZABILIDAD.md)
- [Rutas](RUTAS.md) y [API](API.md)
- [Plan de pruebas](PLAN_PRUEBAS.md) y [tabla de cierre](RESULTADOS_Y_CIERRE.md)
- [Diccionario de datos](DICCIONARIO_DATOS.md)
- [Manual de usuario, demostración y evidencias](MANUAL_USUARIO_Y_DEMOSTRACION.md)
- [Bitácoras de errores e IA, Git y entrega](BITACORA_Y_ENTREGA.md)
