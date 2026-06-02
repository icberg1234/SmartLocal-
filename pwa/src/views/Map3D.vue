<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { getMapData, show3dMap } from '@mappedin/mappedin-js'
import '@mappedin/mappedin-js/lib/index.css'

// Professional indoor 3D wayfinding via the Mappedin Web SDK — the same engine
// Westfield malls use. Renders a real multi-floor 3D mall with search + routing.
const CREDS = {
  key: 'mik_yeBk0Vf0nNJtpesfu560e07e5',
  secret: 'mis_2g9ST8ZcSFb5R9fPnsvYhrX3RyRwPtDGbMGweCYKEq385431022',
  mapId: '65c0ff7430b94e3fabd5bb8c', // Mappedin demo mall (richest layout: food court + store row)
}

// Our brands (same keys as Home) — aliased onto the demo venue's real stores so the
// "tap a brand on Home → get a 3D route to it" flow works end-to-end. In production each
// mall authors its own store names in Mappedin Maker (stored per-mall in base data).
const BRANDS = [
  { key: 'hm', name: 'H&M' }, { key: 'zara', name: 'Zara' }, { key: 'nike', name: 'Nike' },
  { key: 'adidas', name: 'adidas' }, { key: 'apple', name: 'Apple Store' }, { key: 'samsung', name: 'Samsung' },
  { key: 'starbucks', name: 'Starbucks' }, { key: 'mcdonalds', name: "McDonald's" }, { key: 'xiaomi', name: 'Xiaomi' },
  { key: 'lego', name: 'LEGO' }, { key: 'sony', name: 'Sony' }, { key: 'lush', name: 'Lush' },
]

const route = useRoute()
const mapEl = ref(null)
const status = ref('در حال بارگذاریِ نقشهٔ سه‌بعدیِ حرفه‌ای…')
const query = ref('')
const dest = ref('')
const routeMsg = ref('')
const floors = ref([])
const currentFloorId = ref('')

let mapView = null, mapData = null, startSpace = null
const keyToSpace = {}
let namedSpaces = []

const results = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return []
  return namedSpaces
    .filter(s => ((s.__label || s.name || '').toLowerCase().includes(q)))
    .slice(0, 8)
})

function setFloor(id) {
  try { mapView.setFloor(id); currentFloorId.value = id } catch (e) { /* */ }
}

async function navigateTo(space) {
  if (!space || !mapView) return
  dest.value = space.__label || space.name || 'مقصد'
  query.value = ''
  // hop to the destination's floor first (multi-floor venues)
  try { if (space.floor?.id) setFloor(space.floor.id) } catch (e) { /* */ }
  try {
    const dirs = await mapData.getDirections(startSpace, space)
    try { mapView.Navigation.clear() } catch (e) { /* */ }
    if (dirs) {
      await mapView.Navigation.draw(dirs)
      const d = Math.round(dirs.distance || 0)
      routeMsg.value = d ? `مسیر کشیده شد · حدود ${d.toLocaleString('fa-IR')} متر از ورودی` : 'مسیر کشیده شد'
    } else {
      routeMsg.value = 'مسیری یافت نشد'
    }
  } catch (e) { routeMsg.value = '' }
  // frame the WHOLE route (you-are-here → destination), not just the store,
  // so the user sees the full path in 3D.
  try { await mapView.Camera.focusOn([startSpace, space], { pitch: 52, minZoomLevel: 14 }) }
  catch (e) { try { await mapView.Camera.focusOn(space) } catch (e2) { /* */ } }
}

function clearRoute() {
  dest.value = ''; routeMsg.value = ''
  try { mapView.Navigation.clear() } catch (e) { /* */ }
}

async function setup() {
  try {
    mapData = await getMapData(CREDS)
    mapView = await show3dMap(mapEl.value, mapData)

    const spaces = mapData.getByType('space')
    namedSpaces = spaces.filter(s => s.name)
    namedSpaces.forEach((s, i) => {
      const b = BRANDS[i]
      const label = b ? b.name : s.name
      s.__label = label
      if (b) keyToSpace[b.key] = s
      try { mapView.updateState(s, { interactive: true }) } catch (e) { /* */ }
      try { mapView.Labels.add(s, label) } catch (e) { /* */ }
    })

    // floor selector (multi-floor venues)
    try {
      const fl = mapData.getByType('floor') || []
      floors.value = fl.map(f => ({ id: f.id, name: f.name })).reverse()
      currentFloorId.value = mapView.currentFloor?.id || (fl[0] && fl[0].id) || ''
    } catch (e) { /* */ }

    // "you are here" — fixed entrance point
    startSpace = namedSpaces[namedSpaces.length - 1] || spaces[0]
    try {
      mapView.Markers.add(startSpace, '<div style="background:#16a34a;color:#fff;font:600 11px/1 Vazirmatn,sans-serif;padding:6px 10px;border-radius:999px;white-space:nowrap;box-shadow:0 4px 14px rgba(0,0,0,.35)">📍 شما اینجا</div>')
    } catch (e) { /* */ }

    // tap any store → route to it
    mapView.on('click', (e) => { const s = e?.spaces?.[0]; if (s) navigateTo(s) })

    status.value = ''

    // deep-link from Home: /map?store=hm
    const key = route.query.store
    if (key && keyToSpace[key]) setTimeout(() => navigateTo(keyToSpace[key]), 500)
  } catch (e) {
    status.value = 'خطا در بارگذاریِ نقشه: ' + (e?.message || String(e))
  }
}

onMounted(setup)
onBeforeUnmount(() => { try { mapView?.destroy?.() } catch (e) { /* */ } })
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
          <span class="dot"></span>{{ s.__label || s.name }}
          <small v-if="s.floor">· طبقه {{ s.floor.name }}</small>
        </li>
      </ul>
    </div>

    <!-- floor selector -->
    <div v-if="floors.length > 1" class="floors">
      <button
        v-for="f in floors" :key="f.id"
        :class="{ on: f.id === currentFloorId }"
        @click="setFloor(f.id)"
      >{{ f.name }}</button>
    </div>

    <!-- 3D canvas -->
    <div ref="mapEl" class="canvas-wrap"></div>

    <!-- bottom status / route card -->
    <div class="card">
      <p v-if="status" class="muted">{{ status }}</p>
      <template v-else-if="dest">
        <div class="route-row">
          <div>
            <div class="to">🧭 مقصد: <b>{{ dest }}</b></div>
            <div class="rm muted">{{ routeMsg || 'در حال محاسبهٔ مسیر…' }}</div>
          </div>
          <button class="clear" @click="clearRoute">پاک‌کردن</button>
        </div>
      </template>
      <p v-else class="muted">فروشگاهی را جستجو کن یا روی نقشه لمس کن تا مسیر از «شما اینجا» کشیده شود.</p>
    </div>
  </section>
</template>

<style scoped>
.map { position: fixed; top: 56px; inset-inline: 0; bottom: 0; overflow: hidden; }
.canvas-wrap { position: absolute; inset: 0; background: #eef0f5; }

.search { position: absolute; top: 12px; inset-inline: 12px; z-index: 6; max-width: 520px; margin: 0 auto; }
.search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 14px; padding: 10px 14px; box-shadow: 0 8px 24px rgba(20,24,60,.14); }
.search-box .si { font-size: 15px; opacity: .7; }
.search-box input { flex: 1; border: 0; outline: 0; font: inherit; font-size: 15px; background: transparent; color: #14163c; }
.search-box .x { border: 0; background: #eef0f5; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; color: #555; }
.results { list-style: none; margin: 8px 0 0; padding: 6px; background: #fff; border-radius: 14px; box-shadow: 0 12px 30px rgba(20,24,60,.18); max-height: 46vh; overflow: auto; }
.results li { display: flex; align-items: center; gap: 9px; padding: 11px 12px; border-radius: 10px; cursor: pointer; font-size: 14.5px; color: #14163c; }
.results li:hover { background: #f1f3fb; }
.results li .dot { width: 8px; height: 8px; border-radius: 50%; background: #6366f1; flex: none; }
.results li small { color: #8a8fb0; margin-inline-start: auto; font-size: 12px; }

.floors { position: absolute; top: 50%; transform: translateY(-50%); inset-inline-end: 12px; z-index: 6; display: flex; flex-direction: column; gap: 6px; background: #fff; padding: 6px; border-radius: 14px; box-shadow: 0 8px 24px rgba(20,24,60,.16); }
.floors button { width: 40px; height: 40px; border: 0; background: transparent; border-radius: 10px; font: 600 14px Vazirmatn, sans-serif; color: #555; cursor: pointer; }
.floors button.on { background: #6366f1; color: #fff; }

.card { position: absolute; bottom: 14px; inset-inline: 12px; z-index: 6; max-width: 520px; margin: 0 auto; background: rgba(255,255,255,.96); backdrop-filter: blur(8px); border: 1px solid rgba(0,0,0,.06); border-radius: 16px; padding: 13px 16px; box-shadow: 0 12px 30px rgba(20,24,60,.16); }
.card .muted { color: #6b7090; margin: 0; font-size: 13px; }
.route-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.to { font-size: 15px; color: #14163c; } .to b { color: #4f46e5; }
.rm { margin-top: 3px; }
.clear { border: 0; background: #eef0f5; color: #14163c; border-radius: 10px; padding: 9px 14px; font: 600 13px Vazirmatn, sans-serif; cursor: pointer; flex: none; }
</style>
