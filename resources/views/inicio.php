<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ConstructControl — Inicio</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/index.css')) ?>">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-logo">
    <a href="<?= e(route('inicio')) ?>"><img src="<?= e(asset('images/logo.png')) ?>" alt="ConstructControl logo"></a>
  </div>
  <button type="button" class="nav-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="nav-links"
          onclick="var n=this.closest('.navbar'),a=n.classList.toggle('abierto');this.setAttribute('aria-expanded',a);this.setAttribute('aria-label',a?'Cerrar menú':'Abrir menú');">
    <span></span><span></span><span></span>
  </button>
  <div class="nav-links" id="nav-links" onclick="if(event.target.closest('a.nav-link'))this.closest('.navbar').classList.remove('abierto')">
    <a href="#quienes" class="nav-link">Quiénes somos</a>
    <a href="#mv" class="nav-link">Misión y visión</a>
    <a href="#valores" class="nav-link">Valores</a>
    <a href="#features" class="nav-link">Funcionalidades</a>
    


    <?php if (auth()->check()): ?>

        <div class="user-chip">
            <span><?= e(auth()->user()->correo_usuario) ?></span>

            <form method="POST" action="<?= e(route('logout')) ?>" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn-login-nav">Cerrar sesión</button>
            </form>
        </div>

    <?php else: ?>

        <a href="<?= e(route('login')) ?>" class="btn-login-nav">
            Iniciar sesión →
        </a>

    <?php endif; ?>
    

  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-text">
    <div class="hero-badge">🏗 Plataforma de gestión para construcción</div>
    <h1 class="hero-title">Control total para cada <span>proyecto de construcción</span></h1>
    <p class="hero-desc">Gestiona presupuestos, inventario, compras y gastos desde una única plataforma. Mantén cada peso bajo control, adjunta respaldos y toma decisiones con información en tiempo real.</p>
    <div class="hero-btns">
    

      <?php if (auth()->check()): ?>

    <a href="<?= e(route('proyectos.index')) ?>" class="btn-hero-primary">Proyectos</a>

    <?php else: ?>

        <a href="<?= e(route('login')) ?>" class="btn-hero-primary">Comenzar ahora →</a>

    <?php endif; ?>

    </div>
  </div>
  <div class="hero-logo-wrap">
    <img src="<?= e(asset('images/logo.png')) ?>" alt="ConstructControl">
  </div>
</section>

<!-- QUIÉNES SOMOS -->
<section class="quienes" id="quienes">
  <div class="section-inner">
    <span class="section-tag">Quiénes somos</span>
    <h2 class="section-title">Construimos herramientas para <span>controlar proyectos</span>, no solo inventarios</h2>
    <div class="quienes-grid">
      <div class="quienes-text">
        <p>En ConstructControl desarrollamos soluciones enfocadas en constructoras que necesitan administrar sus recursos de forma eficiente.</p>
        <p>Nuestra plataforma integra el control de inventario, presupuestos, gastos, compras y documentación en un solo sistema, permitiendo mantener una visión completa del estado financiero de cada proyecto.</p>
        <p>Creemos que una buena gestión comienza con información clara, organizada y disponible cuando más se necesita.</p>
      </div>
      <div class="quienes-cards">
        <div class="q-card">
          <div class="q-icon">📊</div>
          <div><div class="q-card-title">Información centralizada</div><div class="q-card-desc">Todo el estado financiero y operativo en un solo lugar.</div></div>
        </div>
        <div class="q-card">
          <div class="q-icon">📎</div>
          <div><div class="q-card-title">Documentación digital</div><div class="q-card-desc">Adjunta boletas, facturas y respaldos a cada gasto.</div></div>
        </div>
        <div class="q-card">
          <div class="q-icon">⚡</div>
          <div><div class="q-card-title">Tiempo real</div><div class="q-card-desc">Curva S y avance financiero actualizado al instante.</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- MISIÓN Y VISIÓN -->
<section id="mv">
  <div class="section-inner">
    <span class="section-tag">Propósito</span>
    <h2 class="section-title">Nuestra <span>misión y visión</span></h2>
    <div class="mv-grid">
      <div class="mv-card mision">
        <div class="mv-card-tag">Nuestra misión</div>
        <div class="mv-card-title">Facilitamos la administración de proyectos</div>
        <p class="mv-card-text">Brindar a las empresas constructoras una plataforma tecnológica que facilite la administración de sus proyectos, optimizando el control de materiales, presupuestos y gastos para mejorar la rentabilidad y la toma de decisiones.</p>
      </div>
      <div class="mv-card vision">
        <div class="mv-card-tag">Nuestra visión</div>
        <div class="mv-card-title">Referentes en gestión de construcción</div>
        <p class="mv-card-text">Ser una plataforma referente en la gestión integral de proyectos de construcción, ayudando a empresas de todos los tamaños a administrar sus recursos con eficiencia, transparencia y control.</p>
      </div>
    </div>
  </div>
</section>

<!-- VALORES -->
<section class="valores" id="valores">
  <div class="section-inner">
    <span class="section-tag">Nuestros valores</span>
    <h2 class="section-title">Lo que nos <span>define</span></h2>
    <div class="valores-grid">
      <div class="valor-card">
        <div class="valor-icon">🔍</div>
        <div class="valor-title">Transparencia</div>
        <div class="valor-desc">Cada movimiento queda registrado y respaldado mediante documentos y evidencias.</div>
      </div>
      <div class="valor-card">
        <div class="valor-icon">🎯</div>
        <div class="valor-title">Control</div>
        <div class="valor-desc">Toda la información financiera y operativa centralizada para el seguimiento de cada proyecto.</div>
      </div>
      <div class="valor-card">
        <div class="valor-icon">⚡</div>
        <div class="valor-title">Eficiencia</div>
        <div class="valor-desc">Reducimos el tiempo en tareas administrativas mediante procesos simples y automatizados.</div>
      </div>
      <div class="valor-card">
        <div class="valor-icon">🤝</div>
        <div class="valor-title">Confianza</div>
        <div class="valor-desc">La información se mantiene organizada, segura y disponible para apoyar decisiones.</div>
      </div>
      <div class="valor-card">
        <div class="valor-icon">💡</div>
        <div class="valor-title">Innovación</div>
        <div class="valor-desc">Desarrollamos herramientas modernas que evolucionan con las necesidades del sector.</div>
      </div>
    </div>
  </div>
</section>

<!-- QUÉ OFRECE -->
<section id="features">
  <div class="section-inner">
    <span class="section-tag">Funcionalidades</span>
    <h2 class="section-title">¿Qué ofrece <span>ConstructControl</span>?</h2>
    <p class="section-desc">Una plataforma completa diseñada para cada etapa del proyecto de construcción.</p>
    <div class="features-grid">
      <div class="feat-card">
        <div class="feat-icon">📦</div>
        <div class="feat-title">Gestión de Inventario</div>
        <div class="feat-desc">Controla materiales, herramientas y stock disponible en cada proyecto en tiempo real.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon">💰</div>
        <div class="feat-title">Gestión de Presupuestos</div>
        <div class="feat-desc">Define presupuestos por proyecto y monitorea su ejecución con alertas automáticas.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon">🧾</div>
        <div class="feat-title">Registro de Gastos</div>
        <div class="feat-desc">Registra compras, gastos adicionales e imprevistos con su respectiva evidencia digital.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon">📎</div>
        <div class="feat-title">Adjuntar Boletas y Facturas</div>
        <div class="feat-desc">Asocia imágenes o PDF a cada gasto para mantener toda la documentación organizada.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon">📈</div>
        <div class="feat-title">Curva S y Avance Financiero</div>
        <div class="feat-desc">Visualiza el comportamiento del presupuesto mediante Curvas S e indicadores de avance real vs planificado.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon">📊</div>
        <div class="feat-title">Reportes Inteligentes</div>
        <div class="feat-desc">Obtén estadísticas, gráficos y resúmenes que facilitan la toma de decisiones estratégicas.</div>
      </div>
    </div>
  </div>
</section>

<!-- POR QUÉ ELEGIRNOS -->
<section class="porq">
  <div class="section-inner">
    <span class="section-tag">¿Por qué elegirnos?</span>
    <h2 class="section-title">Razones para confiar en ConstructControl</h2>
    <div class="porq-grid">
      <div class="porq-item"><div class="porq-check">✓</div><div class="porq-text">Centraliza toda la información del proyecto en un solo sistema.</div></div>
      <div class="porq-item"><div class="porq-check">✓</div><div class="porq-text">Evita pérdidas por falta de control presupuestario.</div></div>
      <div class="porq-item"><div class="porq-check">✓</div><div class="porq-text">Facilita auditorías mediante documentación digital completa.</div></div>
      <div class="porq-item"><div class="porq-check">✓</div><div class="porq-text">Mejora el seguimiento y control del presupuesto en tiempo real.</div></div>
      <div class="porq-item"><div class="porq-check">✓</div><div class="porq-text">Reduce errores administrativos con procesos automatizados.</div></div>
      <div class="porq-item"><div class="porq-check">✓</div><div class="porq-text">Información disponible en tiempo real para decisiones oportunas.</div></div>
    </div>
  </div>
</section>

<!-- OBJETIVO -->
<section class="objetivo">
  <div class="section-inner">
    <span class="section-tag">Nuestro objetivo</span>
    <h2 class="section-title">¿Hacia dónde <span>vamos</span>?</h2>
    <div class="objetivo-box">
      <div class="objetivo-icon">🎯</div>
      <div class="objetivo-text">
        <p>Ayudar a las empresas constructoras a administrar sus proyectos de forma más eficiente, proporcionando herramientas que permitan controlar recursos, minimizar desviaciones presupuestarias y respaldar cada gasto con información confiable.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <div class="section-inner">
    <h2 class="cta-title">¿Listo para tomar el control?</h2>
    <p class="cta-desc">Ingresa a la plataforma y comienza a gestionar tus proyectos con información en tiempo real.</p>
    <a href="<?= e(route('login')) ?>" class="btn-cta">Iniciar sesión en ConstructControl →</a>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-logo">
        <img src="<?= e(asset('images/logo.png')) ?>" alt="ConstructControl">
      </div>
      <div class="footer-info">
        <div class="footer-name">ConstructControl</div>
        <div class="footer-tagline">Construimos control, entregamos resultados.</div>
        <div style="font-size:11px;color:rgba(255,255,255,0.35);margin-top:4px">Gestión inteligente para proyectos de construcción</div>
      </div>
    </div>
    <div class="footer-sub">Universidad Católica del Maule · Ingeniería en Software II · 2026</div>
  </div>
</footer>

</body>
</html>