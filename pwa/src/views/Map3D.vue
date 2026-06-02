<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import * as THREE from 'three'
import { OrbitControls } from 'three/addons/controls/OrbitControls.js'

const PALETTE = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#64748b']
const BRANDS = [
  { id: 'nike', name: 'Nike', slug: 'nike', color: '#111111' },
  { id: 'apple', name: 'Apple', slug: 'apple', color: '#444444' },
  { id: 'samsung', name: 'Samsung', slug: 'samsung', color: '#1428A0' },
  { id: 'zara', name: 'Zara', slug: 'zara', color: '#1a1a1a' },
  { id: 'adidas', name: 'Adidas', slug: 'adidas', color: '#0b0b0b' },
  { id: 'starbucks', name: 'Starbucks', slug: 'starbucks', color: '#00704A' },
  { id: 'mcdonalds', name: "McDonald's", slug: 'mcdonalds', color: '#D62300' },
  { id: 'xiaomi', name: 'Xiaomi', slug: 'xiaomi', color: '#FF6900' },
]
const ENTRANCE = { x: -12, z: 0 }
const UNIT_M = 2
const WALK = 1.3

const canvasWrap = ref(null)
const stores = ref([])
const dest = ref(null)
const status = ref('')

let renderer, scene, camera, controls, raf
let storeGroup, routeGroup, walker, walkerT = 0, walkerCurve = null

function assignLayout(list) {
  list.forEach((s, i) => {
    const slot = i % 5
    s.x = -8 + slot * 4
    s.z = i < 5 ? -5 : 5
  })
  return list
}

function roundRect(ctx, x, y, w, h, r) {
  ctx.beginPath(); ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r)
  ctx.arcTo(x + w, y + h, x, y + h, r); ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath()
}

function logoTexture(store) {
  const c = document.createElement('canvas'); c.width = 256; c.height = 128
  const ctx = c.getContext('2d')
  const tex = new THREE.CanvasTexture(c)
  const draw = (img) => {
    ctx.clearRect(0, 0, 256, 128); ctx.fillStyle = '#ffffff'; roundRect(ctx, 4, 4, 248, 120, 16); ctx.fill()
    ctx.fillStyle = store.color; ctx.textAlign = 'center'
    if (img) {
      ctx.drawImage(img, 89, 14, 78, 78)
      ctx.font = 'bold 22px Vazirmatn, sans-serif'; ctx.fillText(store.name, 128, 116)
    } else {
      ctx.font = `bold ${store.name.length > 10 ? 22 : 30}px Vazirmatn, sans-serif`
      ctx.textBaseline = 'middle'; ctx.fillText(store.name.slice(0, 16), 128, 64)
    }
    tex.needsUpdate = true
  }
  draw(null)
  if (store.slug) {
    const img = new Image(); img.crossOrigin = 'anonymous'
    img.onload = () => draw(img)
    img.src = `https://cdn.simpleicons.org/${store.slug}/${store.color.replace('#', '')}`
  }
  return tex
}

function buildStores(list) {
  if (routeGroup) { scene.remove(routeGroup); routeGroup = null; dest.value = null; walkerCurve = null }
  if (storeGroup) { scene.remove(storeGroup); storeGroup.traverse(o => { o.geometry?.dispose?.(); o.material?.map?.dispose?.(); o.material?.dispose?.() }) }
  storeGroup = new THREE.Group()
  for (const s of list) {
    const box = new THREE.Mesh(
      new THREE.BoxGeometry(3.4, 2.6, 3.4),
      new THREE.MeshStandardMaterial({ color: new THREE.Color(s.color).lerp(new THREE.Color(0xffffff), 0.12), roughness: 0.6, metalness: 0.1 }),
    )
    box.position.set(s.x, 1.3, s.z); box.castShadow = true; box.receiveShadow = true
    storeGroup.add(box)
    const sign = new THREE.Mesh(new THREE.PlaneGeometry(3, 1.5), new THREE.MeshBasicMaterial({ map: logoTexture(s), transparent: true }))
    sign.position.set(s.x, 1.7, s.z < 0 ? s.z + 1.71 : s.z - 1.71)
    if (s.z > 0) sign.rotation.y = Math.PI
    storeGroup.add(sign)
  }
  scene.add(storeGroup)
  stores.value = list
}

function navigateTo(store) {
  if (routeGroup) { scene.remove(routeGroup); routeGroup.traverse(o => { o.geometry?.dispose?.(); o.material?.dispose?.() }) }
  routeGroup = new THREE.Group()
  const frontZ = store.z + (store.z < 0 ? 1.7 : -1.7)
  const pts = [
    new THREE.Vector3(ENTRANCE.x, 0.18, 0),
    new THREE.Vector3(store.x, 0.18, 0),
    new THREE.Vector3(store.x, 0.18, frontZ),
  ]
  walkerCurve = new THREE.CatmullRomCurve3(pts, false, 'catmullrom', 0)
  routeGroup.add(new THREE.Mesh(
    new THREE.TubeGeometry(walkerCurve, 80, 0.22, 10, false),
    new THREE.MeshStandardMaterial({ color: 0x7c3aed, emissive: 0x4f46e5, emissiveIntensity: 0.9, transparent: true, opacity: 0.9 }),
  ))
  walker = new THREE.Mesh(new THREE.SphereGeometry(0.45, 24, 24), new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0x7c3aed, emissiveIntensity: 1.2 }))
  routeGroup.add(walker); walkerT = 0; scene.add(routeGroup)
  let meters = 0
  for (let i = 1; i < pts.length; i++) meters += pts[i].distanceTo(pts[i - 1]) * UNIT_M
  dest.value = { name: store.name, meters: Math.round(meters), secs: Math.round(meters / WALK) }
}

async function loadReal() {
  status.value = 'در حال گرفتن فروشگاه‌های واقعی از OpenStreetMap…'
  try {
    const q = '[out:json][timeout:25];(node["shop"]["name"](around:320,51.5152,-0.1419););out 80;'
    const res = await fetch('https://overpass-api.de/api/interpreter', { method: 'POST', body: q })
    const json = await res.json()
    const names = [...new Set(json.elements.map(e => e.tags && e.tags.name).filter(Boolean))].slice(0, 10)
    if (!names.length) { status.value = 'فروشگاهی پیدا نشد؛ دوباره امتحان کن.'; return }
    const list = assignLayout(names.map((n, i) => ({
      id: 'osm' + i, name: n, color: PALETTE[i % PALETTE.length],
      slug: n.toLowerCase().replace(/[^a-z0-9]/g, ''),
    })))
    buildStores(list)
    status.value = `✅ ${names.length} فروشگاهِ واقعی از خیابانِ آکسفوردِ لندن (OpenStreetMap) بارگذاری شد.`
  } catch (e) {
    status.value = 'خطا در اتصال به OpenStreetMap.'
  }
}

function loadBrands() { buildStores(assignLayout(BRANDS.map(b => ({ ...b })))); status.value = '' }

onMounted(() => {
  const wrap = canvasWrap.value
  const w = wrap.clientWidth, h = wrap.clientHeight
  scene = new THREE.Scene(); scene.background = new THREE.Color(0x0f1226); scene.fog = new THREE.Fog(0x0f1226, 35, 70)
  camera = new THREE.PerspectiveCamera(48, w / h, 0.1, 200); camera.position.set(-2, 20, 24)
  renderer = new THREE.WebGLRenderer({ antialias: true })
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); renderer.setSize(w, h)
  renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap
  wrap.appendChild(renderer.domElement)
  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true; controls.target.set(0, 0, 0)
  controls.maxPolarAngle = Math.PI / 2.2; controls.minDistance = 12; controls.maxDistance = 45
  controls.autoRotate = true; controls.autoRotateSpeed = 0.6
  scene.add(new THREE.AmbientLight(0xffffff, 0.75))
  const dir = new THREE.DirectionalLight(0xffffff, 1.1); dir.position.set(12, 26, 10); dir.castShadow = true
  dir.shadow.mapSize.set(1024, 1024); dir.shadow.camera.left = -20; dir.shadow.camera.right = 20; dir.shadow.camera.top = 20; dir.shadow.camera.bottom = -20
  scene.add(dir)
  const floor = new THREE.Mesh(new THREE.PlaneGeometry(30, 20), new THREE.MeshStandardMaterial({ color: 0x171a33, roughness: 0.95 }))
  floor.rotation.x = -Math.PI / 2; floor.receiveShadow = true; scene.add(floor)
  const corridor = new THREE.Mesh(new THREE.PlaneGeometry(26, 3.4), new THREE.MeshStandardMaterial({ color: 0x23264a, roughness: 1 }))
  corridor.rotation.x = -Math.PI / 2; corridor.position.y = 0.02; scene.add(corridor)
  const pin = new THREE.Mesh(new THREE.ConeGeometry(0.6, 1.6, 24), new THREE.MeshStandardMaterial({ color: 0x22d3ee, emissive: 0x06b6d4, emissiveIntensity: 0.8 }))
  pin.position.set(ENTRANCE.x, 1.1, ENTRANCE.z); pin.rotation.x = Math.PI; scene.add(pin)
  const ring = new THREE.Mesh(new THREE.RingGeometry(0.8, 1.1, 32), new THREE.MeshBasicMaterial({ color: 0x22d3ee, transparent: true, opacity: 0.8, side: THREE.DoubleSide }))
  ring.rotation.x = -Math.PI / 2; ring.position.set(ENTRANCE.x, 0.05, ENTRANCE.z); scene.add(ring)

  buildStores(assignLayout(BRANDS.map(b => ({ ...b }))))

  const clock = new THREE.Clock()
  const loop = () => {
    raf = requestAnimationFrame(loop)
    const t = clock.getElapsedTime(); const sc = 1 + Math.sin(t * 3) * 0.25
    ring.scale.set(sc, sc, sc); ring.material.opacity = 0.8 - (sc - 1)
    if (walker && walkerCurve) { walkerT = (walkerT + 0.004) % 1; walker.position.copy(walkerCurve.getPointAt(walkerT)) }
    controls.update(); renderer.render(scene, camera)
  }
  loop()
  window.addEventListener('resize', onResize)
})

function onResize() {
  if (!renderer || !canvasWrap.value) return
  const w = canvasWrap.value.clientWidth, h = canvasWrap.value.clientHeight
  camera.aspect = w / h; camera.updateProjectionMatrix(); renderer.setSize(w, h)
}

onBeforeUnmount(() => {
  cancelAnimationFrame(raf); window.removeEventListener('resize', onResize)
  controls?.dispose?.(); renderer?.dispose?.()
  if (renderer?.domElement && canvasWrap.value?.contains(renderer.domElement)) canvasWrap.value.removeChild(renderer.domElement)
})

const faNum = (n) => Number(n).toLocaleString('fa-IR')
</script>

<template>
  <section class="map">
    <div class="hud">
      <div class="hud-top">
        <h2>نقشهٔ سه‌بعدیِ پاساژ</h2>
        <div class="src">
          <button :class="{ on: !status }" @click="loadBrands">نمونه</button>
          <button @click="loadReal">🌍 واقعی</button>
        </div>
      </div>
      <div v-if="dest" class="route-info">🧭 مقصد: <b>{{ dest.name }}</b> · {{ faNum(dest.meters) }} متر · ~{{ faNum(dest.secs) }} ثانیه پیاده</div>
      <p v-else class="muted">{{ status || 'یک فروشگاه را انتخاب کن تا مسیر را برایت بکشم.' }}</p>
    </div>

    <div ref="canvasWrap" class="canvas-wrap"></div>

    <div class="picker">
      <button v-for="s in stores" :key="s.id" class="chip" @click="navigateTo(s)">
        <img v-if="s.slug" :src="`https://cdn.simpleicons.org/${s.slug}`" :alt="s.name" @error="(e) => (e.target.style.display = 'none')" />
        <span>{{ s.name }}</span>
      </button>
    </div>
  </section>
</template>

<style scoped>
.map { position: relative; margin: -22px -16px -104px; height: calc(100vh - 56px); }
.canvas-wrap { position: absolute; inset: 0; }
.hud { position: absolute; top: 14px; inset-inline: 14px; z-index: 5; background: rgba(15,18,38,.6); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 12px 16px; color: #fff; }
.hud-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.hud h2 { color: #fff; font-size: 17px; margin: 0; }
.src { display: flex; gap: 6px; }
.src button { font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); color: #fff; padding: 6px 11px; border-radius: 999px; }
.src button.on { background: #fff; color: #4f46e5; }
.hud .muted { color: rgba(255,255,255,.72); margin: 6px 0 0; font-size: 12.5px; }
.route-info { font-size: 14px; margin-top: 6px; }
.route-info b { color: #a5b4fc; }
.picker { position: absolute; bottom: 16px; inset-inline: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 0 14px 4px; }
.chip { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; min-width: 74px; max-width: 110px; height: 64px; padding: 8px 10px; border: 1px solid rgba(255,255,255,.14); border-radius: 14px; background: rgba(255,255,255,.94); cursor: pointer; font: inherit; font-size: 11px; font-weight: 700; color: #1b1f2a; }
.chip img { width: 24px; height: 24px; object-fit: contain; }
.chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 92px; }
.chip:active { transform: translateY(1px); }
</style>
