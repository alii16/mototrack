<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MotoTrack — 503 Layanan Tidak Tersedia</title>
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
    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(-2deg); }
      50%       { transform: translateY(-14px) rotate(2deg); }
    }
    @keyframes ping-slow {
      0%   { transform: scale(1);   opacity: .6; }
      100% { transform: scale(2.2); opacity: 0;  }
    }
    @keyframes fade-up {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes spin-slow {
      from { transform: rotate(0deg); }
      to   { transform: rotate(360deg); }
    }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0; }
    }
    @keyframes signal-pulse {
      0%   { r: 4; opacity: 1; }
      100% { r: 12; opacity: 0; }
    }
    @keyframes progress-bar {
      0%   { width: 0%; }
      100% { width: 100%; }
    }
    @keyframes maintenance-bounce {
      0%, 100% { transform: translateY(0); }
      50%       { transform: translateY(-8px); }
    }

    .float-icon        { animation: float 3.5s ease-in-out infinite; }
    .spin-slow         { animation: spin-slow 8s linear infinite; }
    .fade-up           { animation: fade-up .55s cubic-bezier(.22,1,.36,1) both; }
    .maint-bounce      { animation: maintenance-bounce 2s ease-in-out infinite; }
    .progress-animate  { animation: progress-bar 8s linear infinite; }

    .ping-ring {
      position: absolute; inset: 0; border-radius: 50%;
      border: 2px solid #7c3aed;
      animation: ping-slow 2s ease-out infinite;
    }
    .ping-ring:nth-child(2) { animation-delay: .7s; }
    .ping-ring:nth-child(3) { animation-delay: 1.4s; }

    .gps-blink { animation: blink .9s step-start infinite; }
    .signal-ripple { animation: signal-pulse 1.8s ease-out infinite; }
    .signal-ripple:nth-child(2) { animation-delay: .6s; }
    .signal-ripple:nth-child(3) { animation-delay: 1.2s; }

    .stat-chip { transition: transform .15s, box-shadow .15s; }
    .stat-chip:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(124,58,237,.12); }

    .btn-primary { transition: transform .15s, box-shadow .15s, background .15s; }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(124,58,237,.28); }
    .btn-secondary { transition: transform .15s, background .15s; }
    .btn-secondary:hover { transform: translateY(-1px); }

    .grid-bg {
      background-image:
        linear-gradient(rgba(124,58,237,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(124,58,237,.04) 1px, transparent 1px);
      background-size: 32px 32px;
    }
    .terminal-line { opacity: 0; }
    .terminal-line.show { animation: fade-up .3s ease both; }

    @media (max-width: 480px) {
      .error-code { font-size: 80px !important; }
    }
  </style>
</head>

<body class="font-sans bg-slate-50 text-slate-900 min-h-screen overflow-x-hidden">

  <div class="fixed inset-0 grid-bg pointer-events-none"></div>
  <div class="fixed top-0 right-0 w-[500px] h-[500px] bg-violet-100/40 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
  <div class="fixed bottom-0 left-0 w-[400px] h-[400px] bg-purple-100/30 rounded-full blur-3xl translate-y-1/2 -translate-x-1/3 pointer-events-none"></div>

  <!-- HEADER -->
  <header class="relative z-10 bg-white/80 backdrop-blur-md border-b border-slate-200 px-5 md:px-8 h-[56px] flex items-center justify-between">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 no-underline">
      <img src="{{ asset('img/logo-aside.png') }}" alt="MotoTrack" class="w-8 h-8 rounded-lg object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
      {{-- <div class="w-[34px] h-[34px] bg-blue-600 rounded-[9px] flex items-center justify-center text-white flex-shrink-0">
        <i data-lucide="bike" style="width:18px;height:18px;stroke-width:2.5"></i>
      </div> --}}
      <div>
        <div class="font-extrabold text-[14px] text-slate-800 leading-tight">MotoTrack</div>
        <div class="text-[9px] text-slate-400 font-semibold tracking-wide">IoT GPS System</div>
      </div>
    </a>
    <div class="flex items-center gap-2">
      <div class="hidden sm:flex items-center gap-1.5 bg-violet-50 border border-violet-200 px-2.5 py-1 rounded-lg">
        <div class="w-1.5 h-1.5 rounded-full bg-violet-500 gps-blink"></div>
        <span class="text-[10px] font-bold text-violet-600">ERROR 503</span>
      </div>
      <div class="flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-lg">
        <i data-lucide="clock" style="width:12px;height:12px" class="text-slate-500"></i>
        <span class="text-[11px] font-bold text-slate-600" id="headerClock">--:--:--</span>
      </div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="relative z-10 flex flex-col items-center justify-center min-h-[calc(100vh-56px-48px)] px-5 py-10 md:py-16">
    <div class="w-full max-w-2xl mx-auto">

      <!-- TOP BADGE -->
      <div class="flex justify-center mb-6 fade-up" style="animation-delay:.05s">
        <div class="inline-flex items-center gap-2 bg-violet-50 border border-violet-200 px-4 py-1.5 rounded-full">
          <i data-lucide="construction" style="width:13px;height:13px" class="text-violet-600"></i>
          <span class="text-[11px] font-bold text-violet-600 uppercase tracking-widest">Sedang Maintenance</span>
        </div>
      </div>

      <!-- ILLUSTRATION -->
      <div class="flex justify-center mb-6 fade-up" style="animation-delay:.1s">
        <div class="relative w-[220px] h-[220px] md:w-[260px] md:h-[260px] flex items-center justify-center">
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="relative w-[140px] h-[140px] md:w-[160px] md:h-[160px]">
              <div class="ping-ring"></div>
              <div class="ping-ring"></div>
              <div class="ping-ring"></div>
            </div>
          </div>
          <div class="absolute inset-0 flex items-center justify-center spin-slow">
            <svg width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="100" cy="100" r="88" stroke="#ddd6fe" stroke-width="1.5" stroke-dasharray="6 6"/>
            </svg>
          </div>
          <div class="relative w-[110px] h-[110px] md:w-[126px] md:h-[126px] bg-white rounded-full border-2 border-slate-200 shadow-xl flex items-center justify-center">
            <div class="float-icon maint-bounce">
              <svg width="64" height="58" viewBox="0 0 64 58" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Cone/warning -->
                <polygon points="32,4 52,50 12,50" fill="#ede9fe" stroke="#7c3aed" stroke-width="2" stroke-linejoin="round"/>
                <!-- Stripes on cone -->
                <line x1="20" y1="34" x2="44" y2="34" stroke="#7c3aed" stroke-width="3" stroke-linecap="round"/>
                <line x1="24" y1="44" x2="40" y2="44" stroke="#7c3aed" stroke-width="3" stroke-linecap="round"/>
                <!-- Exclamation -->
                <rect x="29.5" y="16" width="5" height="13" rx="2.5" fill="#7c3aed"/>
                <circle cx="32" cy="36" r="3" fill="#7c3aed"/>
                <!-- Wrench -->
                <path d="M50 8 C54 4 60 6 58 12 L48 22 L44 18 Z" fill="#a78bfa" stroke="#7c3aed" stroke-width="1.5" stroke-linejoin="round"/>
                <rect x="40" y="20" width="7" height="20" rx="2" transform="rotate(45 40 20)" fill="#a78bfa" stroke="#7c3aed" stroke-width="1.5"/>
              </svg>
            </div>
          </div>
          <div class="absolute top-3 right-8 w-2.5 h-2.5 bg-violet-400 rounded-full signal-ripple opacity-70"></div>
          <div class="absolute top-3 right-8 w-2.5 h-2.5 bg-violet-400 rounded-full signal-ripple"></div>
          <div class="absolute bottom-6 left-6 w-2 h-2 bg-purple-400 rounded-full signal-ripple opacity-60" style="animation-delay:.9s"></div>
          <div class="absolute top-10 left-4 w-1.5 h-1.5 bg-slate-300 rounded-full"></div>
          <div class="absolute bottom-10 right-4 w-1.5 h-1.5 bg-slate-300 rounded-full"></div>
        </div>
      </div>

      <!-- ERROR CODE + TITLE -->
      <div class="text-center mb-5">
        <div class="error-code font-black text-[110px] md:text-[130px] leading-none text-transparent bg-clip-text bg-gradient-to-b from-violet-600 to-violet-300 fade-up select-none" style="animation-delay:.15s">
          503
        </div>
        <div class="font-extrabold text-xl md:text-2xl text-slate-800 -mt-2 mb-2 fade-up" style="animation-delay:.2s">
          Layanan Tidak Tersedia
        </div>
        <p class="text-slate-500 text-[13px] md:text-[14px] font-medium max-w-sm mx-auto leading-relaxed fade-up" style="animation-delay:.25s">
          Sistem GPS MotoTrack sedang dalam proses pemeliharaan terjadwal. Kami akan segera kembali online.
        </p>
      </div>

      <!-- PROGRESS BAR -->
      <div class="mx-auto max-w-lg mb-5 fade-up" style="animation-delay:.28s">
        <div class="flex justify-between items-center mb-1.5">
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Progress Maintenance</span>
          <span class="text-[10px] font-bold text-violet-600">Estimasi ~30 menit</span>
        </div>
        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-violet-500 to-purple-400 rounded-full progress-animate"></div>
        </div>
      </div>

      <!-- TERMINAL CARD -->
      <div class="bg-slate-900 rounded-2xl px-5 py-4 mb-5 mx-auto max-w-lg fade-up" style="animation-delay:.3s">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
          <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
          <div class="w-2.5 h-2.5 rounded-full bg-emerald-400"></div>
          <span class="ml-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">mototrack-gps ~ terminal</span>
        </div>
        <div class="font-mono text-[11px] md:text-[12px] flex flex-col gap-1.5" id="terminalLines">
          <div class="terminal-line text-slate-400"><span class="text-emerald-400 font-bold">$</span> gps --service <span class="text-blue-400">--status</span></div>
          <div class="terminal-line text-yellow-400"><span class="text-slate-500">›</span> Memeriksa ketersediaan layanan...</div>
          <div class="terminal-line text-violet-400"><span class="text-slate-500">›</span> MAINTENANCE: Server sedang diperbarui</div>
          <div class="terminal-line text-violet-400"><span class="text-slate-500">›</span> HTTP 503 — Service Unavailable (dijadwalkan)</div>
          <div class="terminal-line text-slate-400"><span class="text-slate-500">›</span> Modul: <span class="text-white font-bold">GPS Core v2.2.0</span> sedang di-deploy</div>
          <div class="terminal-line text-emerald-400"><span class="text-slate-500">›</span> Perkiraan selesai: <span class="font-bold text-white">~30 menit</span></div>
        </div>
      </div>

      <!-- STAT CHIPS -->
      <div class="flex flex-wrap justify-center gap-2.5 mb-7 fade-up" style="animation-delay:.35s">
        <div class="stat-chip flex items-center gap-2 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-sm cursor-default">
          <div class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center">
            <i data-lucide="construction" style="width:12px;height:12px" class="text-violet-400"></i>
          </div>
          <div>
            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Status</div>
            <div class="text-[11px] font-extrabold text-violet-500">Maintenance</div>
          </div>
        </div>
        <div class="stat-chip flex items-center gap-2 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-sm cursor-default">
          <div class="w-6 h-6 rounded-lg bg-amber-50 flex items-center justify-center">
            <i data-lucide="triangle-alert" style="width:12px;height:12px" class="text-amber-400"></i>
          </div>
          <div>
            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Kode Error</div>
            <div class="text-[11px] font-extrabold text-amber-500">HTTP 503</div>
          </div>
        </div>
        <div class="stat-chip flex items-center gap-2 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-sm cursor-default">
          <div class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center">
            <i data-lucide="timer" style="width:12px;height:12px" class="text-violet-400"></i>
          </div>
          <div>
            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Estimasi</div>
            <div class="text-[11px] font-extrabold text-violet-500">~30 Menit</div>
          </div>
        </div>
        <div class="stat-chip flex items-center gap-2 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-sm cursor-default">
          <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center">
            <i data-lucide="refresh-cw" style="width:12px;height:12px" class="text-slate-400"></i>
          </div>
          <div>
            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">Tipe</div>
            <div class="text-[11px] font-extrabold text-slate-600">Terjadwal</div>
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="flex flex-col sm:flex-row gap-3 justify-center fade-up" style="animation-delay:.42s">
        <button onclick="location.reload()"
          class="btn-primary flex items-center justify-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white text-[13px] font-bold rounded-xl shadow shadow-violet-200 cursor-pointer">
          <i data-lucide="refresh-cw" style="width:15px;height:15px"></i>
          Coba Lagi
        </button>
        <button onclick="history.back()"
          class="btn-secondary flex items-center justify-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 text-[13px] font-bold rounded-xl hover:bg-slate-50 cursor-pointer">
          <i data-lucide="arrow-left" style="width:15px;height:15px"></i>
          Halaman Sebelumnya
        </button>
      </div>

    </div>
  </main>

  <footer class="relative z-10 bg-white border-t border-slate-200 px-5 md:px-8 py-3 flex justify-between items-center text-[11px] text-slate-400 flex-shrink-0">
    <span>© 2026 MotoTrack · IoT GPS System</span>
    <span>v2.1.0</span>
  </footer>

  <script>
    if (window.lucide) lucide.createIcons();
    function updateClock() {
      const now = new Date();
      document.getElementById('headerClock').textContent =
        [now.getHours(), now.getMinutes(), now.getSeconds()].map(n => String(n).padStart(2,'0')).join(':');
    }
    updateClock(); setInterval(updateClock, 1000);
    document.querySelectorAll('.terminal-line').forEach((line, i) => {
      setTimeout(() => line.classList.add('show'), 300 + i * 350);
    });
  </script>
</body>
</html>