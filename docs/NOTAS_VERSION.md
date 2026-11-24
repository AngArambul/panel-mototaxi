# Aramovil v0.1.0

- Organiza rutas, controladores, formularios y vistas del panel Laravel existente. Conserva la conexión con el backend de despacho.
- Completa la administración web de pasajeros y conductores, la baja lógica y la protección de cuentas activas. La migración agrega un identificador de vigencia de acceso sin borrar registros.
- Limita la API de perfil con tokens Sanctum de 30 días, capacidad de lectura y revocación. Incluye colección Postman e inventario de rutas.
- Incorpora manuales, matriz de trazabilidad, diccionario de datos, bitácoras y guion de demostración con datos sintéticos.

**Verificación.** Ejecutadas 43 pruebas Laravel y 28 del backend Node. Validada la instalación limpia con Composer y npm, migraciones y Vite. Se probaron por HTTP local la API Sanctum y el puente de webhook contra bases aisladas.

**Dependencias externas.** El correo en producción requiere un transporte configurado. Google Maps y la entrega real de WhatsApp requieren las credenciales y las comprobaciones del entorno donde se desplieguen. Consulta la [tabla de cierre](RESULTADOS_Y_CIERRE.md).
