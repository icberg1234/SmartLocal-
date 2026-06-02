<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import * as THREE from 'three'
import { OrbitControls } from 'three/addons/controls/OrbitControls.js'
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js'

const PALETTE = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#64748b']
const PCOL = [0x2563eb, 0xdc2626, 0x059669, 0x7c3aed, 0xea580c, 0x0891b2, 0x334155, 0xdb2777]
const BRANDS = [
  { id: 'nike', name: 'Nike', slug: 'nike', color: '#111111' },
  { id: 'apple', name: 'Apple', slug: 'apple', color: '#5b5b5b' },
  { id: 'samsung', name: 'Samsung', slug: 'samsung', color: '#1428A0' },
  { id: 'zara', name: 'Zara', slug: 'zara', color: '#1a1a1a' },
  { id: 'adidas', name: 'Adidas', slug: 'adidas', color: '#0b0b0b' },
  { id: 'starbucks', name: 'Starbucks', slug: 'starbucks', color: '#00704A' },
  { id: 'mcdonalds', name: "McDonald's", slug: 'mcdonalds', color: '#D62300' },
  { id: 'xiaomi', name: 'Xiaomi', slug: 'xiaomi', color: '#FF6900' },
  { id: 'hm', name: 'H&M', slug: 'handm', color: '#E50010' },
  { id: 'lego', name: 'LEGO', slug: 'lego', color: '#E3000B' },
]
const FLOORS = 5, FH = 4.6
const OW = 22, OD = 13
const IW = 11, ID = 5.5
const UNIT_M = 1.6, WALK = 1.3, FLOOR_SECS = 16

const canvasWrap = ref(null)
const stores = ref([])
const dest = ref(null)
const status = ref('')
const view = ref('orbit')

let renderer, scene, camera, controls, raf
let mallGroup, routeGroup, walker, walkerT = 0, walkerCurve = null
let camTarget = null, followFresh = false

// shared materials (created once)
const M = {}
function initMaterials() {
  M.floor = new THREE.MeshStandardMaterial({ color: 0xd7dae4, roughness: 0.3, metalness: 0.35 })
  M.wall = new THREE.MeshStandardMaterial({ color: 0xd4d8e0, roughness: 0.92 })
  M.metal = new THREE.MeshStandardMaterial({ color: 0xc4c9d2, roughness: 0.25, metalness: 0.9 })
  M.glass = new THREE.MeshStandardMaterial({ color: 0x9fc4e8, roughness: 0.05, metalness: 0.1, transparent: true, opacity: 0.12, envMapIntensity: 1.2, side: THREE.DoubleSide })
  M.step = new THREE.MeshStandardMaterial({ color: 0x8b91a3, roughness: 0.4, metalness: 0.75 })
  M.skin = new THREE.MeshStandardMaterial({ color: 0xe8b58a, roughness: 0.6 })
  M.warm = new THREE.MeshStandardMaterial({ color: 0xfff3d6, emissive: 0xffce7a, emissiveIntensity: 1.6 })
  M.led = new THREE.MeshStandardMaterial({ color: 0x9ad8ff, emissive: 0x5b8cff, emissiveIntensity: 1.6 })
}

function layout(list) {
  list.forEach((s, i) => {
    s.floor = i % FLOORS
    const pass = Math.floor(i / FLOORS)
    s.face = pass % 2 === 0 ? 1 : -1
    s.z = s.face > 0 ? -(OD - 3) : (OD - 3)
    s.x = -10 + Math.floor(pass / 2) * 10
    s.y = s.floor * FH
  })
  return list
}

function skyTex() {
  const c = document.createElement('canvas'); c.width = 16; c.height = 256
  const ctx = c.getContext('2d'); const g = ctx.createLinearGradient(0, 0, 0, 256)
  g.addColorStop(0, '#0a1024'); g.addColorStop(0.5, '#22305a'); g.addColorStop(1, '#7a86ad')
  ctx.fillStyle = g; ctx.fillRect(0, 0, 16, 256)
  return new THREE.CanvasTexture(c)
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
function box(g, w, h, d, x, y, z, mat, shadow = true) {
  const m = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat)
  m.position.set(x, y, z); if (shadow) { m.castShadow = true; m.receiveShadow = true }; g.add(m); return m
}
function makePerson(g, x, y, z) {
  const c = PCOL[Math.floor(Math.random() * PCOL.length)]
  const body = new THREE.Mesh(new THREE.CapsuleGeometry(0.2, 0.6, 4, 8), new THREE.MeshStandardMaterial({ color: c, roughness: 0.7 }))
  body.position.set(x, y + 0.5, z); body.castShadow = true; g.add(body)
  const head = new THREE.Mesh(new THREE.SphereGeometry(0.17, 12, 12), M.skin)
  head.position.set(x, y + 1.0, z); g.add(head)
}

function buildMall(list) {
  if (routeGroup) { scene.remove(routeGroup); routeGroup = null; dest.value = null; walkerCurve = null }
  if (mallGroup) { scene.remove(mallGroup); mallGroup.traverse(o => { o.geometry?.dispose?.(); if (o.material?.map && o.material.map !== null) o.material.map.dispose?.() }) }
  mallGroup = new THREE.Group()
  const H = FLOORS * FH

  // corner + mid structural columns
  for (const cxp of [-OW + 0.7, 0, OW - 0.7]) for (const czp of [-OD + 0.7, OD - 0.7]) box(mallGroup, 0.8, H, 0.8, cxp, H / 2, czp, M.metal)
  // glass curtain walls (sides + back) — front stays open for the feature facade
  box(mallGroup, 2 * OW, H, 0.2, 0, H / 2, OD, M.glass, false)
  box(mallGroup, 0.2, H, 2 * OD, -OW, H / 2, 0, M.glass, false)
  box(mallGroup, 0.2, H, 2 * OD, OW, H / 2, 0, M.glass, false)

  for (let f = 0; f < FLOORS; f++) {
    const y = f * FH
    box(mallGroup, 2 * OW, 0.3, OD - ID, 0, y, -(ID + OD) / 2, M.floor)
    box(mallGroup, 2 * OW, 0.3, OD - ID, 0, y, (ID + OD) / 2, M.floor)
    box(mallGroup, OW - IW, 0.3, 2 * ID, -(IW + OW) / 2, y, 0, M.floor)
    box(mallGroup, OW - IW, 0.3, 2 * ID, (IW + OW) / 2, y, 0, M.floor)
    // glass balustrades around the atrium
    box(mallGroup, 2 * IW, 1.1, 0.1, 0, y + 0.7, -ID, M.glass, false)
    box(mallGroup, 2 * IW, 1.1, 0.1, 0, y + 0.7, ID, M.glass, false)
    box(mallGroup, 0.1, 1.1, 2 * ID, -IW, y + 0.7, 0, M.glass, false)
    box(mallGroup, 0.1, 1.1, 2 * ID, IW, y + 0.7, 0, M.glass, false)
    // LED edge strips along the atrium
    box(mallGroup, 2 * IW, 0.1, 0.16, 0, y + 0.2, -ID, M.led, false)
    box(mallGroup, 2 * IW, 0.1, 0.16, 0, y + 0.2, ID, M.led, false)
    box(mallGroup, 0.16, 0.1, 2 * ID, -IW, y + 0.2, 0, M.led, false)
    box(mallGroup, 0.16, 0.1, 2 * ID, IW, y + 0.2, 0, M.led, false)
  }

  // escalators with handrails
  for (let f = 0; f < FLOORS - 1; f++) {
    const dx = f % 2 === 0 ? -1 : 1, len = Math.hypot(FH, 2 * ID - 1.5), ang = -Math.atan2(FH, 2 * ID - 1.5)
    const ramp = box(mallGroup, 2.6, 0.3, len, dx * 6.5, f * FH + FH / 2 + 0.15, 0, M.step); ramp.rotation.x = ang
    for (const sx of [-1, 1]) { const r = box(mallGroup, 0.1, 0.5, len, dx * 6.5 + sx * 1.35, f * FH + FH / 2 + 0.55, 0, M.glass, false); r.rotation.x = ang }
  }
  // glass elevator shaft + cab
  box(mallGroup, 2.4, H, 2.4, IW - 1.4, H / 2, 0, M.glass, false)
  box(mallGroup, 2, 2.2, 2, IW - 1.4, 1.2, 0, M.metal)

  // glass roof + curved feature facade
  box(mallGroup, 2 * OW, 0.2, 2 * OD, 0, H, 0, M.glass, false)
  const facade = new THREE.Mesh(new THREE.CylinderGeometry(27, 27, H, 80, 1, true, Math.PI * 0.64, Math.PI * 0.72), M.glass)
  facade.position.set(0, H / 2, OD); mallGroup.add(facade)

  // detailed shop units
  for (const s of list) {
    const baseY = s.y + 0.15, w = 7.4, h = 3.6, dep = 3.6, cx = s.x, cz = s.z, fd = s.face
    const frontZ = cz + fd * dep / 2, backZ = cz - fd * dep / 2
    box(mallGroup, w, h, 0.2, cx, baseY + h / 2, backZ, M.wall)
    box(mallGroup, 0.2, h, dep, cx - w / 2, baseY + h / 2, cz, M.wall)
    box(mallGroup, 0.2, h, dep, cx + w / 2, baseY + h / 2, cz, M.wall)
    box(mallGroup, w, 0.16, dep, cx, baseY + h, cz, M.wall)
    box(mallGroup, w * 0.7, 0.06, dep * 0.6, cx, baseY + h - 0.13, cz, M.warm, false) // ceiling light
    box(mallGroup, w - 0.5, h - 1, 0.06, cx, baseY + (h - 1) / 2 + 0.4, backZ + fd * 0.12,
      new THREE.MeshStandardMaterial({ color: new THREE.Color(s.color).lerp(new THREE.Color(0xffffff), 0.15) }), false) // brand back panel
    for (let k = -1; k <= 1; k++) box(mallGroup, 1.2, 1.7, 0.5, cx + k * 2.3, baseY + 0.85, backZ + fd * 0.55, M.metal) // shelves
    box(mallGroup, 3, 0.9, 0.8, cx, baseY + 0.45, cz + fd * 0.5, M.wall) // counter
    const vit = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.3, h - 0.3), M.glass)
    vit.position.set(cx, baseY + (h - 0.3) / 2, frontZ); if (fd < 0) vit.rotation.y = Math.PI; mallGroup.add(vit)
    box(mallGroup, w, 0.14, 0.14, cx, baseY + h - 0.07, frontZ, M.metal, false) // front frame
    const sign = new THREE.Mesh(new THREE.PlaneGeometry(3.8, 1.5), new THREE.MeshBasicMaterial({ map: logoTexture(s), transparent: true }))
    sign.position.set(cx, baseY + h + 0.45, frontZ + fd * 0.03); if (fd < 0) sign.rotation.y = Math.PI; mallGroup.add(sign)
    makePerson(mallGroup, cx + (Math.random() - 0.5) * 3, baseY, cz + fd * 0.3) // shopper inside
  }

  // shoppers on the walkways
  for (let f = 0; f < FLOORS; f++) {
    const fy = f * FH + 0.15
    for (let n = 0; n < 5; n++) {
      const front = Math.random() < 0.5
      makePerson(mallGroup, -17 + Math.random() * 34, fy, (front ? -1 : 1) * (7 + Math.random() * 2))
    }
  }

  scene.add(mallGroup)
  stores.value = list
}

function navigateTo(store) {
  if (routeGroup) { scene.remove(routeGroup); routeGroup.traverse(o => { o.geometry?.dispose?.(); o.material?.dispose?.() }) }
  routeGroup = new THREE.Group()
  const fy = (l) => l * FH + 0.55, sgn = store.z < 0 ? -1 : 1
  const escX = (f) => (f % 2 === 0 ? -1 : 1) * 6.5, zEnd = ID - 1
  const pts = [new THREE.Vector3(0, 0.55, -ID - 1)]                       // entrance
  for (let f = 0; f < store.floor; f++) {                                // ride each escalator up
    pts.push(new THREE.Vector3(escX(f), fy(f), zEnd))                     // walk to escalator base
    pts.push(new THREE.Vector3(escX(f), fy(f + 1), -zEnd))                // ride up to next floor
  }
  pts.push(new THREE.Vector3(store.x, fy(store.floor), sgn * (ID + 1.2))) // walk along the floor
  pts.push(new THREE.Vector3(store.x, fy(store.floor), store.z + store.face * 2.1)) // arrive
  walkerCurve = new THREE.CatmullRomCurve3(pts, false, 'centripetal', 0.4)
  routeGroup.add(new THREE.Mesh(new THREE.TubeGeometry(walkerCurve, 220, 0.26, 14, false),
    new THREE.MeshStandardMaterial({ color: 0x8b5cf6, emissive: 0x7c3aed, emissiveIntensity: 1.7, transparent: true, opacity: 0.95 })))
  // direction arrows so the route is unmistakable
  const arrowMat = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xc4b5fd, emissiveIntensity: 1.4 })
  for (let i = 1; i < 16; i++) {
    const u = i / 16, p = walkerCurve.getPointAt(u), tg = walkerCurve.getTangentAt(u).normalize()
    const a = new THREE.Mesh(new THREE.ConeGeometry(0.24, 0.7, 12), arrowMat)
    a.position.copy(p); a.position.y += 0.12
    a.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), tg)
    routeGroup.add(a)
  }
  walker = new THREE.Mesh(new THREE.CapsuleGeometry(0.4, 0.9, 6, 14), new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0x7c3aed, emissiveIntensity: 1.2 }))
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
function setView(v) {
  view.value = v
  if (v === 'orbit') { controls.enabled = true; controls.autoRotate = true } else { controls.autoRotate = false; followFresh = true }
}
function focusFloor(f) {
  view.value = 'orbit'; controls.autoRotate = false
  if (f === 'all') { camTarget = { pos: new THREE.Vector3(48, FLOORS * FH * 1.22, 56), look: new THREE.Vector3(0, FLOORS * FH * 0.45, 0), rot: true } }
  else { const y = f * FH; camTarget = { pos: new THREE.Vector3(2, y + 6, 30), look: new THREE.Vector3(0, y + 1.5, 0), rot: false } }
}

onMounted(() => {
  const wrap = canvasWrap.value, w = wrap.clientWidth, h = wrap.clientHeight
  initMaterials()
  scene = new THREE.Scene(); scene.background = skyTex(); scene.fog = new THREE.Fog(0x141d3a, 150, 340)
  camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 400); camera.position.set(48, FLOORS * FH * 1.22, 56)
  renderer = new THREE.WebGLRenderer({ antialias: true })
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); renderer.setSize(w, h)
  renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap
  renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 1.0
  wrap.appendChild(renderer.domElement)
  scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(), 0.04).texture
  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true; controls.target.set(0, FLOORS * FH * 0.45, 0)
  controls.maxPolarAngle = Math.PI / 2.05; controls.minDistance = 22; controls.maxDistance = 110
  controls.autoRotate = true; controls.autoRotateSpeed = 0.5
  scene.add(new THREE.HemisphereLight(0xcdd9f5, 0x242b42, 0.5))
  const dir = new THREE.DirectionalLight(0xfff2dc, 1.15); dir.position.set(26, 50, 30); dir.castShadow = true
  dir.shadow.mapSize.set(2048, 2048); Object.assign(dir.shadow.camera, { left: -40, right: 40, top: 55, bottom: -12, far: 150 })
  scene.add(dir)
  for (let f = 0; f < FLOORS; f++) { const pl = new THREE.PointLight(0xffe0ad, 1.5, 52); pl.position.set(0, f * FH + 2.8, 0); scene.add(pl) }
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(160, 160), new THREE.MeshStandardMaterial({ color: 0x0a0c1c, roughness: 1 }))
  ground.rotation.x = -Math.PI / 2; ground.position.y = -0.2; ground.receiveShadow = true; scene.add(ground)
  const pin = new THREE.Mesh(new THREE.ConeGeometry(0.7, 1.8, 24), new THREE.MeshStandardMaterial({ color: 0x22d3ee, emissive: 0x06b6d4, emissiveIntensity: 0.9 }))
  pin.position.set(0, 1.2, -ID - 1); pin.rotation.x = Math.PI; scene.add(pin)
  const ring = new THREE.Mesh(new THREE.RingGeometry(0.9, 1.25, 32), new THREE.MeshBasicMaterial({ color: 0x22d3ee, transparent: true, opacity: 0.8, side: THREE.DoubleSide }))
  ring.rotation.x = -Math.PI / 2; ring.position.set(0, 0.05, -ID - 1); scene.add(ring)

  buildMall(layout(BRANDS.map(b => ({ ...b }))))

  const clock = new THREE.Clock(), up = new THREE.Vector3(0, 1, 0)
  const pPos = new THREE.Vector3(), pTan = new THREE.Vector3(), camGoal = new THREE.Vector3(), lookGoal = new THREE.Vector3(), smoothLook = new THREE.Vector3(), flatTan = new THREE.Vector3()
  const loop = () => {
    raf = requestAnimationFrame(loop)
    const t = clock.getElapsedTime(), sc = 1 + Math.sin(t * 3) * 0.22
    ring.scale.set(sc, sc, sc); ring.material.opacity = 0.85 - (sc - 1)
    const following = !!walkerCurve && view.value !== 'orbit'
    if (walker && walkerCurve) {
      walkerT = (walkerT + (following ? 0.0011 : 0.0032)) % 1
      walkerCurve.getPointAt(walkerT, pPos); walkerCurve.getTangentAt(walkerT, pTan).normalize()
      walker.position.copy(pPos); walker.visible = view.value !== 'first'
    }
    if (following) {
      controls.enabled = false
      if (view.value === 'first') { flatTan.set(pTan.x, pTan.y * 0.25, pTan.z).normalize(); camGoal.copy(pPos).addScaledVector(up, 1.6).addScaledVector(flatTan, -0.2); lookGoal.copy(pPos).addScaledVector(flatTan, 8).addScaledVector(up, 1.0) }
      else { camGoal.copy(pPos).addScaledVector(pTan, -8).addScaledVector(up, 4.5); lookGoal.copy(pPos).addScaledVector(pTan, 5).addScaledVector(up, 1) }
      if (followFresh) { camera.position.copy(camGoal); smoothLook.copy(lookGoal); followFresh = false }
      else { camera.position.lerp(camGoal, 0.06); smoothLook.lerp(lookGoal, 0.06) }
      camera.lookAt(smoothLook)
    } else if (camTarget) {
      controls.enabled = false
      camera.position.lerp(camTarget.pos, 0.06); camera.lookAt(camTarget.look)
      if (camera.position.distanceTo(camTarget.pos) < 0.8) { controls.target.copy(camTarget.look); controls.autoRotate = !!camTarget.rot; controls.enabled = true; camTarget = null }
    } else { controls.enabled = true; controls.update() }
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
        <h2>ماکتِ سه‌بعدیِ پاساژ · ۵ طبقه</h2>
        <div class="src">
          <button :class="{ on: !status }" @click="loadBrands">نمونه</button>
          <button @click="loadReal">🌍 واقعی</button>
        </div>
      </div>
      <div v-if="dest" class="route-info">🧭 <b>{{ dest.name }}</b> · طبقهٔ {{ faNum(dest.floor) }} · {{ faNum(dest.meters) }} متر · ~{{ faNum(dest.secs) }} ثانیه</div>
      <p v-else class="muted">{{ status || 'فروشگاه را انتخاب کن، یا با دکمه‌ها دوربین و طبقه را عوض کن.' }}</p>
      <div class="rows">
        <div class="views">
          <button :class="{ on: view === 'orbit' }" @click="setView('orbit')">🛰 مرور</button>
          <button :class="{ on: view === 'third' }" @click="setView('third')">🎥 سوم‌شخص</button>
          <button :class="{ on: view === 'first' }" @click="setView('first')">🚶 اول‌شخص</button>
        </div>
        <div class="views">
          <button @click="focusFloor('all')">کلی</button>
          <button v-for="f in FLOORS" :key="f" @click="focusFloor(f - 1)">ط{{ faNum(f) }}</button>
        </div>
      </div>
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
.hud { position: absolute; top: 14px; inset-inline: 14px; z-index: 5; max-width: 580px; margin: 0 auto; background: rgba(12,15,34,.64); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 12px 16px; color: #fff; }
.hud-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.hud h2 { color: #fff; font-size: 16px; margin: 0; }
.src { display: flex; gap: 6px; }
.src button { font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); color: #fff; padding: 6px 11px; border-radius: 999px; }
.src button.on { background: #fff; color: #4f46e5; }
.hud .muted { color: rgba(255,255,255,.72); margin: 6px 0 0; font-size: 12.5px; }
.route-info { font-size: 14px; margin-top: 6px; }
.route-info b { color: #a5b4fc; }
.rows { display: flex; flex-direction: column; gap: 6px; margin-top: 8px; }
.views { display: flex; gap: 6px; flex-wrap: wrap; }
.views button { font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,.2); background: rgba(255,255,255,.08); color: #fff; padding: 5px 10px; border-radius: 999px; }
.views button.on { background: #7c3aed; border-color: #7c3aed; }
.picker { position: absolute; bottom: 92px; inset-inline: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 0 14px 4px; justify-content: center; }
.chip { flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; min-width: 76px; max-width: 120px; height: 70px; padding: 8px 10px; border: 1px solid rgba(255,255,255,.14); border-radius: 14px; background: rgba(255,255,255,.95); cursor: pointer; font: inherit; font-size: 11px; font-weight: 700; color: #1b1f2a; }
.chip img { width: 24px; height: 24px; object-fit: contain; }
.chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 96px; }
.chip small { color: #6b7280; font-size: 10px; }
.chip:active { transform: translateY(1px); }
</style>
