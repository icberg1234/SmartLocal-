<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import * as THREE from 'three'
import { OrbitControls } from 'three/addons/controls/OrbitControls.js'
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js'
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js'
import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js'

// A real modern shopping-mall model, loaded from a CDN (not procedural).
const MODELS = [
  'https://cdn.jsdelivr.net/gh/nhtoby311/mall-map@main/public/assets/mall.glb',
  'https://raw.githubusercontent.com/nhtoby311/mall-map/main/public/assets/mall.glb',
]

const canvasWrap = ref(null)
const status = ref('در حال بارگذاریِ مدلِ سه‌بعدیِ واقعی…')
const view = ref('orbit')

let renderer, scene, camera, controls, raf, model, groundMesh
let center = new THREE.Vector3(), size = new THREE.Vector3(), eyeY = 2
let walkT = 0

function setView(v) {
  view.value = v
  controls.autoRotate = v === 'orbit'
  controls.enabled = v === 'orbit'
  if (v === 'orbit' && model) frame()
}

function frame() {
  const box = new THREE.Box3().setFromObject(model)
  box.getCenter(center); box.getSize(size)
  eyeY = box.min.y + size.y * 0.2
  const maxd = Math.max(size.x, size.y, size.z)
  controls.target.copy(center)
  camera.position.set(center.x + maxd * 0.85, center.y + maxd * 0.7, center.z + maxd * 0.85)
  camera.near = Math.max(0.02, maxd / 400); camera.far = maxd * 14; camera.updateProjectionMatrix()
  controls.minDistance = maxd * 0.1; controls.maxDistance = maxd * 3
  if (groundMesh) groundMesh.position.y = box.min.y - 0.02
}

function loadModel(i = 0) {
  if (i >= MODELS.length) { status.value = 'بارگذاریِ مدل ناموفق بود (اتصال/CORS).'; return }
  const loader = new GLTFLoader()
  const draco = new DRACOLoader(); draco.setDecoderPath('https://www.gstatic.com/draco/v1/decoders/'); loader.setDRACOLoader(draco)
  loader.load(
    MODELS[i],
    (gltf) => {
      model = gltf.scene
      const mallMat = new THREE.MeshStandardMaterial({ color: 0xeceef5, roughness: 0.6, metalness: 0.05 })
      model.traverse(o => { if (o.isMesh) { o.castShadow = true; o.receiveShadow = true; o.material = mallMat } })
      scene.add(model)
      frame()
      status.value = '✅ مدلِ کاملِ سه‌بعدی بارگذاری شد — بچرخان یا «تورِ داخلی» را بزن.'
    },
    (xhr) => { if (xhr.total) status.value = `در حال بارگذاریِ مدل… ${Math.round((xhr.loaded / xhr.total) * 100)}%` },
    () => loadModel(i + 1),
  )
}

onMounted(() => {
  const wrap = canvasWrap.value, w = wrap.clientWidth, h = wrap.clientHeight
  scene = new THREE.Scene()
  scene.background = new THREE.Color(0x11141f)
  camera = new THREE.PerspectiveCamera(50, w / h, 0.1, 5000); camera.position.set(20, 12, 24)
  renderer = new THREE.WebGLRenderer({ antialias: true })
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2)); renderer.setSize(w, h)
  renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap
  renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = 0.92
  wrap.appendChild(renderer.domElement)
  scene.environment = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(), 0.04).texture

  controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true; controls.autoRotate = true; controls.autoRotateSpeed = 0.5

  scene.add(new THREE.HemisphereLight(0xeaf0ff, 0x30303a, 0.35))
  const dir = new THREE.DirectionalLight(0xfff1d8, 2.6); dir.position.set(30, 60, 24); dir.castShadow = true
  dir.shadow.mapSize.set(2048, 2048); Object.assign(dir.shadow.camera, { left: -60, right: 60, top: 60, bottom: -60, far: 300 }); dir.shadow.bias = -0.0005
  scene.add(dir)
  groundMesh = new THREE.Mesh(new THREE.PlaneGeometry(4000, 4000), new THREE.MeshStandardMaterial({ color: 0x0d1020, roughness: 1 }))
  groundMesh.rotation.x = -Math.PI / 2; groundMesh.receiveShadow = true; scene.add(groundMesh)

  loadModel()

  const loop = () => {
    raf = requestAnimationFrame(loop)
    if (view.value === 'walk' && model) {
      walkT = (walkT + 0.0010) % 1
      const tt = walkT < 0.5 ? walkT * 2 : (1 - walkT) * 2
      const longX = size.x >= size.z, span = (longX ? size.x : size.z) * 0.72
      const off = (tt - 0.5) * span, dir = walkT < 0.5 ? 1 : -1
      if (longX) { camera.position.set(center.x + off, eyeY, center.z); camera.lookAt(center.x + off + dir * 6, eyeY, center.z) }
      else { camera.position.set(center.x, eyeY, center.z + off); camera.lookAt(center.x, eyeY, center.z + dir * 6) }
    } else {
      controls.update()
    }
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
</script>

<template>
  <section class="map">
    <div class="hud">
      <div class="hud-top">
        <h2>مدلِ سه‌بعدیِ واقعیِ پاساژ</h2>
        <div class="src">
          <button :class="{ on: view === 'orbit' }" @click="setView('orbit')">🛰 مرور</button>
          <button :class="{ on: view === 'walk' }" @click="setView('walk')">🚶 تورِ داخلی</button>
        </div>
      </div>
      <p class="muted">{{ status }}</p>
    </div>
    <div ref="canvasWrap" class="canvas-wrap"></div>
  </section>
</template>

<style scoped>
.map { position: fixed; top: 56px; inset-inline: 0; bottom: 0; }
.canvas-wrap { position: absolute; inset: 0; }
.hud { position: absolute; top: 14px; inset-inline: 14px; z-index: 5; max-width: 560px; margin: 0 auto; background: rgba(12,15,34,.64); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.12); border-radius: 16px; padding: 12px 16px; color: #fff; }
.hud-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.hud h2 { color: #fff; font-size: 16px; margin: 0; }
.src { display: flex; gap: 6px; }
.src button { font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); color: #fff; padding: 6px 11px; border-radius: 999px; }
.src button.on { background: #7c3aed; border-color: #7c3aed; }
.hud .muted { color: rgba(255,255,255,.82); margin: 6px 0 0; font-size: 13px; }
</style>
