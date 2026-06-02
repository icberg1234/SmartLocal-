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
  } catch (e) {
    error.value = 'برای دریافت کد باید وارد شوید.'
  }
}

onMounted(() => {
  refresh()
  timer = setInterval(refresh, 55000) // rotate before the 60s TTL
})
onUnmounted(() => clearInterval(timer))
</script>

<template>
  <section style="text-align:center">
    <h2>کد تخفیف من</h2>
    <p>این کد را به فروشنده نشان دهید (هر دقیقه تازه می‌شود).</p>
    <div v-if="token" class="qr">{{ token }}</div>
    <p v-if="error" style="color:#c62828">{{ error }}</p>
  </section>
</template>

<style scoped>
.qr { background:#fff; padding:18px; border-radius:10px; word-break:break-all; font-family:monospace; font-size:12px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
</style>
