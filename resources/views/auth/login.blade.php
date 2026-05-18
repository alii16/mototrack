<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- SEO & Description -->
  <meta name="description" content="Masuk ke akun MotoTrack Anda untuk memantau dan mengelola kendaraan secara real-time.">
  <meta name="keywords" content="MotoTrack, login, masuk akun, GPS tracker, tracking motor">
  <meta name="author" content="MotoTrack">
  <meta name="robots" content="noindex, nofollow">

  <!-- Open Graph -->
  <meta property="og:title" content="MotoTrack — Login">
  <meta property="og:description" content="Masuk ke akun MotoTrack untuk memantau kendaraan Anda kapan saja dan di mana saja.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:image" content="{{ asset('img/logo-footer.png') }}">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:alt" content="MotoTrack – GPS & ESP32 Integration">
  <meta property="og:locale" content="id_ID">
  <meta property="og:site_name" content="MotoTrack">

  <!-- Theme & App -->
  <meta name="theme-color" content="#1e40af">
  <meta name="application-name" content="MotoTrack">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="MotoTrack">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <title>MotoTrack — Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <link rel="shortcut icon" href="{{asset ('logo.svg')}}" type="image/x-icon">
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] } } }
    }
  </script>
  <style>
    /* ── Base ── */
    *, *::before, *::after { box-sizing: border-box; }

    /* ── Animations ── */
    @keyframes fade-up {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fade-in {
      from { opacity: 0; }
      to   { opacity: 1; }
    }
    @keyframes float-slow {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50%       { transform: translateY(-20px) rotate(3deg); }
    }
    @keyframes float-mid {
      0%, 100% { transform: translateY(0px) rotate(-2deg); }
      50%       { transform: translateY(-12px) rotate(2deg); }
    }
    @keyframes spin-slow {
      from { transform: rotate(0deg); }
      to   { transform: rotate(360deg); }
    }
    @keyframes spin-rev {
      from { transform: rotate(0deg); }
      to   { transform: rotate(-360deg); }
    }
    @keyframes ping-ring {
      0%   { transform: scale(1); opacity: .5; }
      100% { transform: scale(2.5); opacity: 0; }
    }
    @keyframes road-scroll {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); }
    }
    @keyframes bike-ride {
      0%, 100% { transform: translateY(0px); }
      50%       { transform: translateY(-4px); }
    }
    @keyframes signal-pulse {
      0%   { opacity: 1; transform: scale(1); }
      100% { opacity: 0; transform: scale(3); }
    }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0; }
    }
    @keyframes slide-in-left {
      from { opacity: 0; transform: translateX(-40px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes slide-in-right {
      from { opacity: 0; transform: translateX(40px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes number-tick {
      0%   { transform: translateY(0); }
      50%  { transform: translateY(-100%); }
      50.01% { transform: translateY(100%); }
      100% { transform: translateY(0); }
    }
    @keyframes dash-move {
      from { stroke-dashoffset: 1000; }
      to   { stroke-dashoffset: 0; }
    }
    @keyframes glow-pulse {
      0%, 100% { box-shadow: 0 0 20px rgba(37,99,235,.3); }
      50%       { box-shadow: 0 0 40px rgba(37,99,235,.6), 0 0 60px rgba(37,99,235,.2); }
    }
    @keyframes particle-float {
      0%   { transform: translateY(0) translateX(0) scale(1); opacity: .8; }
      100% { transform: translateY(-120px) translateX(var(--px)) scale(0); opacity: 0; }
    }
    @keyframes scanline {
      0%   { top: -10%; }
      100% { top: 110%; }
    }
    @keyframes counter-up {
      from { transform: translateY(8px); opacity: 0; }
      to   { transform: translateY(0); opacity: 1; }
    }
    @keyframes border-trace {
      0%   { stroke-dashoffset: 800; }
      100% { stroke-dashoffset: 0; }
    }

    /* ── Utility classes ── */
    .fade-up     { animation: fade-up .6s cubic-bezier(.22,1,.36,1) both; }
    .fade-in     { animation: fade-in .5s ease both; }
    .float-slow  { animation: float-slow 6s ease-in-out infinite; }
    .float-mid   { animation: float-mid 4s ease-in-out infinite; }
    .spin-slow   { animation: spin-slow 20s linear infinite; }
    .spin-rev    { animation: spin-rev 15s linear infinite; }
    .bike-ride   { animation: bike-ride 1.2s ease-in-out infinite; }
    .blink       { animation: blink 1s step-start infinite; }
    .glow-pulse  { animation: glow-pulse 2.5s ease-in-out infinite; }

    /* ── Background canvas layer ── */
    #bgCanvas {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    /* ── Grid overlay ── */
    .grid-overlay {
      position: fixed;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(37,99,235,.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(37,99,235,.05) 1px, transparent 1px);
      background-size: 40px 40px;
    }

    /* ── GPS coordinate trails ── */
    .gps-trail {
      position: fixed;
      z-index: 1;
      pointer-events: none;
      font-family: 'Plus Jakarta Sans', monospace;
      font-size: 9px;
      font-weight: 700;
      color: rgba(37,99,235,.18);
      white-space: nowrap;
      letter-spacing: .05em;
    }

    /* ── Form card ── */
    .form-card {
      background: rgba(255,255,255,.92);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255,255,255,.8);
      box-shadow:
        0 32px 80px rgba(37,99,235,.10),
        0 8px 24px rgba(0,0,0,.06),
        inset 0 1px 0 rgba(255,255,255,.9);
    }

    /* ── Input style ── */
    .gps-input {
      width: 100%;
      padding: 11px 14px 11px 42px;
      background: rgba(248,250,252,.8);
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 13px;
      font-weight: 500;
      color: #1e293b;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .gps-input:focus {
      border-color: #2563eb;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(37,99,235,.08), 0 2px 8px rgba(37,99,235,.1);
    }
    .gps-input::placeholder { color: #94a3b8; }

    /* ── Input icon wrapper ── */
    .input-wrapper { position: relative; }
    .input-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      pointer-events: none;
      transition: color .2s;
    }
    .input-wrapper:focus-within .input-icon { color: #2563eb; }

    /* ── Password toggle ── */
    .pw-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #94a3b8;
      padding: 2px;
      transition: color .2s;
    }
    .pw-toggle:hover { color: #2563eb; }

    /* ── Submit button ── */
    .btn-login {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #1d4ed8, #2563eb, #3b82f6);
      background-size: 200% 200%;
      border: none;
      border-radius: 12px;
      color: white;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: transform .15s, box-shadow .15s;
      box-shadow: 0 4px 20px rgba(37,99,235,.35);
    }
    .btn-login:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 32px rgba(37,99,235,.45);
    }
    .btn-login:active { transform: translateY(0); }
    .btn-login::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, transparent 30%, rgba(255,255,255,.15) 50%, transparent 70%);
      transform: translateX(-100%);
      transition: transform .5s;
    }
    .btn-login:hover::before { transform: translateX(100%); }

    /* ── Checkbox ── */
    .gps-checkbox {
      width: 16px; height: 16px;
      border: 1.5px solid #cbd5e1;
      border-radius: 5px;
      appearance: none;
      cursor: pointer;
      background: white;
      transition: all .15s;
      flex-shrink: 0;
      position: relative;
    }
    .gps-checkbox:checked {
      background: #2563eb;
      border-color: #2563eb;
    }
    .gps-checkbox:checked::after {
      content: '';
      position: absolute;
      left: 4px; top: 1.5px;
      width: 5px; height: 9px;
      border: 2px solid white;
      border-top: none; border-left: none;
      transform: rotate(45deg);
    }

    /* ── Live status dots ── */
    .status-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      display: inline-block;
    }
    .status-dot.online { background: #22c55e; animation: blink 2s ease-in-out infinite; }
    .status-dot.active { background: #3b82f6; animation: blink 1.5s ease-in-out infinite; }

    /* ── Satellite orbit ── */
    .orbit-ring {
      position: absolute;
      border-radius: 50%;
      border: 1px dashed rgba(37,99,235,.15);
    }

    /* ── Ping dot ── */
    .ping-dot::before {
      content: '';
      position: absolute;
      inset: -4px;
      border-radius: 50%;
      background: currentColor;
      opacity: .4;
      animation: signal-pulse 1.5s ease-out infinite;
    }
    .ping-dot::after {
      content: '';
      position: absolute;
      inset: -8px;
      border-radius: 50%;
      background: currentColor;
      opacity: .2;
      animation: signal-pulse 1.5s ease-out infinite .5s;
    }

    /* ── Left panel ── */
    .left-panel {
      background: linear-gradient(160deg, #0f172a 0%, #1e3a8a 45%, #1d4ed8 100%);
    }

    /* ── Scanline effect ── */
    .scanline {
      position: absolute;
      left: 0; right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, rgba(37,99,235,.4), transparent);
      animation: scanline 4s linear infinite;
      pointer-events: none;
    }

    /* ── Road strip animation ── */
    .road-strip {
      animation: road-scroll 3s linear infinite;
    }

    /* ── Error shake ── */
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%       { transform: translateX(-6px); }
      40%       { transform: translateX(6px); }
      60%       { transform: translateX(-4px); }
      80%       { transform: translateX(4px); }
    }
    .shake { animation: shake .4s ease both; }

    /* ── Loading spinner ── */
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    .spinner {
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin .6s linear infinite;
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
      .left-panel { display: none !important; }
      .right-panel { border-radius: 0 !important; }
    }
  </style>
</head>

<body class="font-sans bg-slate-900 min-h-screen overflow-hidden">

  <!-- ══════ ANIMATED BACKGROUND ══════ -->
  <canvas id="bgCanvas"></canvas>
  <div class="grid-overlay"></div>

  <!-- GPS coordinate trails -->
  <div class="gps-trail" style="top:12%; left:2%; animation: fade-in 1s 1s both;">-8.1845° S, 113.7123° E</div>
  <div class="gps-trail" style="top:35%; left:1.5%; animation: fade-in 1s 1.5s both;">ALT: 124m · SPEED: 0 km/h</div>
  <div class="gps-trail" style="top:62%; left:2%; animation: fade-in 1s 2s both;">SAT: 12/14 · HDOP: 0.8</div>
  <div class="gps-trail" style="top:80%; left:2%; animation: fade-in 1s 2.5s both;">SIG: ████████░░ 82%</div>
  <div class="gps-trail" style="top:18%; right:2%; animation: fade-in 1s 1.2s both; text-align:right">DEVICE: MT-ESP32-001</div>
  <div class="gps-trail" style="top:45%; right:2%; animation: fade-in 1s 1.8s both; text-align:right">NET: CONNECTED · WiFi</div>
  <div class="gps-trail" style="top:72%; right:2%; animation: fade-in 1s 2.2s both; text-align:right">UPTIME: 99.97%</div>

  <!-- ══════ MAIN LAYOUT ══════ -->
  <div class="relative z-10 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-4xl flex rounded-2xl overflow-hidden shadow-2xl" style="min-height: 580px; animation: fade-up .7s cubic-bezier(.22,1,.36,1) both .2s; box-shadow: 0 40px 100px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.05);">

      <!-- ══════ LEFT PANEL — GPS VISUAL ══════ -->
      <div class="left-panel hidden md:flex flex-col justify-between w-[45%] p-8 relative overflow-hidden flex-shrink-0">

        <!-- Scanline -->
        <div class="scanline"></div>

        <!-- Orbit rings -->
        <div class="orbit-ring" style="width:280px;height:280px;top:50%;left:50%;transform:translate(-50%,-50%)"></div>
        <div class="orbit-ring" style="width:380px;height:380px;top:50%;left:50%;transform:translate(-50%,-50%); opacity:.5"></div>
        <div class="orbit-ring spin-slow" style="width:320px;height:320px;top:50%;left:50%;transform:translate(-50%,-50%); border-color: rgba(59,130,246,.2)"></div>
        <div class="orbit-ring spin-rev" style="width:240px;height:240px;top:50%;left:50%;transform:translate(-50%,-50%); border-color: rgba(99,102,241,.2)"></div>

        <!-- TOP: Logo + Brand -->
        <div class="relative z-10 fade-in" style="animation-delay:.4s">
          <div class="flex items-center gap-3 mb-6">
            <img src="{{ asset('img/logo-aside.png') }}" alt="MotoTrack" class="w-8 h-8 rounded-lg border border-blue-400/30 object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
            {{-- <div class="w-10 h-10 bg-blue-500/20 rounded-xl border border-blue-400/30 flex items-center justify-center overflow-hidden">
              <div style="display:none" class="w-full h-full items-center justify-center">
                <i data-lucide="bike" style="width:18px;height:18px;color:#60a5fa"></i>
              </div>
            </div> --}}
            <div>
              <div class="font-black text-[17px] text-white leading-none">MotoTrack</div>
              <div class="text-[9px] text-blue-300/70 font-semibold tracking-widest uppercase mt-0.5">GPS & ESP32 Integration</div>
            </div>
          </div>

          <!-- Live status bar -->
          <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-3 py-2">
            <span class="status-dot online"></span>
            <span class="text-[10px] text-green-400 font-bold">Sistem Online</span>
            <span class="text-white/20 text-[10px]">·</span>
            <span class="status-dot active"></span>
            <span class="text-[10px] text-blue-300 font-bold">1 Perangkat Aktif</span>
          </div>
        </div>

        <!-- CENTER: GPS Illustration -->
        <div class="relative z-10 flex items-center justify-center flex-1 py-6">

          <!-- Center GPS circle -->
          <div class="relative flex items-center justify-center">

            <!-- Ping rings -->
            <div style="position:absolute;width:180px;height:180px;border-radius:50%;border:1.5px solid rgba(59,130,246,.3);animation:ping-ring 3s ease-out infinite"></div>
            <div style="position:absolute;width:180px;height:180px;border-radius:50%;border:1.5px solid rgba(59,130,246,.3);animation:ping-ring 3s ease-out infinite .8s"></div>
            <div style="position:absolute;width:180px;height:180px;border-radius:50%;border:1.5px solid rgba(59,130,246,.3);animation:ping-ring 3s ease-out infinite 1.6s"></div>

            <!-- Main circle -->
            <div class="relative w-[130px] h-[130px] rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, rgba(37,99,235,.3), rgba(99,102,241,.2)); border: 1.5px solid rgba(99,162,255,.3); box-shadow: 0 0 40px rgba(37,99,235,.3), inset 0 0 30px rgba(37,99,235,.1);">

              <!-- Bike SVG -->
              <div class="float-mid">
                <svg width="70" height="56" viewBox="0 0 70 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <!-- Wheels -->
                  <circle cx="14" cy="44" r="11" stroke="#60a5fa" stroke-width="2.5" fill="rgba(37,99,235,.2)"/>
                  <circle cx="56" cy="44" r="11" stroke="#60a5fa" stroke-width="2.5" fill="rgba(37,99,235,.2)"/>
                  <!-- Wheel spokes -->
                  <line x1="14" y1="33" x2="14" y2="55" stroke="#93c5fd" stroke-width="1.2"/>
                  <line x1="3"  y1="44" x2="25" y2="44" stroke="#93c5fd" stroke-width="1.2"/>
                  <line x1="48" y1="33" x2="64" y2="55" stroke="#93c5fd" stroke-width="1.2"/>
                  <line x1="48" y1="55" x2="64" y2="33" stroke="#93c5fd" stroke-width="1.2"/>
                  <!-- Frame -->
                  <path d="M14 44 L30 16 L56 44" stroke="#3b82f6" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                  <path d="M30 16 L44 44" stroke="#3b82f6" stroke-width="2.2" stroke-linecap="round"/>
                  <path d="M30 16 L22 16" stroke="#3b82f6" stroke-width="2.2" stroke-linecap="round"/>
                  <!-- Handlebars -->
                  <path d="M50 29 L62 24 M56 26.5 L56 20" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round"/>
                  <!-- Seat -->
                  <path d="M28 18 L35 18" stroke="#60a5fa" stroke-width="3" stroke-linecap="round"/>
                  <!-- GPS dot on bike -->
                  <circle cx="35" cy="10" r="4" fill="#22c55e"/>
                  <circle cx="35" cy="10" r="2" fill="white"/>
                  <line x1="35" y1="14" x2="35" y2="18" stroke="#22c55e" stroke-width="1.5"/>
                  <!-- Signal waves -->
                  <path d="M56 44 Q62 38 58 30" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" fill="none" opacity=".6"/>
                  <path d="M56 44 Q66 35 61 24" stroke="#22c55e" stroke-width="1.2" stroke-linecap="round" fill="none" opacity=".3"/>
                </svg>
              </div>

              <!-- GPS coordinate label -->
              <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap">
                <div class="text-[9px] font-bold text-blue-300/80 text-center tracking-wider">
                  <span class="blink text-green-400">●</span> LIVE TRACKING
                </div>
              </div>
            </div>

            <!-- Satellite nodes -->
            <div class="absolute" style="top:-18px;left:50%;transform:translateX(-50%)">
              <div class="relative w-3 h-3 rounded-full bg-blue-400 ping-dot" style="color:#60a5fa"></div>
            </div>
            <div class="absolute" style="right:-20px;top:50%;transform:translateY(-50%)">
              <div class="relative w-2.5 h-2.5 rounded-full bg-indigo-400"></div>
            </div>
            <div class="absolute" style="bottom:-20px;left:30%;transform:translateX(-50%)">
              <div class="relative w-2 h-2 rounded-full bg-blue-500/60"></div>
            </div>
          </div>
        </div>

        <!-- BOTTOM: Road animation + stats -->
        <div class="relative z-10">

          <!-- Road strip -->
          <div class="rounded-xl overflow-hidden mb-4" style="background:#0f172a; border:1px solid rgba(255,255,255,.08)">
            <div style="height:44px; position:relative; overflow:hidden;">
              <svg width="200%" height="44" style="animation: road-scroll 3s linear infinite" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="44" fill="#1e293b"/>
                <line x1="0" y1="22" x2="3000" y2="22" stroke="#334155" stroke-width="1"/>
                <line x1="0" y1="22" x2="3000" y2="22" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="24 16"/>
                <!-- Bike on road -->
                <g style="animation: bike-ride 1.2s ease-in-out infinite">
                  <rect x="20" y="14" width="26" height="15" rx="3.5" fill="#2563eb" opacity=".9"/>
                  <circle cx="24" cy="34" r="5" fill="none" stroke="#60a5fa" stroke-width="1.8"/>
                  <circle cx="42" cy="34" r="5" fill="none" stroke="#60a5fa" stroke-width="1.8"/>
                  <text x="25" y="25" font-family="Plus Jakarta Sans,sans-serif" font-weight="900" font-size="8" fill="white">GPS</text>
                </g>
              </svg>
              <div style="position:absolute;right:10px;top:50%;transform:translateY(-50%);display:flex;align-items:center;gap:6px">
                <div class="blink" style="width:6px;height:6px;border-radius:50%;background:#22c55e"></div>
                <span style="font-size:9px;font-weight:700;color:#64748b">AKTIF</span>
              </div>
            </div>
          </div>

          <!-- Mini stats -->
          <div class="grid grid-cols-3 gap-2">
            <div class="bg-white/5 rounded-xl p-2.5 border border-white/8 text-center fade-in" style="animation-delay:.8s">
              <div class="text-[16px] font-black text-white">ESP32</div>
              <div class="text-[8px] text-blue-300/70 font-bold uppercase tracking-wide">Board</div>
            </div>
            <div class="bg-white/5 rounded-xl p-2.5 border border-white/8 text-center fade-in" style="animation-delay:.9s">
              <div class="text-[16px] font-black text-white">99.9<span class="text-[10px]">%</span></div>
              <div class="text-[8px] text-blue-300/70 font-bold uppercase tracking-wide">Uptime</div>
            </div>
            <div class="bg-white/5 rounded-xl p-2.5 border border-white/8 text-center fade-in" style="animation-delay:1s">
              <div class="text-[16px] font-black text-white">WiFi</div>
              <div class="text-[8px] text-blue-300/70 font-bold uppercase tracking-wide">Jaringan</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════ RIGHT PANEL — LOGIN FORM ══════ -->
      <div class="right-panel form-card flex-1 flex flex-col justify-center p-8 md:p-10">

        <!-- Header -->
        <div class="mb-8 fade-up" style="animation-delay:.3s">
          <!-- Mobile logo -->
          <div class="flex items-center gap-2.5 mb-6 md:hidden">
            <img src="{{ asset('img/logo-aside.png') }}" alt="MotoTrack" class="w-8 h-8 rounded-lg border border-blue-400/30 object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
            {{-- <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
              <i data-lucide="bike" style="width:16px;height:16px;color:white"></i>
            </div> --}}
            <div>
              <div class="font-extrabold text-[14px] text-slate-800">MotoTrack</div>
              <div class="text-[8px] text-slate-400 font-semibold tracking-wide">IoT GPS System</div>
            </div>
          </div>

          <div class="inline-flex items-center gap-1.5 bg-blue-50 border border-blue-100 px-3 py-1 rounded-full mb-3">
            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 blink"></div>
            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Sistem Aktif</span>
          </div>
          <h1 class="font-black text-[26px] text-slate-900 leading-tight mb-1">
            Selamat Datang 👋
          </h1>
          <p class="text-slate-500 text-[13px] font-medium">
            Masuk ke dashboard GPS tracking kamu
          </p>
        </div>

        <!-- Session status -->
        @if(session('status'))
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3 fade-up" style="animation-delay:.35s">
          <i data-lucide="check-circle" style="width:14px;height:14px;color:#16a34a;flex-shrink:0"></i>
          <span class="text-[12px] font-semibold text-green-700">{{ session('status') }}</span>
        </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
          @csrf

          <!-- Email -->
          <div class="mb-4 fade-up" style="animation-delay:.4s">
            <label for="email" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide mb-2">
              Email Address
            </label>
            <div class="input-wrapper">
              <i data-lucide="mail" style="width:15px;height:15px" class="input-icon"></i>
              <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                class="gps-input @error('email') border-red-400 @enderror"
                placeholder="nama@example.com"
                required
                autofocus
                autocomplete="username"
              >
            </div>
            @error('email')
            <div class="flex items-center gap-1.5 mt-1.5">
              <i data-lucide="alert-circle" style="width:11px;height:11px;color:#ef4444;flex-shrink:0"></i>
              <span class="text-[11px] text-red-500 font-semibold">{{ $message }}</span>
            </div>
            @enderror
          </div>

          <!-- Password -->
          <div class="mb-4 fade-up" style="animation-delay:.45s">
            <label for="password" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wide mb-2">
              Password
            </label>
            <div class="input-wrapper">
              <i data-lucide="lock" style="width:15px;height:15px" class="input-icon"></i>
              <input
                id="password"
                type="password"
                name="password"
                class="gps-input pr-10 @error('password') border-red-400 @enderror"
                placeholder="••••••••"
                required
                autocomplete="current-password"
              >
              <button type="button" class="pw-toggle" id="pwToggle" tabindex="-1">
                <i data-lucide="eye" style="width:15px;height:15px" id="pwIcon"></i>
              </button>
            </div>
            @error('password')
            <div class="flex items-center gap-1.5 mt-1.5">
              <i data-lucide="alert-circle" style="width:11px;height:11px;color:#ef4444;flex-shrink:0"></i>
              <span class="text-[11px] text-red-500 font-semibold">{{ $message }}</span>
            </div>
            @enderror
          </div>

          <!-- Remember + Forgot -->
          <div class="flex items-center justify-between mb-6 fade-up" style="animation-delay:.5s">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" name="remember" id="remember_me" class="gps-checkbox">
              <span class="text-[12px] font-semibold text-slate-500">Ingat saya</span>
            </label>
            @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-[12px] font-bold text-blue-600 hover:text-blue-700 transition-colors no-underline">
              Lupa password?
            </a>
            @endif
          </div>

          <!-- Submit -->
          <div class="fade-up" style="animation-delay:.55s">
            <button type="submit" class="btn-login" id="loginBtn">
              <span id="btnText" class="flex items-center justify-center gap-2">
                <i data-lucide="log-in" style="width:16px;height:16px"></i>
                Masuk ke Dashboard
              </span>
              <span id="btnLoading" class="hidden flex items-center justify-center gap-2">
                <div class="spinner"></div>
                Memverifikasi...
              </span>
            </button>
          </div>

        </form>

        <!-- Footer divider -->
        <div class="mt-6 pt-5 border-t border-slate-100 fade-up" style="animation-delay:.6s">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5">
              <i data-lucide="shield-check" style="width:12px;height:12px;color:#22c55e"></i>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Koneksi Aman · SSL/TLS</span>
            </div>

          </div>
          <!-- Clock -->
          <div class="flex items-center justify-center gap-2 mt-3">
            <i data-lucide="clock" style="width:11px;height:11px;color:#94a3b8"></i>
            <span class="text-[11px] font-bold text-slate-400" id="loginClock">--:--:--</span>
            <span class="text-[11px] text-slate-300">·</span>
            <span class="text-[11px] font-bold text-slate-400">MotoTrack v2.1.0</span>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ══════ SCRIPTS ══════ -->
  <script>
    // ── Init Lucide ──
    if (window.lucide) lucide.createIcons();

    // ── Clock ──
    function updateClock() {
      const now = new Date();
      const t = [now.getHours(), now.getMinutes(), now.getSeconds()]
        .map(n => String(n).padStart(2,'0')).join(':');
      const el = document.getElementById('loginClock');
      if (el) el.textContent = t;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ── Password toggle ──
    const pwToggle = document.getElementById('pwToggle');
    const pwInput  = document.getElementById('password');
    const pwIcon   = document.getElementById('pwIcon');
    if (pwToggle) {
      pwToggle.addEventListener('click', () => {
        const isText = pwInput.type === 'text';
        pwInput.type = isText ? 'password' : 'text';
        pwIcon.setAttribute('data-lucide', isText ? 'eye' : 'eye-off');
        lucide.createIcons();
      });
    }

    // ── Form loading state ──
    const form    = document.getElementById('loginForm');
    const btnText = document.getElementById('btnText');
    const btnLoad = document.getElementById('btnLoading');
    if (form) {
      form.addEventListener('submit', (e) => {
        if (form.checkValidity()) {
          btnText.classList.add('hidden');
          btnLoad.classList.remove('hidden');
          btnLoad.classList.add('flex');
        }
      });
    }

    // ── Canvas background: particles + GPS grid ──
    const canvas = document.getElementById('bgCanvas');
    const ctx    = canvas.getContext('2d');

    function resize() {
      canvas.width  = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    // Particles
    const particles = Array.from({ length: 60 }, () => ({
      x: Math.random() * window.innerWidth,
      y: Math.random() * window.innerHeight,
      r: Math.random() * 1.5 + 0.3,
      vx: (Math.random() - 0.5) * 0.3,
      vy: (Math.random() - 0.5) * 0.3,
      alpha: Math.random() * 0.4 + 0.1,
      color: Math.random() > 0.5 ? '59,130,246' : '99,102,241'
    }));

    // GPS nodes
    const nodes = Array.from({ length: 8 }, () => ({
      x: Math.random() * window.innerWidth,
      y: Math.random() * window.innerHeight,
      pulse: Math.random() * Math.PI * 2
    }));

    let frame = 0;

    function drawBg() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Dark gradient base
      const grad = ctx.createRadialGradient(
        canvas.width * .5, canvas.height * .4, 0,
        canvas.width * .5, canvas.height * .4, canvas.width * .8
      );
      grad.addColorStop(0,   'rgba(30,58,138,.95)');
      grad.addColorStop(.5,  'rgba(15,23,42,.97)');
      grad.addColorStop(1,   'rgba(2,6,23,1)');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // Draw connections between nearby nodes
      for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
          const dx = nodes[i].x - nodes[j].x;
          const dy = nodes[i].y - nodes[j].y;
          const dist = Math.sqrt(dx*dx + dy*dy);
          if (dist < 280) {
            ctx.beginPath();
            ctx.moveTo(nodes[i].x, nodes[i].y);
            ctx.lineTo(nodes[j].x, nodes[j].y);
            ctx.strokeStyle = `rgba(59,130,246,${.06 * (1 - dist/280)})`;
            ctx.lineWidth = 1;
            ctx.stroke();
          }
        }
      }

      // Draw GPS nodes
      nodes.forEach((n, i) => {
        n.pulse += 0.025;
        const pScale = (Math.sin(n.pulse) + 1) / 2;

        // Pulse ring
        ctx.beginPath();
        ctx.arc(n.x, n.y, 4 + pScale * 20, 0, Math.PI * 2);
        ctx.strokeStyle = `rgba(59,130,246,${.15 * (1 - pScale)})`;
        ctx.lineWidth = 1;
        ctx.stroke();

        // Core dot
        ctx.beginPath();
        ctx.arc(n.x, n.y, 2.5, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(96,165,250,${.4 + pScale * .4})`;
        ctx.fill();
      });

      // Draw particles
      particles.forEach(p => {
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0) p.x = canvas.width;
        if (p.x > canvas.width) p.x = 0;
        if (p.y < 0) p.y = canvas.height;
        if (p.y > canvas.height) p.y = 0;

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${p.color},${p.alpha})`;
        ctx.fill();
      });

      frame++;
      requestAnimationFrame(drawBg);
    }
    drawBg();
  </script>

</body>
</html>