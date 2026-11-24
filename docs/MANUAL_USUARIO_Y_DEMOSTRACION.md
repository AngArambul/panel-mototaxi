# Manual de usuario y guion de demostración

Use la dirección del servidor Laravel indicada en [instalación](../README.md). Para la demostración use la base web aislada y el despacho controlado descritos ahí. Las cuentas de demostración deben pertenecer a dominios reservados como `example.test` y los teléfonos y ubicaciones deben ser sintéticos.

## Visitante

1. En **Crear cuenta**, escriba nombre, correo y contraseña confirmada. La cuenta nueva es un pasajero.
2. En **Iniciar sesión**, introduzca las credenciales web. Los mensajes junto al campo indican qué corregir.
3. En **¿Olvidaste tu contraseña?**, solicite un enlace. La demostración de recuperación usa una prueba automatizada con notificación simulada. Si dispone de un buzón de prueba autorizado, configure ese transporte y mantenga el enlace fuera de evidencias. El transporte de log no sirve para generar enlaces.

## Pasajero y conductor

Desde la navegación del panel se entra a **Perfil**, donde puede modificar nombre, correo y contraseña. El cambio de contraseña solicita la actual. Deshabilitar la propia cuenta ordinaria exige confirmación y contraseña. Los administradores mantienen disponible su acceso crítico. Use **Cerrar sesión** para volver al inicio. Una página privada debe exigir sesión de nuevo.

Las tareas de conductor asignado, disponibilidad y ubicación en operación se realizan en Node y Android. El panel Laravel conserva el perfil web del conductor.

## Administrador web

1. Abra **Usuarios** para consultar cuentas. Cree una de prueba con nombre, correo reservado, rol permitido y contraseña confirmada. El teléfono es opcional.
2. Consulte la fila resultante y abra **Editar**. Actualice nombre, correo o teléfono y vuelva al listado para verificar persistencia. El rol se decide al crear. El panel protege cuentas administrativas.
3. Seleccione **Desactivar**, confirme y verifique el nuevo estado. La cuenta queda registrada y pierde acceso. Use **Reactivar** si necesita restablecer su elegibilidad.
4. Un administrador de la web no obtiene por ese rol acceso a toda la flotilla. El despacho exige una cuenta de propietario en Node y muestra sólo sus unidades.

## Despacho conectado

1. Inicie una sesión web activa. En **Despacho**, conecte su cuenta de propietario del servicio Node.
2. Consulte las tarjetas de unidades y sus viajes. Cuando el servicio devuelve GPS vigente, puede abrir la ubicación en Google Maps.
3. Para crear una solicitud, indique recogida, destino y unidad. El nombre visible del pasajero es opcional. Compruebe el mensaje de resultado y el nuevo viaje en la lista. Un error de conexión debe conservar un mensaje de fallo.
4. Para una unidad elegible, use el formulario de invitación. Trate el código resultante como un permiso de alta temporal y cubra su valor en las evidencias.

## API para un cliente HTTP

Abra **Tokens de acceso**, ponga nombre al token y confirme la contraseña actual. Copie su valor cuando se muestre: se entrega una vez, vence a los 30 días y concede el alcance `perfil:leer`. Siga los pasos del [contrato API](API.md). Mantenga el valor del token en variables privadas del cliente. En la evidencia enseñe el código de estado y los campos de perfil permitido, excluya la cabecera completa y revoque el token al terminar.

## Guion reproducible

| Paso | Precondición | Cuenta de prueba | Acción | Resultado esperado | Evidencia |
|---|---|---|---|---|---|
| D01 | Instalación aislada y frontend compilado | Visitante | Abra la URL de Laravel y compruebe versión por terminal | Inicio accesible y `php artisan --version` coincide con el README | E01 |
| D02 | Correo sintético no creado | Visitante | Registre la cuenta, inicie y cierre sesión. Vuelva a una página privada | Rol pasajero, sesión invalida al salir | E03 |
| D03 | Usuario sintético habilitado | Pasajero | Abra directamente administración de usuarios | 403 y ningún cambio persistido | E04 |
| D04 | Cuenta admin de demostración | Administrador | Cree, lea, edite, desactive y reactive una cuenta sintética | Estado e identidad correctos tras refrescar. Registro conservado | E05 |
| D05 | Cuenta de prueba desactivada desde otra sesión | Usuario deshabilitado | Intente abrir una ruta privada con sesión preexistente | Middleware responde con acceso denegado | E04 |
| D06 | Cuenta admin y formulario abierto | Administrador | Envíe correo duplicado, campo obligatorio vacío y valores válidos | Mensajes junto a campos. Ningún registro inválido creado | E06 |
| D07 | Servicio Node de prueba disponible | Usuario web + propietario de prueba | Conecte, consulte unidades y solicite un viaje de práctica | Node registra el viaje y devuelve una respuesta visible en el panel | E07 |
| D08 | Servicio simulado sin conexión o con alcance insuficiente | Usuario web + propietario de prueba | Consulte o intente una mutación fallida | Error visible. La web no confirma una operación fallida | E07 |
| D09 | Prueba de notificación simulada o buzón autorizado | Titular sintético | Ejecute las pruebas de recuperación con notificación simulada. Cambie contraseña desde perfil | Recuperación válida y cambio exige contraseña actual | E03 |
| D10 | Token válido, insuficiente y revocado en cliente local | Titular sintético | Consulte el propio perfil con cada opción y luego sin token | 200 con permiso, 403 sin habilidad, 401 revocado o ausente | E08 |
| D11 | DevTools en navegador | Visitante, pasajero y administrador | Observe 390 × 844, 768 × 1024 y 1440 × 900 px. Use Tab, Shift+Tab y Enter | Se conservan foco, botones, etiquetas, contraste y lectura sin ocultar campos | E09 |
| D12 | Esquema aislado y dato sintético guardado | Operador de prueba | Consulte estado de migraciones, esquema de `usuarios` y relación lógica de token/sesión | Estructura y persistencia verificables sin mostrar secretos | E10 |

Para preparar cuentas sintéticas en SQLite aislada con nombre `mototaxi-demostracion.sqlite`, ejecute `php artisan db:seed --class=DemonstrationSeeder`, indique una contraseña de demostración al prompt oculto y utilice `admin@mototaxi.test`, `conductor@mototaxi.test` y `pasajero@mototaxi.test`. Los pasos completos están en [instalación](../README.md).

Si usa un receptor simulado, identifíquelo al presentar. La demostración académica no requiere mensajes a personas reales ni cambiar el webhook activo.

## Índice de evidencias

Las capturas proceden de Chrome de escritorio en modo sin ventana, con dimensiones de viewport fijadas. Se arrancó el panel real sobre SQLite desechable y se usó un doble HTTP para despacho. Las fuentes externas se bloquearon en esta prueba. [Registro del navegador](evidencias/interfaz/verificacion-navegador.json)

| Evidencia | Requisitos | Archivo o verificación | Estado |
|---|---|---|---|
| E01 | R01, R10, R17 | `php artisan --version`, `route:list -v` y [RUTAS.md](RUTAS.md) | Inventario y salida de comandos |
| E02 | R01–R04, R06–R09, R11–R14 | [Ejecución PHPUnit](evidencias/phpunit.txt), SQLite en memoria | Ver informe de ejecución |
| E03 | R06, R07 | [Inicio de sesión móvil](evidencias/interfaz/movil-390x844.png) y [pruebas de restablecimiento](evidencias/phpunit.txt) | Login y restablecimiento simulado verificados. Envío externo pendiente |
| E04 | R02, R08 | [Acceso denegado](evidencias/interfaz/acceso-denegado.png) y [registro de navegador](evidencias/interfaz/verificacion-navegador.json) | 403 en español con pasajero sintético y menú sin administración comprobados |
| E05 | R03, R04 | [Alta en tableta](evidencias/interfaz/tableta-768x1024.png) y [cuenta actualizada y desactivada](evidencias/interfaz/crud-usuarios.png) | CRUD y reactivación comprobados con SQLite desechable |
| E06 | R03, R09 | [Error de correo duplicado](evidencias/interfaz/validacion-formularios.png) | Formulario conserva nombre y correo y limpia ambos campos de contraseña |
| E07 | R05, R09, R11, R20 | [PHPUnit](evidencias/phpunit.txt), [pruebas Node](evidencias/node-test.txt) y [webhook HTTP](evidencias/webhook-http.txt), cargas y servicios sintéticos | Pruebas automáticas ejecutadas. Alcance real separado |
| E08 | R12, R13 | [Contrato API](API.md), colección y [cliente HTTP local](evidencias/api-http.txt) | Consulte las respuestas capturadas |
| E09 | R15, R17, R19–R20 | [Móvil](evidencias/interfaz/movil-390x844.png), [tableta](evidencias/interfaz/tableta-768x1024.png), [escritorio](evidencias/interfaz/escritorio-1440x900.png), [registro de tamaños, etiquetas y foco](evidencias/interfaz/verificacion-navegador.json) | Sin desborde horizontal en pantallas comprobadas. Tab y Shift+Tab verificados en login |
| E10 | R16, R17, R20 | [Instalación limpia](evidencias/instalacion-limpia.txt), [validación local](evidencias/validacion-local.txt) y [compilación Vite](evidencias/vite-build.txt) | Consulte los pasos ejecutados |
| E11 | R18 | Rama `main`, referencia inicial `3bcabf0` y tag local de entrega | [Estado de Git](BITACORA_Y_ENTREGA.md#estado-de-gitgithub). Remoto pendiente |
| E12 | R19 | [Bitácora IA](BITACORA_Y_ENTREGA.md#bitácora-de-ia) y revisión personal | Pendiente de revisión del estudiante |
