<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- SEO & Description -->
  <meta name="description" content="Kelola dan perbarui informasi profil akun MotoTrack Anda.">
  <meta name="keywords" content="MotoTrack, profil pengguna, edit akun, pengaturan akun">
  <meta name="author" content="MotoTrack">
  <meta name="robots" content="noindex, nofollow">

  <!-- Open Graph -->
  <meta property="og:title" content="MotoTrack – Profil">
  <meta property="og:description" content="Perbarui data profil dan pengaturan akun MotoTrack Anda.">
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

  <title>MotoTrack — Profil</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] } } }
    }
  </script>
  <link rel="stylesheet" href="{{ asset('css/main.css') }}">
  <style>
    @keyframes fade-up {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fade-up .45s cubic-bezier(.22,1,.36,1) both; }

    /* Tab active underline */
    .tab-btn { position: relative; transition: color .15s; }
    .tab-btn::after {
      content: '';
      position: absolute;
      bottom: -1px; left: 0; right: 0;
      height: 2px;
      background: #2563eb;
      border-radius: 2px;
      transform: scaleX(0);
      transition: transform .2s cubic-bezier(.22,1,.36,1);
    }
    .tab-btn.active { color: #2563eb !important; font-weight: 800; }
    .tab-btn.active::after { transform: scaleX(1); }

    /* Panel transition */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fade-up .3s ease both; }

    /* Input focus ring */
    .moto-input {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid #e2e8f0;
      background: #f8fafc;
      border-radius: 12px;
      font-size: 13px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 500;
      color: #1e293b;
      outline: none;
      transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .moto-input:focus {
      border-color: #2563eb;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    .moto-input::placeholder { color: #cbd5e1; }
    .moto-input:disabled { opacity: .5; cursor: not-allowed; }

    /* Error text */
    .moto-error { font-size: 11px; color: #ef4444; margin-top: 4px; font-weight: 600; }

    /* Avatar ring pulse on hover */
    .avatar-wrap { transition: transform .2s; }
    .avatar-wrap:hover { transform: scale(1.04); }

    /* Delete modal */
    #deleteModal { display: none; }
    #deleteModal.open { display: flex; }

    /* Profile content scroll */
    .profile-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 14px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    @media (min-width: 640px)  { .profile-scroll { padding: 18px; gap: 16px; } }
    @media (min-width: 1024px) { .profile-scroll { padding: 20px; gap: 16px; } }
  </style>
</head>

<body class="font-sans text-slate-900 text-sm">

  <!-- Sidebar overlay (mobile) -->
  <div id="sidebar-overlay" onclick="closeSidebar()"></div>

  <div class="layout-root">

    <!-- ══════════════════ SIDEBAR ══════════════════ -->
    <aside id="sidebar">
      <div class="flex items-center gap-2.5 px-5 py-[22px] border-b border-slate-200 flex-shrink-0">
        <img src="{{ asset('img/logo-footer.png') }}" alt="Logo" class="h-[70px]">
        <button onclick="closeSidebar()" class="ml-auto text-slate-400 hover:text-slate-600 md:hidden p-1 -mr-1" aria-label="Tutup sidebar">
          <i data-lucide="x" style="width:18px;height:18px"></i>
        </button>
      </div>

      <nav class="p-3 flex-1 overflow-y-auto">
        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 mb-1.5 mt-2.5">Menu</div>
        <a href="{{ route('dashboard') }}" onclick="closeSidebar()"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-500 font-medium text-[13.5px] cursor-pointer hover:bg-blue-50 hover:text-blue-600 transition-all no-underline">
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
        <!-- User area — klik untuk ke profil -->
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
          <button id="menu-toggle" onclick="openSidebar()" aria-label="Buka menu"
            class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors flex-shrink-0">
            <i data-lucide="menu" style="width:20px;height:20px"></i>
          </button>
          <div class="min-w-0">
            <div class="text-base md:text-lg font-extrabold tracking-tight truncate">Profil Saya</div>
            <div class="text-[10px] md:text-[11px] text-slate-400 font-medium hidden sm:block">Kelola informasi akun dan keamanan</div>
          </div>
        </div>
        <div class="flex flex-col items-end bg-blue-50 border border-blue-200 px-2.5 md:px-3.5 py-1 md:py-1.5 rounded-lg flex-shrink-0">
          <div class="text-[13px] md:text-[15px] font-extrabold text-blue-600 leading-tight tracking-tight" id="clock">00:00:00</div>
          <div class="text-[8px] md:text-[8.5px] font-semibold text-blue-500 hidden sm:block" id="date">-</div>
        </div>
      </header>

      <!-- SCROLLABLE CONTENT -->
      <div class="profile-scroll scrollbar-thin">

        <!-- PROFILE BANNER -->
        <div class="relative bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl px-5 md:px-7 py-5 md:py-6 overflow-hidden banner-deco fade-up">
          <div class="relative z-10 flex items-center gap-4">
            <!-- Avatar -->
            <div class="avatar-wrap w-[56px] h-[56px] md:w-[68px] md:h-[68px] rounded-2xl bg-white/20 border-2 border-white/40 flex items-center justify-center text-white font-extrabold text-[20px] md:text-[26px] flex-shrink-0 backdrop-blur-sm">
              {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0">
              <div class="text-white font-extrabold text-[16px] md:text-[19px] leading-tight truncate">{{ auth()->user()->name }}</div>
              <div class="text-blue-200 text-[11px] md:text-[12px] font-medium mt-0.5 truncate">{{ auth()->user()->email }}</div>
              <div class="flex items-center gap-1.5 mt-1.5">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-400 live-dot"></div>
                <span class="text-[10px] font-bold text-emerald-300 uppercase tracking-wide">Akun Aktif</span>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB NAV -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden fade-up" style="animation-delay:.05s">
          <div class="flex border-b border-slate-200 px-1">
            <button onclick="switchTab('info')" id="tab-info"
              class="tab-btn active flex items-center gap-1.5 px-4 py-3.5 text-[12px] text-slate-400 font-semibold cursor-pointer">
              <i data-lucide="user" style="width:13px;height:13px"></i> Informasi
            </button>
            <button onclick="switchTab('password')" id="tab-password"
              class="tab-btn flex items-center gap-1.5 px-4 py-3.5 text-[12px] text-slate-400 font-semibold cursor-pointer">
              <i data-lucide="lock" style="width:13px;height:13px"></i> Password
            </button>
            <button onclick="switchTab('danger')" id="tab-danger"
              class="tab-btn flex items-center gap-1.5 px-4 py-3.5 text-[12px] text-slate-400 font-semibold cursor-pointer">
              <i data-lucide="trash-2" style="width:13px;height:13px"></i> Hapus Akun
            </button>
          </div>

          <!-- ── TAB: INFO ── -->
          <div id="panel-info" class="tab-panel active p-5 md:p-6">
            <div class="mb-4">
              <div class="font-extrabold text-[14px] text-slate-800">Informasi Profil</div>
              <div class="text-[11px] text-slate-400 font-medium mt-0.5">Perbarui nama dan alamat email akun kamu</div>
            </div>

            <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

            <form method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-4">
              @csrf
              @method('patch')

              <!-- Name -->
              <div>
                <label for="name" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Nama</label>
                <input id="name" name="name" type="text"
                  class="moto-input"
                  value="{{ old('name', $user->name) }}"
                  required autofocus autocomplete="name"
                  placeholder="Nama lengkap kamu"/>
                @if ($errors->get('name'))
                  @foreach ($errors->get('name') as $msg)
                    <div class="moto-error">{{ $msg }}</div>
                  @endforeach
                @endif
              </div>

              <!-- Email -->
              <div>
                <label for="email" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Email</label>
                <input id="email" name="email" type="email"
                  class="moto-input"
                  value="{{ old('email', $user->email) }}"
                  required autocomplete="username"
                  placeholder="email@contoh.com"/>
                @if ($errors->get('email'))
                  @foreach ($errors->get('email') as $msg)
                    <div class="moto-error">{{ $msg }}</div>
                  @endforeach
                @endif

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                  <div class="mt-2 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                    <i data-lucide="alert-triangle" style="width:13px;height:13px" class="text-amber-500 flex-shrink-0"></i>
                    <span class="text-[11px] text-amber-700 font-medium flex-1">Email belum diverifikasi.</span>
                    <button form="send-verification" class="text-[11px] font-bold text-blue-600 hover:text-blue-700 underline cursor-pointer">Kirim ulang</button>
                  </div>
                  @if (session('status') === 'verification-link-sent')
                    <div class="mt-2 text-[11px] font-bold text-emerald-600 flex items-center gap-1.5">
                      <i data-lucide="check-circle" style="width:12px;height:12px"></i> Link verifikasi telah dikirim.
                    </div>
                  @endif
                @endif
              </div>

              <!-- Save -->
              <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                  class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-[12px] font-bold rounded-xl shadow shadow-blue-200 transition-colors cursor-pointer">
                  <i data-lucide="save" style="width:13px;height:13px"></i> Simpan Perubahan
                </button>

              </div>
            </form>
          </div>

          <!-- ── TAB: PASSWORD ── -->
          <div id="panel-password" class="tab-panel p-5 md:p-6">
            <div class="mb-4">
              <div class="font-extrabold text-[14px] text-slate-800">Ubah Password</div>
              <div class="text-[11px] text-slate-400 font-medium mt-0.5">Gunakan password panjang dan acak agar akun tetap aman</div>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-4">
              @csrf
              @method('put')

              <!-- Current password -->
              <div>
                <label for="current_password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Password Saat Ini</label>
                <div class="relative">
                  <input id="current_password" name="current_password" type="password"
                    class="moto-input" autocomplete="current-password" placeholder="••••••••"/>
                  <button type="button" onclick="togglePass('current_password', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i data-lucide="eye" style="width:14px;height:14px"></i>
                  </button>
                </div>
                @if ($errors->updatePassword->get('current_password'))
                  @foreach ($errors->updatePassword->get('current_password') as $msg)
                    <div class="moto-error">{{ $msg }}</div>
                  @endforeach
                @endif
              </div>

              <!-- New password -->
              <div>
                <label for="new_password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Password Baru</label>
                <div class="relative">
                  <input id="new_password" name="password" type="password"
                    class="moto-input" autocomplete="new-password" placeholder="Min. 8 karakter"/>
                  <button type="button" onclick="togglePass('new_password', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i data-lucide="eye" style="width:14px;height:14px"></i>
                  </button>
                </div>
                @if ($errors->updatePassword->get('password'))
                  @foreach ($errors->updatePassword->get('password') as $msg)
                    <div class="moto-error">{{ $msg }}</div>
                  @endforeach
                @endif
              </div>

              <!-- Confirm password -->
              <div>
                <label for="password_confirmation" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Konfirmasi Password Baru</label>
                <div class="relative">
                  <input id="password_confirmation" name="password_confirmation" type="password"
                    class="moto-input" autocomplete="new-password" placeholder="Ulangi password baru"/>
                  <button type="button" onclick="togglePass('password_confirmation', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i data-lucide="eye" style="width:14px;height:14px"></i>
                  </button>
                </div>
                @if ($errors->updatePassword->get('password_confirmation'))
                  @foreach ($errors->updatePassword->get('password_confirmation') as $msg)
                    <div class="moto-error">{{ $msg }}</div>
                  @endforeach
                @endif
              </div>

              <!-- Save -->
              <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                  class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-[12px] font-bold rounded-xl shadow shadow-blue-200 transition-colors cursor-pointer">
                  <i data-lucide="lock" style="width:13px;height:13px"></i> Perbarui Password
                </button>

              </div>
            </form>
          </div>

          <!-- ── TAB: DANGER ── -->
          <div id="panel-danger" class="tab-panel p-5 md:p-6">
            <div class="mb-4">
              <div class="font-extrabold text-[14px] text-red-600">Hapus Akun</div>
              <div class="text-[11px] text-slate-400 font-medium mt-0.5">Tindakan ini tidak dapat dibatalkan</div>
            </div>

            <!-- Warning box -->
            <div class="flex gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3.5 mb-5">
              <i data-lucide="triangle-alert" style="width:16px;height:16px" class="text-red-400 flex-shrink-0 mt-0.5"></i>
              <div class="text-[12px] text-red-700 font-medium leading-relaxed">
                Setelah akun dihapus, <strong>semua data dan informasi akan dihapus secara permanen</strong>. Unduh data yang ingin kamu simpan sebelum melanjutkan.
              </div>
            </div>

            <button onclick="document.getElementById('deleteModal').classList.add('open')"
              class="flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white text-[12px] font-bold rounded-xl shadow shadow-red-200 transition-colors cursor-pointer">
              <i data-lucide="trash-2" style="width:13px;height:13px"></i> Hapus Akun Saya
            </button>
          </div>

        </div><!-- /tab card -->

      </div><!-- /profile-scroll -->

      <!-- FOOTER -->
      <footer class="bg-white border-t border-slate-200 px-4 md:px-6 py-2 flex justify-between items-center text-[11px] text-slate-400 flex-shrink-0 gap-2">
        <span class="truncate">© 2026 MotoTrack · IoT GPS System</span>
        <span>v2.1.0</span>
      </footer>

    </div><!-- /main-area -->
  </div><!-- /layout-root -->


  <!-- ══════════════════ DELETE MODAL ══════════════════ -->
  <div id="deleteModal"
    class="fixed inset-0 bg-slate-900/25 backdrop-blur-[6px] items-center justify-center z-50 p-3 md:p-4">
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl w-full max-w-[420px] overflow-hidden"
      style="box-shadow:0 24px 64px rgba(15,23,42,.18)">

      <!-- Modal header -->
      <div class="relative bg-gradient-to-br from-red-500 to-red-700 px-5 md:px-6 pt-5 pb-4 text-white">
        <div class="font-extrabold text-[15px]">Konfirmasi Hapus Akun</div>
        <div class="text-[11px] text-red-200 mt-0.5 font-medium">Masukkan password untuk melanjutkan</div>
        <button onclick="document.getElementById('deleteModal').classList.remove('open')"
          class="absolute top-4 right-4 w-7 h-7 bg-white/15 hover:bg-white/25 rounded-xl flex items-center justify-center transition-colors cursor-pointer">
          <i data-lucide="x" style="width:14px;height:14px"></i>
        </button>
      </div>

      <form method="post" action="{{ route('profile.destroy') }}" class="p-5 md:p-6 flex flex-col gap-4">
        @csrf
        @method('delete')

        <div class="flex gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
          <i data-lucide="triangle-alert" style="width:14px;height:14px" class="text-red-400 flex-shrink-0 mt-0.5"></i>
          <div class="text-[11px] text-red-700 font-medium leading-relaxed">
            Tindakan ini <strong>tidak dapat dibatalkan</strong>. Semua data akan dihapus permanen.
          </div>
        </div>

        <div>
          <label for="del_password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Password Kamu</label>
          <div class="relative">
            <input id="del_password" name="password" type="password"
              class="moto-input" placeholder="Masukkan password untuk konfirmasi"/>
            <button type="button" onclick="togglePass('del_password', this)"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
              <i data-lucide="eye" style="width:14px;height:14px"></i>
            </button>
          </div>
          @if ($errors->userDeletion->get('password'))
            @foreach ($errors->userDeletion->get('password') as $msg)
              <div class="moto-error">{{ $msg }}</div>
            @endforeach
          @endif
        </div>

        <div class="flex gap-2.5">
          <button type="button" onclick="document.getElementById('deleteModal').classList.remove('open')"
            class="flex-1 py-2.5 bg-slate-50 border border-slate-200 text-slate-500 font-bold text-[12px] rounded-xl hover:bg-slate-100 transition-colors cursor-pointer">
            Batal
          </button>
          <button type="submit"
            class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold text-[12px] rounded-xl shadow shadow-red-200 transition-colors cursor-pointer">
            Ya, Hapus Akun
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Alpine.js for x-data transitions -->
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

  <script>
    // ── Lucide ──
    if (window.lucide) lucide.createIcons();

    // ── Clock ──
    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    function updateClock() {
      const now = new Date();
      const hh = String(now.getHours()).padStart(2,'0');
      const mm = String(now.getMinutes()).padStart(2,'0');
      const ss = String(now.getSeconds()).padStart(2,'0');
      document.getElementById('clock').textContent = `${hh}:${mm}:${ss}`;
      const dateEl = document.getElementById('date');
      if (dateEl) dateEl.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // ── Sidebar ──
    function openSidebar() {
      document.getElementById('sidebar').classList.add('open');
      document.getElementById('sidebar-overlay').classList.add('active');
    }
    function closeSidebar() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('sidebar-overlay').classList.remove('active');
    }

    // ── Tabs ──
    function switchTab(name) {
      ['info','password','danger'].forEach(t => {
        document.getElementById('tab-' + t).classList.remove('active');
        document.getElementById('panel-' + t).classList.remove('active');
      });
      document.getElementById('tab-' + name).classList.add('active');
      document.getElementById('panel-' + name).classList.add('active');
      // Re-init lucide after panel switch
      if (window.lucide) lucide.createIcons();
    }

    // ── Auto-open tab if there are errors ──
    @if ($errors->updatePassword->any())
      switchTab('password');
    @elseif ($errors->userDeletion->any())
      switchTab('danger');
      document.getElementById('deleteModal').classList.add('open');
    @endif

    // ── Toggle password visibility ──
    function togglePass(inputId, btn) {
      const input = document.getElementById(inputId);
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      btn.innerHTML = isText
        ? '<i data-lucide="eye" style="width:14px;height:14px"></i>'
        : '<i data-lucide="eye-off" style="width:14px;height:14px"></i>';
      if (window.lucide) lucide.createIcons();
    }

    // ── Close delete modal on backdrop click ──
    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) this.classList.remove('open');
    });
  </script>


  <!-- ══ TOAST CONTAINER ══ -->
  <div id="moto-toast-wrap"></div>

  <script>
    // ── motoToast helper ──
    const _MT_ICONS = {
      success: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
      error:   '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
      warning: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      info:    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    };
    const _MT_TITLES = { success:'Berhasil', error:'Gagal', warning:'Peringatan', info:'Info' };
    function motoToast(type, msg, duration = 4000) {
      const wrap = document.getElementById('moto-toast-wrap');
      if (!wrap) return;
      const el = document.createElement('div');
      el.className = 'moto-toast ' + type;
      el.innerHTML = `
        <div class="moto-toast-icon">${_MT_ICONS[type]}</div>
        <div class="moto-toast-body">
          <div class="moto-toast-title">${_MT_TITLES[type]}</div>
          <div class="moto-toast-msg">${msg}</div>
        </div>
        <div class="moto-toast-close" onclick="this.closest('.moto-toast').remove()">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </div>`;
      wrap.appendChild(el);
      setTimeout(() => { el.classList.add('hide'); setTimeout(() => el.remove(), 260); }, duration);
    }

    // ── Trigger toast dari Laravel session ──
    @if (session('status') === 'profile-updated')
      window.addEventListener('DOMContentLoaded', () => motoToast('success', 'Informasi profil berhasil disimpan.'));
    @endif
    @if (session('status') === 'password-updated')
      window.addEventListener('DOMContentLoaded', () => motoToast('success', 'Password berhasil diperbarui.'));
    @endif
    @if (session('status') === 'verification-link-sent')
      window.addEventListener('DOMContentLoaded', () => motoToast('info', 'Link verifikasi telah dikirim ke email kamu.'));
    @endif
    @if ($errors->updatePassword->any())
      window.addEventListener('DOMContentLoaded', () => motoToast('error', 'Gagal memperbarui password. Periksa kembali isian kamu.'));
    @endif
    @if ($errors->userDeletion->any())
      window.addEventListener('DOMContentLoaded', () => motoToast('error', 'Gagal menghapus akun. Password tidak sesuai.'));
    @endif
    @if ($errors->any() && !$errors->updatePassword->any() && !$errors->userDeletion->any())
      window.addEventListener('DOMContentLoaded', () => motoToast('error', 'Terdapat kesalahan pada form. Periksa kembali isian kamu.'));
    @endif
  </script>
</body>
</html>