# API del panel Mototaxi

Para desarrollo local usa `http://127.0.0.1:8000` con `php artisan serve`. La API de perfil usa un token Bearer de Laravel Sanctum 4.4.0. El panel web inicia sesión con cookie y protege los formularios con CSRF. La sesión de flotilla se valida aparte con la API de Node y el permiso `PROPIETARIO`. [Documentación de Sanctum para Laravel 12](https://laravel.com/docs/12.x/sanctum)

## Endpoints

| Método y ruta | Propósito | Autenticación y permisos | Parámetros y headers | Éxito | Errores | Efecto en base de datos | Prueba |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `GET /api/user` | Consultar la identidad y el rol de la cuenta que emitió el token | Cuenta activa, token Bearer vigente con habilidad `perfil:leer`. Sólo devuelve el perfil propio | `Authorization: Bearer <token>`, `Accept: application/json`. Sin query ni cuerpo | `200` JSON de tres campos con `Cache-Control: no-store` | `401` token ausente, inválido, vencido o revocado, `403` cuenta desactivada o habilidad faltante, `429` límite de 60 por minuto | Sanctum registra último uso cuando autentica el token | `ProfileTokenTest`, colección Postman y prueba HTTP local |
| `GET /api/webhook/whatsapp` | Conservar la URI de verificación de Meta en Laravel como puente hacia Node | Node compara el token de verificación del webhook | `hub.mode=subscribe`, `hub.verify_token` y `hub.challenge`. URI exacta con esos nombres | `200` texto plano con el desafío recibido | `403` verificación rechazada por Node, `503` conexión indisponible o webhook sin configurar | Sin escritura | `DispatchIntegrationTest` y colección sandbox |
| `POST /api/webhook/whatsapp` | Enviar el evento firmado al webhook del backend Node | Node valida `X-Hub-Signature-256` (HMAC SHA-256 calculado sobre los bytes del evento) | `Content-Type: application/json`, `X-Hub-Signature-256: sha256=<firma>`. Límite 262,144 bytes | `200` JSON de confirmación cuando Node recibe el evento | `400` JSON malformado con firma válida, `403` firma rechazada, `413` tamaño excedido, `503` backend indisponible o sin configurar | Node registra y deduplica por identificador de Meta. PHP conserva el cuerpo y la firma en el reenvío | `DispatchIntegrationTest`, suite de Node y colección sandbox |

El cliente Laravel usa `DISPATCH_API_URL` y los timeouts en `ChatbotController`. El webhook tiene 2 s de conexión y 5 s totales. El cliente del despacho web tiene 3 s de conexión y 15 s totales. El proceso Node conserva los reintentos de entrega. Laravel devuelve `503` si el backend no recibe el evento. [Webhook de Meta](https://developers.facebook.com/documentation/business-messaging/whatsapp/webhooks/create-webhook-endpoint)

### Ejemplo de perfil propio

Emite un token temporal en **Mi perfil → Tokens de acceso**. La respuesta web lo muestra una vez y solicita tu contraseña actual al emitirlo. Caduca a los 30 días. Para la demostración usa un usuario sintético y revoca el token al terminar. Usa el valor de manera local, evita guardarlo en archivos, versiones de la colección, historial de comandos y capturas.

```http
GET /api/user HTTP/1.1
Accept: application/json
Authorization: Bearer <token-temporal>
```

Ejemplo de respuesta `200`, con datos sintéticos.

```json
{
  "id_usuario": "00000000-0000-4000-8000-000000000001",
  "nombre": "Pasajero de prueba",
  "rol": "PASAJERO"
}
```

### Ejemplos de webhook en un entorno aislado

Usa un Node local con base desechable, un token de verificación sintético y el App Secret de prueba. Orienta `DISPATCH_API_URL` en el entorno de demostración a ese Node. La verificación lee los parámetros y devuelve el valor literal de `hub.challenge`.

```http
GET /api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=<token-local>&hub.challenge=desafio-sintetico HTTP/1.1
```

Para el caso negativo, una firma deliberadamente inválida permite verificar que el reenvío al backend obtiene `403` en un Node configurado.

```http
POST /api/webhook/whatsapp HTTP/1.1
Content-Type: application/json
X-Hub-Signature-256: sha256=firma-invalida

{"object":"whatsapp_business_account","entry":[]}
```

Una recepción válida sólo confirma que Node aceptó y registró el evento. La entrega de una respuesta de WhatsApp requiere una prueba aparte con Meta y una cuenta empresarial autorizada. La ruta pública activa de la flota puede apuntar directamente a Node, por lo que estas pruebas locales del puente Laravel son independientes de producción.

## Colección reproducible

1. Importa [la colección](postman/Mototaxi.postman_collection.json) y [el entorno](postman/Mototaxi.local.postman_environment.json). La colección usa el esquema Postman 2.1. [Especificación oficial](https://schema.postman.com/json/collection/v2.1.0/docs/index.html)
2. Ajusta `base_url` al Laravel local. Para los casos de éxito y revocación pega un token de la cuenta sintética en el valor local de `perfil_token`. Revócalo desde la interfaz antes de ejecutar el caso «Token revocado».
3. Para la habilidad incorrecta, abre `php artisan tinker` sobre la base desechable. Carga el usuario sintético indicando el email que usaste en la demostración y emite un token de cinco minutos. Colócalo sólo en el valor local de `perfil_token_otro`. PHPUnit prepara este caso automáticamente.

   ```php
   $cuenta = App\Models\User::where('email', 'pasajero-sintetico@example.test')->firstOrFail();
   $cuenta->createToken('prueba-sin-habilidad', ['otro'], now()->addMinutes(5))->plainTextToken;
   ```

4. La carpeta «Webhook aislado» se ejecuta únicamente contra el backend desechable. Define `webhook_verify_token` con el valor sintético de Node. No uses el valor de producción.
5. Ejecuta el caso «Token revocado» aparte después de revocarlo desde el panel. Los campos de secretos llegan vacíos.

El inventario de rutas y revisiones de autorización está en [Rutas](RUTAS.md). Los formularios web pueden responder con redirección y mensajes de sesión; los endpoints de esta tabla usan estados HTTP y cuerpo JSON o texto plano según el contrato.

## Evidencias ejecutadas

- **Perfil**. Seis solicitudes HTTP con `fetch` de Node a Laravel real en loopback. Token válido `200`; sin token, vencido y revocado `401`; habilidad distinta y cuenta desactivada `403`. Los datos de prueba salieron de un SQLite recién migrado. [Resultado de perfil](evidencias/api-http.txt)
- **Webhook**. Ocho solicitudes HTTP a Laravel y Node reales en loopback, con credenciales sintéticas y proveedores externos bloqueados. La firma válida insertó una fila, su repetición conservó una sola fila, y se verificaron los errores `400`, `403`, `413` y `503`. [Resultado de webhook](evidencias/webhook-http.txt)

Postman queda preparado para reproducir los casos. La conexión externa y la entrega de mensajes por Meta se verifican por separado.
