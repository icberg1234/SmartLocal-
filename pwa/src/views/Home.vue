<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../api'

const points = ref(0)
const tier = ref('bronze')
const stores = ref([])

const tierFa = { bronze: 'برنزی', silver: 'نقره‌ای', gold: 'طلایی' }

onMounted(async () => {
  try {
    const { data } = await api.get('/me/points')
    points.value = data.balance
    tier.value = data.tier
  } catch (e) { /* not logged in */ }

  try {
    const { data } = await api.get('/stores')
    stores.value = data.data || []
  } catch (e) { /* ignore */ }
})
</script>

<template>
  <section>
    <div class="points">
      <span class="plabel">امتیازِ کلِ پاساژ</span>
      <div class="pval">{{ Number(points).toLocaleString() }}</div>
      <span class="ptier">سطح: {{ tierFa[tier] || tier }}</span>
    </div>

    <div class="section-title">
      <h3>فروشگاه‌ها</h3>
      <RouterLink class="link" to="/my-qr">کد تخفیف →</RouterLink>
    </div>

    <div class="list">
      <div v-for="s in stores" :key="s.id" class="row-item">
        <div class="ava">🏬</div>
        <div class="body">
          <b>{{ s.name }}</b>
          <span v-if="s.member_discount_pct" class="badge">{{ s.member_discount_pct }}٪ تخفیفِ عضو</span>
        </div>
      </div>
      <div v-if="!stores.length" class="empty">فروشگاهی برای نمایش نیست.</div>
    </div>
  </section>
</template>

<style scoped>
.points { background: var(--grad); color: #fff; border-radius: var(--radius); padding: 24px; text-align: center; box-shadow: var(--shadow-primary); margin-bottom: 20px; }
.plabel { font-size: 13px; color: rgba(255, 255, 255, .85); }
.pval { font-size: 44px; font-weight: 800; line-height: 1.1; margin: 4px 0; }
.ptier { font-size: 14px; color: rgba(255, 255, 255, .92); }
</style>
