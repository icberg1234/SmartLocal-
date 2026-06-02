<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const plans = ref([])
const form = ref({ key: '', name: '', price: 0, store_quota: 0, duration_days: 180 })
const msg = ref('')
const error = ref('')

async function load() {
  try {
    const { data } = await api.get('/plans')
    plans.value = data.data || []
  } catch (e) {
    error.value = 'خطا در دریافت پکیج‌ها'
  }
}
onMounted(load)

async function create() {
  msg.value = ''
  error.value = ''
  try {
    await api.post('/admin/plans', form.value)
    msg.value = 'پکیج ساخته شد ✓'
    await load()
  } catch (e) {
    error.value = e?.response?.data?.message || 'خطا (نیازمندِ نقشِ سوپرادمین)'
  }
}
</script>

<template>
  <section>
    <h2>مدیریت پکیج‌ها</h2>

    <ul class="plans">
      <li v-for="p in plans" :key="p.key" class="plan">
        <strong>{{ p.name }}</strong> — {{ p.store_quota }} فروشگاه · {{ p.duration_days }} روز ·
        {{ Number(p.price).toLocaleString() }} تومان
      </li>
      <li v-if="!plans.length" class="plan">پکیجی یافت نشد.</li>
    </ul>

    <h3>پکیجِ جدید (سوپرادمین)</h3>
    <input v-model="form.key" placeholder="کلید (مثلاً platinum)" />
    <input v-model="form.name" placeholder="نام نمایشی" style="margin-top:8px" />
    <input v-model.number="form.price" type="number" placeholder="قیمت (تومان)" style="margin-top:8px" />
    <input v-model.number="form.store_quota" type="number" placeholder="سهمیه فروشگاه" style="margin-top:8px" />
    <input v-model.number="form.duration_days" type="number" placeholder="مدت (روز)" style="margin-top:8px" />
    <button @click="create" style="margin-top:12px">ساخت پکیج</button>

    <p v-if="msg" style="color:#2e7d32">{{ msg }}</p>
    <p v-if="error" class="err">{{ error }}</p>
  </section>
</template>

<style scoped>
.plans { list-style:none; padding:0; margin-bottom:8px; }
.plan { background:#fff; border-radius:8px; padding:12px; margin-bottom:8px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
h3 { margin-top:20px; color:#1565C0; }
.err { color:#c62828; }
</style>
