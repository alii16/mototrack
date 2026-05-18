// ── Sidebar toggle (same as geofence/dashboard) ──
function openSidebar() {
    document.getElementById("sidebar").classList.add("open");
    document.getElementById("sidebar-overlay").classList.add("active");
}
function closeSidebar() {
    document.getElementById("sidebar").classList.remove("open");
    document.getElementById("sidebar-overlay").classList.remove("active");
}

// ── Clock ──
function updateClock() {
    const now = new Date(),
        pad = (n) => String(n).padStart(2, "0");
    document.getElementById("clock").textContent =
        pad(now.getHours()) +
        ":" +
        pad(now.getMinutes()) +
        ":" +
        pad(now.getSeconds());
    const days = [
        "Minggu",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
    ];
    const months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "Mei",
        "Jun",
        "Jul",
        "Agu",
        "Sep",
        "Okt",
        "Nov",
        "Des",
    ];
    document.getElementById("date").textContent =
        days[now.getDay()] +
        ", " +
        now.getDate() +
        " " +
        months[now.getMonth()] +
        " " +
        now.getFullYear();
}
updateClock();
setInterval(updateClock, 1000);

// ── State ──
const ROUTE_COLORS = [
    "#2563eb",
    "#f97316",
    "#22c55e",
    "#a855f7",
    "#ef4444",
    "#06b6d4",
    "#f43f5e",
    "#0ea5e9",
];
let allSessions = [];
let activeId = null;
let tripChart = null;

// ── Leaflet map ──
const map = L.map("mainMap").setView([-3.6527, 128.1947], 13);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
}).addTo(map);
const histLayer = L.layerGroup().addTo(map);

// ── Format helpers ──
function fmtDateTime(str) {
    return new Date(str).toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}
function fmtTimeOnly(str) {
    return new Date(str).toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    });
}
function fmtDur(start, end) {
    const m = Math.round((new Date(end) - new Date(start)) / 60000);
    return m < 60 ? m + " menit" : Math.floor(m / 60) + "j " + (m % 60) + "m";
}

// Haversine
function totalDist(pts) {
    let d = 0,
        R = 6371;
    for (let i = 1; i < pts.length; i++) {
        const dLat = ((pts[i][0] - pts[i - 1][0]) * Math.PI) / 180;
        const dLng = ((pts[i][1] - pts[i - 1][1]) * Math.PI) / 180;
        const a =
            Math.sin(dLat / 2) ** 2 +
            Math.cos((pts[i - 1][0] * Math.PI) / 180) *
                Math.cos((pts[i][0] * Math.PI) / 180) *
                Math.sin(dLng / 2) ** 2;
        d += R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }
    return d;
}

// ── Load history from API ──
function loadHistory() {
    fetch("/api/history")
        .then((r) => {
            if (r.status === 401) {
                location.href = "/login";
                return null;
            }
            return r.json();
        })
        .then((data) => {
            if (!data) return;
            allSessions = data;
            updateStats();
            renderList(data);
            buildChart(data);
        })
        .catch((e) => {
            document.getElementById("riwayatList").innerHTML =
                `<div class="px-5 py-8 text-center">
               <div class="font-bold text-amber-500 text-[13px]">Gagal memuat: ${e.message}</div>
             </div>`;
            motoToast('error', 'Gagal memuat riwayat: ' + e.message);
        });
}

// ── Stat cards ──
function updateStats() {
    const total = allSessions.length;
    document.getElementById("statTotal").textContent = total;
    document.getElementById("statTotalSub").textContent =
        total + " sesi tersimpan";
    document.getElementById("tripCount").textContent = total + " perjalanan";

    let totalKm = 0,
        totalMin = 0;
    allSessions.forEach((s) => {
        const pts = (s.track ?? []).map((p) => [
            parseFloat(p.lat),
            parseFloat(p.lng),
        ]);
        totalKm += totalDist(pts);
        totalMin += Math.round(
            (new Date(s.ended_at) - new Date(s.started_at)) / 60000,
        );
    });
    document.getElementById("statJarak").textContent = totalKm.toFixed(1);
    document.getElementById("statDurasi").textContent = (totalMin / 60).toFixed(
        1,
    );
}

// ── Dynamic Chart — last 7 days ──
function buildChart(sessions) {
    const labels = [],
        dataKm = [];
    const today = new Date();
    today.setHours(23, 59, 59, 999);

    for (let i = 6; i >= 0; i--) {
        const d = new Date(today);
        d.setDate(d.getDate() - i);
        const dayStr = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"][
            d.getDay()
        ];
        labels.push(dayStr);

        const dayStart = new Date(d);
        dayStart.setHours(0, 0, 0, 0);
        const dayEnd = new Date(d);
        dayEnd.setHours(23, 59, 59, 999);

        let km = 0;
        sessions.forEach((s) => {
            const t = new Date(s.started_at);
            if (t >= dayStart && t <= dayEnd) {
                const pts = (s.track ?? []).map((p) => [
                    parseFloat(p.lat),
                    parseFloat(p.lng),
                ]);
                km += totalDist(pts);
            }
        });
        dataKm.push(parseFloat(km.toFixed(2)));
    }

    const ctx = document.getElementById("tripChart").getContext("2d");

    if (tripChart) tripChart.destroy();
    tripChart = new Chart(ctx, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: "Jarak (km)",
                    data: dataKm,
                    backgroundColor: (ctx) => {
                        const g = ctx.chart.ctx.createLinearGradient(
                            0,
                            0,
                            0,
                            100,
                        );
                        g.addColorStop(0, "rgba(37,99,235,.9)");
                        g.addColorStop(1, "rgba(37,99,235,.4)");
                        return g;
                    },
                    borderRadius: 6,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "#1e293b",
                    titleColor: "#94a3b8",
                    bodyColor: "#fff",
                    padding: 8,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: { label: (c) => c.parsed.y + " km" },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: { size: 10, family: "Plus Jakarta Sans" },
                        color: "#94a3b8",
                    },
                },
                y: {
                    grid: { color: "#f1f5f9" },
                    border: { display: false },
                    ticks: {
                        font: { size: 10, family: "Plus Jakarta Sans" },
                        color: "#94a3b8",
                        callback: (v) => v + " km",
                    },
                },
            },
            barPercentage: 0.6,
            categoryPercentage: 0.65,
        },
    });
}

// ── Render list ──
function renderList(sessions) {
    const el = document.getElementById("riwayatList");
    if (!sessions.length) {
        el.innerHTML = `<div class="px-5 py-10 text-center">
          <div class="text-[13px] font-bold text-slate-400">Belum ada sesi tersimpan.</div>
        </div>`;
        return;
    }
    el.innerHTML = sessions
        .map((s, i) => {
            const color = ROUTE_COLORS[i % ROUTE_COLORS.length];
            const isActive = s.id === activeId;
            const start = fmtDateTime(s.started_at);
            const end = fmtTimeOnly(s.ended_at);
            const dur = Math.round(
                (new Date(s.ended_at) - new Date(s.started_at)) / 60000,
            );
            const pts = (s.track ?? []).map((p) => [
                parseFloat(p.lat),
                parseFloat(p.lng),
            ]);
            const km = totalDist(pts).toFixed(1);

            return `<div class="row-hover${isActive ? " active" : ""} px-4 md:px-5 py-3 md:py-3.5 flex items-center gap-3 cursor-default border-b border-slate-100 last:border-0" data-id="${s.id}">
          <!-- Icon -->
          <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl md:rounded-2xl flex items-center justify-center flex-shrink-0 relative" style="background:${color}14">
            <svg width="16" height="16" fill="none" stroke="${color}" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            ${isActive ? `<div class="absolute -top-0.5 -right-0.5 w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-blue-500 border-2 border-white live-dot"></div>` : ""}
          </div>
          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="font-bold text-[12px] md:text-[13px] text-slate-800 truncate">${start} – ${end}</div>
            <div class="text-[10px] text-slate-400 font-medium mt-0.5">${s.points ?? pts.length} titik &bull; ±${dur} menit</div>
          </div>
          <!-- Badge km -->
          <div class="${isActive ? "invisible" : ""}">
            <span class="text-[10px] font-bold px-2 md:px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-100">${km} km</span>
          </div>
          <!-- Actions -->
          <div class="flex gap-1 md:gap-1.5 flex-shrink-0">
            <button onclick="hapus(${s.id})"
              class="w-7 h-7 md:w-8 md:h-8 rounded-lg md:rounded-xl bg-red-50 hover:bg-red-100 text-red-400 flex items-center justify-center transition-colors cursor-pointer border border-red-100">
              <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
            <button onclick="lihat(${s.id})"
              class="px-2 md:px-2.5 h-7 md:h-8 rounded-lg md:rounded-xl text-[10px] md:text-[11px] font-bold transition-all cursor-pointer border flex items-center gap-1 ${
                  isActive
                      ? "bg-blue-600 text-white border-blue-600 shadow shadow-blue-200"
                      : "bg-slate-50 text-slate-500 border-slate-200 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200"
              }">
              ${isActive ? "Tutup" : "Lihat"}
              <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="${isActive ? "M6 18L18 6M6 6l12 12" : "M9 5l7 7-7 7"}"/>
              </svg>
            </button>
          </div>
        </div>`;
        })
        .join("");
}

// ── Filter/search ──
function filterList() {
    const q = document.getElementById("searchInput").value.toLowerCase();
    const filtered = allSessions.filter((s) =>
        fmtDateTime(s.started_at).toLowerCase().includes(q),
    );
    renderList(filtered);
}

// ── Select session → show on map ──
function lihat(id) {
    if (activeId === id) {
        closeDetail();
        return;
    }
    activeId = id;
    const s = allSessions.find((s) => s.id === id);
    if (!s) return;

    const track = s.track ?? [];
    const pts = track.map((p) => [parseFloat(p.lat), parseFloat(p.lng)]);

    // Show map, hide placeholder
    document.getElementById("mapPlaceholder").style.display = "none";
    document.getElementById("btnClose").classList.replace("hidden", "flex");
    document.getElementById("mapBadge").classList.replace("hidden", "flex");

    histLayer.clearLayers();

    if (!pts.length) {
        document.getElementById("mapMeta").textContent =
            "Sesi ini tidak memiliki titik GPS.";
        renderList(allSessions);
        return;
    }

    // Polyline
    const color = ROUTE_COLORS[allSessions.indexOf(s) % ROUTE_COLORS.length];
    L.polyline(pts, { color, weight: 3, opacity: 0.85 }).addTo(histLayer);

    // Start marker
    L.circleMarker(pts[0], {
        radius: 6,
        color: "#22c55e",
        fillColor: "#22c55e",
        fillOpacity: 1,
        weight: 2,
    })
        .bindPopup("<b>Mulai</b><br>" + fmtDateTime(s.started_at))
        .addTo(histLayer);

    // End marker
    L.circleMarker(pts[pts.length - 1], {
        radius: 6,
        color: "#ef4444",
        fillColor: "#ef4444",
        fillOpacity: 1,
        weight: 2,
    })
        .bindPopup("<b>Selesai</b><br>" + fmtDateTime(s.ended_at))
        .addTo(histLayer);

    // Mid-points (subsampled)
    const step = Math.max(1, Math.floor(pts.length / 40));
    track.forEach((p, i) => {
        if (i === 0 || i === track.length - 1 || i % step !== 0) return;
        L.circleMarker([parseFloat(p.lat), parseFloat(p.lng)], {
            radius: 3,
            color,
            fillColor: color,
            fillOpacity: 0.7,
            weight: 0,
        })
            .bindPopup(
                `<b>Titik #${i}</b><br>` +
                    `${parseFloat(p.lat).toFixed(6)}, ${parseFloat(p.lng).toFixed(6)}` +
                    (p.ts ? `<br>${fmtDateTime(p.ts)}` : "") +
                    (p.sat !== undefined ? `<br>Satelit: ${p.sat}` : ""),
            )
            .addTo(histLayer);
    });

    map.fitBounds(L.polyline(pts).getBounds(), { padding: [30, 30] });
    map.invalidateSize();

    // Update footer
    const dist = totalDist(pts).toFixed(2);
    const dur = fmtDur(s.started_at, s.ended_at);
    const avgSat = track.length
        ? (track.reduce((a, p) => a + (p.sat ?? 0), 0) / track.length).toFixed(
              1,
          )
        : "-";

    document.getElementById("mapTitle").textContent =
        "Rute " + fmtDateTime(s.started_at);
    document.getElementById("mapMeta").textContent = dist + " km · " + dur;
    document.getElementById("fDate").textContent = new Date(
        s.started_at,
    ).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
    document.getElementById("fTime").textContent =
        fmtTimeOnly(s.started_at) + " – " + fmtTimeOnly(s.ended_at);
    document.getElementById("fJarak").textContent = "Jarak: " + dist + " km";
    document.getElementById("fDurasi").textContent = "Durasi: " + dur;
    document.getElementById("fSpeed").textContent = "Sat avg: " + avgSat;

    renderList(allSessions);
}

async function hapus(id) {
    const ok = await motoConfirm('Hapus Riwayat', 'Sesi perjalanan yang dihapus tidak dapat dikembalikan.');
    if (!ok) return;
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
    try {
        const r = await fetch("/api/history/" + id, {
            method: "DELETE",
            headers: { "X-CSRF-TOKEN": CSRF, "Content-Type": "application/json" },
        });
        if (!r.ok) throw new Error("HTTP " + r.status);
        const idx = allSessions.findIndex((s) => s.id === id);
        if (idx !== -1) allSessions.splice(idx, 1);
        if (activeId === id) closeDetail();
        else renderList(allSessions);
        updateStats();
        buildChart(allSessions);
        motoToast('success', 'Riwayat sesi berhasil dihapus.');
    } catch(e) {
        motoToast('error', 'Gagal menghapus riwayat: ' + e.message);
    }
}

function closeDetail() {
    activeId = null;
    histLayer.clearLayers();
    document.getElementById("mapPlaceholder").style.display = "flex";
    document.getElementById("btnClose").classList.replace("flex", "hidden");
    document.getElementById("mapBadge").classList.replace("flex", "hidden");
    document.getElementById("mapTitle").textContent = "Peta Riwayat";
    document.getElementById("mapMeta").textContent =
        "Pilih riwayat untuk melihat rute";
    document.getElementById("fDate").textContent = "—";
    document.getElementById("fTime").textContent =
        "Pilih riwayat untuk melihat detail";
    document.getElementById("fJarak").textContent = "Jarak: —";
    document.getElementById("fDurasi").textContent = "Durasi: —";
    document.getElementById("fSpeed").textContent = "Sat avg: —";
    renderList(allSessions);
}

// ── Init ──
lucide.createIcons();
loadHistory();