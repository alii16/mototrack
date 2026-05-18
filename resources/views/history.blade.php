<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- SEO & Description -->
  <meta name="description" content="Lihat riwayat perjalanan kendaraan Anda – rekam jejak rute, jarak tempuh, dan waktu perjalanan secara lengkap.">
  <meta name="keywords" content="MotoTrack, riwayat perjalanan, histori GPS, jejak rute kendaraan">
  <meta name="author" content="MotoTrack">
  <meta name="robots" content="noindex, nofollow">

  <!-- Open Graph -->
  <meta property="og:title" content="MotoTrack – Riwayat Perjalanan">
  <meta property="og:description" content="Akses rekaman lengkap rute dan perjalanan kendaraan Anda.">
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

  <title>MotoTrack – Riwayat Perjalanan</title>

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

  {{-- Shared CSS (layout-root, sidebar, main-area, scrollbar, animations) --}}
  <link rel="stylesheet" href="{{ asset('css/main.css') }}">

  <style>
    /* ── Page-specific animations ── */
    @keyframes fade-up  { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
    @keyframes slide-in { from{opacity:0;transform:translateX(16px)} to{opacity:1;transform:translateX(0)} }
    @keyframes ping-slow{ 0%{transform:scale(1);opacity:.45} 100%{transform:scale(2.6);opacity:0} }
    .fade-up  { animation: fade-up  .45s cubic-bezier(.22,1,.36,1) both }
    .slide-in { animation: slide-in .35s cubic-bezier(.22,1,.36,1) both }
    .ping-slow{ animation: ping-slow 2s ease-out infinite }

    /* ── Scrollbar ── */
    .scrollbar-thin::-webkit-scrollbar { width: 4px }
    .scrollbar-thin::-webkit-scrollbar-thumb { background:#dde3ed; border-radius:8px }

    /* ── History row ── */
    .row-hover { transition: background .15s }
    .row-hover:hover   { background: #f8faff }
    .row-hover.active  { background: linear-gradient(90deg,#eff6ff,#f8faff) }

    /* ── Leaflet ── */
    #mainMap {
      position: absolute;
      inset: 0;
      z-index: 0;
    }
    .leaflet-popup-content-wrapper {
      border-radius: 14px !important;
      font-family: 'Plus Jakarta Sans', sans-serif !important;
      font-size: 12px !important;
      box-shadow: 0 8px 32px rgba(37,99,235,.18) !important;
    }
    .leaflet-popup-content { font-family: 'Plus Jakarta Sans', sans-serif !important }

    /* ── History-specific content scroll ── */
    .hist-content-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 14px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    @media (min-width: 640px)  { .hist-content-scroll { padding: 18px; gap: 16px } }
    @media (min-width: 1024px) { .hist-content-scroll { padding: 20px; gap: 16px } }

    /* ── Main row: stack on mobile, side by side on lg ── */
    .main-row {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    @media (min-width: 1024px) {
      .main-row {
        flex-direction: row;
        align-items: flex-start;
        gap: 16px;
      }
    }

    /* ── Left panel width ── */
    .left-panel {
      width: 100%;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    @media (min-width: 1024px) {
      .left-panel { width: 400px; gap: 14px }
    }
    @media (min-width: 1280px) {
      .left-panel { width: 430px }
    }

    /* ── Map body height responsive ── */
    .map-body-hist {
      position: relative;
      min-height: 240px;
    }
    @media (min-width: 640px)  { .map-body-hist { min-height: 280px } }
    @media (min-width: 1024px) { .map-body-hist { min-height: 360px } }

    /* ── SVG map mock ── */
    #mapSvg { width: 100%; display: block }
  </style>
</head>

<body class="font-sans text-slate-900 text-sm">

  <!-- Sidebar overlay (mobile) -->
  <div id="sidebar-overlay" onclick="closeSidebar()"></div>

  <div class="layout-root">

    <!-- ══════════════════════════════════════════
         SIDEBAR — same as geofence.blade.php
    ══════════════════════════════════════════ -->
    <aside id="sidebar">

      <!-- Logo -->
      <div class="flex items-center gap-2.5 px-5 py-[22px] border-b border-slate-200 flex-shrink-0">
        <img src="{{ asset('img/logo-footer.png') }}" alt="Logo" class="h-[70px]">
        <button onclick="closeSidebar()" class="ml-auto text-slate-400 hover:text-slate-600 md:hidden p-1 -mr-1" aria-label="Tutup sidebar">
          <i data-lucide="x" style="width:18px;height:18px"></i>
        </button>
      </div>

      <!-- Nav -->
      <nav class="p-3 flex-1 overflow-y-auto">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 mb-1.5 mt-2.5">Menu</div>
        <a href="{{ route('dashboard') }}" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500 font-medium text-[13.5px] cursor-pointer hover:bg-blue-50 hover:text-blue-600 transition-all mt-0.5 no-underline">
          <i data-lucide="layout-dashboard" style="width:15px;height:15px"></i> Dashboard
        </a>
        <a href="{{ route('geofence') }}" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500 font-medium text-[13.5px] cursor-pointer hover:bg-blue-50 hover:text-blue-600 transition-all mt-0.5 no-underline">
          <i data-lucide="circle-gauge" style="width:15px;height:15px"></i> Geofencing
        </a>
        <a href="#" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-bold text-[13.5px] cursor-pointer no-underline mt-0.5">
          <i data-lucide="clock" style="width:15px;height:15px"></i> Riwayat
        </a>
      </nav>

      <!-- User + Logout -->
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

    </aside><!-- /sidebar -->


    <!-- ══════════════════════════════════════════
         MAIN AREA
    ══════════════════════════════════════════ -->
    <div class="main-area">

      <!-- HEADER -->
      <header class="bg-white border-b border-slate-200 px-4 md:px-6 h-[56px] md:h-[60px] flex items-center justify-between flex-shrink-0 gap-2">

        <div class="flex items-center gap-2 md:gap-3 min-w-0">
          <!-- Hamburger (mobile) -->
          <button id="menu-toggle" onclick="openSidebar()" aria-label="Buka menu"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors flex-shrink-0">
            <i data-lucide="menu" style="width:20px;height:20px"></i>
          </button>
          <div class="min-w-0">
            <div class="text-base md:text-lg font-extrabold tracking-tight truncate">Riwayat Perjalanan</div>
            <div class="text-[10px] md:text-[11px] text-slate-400 font-medium hidden sm:block">Histori perjalanan kendaraan</div>
          </div>
        </div>

        <!-- Clock -->
        <div
          class="flex flex-col items-end bg-blue-50 border border-blue-200 px-2.5 md:px-3.5 py-1 md:py-1.5 rounded-lg">
          <div
            class="header-clock-time text-[13px] md:text-[15px] font-extrabold text-blue-600 leading-tight tracking-tight"
            id="clock">00:00:00</div>
          <div class="header-clock-date text-[8px] md:text-[8.5px] font-semibold text-blue-500" id="date">-</div>
        </div>

      </header><!-- /header -->

      <!-- ══════════ SCROLLABLE CONTENT ══════════ -->
      <div class="hist-content-scroll scrollbar-thin">

        <!-- STAT CARDS -->
        <div class="stat-grid fade-up">

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3 md:py-4 shadow-sm flex items-start justify-between border border-slate-100">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Total Perjalanan</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-indigo-800 mt-1 leading-none" id="statTotal">—</div>
              <div class="text-[11px] mt-1.5 font-medium text-indigo-400 truncate hidden sm:block" id="statTotalSub"> memuat...</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-indigo-50 flex items-center justify-center text-blue-500 flex-shrink-0">
              <i data-lucide="route" style="width:16px;height:16px"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3 md:py-4 shadow-sm flex items-start justify-between border border-slate-100">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Total Jarak Tempuh</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-emerald-500 mt-1 leading-none" id="statJarak">—</div>
              <div class="text-[11px] mt-1.5 font-medium text-emerald-400 truncate hidden sm:block">km keseluruhan</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-emerald-50 flex items-center justify-center text-emerald-500 flex-shrink-0">
              <i data-lucide="map-pin" style="width:16px;height:16px"></i>
            </div>
          </div>

          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3 md:py-4 shadow-sm flex items-start justify-between border border-slate-100">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Total Durasi</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-yellow-500 mt-1 leading-none" id="statDurasi">—</div>
              <div class="text-[11px] mt-1.5 font-medium text-yellow-400 truncate hidden sm:block">jam perjalanan</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-yellow-50 flex items-center justify-center text-violet-500 flex-shrink-0">
              <i data-lucide="timer" style="width:16px;height:16px"></i>
            </div>
          </div>


          <div class="bg-white rounded-2xl px-4 md:px-[18px] py-3 md:py-4 shadow-sm flex items-start justify-between border border-slate-100">
            <div class="min-w-0 pr-2">
              <div class="text-xs text-slate-500 font-semibold">Total Testing</div>
              <div class="text-[22px] md:text-[26px] font-extrabold text-blue-500 mt-1 leading-none" id="statTesting">—</div>
                <div class="text-[11px] mt-1.5 font-medium text-blue-400 truncate hidden sm:block">jam testing</div>
            </div>
            <div class="w-8 h-8 md:w-9 md:h-9 rounded-[9px] bg-blue-50 flex items-center justify-center text-violet-500 flex-shrink-0">
              <i data-lucide="timer" style="width:16px;height:16px"></i>
            </div>
          </div>

        </div><!-- /stat cards -->

        <!-- MAIN ROW: list + map -->
        <div class="main-row">

          <!-- ── LEFT: CHART + LIST ── -->
          <div class="left-panel">

            <!-- CHART CARD -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 fade-up" style="animation-delay:.04s">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <div class="font-bold text-[13px] text-slate-800">Jarak per Hari</div>
                  <div class="text-[10px] text-slate-400 font-medium">7 hari terakhir</div>
                </div>
                <div class="text-[11px] font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">Minggu ini</div>
              </div>
              <canvas id="tripChart" height="100"></canvas>
            </div>

            <!-- LIST CARD -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden fade-up" style="animation-delay:.08s">
              <div class="flex items-center justify-between px-4 md:px-5 py-3.5 md:py-4 border-b border-slate-100">
                <div class="font-bold text-[13px] md:text-[14px] text-slate-800">Daftar Riwayat</div>
                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-100 px-2.5 py-1 rounded-full" id="tripCount">— perjalanan</span>
              </div>
              <!-- Search -->
              <div class="px-4 md:px-5 py-2.5 border-b border-slate-100">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-50 transition-all">
                  <i data-lucide="search" style="width:13px;height:13px" class="text-slate-400 flex-shrink-0"></i>
                  <input id="searchInput" type="text" placeholder="Cari tanggal atau rute..." oninput="filterList()"
                    class="flex-1 bg-transparent text-[12px] md:text-[13px] font-medium text-slate-700 placeholder-slate-300 outline-none min-w-0">
                </div>
              </div>
              <div id="riwayatList" class="divide-y divide-slate-100 overflow-y-auto scrollbar-thin" style="max-height:340px">
                <div class="px-5 py-10 text-center flex flex-col items-center gap-2">
                  <i data-lucide="loader" style="width:28px;height:28px" class="text-slate-300 animate-spin"></i>
                  <div class="text-[13px] font-bold text-slate-500">Memuat riwayat...</div>
                </div>
              </div>
            </div><!-- /list card -->

          </div><!-- /left -->

          <!-- ── RIGHT: MAP ── -->
          <div class="flex-1 min-w-0 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col fade-up" style="animation-delay:.12s" id="mapWrap">

            <!-- Map header -->
            <div class="flex items-center justify-between px-4 md:px-5 py-3 md:py-3.5 border-b border-slate-100 flex-shrink-0 gap-2">
              <div class="flex items-center gap-2 min-w-0 flex-1">
                <div class="w-2 h-2 rounded-full bg-blue-500 live-dot flex-shrink-0"></div>
                <div class="font-bold text-[12px] md:text-[13px] text-slate-800 truncate" id="mapTitle">Peta Riwayat</div>
                <div id="mapBadge" class="hidden items-center gap-1 bg-blue-50 text-blue-600 border border-blue-100 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-widest flex-shrink-0">
                  <i data-lucide="navigation" style="width:9px;height:9px"></i> Rute
                </div>
              </div>
              <div class="flex items-center gap-1.5 md:gap-2 flex-shrink-0">
                <span class="bg-slate-100 px-2 md:px-2.5 py-1 rounded-md text-[9px] md:text-[10px] font-semibold text-slate-500 hidden sm:block truncate max-w-[160px]" id="mapMeta">Pilih riwayat untuk melihat rute</span>
                <button id="btnClose" onclick="closeDetail()"
                  class="hidden items-center gap-1 md:gap-1.5 px-2 md:px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[10px] md:text-[11px] font-semibold text-slate-500 hover:bg-red-50 hover:text-red-500 hover:border-red-200 transition-all cursor-pointer">
                  <i data-lucide="x" style="width:12px;height:12px"></i>
                  <span class="hidden sm:inline">Tutup</span>
                </button>
              </div>
            </div>

            <!-- Map body -->
            <div class="map-body-hist">
              <!-- Placeholder -->
              <div id="mapPlaceholder" class="absolute inset-0 flex flex-col items-center justify-center gap-3 md:gap-4 z-10"
                style="background:linear-gradient(135deg,#f8fafc 0%,#f0f4ff 100%)">
                <div class="relative">
                  <div class="w-14 h-14 md:w-20 md:h-20 bg-blue-50 border-2 border-blue-100 rounded-3xl flex items-center justify-center text-blue-300">
                    <i data-lucide="map" style="width:28px;height:28px"></i>
                  </div>
                  <div class="absolute -bottom-1 -right-1 w-5 h-5 md:w-7 md:h-7 bg-blue-600 rounded-lg md:rounded-xl flex items-center justify-center shadow-sm">
                    <i data-lucide="route" style="width:10px;height:10px" class="text-white"></i>
                  </div>
                </div>
                <div class="text-center px-4">
                  <div class="font-extrabold text-[12px] md:text-[14px] text-slate-700">Belum ada riwayat dipilih</div>
                  <div class="text-[10px] md:text-[12px] text-slate-400 mt-1.5 max-w-[200px] leading-relaxed mx-auto">
                    Klik <span class="font-bold text-slate-600">Lihat</span> pada daftar untuk melihat rute perjalanan
                  </div>
                </div>
              </div>

              <!-- Leaflet map -->
              <div id="mainMap"></div>
            </div>

            <!-- Map footer -->
            <div class="px-4 md:px-5 py-2 md:py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between flex-shrink-0 gap-2 flex-wrap">
              <div class="flex items-center gap-3 text-[9px] md:text-[10px] font-semibold text-slate-400">
                <span class="flex items-center gap-1">
                  <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>GPS Aktif
                </span>
                <span id="fDate">—</span>
                <span id="fTime" class="hidden sm:inline">Pilih riwayat untuk melihat detail</span>
              </div>
              <div class="flex items-center gap-2 md:gap-3 text-[9px] md:text-[10px] font-semibold text-slate-500">
                <span id="fJarak">Jarak: —</span>
                <span id="fDurasi">Durasi: —</span>
                <span id="fSpeed" class="hidden sm:inline">Sat avg: —</span>
              </div>
            </div>

          </div><!-- /map card -->

        </div><!-- /main-row -->

      </div><!-- /hist-content-scroll -->

      <!-- FOOTER -->
      <footer class="bg-white border-t border-slate-200 px-4 md:px-6 py-2 flex justify-between items-center text-[10px] md:text-[11px] text-slate-400 flex-shrink-0 gap-2">
        <span class="truncate">© 2026 MotoTrack · IoT GPS System</span>
        <span>v2.1.0</span>
      </footer>

    </div><!-- /main-area -->

  </div><!-- /layout-root -->


  <!-- ══════════════════════════════════════════
       SCRIPTS
  ══════════════════════════════════════════ -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
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
          <div id="moto-confirm-title">Hapus Riwayat</div>
          <div id="moto-confirm-msg">Tindakan ini tidak dapat dibatalkan.</div>
        </div>
      </div>
      <div id="moto-confirm-footer">
        <button id="moto-confirm-cancel" onclick="motoConfirmResolve(false)">Batal</button>
        <button id="moto-confirm-ok"     onclick="motoConfirmResolve(true)">Ya, Hapus</button>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/history.js') }}"></script>

</body>
</html>