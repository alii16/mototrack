<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- SEO & Description -->
  <meta name="description" content="Reset password akun MotoTrack Anda melalui email.">
  <meta name="keywords" content="MotoTrack, lupa password, reset password, GPS tracker">
  <meta name="author" content="MotoTrack">
  <meta name="robots" content="noindex, nofollow">

  <!-- Open Graph -->
  <meta property="og:title" content="MotoTrack — Lupa Password">
  <meta property="og:description" content="Reset password akun MotoTrack Anda melalui email.">
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

  <title>MotoTrack — Lupa Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <link rel="shortcut icon" href="{{ asset('logo.svg') }}" type="image/x-icon">
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] } } }
    }
  </script>
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    @keyframes fade-up {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fade-in {
      from { opacity: 0; }
      to   { opacity: 1; }
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
    @keyframes scanline {
      0%   { top: -10%; }
      100% { top: 110%; }
    }
    @keyframes glow-pulse {
      0%, 100% { box-shadow: 0 0 20px rgba(37,99,235,.3); }
      50%       { box-shadow: 0 0 40px rgba(37,99,235,.6), 0 0 60px rgba(37,99,235,.2); }
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .fade-up    { animation: fade-up .6s cubic-bezier(.22,1,.36,1) both; }
    .fade-in    { animation: fade-in .5s ease both; }
    .float-mid  { animation: float-mid 4s ease-in-out infinite; }
    .spin-slow  { animation: spin-slow 20s linear infinite; }
    .spin-rev   { animation: spin-rev 15s linear infinite; }
    .blink      { animation: blink 1s step-start infinite; }
    .glow-pulse { animation: glow-pulse 2.5s ease-in-out infinite; }

    #bgCanvas {
      position: fixed; inset: 0; z-index: 0; pointer-events: none;
    }
    .grid-overlay {
      position: fixed; inset: 0; z-index: 1; pointer-events: none;
      background-image:
        linear-gradient(rgba(37,99,235,.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(37,99,235,.05) 1px, transparent 1px);
      background-size: 40px 40px;
    }
    .gps-trail {
      position: fixed; z-index: 1; pointer-events: none;
      font-family: 'Plus Jakarta Sans', monospace;
      font-size: 9px; font-weight: 700;
      color: rgba(37,99,235,.18); white-space: nowrap; letter-spacing: .05em;
    }
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
    .gps-input {
      width: 100%;
      padding: 11px 14px 11px 42px;
      background: rgba(248,250,252,.8);
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 13px; font-weight: 500; color: #1e293b;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .gps-input:focus {
      border-color: #2563eb; background: #fff;
      box-shadow: 0 0 0 4px rgba(37,99,235,.08), 0 2px 8px rgba(37,99,235,.1);
    }
    .gps-input::placeholder { color: #94a3b8; }
    .input-wrapper { position: relative; }
    .input-icon {
      position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
      color: #94a3b8; pointer-events: none; transition: color .2s;
    }
    .input-wrapper:focus-within .input-icon { color: #2563eb; }
    .btn-submit {
      width: 100%; padding: 13px;
      background: linear-gradient(135deg, #1d4ed8, #2563eb, #3b82f6);
      background-size: 200% 200%;
      border: none; border-radius: 12px; color: white;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 14px; font-weight: 800;
      cursor: pointer; position: relative; overflow: hidden;
      transition: transform .15s, box-shadow .15s;
      box-shadow: 0 4px 20px rgba(37,99,235,.35);
    }
    .btn-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 32px rgba(37,99,235,.45);
    }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit::before {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(135deg, transparent 30%, rgba(255,255,255,.15) 50%, transparent 70%);
      transform: translateX(-100%); transition: transform .5s;
    }
    .btn-submit:hover::before { transform: translateX(100%); }
    .status-dot {
      width: 7px; height: 7px; border-radius: 50%; display: inline-block;
    }
    .status-dot.online { background: #22c55e; animation: blink 2s ease-in-out infinite; }
    .status-dot.active { background: #3b82f6; animation: blink 1.5s ease-in-out infinite; }
    .orbit-ring {
      position: absolute; border-radius: 50%;
      border: 1px dashed rgba(37,99,235,.15);
    }
    .ping-dot::before {
      content: ''; position: absolute; inset: -4px; border-radius: 50%;
      background: currentColor; opacity: .4;
      animation: signal-pulse 1.5s ease-out infinite;
    }
    .ping-dot::after {
      content: ''; position: absolute; inset: -8px; border-radius: 50%;
      background: currentColor; opacity: .2;
      animation: signal-pulse 1.5s ease-out infinite .5s;
    }
    .left-panel {
      background: linear-gradient(160deg, #0f172a 0%, #1e3a8a 45%, #1d4ed8 100%);
    }
    .scanline {
      position: absolute; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, transparent, rgba(37,99,235,.4), transparent);
      animation: scanline 4s linear infinite; pointer-events: none;
    }
    .spinner {
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,.3); border-top-color: white;
      border-radius: 50%; animation: spin .6s linear infinite;
    }
    @media (max-width: 768px) {
      .left-panel { display: none !important; }
      .right-panel { border-radius: 0 !important; }
    }
  </style>
</head>

<body class="font-sans bg-slate-900 min-h-screen overflow-y-auto">

  <!-- ANIMATED BACKGROUND -->
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

  <!-- MAIN LAYOUT -->
  <div class="relative z-10 min-h-screen flex items-center justify-center p-4 py-8">

    <div class="w-full max-w-4xl flex rounded-2xl overflow-hidden shadow-2xl" style="min-height: 520px; animation: fade-up .7s cubic-bezier(.22,1,.36,1) both .2s; box-shadow: 0 40px 100px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.05);">

      <!-- LEFT PANEL -->
      <div class="left-panel hidden md:flex flex-col justify-between w-[45%] p-8 relative overflow-hidden flex-shrink-0">
        <div class="scanline"></div>

        <!-- Orbit rings -->
        <div class="orbit-ring" style="width:280px;height:280px;top:50%;left:50%;transform:translate(-50%,-50%)"></div>
        <div class="orbit-ring" style="width:380px;height:380px;top:50%;left:50%;transform:translate(-50%,-50%); opacity:.5"></div>
        <div class="orbit-ring spin-slow" style="width:320px;height:320px;top:50%;left:50%;transform:translate(-50%,-50%); border-color: rgba(59,130,246,.2)"></div>
        <div class="orbit-ring spin-rev" style="width:240px;height:240px;top:50%;left:50%;transform:translate(-50%,-50%); border-color: rgba(99,102,241,.2)"></div>

        <!-- TOP: Logo + Brand -->
        <div class="relative z-10 fade-in" style="animation-delay:.4s">
          <div class="flex items-center gap-3 mb-6">
            <img src="{{ asset('img/logo-aside.png') }}" alt="MotoTrack" class="w-8 h-8 rounded-lg border border-blue-400/30 object-contain" onerror="this.style.display='none'">
            <div>
              <div class="font-black text-[17px] text-white leading-none">MotoTrack</div>
              <div class="text-[9px] text-blue-300/70 font-semibold tracking-widest uppercase mt-0.5">GPS & ESP32 Integration</div>
            </div>
          </div>
          <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-3 py-2">
            <span class="status-dot online"></span>
            <span class="text-[10px] text-green-400 font-bold">Sistem Online</span>
            <span class="text-white/20 text-[10px]">·</span>
            <span class="status-dot active"></span>
            <span class="text-[10px] text-blue-300 font-bold">1 Perangkat Aktif</span>
          </div>
        </div>

        <!-- CENTER: Illustration -->
        <div class="relative z-10 flex items-center justify-center flex-1 py-6">
          <div class="relative flex items-center justify-center">
            <!-- Ping rings -->
            <div style="position:absolute;width:180px;height:180px;border-radius:50%;border:1.5px solid rgba(59,130,246,.3);animation:ping-ring 3s ease-out infinite"></div>
            <div style="position:absolute;width:180px;height:180px;border-radius:50%;border:1.5px solid rgba(59,130,246,.3);animation:ping-ring 3s ease-out infinite .8s"></div>
            <div style="position:absolute;width:180px;height:180px;border-radius:50%;border:1.5px solid rgba(59,130,246,.3);animation:ping-ring 3s ease-out infinite 1.6s"></div>

            <!-- Main circle with lock/email icon (forgot password theme) -->
            <div class="relative w-[130px] h-[130px] rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, rgba(37,99,235,.3), rgba(99,102,241,.2)); border: 1.5px solid rgba(99,162,255,.3); box-shadow: 0 0 40px rgba(37,99,235,.3), inset 0 0 30px rgba(37,99,235,.1);">
              <div class="float-mid flex flex-col items-center gap-1">
                <!-- Email with key icon -->
                <svg width="64" height="56" viewBox="0 0 64 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <!-- Envelope -->
                  <rect x="4" y="14" width="40" height="28" rx="4" stroke="#60a5fa" stroke-width="2.5" fill="rgba(37,99,235,.15)"/>
                  <path d="M4 18 L24 30 L44 18" stroke="#93c5fd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <!-- Key -->
                  <circle cx="52" cy="38" r="7" stroke="#fbbf24" stroke-width="2.2" fill="rgba(251,191,36,.1)"/>
                  <line x1="47" y1="43" x2="38" y2="52" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/>
                  <line x1="40" y1="50" x2="37" y2="53" stroke="#fbbf24" stroke-width="1.8" stroke-linecap="round"/>
                  <line x1="38" y1="52" x2="35" y2="49" stroke="#fbbf24" stroke-width="1.8" stroke-linecap="round"/>
                  <!-- Signal dot -->
                  <circle cx="24" cy="8" r="3.5" fill="#22c55e"/>
                  <circle cx="24" cy="8" r="1.8" fill="white"/>
                </svg>
                <div class="text-[8px] font-bold text-blue-300/80 tracking-wider text-center whitespace-nowrap">
                  <span class="blink text-yellow-400">●</span> RESET LINK
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
          </div>
        </div>

        <!-- BOTTOM: Stats -->
        <div class="relative z-10">
          <div class="rounded-xl overflow-hidden mb-4" style="background:#0f172a; border:1px solid rgba(255,255,255,.08)">
            <div style="height:44px; position:relative; overflow:hidden;">
              <svg width="200%" height="44" style="animation: road-scroll 3s linear infinite" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="44" fill="#1e293b"/>
                <line x1="0" y1="22" x2="3000" y2="22" stroke="#334155" stroke-width="1"/>
                <line x1="0" y1="22" x2="3000" y2="22" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="24 16"/>
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

      <!-- RIGHT PANEL — FORGOT PASSWORD FORM -->
      <div class="right-panel form-card flex-1 flex flex-col justify-center p-8 md:p-10">

        <!-- Header -->
        <div class="mb-8 fade-up" style="animation-delay:.3s">
          <!-- Mobile logo -->
          <div class="flex items-center gap-2.5 mb-6 md:hidden">
            <img src="{{ asset('img/logo-aside.png') }}" alt="MotoTrack" class="w-8 h-8 rounded-lg border border-blue-400/30 object-contain" onerror="this.style.display='none'">
            <div>
              <div class="font-extrabold text-[14px] text-slate-800">MotoTrack</div>
              <div class="text-[8px] text-slate-400 font-semibold tracking-wide">IoT GPS System</div>
            </div>
          </div>

          <div class="inline-flex items-center gap-1.5 bg-yellow-50 border border-yellow-100 px-3 py-1 rounded-full mb-3">
            <i data-lucide="key" style="width:10px;height:10px;color:#d97706"></i>
            <span class="text-[10px] font-bold text-yellow-700 uppercase tracking-widest">Reset Password</span>
          </div>
          <h1 class="font-black text-[26px] text-slate-900 leading-tight mb-1">
            Lupa Password? 🔑
          </h1>
          <p class="text-slate-500 text-[13px] font-medium">
            Tenang, kami akan kirimkan link reset ke email kamu.
          </p>
        </div>

        <!-- Session status -->
        @if(session('status'))
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3 fade-up" style="animation-delay:.35s">
          <i data-lucide="check-circle" style="width:14px;height:14px;color:#16a34a;flex-shrink:0"></i>
          <span class="text-[12px] font-semibold text-green-700">{{ session('status') }}</span>
        </div>
        @endif

        {{-- Info box: cek spam --}}
        <div class="flex gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-5 fade-up" style="animation-delay:.37s">
            <i data-lucide="info" style="width:15px;height:15px;color:#d97706;flex-shrink:0;margin-top:1px"></i>
            <div>
                <p class="text-[12px] font-bold text-amber-700 mb-0.5">Tips: Email tidak muncul?</p>
                <p class="text-[11px] text-amber-600 font-medium leading-relaxed">
                Cek folder <span class="font-black">Spam</span> atau <span class="font-black">Sampah</span> di email kamu.
                Link reset biasanya tiba dalam 1–2 menit. Jika masih belum ada, coba kirim ulang.
                </p>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('password.email') }}" id="forgotForm" novalidate>
          @csrf

          <!-- Email -->
          <div class="mb-6 fade-up" style="animation-delay:.4s">
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
            <p class="text-[11px] text-slate-400 font-medium mt-2">
              Masukkan email yang terdaftar. Kami akan mengirim link reset password.
            </p>
          </div>

          <!-- Submit -->
          <div class="fade-up" style="animation-delay:.5s">
            <button type="submit" class="btn-submit" id="submitBtn">
              <span id="btnText" class="flex items-center justify-center gap-2">
                <i data-lucide="send" style="width:16px;height:16px"></i>
                Kirim Link Reset
              </span>
              <span id="btnLoading" class="hidden flex items-center justify-center gap-2">
                <div class="spinner"></div>
                Mengirim...
              </span>
            </button>
          </div>

        </form>

        <!-- Footer -->
        <div class="mt-6 pt-5 border-t border-slate-100 fade-up" style="animation-delay:.55s">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5">
              <i data-lucide="shield-check" style="width:12px;height:12px;color:#22c55e"></i>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Koneksi Aman · SSL/TLS</span>
            </div>
            @if(Route::has('login'))
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 text-[11px] font-bold text-blue-600 hover:text-blue-700 transition-colors">
              <i data-lucide="arrow-left" style="width:11px;height:11px"></i>
              Kembali ke Login
            </a>
            @endif
          </div>
          <div class="flex items-center justify-center gap-2 mt-3">
            <i data-lucide="clock" style="width:11px;height:11px;color:#94a3b8"></i>
            <span class="text-[11px] font-bold text-slate-400" id="clock">--:--:--</span>
            <span class="text-[11px] text-slate-300">·</span>
            <span class="text-[11px] font-bold text-slate-400">MotoTrack v2.1.0</span>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    if (window.lucide) lucide.createIcons();

    function updateClock() {
      const now = new Date();
      const t = [now.getHours(), now.getMinutes(), now.getSeconds()]
        .map(n => String(n).padStart(2,'0')).join(':');
      const el = document.getElementById('clock');
      if (el) el.textContent = t;
    }
    updateClock();
    setInterval(updateClock, 1000);

    const form    = document.getElementById('forgotForm');
    const btnText = document.getElementById('btnText');
    const btnLoad = document.getElementById('btnLoading');
    if (form) {
      form.addEventListener('submit', () => {
        if (form.checkValidity()) {
          btnText.classList.add('hidden');
          btnLoad.classList.remove('hidden');
          btnLoad.classList.add('flex');
        }
      });
    }

    // Canvas background
    const canvas = document.getElementById('bgCanvas');
    const ctx    = canvas.getContext('2d');
    function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
    resize();
    window.addEventListener('resize', resize);

    const particles = Array.from({ length: 60 }, () => ({
      x: Math.random() * window.innerWidth, y: Math.random() * window.innerHeight,
      r: Math.random() * 1.5 + 0.3, vx: (Math.random() - 0.5) * 0.3, vy: (Math.random() - 0.5) * 0.3,
      alpha: Math.random() * 0.4 + 0.1, color: Math.random() > 0.5 ? '59,130,246' : '99,102,241'
    }));
    const nodes = Array.from({ length: 8 }, () => ({
      x: Math.random() * window.innerWidth, y: Math.random() * window.innerHeight,
      pulse: Math.random() * Math.PI * 2
    }));

    function drawBg() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      const grad = ctx.createRadialGradient(canvas.width*.5, canvas.height*.4, 0, canvas.width*.5, canvas.height*.4, canvas.width*.8);
      grad.addColorStop(0, 'rgba(30,58,138,.95)');
      grad.addColorStop(.5, 'rgba(15,23,42,.97)');
      grad.addColorStop(1, 'rgba(2,6,23,1)');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
          const dx = nodes[i].x - nodes[j].x, dy = nodes[i].y - nodes[j].y;
          const dist = Math.sqrt(dx*dx + dy*dy);
          if (dist < 280) {
            ctx.beginPath(); ctx.moveTo(nodes[i].x, nodes[i].y); ctx.lineTo(nodes[j].x, nodes[j].y);
            ctx.strokeStyle = `rgba(59,130,246,${.06*(1-dist/280)})`; ctx.lineWidth = 1; ctx.stroke();
          }
        }
      }
      nodes.forEach(n => {
        n.pulse += 0.025;
        const pScale = (Math.sin(n.pulse) + 1) / 2;
        ctx.beginPath(); ctx.arc(n.x, n.y, 4 + pScale*20, 0, Math.PI*2);
        ctx.strokeStyle = `rgba(59,130,246,${.15*(1-pScale)})`; ctx.lineWidth = 1; ctx.stroke();
        ctx.beginPath(); ctx.arc(n.x, n.y, 2.5, 0, Math.PI*2);
        ctx.fillStyle = `rgba(96,165,250,${.4+pScale*.4})`; ctx.fill();
      });
      particles.forEach(p => {
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0) p.x = canvas.width; if (p.x > canvas.width) p.x = 0;
        if (p.y < 0) p.y = canvas.height; if (p.y > canvas.height) p.y = 0;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
        ctx.fillStyle = `rgba(${p.color},${p.alpha})`; ctx.fill();
      });
      requestAnimationFrame(drawBg);
    }
    drawBg();
  </script>

</body>
</html>