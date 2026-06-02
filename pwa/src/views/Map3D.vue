<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import * as THREE from 'three'
import { OrbitControls } from 'three/addons/controls/OrbitControls.js'

// Clean flat-design shopping directory — find a shop fast on a phone.
const SHOPS = [
  { key: 'hm', name: 'H&M', slug: 'handm', color: '#e11d2a', cat: 'مد' },
  { key: 'zara', name: 'Zara', slug: 'zara', color: '#111827', cat: 'مد' },
  { key: 'nike', name: 'Nike', slug: 'nike', color: '#1f2937', cat: 'ورزشی' },
  { key: 'adidas', name: 'Adidas', slug: 'adidas', color: '#0b1320', cat: 'ورزشی' },
  { key: 'apple', name: 'Apple', slug: 'apple', color: '#6b7280', cat: 'دیجیتال' },
  { key: 'samsung', name: 'Samsung', slug: 'samsung', color: '#1428a0', cat: 'دیجیتال' },
  { key: 'starbucks', name: 'Starbucks', slug: 'starbucks', color: '#00704a', cat: 'کافه' },
  { key: 'mcdonalds', name: "McDonald's", slug: 'mcdonalds', color: '#da291c', cat: 'فست‌فود' },
  { key: 'xiaomi', name: 'Xiaomi', slug: 'xiaomi', color: '#ff6900', cat: 'دیجیتال' },
  { key: 'lego', name: 'LEGO', slug: 'lego', color: '#e3000b', cat: 'اسباب‌بازی' },
  { key: 'sony', name: 'Sony', slug: 'sony', color: '#1f2937', cat: 'دیجیتال' },
  { key: 'lush', name: 'Lush', slug: 'lush', color: '#1a1a1a', cat: 'زیبایی' },
]
const COLS = [-20, -12, -4, 4, 12, 20]
SHOPS.forEach((s, i) => { const c = i % 6, r = Math.floor(i / 6); s.x = COLS[c]; s.z = r === 0 ? -7 : 7; s.face = r === 0 ? 1 : -1 })
const ENTRANCE = new THREE.Vector3(-27, 0, 0)

const canvasWrap = ref(null)
const q = ref('')
const dest = ref(null)
const routeQ = useRoute()
const filtered = computed(() => { const t = q.value.trim().toLowerCase(); return t ? SHOPS.filter(s => s.name.toLowerCase().includes(t) || s.cat.includes(t)) : SHOPS })

let renderer, scene, camera, controls, raf
let routeGroup, pinGroup, camTarget = null, tBounce = 0

function roundRect(c, x, y, w, h, r) { c.beginPath(); c.moveTo(x + r, y); c.arcTo(x + w, y, x + w, y + h, r); c.arcTo(x + w, y + h, x, y + h, r); c.arcTo(x, y + h, x, y, r); c.arcTo(x, y, x + w, y, r); c.closePath() }
function signTexture(s) {
  const c = document.createElement('canvas'); c.width = 256; c.height = 128
  const ctx = c.getContext('2d'); const tex = new THREE.CanvasTexture(c); tex.anisotropy = 8
  const draw = (img) => {
    ctx.clearRect(0, 0, 256, 128); ctx.fillStyle = '#fff'; roundRect(ctx, 6, 6, 244, 116, 20); ctx.fill()
    ctx.fillStyle = s.color; ctx.textAlign = 'center'
    if (img) { ctx.drawImage(img, 100, 12, 56, 56); ctx.font = 'bold 26px Vazirmatn,sans-serif'; ctx.fillText(s.name, 128, 104) }
    else { ctx.font = 'bold 30px Vazirmatn,sans-serif'; ctx.textBaseline = 'middle'; ctx.fillText(s.name, 128, 64) }
    tex.needsUpdate = true
  }
  draw(null)
  if (s.slug) { const im = new Image(); im.crossOrigin = 'anonymous'; im.onload = () => draw(im); im.src = `https://cdn.simpleicons.org/${s.slug}/${s.color.replace('#', '')}` }
  return tex
}
function skyTex() {
  const c = document.createElement('canvas'); c.width = 8; c.height = 256; const x = c.getContext('2d')
  const g = x.createLinearGradient(0, 0, 0, 256); g.addColorStop(0, '#dbe4f2'); g.addColorStop(0.55, '#eef0ef'); g.addColorStop(1, '#f5efe4')
  x.fillStyle = g; x.fillRect(0, 0, 8, 256); return new THREE.CanvasTexture(c)
}

function buildMall() {
  const g = new THREE.Group()
  const floorMat = new THREE.MeshStandardMaterial({ color: 0xefe9dd, roughness: 0.96 })
  const aisleMat = new THREE.MeshStandardMaterial({ color: 0xe4ddcb, roughness: 0.9 })
  const blockMat = new THREE.MeshStandardMaterial({ color: 0xf7f4ee, roughness: 0.85 })
  const ledMat = new THREE.MeshStandardMaterial({ color: 0xffd98a, emissive: 0xffc25a, emissiveIntensity: 0.9 })
  const floor = new THREE.Mesh(new THREE.BoxGeometry(64, 0.4, 26), floorMat); floor.position.y = -0.2; floor.receiveShadow = true; g.add(floor)
  const aisle = new THREE.Mesh(new THREE.BoxGeometry(60, 0.42, 6), aisleMat); aisle.position.set(0, -0.18, 0); aisle.receiveShadow = true; g.add(aisle)
  const led = new THREE.Mesh(new THREE.BoxGeometry(58, 0.05, 0.3), ledMat); led.position.set(0, 0.06, 0); g.add(led)
  for (const s of SHOPS) {
    const block = new THREE.Mesh(new THREE.BoxGeometry(7, 3.6, 6), blockMat)
    block.position.set(s.x, 1.8, s.z); block.castShadow = true; block.receiveShadow = true; g.add(block)
    const frontZ = s.z + s.face * 3
    const awning = new THREE.Mesh(new THREE.BoxGeometry(7, 0.5, 0.7), new THREE.MeshStandardMaterial({ color: new THREE.Color(s.color), roughness: 0.55 }))
    awning.position.set(s.x, 3.5, frontZ); g.add(awning)
    const sign = new THREE.Mesh(new THREE.PlaneGeometry(4.4, 2.2), new THREE.MeshBasicMaterial({ map: signTexture(s), transparent: true }))
    sign.position.set(s.x, 2.2, frontZ + s.face * 0.06); if (s.face < 0) sign.rotation.y = Math.PI; g.add(sign)
    s.pos = new THREE.Vector3(s.x, 0, frontZ + s.face * 0.5)
  }
  // entrance arch + you-are-here
  const arch = new THREE.Mesh(new THREE.TorusGeometry(3, 0.4, 12, 24, Math.PI), new THREE.MeshStandardMaterial({ color: 0x6366f1, roughness: 0.4, metalness: 0.3 }))
  arch.position.set(ENTRANCE.x, 0, 0); arch.rotation.y = Math.PI / 2; g.add(arch)
  scene.add(g)
}

function youHere() {
  const grp = new THREE.Group()
  const ring = new THREE.Mesh(new THREE.RingGeometry(1, 1.4, 32), new THREE.MeshBasicMaterial({ color: 0x06b6d4, transparent: true, opacity: 0.85, side: THREE.DoubleSide }))
  ring.rotation.x = -Math.PI / 2; ring.position.set(ENTRANCE.x, 0.1, 0); grp.add(ring)
  const dot = new THREE.Mesh(new THREE.SphereGeometry(0.6, 16, 16), new THREE.MeshStandardMaterial({ color: 0x22d3ee, emissive: 0x06b6d4, emissiveIntensity: 0.7 }))
  dot.position.set(ENTRANCE.x, 0.8, 0); grp.add(dot)
  scene.add(grp)
}

function navigateTo(shop) {
  dest.value = shop
  if (routeGroup) { scene.remove(routeGroup); routeGroup.traverse(o => { o.geometry?.dispose?.(); o.material?.dispose?.() }) }
  routeGroup = new THREE.Group()
  const pts = [ENTRANCE.clone().setY(0.25), new THREE.Vector3(shop.x, 0.25, 0), new THREE.Vector3(shop.x, 0.25, shop.pos.z)]
  const curve = new THREE.CatmullRomCurve3(pts, false, 'centripetal', 0.1)
  routeGroup.add(new THREE.Mesh(new THREE.TubeGeometry(curve, 80, 0.28, 10, false),
    new THREE.MeshStandardMaterial({ color: 0x6d28d9, emissive: 0x7c3aed, emissiveIntensity: 1.4, transparent: true, opacity: 0.95 })))
  const am = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xc4b5fd, emissiveIntensity: 1.2 })
  for (let i = 1; i < 10; i++) { const u = i / 10, p = curve.getPointAt(u), tg = curve.getTangentAt(u).normalize(); const a = new THREE.Mesh(new THREE.ConeGeometry(0.32, 0.9, 10), am); a.position.copy(p); a.position.y = 0.5; a.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), tg); routeGroup.add(a) }
  scene.add(routeGroup)
  // bouncing destination pin
  if (pinGroup) scene.remove(pinGroup)
  pinGroup = new THREE.Group()
  const pin = new THREE.Mesh(new THREE.ConeGeometry(0.9, 2.2, 18), new THREE.MeshStandardMaterial({ color: new THREE.Color(shop.color), emissive: new THREE.Color(shop.color), emissiveIntensity: 0.5 }))
  pin.rotation.x = Math.PI; pin.position.set(shop.x, 6, shop.pos.z); pinGroup.add(pin); scene.add(pinGroup)
  // ease camera toward the shop
  camTarget = { pos: new THREE.Vector3(shop.x, 13, shop.face * 13), look: new THREE.Vector3(shop.x, 2.5, shop.z) }
  controls.autoRotate = false
}
function overview() {
  dest.value = null
  if (routeGroup) { scene.remove(routeGroup); routeGroup = null }
  if (pinGroup) { scene.remove(pinGroup); pinGroup = null }
  camTarget = { pos: new THREE.Vector3(4, 46, 36), look: new THREE.Vector3(0, 0, 0) }
}
function onSearchEnter() { if (filtered.value.length) navigateTo(filtered.value[0]) }

onMounted(() => {
  const wrap = canvasWrap.value, w = wrap.clientWidth, h = wrap.clientHeight
  scene = new THREE.Scene(); scene.background = skyTex(); scene.fog = new THREE.Fog(0xe9e6dc, 90, 170)
  camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 400); camera.position.set(4, 46, 36)
  renderer = new THREE.WebGLRenderer({ antialias: true })
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); renderer.setSize(w, h)
  renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap
  renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.0
  wrap.appendChild(renderer.domElement)
  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true; controls.target.set(0, 0, 0); controls.maxPolarAngle = Math.PI / 2.3; controls.minDistance = 18; controls.maxDistance = 95
  scene.add(new THREE.HemisphereLight(0xfdfdff, 0xcbc2af, 1.0))
  const dir = new THREE.DirectionalLight(0xfff2dc, 1.7); dir.position.set(18, 38, 16); dir.castShadow = true
  dir.shadow.mapSize.set(2048, 2048); Object.assign(dir.shadow.camera, { left: -40, right: 40, top: 26, bottom: -26, far: 120 }); dir.shadow.bias = -0.0004
  scene.add(dir)
  buildMall(); youHere()
  const qy = routeQ.query.store
  if (qy) { const s = SHOPS.find(x => x.key === qy); if (s) navigateTo(s) }

  const loop = () => {
    raf = requestAnimationFrame(loop)
    tBounce += 0.05
    if (pinGroup) pinGroup.position.y = Math.abs(Math.sin(tBounce)) * 1.2
    if (camTarget) {
      camera.position.lerp(camTarget.pos, 0.06); controls.target.lerp(camTarget.look, 0.06)
      if (camera.position.distanceTo(camTarget.pos) < 0.6) camTarget = null
    }
    controls.update(); renderer.render(scene, camera)
  }
  loop()
  window.addEventListener('resize', onResize)
})
function onResize() { if (!renderer || !canvasWrap.value) return; const w = canvasWrap.value.clientWidth, h = canvasWrap.value.clientHeight; camera.aspect = w / h; camera.updateProjectionMatrix(); renderer.setSize(w, h) }
onBeforeUnmount(() => { cancelAnimationFrame(raf); window.removeEventListener('resize', onResize); controls?.dispose?.(); renderer?.dispose?.(); if (renderer?.domElement && canvasWrap.value?.contains(renderer.domElement)) canvasWrap.value.removeChild(renderer.domElement) })
</script>

<template>
  <section class="map">
    <div class="hud">
      <div class="hud-top">
        <h2>راهنمای فروشگاه‌ها</h2>
        <button class="src" @click="overview">نمای کلی</button>
      </div>
      <input class="search" v-model="q" @keyup.enter="onSearchEnter" placeholder="🔎 نامِ فروشگاه را بنویس…" />
      <div v-if="dest" class="route-info">🧭 مسیر تا <b>{{ dest.name }}</b> — از ورودی مستقیم برو.</div>
    </div>
    <div ref="canvasWrap" class="canvas-wrap"></div>
    <div class="picker">
      <button v-for="s in filtered" :key="s.key" class="chip" :class="{ on: dest && dest.key === s.key }" @click="navigateTo(s)">
        <img :src="`https://cdn.simpleicons.org/${s.slug}`" :alt="s.name" @error="(e) => (e.target.style.display = 'none')" />
        <span>{{ s.name }}</span>
      </button>
      <div v-if="!filtered.length" class="noresult">فروشگاهی با این نام نیست.</div>
    </div>
  </section>
</template>

<style scoped>
.map { position: fixed; top: 56px; inset-inline: 0; bottom: 0; }
.canvas-wrap { position: absolute; inset: 0; }
.hud { position: absolute; top: 14px; inset-inline: 14px; z-index: 5; max-width: 560px; margin: 0 auto; background: rgba(255,255,255,.86); backdrop-filter: blur(8px); border: 1px solid rgba(0,0,0,.06); border-radius: 16px; padding: 12px 14px; box-shadow: 0 6px 20px rgba(0,0,0,.1); }
.hud-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
.hud h2 { font-size: 16px; margin: 0; color: var(--text); }
.src { font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; border: 0; background: var(--primary-50); color: var(--primary); padding: 7px 12px; border-radius: 999px; }
.search { width: 100%; box-sizing: border-box; padding: 11px 14px; border: 1.5px solid var(--border); border-radius: 12px; font: inherit; }
.search:focus { outline: 0; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-50); }
.route-info { font-size: 13.5px; margin-top: 8px; color: var(--text); } .route-info b { color: var(--primary); }
.picker { position: absolute; bottom: 92px; inset-inline: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 0 14px 4px; }
.chip { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; min-width: 74px; height: 64px; padding: 8px 10px; border: 1.5px solid var(--border); border-radius: 14px; background: #fff; cursor: pointer; font: inherit; font-size: 11px; font-weight: 700; color: var(--text); }
.chip.on { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-50); }
.chip img { width: 24px; height: 24px; object-fit: contain; }
.noresult { align-self: center; color: #fff; background: rgba(0,0,0,.5); padding: 8px 14px; border-radius: 12px; font-size: 13px; }
</style>
