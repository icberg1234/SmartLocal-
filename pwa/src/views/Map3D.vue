<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import * as THREE from 'three'
import { OrbitControls } from 'three/addons/controls/OrbitControls.js'

// --- demo store layout (two rows along a central corridor) ---
const ROWX = [-7.5, -2.5, 2.5, 7.5]
const STORES = [
  { id: 'nike', name: 'Nike', slug: 'nike', color: '#111111', x: ROWX[0], z: -5 },
  { id: 'apple', name: 'Apple', slug: 'apple', color: '#444444', x: ROWX[1], z: -5 },
  { id: 'samsung', name: 'Samsung', slug: 'samsung', color: '#1428A0', x: ROWX[2], z: -5 },
  { id: 'zara', name: 'Zara', slug: 'zara', color: '#1a1a1a', x: ROWX[3], z: -5 },
  { id: 'adidas', name: 'Adidas', slug: 'adidas', color: '#0b0b0b', x: ROWX[0], z: 5 },
  { id: 'starbucks', name: 'Starbucks', slug: 'starbucks', color: '#00704A', x: ROWX[1], z: 5 },
  { id: 'mcdonalds', name: "McDonald's", slug: 'mcdonalds', color: '#D62300', x: ROWX[2], z: 5 },
  { id: 'xiaomi', name: 'Xiaomi', slug: 'xiaomi', color: '#FF6900', x: ROWX[3], z: 5 },
]
const ENTRANCE = { x: -12, z: 0 }
const UNIT_M = 2 // 1 world unit ≈ 2 meters
const WALK = 1.3 // m/s

const canvasWrap = ref(null)
const dest = ref(null) // { name, meters, secs }

let renderer, scene, camera, controls, raf
let routeGroup, walker, walkerT = 0, walkerCurve = null

function logoTexture(store) {
  const c = document.createElement('canvas')
  c.width = 256; c.height = 128
  const ctx = c.getContext('2d')
  const draw = (img) => {
    ctx.clearRect(0, 0, 256, 128)
    ctx.fillStyle = '#ffffff'; roundRect(ctx, 4, 4, 248, 120, 16); ctx.fill()
    if (img) {
      const s = 78
      ctx.drawImage(img, 128 - s / 2, 16, s, s)
      ctx.fillStyle = store.color; ctx.font = 'bold 22px Vazirmatn, sans-serif'
      ctx.textAlign = 'center'; ctx.fillText(store.name, 128, 116)
    } else {
      ctx.fillStyle = store.color; ctx.font = 'bold 34px Vazirmatn, sans-serif'
      ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText(store.name, 128, 64)
    }
    tex.needsUpdate = true
  }
  const tex = new THREE.CanvasTexture(c)
  draw(null)
  const img = new Image()
  img.crossOrigin = 'anonymous'
  img.onload = () => draw(img)
  img.src = `https://cdn.simpleicons.org/${store.slug}/${store.color.replace('#', '')}`
  return tex
}

function roundRect(ctx, x, y, w, h, r) {
  ctx.beginPath()
  ctx.moveTo(x + r, y); ctx.arcTo(x + w, y, x + w, y + h, r); ctx.arcTo(x + w, y + h, x, y + h, r)
  ctx.arcTo(x, y + h, x, y, r); ctx.arcTo(x, y, x + w, y, r); ctx.closePath()
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
  walkerCurve = new THREE.CatmullRomCurve3(pts, false, 'catmullrom', 0.0)
  const tube = new THREE.Mesh(
    new THREE.TubeGeometry(walkerCurve, 80, 0.22, 10, false),
    new THREE.MeshStandardMaterial({ color: 0x7c3aed, emissive: 0x4f46e5, emissiveIntensity: 0.9, transparent: true, opacity: 0.9 }),
  )
  routeGroup.add(tube)

  // moving "you" dot
  walker = new THREE.Mesh(
    new THREE.SphereGeometry(0.45, 24, 24),
    new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0x7c3aed, emissiveIntensity: 1.2 }),
  )
  routeGroup.add(walker)
  walkerT = 0
  scene.add(routeGroup)

  let meters = 0
  for (let i = 1; i < pts.length; i++) meters += pts[i].distanceTo(pts[i - 1]) * UNIT_M
  dest.value = { name: store.name, meters: Math.round(meters), secs: Math.round(meters / WALK) }
}

onMounted(() => {
  const wrap = canvasWrap.value
  const w = wrap.clientWidth, h = wrap.clientHeight

  scene = new THREE.Scene()
  scene.background = new THREE.Color(0x0f1226)
  scene.fog = new THREE.Fog(0x0f1226, 35, 70)

  camera = new THREE.PerspectiveCamera(48, w / h, 0.1, 200)
  camera.position.set(-2, 20, 24)

  renderer = new THREE.WebGLRenderer({ antialias: true })
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2))
  renderer.setSize(w, h)
  renderer.shadowMap.enabled = true
  renderer.shadowMap.type = THREE.PCFSoftShadowMap
  wrap.appendChild(renderer.domElement)

  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true
  controls.target.set(0, 0, 0)
  controls.maxPolarAngle = Math.PI / 2.2
  controls.minDistance = 12; controls.maxDistance = 45
  controls.autoRotate = true; controls.autoRotateSpeed = 0.6

  scene.add(new THREE.AmbientLight(0xffffff, 0.75))
  const dir = new THREE.DirectionalLight(0xffffff, 1.1)
  dir.position.set(12, 26, 10); dir.castShadow = true
  dir.shadow.mapSize.set(1024, 1024)
  dir.shadow.camera.left = -20; dir.shadow.camera.right = 20
  dir.shadow.camera.top = 20; dir.shadow.camera.bottom = -20
  scene.add(dir)

  // floor
  const floor = new THREE.Mesh(
    new THREE.PlaneGeometry(30, 20),
    new THREE.MeshStandardMaterial({ color: 0x171a33, roughness: 0.95 }),
  )
  floor.rotation.x = -Math.PI / 2; floor.receiveShadow = true
  scene.add(floor)

  // corridor strip
  const corridor = new THREE.Mesh(
    new THREE.PlaneGeometry(26, 3.4),
    new THREE.MeshStandardMaterial({ color: 0x23264a, roughness: 1 }),
  )
  corridor.rotation.x = -Math.PI / 2; corridor.position.y = 0.02; corridor.receiveShadow = true
  scene.add(corridor)

  // stores
  for (const s of STORES) {
    const box = new THREE.Mesh(
      new THREE.BoxGeometry(3.4, 2.6, 3.4),
      new THREE.MeshStandardMaterial({ color: new THREE.Color(s.color).lerp(new THREE.Color(0xffffff), 0.12), roughness: 0.6, metalness: 0.1 }),
    )
    box.position.set(s.x, 1.3, s.z); box.castShadow = true; box.receiveShadow = true
    scene.add(box)

    const sign = new THREE.Mesh(
      new THREE.PlaneGeometry(3, 1.5),
      new THREE.MeshBasicMaterial({ map: logoTexture(s), transparent: true }),
    )
    const front = s.z < 0 ? s.z + 1.71 : s.z - 1.71
    sign.position.set(s.x, 1.7, front)
    if (s.z > 0) sign.rotation.y = Math.PI
    scene.add(sign)
  }

  // "you are here"
  const you = new THREE.Group()
  const pin = new THREE.Mesh(
    new THREE.ConeGeometry(0.6, 1.6, 24),
    new THREE.MeshStandardMaterial({ color: 0x22d3ee, emissive: 0x06b6d4, emissiveIntensity: 0.8 }),
  )
  pin.position.set(ENTRANCE.x, 1.1, ENTRANCE.z); pin.rotation.x = Math.PI
  you.add(pin)
  const ring = new THREE.Mesh(
    new THREE.RingGeometry(0.8, 1.1, 32),
    new THREE.MeshBasicMaterial({ color: 0x22d3ee, transparent: true, opacity: 0.8, side: THREE.DoubleSide }),
  )
  ring.rotation.x = -Math.PI / 2; ring.position.set(ENTRANCE.x, 0.05, ENTRANCE.z)
  you.add(ring)
  scene.add(you)
  you.userData.ring = ring

  const clock = new THREE.Clock()
  const loop = () => {
    raf = requestAnimationFrame(loop)
    const t = clock.getElapsedTime()
    const sc = 1 + Math.sin(t * 3) * 0.25
    ring.scale.set(sc, sc, sc); ring.material.opacity = 0.8 - (sc - 1)
    if (walker && walkerCurve) {
      walkerT = (walkerT + 0.004) % 1
      walker.position.copy(walkerCurve.getPointAt(walkerT))
    }
    controls.update()
    renderer.render(scene, camera)
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
  cancelAnimationFrame(raf)
  window.removeEventListener('resize', onResize)
  controls?.dispose?.()
  renderer?.dispose?.()
  if (renderer?.domElement && canvasWrap.value?.contains(renderer.domElement)) canvasWrap.value.removeChild(renderer.domElement)
})

const faNum = (n) => Number(n).toLocaleString('fa-IR')
</script>

<template>
  <section class="map">
    <div class="hud">
      <h2>نقشهٔ سه‌بعدیِ پاساژ</h2>
      <p v-if="!dest" class="muted">یک فروشگاه را انتخاب کن تا مسیر را برایت بکشم.</p>
      <div v-else class="route-info">
        🧭 مقصد: <b>{{ dest.name }}</b> · {{ faNum(dest.meters) }} متر · ~{{ faNum(dest.secs) }} ثانیه پیاده
      </div>
    </div>

    <div ref="canvasWrap" class="canvas-wrap"></div>

    <div class="picker">
      <button v-for="s in STORES" :key="s.id" class="chip" @click="navigateTo(s)">
        <img :src="`https://cdn.simpleicons.org/${s.slug}`" :alt="s.name" />
        <span>{{ s.name }}</span>
      </button>
    </div>
  </section>
</template>

<style scoped>
.map { position: relative; margin: -22px -16px -104px; height: calc(100vh - 56px); }
.canvas-wrap { position: absolute; inset: 0; }
.hud { position: absolute; top: 14px; inset-inline: 14px; z-index: 5; background: rgba(15,18,38,.55); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 12px 16px; color: #fff; }
.hud h2 { color: #fff; font-size: 18px; margin: 0 0 2px; }
.hud .muted { color: rgba(255,255,255,.7); margin: 0; font-size: 13px; }
.route-info { font-size: 14px; }
.route-info b { color: #a5b4fc; }
.picker { position: absolute; bottom: 16px; inset-inline: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 0 14px 4px; }
.chip { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; gap: 5px; width: 74px; padding: 10px 6px; border: 1px solid rgba(255,255,255,.14); border-radius: 14px; background: rgba(255,255,255,.92); cursor: pointer; font: inherit; font-size: 11px; font-weight: 700; color: #1b1f2a; }
.chip img { width: 26px; height: 26px; object-fit: contain; }
.chip:active { transform: translateY(1px); }
</style>
