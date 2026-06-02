<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../api'

const overview = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/overview')
    overview.value = data.data
  } catch (e) {
    error.value = 'برای دیدنِ داشبورد باید با نقشِ مدیرِ پاساژ وارد شوید.'
  }
})
</script>

<template>
  <section>
    <h2>داشبورد مدیر پاساژ</h2>

    <div class="grid">
      <div class="card"><span>پکیج</span><strong>{{ overview?.plan || '—' }}</strong></div>
      <div class="card"><span>سهمیه فروشگاه</span><strong>{{ overview?.quota ?? '—' }}</strong></div>
      <div class="card"><span>مصرف‌شده</span><strong>{{ overview?.stores_used ?? '—' }}</strong></div>
      <div class="card"><span>باقی‌مانده</span><strong>{{ overview?.stores_remaining ?? '—' }}</strong></div>
    </div>

    <nav class="links">
      <RouterLink to="/admin/settings">⚙️ تنظیمات پاساژ (درگاه / پیامک / برند)</RouterLink>
      <RouterLink to="/admin/plans">📦 مدیریت پکیج‌ها</RouterLink>
    </nav>

    <p v-if="error" class="err">{{ error }}</p>
  </section>
</template>

<style scoped>
.grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px; }
.card { background:#fff; border-radius:10px; padding:14px; box-shadow:0 1px 4px rgba(0,0,0,.08); display:flex; flex-direction:column; gap:6px; }
.card span { color:#666; font-size:13px; }
.card strong { font-size:22px; color:#1565C0; }
.links { display:flex; flex-direction:column; gap:10px; }
.links a { background:#fff; border-radius:8px; padding:12px; text-decoration:none; color:#1565C0; box-shadow:0 1px 4px rgba(0,0,0,.08); }
.err { color:#c62828; margin-top:12px; }
</style>
