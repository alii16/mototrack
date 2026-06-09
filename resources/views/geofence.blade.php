<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- SEO & Description -->
  <meta name="description" content="Kelola zona geofencing kendaraan Anda – atur batas area dan terima notifikasi saat kendaraan keluar atau masuk zona.">
  <meta name="keywords" content="MotoTrack, geofencing, zona aman, tracking motor, batas wilayah GPS">
  <meta name="author" content="MotoTrack">
  <meta name="robots" content="noindex, nofollow">

  <!-- Open Graph -->
  <meta property="og:title" content="MotoTrack – Geofencing">
  <meta property="og:description" content="Atur zona geofencing dan pantau pergerakan kendaraan di luar batas area yang ditentukan.">
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

  <title>MotoTrack - Geofencing</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
  <link rel="shortcut icon" href="{{asset ('logo.svg')}}" type="image/x-icon">
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans','sans-serif'] } } }
    }
  </script>
  {{-- Shared CSS: sidebar, layout-root, main-area, scrollbar, animations --}}
  <link rel="stylesheet" href="{{ asset('css/main.css') }}">
  <style>
    /* ── Geofence-specific styles ── */
    @keyframes fade-up  { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
    @keyframes slide-in { from{opacity:0;transform:translateX(16px)} to{opacity:1;transform:translateX(0)} }
    .fade-up  { animation: fade-up  .45s cubic-bezier(.22,1,.36,1) both }
    .slide-in { animation: slide-in .35s cubic-bezier(.22,1,.36,1) both }

    .zone-row { transition:background .15s }
    .zone-row:hover  { background:#f8faff }
    .zone-row.active { background:linear-gradient(90deg,#eff6ff 0%,#f8faff 100%) }

    /* Leaflet map fill */
    #mainMap { position:absolute; inset:0; z-index:0 }

    /* Leaflet popup */
    .leaflet-popup-content-wrapper {
      border-radius:14px!important;
      font-family:'Plus Jakarta Sans',sans-serif!important;
      font-size:12px!important;
      box-shadow:0 8px 32px rgba(37,99,235,.18)!important;
    }
    .leaflet-popup-content { font-family:'Plus Jakarta Sans',sans-serif!important }

    /* Modal map picker */
    #modalMapPicker {
      width:100%; height:200px;
      border-radius:14px; overflow:hidden;
      border:1.5px solid #e2e8f0; margin-bottom:12px;
    }

    /* Scrollable content area */
    .geo-content-scroll {
      flex:1; overflow-y:auto;
      padding:14px;
      display:flex; flex-direction:column; gap:14px;
    }
    @media (min-width:640px)  { .geo-content-scroll { padding:18px; gap:16px } }
    @media (min-width:1024px) { .geo-content-scroll { padding:20px; gap:16px } }
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
        <!-- Close btn (mobile only) -->
        <button onclick="closeSidebar()" class="ml-auto text-slate-400 hover:text-slate-600 md:hidden p-1 -mr-1" aria-label="Tutup sidebar">
          <i data-lucide="x" style="width:18px;height:18px"></i>
        </button>
      </div>

      <nav class="p-3 flex-1 overflow-y-auto">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 mb-1.5 mt-2.5">Menu</div>
        <a href="{{ route('dashboard') }}" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500 font-medium text-[13.5px] cursor-pointer hover:bg-blue-50 hover:text-blue-600 transition-all mt-0.5 no-underline">
          <i data-lucide="layout-dashboard" style="width:15px;height:15px"></i> Dashboard
        </a>
        <a href="#" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-bold text-[13.5px] cursor-pointer no-underline mt-0.5">
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

    <!-- ══════════════════ MAIN AREA ══════════════════ -->
    <div class="main-area">

      <!-- HEADER -->
      <header class="bg-white border-b border-slate-200 px-4 md:px-6 h-[56px] md:h-[60px] flex items-center justify-between flex-shrink-0 gap-2">

        <div class="flex items-center gap-2 md:gap-3 min-w-0">
          <!-- Hamburger (mobile only) -->
          <button id="menu-toggle" onclick="openSidebar()" aria-label="Buka menu"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors flex-shrink-0">
            <i data-lucide="menu" style="width:20px;height:20px"></i>
          </button>
          <div class="min-w-0">
            <div class="text-base md:text-lg font-extrabold tracking-tight truncate">Geofencing</div>
            <div class="text-[10px] md:text-[11px] text-slate-400 font-medium hidden sm:block">Kelola zona keamanan kendaraan</div>
          </div>
        </div>

        <div
          class="flex flex-col items-end bg-blue-50 border border-blue-200 px-2.5 md:px-3.5 py-1 md:py-1.5 rounded-lg">
          <div
            class="header-clock-time text-[13px] md:text-[15px] font-extrabold text-blue-600 leading-tight tracking-tight"
            id="clock">00:00:00</div>
          <div class="header-clock-date text-[8px] md:text-[8.5px] font-semibold text-blue-500" id="date">-</div>
        </div>

      </header>

      <!-- HINT BAR -->
      <div id="hintBar" class="hidden mx-3 md:mx-5 mt-3 md:mt-4">
        <div class="flex items-center gap-3 bg-blue-600 text-white rounded-xl px-4 py-2.5 text-[11px] md:text-[12px] font-semibold shadow shadow-blue-200">
          <div class="w-2 h-2 rounded-full bg-white live-dot flex-shrink-0"></div>
          <span class="flex-1 truncate">Mode pilih titik — klik pada peta di modal untuk menentukan lokasi zona</span>
          <button onclick="document.getElementById('hintBar').classList.replace('block','hidden')"
            class="flex-shrink-0 text-[11px] bg-white/20 hover:bg-white/30 px-2.5 py-1 rounded-lg transition-colors cursor-pointer font-bold">Tutup</button>
        </div>
      </div>

      <!-- ══════════════════ SCROLLABLE CONTENT ══════════════════ -->
      <div class="geo-content-scroll scrollbar-thin">

        <!-- STAT CARDS -->
        <div class="stat-grid fade-up">

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Total Zona</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-indigo-800 mt-1 leading-none" id="statTotal">0
                <span class="text-[12px] md:text-[13px] font-medium text-slate-400">zona</span>
              </div>
              <div class="text-[11px] mt-1.5 font-medium text-indigo-400 truncate hidden sm:block">zona terdaftar</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-indigo-50 flex items-center justify-center text-indigo-500 flex-shrink-0">
              <i data-lucide="layers" style="width:16px;height:16px"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Aktif</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-emerald-500 mt-1 leading-none" id="statActive">0
                <span class="text-[12px] md:text-[13px] font-medium text-slate-400">zona</span>
              </div>
              <div class="text-[11px] mt-1.5 font-medium text-emerald-400 truncate hidden sm:block">zona aktif</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-emerald-50 flex items-center justify-center text-emerald-500 flex-shrink-0">
              <i data-lucide="check-circle-2" style="width:16px;height:16px"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Nonaktif</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-yellow-500 mt-1 leading-none" id="statInactive">0
                <span class="text-[12px] md:text-[13px] font-medium text-yellow-300">zona</span>
              </div>
              <div class="text-[11px] mt-1.5 font-medium text-yellow-400 truncate hidden sm:block">zona nonaktif</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-yellow-50 flex items-center justify-center text-slate-400 flex-shrink-0">
              <i data-lucide="circle-slash-2" style="width:16px;height:16px"></i>
            </div>
          </div>

          {{-- 4th card: placeholder or total radius summary --}}
          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3.5 md:py-4 shadow-sm flex items-start justify-between">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Total Radius</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-blue-500 mt-1 leading-none" id="statTotalRadius">0
                <span class="text-[12px] md:text-[13px] font-medium text-slate-400">m</span>
              </div>
              <div class="text-[11px] mt-1.5 font-medium text-blue-400 truncate hidden sm:block">kumulatif radius</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-blue-50 flex items-center justify-center text-blue-500 flex-shrink-0">
              <i data-lucide="circle-dot" style="width:16px;height:16px"></i>
            </div>
          </div>

        </div>

        <!-- MAIN ROW -->
        <div class="flex flex-col lg:flex-row gap-3 md:gap-4 items-start">

          <!-- LEFT: ZONE LIST -->
          <div class="w-full lg:w-[420px] xl:w-[440px] flex-shrink-0 flex flex-col gap-3 md:gap-4">

            <!-- PIE CHART CARD -->
            <div class="bg-white rounded-2xl shadow-sm p-4 fade-up" style="animation-delay:.04s">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <div class="font-bold text-[13px] text-slate-800">Distribusi Radius Zona</div>
                  <div class="text-[10px] text-slate-400 font-medium">semua zona terdaftar</div>
                </div>
                <div class="text-[11px] font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">meter</div>
              </div>
              <div class="flex items-center gap-5">
                <div class="relative flex-shrink-0">
                  <canvas id="radiusPieChart" width="90" height="90" role="img" aria-label="Pie chart distribusi radius zona geofencing"></canvas>
                  <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <div class="text-[13px] font-extrabold text-slate-800 leading-none" id="pieTotalRadius">—</div>
                    <div class="text-[8px] font-bold text-slate-400 mt-0.5">total (m)</div>
                  </div>
                </div>
                <!-- Legend: show 3 first, scrollable if more -->
                <div class="flex-1 overflow-y-auto scrollbar-thin" style="max-height:135px">
                  <div class="flex flex-col gap-1.5" id="pieLegend">
                    <div class="text-[10px] text-slate-300 italic">Memuat legend...</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ZONE LIST CARD -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden fade-up" style="animation-delay:.06s">
              <div class="flex items-center justify-between px-4 md:px-5 py-3.5 md:py-4 border-b border-slate-200">
                <div class="font-bold text-[13px] md:text-[14px] text-slate-800">Daftar Zona</div>
                <button onclick="openAddModal()"
                  class="flex items-center gap-1.5 px-3 md:px-3.5 py-1.5 md:py-2 bg-blue-600 hover:bg-blue-700 text-white text-[11px] md:text-[12px] font-bold rounded-lg shadow shadow-blue-200 transition-colors cursor-pointer">
                  <i data-lucide="plus" style="width:13px;height:13px;stroke-width:3"></i>
                  Tambah Zona
                </button>
              </div>
              <!-- Scrollable: ~5 rows (each ~68px) -->
              <div class="overflow-y-auto scrollbar-thin" style="max-height:340px">
                <div id="zoneList">
                  <div class="px-5 py-10 text-center">
                    <div class="flex justify-center mb-2">
                      <i data-lucide="loader" style="width:28px;height:28px" class="text-slate-300 animate-spin"></i>
                    </div>
                    <div class="font-bold text-slate-500 text-[13px]">Memuat zona...</div>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- /left -->

          <!-- RIGHT: MAP -->
          <div class="w-full lg:flex-1 min-w-0 bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col fade-up" style="animation-delay:.1s" id="mapWrap">

            <!-- Map header -->
            <div class="flex items-center justify-between px-4 md:px-5 py-3 md:py-3.5 border-b border-slate-200 flex-shrink-0 gap-2">
              <div class="flex items-center gap-2 min-w-0 flex-1">
                <div class="w-2 h-2 rounded-full bg-blue-500 live-dot flex-shrink-0"></div>
                <div class="font-bold text-[12px] md:text-[13px] text-slate-800 truncate" id="mapTitle">Peta Geofencing</div>
                {{-- <div id="mapLiveBadge" class="hidden items-center gap-1 bg-emerald-50 text-emerald-600 border border-emerald-200 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-widest flex-shrink-0">
                  <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 live-dot"></div>Live
                </div> --}}
              </div>
              <div class="flex items-center gap-1.5 md:gap-2 flex-shrink-0">
                <span class="bg-slate-100 px-2 md:px-2.5 py-1 rounded-md text-[9px] md:text-[10px] font-semibold text-slate-500 max-w-[100px] md:max-w-[200px] truncate" id="mapCoord">Pilih zona untuk melihat peta</span>
                <button id="btnCloseMap" onclick="closeMap()"
                  class="hidden items-center gap-1 md:gap-1.5 px-2 md:px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[10px] md:text-[11px] font-semibold text-slate-500 hover:bg-red-50 hover:text-red-500 hover:border-red-200 transition-all cursor-pointer">
                  <i data-lucide="x" style="width:12px;height:12px"></i>
                  <span class="hidden sm:inline">Tutup</span>
                </button>
              </div>
            </div>

            <!-- Map body -->
            <div id="mapBody" class="relative" style="height:340px">
              <div id="mapPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center gap-3 md:gap-4 z-10"
                style="background:linear-gradient(135deg,#f8fafc 0%,#f0f4ff 100%)">
                <div class="relative">
                  <div class="w-14 h-14 md:w-20 md:h-20 bg-blue-50 border-2 border-blue-100 rounded-3xl flex items-center justify-center text-blue-300">
                    <i data-lucide="map" style="width:28px;height:28px"></i>
                  </div>
                  <div class="absolute -bottom-1 -right-1 w-5 h-5 md:w-7 md:h-7 bg-blue-600 rounded-lg md:rounded-xl flex items-center justify-center shadow-sm">
                    <i data-lucide="eye" style="width:10px;height:10px" class="text-white"></i>
                  </div>
                </div>
                <div class="text-center px-4">
                  <div class="font-extrabold text-[12px] md:text-[14px] text-slate-700">Belum ada zona dipilih</div>
                  <div class="text-[10px] md:text-[12px] text-slate-400 mt-1.5 max-w-[200px] leading-relaxed mx-auto">
                    Klik <span class="font-bold text-slate-600">Lihat</span> pada zona untuk menampilkan peta
                  </div>
                </div>
              </div>
              <div id="mainMap"></div>
            </div>

            <!-- Map footer -->
            <div class="px-4 md:px-5 py-2 md:py-2.5 bg-slate-50 border-t border-slate-200 flex items-center gap-2.5 md:gap-4 flex-shrink-0 flex-wrap">
              <div class="flex items-center gap-1.5 text-[9px] md:text-[10px] font-semibold text-slate-500">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>GPS Aktif
              </div>
              <div class="text-[9px] md:text-[10px] font-semibold text-slate-500 truncate max-w-[140px] sm:max-w-none" id="footerCenter">Pusat: —</div>
              <div class="text-[9px] md:text-[10px] font-semibold text-slate-500" id="footerRadius">Radius: —</div>
              <div class="ml-auto text-[9px] md:text-[10px] font-semibold text-slate-400 truncate max-w-[80px] md:max-w-none" id="footerZoneName"></div>
            </div>

          </div><!-- /map -->

        </div><!-- /main row -->

      </div><!-- /geo-content-scroll -->

      <!-- FOOTER -->
      <footer class="bg-white border-t border-slate-200 px-4 md:px-6 py-2 flex justify-between items-center text-[11px] text-slate-400 flex-shrink-0 gap-2">
        <span class="truncate">© 2026 MotoTrack · IoT GPS System</span>
        <span>v2.1.0</span>
      </footer>

    </div><!-- /main-area -->

  </div><!-- /layout-root -->


  <!-- ══════════════════ MODAL: ADD / EDIT ══════════════════ -->
  <div id="geoModal" class="fixed inset-0 bg-slate-900/25 backdrop-blur-[6px] hidden items-center justify-center z-50 p-3 md:p-4">
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl w-full max-w-[460px] overflow-hidden flex flex-col"
      style="box-shadow:0 24px 64px rgba(15,23,42,.18);max-height:92vh">

      <!-- Modal header -->
      <div class="relative bg-gradient-to-br from-blue-600 to-indigo-700 px-5 md:px-6 pt-5 md:pt-6 pb-4 md:pb-5 text-white flex-shrink-0">
        <div class="font-extrabold text-[14px] md:text-[16px]" id="modalTitle">Tambah Zona Baru</div>
        <div class="text-[10px] md:text-[11px] text-blue-200 mt-0.5 font-medium">Isi detail zona geofencing di bawah ini</div>
        <button onclick="closeModal()"
          class="absolute top-4 right-4 w-8 h-8 bg-white/15 hover:bg-white/25 rounded-xl flex items-center justify-center transition-colors cursor-pointer">
          <i data-lucide="x" style="width:16px;height:16px"></i>
        </button>
      </div>

      <!-- Modal body -->
      <div class="px-4 md:px-6 py-4 md:py-5 flex flex-col gap-3.5 overflow-y-auto scrollbar-thin flex-1">

        <!-- Name -->
        <div>
          <label class="block text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Nama Zona</label>
          <input id="geoName" type="text" placeholder="cth: Rumah, Sekolah, Kantor..."
            class="w-full px-3.5 md:px-4 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-[12px] md:text-[13px] font-medium placeholder-slate-300 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all"/>
        </div>

        <!-- Location tabs -->
        <div>
          <label class="block text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Lokasi Titik Zona</label>
          <div class="flex bg-slate-100 rounded-xl p-1 mb-3">
            <button id="tabManual" onclick="switchTab('manual')"
              class="flex-1 py-1.5 md:py-2 text-[11px] md:text-[12px] font-bold rounded-lg transition-all cursor-pointer bg-white text-blue-600 shadow-sm">Input Manual</button>
            <button id="tabMap" onclick="switchTab('map')"
              class="flex-1 py-1.5 md:py-2 text-[11px] md:text-[12px] font-bold rounded-lg transition-all cursor-pointer text-slate-400 hover:text-slate-600">Pilih di Peta</button>
          </div>
          <!-- Panel Manual -->
          <div id="panelManual" class="grid grid-cols-2 gap-2 md:gap-3">
            <div>
              <label class="block text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Latitude</label>
              <input id="geoLat" type="number" step="0.000001" placeholder="-3.652700"
                class="w-full px-3 md:px-4 py-2 md:py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-[11px] md:text-[13px] font-medium placeholder-slate-300 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all"/>
            </div>
            <div>
              <label class="block text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Longitude</label>
              <input id="geoLng" type="number" step="0.000001" placeholder="128.194700"
                class="w-full px-3 md:px-4 py-2 md:py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-[11px] md:text-[13px] font-medium placeholder-slate-300 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all"/>
            </div>
          </div>
          <!-- Panel Map Picker -->
          <div id="panelMap" class="hidden">
            <div id="modalMapPicker"></div>
            <div id="markerPickBox" class="flex items-center gap-2.5 bg-slate-50 border border-dashed border-slate-300 rounded-xl px-3 md:px-4 py-2.5 text-[11px] md:text-[12px] text-slate-400 font-medium">
              <i data-lucide="map-pin" style="width:16px;height:16px" class="flex-shrink-0 text-slate-300"></i>
              <span id="markerPickText">Klik titik pada peta untuk memilih lokasi</span>
            </div>
          </div>
        </div>

        <!-- Radius -->
        <div>
          <label class="block text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Radius (meter)</label>
          <input id="geoRadius" type="number" min="1" placeholder="cth: 100, 500, 1000"
            class="w-full px-3.5 md:px-4 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-[12px] md:text-[13px] font-medium placeholder-slate-300 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all"/>
        </div>

        <!-- Status -->
        <div>
          <label class="block text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Status</label>
          <select id="geoStatus"
            class="w-full px-3.5 md:px-4 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-[12px] md:text-[13px] font-medium outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all cursor-pointer">
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-[9px] md:text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
            Deskripsi <span class="normal-case font-normal text-slate-300">(opsional)</span>
          </label>
          <textarea id="geoDesc" rows="2" placeholder="Catatan tambahan..."
            class="w-full px-3.5 md:px-4 py-2.5 border border-slate-200 bg-slate-50 rounded-xl text-[12px] md:text-[13px] font-medium placeholder-slate-300 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition-all resize-none"></textarea>
        </div>

      </div><!-- /modal body -->

      <!-- Modal footer -->
      <div class="px-4 md:px-6 py-3 md:py-4 border-t border-slate-200 flex gap-2.5 flex-shrink-0">
        <button onclick="closeModal()"
          class="flex-1 py-2 md:py-2.5 bg-slate-50 border border-slate-200 text-slate-500 font-bold text-[12px] md:text-[13px] rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
        <button onclick="submitGeofence()"
          class="flex-1 py-2 md:py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[12px] md:text-[13px] rounded-xl shadow shadow-blue-200 transition-colors cursor-pointer">Simpan Zona</button>
      </div>

    </div>
  </div>


  <!-- ══ TOAST CONTAINER ══ -->
  <div id="moto-toast-wrap"></div>

  <!-- ══ CONFIRM DIALOG ══ -->
  <div id="moto-confirm-wrap">
    <div id="moto-confirm-box">
      <div id="moto-confirm-header">
        <div id="moto-confirm-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </div>
        <div>
          <div id="moto-confirm-title">Hapus Zona</div>
          <div id="moto-confirm-msg">Tindakan ini tidak dapat dibatalkan.</div>
        </div>
      </div>
      <div id="moto-confirm-footer">
        <button id="moto-confirm-cancel" onclick="motoConfirmResolve(false)">Batal</button>
        <button id="moto-confirm-ok"     onclick="motoConfirmResolve(true)">Ya, Hapus</button>
      </div>
    </div>
  </div>

  <!-- ══════════════════ SCRIPTS ══════════════════ -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="{{ asset('js/geofence.js') }}"></script>
  <script>
    // ── Sidebar toggle — same pattern as dashboard ──
    function openSidebar()  {
      document.getElementById('sidebar').classList.add('open');
      document.getElementById('sidebar-overlay').classList.add('active');
    }
    function closeSidebar() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('sidebar-overlay').classList.remove('active');
    }

    // ── Init Lucide icons ──
    if (window.lucide) lucide.createIcons();

    // ── Toast helper ──
    const ICONS = {
      success: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
      error:   '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
      warning: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      info:    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    };
    const TITLES = { success:'Berhasil', error:'Gagal', warning:'Peringatan', info:'Info' };
    function motoToast(type, msg, duration = 3500) {
      const wrap = document.getElementById('moto-toast-wrap');
      const el = document.createElement('div');
      el.className = `moto-toast ${type}`;
      el.innerHTML = `
        <div class="moto-toast-icon">${ICONS[type]}</div>
        <div class="moto-toast-body">
          <div class="moto-toast-title">${TITLES[type]}</div>
          <div class="moto-toast-msg">${msg}</div>
        </div>
        <div class="moto-toast-close" onclick="this.closest('.moto-toast').remove()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </div>`;
      wrap.appendChild(el);
      setTimeout(() => { el.classList.add('hide'); setTimeout(() => el.remove(), 260); }, duration);
    }

    // ── Confirm dialog helper ──
    let motoConfirmResolve = () => {};
    function motoConfirm(title, msg) {
      document.getElementById('moto-confirm-title').textContent = title;
      document.getElementById('moto-confirm-msg').textContent   = msg;
      document.getElementById('moto-confirm-wrap').classList.add('open');
      return new Promise(resolve => {
        motoConfirmResolve = (val) => {
          document.getElementById('moto-confirm-wrap').classList.remove('open');
          resolve(val);
        };
      });
    }
  </script>

</body>
</html>