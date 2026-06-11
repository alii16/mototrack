lucide.createIcons();

/* ── Sidebar toggle ── */
function openSidebar() {
    document.getElementById("sidebar").classList.add("open");
    document.getElementById("sidebar-overlay").classList.add("active");
    document.body.style.overflow = "hidden";
}
function closeSidebar() {
    document.getElementById("sidebar").classList.remove("open");
    document.getElementById("sidebar-overlay").classList.remove("active");
    document.body.style.overflow = "";
}

/* ── Clock ── */
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

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ── Leaflet ── */
const map = L.map("map").setView([-3.6527, 128.1947], 13);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
}).addTo(map);
const currentMarker = L.marker([0, 0], { title: "Posisi ESP32" }).addTo(map);
const liveTrack = L.polyline([], {
    color: "#2563eb",
    weight: 3,
    opacity: 0.8,
}).addTo(map);
let historyLayer = L.layerGroup().addTo(map);

let currentRelay = false,
    firstFix = true,
    livePoints = [],
    geofences = [],
    geoLayers = {},
    insideState = {},
    lastNotifTime = {},
    lastKnownLat = null,
    lastKnownLng = null;
const NOTIF_COOLDOWN = 60000;

// Tambah variable tracking state sebelumnya
let prevEspOnline = null;

function fetchStatus() {
    fetch("/api/status")
        .then((r) => {
            if (r.status === 401) { location.href = "/login"; return null; }
            return r.json();
        })
        .then((d) => {
            if (!d) return;
            updateEspBadge(d.esp_online);
            // Relay hanya di-update dari HTTP jika MQTT tidak terhubung (fallback)
            if (!mqttClient || !mqttClient.connected) {
                updateRelay(d.relay);
            }
            updateGPS(d);
            setBar(
                (mqttClient && mqttClient.connected ? "MQTT ● " : "Live ● ") +
                new Date().toLocaleTimeString("id-ID")
            );

            // ← TAMBAHKAN INI: flush hanya saat transisi online→offline
            if (prevEspOnline === true && d.esp_online === false) {
                fetch("/api/session/flush", {
                    method: "POST",
                    headers: { "X-CSRF-TOKEN": CSRF }
                }).catch(() => {});
            }
            prevEspOnline = d.esp_online;
        })
        .catch((e) => setBar("Error: " + e.message));
}

function fetchHistory() {
    fetch("/api/history")
        .then((r) => {
            if (r.status === 401) {
                location.href = "/login";
                return null;
            }
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then((logs) => {
            if (!logs) return;
            const recent = logs.slice(0, 5);
            document.getElementById("stat-history").innerHTML =
                `${logs.length} <span class="text-[13px] font-medium text-slate-400">trip</span>`;
            document.getElementById("stat-history-sub").textContent =
                logs.length > 0
                    ? `${recent.length} perjalanan terbaru`
                    : "Belum ada sesi";
            if (!recent.length) {
                document.getElementById("history-list").innerHTML = `
            <div class="flex flex-col items-center gap-3 py-10 text-slate-400">
              <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="opacity-50"><path d="M12 2a10 10 0 1 0 10 10"/><polyline points="12 6 12 12 16 14"/></svg>
              </div>
              <div class="text-center">
                <div class="font-bold text-[12px] text-slate-500">Belum ada sesi tersimpan</div>
                <div class="text-[11px] text-slate-400 mt-0.5">Sesi akan muncul setelah perjalanan</div>
              </div>
            </div>`;
                return;
            }
            // Simpan logs untuk filter search
            window._dashHistoryLogs = recent;
            renderHistoryRows(recent);
            // Setup search listener (sekali saja)
            const searchEl = document.getElementById("hist-search");
            if (searchEl && !searchEl._bound) {
                searchEl._bound = true;
                searchEl.addEventListener("input", () => {
                    const q = searchEl.value.toLowerCase().trim();
                    const filtered = (window._dashHistoryLogs || []).filter(l => {
                        const d = new Date(l.started_at).toLocaleDateString("id-ID", { day:"2-digit", month:"short", year:"numeric" });
                        return !q || d.toLowerCase().includes(q);
                    });
                    renderHistoryRows(filtered);
                });
            }
        })
        .catch((e) => {
            document.getElementById("history-list").innerHTML =
                `<div class="text-amber-500 text-xs px-1">Gagal muat: ${e.message}</div>`;
        });
}

function renderHistoryRows(logs) {
    const el = document.getElementById("history-list");
    if (!el) return;
    if (!logs.length) {
        el.innerHTML = `
            <div class="flex flex-col items-center gap-3 py-10 text-slate-400">
              <div class="text-center">
                <div class="font-bold text-[12px] text-slate-500">Tidak ada hasil</div>
                <div class="text-[11px] text-slate-400 mt-0.5">Coba kata kunci lain</div>
              </div>
            </div>`;
        return;
    }
    const mapIcon = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>`;
    el.innerHTML = logs.map((l) => {
        const dateObj   = new Date(l.started_at);
        const dateStr   = dateObj.toLocaleDateString("id-ID", { day:"2-digit", month:"short", year:"numeric" });
        const startTime = dateObj.toLocaleTimeString("id-ID", { hour:"2-digit", minute:"2-digit" });
        const endTime   = new Date(l.ended_at).toLocaleTimeString("id-ID", { hour:"2-digit", minute:"2-digit" });
        const dur       = Math.round((new Date(l.ended_at) - dateObj) / 60000);
        const durStr    = dur < 60 ? `${dur} mnt` : `${Math.floor(dur/60)}j ${dur%60}m`;
        return `<div class="hist-row" style="cursor:default">
            <div class="hist-row-icon" style="pointer-events:none">${mapIcon}</div>
            <div class="hist-row-body">
              <div class="hist-row-title">${dateStr}</div>
              <div class="hist-row-sub">${startTime} – ${endTime} &nbsp;·&nbsp; ${l.points} titik</div>
            </div>
            <div class="hist-row-badge">${durStr}</div>
          </div>`;
    }).join("");
}
function showSession(trackJson, label) {
    const track = JSON.parse(trackJson);
    historyLayer.clearLayers();
    const pts = track.map((p) => [p.lat, p.lng]);
    if (!pts.length) return;
    L.polyline(pts, {
        color: "#f59e0b",
        weight: 2.5,
        opacity: 0.75,
        dashArray: "5 5",
    }).addTo(historyLayer);
    L.circleMarker(pts[0], { radius: 5, color: "#22c55e", fillOpacity: 1 })
        .addTo(historyLayer)
        .bindPopup("Mulai: " + label);
    L.circleMarker(pts[pts.length - 1], {
        radius: 5,
        color: "#ef4444",
        fillOpacity: 1,
    })
        .addTo(historyLayer)
        .bindPopup("Selesai");
    map.fitBounds(L.polyline(pts).getBounds(), { padding: [30, 30] });
}

function updateEspBadge(online) {
    const badge = document.getElementById("esp-badge");
    if (online) {
        badge.className =
            "inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wide px-2 md:px-3 py-1 rounded-full bg-green-100 text-green-600 flex-shrink-0";
        badge.innerHTML =
            '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg> <span id="esp-badge-text" class="hidden sm:inline">ESP ONLINE</span>';
    } else {
        badge.className =
            "inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wide px-2 md:px-3 py-1 rounded-full bg-red-100 text-red-600 flex-shrink-0";
        badge.innerHTML =
            '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.56 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg> <span id="esp-badge-text" class="hidden sm:inline">ESP OFFLINE</span>';
    }
}

function updateRelay(state) {
    currentRelay = state;
    const s = document.getElementById("relay-status"),
        btn = document.getElementById("relay-btn");
    const badge = document.getElementById("relay-badge"),
        badgeTxt = document.getElementById("relay-badge-text");
    const r1 = document.getElementById("relay-r1");
    const engineEl = document.getElementById("engine-status");

    s.textContent = state ? "ON" : "OFF";
    s.className = state
        ? "font-extrabold leading-none text-green-500"
        : "font-extrabold leading-none text-red-500";
    btn.textContent = state ? "Matikan Relay" : "Nyalakan Relay";
    btn.style.background = state ? "#ef4444" : "#22c55e";

    if (state) {
        badge.className =
            "flex items-center gap-1.5 bg-green-100 text-green-600 px-2.5 py-1 rounded-full text-[11px] font-bold flex-shrink-0";
        badgeTxt.textContent = "Mati";
        r1.textContent = "Aktif";
        r1.style.color = "#2563eb";
        engineEl.textContent = "Tidak Bisa Nyala";
        engineEl.className =
            "font-semibold px-2 py-0.5 rounded-md text-[11px] bg-blue-100 text-blue-600";
    } else {
        badge.className =
            "flex items-center gap-1.5 bg-red-100 text-red-500 px-2.5 py-1 rounded-full text-[11px] font-bold flex-shrink-0";
        badgeTxt.textContent = "Hidup";
        r1.textContent = "Nonaktif";
        r1.style.color = "#94a3b8";
        engineEl.textContent = "Siap Nyala";
        engineEl.className =
            "font-semibold px-2 py-0.5 rounded-md text-[11px] bg-slate-200 text-slate-600";
    }
}

function updateGPS(d) {
    const valid = d.gps_valid && d.esp_online,
        online = d.esp_online;
    const gpsEl = document.getElementById("stat-gps-status");
    gpsEl.textContent = online
        ? d.gps_valid
            ? "Online"
            : "Searching"
        : "Offline";
    gpsEl.style.color =
        online && d.gps_valid ? "#22c55e" : online ? "#f59e0b" : "#ef4444";
    const sat = d.satellites ?? 0;
    const satLabel = sat >= 10 ? "Sgt Kuat" : sat >= 7 ? "Kuat" : sat >= 4 ? "Sedang" : "Lemah";
    document.getElementById("stat-gps-sub").textContent = online
        ? `Sinyal ${d.satellites ?? 0} satelit`
        : "ESP tidak terhubung";
    document.getElementById("stat-sat-sub").textContent = online && d.gps_valid
        ? `Sinyal ${satLabel}`
        : "Sinyal –";
    document.getElementById("stat-sat").innerHTML =
        `${sat} <span class="text-[13px] font-medium text-slate-400">sat</span>`;
    if (valid) {
        lastKnownLat = parseFloat(d.lat);
        lastKnownLng = parseFloat(d.lng);
        const ll = [lastKnownLat, lastKnownLng];
        document.getElementById("coord-display").textContent =
            `${lastKnownLat.toFixed(5)}°S, ${lastKnownLng.toFixed(5)}°E`;
        currentMarker
            .setLatLng(ll)
            .bindPopup(
                `<b>Posisi sekarang</b><br>${parseFloat(d.lat).toFixed(6)}, ${parseFloat(d.lng).toFixed(6)}`,
            );
        livePoints.push(ll);
        liveTrack.setLatLngs(livePoints);
        if (firstFix) {
            map.setView(ll, 16);
            firstFix = false;
        }
        if (geofences.length > 0) checkGeofences(lastKnownLat, lastKnownLng);
    }
    if (!online && livePoints.length > 0) {
        livePoints = [];
        liveTrack.setLatLngs([]);
        fetchHistory();
    }
}

function toggleRelay() {
    const btn = document.getElementById("relay-btn");
    btn.disabled = true;
    const turningOn = !currentRelay;

    // Optimistic update — langsung update UI tanpa tunggu response
    updateRelay(turningOn);

    fetch(turningOn ? "/api/relay/on" : "/api/relay/off", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": CSRF },
    })
        .then((r) => { if (!r.ok) throw new Error("HTTP " + r.status); return r.json(); })
        .then((d) => {
            btn.disabled = false;
            motoToast("success", turningOn ? "Relay berhasil dinyalakan." : "Relay berhasil dimatikan.");
        })
        .catch((e) => {
            // Rollback UI jika request gagal
            updateRelay(!turningOn);
            btn.disabled = false;
            motoToast("error", "Gagal mengubah relay: " + e.message);
        });
}


function setBar(msg) {
    document.getElementById("conn-bar").textContent = msg;
}

function loadGeofences() {
    fetch("/api/geofences")
        .then((r) => {
            if (r.status === 401) {
                location.href = "/login";
                return null;
            }
            return r.json();
        })
        .then((data) => {
            if (!data) return;
            Object.values(geoLayers).forEach((ls) =>
                ls.forEach((l) => map.removeLayer(l)),
            );
            geoLayers = {};
            geofences = data.filter((g) => g.status === "active");
            geofences.forEach((g) => {
                if (!(g.id in insideState)) insideState[g.id] = null;
                if (!(g.id in lastNotifTime)) lastNotifTime[g.id] = 0;
            });
            geofences.forEach((g) => {
                const lat = parseFloat(g.latitude),
                    lng = parseFloat(g.longitude),
                    radius = parseFloat(g.radius);
                const circle = L.circle([lat, lng], {
                    radius,
                    color: "#2563eb",
                    fillColor: "#2563eb",
                    fillOpacity: 0.1,
                    weight: 1.5,
                    dashArray: "4 4",
                })
                    .addTo(map)
                    .bindPopup(`<b>${g.name}</b><br>Radius: ${radius}m`);
                const label = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: "",
                        html: `<div style="background:#fff;border:1px solid #2563eb;color:#2563eb;font-size:10px;padding:2px 6px;border-radius:4px;white-space:nowrap;font-family:'Plus Jakarta Sans',sans-serif;font-weight:600">${g.name}</div>`,
                        iconAnchor: [0, 0],
                    }),
                }).addTo(map);
                geoLayers[g.id] = [circle, label];
            });
            renderGeoList();
            document.getElementById("stat-geo").innerHTML =
                `${geofences.length} <span class="text-[13px] font-medium text-slate-400">zona</span>`;
            document.getElementById("stat-geo-sub").textContent =
                `dari ${data.length} zona terdaftar`;
            if (lastKnownLat !== null && lastKnownLng !== null)
                setTimeout(
                    () => checkGeofences(lastKnownLat, lastKnownLng),
                    800,
                );
        })
        .catch(() => {
            document.getElementById("geo-list").innerHTML =
                '<div class="text-amber-500">Gagal memuat geofence</div>';
        motoToast("error", "Gagal memuat data geofence.");
        });
}

function renderGeoList() {
    const el = document.getElementById("geo-list");
    if (!geofences.length) {
        el.innerHTML =
            '<div class="text-slate-400">Belum ada geofence aktif</div>';
        return;
    }
    el.innerHTML = geofences
        .map(
            (g) => `<div class="geo-item" id="geo-item-${g.id}">
  <div class="geo-dot ${insideState[g.id] ? "inside" : ""}" id="geo-dot-${g.id}"></div>
  <div class="geo-name">${g.name}</div><div class="geo-radius">${g.radius}m</div></div>`,
        )
        .join("");
}

function checkGeofences(lat, lng) {
    if (!geofences.length) return;
    geofences.forEach((g) => {
        const dist = haversine(
            lat,
            lng,
            parseFloat(g.latitude),
            parseFloat(g.longitude),
        );
        const inside = dist <= parseFloat(g.radius);
        const dot = document.getElementById(`geo-dot-${g.id}`);
        if (dot) dot.className = "geo-dot" + (inside ? " inside" : "");
        if (!(g.id in insideState)) insideState[g.id] = null;
        const wasInside = insideState[g.id],
            now = Date.now();
        const isNewEntry =
            inside && (wasInside === false || wasInside === null);
        if (isNewEntry && now - (lastNotifTime[g.id] || 0) > NOTIF_COOLDOWN) {
            lastNotifTime[g.id] = now;
            sendTelegramAlert(g, lat, lng, dist);
            showToast(`Kamu memasuki area: <b>${g.name}</b>`);
        }
        insideState[g.id] = inside;
    });
}

function sendTelegramAlert(geo, lat, lng, dist) {
    const token =
        (document.querySelector('meta[name="csrf-token"]') || {}).content ||
        CSRF;
    fetch("/api/geofence/alert", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": token },
        body: JSON.stringify({
            geo_name: geo.name,
            geo_id: geo.id,
            lat,
            lng,
            distance: Math.round(dist),
        }),
    })
        .then((r) => (r.status === 419 ? null : r.json()))
        .catch((e) => console.error("[Geofence]", e));
}

function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000,
        d1 = ((lat2 - lat1) * Math.PI) / 180,
        d2 = ((lng2 - lng1) * Math.PI) / 180;
    const a =
        Math.sin(d1 / 2) ** 2 +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(d2 / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// ── motoToast (konsisten dengan geofence & history) ──
const _MT_ICONS = {
    success: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    error:   '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    warning: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    info:    '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
};
const _MT_TITLES = { success:"Berhasil", error:"Gagal", warning:"Peringatan", info:"Info" };
function motoToast(type, msg, duration = 3500) {
    const wrap = document.getElementById("moto-toast-wrap");
    if (!wrap) return;
    const el = document.createElement("div");
    el.className = "moto-toast " + type;
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
    setTimeout(() => { el.classList.add("hide"); setTimeout(() => el.remove(), 260); }, duration);
}
// Alias untuk geofence alert lama (showToast dipanggil dari checkGeofenceAlerts)
function showToast(msg) { motoToast("info", msg); }

/* ═══════════════════════════════════════════════════════════
 *  MQTT via WebSocket — Mosquitto Lokal (port 9001)
 *  Ganti IP di bawah dengan IP komputer kamu (sama dengan SERVER_URL)
 * ═══════════════════════════════════════════════════════════ */
const MQTT_WS_URL       = "ws://192.168.1.100:9001/mqtt";  // ws:// bukan wss://
const MQTT_USER         = "";   // kosong — tanpa auth
const MQTT_PASS         = "";   // kosong — tanpa auth
const TOPIC_RELAY_STATE = "mototrack/naurah/relay/state";

let mqttClient = null;

function mqttConnect() {
    if (typeof mqtt === "undefined") {
        console.warn("[MQTT] Library mqtt.js tidak tersedia, fallback ke HTTP polling.");
        return;
    }
    mqttClient = mqtt.connect(MQTT_WS_URL, {
        clientId:        "dashboard-web-" + Math.random().toString(36).slice(2, 8),
        username:        MQTT_USER,
        password:        MQTT_PASS,
        clean:           true,
        reconnectPeriod: 5000,
        connectTimeout:  10000,
    });

    mqttClient.on("connect", () => {
        console.log("[MQTT] WebSocket terhubung ke broker");
        // Subscribe state relay — broker langsung kirim retained message terakhir
        mqttClient.subscribe(TOPIC_RELAY_STATE, { qos: 1 }, (err) => {
            if (err) console.error("[MQTT] Subscribe gagal:", err);
            else console.log("[MQTT] Subscribe:", TOPIC_RELAY_STATE);
        });
    });

    mqttClient.on("message", (topic, payload) => {
        const msg = payload.toString().trim();
        console.log("[MQTT] Pesan:", topic, "→", msg);
        if (topic === TOPIC_RELAY_STATE) {
            const isOn = (msg === "1" || msg === "true" || msg === "on");
            updateRelay(isOn);
        }
    });

    mqttClient.on("error",     (err) => console.error("[MQTT] Error:", err.message));
    mqttClient.on("reconnect", ()    => console.log("[MQTT] Reconnecting..."));
    mqttClient.on("offline",   ()    => console.warn("[MQTT] Offline, fallback ke polling"));
}

fetchStatus();
fetchHistory();
loadGeofences();
mqttConnect();
setInterval(fetchStatus, 5000);
setInterval(fetchHistory, 100000);
setInterval(loadGeofences, 30000);