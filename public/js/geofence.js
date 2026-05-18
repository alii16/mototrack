const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
const COLORS = ['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16'];

let geofencesData = {};
let geofenceOrder = [];
let selectedId    = null;
let editingId     = null;
let mainMap       = null;
let modalMap      = null;
let modalPickMark = null;
let currentCircle = null;
let currentMarker = null;
let pieChart      = null;

// ── Mobile sidebar ──
function openSidebar()  { document.getElementById('mobileSidebar').classList.add('open'); document.getElementById('sidebarOverlay').classList.add('show'); }
function closeSidebar() { document.getElementById('mobileSidebar').classList.remove('open'); document.getElementById('sidebarOverlay').classList.remove('show'); }

// ── Clock ──
function updateClock() {
  const now = new Date(), p = n => String(n).padStart(2,'0');
  document.getElementById('clock').textContent = p(now.getHours())+':'+p(now.getMinutes())+':'+p(now.getSeconds());
  const D=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],M=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  const d=document.getElementById('date'); if(d) d.textContent=D[now.getDay()]+', '+now.getDate()+' '+M[now.getMonth()]+' '+now.getFullYear();
}
updateClock(); setInterval(updateClock,1000);

// ── Init main Leaflet map ──
function initMainMap() {
  if (mainMap) return;
  mainMap = L.map('mainMap').setView([-3.6527,128.1947],13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(mainMap);
}

// ── Load geofences ──
async function loadGeofences() {
  try {
    const res  = await fetch('/api/geofences');
    if (!res.ok) throw new Error('HTTP '+res.status);
    const data = await res.json();
    geofencesData={}; geofenceOrder=[];
    data.forEach(g => { geofencesData[g.id]=g; geofenceOrder.push(g.id); });
    updateStats(data); renderZoneList(data);
  } catch(e) {
    console.error(e);
    document.getElementById('zoneList').innerHTML=`<div class="px-5 py-10 text-center"><div class="flex justify-center mb-2"><i data-lucide="alert-triangle" class="w-8 h-8 text-red-300"></i></div><div class="font-bold text-red-400 text-[13px]">Gagal memuat zona</div><div class="text-[11px] text-slate-400 mt-1">${e.message}</div></div>`;
    setTimeout(() => lucide.createIcons(), 50);
  }
}

function updateStats(data) {
  document.getElementById('statTotal').textContent    = data.length;
  document.getElementById('statActive').textContent   = data.filter(g=>g.status==='active').length;
  document.getElementById('statInactive').textContent = data.filter(g=>g.status!=='active').length;
  const totalR = document.getElementById('statTotalRadius');
  if (totalR) totalR.textContent = data.reduce((s,g)=>s+parseFloat(g.radius||0),0).toLocaleString('id-ID');
}

// ── Pie chart ──
function renderPieChart(geofences) {
  const canvas = document.getElementById('radiusPieChart');
  if (!canvas || !geofences.length) return;

  const labels     = geofences.map(g => g.name);
  const data       = geofences.map(g => parseFloat(g.radius));
  const total      = data.reduce((a,b) => a+b, 0);
  const bgColors   = geofences.map((g,i) => g.status==='active' ? COLORS[i%COLORS.length]+'CC' : '#cbd5e1');
  const borderColors = geofences.map((g,i) => g.status==='active' ? COLORS[i%COLORS.length] : '#94a3b8');

  const totalEl = document.getElementById('pieTotalRadius');
  if (totalEl) totalEl.textContent = total.toLocaleString('id-ID');

  if (pieChart) { pieChart.destroy(); pieChart = null; }

  pieChart = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: bgColors,
        borderColor: borderColors,
        borderWidth: 2,
        hoverOffset: 5,
      }]
    },
    options: {
      responsive: false,
      cutout: '62%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => {
              const g = geofences[ctx.dataIndex];
              const pct = Math.round(ctx.parsed / total * 100);
              return `${ctx.label}: ${ctx.parsed}m (${pct}%) — ${g.status==='active'?'Aktif':'Nonaktif'}`;
            }
          }
        }
      }
    }
  });

  // Legend — tampilkan 3 pertama, sisanya bisa di-scroll
  const legendEl = document.getElementById('pieLegend');
  if (!legendEl) return;
  legendEl.innerHTML = geofences.map((g, i) => {
    const color = g.status === 'active' ? COLORS[i%COLORS.length] : '#94a3b8';
    const pct   = Math.round(parseFloat(g.radius) / total * 100);
    return `
      <div class="flex items-center gap-2">
        <div class="w-2 h-2 rounded-sm flex-shrink-0" style="background:${color}"></div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <span class="text-[11px] font-bold text-slate-700 truncate">${g.name}</span>
            <span class="text-[11px] font-bold text-slate-800 flex-shrink-0">${g.radius}m</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full" style="width:${pct}%;background:${color}"></div>
            </div>
            <span class="text-[9px] text-slate-400 font-medium flex-shrink-0">${pct}%</span>
          </div>
        </div>
      </div>`;
  }).join('');
}

// ── Render list ──
function renderZoneList(geofences) {
  renderPieChart(geofences);
  const list = document.getElementById('zoneList');
  if (!geofences.length) {
    list.innerHTML=`<div class="px-5 py-10 text-center"><div class="flex justify-center mb-2"><i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i></div><div class="font-bold text-slate-500 text-[13px]">Belum ada zona</div><div class="text-[11px] text-slate-400 mt-1">Tambah zona baru untuk memulai</div></div>`;
    setTimeout(() => lucide.createIcons(), 50);
    return;
  }
  list.innerHTML = geofences.map(g => {
    const idx   = geofenceOrder.indexOf(g.id);
    const color = COLORS[(idx>=0?idx:0)%COLORS.length];
    const active = selectedId===g.id;
    const lat4  = parseFloat(g.latitude).toFixed(4), lng4=parseFloat(g.longitude).toFixed(4);
    return `
    <div class="zone-row ${active?'active':''} border-b border-slate-100 last:border-0" id="zone-row-${g.id}">
      <div class="flex items-center gap-2.5 md:gap-3 px-4 md:px-5 py-3 md:py-3.5">
        <div class="w-9 h-9 md:w-10 md:h-10 rounded-2xl flex items-center justify-center flex-shrink-0 relative" style="background:${color}18">
          <svg class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="${color}" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          ${active?`<div class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 md:w-3 md:h-3 rounded-full bg-emerald-500 border-2 border-white live-dot"></div>`:''}
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-bold text-[12px] md:text-[13px] text-slate-800 truncate">${g.name}</div>
          <div class="text-[9px] md:text-[10px] text-slate-400 font-medium mt-0.5">${g.radius}m &nbsp;·&nbsp; ${lat4}°, ${lng4}°</div>
        </div>
        <div class="${active?'invisible':''} flex-shrink-0 hidden sm:block">
          ${g.status==='active'
            ?`<span class="text-[9px] md:text-[10px] font-bold px-2 md:px-2.5 py-0.5 md:py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">Aktif</span>`
            :`<span class="text-[9px] md:text-[10px] font-bold px-2 md:px-2.5 py-0.5 md:py-1 rounded-full bg-slate-100 text-slate-400">Nonaktif</span>`}
        </div>
        <div class="flex gap-1 md:gap-1.5 flex-shrink-0">
          <button onclick="editGeofence(${g.id})" title="Edit" class="w-7 h-7 md:w-8 md:h-8 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition-colors cursor-pointer border border-blue-100">
            <svg class="w-3 h-3 md:w-3.5 md:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </button>
          <button onclick="deleteGeofence(${g.id})" title="Hapus" class="w-7 h-7 md:w-8 md:h-8 rounded-xl bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition-colors cursor-pointer border border-red-100">
            <svg class="w-3 h-3 md:w-3.5 md:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
          <button onclick="toggleZone(${g.id})" class="px-2 md:px-2.5 h-7 md:h-8 rounded-xl text-[10px] md:text-[11px] font-bold transition-all cursor-pointer border flex items-center gap-1 ${active?'bg-blue-600 text-white border-blue-600 shadow shadow-blue-200':'bg-slate-50 text-slate-500 border-slate-200 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200'}">
            ${active?'Tutup':'Lihat'}
            <svg class="w-2.5 h-2.5 md:w-3 md:h-3 ${active?'rotate-90':''}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>
      <div class="${active?'block':'hidden'} px-4 md:px-5 pb-3.5 slide-in">
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-3.5 md:p-4">
          <div class="text-[8px] md:text-[9px] font-bold text-blue-500 uppercase tracking-widest mb-2.5">Detail Zona</div>
          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 md:gap-y-2">
            <div class="flex justify-between text-[10px] md:text-[11px]"><span class="text-slate-400">Nama</span><span class="font-bold text-slate-700 ml-2 truncate">${g.name}</span></div>
            <div class="flex justify-between text-[10px] md:text-[11px]"><span class="text-slate-400">Radius</span><span class="font-bold text-slate-700">${g.radius}m</span></div>
            <div class="flex justify-between text-[10px] md:text-[11px]"><span class="text-slate-400">Latitude</span><span class="font-bold text-slate-700">${parseFloat(g.latitude).toFixed(6)}°</span></div>
            <div class="flex justify-between text-[10px] md:text-[11px]"><span class="text-slate-400">Longitude</span><span class="font-bold text-slate-700">${parseFloat(g.longitude).toFixed(6)}°</span></div>
            <div class="flex justify-between text-[10px] md:text-[11px] col-span-2 pt-1.5 border-t border-blue-100 mt-0.5"><span class="text-slate-400">Status</span><span class="font-bold ${g.status==='active'?'text-emerald-600':'text-slate-400'}">${g.status==='active'?'<span class="flex items-center gap-1"><i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Aktif</span>':'<span class="flex items-center gap-1"><i data-lucide="circle" class="w-3.5 h-3.5"></i> Nonaktif</span>'}</span></div>
          </div>
        </div>
      </div>
    </div>`;
  }).join('');
  setTimeout(() => lucide.createIcons(), 50);
}

// ── Map actions ──
function toggleZone(id) { if(selectedId===id){closeMap();}else{selectGeofence(id);} }

function selectGeofence(id) {
  const geo=geofencesData[id]; if(!geo)return;
  selectedId=id;
  const lat=parseFloat(geo.latitude), lng=parseFloat(geo.longitude), radius=parseFloat(geo.radius);
  document.getElementById('mapPlaceholder').style.display='none';
  document.getElementById('btnCloseMap').classList.replace('hidden','flex');
  // document.getElementById('mapLiveBadge').classList.replace('hidden','flex');
  initMainMap(); setTimeout(()=>mainMap.invalidateSize(),80);
  if(currentCircle){mainMap.removeLayer(currentCircle);currentCircle=null;}
  if(currentMarker){mainMap.removeLayer(currentMarker);currentMarker=null;}
  const idx=geofenceOrder.indexOf(id), color=COLORS[(idx>=0?idx:0)%COLORS.length];
  currentCircle=L.circle([lat,lng],{radius,color,fillColor:color,fillOpacity:.14,weight:2.5,dashArray:geo.status==='active'?null:'8 5'}).addTo(mainMap);
  currentMarker=L.circleMarker([lat,lng],{radius:8,fillColor:color,color:'#fff',weight:3,fillOpacity:1}).addTo(mainMap)
    .bindPopup(`<b style="color:${color};font-size:13px">${geo.name}</b><br><span style="color:#64748b;font-size:11px">Radius: ${radius}m<br>${lat.toFixed(6)}°, ${lng.toFixed(6)}°</span>`).openPopup();
  mainMap.fitBounds(currentCircle.getBounds(),{padding:[24,24]});
  document.getElementById('mapTitle').textContent='Peta — '+geo.name;
  document.getElementById('mapCoord').textContent=lat.toFixed(4)+'°, '+lng.toFixed(4)+'°';
  document.getElementById('footerCenter').textContent='Pusat: '+lat.toFixed(6)+', '+lng.toFixed(6);
  document.getElementById('footerRadius').textContent='Radius: '+radius+'m';
  document.getElementById('footerZoneName').textContent=geo.name;
  renderZoneList(Object.values(geofencesData));
}

function closeMap() {
  selectedId=null;
  if(currentCircle){mainMap.removeLayer(currentCircle);currentCircle=null;}
  if(currentMarker){mainMap.removeLayer(currentMarker);currentMarker=null;}
  document.getElementById('mapPlaceholder').style.display='flex';
  document.getElementById('btnCloseMap').classList.replace('flex','hidden');
  // document.getElementById('mapLiveBadge').classList.replace('flex','hidden');
  document.getElementById('mapTitle').textContent='Peta Geofencing';
  document.getElementById('mapCoord').textContent='Pilih zona untuk melihat peta';
  document.getElementById('footerCenter').textContent='Pusat: —';
  document.getElementById('footerRadius').textContent='Radius: —';
  document.getElementById('footerZoneName').textContent='';
  renderZoneList(Object.values(geofencesData));
}

// ── Modal ──
function openAddModal() {
  editingId=null;
  document.getElementById('modalTitle').textContent='Tambah Zona Baru';
  ['geoName','geoLat','geoLng','geoRadius','geoDesc'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('geoStatus').value='active';
  switchTab('manual');
  document.getElementById('geoModal').classList.replace('hidden','flex');
}

async function editGeofence(id) {
  try {
    const res=await fetch(`/api/geofences/${id}`);
    if(!res.ok)throw new Error('HTTP '+res.status);
    const geo=await res.json();
    editingId=id;
    document.getElementById('modalTitle').textContent='Edit Zona';
    document.getElementById('geoName').value=geo.name;
    document.getElementById('geoLat').value=geo.latitude;
    document.getElementById('geoLng').value=geo.longitude;
    document.getElementById('geoRadius').value=geo.radius;
    document.getElementById('geoStatus').value=geo.status;
    document.getElementById('geoDesc').value=geo.description||'';
    switchTab('manual');
    document.getElementById('geoModal').classList.replace('hidden','flex');
  } catch(e){ motoToast('error','Gagal memuat data zona: '+e.message); }
}

function closeModal() {
  document.getElementById('geoModal').classList.replace('flex','hidden');
  editingId=null;
  if(modalMap){modalMap.remove();modalMap=null;modalPickMark=null;}
  resetMarkerPick();
  document.getElementById('hintBar').classList.replace('block','hidden');
}

async function submitGeofence() {
  const name=document.getElementById('geoName').value.trim();
  const lat=parseFloat(document.getElementById('geoLat').value);
  const lng=parseFloat(document.getElementById('geoLng').value);
  const radius=parseFloat(document.getElementById('geoRadius').value);
  const status=document.getElementById('geoStatus').value;
  const desc=document.getElementById('geoDesc').value;
  if(!name||isNaN(lat)||isNaN(lng)||isNaN(radius)||radius<1){motoToast('warning','Harap isi semua field yang wajib (Nama, Lat, Lng, Radius).');return;}
  try {
    const url=editingId?`/api/geofences/${editingId}`:'/api/geofences';
    const res=await fetch(url,{method:editingId?'PUT':'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({name,latitude:lat,longitude:lng,radius,status,description:desc})});
    if(!res.ok)throw new Error('HTTP '+res.status);
    closeModal();
    motoToast('success', editingId ? 'Zona berhasil diperbarui.' : 'Zona baru berhasil ditambahkan.');
    await loadGeofences();
  } catch(e){ motoToast('error','Gagal menyimpan zona: '+e.message); }
}

async function deleteGeofence(id) {
  const ok = await motoConfirm('Hapus Zona', 'Zona yang dihapus tidak dapat dikembalikan.');
  if(!ok) return;
  try {
    const res=await fetch(`/api/geofences/${id}`,{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF}});
    if(!res.ok)throw new Error('HTTP '+res.status);
    if(selectedId===id)closeMap();
    motoToast('success','Zona berhasil dihapus.');
    await loadGeofences();
  } catch(e){ motoToast('error','Gagal menghapus zona: '+e.message); }
}

// ── Tab switcher ──
function switchTab(tab) {
  const isManual=tab==='manual';
  const on='flex-1 py-1.5 md:py-2 text-[11px] md:text-[12px] font-bold rounded-lg transition-all cursor-pointer bg-white text-blue-600 shadow-sm';
  const off='flex-1 py-1.5 md:py-2 text-[11px] md:text-[12px] font-bold rounded-lg transition-all cursor-pointer text-slate-400 hover:text-slate-600';
  document.getElementById('tabManual').className=isManual?on:off;
  document.getElementById('tabMap').className=!isManual?on:off;
  document.getElementById('panelManual').classList.toggle('hidden',!isManual);
  document.getElementById('panelManual').classList.toggle('grid',isManual);
  document.getElementById('panelMap').classList.toggle('hidden',isManual);
  if(!isManual){document.getElementById('hintBar').classList.replace('hidden','block');initModalMap();}
  else{document.getElementById('hintBar').classList.replace('block','hidden');if(modalMap){modalMap.remove();modalMap=null;modalPickMark=null;}}
}

// ── Modal map ──
function initModalMap() {
  if(modalMap){setTimeout(()=>modalMap.invalidateSize(),50);return;}
  setTimeout(()=>{
    const sLat=parseFloat(document.getElementById('geoLat').value)||(-3.6527);
    const sLng=parseFloat(document.getElementById('geoLng').value)||128.1947;
    modalMap=L.map('modalMapPicker').setView([sLat,sLng],13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(modalMap);
    if(editingId){
      modalPickMark=L.circleMarker([sLat,sLng],{radius:8,fillColor:'#2563eb',color:'#fff',weight:3,fillOpacity:1}).addTo(modalMap).bindPopup(`${sLat.toFixed(6)}, ${sLng.toFixed(6)}`).openPopup();
      updatePickBox(sLat.toFixed(6),sLng.toFixed(6));
    }
    modalMap.on('click',function(e){
      const lat=parseFloat(e.latlng.lat.toFixed(6)),lng=parseFloat(e.latlng.lng.toFixed(6));
      document.getElementById('geoLat').value=lat; document.getElementById('geoLng').value=lng;
      if(modalPickMark)modalMap.removeLayer(modalPickMark);
      modalPickMark=L.circleMarker([lat,lng],{radius:8,fillColor:'#2563eb',color:'#fff',weight:3,fillOpacity:1}).addTo(modalMap).bindPopup(`${lat}°, ${lng}°`).openPopup();
      updatePickBox(lat,lng);
    });
  },120);
}

function updatePickBox(lat,lng) {
  const box=document.getElementById('markerPickBox'),txt=document.getElementById('markerPickText');
  if(box)box.className='flex items-center gap-2.5 bg-blue-50 border border-blue-200 rounded-xl px-3 md:px-4 py-2.5 text-[11px] md:text-[12px] text-blue-700 font-medium';
  if(txt)txt.textContent=`Titik dipilih: ${lat}°, ${lng}°`;
}

function resetMarkerPick() {
  const box=document.getElementById('markerPickBox'),txt=document.getElementById('markerPickText');
  if(box)box.className='flex items-center gap-2.5 bg-slate-50 border border-dashed border-slate-300 rounded-xl px-3 md:px-4 py-2.5 text-[11px] md:text-[12px] text-slate-400 font-medium';
  if(txt)txt.textContent='Klik titik pada peta untuk memilih lokasi';
}

// ── Init ──
loadGeofences();
// Init Lucide icons for static elements
document.addEventListener('DOMContentLoaded', () => { if(window.lucide) lucide.createIcons(); });