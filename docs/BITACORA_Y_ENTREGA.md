# Bitácora y expediente de entrega

Identificador documental de esta copia `entrega-2026-11-23`. La numeración inicial para la versión del panel es `v0.1.0` y la entrega local se distingue de una release publicada.

## Estado de Git/GitHub

En la primera revisión `git status --short --branch` devolvió «not a git repository». Se creó el repositorio local en `main`, con el primer commit `3bcabf0` (*chore: registrar respaldo inicial del panel Aramovil*). La auditoría preserva una copia histórica de archivos Laravel en `../aramovil2/auditoria/originales/panel-mototaxi`. Es un respaldo de archivos y su uso como referencia no atribuye un commit o versión publicados.

| Elemento | Evidencia disponible | Estado |
|---|---|---|
| Repositorio remoto y URL | El repositorio local no tiene remoto configurado | Pendiente de definir remoto autorizado |
| Rama principal y ramas de trabajo | Rama local `main`. Historial comienza en `3bcabf0` | Verificado localmente |
| Commits, issues, PR e integración | `git log --oneline --decorate` en el proyecto. Código, respaldo, matriz y bitácora | Commits locales comprobables. Sin issues o PR publicados |
| Implementación | `7e0599f` consolidó cuentas, despacho y API. `45a6be0` localizó el acceso denegado | Commits locales comprobados |
| Tag | `v0.1.0` elegida para la nueva línea local | Consulte `git show v0.1.0` para verificar el commit etiquetado de la entrega |
| Push y publicación de Release | Sin operación ejecutada | Pendiente de acceso explícito al remoto |
| Secretos y rastreo | `.env` está excluido mediante `.gitignore` | No hay historial previo a la copia entregada. El nuevo historial evita rastrearlo |

### Procedimiento cuando se obtenga el remoto

Si recupera un remoto anterior, clone esa procedencia por separado para comparar ramas e historia antes de publicar. Si se crea un remoto nuevo, el responsable puede configurar la URL autorizada con `git remote add origin URL_AUTORIZADA`. Compruebe rama, historia y estado con:

```powershell
git status --short --branch
git remote -v
git branch --all
git log -12 --oneline --decorate
git tag --list
git ls-files .env '*.sqlite' '*.sqlite-*'
```

Compare y copie los cambios revisados sin sobrescribir archivos ajenos. Agrupe autenticación/usuarios, despacho/API y documentación/pruebas en commits coherentes si la organización del remoto lo permite. Revise `git diff --cached` antes de cada commit. Excluya `.env`, bases de datos, respaldos, sesiones de almacenamiento, evidencias con secretos y dependencias instaladas. Si detecta una credencial publicada, debe rotarse con su proveedor y revisar la configuración que la usa. La historia compartida requiere autorización específica para reescribirla.

Una etiqueta se crea sobre el commit verificado. Publicar una release requiere que el commit y la etiqueta se hayan subido al remoto y que el responsable autorice la publicación. La carpeta de entrega conserva código, migraciones, pruebas y documentación preparada para ese paso.

### Notas de la copia local

- Reestructuración de rutas, formularios y controladores Laravel sobre el panel existente.
- Controles de cuenta activa, administración y baja lógica de usuarios.
- Compatibilidad del puente con despacho Node y consulta limitada de perfil mediante Sanctum.
- Sustento académico mediante [trazabilidad](DIAGNOSTICO_Y_TRAZABILIDAD.md), [documento](DOCUMENTO_TECNICO.md), [diccionario](DICCIONARIO_DATOS.md) y [guion de demostración](MANUAL_USUARIO_Y_DEMOSTRACION.md).
- Verificación de endpoints externos con las condiciones descritas en la [API](API.md). El resultado de un simulador se identifica en los reportes.

### Archivos modificados y motivo

| Archivos | Motivo |
|---|---|
| `routes/web.php`, `auth.php`, `api.php` y `accounts.php` | Inventario nombrado y protecciones para sesiones, formularios y API |
| `app/Http/Controllers/Auth/`, `ProfileController.php` y `Models/User.php` | Roles web, hash, sesión, revocación y baja lógica |
| `app/Http/Middleware/EnsureAccountIsActive.php`, `Policies/UserPolicy.php` y `bootstrap/app.php` | Bloqueo central de cuentas inactivas y autorización por recurso |
| `app/Http/Controllers/AccountController.php` y `Http/Requests/AccountRequest.php` | CRUD web, límites por campo y control de duplicidad |
| `app/Http/Controllers/DispatchController.php`, `PanelController.php` y `Services/DispatchClient.php` | Formularios de despacho, respuesta a fallos, compatibilidad con Node y bloqueo de reenvíos |
| `app/Http/Controllers/Api/` y `ProfileTokenController.php` | Perfil acotado mediante Sanctum, control de tokens y compatibilidad de webhook |
| `resources/views/` y `lang/es*` | Navegación, formularios, validación localizada y página de acceso denegado |
| `database/` | Migración aditiva de versión de sesión y datos de demostración acotados a su base aislada |
| `tests/Feature/`, `.env.example`, `.gitignore`, `README.md` y `docs/` | Regresión, configuración reproducible y evidencia técnica |

El listado exacto se obtiene con `git diff --name-status 3bcabf0..HEAD`.

## Bitácora de errores y correcciones

Los casos se derivan de inspección del código inicial, de la suite y de observaciones del navegador. La [ejecución final](evidencias/phpunit.txt) se conserva sin secretos.

| ID | Descripción y reproducción de partida | Impacto | Causa | Corrección | Archivos o módulo | Prueba posterior | Estado |
|---|---|---|---|---|---|---|---|
| B01 | Con una sesión de cuenta deshabilitada, intentar abrir el panel | Acciones privadas posibles con un usuario que dejó de estar activo | Existía la columna `activo` sin uso general | Middleware de cuenta activa después de autenticación en web y API | Middleware, bootstrap y rutas | Cuenta habilitada permitida y deshabilitada 403 | Verificado en suite |
| B02 | Desde perfil, enviar baja física de una cuenta existente | Riesgo de romper referencias o activar cascadas legadas | Borrado físico desde perfil | Desactivación lógica y revocación en el flujo autorizado | Controlador de perfil, modelo y pruebas | Registro persiste tras baja y vuelve a exigir sesión activa | Verificado en suite |
| B03 | Enviar una mutación de despacho sin permiso externo o con servidor offline | Despacho funciona sólo si el propietario está autenticado. Sesión externa puede vencer | Token separado, puente HTTP y manejo local incompleto | Formularios validados, tiempos de espera, errores claros y preservación del contrato | Controlador y cliente de despacho, vistas | Roles externo y Laravel, latencia y fallo del receptor, redirección web o JSON | Verificado en pruebas controladas |
| B04 | Con un token genérico, consultar `/api/user` | Exposición de más atributos de los necesarios | Respuesta directa del modelo sin habilidad específica | Respuesta acotada y capacidad Sanctum para perfil personal | API, modelo/tokens y pruebas | 200 con capacidad; 401/403 con token ausente, vencido, revocado o sin capacidad | Verificado por HTTP |
| B07 | Acceso directo sin permiso mostraba el mensaje 403 genérico en inglés | Interrupción de la experiencia en español | Vista de excepción por defecto | Página 403 Blade con texto y navegación en español | `resources/views/errors/403.blade.php` | HTTP 403 confirmado en Chrome con una cuenta de pasajero sintética | Verificado |
| B06 | El formulario para conectar propietario seguía visible con flotilla conectada | Instrucciones y formulario de acceso quedaban visibles durante la operación | `display:grid` de Tailwind prevalecía sobre el atributo `hidden` del navegador | Aplicar `display:none` para componentes ocultos en el layout de despacho | `resources/views/dashboard.blade.php` | Chrome confirmó `display:none` tras conectar y después de guardar un viaje sintético | Verificado en navegador |
| B05 | Abrir README o buscar un commit | Difícil reproducir el proyecto existente o demostrar la versión | README de plantilla y copia sin `.git` | README, guion, identificación local y procedimiento de entrega | Documentación | Reproducir desde copia y recuperar historial desde remoto | Verificado en la copia local. GitHub requiere remoto |

## Bitácora de IA

| Fecha | Herramienta / modelo | Propósito | Prompt o referencia | Sugerencia recibida | Elementos afectados | Decisión | Modificación | Validación | Aprendizaje |
|---|---|---|---|---|---|---|---|---|---|
| 2026-11-23 | Codex, modelo concreto no confirmado | Diagnosticar el proyecto y sus dependencias | Consigna del estudiante con 20 apartados | Tomar inventario de rutas, pruebas, PostgreSQL y antecedentes | Revisión de Laravel, con Android y Node como contexto | Aceptada por el asistente | Documentación vinculada a archivos y versiones reales | Comprobaciones de versiones, inspección de archivos y suite inicial | Pendiente de revisión personal |
| 2026-11-23 | Codex | Resolver autorización y persistencia | Actividades A1/A2, apartados de CRUD y seguridad | Reusar estado `activo`, diferenciar identidades y conservar relaciones por baja lógica | Autenticación, Policy de usuarios y panel de despacho | Modificada por el asistente durante auditoría | Despacho conserva el acceso de cuenta web activa que se identifica como propietario Node | Pruebas con SQLite temporal y HTTP controlado en E02 y E07 | Pendiente de revisión personal |
| 2026-11-23 | Codex | Demostrar API con menor exposición | Actividad A3, apartado API/paquete | Reutilizar Sanctum con un permiso de perfil en lugar de crear autenticación nueva | Perfil JSON y tokens | Aceptada por el asistente | Capacidad `perfil:leer` y evidencias con datos sintéticos | Seis casos de perfil y ocho de webhook en informes HTTP de E07/E08 | Pendiente de revisión personal |
| 2026-11-23 | Codex | Preparar documentación académica | Índice numerado parcial y requisitos de entrega | Separar evidencia reproducible y dependencias pendientes. No inventar capturas o commits | Matriz, manuales, técnico y bitácora | Aceptada por el asistente | Archivos Markdown y guion con datos reservados | Matriz vinculada a capturas reales, reportes y procedimientos de E01 a E12 | Pendiente de revisión personal |

El estudiante proporcionó consigna y proyecto. Quedan pendientes su revisión personal, confirmación de decisiones, registro de cambios realizados por él, verificación independiente y explicación de lo aprendido. Use esta bitácora con las reglas académicas de su institución. Los prompts o evidencias que se publiquen excluyen cuentas reales, enlaces de recuperación, contraseñas, capturas con identificadores y secretos.

## Entrega

La [tabla de cierre](RESULTADOS_Y_CIERRE.md) relaciona evidencia y bloqueos por requisito. La [matriz de trazabilidad](DIAGNOSTICO_Y_TRAZABILIDAD.md) documenta sus criterios y estados de partida. La documentación preparada permite revisar la copia local sin depender de una versión publicada. Antes de llevar la demostración a un entorno real, confirme el origen de las bases y el acceso al remoto previsto, y use sólo datos autorizados.
