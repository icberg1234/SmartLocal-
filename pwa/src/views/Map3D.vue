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
  { id: 'hm', name: 'H&M', slug: 'handm', color: '#E50010' },
  { id: 'lego', name: 'LEGO', slug: 'lego', color: '#E3000B' },
]
// building dims
const FLOORS = 5, FH = 4.4
const OW = 20, OD = 12          // outer half-extents
const IW = 10, ID = 5           // atrium half-extents
const UNIT_M = 1.6, WALK = 1.3, FLOOR_SECS = 16

const canvasWrap = ref(null)
const stores = ref([])
const dest = ref(null)
const status = ref('')

let renderer, scene, camera, controls, raf
let mallGroup, routeGroup, walker, walkerT = 0, walkerCurve = null

function layout(list) {
  list.forEach((s, i) => {
    s.floor = i % FLOORS
    const pass = Math.floor(i / FLOORS)
    s.face = pass % 2 === 0 ? 1 : -1            // front (atrium -Z side) / back (+Z side)
    s.z = s.face > 0 ? -(OD - 2.4) : (OD - 2.4)
    s.x = -10 + Math.floor(pass / 2) * 10       // spread along the strip
    s.y = s.floor * FH
  })
  return list
}

function roundRect(ctx, x, y, w, h, r) {
  ctx.beginPath(); ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r)
  ctx.arcTo(x + w, y + h, x, y + h, r); ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath()
}
function logoTexture(store) {
  const c = document.createElement('canvas'); c.width = 256; c.height = 128
  const ctx = c.getContext('2d'); const tex = new THREE.CanvasTexture(c)
  const draw = (img) => {
    ctx.clearRect(0, 0, 256, 128); ctx.fillStyle = '#ffffff'; roundRect(ctx, 4, 4, 248, 120, 18); ctx.fill()
    ctx.fillStyle = store.color; ctx.textAlign = 'center'
    if (img) { ctx.drawImage(img, 92, 12, 72, 72); ctx.font = 'bold 22px Vazirmatn, sans-serif'; ctx.fillText(store.name, 128, 112) }
    else { ctx.font = `bold ${store.name.length > 10 ? 22 : 30}px Vazirmatn, sans-serif`; ctx.textBaseline = 'middle'; ctx.fillText(store.name.slice(0, 16), 128, 64) }
    tex.needsUpdate = true
  }
  draw(null)
  if (store.slug) { const img = new Image(); img.crossOrigin = 'anonymous'; img.onload = () => draw(img); img.src = `https://cdn.simpleicons.org/${store.slug}/${store.color.replace('#', '')}` }
  return tex
}

function addBox(g, w, h, d, x, y, z, mat) {
  const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat)
  m.position.set(x, y, z); m.castShadow = true; m.receiveShadow = true; g.add(m); return m
}

function buildMall(list) {
  if (routeGroup) { scene.remove(routeGroup); routeGroup = null; dest.value = null; walkerCurve = null }
  if (mallGroup) { scene.remove(mallGroup); mallGroup.traverse(o => { o.geometry?.dispose?.(); o.material?.map?.dispose?.(); o.material?.dispose?.() }) }
  mallGroup = new THREE.Group()

  const floorMat = new THREE.MeshStandardMaterial({ color: 0xe9ecf5, roughness: 0.85 })
  const trimMat = new THREE.MeshStandardMaterial({ color: 0x3b3f63, roughness: 0.6 })
  const glassMat = new THREE.MeshStandardMaterial({ color: 0x8fd3ff, transparent: true, opacity: 0.22, roughness: 0.1, metalness: 0.1 })
  const escMat = new THREE.MeshStandardMaterial({ color: 0x9aa3c7, roughness: 0.5, metalness: 0.3 })

  for (let f = 0; f < FLOORS; f++) {
    const y = f * FH
    // 4 walkway strips (ring around atrium)
    addBox(mallGroup, 2 * OW, 0.3, OD - ID, 0, y, -(ID + OD) / 2, floorMat)
    addBox(mallGroup, 2 * OW, 0.3, OD - ID, 0, y, (ID + OD) / 2, floorMat)
    addBox(mallGroup, OW - IW, 0.3, 2 * ID, -(IW + OW) / 2, y, 0, floorMat)
    addBox(mallGroup, OW - IW, 0.3, 2 * ID, (IW + OW) / 2, y, 0, floorMat)
    // glass railings around the atrium void
    addBox(mallGroup, 2 * IW, 1.1, 0.12, 0, y + 0.7, -ID, glassMat)
    addBox(mallGroup, 2 * IW, 1.1, 0.12, 0, y + 0.7, ID, glassMat)
    addBox(mallGroup, 0.12, 1.1, 2 * ID, -IW, y + 0.7, 0, glassMat)
    addBox(mallGroup, 0.12, 1.1, 2 * ID, IW, y + 0.7, 0, glassMat)
    // floor number trim
    addBox(mallGroup, 0.6, 0.6, 0.6, -OW + 0.6, y + 0.9, -OD + 0.6, trimMat)
  }

  // escalators criss-crossing the atrium
  for (let f = 0; f < FLOORS - 1; f++) {
    const dirx = f % 2 === 0 ? -1 : 1
    const esc = new THREE.Mesh(new THREE.BoxGeometry(3, 0.4, Math.hypot(FH, 2 * ID - 2) ), escMat)
    esc.position.set(dirx * 6, f * FH + FH / 2, 0)
    esc.rotation.x = -Math.atan2(FH, 2 * ID - 2)
    esc.castShadow = true; mallGroup.add(esc)
  }

  // glass roof
  const roof = new THREE.Mesh(new THREE.BoxGeometry(2 * OW, 0.2, 2 * OD), new THREE.MeshStandardMaterial({ color: 0xbfe3ff, transparent: true, opacity: 0.18, roughness: 0.05 }))
  roof.position.set(0, FLOORS * FH, 0); mallGroup.add(roof)

  // storefronts along the atrium-facing edges
  for (const s of list) {
    addBox(mallGroup, 7, 2.6, 2.4, s.x, s.y + 1.5, s.z,
      new THREE.MeshStandardMaterial({ color: new THREE.Color(s.color).lerp(new THREE.Color(0xffffff), 0.1), roughness: 0.55 }))
    const sign = new THREE.Mesh(new THREE.PlaneGeometry(3.4, 1.6), new THREE.MeshBasicMaterial({ map: logoTexture(s), transparent: true }))
    sign.position.set(s.x, s.y + 1.7, s.z + s.face * 1.35)
    if (s.face < 0) sign.rotation.y = Math.PI
    mallGroup.add(sign)
  }

  scene.add(mallGroup)
  stores.value = list
}

function navigateTo(store) {
  if (routeGroup) { scene.remove(routeGroup); routeGroup.traverse(o => { o.geometry?.dispose?.(); o.material?.dispose?.() }) }
  routeGroup = new THREE.Group()
  const pts = [new THREE.Vector3(0, 0.4, -ID - 1.5)]
  for (let l = 1; l <= store.floor; l++) pts.push(new THREE.Vector3((l % 2 ? 6 : -6), l * FH + 0.4, 0))
  pts.push(new THREE.Vector3(store.x, store.floor * FH + 0.4, store.z + store.face * 2.4))
  walkerCurve = new THREE.CatmullRomCurve3(pts, false, 'catmullrom', 0.3)
  routeGroup.add(new THREE.Mesh(new THREE.TubeGeometry(walkerCurve, 120, 0.22, 10, false),
    new THREE.MeshStandardMaterial({ color: 0x7c3aed, emissive: 0x6d28d9, emissiveIntensity: 1, transparent: true, opacity: 0.92 })))
  walker = new THREE.Mesh(new THREE.SphereGeometry(0.5, 24, 24), new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0x7c3aed, emissiveIntensity: 1.3 }))
  routeGroup.add(walker); walkerT = 0; scene.add(routeGroup)
  let meters = 0
  for (let i = 1; i < pts.length; i++) meters += pts[i].distanceTo(pts[i - 1]) * UNIT_M
  dest.value = { name: store.name, floor: store.floor + 1, meters: Math.round(meters), secs: Math.round(meters / WALK + store.floor * FLOOR_SECS) }
}

async function loadReal() {
  status.value = 'در حال گرفتن فروشگاه‌های واقعی از OpenStreetMap…'
  try {
    const q = '[out:json][timeout:25];(node["shop"]["name"](around:320,51.5152,-0.1419););out 80;'
    const res = await fetch('https://overpass-api.de/api/interpreter', { method: 'POST', body: q })
    const json = await res.json()
    const names = [...new Set(json.elements.map(e => e.tags && e.tags.name).filter(Boolean))].slice(0, 10)
    if (!names.length) { status.value = 'فروشگاهی پیدا نشد.'; return }
    buildMall(layout(names.map((n, i) => ({ id: 'osm' + i, name: n, color: PALETTE[i % PALETTE.length], slug: n.toLowerCase().replace(/[^a-z0-9]/g, '') }))))
    status.value = `✅ ${names.length} فروشگاهِ واقعی (خیابانِ آکسفوردِ لندن) روی ۵ طبقه چیده شد.`
  } catch (e) { status.value = 'خطا در اتصال به OpenStreetMap.' }
}
function loadBrands() { buildMall(layout(BRANDS.map(b => ({ ...b })))); status.value = '' }

onMounted(() => {
  const wrap = canvasWrap.value, w = wrap.clientWidth, h = wrap.clientHeight
  scene = new THREE.Scene(); scene.background = new THREE.Color(0x0c0f22); scene.fog = new THREE.Fog(0x0c0f22, 55, 110)
  camera = new THREE.PerspectiveCamera(46, w / h, 0.1, 300); camera.position.set(10, FLOORS * FH * 1.08, 62)
  renderer = new THREE.WebGLRenderer({ antialias: true })
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); renderer.setSize(w, h)
  renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap
  wrap.appendChild(renderer.domElement)
  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true; controls.target.set(0, FLOORS * FH * 0.5, 0)
  controls.maxPolarAngle = Math.PI / 2.05; controls.minDistance = 24; controls.maxDistance = 95
  controls.autoRotate = true; controls.autoRotateSpeed = 0.55
  scene.add(new THREE.HemisphereLight(0xcfe0ff, 0x202840, 0.9))
  const dir = new THREE.DirectionalLight(0xffffff, 1.05); dir.position.set(18, 40, 22); dir.castShadow = true
  dir.shadow.mapSize.set(2048, 2048); Object.assign(dir.shadow.camera, { left: -35, right: 35, top: 45, bottom: -10, far: 120 })
  scene.add(dir)
  for (let f = 0; f < FLOORS; f++) { const pl = new THREE.PointLight(0xffe9c7, 0.5, 30); pl.position.set(0, f * FH + 2.5, 0); scene.add(pl) }
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(120, 120), new THREE.MeshStandardMaterial({ color: 0x0a0c1c, roughness: 1 }))
  ground.rotation.x = -Math.PI / 2; ground.position.y = -0.2; ground.receiveShadow = true; scene.add(ground)
  // "you are here" at ground entrance
  const pin = new THREE.Mesh(new THREE.ConeGeometry(0.7, 1.8, 24), new THREE.MeshStandardMaterial({ color: 0x22d3ee, emissive: 0x06b6d4, emissiveIntensity: 0.9 }))
  pin.position.set(0, 1.2, -ID - 1.5); pin.rotation.x = Math.PI; scene.add(pin)
  const ring = new THREE.Mesh(new THREE.RingGeometry(0.9, 1.25, 32), new THREE.MeshBasicMaterial({ color: 0x22d3ee, transparent: true, opacity: 0.8, side: THREE.DoubleSide }))
  ring.rotation.x = -Math.PI / 2; ring.position.set(0, 0.05, -ID - 1.5); scene.add(ring)

  buildMall(layout(BRANDS.map(b => ({ ...b }))))

  const clock = new THREE.Clock()
  const loop = () => {
    raf = requestAnimationFrame(loop)
    const t = clock.getElapsedTime(), sc = 1 + Math.sin(t * 3) * 0.22
    ring.scale.set(sc, sc, sc); ring.material.opacity = 0.85 - (sc - 1)
    if (walker && walkerCurve) { walkerT = (walkerT + 0.0035) % 1; walker.position.copy(walkerCurve.getPointAt(walkerT)) }
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
        <h2>پاساژِ سه‌بعدی · ۵ طبقه</h2>
        <div class="src">
          <button :class="{ on: !status }" @click="loadBrands">نمونه</button>
          <button @click="loadReal">🌍 واقعی</button>
        </div>
      </div>
      <div v-if="dest" class="route-info">🧭 <b>{{ dest.name }}</b> · طبقهٔ {{ faNum(dest.floor) }} · {{ faNum(dest.meters) }} متر · ~{{ faNum(dest.secs) }} ثانیه</div>
      <p v-else class="muted">{{ status || 'یک فروشگاه را انتخاب کن تا مسیر را در طبقات برایت بکشم.' }}</p>
    </div>
    <div ref="canvasWrap" class="canvas-wrap"></div>
    <div class="picker">
      <button v-for="s in stores" :key="s.id" class="chip" @click="navigateTo(s)">
        <img v-if="s.slug" :src="`https://cdn.simpleicons.org/${s.slug}`" :alt="s.name" @error="(e) => (e.target.style.display = 'none')" />
        <span>{{ s.name }}</span>
        <small>ط{{ faNum(s.floor + 1) }}</small>
      </button>
    </div>
  </section>
</template>

<style scoped>
.map { position: fixed; top: 56px; inset-inline: 0; bottom: 0; }
.canvas-wrap { position: absolute; inset: 0; }
.hud { position: absolute; top: 14px; inset-inline: 14px; z-index: 5; max-width: 560px; margin: 0 auto; background: rgba(12,15,34,.62); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 12px 16px; color: #fff; }
.hud-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.hud h2 { color: #fff; font-size: 17px; margin: 0; }
.src { display: flex; gap: 6px; }
.src button { font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); color: #fff; padding: 6px 11px; border-radius: 999px; }
.src button.on { background: #fff; color: #4f46e5; }
.hud .muted { color: rgba(255,255,255,.72); margin: 6px 0 0; font-size: 12.5px; }
.route-info { font-size: 14px; margin-top: 6px; }
.route-info b { color: #a5b4fc; }
.picker { position: absolute; bottom: 92px; inset-inline: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 0 14px 4px; justify-content: center; flex-wrap: nowrap; }
.chip { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; min-width: 76px; max-width: 120px; height: 70px; padding: 8px 10px; border: 1px solid rgba(255,255,255,.14); border-radius: 14px; background: rgba(255,255,255,.95); cursor: pointer; font: inherit; font-size: 11px; font-weight: 700; color: #1b1f2a; }
.chip img { width: 24px; height: 24px; object-fit: contain; }
.chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 96px; }
.chip small { color: #6b7280; font-size: 10px; }
.chip:active { transform: translateY(1px); }
</style>
