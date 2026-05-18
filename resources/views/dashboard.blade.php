<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- SEO & Description -->
  <meta name="description" content="Dashboard utama MotoTrack – pantau posisi kendaraan secara real-time, status mesin, dan notifikasi keamanan.">
  <meta name="keywords" content="MotoTrack, tracking motor, GPS tracker, pantau kendaraan, dashboard">
  <meta name="author" content="MotoTrack">
  <meta name="robots" content="noindex, nofollow">

  <!-- Open Graph -->
  <meta property="og:title" content="MotoTrack Dashboard">
  <meta property="og:description" content="Pantau posisi dan status kendaraan Anda secara real-time.">
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

  <title>MotoTrack Dashboard</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="shortcut icon" href="{{asset ('logo.svg')}}" type="image/x-icon">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="{{ asset('css/main.css') }}">
  <style>
    /* ── Row: Peta + Engine Control (sejajar, tinggi sama di desktop) ── */
    .dash-map-row {
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    @media (min-width: 768px) {
      .dash-map-row          { flex-direction: row; align-items: stretch; }
      .dash-map-card         { flex: 1; min-width: 0; min-height: 320px; }
      .dash-engine-card      { width: 240px; flex-shrink: 0; }
    }
    @media (min-width: 1024px) {
      .dash-map-card         { min-height: 360px; }
      .dash-engine-card      { width: 290px; }
    }
    /* map-body fill sisa tinggi card */
    .dash-map-card .map-body { flex: 1; min-height: 220px; }

    /* ── History list rows (gaya history.blade.php) ── */
    .hist-row {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 16px;
      transition: background .15s; cursor: pointer;
    }
    .hist-row:hover { background: #f8faff; }
    .hist-row-icon {
      width: 34px; height: 34px; border-radius: 10px;
      background: #eff6ff; color: #2563eb;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; transition: background .15s, color .15s;
    }
    .hist-row:hover .hist-row-icon { background: #2563eb; color: #fff; }
    .hist-row-body  { flex: 1; min-width: 0; }
    .hist-row-title {
      font-size: 12px; font-weight: 700; color: #1e293b;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .hist-row-sub   { font-size: 11px; color: #94a3b8; margin-top: 1px; }
    .hist-row-badge {
      font-size: 10px; font-weight: 700;
      padding: 2px 8px; border-radius: 99px;
      background: #f1f5f9; color: #64748b;
      white-space: nowrap; flex-shrink: 0;
    }
    #hist-search { font-family: 'Plus Jakarta Sans', sans-serif; }

    /* Riwayat dashboard: non-interactive, no hover effect */
    #history-list .hist-row { cursor: default !important; }
    #history-list .hist-row:hover { background: transparent !important; }
    #history-list .hist-row:hover .hist-row-icon { background: #eff6ff !important; color: #2563eb !important; }
  </style>
</head>

<body class="font-sans text-slate-900 text-sm">

  <!-- ══════════════════ OVERLAY (mobile) ══════════════════ -->
  <div id="sidebar-overlay" onclick="closeSidebar()"></div>

  <div class="layout-root">

    <!-- ══════════════════ SIDEBAR ══════════════════ -->
    <aside id="sidebar">

      <div class="flex items-center gap-2.5 px-5 py-[22px] border-b border-slate-200 flex-shrink-0">
        <img src="{{ asset('img/logo-footer.png') }}" alt="Logo" class="h-[70px]">
        <!-- Close btn on mobile only -->
        <button onclick="closeSidebar()" class="ml-auto text-slate-400 hover:text-slate-600 md:hidden p-1 -mr-1"
          aria-label="Tutup sidebar">
          <i data-lucide="x" style="width:18px;height:18px"></i>
        </button>
      </div>

      <nav class="p-3 flex-1 overflow-y-auto">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 mb-1.5 mt-2.5">Menu</div>
        <a href="#" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-bold text-[13.5px] cursor-pointer no-underline">
          <i data-lucide="layout-dashboard" style="width:15px;height:15px"></i> Dashboard
        </a>
        <a href="{{ route('geofence') }}" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500 font-medium text-[13.5px] cursor-pointer hover:bg-blue-50 hover:text-blue-600 transition-all mt-0.5 no-underline">
          <i data-lucide="goal" style="width:15px;height:15px"></i> Geofencing
        </a>
        <a href="{{ route('history') }}" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500 font-medium text-[13.5px] cursor-pointer hover:bg-blue-50 hover:text-blue-600 transition-all mt-0.5 no-underline">
          <i data-lucide="clock" style="width:15px;height:15px"></i> Riwayat
        </a>
      </nav>

      <div class="border-t border-slate-200 p-3 flex-shrink-0">
        <a href="{{ route('profile.edit') }}" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-2.5 py-1.5 mb-2.5 rounded-xl hover:bg-blue-50 transition-colors no-underline group">
          <div class="w-[34px] h-[34px] rounded-full bg-blue-600 border-2 border-blue-300 flex items-center justify-center text-[11px] font-extrabold text-white flex-shrink-0">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-[12.5px] font-bold truncate text-slate-800 group-hover:text-blue-600 transition-colors">{{ auth()->user()->name }}</div>
            <div class="text-[10.5px] text-slate-500 font-medium">admin · aktif</div>
          </div>
          <i data-lucide="chevron-right" style="width:13px;height:13px" class="text-slate-300 group-hover:text-blue-400 transition-colors flex-shrink-0"></i>
        </a>
        <form id="logout-form" method="POST" action="{{ route('logout') }}">@csrf</form>
        <button onclick="document.getElementById('logout-form').submit()"
          class="w-full py-2 px-3 bg-red-50 text-red-500 border border-red-200 rounded-lg font-bold text-[13px] flex items-center justify-center gap-1.5 hover:bg-red-100 transition-colors cursor-pointer">
          <i data-lucide="log-out" style="width:14px;height:14px"></i> Keluar
        </button>
      </div>
    </aside>

    <!-- ══════════════════ MAIN ══════════════════ -->
    <div class="main-area">

      <!-- HEADER -->
      <header
        class="bg-white border-b border-slate-200 px-4 md:px-6 h-[56px] md:h-[60px] flex items-center justify-between flex-shrink-0 gap-2">

        <div class="flex items-center gap-2 md:gap-3 min-w-0">
          <!-- Hamburger toggle (mobile only) -->
          <button id="menu-toggle" onclick="openSidebar()" aria-label="Buka menu"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors flex-shrink-0">
            <i data-lucide="menu" style="width:20px;height:20px"></i>
          </button>
          <div class="text-base md:text-lg font-extrabold tracking-tight truncate">Dashboard</div>
        </div>

        <div class="flex items-center gap-2 md:gap-3 flex-shrink-0">
          <span id="esp-badge"
            class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wide px-2 md:px-3 py-1 rounded-full bg-red-100 text-red-600 flex-shrink-0">
            <i data-lucide="wifi-off" style="width:11px;height:11px"></i>
            <span id="esp-badge-text" class="hidden sm:inline">ESP OFFLINE</span>
          </span>
          <div
            class="flex flex-col items-end bg-blue-50 border border-blue-200 px-2.5 md:px-3.5 py-1 md:py-1.5 rounded-lg">
            <div
              class="header-clock-time text-[13px] md:text-[15px] font-extrabold text-blue-600 leading-tight tracking-tight"
              id="clock">00:00:00</div>
            <div class="header-clock-date text-[8px] md:text-[8.5px] font-semibold text-blue-500" id="date">-</div>
          </div>
        </div>
      </header>

      <!-- CONTENT -->
      <div class="content-scroll scrollbar-thin">

        <!-- BANNER -->
        <div
          class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl px-4 md:px-6 py-4 md:py-[18px] flex items-center gap-3 md:gap-4 text-white flex-shrink-0 banner-deco">
          <div
            class="w-10 h-10 md:w-11 md:h-11 rounded-full bg-white/20 border-2 border-white/40 flex items-center justify-center text-sm font-extrabold flex-shrink-0 z-10">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
          </div>
          <div class="z-10 min-w-0">
            <div class="banner-name font-extrabold tracking-tight truncate">Halo, {{ auth()->user()->name }} 👋</div>
            <div class="text-[11px] md:text-xs opacity-80 mt-0.5 font-medium">Administrator · MotoTrack IoT GPS System
            </div>
          </div>
        </div>

        <!-- STAT CARDS -->
        <div class="stat-grid">

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Riwayat Perjalanan</div>
              <div class="text-[22px] md:text-[26px] font-extrabold mt-1 leading-none" id="stat-history">– <span
                  class="text-[12px] md:text-[13px] font-medium text-slate-400">trip</span></div>
              <div class="text-[11px] mt-1.5 font-medium text-blue-500 truncate hidden sm:block" id="stat-history-sub">Memuat...</div>
            </div>
            <div
              class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-blue-50 flex items-center justify-center text-blue-500 flex-shrink-0">
              <i data-lucide="folder-open" style="width:16px;height:16px"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Geofencing Aktif</div>
              <div class="text-[22px] md:text-[26px] font-extrabold mt-1 leading-none" id="stat-geo">– <span
                  class="text-[12px] md:text-[13px] font-medium text-slate-400">zona</span></div>
              <div class="text-[11px] mt-1.5 font-medium text-blue-500 truncate hidden sm:block" id="stat-geo-sub">Memuat...</div>
            </div>
            <div
              class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-blue-50 flex items-center justify-center text-blue-500 flex-shrink-0">
              <i data-lucide="goal" style="width:16px;height:16px"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Status GPS</div>
              <div class="text-[18px] md:text-[20px] font-extrabold mt-1 leading-none" id="stat-gps-status">–</div>
              <div class="text-[11px] mt-1.5 font-medium text-blue-500 truncate hidden sm:block" id="stat-gps-sub">Memuat...</div>
            </div>
            <div
              class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-green-50 flex items-center justify-center text-green-500 flex-shrink-0">
              <i data-lucide="satellite-dish" style="width:16px;height:16px"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Jumlah Satelit</div>
              <div class="text-[22px] md:text-[26px] font-extrabold mt-1 leading-none" id="stat-sat">– <span
                  class="text-[12px] md:text-[13px] font-medium text-slate-400">sat</span></div>
              <div class="text-[11px] mt-1.5 font-medium text-blue-500 truncate hidden sm:block" id="stat-sat-sub">Sinyal –%</div>
            </div>
            <div
              class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-indigo-50 flex items-center justify-center text-indigo-500 flex-shrink-0">
              <i data-lucide="satellite" style="width:16px;height:16px"></i>
            </div>
          </div>

        </div>

        <!-- ══ ROW: PETA + ENGINE CONTROL ══ -->
        <div class="dash-map-row">

          <!-- PETA -->
          <div class="dash-map-card map-container">
            <div class="flex justify-between items-center px-4 py-3 border-b border-slate-200 flex-shrink-0 gap-2">
              <div class="flex items-center gap-2 font-bold text-sm">
                <div class="w-2 h-2 rounded-full bg-blue-500 live-dot flex-shrink-0"></div>
                Peta Lokasi
              </div>
              <span
                class="bg-slate-100 px-2 md:px-2.5 py-1 rounded-md text-[10px] md:text-[11px] font-semibold text-slate-500 truncate max-w-[160px] md:max-w-none"
                id="coord-display">Menunggu GPS...</span>
            </div>
            <div class="map-body z-10">
              <div id="map"></div>
            </div>
          </div>

          <!-- ENGINE CONTROL + STATUS MESIN (1 card) -->
          <div class="dash-engine-card bg-white rounded-2xl shadow-sm flex flex-col">

            <!-- Card header -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 flex-shrink-0 gap-2">
              <div class="flex items-center gap-2 font-bold text-sm">
                <i data-lucide="cog" style="width:14px;height:14px" class="text-blue-500"></i>
                Kontrol Mesin
              </div>
              <div id="relay-badge"
                class="flex items-center gap-1.5 bg-green-100 text-green-600 px-2.5 py-1 rounded-full text-[11px] font-bold flex-shrink-0">
                <div class="w-1.5 h-1.5 rounded-full bg-green-500 live-dot"></div>
                <span id="relay-badge-text">Running</span>
              </div>
            </div>

            <!-- Card body: grows to fill height -->
            <div class="flex flex-col flex-1 p-4 gap-2">

              <!-- Status Mesin -->
              <div class="bg-slate-50 border border-slate-200 rounded-xl px-2 py-3">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                  <i data-lucide="activity" style="width:11px;height:11px"></i> Status Mesin
                </div>
                <div class="flex items-center justify-between gap-2">
                  <span class="font-bold text-[11px]" id="engine-status">–</span>
                  <div class="bg-slate-100 rounded-lg py-1.5 px-3 text-center">
                    <div class="text-[9px] text-slate-400 font-semibold">Relay 1</div>
                    <div class="text-[12px] font-bold text-blue-600 mt-0.5" id="relay-r1">Inactive</div>
                  </div>
                </div>
              </div>

              <!-- Relay status big text -->
              <div class="text-center">
                <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-widest mb-1">Status Relay</div>
                <div id="relay-status" class="font-extrabold leading-none text-red-500" style="font-size:clamp(28px,5vw,40px)">OFF</div>
              </div>

              <!-- Relay button — pushed to bottom on desktop via mt-auto -->
              <div class="mt-auto flex flex-col gap-2">
                <button id="relay-btn" onclick="toggleRelay()"
                  class="w-full py-3 bg-red-500 hover:bg-red-600 hover:-translate-y-px active:translate-y-0 text-white font-extrabold text-sm rounded-xl transition-all cursor-pointer">
                  Nyalakan Relay
                </button>
                <div class="text-[11px] text-slate-400 text-center leading-snug">Mesin dikontrol dari jarak jauh via relay</div>
              </div>

            </div>
          </div>

        </div><!-- /dash-map-row -->

        <!-- ══ CARDS BAWAH ══ -->
        <div class="bottom-cards">

          <!-- RIWAYAT SESI -->
          <div class="flex-1 min-w-0 bg-white rounded-2xl shadow-sm overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-4 md:px-5 py-3.5 md:py-4 border-b border-slate-100">
              <div class="font-bold text-[13px] text-slate-800 flex items-center gap-1.5">
                <i data-lucide="history" style="width:14px;height:14px" class="text-blue-500"></i>
                Riwayat Terbaru
              </div>
              <a href="{{ route('history') }}"
                class="text-[11px] font-semibold text-blue-500 hover:text-blue-700 flex items-center gap-1 no-underline transition-colors whitespace-nowrap">
                Lihat semua <i data-lucide="arrow-right" style="width:11px;height:11px"></i>
              </a>
            </div>
            <!-- Search -->
            <div class="px-4 md:px-5 py-2.5 border-b border-slate-100">
              <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-50 transition-all">
                <i data-lucide="search" style="width:13px;height:13px" class="text-slate-400 flex-shrink-0"></i>
                <input id="hist-search" type="text" placeholder="Cari tanggal atau rute..."
                  class="flex-1 bg-transparent text-[12px] font-medium text-slate-700 placeholder-slate-300 outline-none min-w-0">
              </div>
            </div>
            <!-- List -->
            <div id="history-list" class="divide-y divide-slate-100 overflow-y-auto scrollbar-thin" style="max-height:320px">
              <div class="px-5 py-8 text-center flex flex-col items-center gap-2">
                <i data-lucide="loader" style="width:24px;height:24px" class="text-slate-300 animate-spin"></i>
                <div class="text-[12px] font-bold text-slate-400">Memuat riwayat...</div>
              </div>
            </div>
          </div>

          <!-- KANAN -->
          <div class="right-panel">

            <!-- GEOFENCE AKTIF -->
            <div class="bg-white rounded-2xl px-4 py-3.5 shadow-sm">
              <div class="text-[12px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <i data-lucide="goal" style="width:13px;height:13px"></i> Geofence Aktif
              </div>
              <div id="geo-list" class="text-slate-400 text-xs">Memuat...</div>
            </div>

          </div>
        </div>

      </div><!-- end content-scroll -->

      <!-- FOOTER -->
      <footer
        class="bg-white border-t border-slate-200 px-4 md:px-6 py-2 flex justify-between items-center text-[11px] text-slate-400 flex-shrink-0 gap-2">
        <span class="truncate">© 2026 MotoTrack · IoT GPS System</span>
        <span id="conn-bar" class="whitespace-nowrap">Menghubungkan...</span>
      </footer>

    </div><!-- end main-area -->

  </div><!-- end layout-root -->

  <div id="moto-toast-wrap"></div>

  <!-- ══════════════════ SCRIPTS ══════════════════ -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="{{ asset('js/dashboard.js') }}"></script>
</body>

</html>