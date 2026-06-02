<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../../api'

const overview = ref(null)
const kpi = ref(null)
const error = ref('')
const fa = (n) => Number(n ?? 0).toLocaleString('fa-IR')

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/overview')
    overview.value = data.data
  } catch (e) {
    error.value = 'برای دیدنِ داشبورد باید با نقشِ مدیرِ پاساژ وارد شوید.'
  }
  try {
    const { data } = await api.get('/mall/analytics')
    kpi.value = data
  } catch (e) { /* needs manager role */ }
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

    <div class="section-title" style="margin-top:20px"><h3>شاخص‌های کلیدی · ۳۰ روزه</h3></div>
    <div class="stats">
      <div class="stat"><span class="lbl">مشتریانِ فعال</span><span class="val">{{ kpi ? fa(kpi.monthly_redeeming_customers) : '—' }}</span></div>
      <div class="stat"><span class="lbl">GMVِ تخفیف‌خورده</span><span class="val sm">{{ kpi ? fa(kpi.redeemed_gmv) : '—' }}<small> ت</small></span></div>
      <div class="stat"><span class="lbl">فروشگاه‌های فعال</span><span class="val">{{ kpi ? fa(kpi.active_redeeming_stores) : '—' }}</span></div>
      <div class="stat"><span class="lbl">نرخِ بازگشت</span><span class="val">{{ kpi ? fa(kpi.repeat_redemption_rate) : '—' }}<small>٪</small></span></div>
    </div>

    <div class="menu">
      <RouterLink to="/admin/settings"><span class="ic">⚙️</span><span>تنظیمات پاساژ (درگاه / پیامک / برند)</span><span class="arrow">‹</span></RouterLink>
      <RouterLink to="/admin/plans"><span class="ic">📦</span><span>مدیریت پکیج‌ها</span><span class="arrow">‹</span></RouterLink>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

<style scoped>
.stat .val.sm { font-size: 20px; }
.stat .val small { font-size: 12px; font-weight: 600; color: var(--muted); }
</style>
