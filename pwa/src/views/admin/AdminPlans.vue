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
    <h1 class="h-title">مدیریت پکیج‌ها</h1>
    <p class="sub">پکیج‌های قابلِ‌فروش به مدیرانِ پاساژ.</p>

    <div class="list">
      <div v-for="p in plans" :key="p.key" class="row-item">
        <div class="ava">📦</div>
        <div class="body">
          <b>{{ p.name }}</b>
          <span class="muted" style="font-size:13px">{{ p.store_quota }} فروشگاه · {{ p.duration_days }} روز · {{ Number(p.price).toLocaleString() }} تومان</span>
        </div>
      </div>
      <div v-if="!plans.length" class="empty">پکیجی یافت نشد.</div>
    </div>

    <div class="card" style="margin-top:18px">
      <h3 class="card-h">پکیجِ جدید <small class="muted">(سوپرادمین)</small></h3>
      <div class="field"><label>کلید</label><input class="input" v-model="form.key" placeholder="platinum" dir="ltr" /></div>
      <div class="field"><label>نام نمایشی</label><input class="input" v-model="form.name" placeholder="پکیج پلاتینیوم" /></div>
      <div class="grid2">
        <div class="field"><label>قیمت (تومان)</label><input class="input" v-model.number="form.price" type="number" dir="ltr" /></div>
        <div class="field"><label>سهمیه فروشگاه</label><input class="input" v-model.number="form.store_quota" type="number" dir="ltr" /></div>
      </div>
      <div class="field"><label>مدت (روز)</label><input class="input" v-model.number="form.duration_days" type="number" dir="ltr" /></div>
      <button class="btn btn--block" @click="create">ساختِ پکیج</button>
      <p v-if="msg" class="ok">{{ msg }}</p>
      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </section>
</template>

<style scoped>
.card-h { font-size: 16px; margin-bottom: 14px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
</style>
