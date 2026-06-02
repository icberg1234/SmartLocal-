<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const points = ref(0)
const tier = ref('bronze')
const stores = ref([])

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
    <div class="card">
      <strong>{{ points }}</strong> امتیاز · سطح: {{ tier }}
    </div>
    <h3>فروشگاه‌ها</h3>
    <ul class="stores">
      <li v-for="s in stores" :key="s.id" class="store">
        {{ s.name }} <span v-if="s.member_discount_pct">— {{ s.member_discount_pct }}٪ تخفیف عضو</span>
      </li>
      <li v-if="!stores.length">فروشگاهی یافت نشد.</li>
    </ul>
  </section>
</template>

<style scoped>
.card { background:#fff; border-radius:10px; padding:14px; box-shadow:0 1px 4px rgba(0,0,0,.08); margin-bottom:16px; }
.stores { list-style:none; padding:0; }
.store { background:#fff; border-radius:8px; padding:10px; margin-bottom:8px; }
</style>
