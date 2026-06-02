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
    <h1 class="h-title">داشبورد مدیر</h1>
    <p class="sub">وضعیتِ پکیج و سهمیه‌ی پاساژِ شما.</p>

    <div class="stats">
      <div class="stat"><span class="lbl">پکیج</span><span class="val">{{ overview?.plan || '—' }}</span></div>
      <div class="stat"><span class="lbl">سهمیه فروشگاه</span><span class="val">{{ overview?.quota ?? '—' }}</span></div>
      <div class="stat"><span class="lbl">مصرف‌شده</span><span class="val">{{ overview?.stores_used ?? '—' }}</span></div>
      <div class="stat"><span class="lbl">باقی‌مانده</span><span class="val">{{ overview?.stores_remaining ?? '—' }}</span></div>
    </div>

    <div class="menu">
      <RouterLink to="/admin/settings"><span class="ic">⚙️</span><span>تنظیمات پاساژ (درگاه / پیامک / برند)</span><span class="arrow">‹</span></RouterLink>
      <RouterLink to="/admin/plans"><span class="ic">📦</span><span>مدیریت پکیج‌ها</span><span class="arrow">‹</span></RouterLink>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>
