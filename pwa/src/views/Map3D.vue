<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import * as THREE from 'three'
import { OrbitControls } from 'three/addons/controls/OrbitControls.js'
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js'
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js'
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js'

const MODELS = [
  'https://cdn.jsdelivr.net/gh/nhtoby311/mall-map@main/public/assets/mall.glb',
  'https://raw.githubusercontent.com/nhtoby311/mall-map/main/public/assets/mall.glb',
]
const BRANDS = [
  { key: 'hm', name: 'H&M', slug: 'handm', color: '#E50010' },
  { key: 'nike', name: 'Nike', slug: 'nike', color: '#111111' },
  { key: 'apple', name: 'Apple', slug: 'apple', color: '#555555' },
  { key: 'zara', name: 'Zara', slug: 'zara', color: '#1a1a1a' },
  { key: 'starbucks', name: 'Starbucks', slug: 'starbucks', color: '#00704A' },
  { key: 'mcdonalds', name: "McDonald's", slug: 'mcdonalds', color: '#D62300' },
  { key: 'samsung', name: 'Samsung', slug: 'samsung', color: '#1428A0' },
  { key: 'xiaomi', name: 'Xiaomi', slug: 'xiaomi', color: '#FF6900' },
]

const canvasWrap = ref(null)
const status = ref('در حال بارگذاریِ مدلِ سه‌بعدیِ واقعی…')
const dest = ref(null)
const stores = ref(BRANDS)
const routeQ = useRoute()

let renderer, scene, camera, controls, raf, model, groundMesh
const box = new THREE.Box3(), size = new THREE.Vector3()
let markersGroup, routeGroup, walker, walkerT = 0, walkerCurve = null
const youPos = new THREE.Vector3()
let unit = 1

function roundRect(c, x, y, w, h, r) { c.beginPath(); c.moveTo(x + r, y); c.arcTo(x + w, y, x + w, y + h, r); c.arcTo(x + w, y + h, x, y + h, r); c.arcTo(x, y + h, x, y, r); c.arcTo(x, y, x + w, y, r); c.closePath() }
function signTexture(s) {
  const c = document.createElement('canvas'); c.width = 256; c.height = 128
  const ctx = c.getContext('2d'); const tex = new THREE.CanvasTexture(c)
  const draw = (img) => {
    ctx.clearRect(0, 0, 256, 128); ctx.fillStyle = '#fff'; roundRect(ctx, 6, 6, 244, 116, 22); ctx.fill()
    ctx.fillStyle = s.color || '#06b6d4'; ctx.textAlign = 'center'
    if (img) { ctx.drawImage(img, 96, 14, 64, 64); ctx.font = 'bold 26px Vazirmatn,sans-serif'; ctx.fillText(s.name, 128, 110) }
    else { ctx.font = 'bold 30px Vazirmatn,sans-serif'; ctx.textBaseline = 'middle'; ctx.fillText(s.name, 128, 64) }
    tex.needsUpdate = true
  }
  draw(null)
  if (s.slug) { const img = new Image(); img.crossOrigin = 'anonymous'; img.onload = () => draw(img); img.src = `https://cdn.simpleicons.org/${s.slug}/${(s.color || '#000').replace('#', '')}` }
  return tex
}
function addMarker(g, pos, colorHex, signObj) {
  const pin = new THREE.Mesh(new THREE.ConeGeometry(unit * 0.9, unit * 3.4, 18),
    new THREE.MeshStandardMaterial({ color: colorHex, emissive: colorHex, emissiveIntensity: 0.7 }))
  pin.position.copy(pos); pin.position.y += unit * 1.7; pin.rotation.x = Math.PI; g.add(pin)
  const sp = new THREE.Sprite(new THREE.SpriteMaterial({ map: signTexture(signObj), transparent: true, depthTest: false }))
  sp.scale.set(unit * 8, unit * 4, 1); sp.position.copy(pos); sp.position.y += unit * 6.5; g.add(sp)
}

function placeMarkers() {
  if (markersGroup) scene.remove(markersGroup)
  markersGroup = new THREE.Group()
  const min = box.min, max = box.max, floorY = min.y + size.y * 0.04
  const padX = size.x * 0.14, padZ = size.z * 0.16
  youPos.set((min.x + max.x) / 2, floorY, max.z - padZ)
  addMarker(markersGroup, youPos, 0x22d3ee, { name: 'شما اینجایید', color: '#06b6d4' })
  BRANDS.forEach((b, i) => {
    const col = i % 4, row = Math.floor(i / 4)
    const x = min.x + padX + (size.x - 2 * padX) * (col / 3)
    const z = min.z + padZ + (size.z * 0.52) * row
    b.pos = new THREE.Vector3(x, floorY, z)
    addMarker(markersGroup, b.pos, new THREE.Color(b.color).getHex(), b)
  })
  scene.add(markersGroup)
}

function navigateTo(brand) {
  if (!brand.pos) return
  if (routeGroup) { scene.remove(routeGroup); routeGroup.traverse(o => { o.geometry?.dispose?.(); o.material?.dispose?.() }) }
  routeGroup = new THREE.Group()
  const lift = unit * 3
  const mid = youPos.clone().lerp(brand.pos, 0.5); mid.y += lift
  walkerCurve = new THREE.CatmullRomCurve3([youPos.clone(), mid, brand.pos.clone()], false, 'centripetal', 0.4)
  routeGroup.add(new THREE.Mesh(new THREE.TubeGeometry(walkerCurve, 90, unit * 0.6, 10, false),
    new THREE.MeshStandardMaterial({ color: 0x8b5cf6, emissive: 0x7c3aed, emissiveIntensity: 1.7, transparent: true, opacity: 0.95 })))
  const am = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xc4b5fd, emissiveIntensity: 1.3 })
  for (let i = 1; i < 12; i++) {
    const u = i / 12, p = walkerCurve.getPointAt(u), tg = walkerCurve.getTangentAt(u).normalize()
    const a = new THREE.Mesh(new THREE.ConeGeometry(unit * 0.7, unit * 1.8, 10), am)
    a.position.copy(p); a.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), tg); routeGroup.add(a)
  }
  walker = new THREE.Mesh(new THREE.SphereGeometry(unit * 1.1, 20, 20), new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0x7c3aed, emissiveIntensity: 1.3 }))
  routeGroup.add(walker); walkerT = 0; scene.add(routeGroup)
  const dist = youPos.distanceTo(brand.pos), meters = Math.max(5, Math.round(dist * 1.8))
  dest.value = { name: brand.name, meters, secs: Math.round(meters / 1.3) }
  controls.autoRotate = false
  controls.target.copy(mid); controls.target.y = (youPos.y + brand.pos.y) / 2
  camera.position.set(mid.x + dist * 0.95, mid.y + dist * 0.85, mid.z + dist * 0.95)
}

function frame() {
  box.setFromObject(model); box.getSize(size)
  const c = box.getCenter(new THREE.Vector3()), maxd = Math.max(size.x, size.y, size.z)
  unit = maxd * 0.012
  controls.autoRotate = true; controls.target.copy(c)
  camera.position.set(c.x + maxd * 0.85, c.y + maxd * 0.7, c.z + maxd * 0.85)
  camera.near = Math.max(0.02, maxd / 400); camera.far = maxd * 14; camera.updateProjectionMatrix()
  controls.minDistance = maxd * 0.08; controls.maxDistance = maxd * 3
  if (groundMesh) groundMesh.position.y = box.min.y - 0.02
}
function overview() { dest.value = null; if (model) frame() }

function loadModel(i = 0) {
  if (i >= MODELS.length) { status.value = 'بارگذاریِ مدل ناموفق بود.'; return }
  const loader = new GLTFLoader()
  const draco = new DRACOLoader(); draco.setDecoderPath('https://www.gstatic.com/draco/v1/decoders/'); loader.setDRACOLoader(draco)
  loader.load(MODELS[i], (gltf) => {
    model = gltf.scene
    const m = new THREE.MeshStandardMaterial({ color: 0xeceef5, roughness: 0.6, metalness: 0.05 })
    model.traverse(o => { if (o.isMesh) { o.castShadow = true; o.receiveShadow = true; o.material = m } })
    scene.add(model)
    frame(); placeMarkers()
    status.value = '✅ مدل آماده است — یک فروشگاه را انتخاب کن تا مسیر کشیده شود.'
    const q = routeQ.query.store
    if (q) { const b = BRANDS.find(x => x.key === q); if (b) { status.value = `🧭 مسیر تا ${b.name}`; navigateTo(b) } }
  }, (xhr) => { if (xhr.total) status.value = `در حال بارگذاریِ مدل… ${Math.round((xhr.loaded / xhr.total) * 100)}%` }, () => loadModel(i + 1))
}

onMounted(() => {
  const wrap = canvasWrap.value, w = wrap.clientWidth, h = wrap.clientHeight
  scene = new THREE.Scene(); scene.background = new THREE.Color(0x11141f)
  camera = new THREE.PerspectiveCamera(50, w / h, 0.1, 5000); camera.position.set(20, 14, 24)
  renderer = new THREE.WebGLRenderer({ antialias: true })
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); renderer.setSize(w, h)
  renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap
  renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 0.95
  wrap.appendChild(renderer.domElement)
  scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(), 0.04).texture
  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true; controls.autoRotate = true; controls.autoRotateSpeed = 0.45
  scene.add(new THREE.HemisphereLight(0xeaf0ff, 0x30303a, 0.5))
  const dir = new THREE.DirectionalLight(0xfff1d8, 2.4); dir.position.set(30, 60, 24); dir.castShadow = true
  dir.shadow.mapSize.set(2048, 2048); Object.assign(dir.shadow.camera, { left: -60, right: 60, top: 60, bottom: -60, far: 300 }); dir.shadow.bias = -0.0005
  scene.add(dir)
  groundMesh = new THREE.Mesh(new THREE.PlaneGeometry(6000, 6000), new THREE.MeshStandardMaterial({ color: 0x0d1020, roughness: 1 }))
  groundMesh.rotation.x = -Math.PI / 2; groundMesh.receiveShadow = true; scene.add(groundMesh)
  loadModel()
  const loop = () => {
    raf = requestAnimationFrame(loop)
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
        <button class="src" @click="overview">🛰 نمای کلی</button>
      </div>
      <div v-if="dest" class="route-info">🧭 مقصد: <b>{{ dest.name }}</b> · {{ faNum(dest.meters) }} متر · ~{{ faNum(dest.secs) }} ثانیه</div>
      <p v-else class="muted">{{ status }}</p>
    </div>
    <div ref="canvasWrap" class="canvas-wrap"></div>
    <div class="picker">
      <button v-for="s in stores" :key="s.key" class="chip" @click="navigateTo(s)">
        <img :src="`https://cdn.simpleicons.org/${s.slug}`" :alt="s.name" @error="(e) => (e.target.style.display = 'none')" />
        <span>{{ s.name }}</span>
      </button>
    </div>
  </section>
</template>

<style scoped>
.map { position: fixed; top: 56px; inset-inline: 0; bottom: 0; }
.canvas-wrap { position: absolute; inset: 0; }
.hud { position: absolute; top: 14px; inset-inline: 14px; z-index: 5; max-width: 560px; margin: 0 auto; background: rgba(12,15,34,.64); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 12px 16px; color: #fff; }
.hud-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.hud h2 { color: #fff; font-size: 16px; margin: 0; }
.src { font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); color: #fff; padding: 6px 11px; border-radius: 999px; }
.hud .muted { color: rgba(255,255,255,.82); margin: 6px 0 0; font-size: 13px; }
.route-info { font-size: 14px; margin-top: 6px; } .route-info b { color: #a5b4fc; }
.picker { position: absolute; bottom: 92px; inset-inline: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 0 14px 4px; justify-content: center; }
.chip { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; min-width: 72px; height: 64px; padding: 8px 10px; border: 1px solid rgba(255,255,255,.14); border-radius: 14px; background: rgba(255,255,255,.95); cursor: pointer; font: inherit; font-size: 11px; font-weight: 700; color: #1b1f2a; }
.chip img { width: 24px; height: 24px; object-fit: contain; }
.chip span { white-space: nowrap; }
.chip:active { transform: translateY(1px); }
</style>
