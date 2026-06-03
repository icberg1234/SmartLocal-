<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { getMapData, show3dMap } from '@mappedin/mappedin-js'
import '@mappedin/mappedin-js/lib/index.css'

// Premium indoor mall navigation on the Mappedin Web SDK (the engine real malls
// like Westfield use): real 3D mall + search + first-person wayfinding.
const CREDS = {
  key: 'mik_yeBk0Vf0nNJtpesfu560e07e5',
  secret: 'mis_2g9ST8ZcSFb5R9fPnsvYhrX3RyRwPtDGbMGweCYKEq385431022',
  mapId: '65c0ff7430b94e3fabd5bb8c',
}

// Our brands (same keys as Home) aliased onto the venue's stores + brand colors,
// so the storefronts read as real brands and "tap brand on Home → 3D route" works.
const BRANDS = [
  { key: 'hm', name: 'H&M', color: '#e50010' }, { key: 'zara', name: 'Zara', color: '#1b1b1b' },
  { key: 'nike', name: 'Nike', color: '#111111' }, { key: 'adidas', name: 'adidas', color: '#0b0b0b' },
  { key: 'apple', name: 'Apple Store', color: '#8e9296' }, { key: 'samsung', name: 'Samsung', color: '#1428a0' },
  { key: 'starbucks', name: 'Starbucks', color: '#00704a' }, { key: 'mcdonalds', name: "McDonald's", color: '#d9a300' },
  { key: 'xiaomi', name: 'Xiaomi', color: '#ff6900' }, { key: 'lego', name: 'LEGO', color: '#d4122a' },
  { key: 'sony', name: 'Sony', color: '#0a0a0a' }, { key: 'lush', name: 'Lush', color: '#1b5e20' },
]

const route = useRoute()
const mapEl = ref(null)
const status = ref('در حال بارگذاریِ تجربهٔ سه‌بعدی…')
const query = ref('')
const dest = ref('')
const distM = ref(0)
const floors = ref([])
const currentFloorId = ref('')
const mode = ref('overview')   // 'overview' | 'first'
const navigating = ref(false)

let mapView = null, mapData = null, startSpace = null, destSpace = null
let pulseTimer = null
const keyToSpace = {}
let namedSpaces = []

const etaMin = computed(() => Math.max(1, Math.round((distM.value / 1.33) / 60)))
const fa = (n) => Number(n || 0).toLocaleString('fa-IR')
const results = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return []
  return namedSpaces.filter(s => (s.__label || s.name || '').toLowerCase().includes(q)).slice(0, 8)
})

function bearingDeg(a, b) {
  try {
    const toR = d => d * Math.PI / 180, toD = r => r * 180 / Math.PI
    const dLon = toR(b.longitude - a.longitude)
    const y = Math.sin(dLon) * Math.cos(toR(b.latitude))
    const x = Math.cos(toR(a.latitude)) * Math.sin(toR(b.latitude)) -
      Math.sin(toR(a.latitude)) * Math.cos(toR(b.latitude)) * Math.cos(dLon)
    return (toD(Math.atan2(y, x)) + 360) % 360
  } catch (e) { return 0 }
}

function setFloor(id) { try { mapView.setFloor(id); currentFloorId.value = id } catch (e) { /* */ } }

function stopPulse() {
  if (pulseTimer) { clearInterval(pulseTimer); pulseTimer = null }
  if (destSpace) { try { mapView.updateState(destSpace, { color: destSpace.__brandColor || undefined }) } catch (e) { /* */ } }
}
function pulse(space) {
  stopPulse()
  destSpace = space
  let on = false
  pulseTimer = setInterval(() => {
    on = !on
    try { mapView.updateState(space, { color: on ? '#22d3ee' : '#f59e0b' }) } catch (e) { /* */ }
  }, 650)
}

async function enterFirstPerson() {
  if (!mapView || !startSpace || !destSpace) return
  mode.value = 'first'
  try {
    // Mappedin clamps pitch to ~69°, so this is the most immersive ("eye-level-ish") view it allows.
    await mapView.Camera.animateTo(
      { center: startSpace.center, zoomLevel: 21.5, pitch: 68, bearing: bearingDeg(startSpace.center, destSpace.center) },
      { duration: 1900 },
    )
  } catch (e) { /* */ }
}
async function enterOverview() {
  if (!mapView) return
  mode.value = 'overview'
  try {
    const t = destSpace ? [startSpace, destSpace] : namedSpaces
    await mapView.Camera.focusOn(t, { pitch: 48, duration: 1300 })
  } catch (e) { /* */ }
}

async function navigateTo(space, { fly = true } = {}) {
  if (!space || !mapView) return
  dest.value = space.__label || space.name || 'مقصد'
  query.value = ''
  navigating.value = true
  try { if (space.floor?.id) setFloor(space.floor.id) } catch (e) { /* */ }
  try {
    const dirs = await mapData.getDirections(startSpace, space)
    try { mapView.Navigation.clear() } catch (e) { /* */ }
    if (dirs) {
      await mapView.Navigation.draw(dirs, {
        pathOptions: {
          color: '#22d3ee', accentColor: '#ffffff', width: '13px',
          displayArrowsOnPath: true, animateArrowsOnPath: true,
          showPulse: true, pulseIterations: 9999, drawDuration: 1300,
        },
      })
      distM.value = Math.round(dirs.distance || 0)
    } else { distM.value = 0 }
  } catch (e) { distM.value = 0 }
  pulse(space)
  // glide straight into the immersive first-person walk-up (overview stays on the toggle)
  if (fly) await enterFirstPerson()
  else await enterOverview()
}

function clearRoute() {
  dest.value = ''; distM.value = 0; navigating.value = false
  stopPulse(); destSpace = null
  try { mapView.Navigation.clear() } catch (e) { /* */ }
  enterOverview()
}

async function setup() {
  try {
    mapData = await getMapData(CREDS)
    mapView = await show3dMap(mapEl.value, mapData)

    const spaces = mapData.getByType('space')
    namedSpaces = spaces.filter(s => s.name)
    namedSpaces.forEach((s, i) => {
      const b = BRANDS[i]
      s.__label = b ? b.name : s.name
      if (b) {
        s.__brandColor = b.color
        keyToSpace[b.key] = s
        try { mapView.updateState(s, { color: b.color }) } catch (e) { /* */ }
      }
      try { mapView.updateState(s, { interactive: true }) } catch (e) { /* */ }
      try { mapView.Labels.add(s, s.__label) } catch (e) { /* */ }
    })

    try {
      const fl = mapData.getByType('floor') || []
      floors.value = fl.map(f => ({ id: f.id, name: f.name })).reverse()
      currentFloorId.value = mapView.currentFloor?.id || (fl[0] && fl[0].id) || ''
    } catch (e) { /* */ }

    startSpace = namedSpaces[namedSpaces.length - 1] || spaces[0]
    try {
      mapView.Markers.add(startSpace, '<div class="yah">📍 شما اینجا</div>')
    } catch (e) { /* */ }

    mapView.on('click', (e) => { const s = e?.spaces?.[0]; if (s) navigateTo(s) })

    status.value = ''

    const key = route.query.store
    if (key && keyToSpace[key]) setTimeout(() => navigateTo(keyToSpace[key]), 500)
  } catch (e) {
    status.value = 'خطا در بارگذاریِ نقشه: ' + (e?.message || String(e))
  }
}

onMounted(setup)
onBeforeUnmount(() => { stopPulse(); try { mapView?.destroy?.() } catch (e) { /* */ } })
</script>

<template>
  <section class="map">
    <!-- search -->
    <div class="search">
      <div class="search-box">
        <span class="si">🔍</span>
        <input v-model="query" type="search" placeholder="جستجوی فروشگاه… (مثلاً Nike)" />
        <button v-if="query" class="x" @click="query = ''">✕</button>
      </div>
      <ul v-if="results.length" class="results">
        <li v-for="s in results" :key="s.id" @click="navigateTo(s)">
          <span class="dot" :style="{ background: s.__brandColor || '#6366f1' }"></span>{{ s.__label || s.name }}
          <small v-if="s.floor">· طبقه {{ s.floor.name }}</small>
        </li>
      </ul>
    </div>

    <!-- floor selector -->
    <div v-if="floors.length > 1" class="floors">
      <button v-for="f in floors" :key="f.id" :class="{ on: f.id === currentFloorId }" @click="setFloor(f.id)">{{ f.name }}</button>
    </div>

    <!-- 3D canvas -->
    <div ref="mapEl" class="canvas-wrap"></div>

    <!-- loading -->
    <div v-if="status" class="loading"><span class="spin"></span>{{ status }}</div>

    <!-- premium route card -->
    <transition name="rise">
      <div v-if="!status && navigating" class="route-card">
        <div class="rc-head">
          <div class="rc-dest">
            <span class="pin" :style="{ background: destSpace?.__brandColor || '#4f46e5' }"></span>
            <div>
              <div class="rc-name">{{ dest }}</div>
              <div class="rc-sub">از موقعیتِ شما</div>
            </div>
          </div>
          <button class="rc-close" @click="clearRoute">✕</button>
        </div>
        <div class="rc-metrics">
          <div class="m"><span class="mv">{{ fa(etaMin) }}<small> دقیقه</small></span><span class="ml">زمانِ تقریبی</span></div>
          <div class="sep"></div>
          <div class="m"><span class="mv">{{ fa(distM) }}<small> متر</small></span><span class="ml">فاصله</span></div>
          <div class="sep"></div>
          <div class="m"><span class="mv">🚶</span><span class="ml">پیاده</span></div>
        </div>
        <div class="rc-actions">
          <div class="seg">
            <button :class="{ on: mode === 'overview' }" @click="enterOverview">نمای کلی</button>
            <button :class="{ on: mode === 'first' }" @click="enterFirstPerson">اول‌شخص</button>
          </div>
          <button class="start" @click="enterFirstPerson">▶ شروع مسیریابی</button>
        </div>
      </div>
    </transition>

    <!-- idle hint -->
    <div v-if="!status && !navigating" class="hint">
      🧭 فروشگاهی را جستجو کن یا روی نقشه لمس کن تا مسیرِ سه‌بعدی از «شما اینجا» کشیده شود.
    </div>
  </section>
</template>

<style scoped>
.map { position: fixed; top: 56px; inset-inline: 0; bottom: 0; overflow: hidden; }
.canvas-wrap { position: absolute; inset: 0; background: #eef0f5; }

/* search */
.search { position: absolute; top: 12px; inset-inline: 12px; z-index: 6; max-width: 520px; margin: 0 auto; }
.search-box { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,.92); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.6); border-radius: 16px; padding: 11px 15px; box-shadow: 0 10px 30px rgba(20,24,60,.16); }
.search-box .si { font-size: 15px; opacity: .65; }
.search-box input { flex: 1; border: 0; outline: 0; font: inherit; font-size: 15px; background: transparent; color: #14163c; }
.search-box .x { border: 0; background: #eef0f5; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; color: #555; }
.results { list-style: none; margin: 8px 0 0; padding: 6px; background: rgba(255,255,255,.97); backdrop-filter: blur(12px); border-radius: 16px; box-shadow: 0 16px 40px rgba(20,24,60,.2); max-height: 44vh; overflow: auto; }
.results li { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border-radius: 11px; cursor: pointer; font-size: 14.5px; color: #14163c; }
.results li:active, .results li:hover { background: #f1f3fb; }
.results li .dot { width: 9px; height: 9px; border-radius: 50%; flex: none; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
.results li small { color: #8a8fb0; margin-inline-start: auto; font-size: 12px; }

/* floor selector */
.floors { position: absolute; top: 46%; transform: translateY(-50%); inset-inline-end: 12px; z-index: 6; display: flex; flex-direction: column; gap: 6px; background: rgba(255,255,255,.92); backdrop-filter: blur(10px); padding: 6px; border-radius: 16px; box-shadow: 0 10px 26px rgba(20,24,60,.18); }
.floors button { width: 42px; height: 42px; border: 0; background: transparent; border-radius: 12px; font: 700 14px Vazirmatn, sans-serif; color: #555; cursor: pointer; transition: .2s; }
.floors button.on { background: #4f46e5; color: #fff; box-shadow: 0 6px 16px rgba(79,70,229,.4); }

/* loading */
.loading { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; gap: 12px; color: #4f46e5; font-size: 14px; background: #eef0f5; z-index: 4; }
.spin { width: 22px; height: 22px; border: 3px solid #c7cbe6; border-top-color: #4f46e5; border-radius: 50%; animation: sp 1s linear infinite; }
@keyframes sp { to { transform: rotate(360deg); } }

/* idle hint */
.hint { position: absolute; bottom: 84px; inset-inline: 12px; z-index: 6; max-width: 520px; margin: 0 auto; background: rgba(255,255,255,.92); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.6); border-radius: 16px; padding: 13px 16px; color: #5b6080; font-size: 13px; box-shadow: 0 10px 30px rgba(20,24,60,.14); text-align: center; }

/* premium glassmorphism route card */
.route-card { position: absolute; bottom: 84px; inset-inline: 12px; z-index: 7; max-width: 520px; margin: 0 auto; background: rgba(17,19,46,.78); backdrop-filter: blur(18px) saturate(1.3); border: 1px solid rgba(255,255,255,.14); border-radius: 22px; padding: 16px 18px; box-shadow: 0 20px 50px rgba(8,10,30,.45); color: #fff; }
.rc-head { display: flex; align-items: center; justify-content: space-between; }
.rc-dest { display: flex; align-items: center; gap: 12px; }
.rc-dest .pin { width: 12px; height: 12px; border-radius: 50%; box-shadow: 0 0 0 4px rgba(255,255,255,.12), 0 0 14px 2px currentColor; }
.rc-name { font-size: 18px; font-weight: 800; letter-spacing: -.2px; }
.rc-sub { font-size: 11.5px; color: rgba(255,255,255,.55); margin-top: 1px; }
.rc-close { border: 0; background: rgba(255,255,255,.12); color: #fff; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 13px; }
.rc-metrics { display: flex; align-items: center; gap: 10px; margin: 14px 0; }
.rc-metrics .m { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 3px; }
.rc-metrics .mv { font-size: 21px; font-weight: 800; color: #a5b4fc; line-height: 1; }
.rc-metrics .mv small { font-size: 11px; font-weight: 600; color: rgba(255,255,255,.6); }
.rc-metrics .ml { font-size: 11px; color: rgba(255,255,255,.55); }
.rc-metrics .sep { width: 1px; height: 30px; background: rgba(255,255,255,.14); }
.rc-actions { display: flex; align-items: center; gap: 10px; }
.seg { display: flex; background: rgba(255,255,255,.1); border-radius: 12px; padding: 3px; }
.seg button { border: 0; background: transparent; color: rgba(255,255,255,.7); font: 700 12.5px Vazirmatn, sans-serif; padding: 8px 12px; border-radius: 10px; cursor: pointer; transition: .2s; }
.seg button.on { background: #fff; color: #14163c; }
.start { flex: 1; border: 0; background: linear-gradient(135deg, #6366f1, #22d3ee); color: #fff; font: 800 14px Vazirmatn, sans-serif; padding: 12px; border-radius: 12px; cursor: pointer; box-shadow: 0 10px 24px rgba(34,211,238,.35); }
.start:active { transform: translateY(1px); }

.rise-enter-active, .rise-leave-active { transition: all .35s cubic-bezier(.2,.8,.2,1); }
.rise-enter-from, .rise-leave-to { opacity: 0; transform: translateY(20px); }

/* "you are here" marker (injected into SDK DOM — global) */
:global(.yah) { background: #16a34a; color: #fff; font: 700 11px/1 Vazirmatn, sans-serif; padding: 7px 11px; border-radius: 999px; white-space: nowrap; box-shadow: 0 4px 16px rgba(0,0,0,.4); }
</style>
