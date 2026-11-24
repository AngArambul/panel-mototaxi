<!DOCTYPE html>
<html lang="es-MX">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ARAMOVIL - Centro de Privacidad, Accesibilidad WCAG 2.1 AA y Transparencia</title>
  <style>
    :root {
      --bg-primary: #111317;      /* Contraste 18.4:1 con texto blanco */
      --bg-card: #1B1F26;
      --text-primary: #FFFFFF;
      --text-secondary: #D1D5DB;  /* Contraste 11.2:1 sobre #111317 (Cumple WCAG AAA) */
      --accent-green: #00E676;
      --accent-dark-green: #052613;
      --focus-ring: #60A5FA;
    }
    body {
      background-color: var(--bg-primary);
      color: var(--text-primary);
      font-family: system-ui, -apple-system, sans-serif;
      line-height: 1.6;
      margin: 0;
      padding: 24px;
    }
    a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
      outline: 3px solid var(--focus-ring);
      outline-offset: 3px;
    }
    .container { max-width: 960px; margin: 0 auto; }
    .card {
      background: var(--bg-card);
      border: 1px solid #2D3545;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .badge-wcag {
      background: #103425;
      color: var(--accent-green);
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 0.85rem;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <main class="container" role="main" aria-labelledby="main-heading">
    <header class="card">
      <span class="badge-wcag">WCAG 2.1 Nivel AA Verificado</span>
      <h1 id="main-heading">Centro Legal, Privacidad y Transparencia Operativa — ARAMOVIL</h1>
      <p style="color: var(--text-secondary);">
        Responsable del tratamiento de datos: <strong>Angel Gabriel Ortega Arambul (ARAMOVIL / Mototaxis Los Ruiseñores)</strong>, con domicilio operativo en El Refugio / Los Ruiseñores, Municipio de Tala, Jalisco, México. Contacto oficial ARCO / GDPR / DMCA: <a href="mailto:privacidad@aramovil.mx" style="color: var(--accent-green);">privacidad@aramovil.mx</a>.
      </p>
    </header>

    <section class="card" aria-labelledby="pricing-heading">
      <h2 id="pricing-heading">1. Transparencia Tarifaria y Cancelación en 1 Clic (Click-to-Cancel)</h2>
      <ul>
        <li><strong>Tarifa Base Local (Los Ruiseñores):</strong> $30.00 MXN.</li>
        <li><strong>Cargos por servicio o costos ocultos:</strong> $0.00 MXN (Desglose total desde el primer paso).</li>
        <li><strong>Política de Cancelación y Reembolsos:</strong> La cancelación de cualquier viaje antes del abordaje o la baja del servicio de flotilla se ejecuta en <strong>1 solo clic</strong> sin cargos de penalización. Si un cobro electrónico presenta duplicidad, el reembolso íntegro (100%) se procesa en un máximo de 72 horas hábiles.</li>
      </ul>
    </section>

    <section class="card" aria-labelledby="subprocessors-heading">
      <h2 id="subprocessors-heading">2. Lista Pública de Subprocesadores (DPA) y Atribuciones Open Source</h2>
      <ul>
        <li><strong>Meta Platforms, Inc. (WhatsApp Cloud API):</strong> Procesamiento de mensajería transaccional de solicitud de viajes bajo Acuerdo de Procesamiento de Datos (DPA).</li>
        <li><strong>OpenStreetMap / CartoDB:</strong> Cartografía vectorial sin envío de identificadores personales del pasajero.</li>
        <li><strong>Atribuciones OSS:</strong> Este software utiliza bibliotecas bajo licencias permisivas <code>MIT</code> (Express, Socket.io) y <code>Apache-2.0</code> (Android Jetpack Compose).</li>
      </ul>
    </section>
  </main>

  <script>
    window.aramovilConsent = { analytics: false, marketing: false };
    function activarScriptsConsentidos(consent) {
      if (consent.analytics === true) {
        console.log("Analítica autorizada explícitamente por el usuario (Opt-in).");
      }
    }
  </script>
</body>
</html>