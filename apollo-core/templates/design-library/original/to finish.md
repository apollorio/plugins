<!DOCTYPE html>
<html lang="pt-BR" class="antialiased">
<head>
  <meta charset="UTF-8" />
  <title>apollo::rio · terminal de acesso</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />

  <!-- Tailwind (para ícones utilitários se quiser usar depois) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Remix Icons -->
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />

  <style>
    :root {
      --font-main: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;

      --bg-dark: #020617;
      --glass-bg: rgba(15,23,42,0.9);
      --glass-border: rgba(248, 250, 252, 0.06);

      /* Paleta laranja / âmbar */
      --color-normal: #ffffff;
      --color-accent: #fb923c; /* Laranja */
      --color-warning: #facc15; /* Âmbar */
      --color-danger: #f97373;  /* Vermelho suave */

      --current-color: var(--color-accent);
    }

    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
      margin: 0;
      padding: 0;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: var(--font-main);
      background-color: #000;
      color: #f9fafb;
      overflow: hidden;
      height: 100vh;
      width: 100vw;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .4s ease;
    }

    /* Fundo “espaçonave” */
    .bg-layer {
      position: fixed;
      inset: 0;
      z-index: -3;
      background:
        radial-gradient(circle at 0 0, rgba(251,146,60,0.20), transparent 55%),
        radial-gradient(circle at 100% 0, rgba(245,158,11,0.2), transparent 60%),
        radial-gradient(circle at 50% 120%, #020617 30%, #000000 100%);
    }

    .grid-overlay {
      position: fixed;
      inset: 0;
      z-index: -2;
      background-image:
        linear-gradient(rgba(249,250,251,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(249,250,251,0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      opacity: .5;
      pointer-events: none;
    }

    .noise-overlay {
      position: fixed;
      inset: 0;
      z-index: -1;
      opacity: .06;
      pointer-events: none;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.75' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }

    /* Cartão principal (modo “mobile emulador”) */
    .terminal-wrapper {
      width: 100%;
      height: 100%;
      position: relative;
      display: flex;
      flex-direction: column;
      background: transparent;
    }

    @media (min-width: 768px) {
      .terminal-wrapper {
        width: 420px;
        height: 86vh;
        max-height: 840px;
        border-radius: 24px;
        border: 1px solid var(--glass-border);
        background: linear-gradient(145deg, rgba(15,23,42,0.98), rgba(15,23,42,0.92));
        box-shadow:
          0 25px 60px rgba(0,0,0,0.75),
          0 0 0 1px rgba(15,23,42,0.9);
        overflow: hidden;
        backdrop-filter: blur(26px);
      }
    }

    .scroll-area {
      flex: 1;
      overflow-y: auto;
      padding: 20px 22px 18px;
      scrollbar-width: none;
    }
    .scroll-area::-webkit-scrollbar { display: none; }

    h1, h2, h3 { font-weight: 800; letter-spacing: -.02em; }

    .flavor-text {
      font-family: monospace;
      font-size: 10px;
      color: rgba(248,250,252,0.55);
      text-transform: uppercase;
      letter-spacing: .16em;
      margin-bottom: 4px;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      opacity: .85;
    }

    /* Header Apollo::rio com coordenadas */
    header.apollo-header {
      padding: 16px 20px 10px;
      border-bottom: 1px solid rgba(148,163,184,0.35);
      background: radial-gradient(circle at 0 0, rgba(251,146,60,0.16), transparent 65%);
      position: relative;
      z-index: 10;
    }

    .logo-mark {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo-icon {
      width: 26px;
      height: 26px;
      border-radius: 999px;
      background:
        radial-gradient(circle at 30% 0, #fed7aa, transparent 55%),
        radial-gradient(circle at 80% 120%, #7c2d12, #111827);
      border: 1px solid rgba(248,250,252,0.45);
      box-shadow:
        0 0 0 1px rgba(15,23,42,0.9),
        0 0 18px rgba(251,146,60,0.75);
      position: relative;
    }
    .logo-icon::after{
      content:'';
      position:absolute;
      inset:5px;
      border-radius:999px;
      border:1px solid rgba(15,23,42,0.9);
    }

    .logo-text {
      display: flex;
      flex-direction: column;
      line-height: 1.1;
    }
    .logo-text .brand {
      font-size: 14px;
      font-weight: 800;
      letter-spacing: .12em;
      text-transform: uppercase;
    }
    .logo-text .sub {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .18em;
      color: rgba(248,250,252,0.6);
    }

    .coordinates {
      margin-top: 8px;
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      font-family: monospace;
      font-size: 10px;
      color: rgba(248,250,252,0.6);
    }
    .coordinates span:nth-child(2){
      opacity:.8;
    }

    /* linha de varredura */
    .scan-line {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--color-accent), transparent);
      opacity: .7;
      animation: scan 3.4s linear infinite;
      pointer-events: none;
      z-index: 40;
    }
    @keyframes scan {
      0% { transform: translateY(0); opacity: 0; }
      18% { opacity: 1; }
      100% { transform: translateY(780px); opacity: 0; }
    }

    /* Formulários */
    .form-group { margin-bottom: 16px; position: relative; }
    label {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .14em;
      color: rgba(148,163,184,0.95);
      margin-bottom: 6px;
    }
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-prefix {
      position: absolute;
      left: 10px;
      font-family: monospace;
      font-size: 11px;
      color: var(--color-accent);
      pointer-events: none;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      border-radius: 10px;
      border: 1px solid rgba(148,163,184,0.6);
      background: rgba(15,23,42,0.84);
      color: #e5e7eb;
      padding: 10px 10px 10px 30px;
      font-size: 13px;
      transition: all .25s ease;
    }
    input::placeholder{color:rgba(148,163,184,0.7);}
    input:focus{
      outline:none;
      border-color: var(--color-accent);
      box-shadow: 0 0 0 1px rgba(251,146,60,0.45), 0 0 18px rgba(251,146,60,0.55);
      background: rgba(15,23,42,0.96);
    }

    /* Botões */
    .btn-primary {
      width: 100%;
      background: linear-gradient(90deg, rgba(251,146,60,0.16), rgba(248,250,252,0.02));
      border: 1px solid rgba(251,146,60,0.8);
      color: #fed7aa;
      padding: 13px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 12px;
      letter-spacing: .18em;
      text-transform: uppercase;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all .25s ease;
      position: relative;
      overflow: hidden;
    }
    .btn-primary::before{
      content:'';
      position:absolute;
      inset:-40%;
      background: radial-gradient(circle at 0 0, rgba(255,255,255,0.32), transparent 60%);
      opacity:0;
      transition:opacity .25s;
    }
    .btn-primary:hover{
      background: linear-gradient(90deg,#f97316,#fbbf24);
      color:#111827;
      box-shadow:0 0 24px rgba(251,146,60,0.85);
    }
    .btn-primary:hover::before{opacity:1;}
    .btn-primary:disabled{
      opacity:.5;
      cursor:not-allowed;
      box-shadow:none;
    }

    .btn-text{
      border:none;
      background:none;
      padding:0;
      font-size:11px;
      color:rgba(148,163,184,0.9);
      text-decoration:underline;
      cursor:pointer;
    }
    .btn-text:hover{color:#e5e7eb;}

    /* Toggles */
    .custom-toggle {
      display:flex;
      align-items:center;
      gap:8px;
      font-size:11px;
      color:rgba(148,163,184,0.9);
      cursor:pointer;
      user-select:none;
    }
    .toggle-track{
      width:38px;
      height:20px;
      border-radius:20px;
      background:rgba(15,23,42,0.9);
      border:1px solid rgba(148,163,184,0.8);
      position:relative;
      transition:.25s;
    }
    .toggle-thumb{
      width:14px;
      height:14px;
      border-radius:999px;
      background:#e5e7eb;
      position:absolute;
      top:2px; left:2px;
      box-shadow:0 3px 6px rgba(0,0,0,0.55);
      transition:.25s;
    }
    .custom-toggle.active .toggle-track{
      background:rgba(251,146,60,0.9);
      border-color:rgba(253,186,116,1);
    }
    .custom-toggle.active .toggle-thumb{
      transform:translateX(16px);
      background:#0b1120;
    }

    /* Notificações */
    .notification-area{
      position:absolute;
      inset:14px 16px auto 16px;
      z-index:60;
      pointer-events:none;
    }
    .auth-alert{
      background:rgba(3,7,18,0.96);
      border-radius:9px;
      border:1px solid rgba(248,250,252,0.08);
      padding:8px 10px;
      margin-bottom:8px;
      font-family:monospace;
      font-size:11px;
      color:#e5e7eb;
      pointer-events:auto;
      animation:slideIn .25s ease-out;
    }
    @keyframes slideIn{
      from{opacity:0;transform:translateY(-6px);}
      to{opacity:1;transform:translateY(0);}
    }

    /* Estados de segurança */
    body[data-state="warning"]{--current-color:var(--color-warning);}
    body[data-state="danger"]{--current-color:var(--color-danger);}
    .status-dot{
      width:8px;height:8px;border-radius:999px;
    }

    /* Tela de bloqueio */
    .lockout-overlay{
      position:absolute;
      inset:0;
      background:rgba(15,23,42,0.98);
      display:none;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      text-align:center;
      padding:20px;
      z-index:80;
    }
    body[data-state="danger"] .lockout-overlay{display:flex;}

    .shake{
      animation:shake .5s cubic-bezier(.36,.07,.19,.97) both;
    }
    @keyframes shake{
      10%,90%{transform:translate3d(-1px,0,0);}
      20%,80%{transform:translate3d(2px,0,0);}
      30%,50%,70%{transform:translate3d(-4px,0,0);}
      40%,60%{transform:translate3d(4px,0,0);}
    }

    /* Overlay do questionário */
    .aptitude-overlay{
      position:absolute;
      inset:0;
      background:#020617;
      z-index:70;
      display:none;
      flex-direction:column;
    }
    .aptitude-overlay.active{display:flex;}

    /* Rodapé */
    footer{
      padding:8px 14px 10px;
      font-family:monospace;
      font-size:9px;
      text-align:center;
      color:rgba(148,163,184,0.8);
      border-top:1px solid rgba(148,163,184,0.35);
      background:radial-gradient(circle at 100% 0,rgba(251,146,60,0.12),transparent 55%);
    }

    /* Utilidades */
    .muted{color:rgba(148,163,184,0.9);}
    .small-note{font-size:10px;color:rgba(148,163,184,0.8);margin-top:6px;}

    .quiz-chip{
      border-radius:999px;
      border:1px solid rgba(148,163,184,0.7);
      padding:6px 10px;
      font-size:11px;
      color:rgba(249,250,251,0.9);
      background:rgba(15,23,42,0.8);
      cursor:pointer;
      transition:.2s;
    }
    .quiz-chip.selected{
      background:var(--color-accent);
      border-color:#fed7aa;
      color:#111827;
      box-shadow:0 0 16px rgba(251,146,60,0.8);
    }

  </style>
</head>
<body data-state="normal">

  <div class="bg-layer"></div>
  <div class="grid-overlay"></div>
  <div class="noise-overlay"></div>

  <main class="terminal-wrapper">
    <div class="scan-line"></div>

    <!-- Notificações -->
    <div class="notification-area" id="notification-area"></div>

    <!-- Tela de bloqueio -->
    <div class="lockout-overlay">
      <i class="ri-alarm-warning-fill text-6xl text-red-500 mb-3 animate-pulse"></i>
      <h2 class="text-2xl font-bold text-red-500 mb-1">ACESSO BLOQUEADO</h2>
      <p class="text-xs font-mono text-red-200 mb-4">MÚLTIPAS TENTATIVAS SEM SUCESSO DETECTADAS</p>
      <div class="border border-red-900/80 bg-black/60 rounded px-4 py-3 text-red-400 text-xs font-mono">
        <span id="lockout-timer">Aguarde alguns instantes para tentar novamente…</span>
      </div>
    </div>

    <!-- HEADER APOLLO -->
    <header class="apollo-header">
      <div class="logo-mark">
        <div class="logo-icon"></div>
        <div class="logo-text">
          <span class="brand">apollo::rio</span>
          <span class="sub">portal clubber</span>
        </div>
      </div>
      <div class="coordinates">
        <span id="coordinates">22°54′S · 43°12′W</span>
        <span id="timestamp">--:--:-- BRT</span>
      </div>
    </header>

    <!-- ÁREA SCROLL -->
    <div class="scroll-area">

      <!-- Bloco de status -->
      <div class="flavor-text" style="margin-bottom:10px;">
        <span>APOLLO_GATEWAY_RJ · V2.4</span>
        <span id="clock">--:--:-- UTC</span>
      </div>

      <!-- LOGIN -->
      <section id="login-view">
        <div class="mb-5 p-3 rounded-md border-l-2" style="border-color:var(--color-accent);background:rgba(15,23,42,0.9);">
          <p class="text-[11px] font-mono text-slate-100">
            &gt; link seguro estabelecido<br>
            &gt; aguardando credenciais do clubber…
          </p>
        </div>

        <form id="login-form" autocomplete="off">
          <div class="form-group">
            <label for="user-id">ID clubber ou e-mail</label>
            <div class="input-wrapper">
              <span class="input-prefix">&gt;</span>
              <input id="user-id" type="text" placeholder="CC-00000 ou voce@email.com" required>
            </div>
          </div>

          <div class="form-group">
            <label for="user-pass">Chave de acesso</label>
            <div class="input-wrapper">
              <span class="input-prefix">#</span>
              <input id="user-pass" type="password" placeholder="••••••••" required>
            </div>
          </div>

          <div class="flex items-center justify-between mb-7 mt-3">
            <div class="custom-toggle" id="remember-toggle">
              <div class="toggle-track"><div class="toggle-thumb"></div></div>
              <span>manter sessão ativa</span>
            </div>
            <button type="button" class="btn-text">esqueci minha chave</button>
          </div>

          <button type="submit" class="btn-primary">
            <span>entrar no portal</span>
            <i class="ri-arrow-right-line text-sm"></i>
          </button>
        </form>

        <div class="mt-7 border-t border-slate-600/40 pt-4 text-center">
          <p class="text-[11px] uppercase tracking-[0.16em] text-slate-400 mb-1">primeira vez por aqui?</p>
          <button id="btn-to-register" type="button"
            style="color:var(--color-accent);font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;">
            iniciar registro
          </button>
        </div>
      </section>

      <!-- REGISTRO -->
      <section id="register-view" class="hidden">
        <div class="mb-5 text-xs font-mono text-center rounded-md px-3 py-2"
             style="border:1px solid rgba(251,146,60,0.9); background:rgba(251,146,60,0.08); color:#fed7aa;">
          NOVO PERFIL APOLLO · PREENCHE SEUS DADOS E RESPONDA AO QUIZ
        </div>

        <form id="register-form" autocomplete="off">
          <div class="form-group">
            <label for="reg-name">Nome completo</label>
            <div class="input-wrapper">
              <span class="input-prefix">@</span>
              <input id="reg-name" type="text" placeholder="Nome igual ao documento" required>
            </div>
          </div>

          <div class="form-group">
            <label for="reg-doc">CPF ou Passaporte</label>
            <div class="input-wrapper">
              <span class="input-prefix">ID</span>
              <!-- texto livre: aceita CPF ou passaporte -->
              <input id="reg-doc" type="text" placeholder="000.000.000-00 ou passaporte" required>
            </div>
          </div>

          <div class="form-group">
            <label for="reg-email">E-mail principal</label>
            <div class="input-wrapper">
              <span class="input-prefix">&gt;</span>
              <input id="reg-email" type="email" placeholder="voce@email.com" required>
            </div>
          </div>

          <div class="form-group">
            <label for="reg-pass">Crie sua chave</label>
            <div class="input-wrapper">
              <span class="input-prefix">*</span>
              <input id="reg-pass" type="password" placeholder="mínimo 8 caracteres" minlength="8" required>
            </div>
          </div>

          <div class="my-5 p-3 rounded-lg border border-slate-700/70 bg-slate-900/70">
            <div class="custom-toggle" id="terms-toggle">
              <div class="toggle-track"><div class="toggle-thumb"></div></div>
              <span class="text-[10px] leading-tight">
                Eu aceito os protocolos de convivência, respeito e privacidade do coletivo Apollo.
              </span>
            </div>
            <p class="small-note">
              Sem spam, sem venda de dados. Usamos seu perfil apenas para curadoria de eventos e segurança básica.
            </p>
          </div>

          <button type="submit" class="btn-primary">
            <span>começar quiz de acesso</span>
            <i class="ri-cpu-line text-sm"></i>
          </button>

          <div class="mt-6 text-center">
            <button type="button" id="btn-to-login" class="btn-text">
              cancelar · voltar para login
            </button>
          </div>
        </form>
      </section>

    </div>

    <!-- RODAPÉ -->
    <footer>
      NÓ APOLLO::RIO · camada de registro clubber · criptografia ponta-a-ponta
    </footer>

    <!-- OVERLAY QUIZ -->
    <section class="aptitude-overlay" id="aptitude-module">
      <div class="p-5 flex items-center justify-between border-b border-slate-700/80">
        <div class="text-left">
          <p class="text-[10px] uppercase tracking-[0.16em] text-slate-400 mb-1">módulo de acesso</p>
          <h2 class="text-[18px] font-bold text-amber-200">quiz de comportamento & pista</h2>
        </div>
        <div id="test-step"
             class="text-[10px] font-mono px-2 py-1 rounded border border-slate-500/70 text-slate-200">
          ETAPA 1/3
        </div>
      </div>

      <div id="test-content-area"
           class="flex-1 flex flex-col items-center justify-center text-center px-6 py-6">
        <!-- conteúdo dinâmico -->
        <div class="text-4xl mb-3 animate-spin">❖</div>
        <p class="text-xs text-slate-400 font-mono">carregando módulo…</p>
      </div>

      <div class="p-4 border-t border-slate-700/80">
        <button id="btn-test-action" class="btn-primary">
          <span>começar</span>
        </button>
      </div>
    </section>

  </main>

  <script>
  'use strict';

  /* CONFIGURAÇÃO */
  const CONFIG = {
    maxAttempts: 3,
    lockoutDuration: 30000,
    colors: {
      normal: '#ffffff',
      accent: '#fb923c',
      warning: '#facc15',
      danger: '#f97373'
    },
    credentials: {
      id: 'CC-31415',
      key: 'CRYPTID-X'
    },
    eventSounds: ['House','Disco','Techno','Dark','Psytrance']
  };

  const STATE = {
    attempts: 0,
    isLocked: false,
    currentView: 'login',
    activeTest: 0
  };

  /* ELEMENTOS */
  const els = {
    body: document.body,
    terminal: document.querySelector('.terminal-wrapper'),
    loginView: document.getElementById('login-view'),
    registerView: document.getElementById('register-view'),
    aptitudeModule: document.getElementById('aptitude-module'),
    notificationArea: document.getElementById('notification-area'),
    statusLight: null, // vamos criar dinamicamente
    clock: document.getElementById('clock'),
    timestamp: document.getElementById('timestamp'),
    testContent: document.getElementById('test-content-area'),
    testStep: document.getElementById('test-step'),
    testBtn: document.getElementById('btn-test-action'),
    lockoutTimer: document.getElementById('lockout-timer')
  };

  /* UTIL */
  function updateClock() {
    const now = new Date();
    const utc = now.toISOString().split('T')[1].split('.')[0];
    els.clock.textContent = utc + ' UTC';

    const brt = now.toLocaleTimeString('pt-BR', { hour12:false });
    els.timestamp.textContent = brt + ' BRT';
  }

  function setSecurityState(state) {
    els.body.setAttribute('data-state', state);
  }

  function notify(msg, type='info') {
    const div = document.createElement('div');
    div.className = 'auth-alert';
    div.textContent = '> ' + msg;

    if (type === 'error') {
      div.style.borderColor = CONFIG.colors.danger;
      div.style.color = CONFIG.colors.danger;
    } else if (type === 'success') {
      div.style.borderColor = CONFIG.colors.accent;
      div.style.color = CONFIG.colors.accent;
    } else {
      div.style.borderColor = 'rgba(148,163,184,0.7)';
      div.style.color = '#e5e7eb';
    }

    els.notificationArea.appendChild(div);

    setTimeout(() => {
      div.style.opacity = '0';
      setTimeout(() => div.remove(), 400);
    }, 3500);
  }

  function playShake() {
    els.terminal.classList.remove('shake');
    void els.terminal.offsetWidth;
    els.terminal.classList.add('shake');
  }

  /* LOGIN */
  function handleLogin(e) {
    e.preventDefault();
    if (STATE.isLocked) return;

    const id = document.getElementById('user-id').value.trim();
    const pass = document.getElementById('user-pass').value.trim();

    const btn = e.target.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> verificando…';
    btn.disabled = true;

    setTimeout(() => {
      if (id === CONFIG.credentials.id && pass === CONFIG.credentials.key) {
        notify('acesso liberado · bem-vinde de volta.', 'success');
        STATE.attempts = 0;
      } else {
        STATE.attempts++;
        playShake();

        if (STATE.attempts === 1) {
          notify('credenciais inválidas · tente novamente.', 'error');
        } else if (STATE.attempts === 2) {
          setSecurityState('warning');
          notify('atenção · atividade suspeita detectada.', 'error');
        } else if (STATE.attempts >= CONFIG.maxAttempts) {
          setSecurityState('danger');
          STATE.isLocked = true;
          notify('sistema bloqueado temporariamente por segurança.', 'error');

          let remaining = CONFIG.lockoutDuration / 1000;
          const interval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
              clearInterval(interval);
              STATE.isLocked = false;
              STATE.attempts = 0;
              setSecurityState('normal');
              notify('bloqueio encerrado · você pode tentar novamente.', 'success');
            }
            if (els.lockoutTimer) {
              els.lockoutTimer.textContent = 'Bloqueio temporário. Aguarde ' + remaining + 's…';
            }
          }, 1000);
        }
      }

      btn.innerHTML = original;
      btn.disabled = false;
    }, 900);
  }

  /* REGISTRO */
  function handleRegister(e) {
    e.preventDefault();

    const name = document.getElementById('reg-name').value.trim();
    const doc  = document.getElementById('reg-doc').value.trim();
    const email= document.getElementById('reg-email').value.trim();
    const pass = document.getElementById('reg-pass').value.trim();
    const termsToggle = document.getElementById('terms-toggle');

    if (!name || !doc || !email || !pass) {
      notify('preencha todos os campos do cadastro.', 'error');
      playShake();
      return;
    }

    if (!termsToggle.classList.contains('active')) {
      notify('você precisa aceitar os protocolos do coletivo.', 'error');
      playShake();
      return;
    }

    notify('dados básicos ok · abrindo quiz de acesso…', 'success');
    setTimeout(openAptitudeTest, 800);
  }

  /* QUIZ */
  function openAptitudeTest() {
    els.aptitudeModule.classList.add('active');
    runTest(1);
  }

  function attachSingleChoiceListener() {
    const options = els.testContent.querySelectorAll('.test-option');
    options.forEach(btn => {
      btn.addEventListener('click', () => {
        const multi = btn.dataset.multi === 'true';
        if (!multi) {
          options.forEach(b => b.classList.remove('selected'));
        }
        btn.classList.toggle('selected');
      });
    });
  }

  function runTest(step) {
    STATE.activeTest = step;
    els.testStep.textContent = `ETAPA ${step}/3`;
    els.testBtn.style.display = 'flex';
    els.testBtn.textContent = step < 3 ? 'confirmar resposta' : 'finalizar registro';

    if (step === 1) {
      els.testContent.innerHTML = `
        <h3 class="text-[16px] font-semibold text-amber-100 mb-4">percepção de padrão</h3>
        <p class="text-[11px] text-slate-400 mb-4 max-w-xs">
          qual símbolo completa a sequência abaixo de forma lógica?
        </p>
        <div class="text-4xl font-mono tracking-[0.4em] mb-6 text-amber-200">
          △ □ ○ ?
        </div>
        <div class="grid grid-cols-2 gap-3 w-full max-w-xs">
          <button class="test-option p-3 border border-slate-600/70 rounded-md text-2xl bg-slate-900/60 hover:bg-slate-800/80 transition" data-val="tri">
            △
          </button>
          <button class="test-option p-3 border border-slate-600/70 rounded-md text-2xl bg-slate-900/60 hover:bg-slate-800/80 transition" data-val="hex">
            ⬡
          </button>
          <button class="test-option p-3 border border-slate-600/70 rounded-md text-2xl bg-slate-900/60 hover:bg-slate-800/80 transition" data-val="star">
            ★
          </button>
          <button class="test-option p-3 border border-slate-600/70 rounded-md text-2xl bg-slate-900/60 hover:bg-slate-800/80 transition" data-val="sq">
            □
          </button>
        </div>
      `;
      attachSingleChoiceListener();
    } else if (step === 2) {
      els.testContent.innerHTML = `
        <h3 class="text-[16px] font-semibold text-amber-100 mb-4">ética de cena</h3>
        <p class="text-[11px] text-slate-400 mb-4 max-w-xs">
          alguém diz: <span class="italic">"não gosto quando misturam eletrônico com funk / tribal / techno"</span>.
          qual atitude mais alinhada com a cultura da pista?
        </p>
        <div class="space-y-2 w-full max-w-xs text-left">
          <button class="test-option w-full p-3 border border-slate-600/80 rounded-md text-[11px] bg-slate-900/70 hover:bg-slate-800/90 transition" data-val="bad">
            critico alto, falo mal nas redes e desmereço o trabalho de quem vive disso.
          </button>
          <button class="test-option w-full p-3 border border-slate-600/80 rounded-md text-[11px] bg-slate-900/70 hover:bg-slate-800/90 transition" data-val="good">
            respeito que não é meu gosto, mas entendo que é arte, renda e espaço de outras pessoas.
          </button>
        </div>
      `;
      attachSingleChoiceListener();
    } else if (step === 3) {
      els.testContent.innerHTML = `
        <h3 class="text-[16px] font-semibold text-amber-100 mb-3">som de pista</h3>
        <p class="text-[11px] text-slate-400 mb-4 max-w-xs">
          quais sons você mais procura em um rolê? (pode marcar mais de um)
        </p>
        <div id="sound-options" class="flex flex-wrap gap-2 justify-center w-full max-w-xs"></div>
      `;

      const container = document.getElementById('sound-options');
      CONFIG.eventSounds.forEach(sound => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'quiz-chip test-option';
        btn.dataset.multi = 'true';
        btn.textContent = sound;
        container.appendChild(btn);
      });

      attachSingleChoiceListener();
    }
  }

  function handleTestNext() {
    const selected = els.testContent.querySelectorAll('.test-option.selected');

    if (STATE.activeTest === 1 || STATE.activeTest === 2) {
      if (selected.length === 0) {
        notify('seleciona uma opção antes de continuar.', 'error');
        playShake();
        return;
      }
      runTest(STATE.activeTest + 1);
      return;
    }

    if (STATE.activeTest === 3) {
      if (selected.length === 0) {
        notify('marca pelo menos um estilo de som que você gosta.', 'error');
        playShake();
        return;
      }

      // Finalização
      els.testContent.innerHTML = `
        <i class="ri-checkbox-circle-fill text-6xl text-emerald-400 mb-3"></i>
        <h2 class="text-xl font-bold text-emerald-100 mb-1">registro concluído</h2>
        <p class="text-[11px] text-slate-300 max-w-xs mx-auto">
          seu perfil clubber foi criado com sucesso. a partir de agora,
          usamos essas respostas só para curar melhor seus convites e zelar pela pista.
        </p>
      `;
      els.testBtn.style.display = 'none';

      notify('bem-vinde à tripulação apollo::rio ✦', 'success');

      setTimeout(() => {
        els.aptitudeModule.classList.remove('active');
        els.registerView.classList.add('hidden');
        els.loginView.classList.remove('hidden');
      }, 2600);
    }
  }

  /* INIT */
  document.addEventListener('DOMContentLoaded', () => {
    updateClock();
    setInterval(updateClock, 1000);

    // botão “ir para registro”
    document.getElementById('btn-to-register').addEventListener('click', () => {
      els.loginView.classList.add('hidden');
      els.registerView.classList.remove('hidden');
    });

    // voltar para login
    document.getElementById('btn-to-login').addEventListener('click', () => {
      els.registerView.classList.add('hidden');
      els.loginView.classList.remove('hidden');
    });

    // toggles
    document.querySelectorAll('.custom-toggle').forEach(t => {
      t.addEventListener('click', () => t.classList.toggle('active'));
    });

    // forms
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    document.getElementById('register-form').addEventListener('submit', handleRegister);

    // botão do quiz
    els.testBtn.addEventListener('click', handleTestNext);
  });
  </script>
</body>
</html>
