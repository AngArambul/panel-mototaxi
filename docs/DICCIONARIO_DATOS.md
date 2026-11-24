# Diccionario de datos

Este diccionario distingue el esquema migrable del panel Laravel y las fuentes de datos operativos. Las migraciones bajo `database/migrations` son la referencia para una instalación limpia. En PostgreSQL legado algunas definiciones preexistían y se preservan.

## Modelo y relaciones

```text
usuarios (UUID) 1 ── N personal_access_tokens (polimórfico)
usuarios (UUID) 1 ── N sessions (user_id lógico si el driver es database)
usuarios (correo) 1 ── 0..1 password_reset_tokens (asociación lógica)
```

Sanctum y las sesiones se asocian por identificador sin una clave foránea directa en las migraciones de Laravel. Los servicios legados de PostgreSQL pueden referenciar `usuarios`. La baja lógica preserva las asociaciones operativas. El servidor aplica las reglas de cuenta activa y revocación.

## Identidades web y autenticación

| Tabla | Campo | Tipo y longitud de migración | ¿Nulo? | Valor predeterminado | Clave o relación | Significado |
|---|---|---|---|---|---|---|
| usuarios | id_usuario | UUID | No | Generado por Eloquent | PK | Identificador estable |
| usuarios | nombre | varchar(255) en migración. Varchar(100) en PostgreSQL existente | No | — | — | Nombre visible. El atributo Eloquent `name` lo adapta |
| usuarios | telefono | varchar(255) | Sí | null | Único | Teléfono opcional. El formulario aplica su formato efectivo |
| usuarios | email | varchar(255) en migración. Varchar(150) en PostgreSQL existente | Sí | null | Único | Identidad de sesión. El alta web lo requiere |
| usuarios | password | varchar(255) | Sí | null | — | Hash de contraseña. La nulabilidad conserva el esquema legado |
| usuarios | rol | varchar(255) en instalación limpia | No | PASAJERO | Enumeración lógica. PostgreSQL legado usa enum nativo | PASAJERO, CONDUCTOR o ADMINISTRADOR |
| usuarios | activo | boolean | No | true | — | Acceso permitido por middleware |
| usuarios | auth_version | UUID | Sí | null | — | Versión para invalidar sesiones al cambiar credenciales o estado. Migración aditiva del 23 de noviembre |
| usuarios | remember_token | varchar(100) | Sí | null | — | Token auxiliar de sesión |
| usuarios | email_verified_at | timestamp | Sí | null | — | Fecha de verificación de correo si se usa |
| usuarios | creado_en | timestamp | No | Hora de inserción | — | Registro de creación |
| usuarios | actualizado_en | timestamp | No | Hora de inserción | — | Columna histórica de actualización. La sincronización depende del modelo o esquema |
| personal_access_tokens | id | bigint autoincremental | No | Secuencia | PK | Identificador interno |
| personal_access_tokens | tokenable_type | varchar(255) | No | — | Índice compuesto con tokenable_id | Clase del titular |
| personal_access_tokens | tokenable_id | UUID | No | — | Asociación polimórfica con `usuarios.id_usuario` | Titular |
| personal_access_tokens | name | text | No | — | — | Etiqueta del cliente |
| personal_access_tokens | token | varchar(64) | No | — | Único | Huella del token |
| personal_access_tokens | abilities | text | Sí | null | — | Capacidades serializadas, entre ellas `perfil:leer` |
| personal_access_tokens | last_used_at | timestamp | Sí | null | — | Último uso |
| personal_access_tokens | expires_at | timestamp | Sí | null | Índice | Vencimiento si está definido |
| personal_access_tokens | created_at, updated_at | timestamp | Sí | null | — | Marcas de Laravel |
| password_reset_tokens | email | varchar(255) | No | — | PK. Relación lógica por correo | Cuenta que solicita restablecimiento |
| password_reset_tokens | token | varchar(255) | No | — | — | Huella del enlace temporal |
| password_reset_tokens | created_at | timestamp | Sí | null | — | Control de expiración |
| sessions | id | varchar(255) | No | — | PK | Identificador de sesión |
| sessions | user_id | varchar(255) | Sí | null | Índice, relación lógica por UUID | Identidad autenticada |
| sessions | ip_address | varchar(45) | Sí | null | — | Metadato de sesión |
| sessions | user_agent | text | Sí | null | — | Descripción del cliente |
| sessions | payload | longtext | No | — | — | Estado de sesión |
| sessions | last_activity | integer | No | — | Índice | Momento de última actividad |

La verificación de propiedad de una cuenta usa su UUID y la Policy. La baja lógica conserva todos los campos de identidad. La unicidad de correo y teléfono también se conserva al deshabilitar una cuenta. Si se requiere reutilizar sus datos, revise reactivación antes de crear otra.

## Tablas de infraestructura

| Tabla | Campos con claves y restricciones | Nulos y valores iniciales | Uso |
|---|---|---|---|
| users | id bigint PK. Name y email varchar(255). Email único. Password varchar(255). Remember_token varchar(100). Email_verified_at, created_at, updated_at timestamp | Opcionales los tokens y timestamps. Sin valores de aplicación por defecto | Tabla del esqueleto preservada por la migración inicial. El modelo operativo usa `usuarios` |
| cache | key varchar(255) PK, value mediumtext, expiration integer indexado | Ninguno nulo | Driver de caché database cuando se configura |
| cache_locks | key varchar(255) PK, owner varchar(255), expiration integer indexado | Ninguno nulo | Exclusión en caché |
| jobs | id bigint PK, queue varchar(255) indexado, payload longtext, attempts tinyint unsigned, reserved_at, available_at, created_at integer unsigned | reserved_at admite null | Cola database |
| job_batches | id varchar(255) PK, name varchar(255), total_jobs/pending_jobs/failed_jobs integer, failed_job_ids longtext, options mediumtext, cancelled_at/created_at/finished_at integer | options, cancelled_at y finished_at admiten null | Lotes de trabajos |
| failed_jobs | id bigint PK, uuid varchar(255) único, connection y queue text, payload y exception longtext, failed_at timestamp | failed_at se fija a hora de inserción. Demás obligatorios | Fallos de cola |
| migrations | id entero autoincremental PK, migration varchar(255), batch integer | Obligatorios | Control de migraciones del framework |

## Fuentes operativas

PostgreSQL legado contiene `conductores`, `servicios_mototaxi`, `ubicacion_actual` y `sesiones_dispositivo`. Se conserva un volcado comprobado con `pg_restore --list`. El esquema histórico no se recrea desde las migraciones de Laravel. Existen referencias de conductores y sesiones de dispositivo a `usuarios` con cascada y de servicios a `usuarios` con restricción. El CRUD de cuentas usa baja lógica para conservar los datos relacionados. El panel no duplica en Laravel las unidades ni los viajes actuales de Node.

El sistema Node conserva su esquema en `aramovil2-server/src/store.js` y reglas en `src/dispatch.js`. La aceptación de viaje usa una transacción que vuelve a comprobar estado, alcance de la oferta y disponibilidad. Su suite de integración contiene la competencia entre dos conductores. La evidencia de esta prueba corresponde a Node. El panel verifica la comunicación y códigos del servicio. La cancelación registra un estado y conserva el historial.

## Comprobación de la instalación

Para las tablas web inspeccione la instalación aislada con `php artisan db:show`, `php artisan db:table usuarios` y `php artisan migrate:status`. Para evidencia académica capture esquema, índices y conteos de prueba, sin seleccionar contraseñas, huellas completas, payloads, coordenadas ni tokens. Los resultados automatizados deben comprobar también el cambio persistido de alta, edición o baja.
