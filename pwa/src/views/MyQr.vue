<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '../api'

const token = ref('')
const error = ref('')
let timer = null

async function refresh() {
  try {
    const { data } = await api.get('/me/redeem-token')
    token.value = data.token
    error.value = ''
  } catch (e) {
    error.value = 'برای دریافت کد باید وارد شوید.'
  }
}

onMounted(() => {
  refresh()
  timer = setInterval(refresh, 55000)
})
onUnmounted(() => clearInterval(timer))
</script>

<template>
  <section style="text-align:center">
    <h1 class="h-title">کد تخفیف من</h1>
    <p class="sub">این کد را سرِ صندوق به فروشنده نشان بده.</p>

    <div class="qr-card">
      <div class="qr-frame">
        <span v-if="token" class="qr-code">{{ token }}</span>
        <span v-else class="muted">در حال دریافت…</span>
      </div>
      <span class="qr-hint">⏱️ هر دقیقه به‌صورت خودکار تازه می‌شود</span>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

<style scoped>
.qr-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; align-items: center; gap: 14px; }
.qr-frame { width: 100%; min-height: 120px; border: 2px dashed var(--primary); border-radius: var(--radius-sm); background: var(--primary-50); display: grid; place-items: center; padding: 18px; }
.qr-code { font-family: ui-monospace, 'Courier New', monospace; font-size: 13px; word-break: break-all; color: var(--primary-600); direction: ltr; }
.qr-hint { font-size: 13px; color: var(--muted); }
</style>
