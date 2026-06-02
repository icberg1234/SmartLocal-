<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'

const form = ref({
  brand: '',
  sms: { driver: 'fake', kavenegar_key: '' },
  payment: { driver: 'fake', zarinpal_merchant: '' },
})
const smsKeySet = ref(false)
const merchantSet = ref(false)
const msg = ref('')
const error = ref('')

function apply(s) {
  form.value.brand = s.brand || ''
  form.value.sms.driver = s.sms?.driver || 'fake'
  form.value.payment.driver = s.payment?.driver || 'fake'
  smsKeySet.value = !!s.sms?.kavenegar_key_set
  merchantSet.value = !!s.payment?.zarinpal_merchant_set
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/mall/settings')
    apply(data.data)
  } catch (e) {
    error.value = 'برای مدیریتِ تنظیمات باید با نقشِ مدیر وارد شوید.'
  }
})

async function save() {
  msg.value = ''
  error.value = ''
  try {
    const { data } = await api.put('/admin/mall/settings', form.value)
    apply(data.data)
    form.value.sms.kavenegar_key = ''
    form.value.payment.zarinpal_merchant = ''
    msg.value = 'ذخیره شد ✓'
  } catch (e) {
    error.value = e?.response?.data?.message || 'خطا در ذخیره'
  }
}
</script>

<template>
  <section>
    <h2>تنظیمات پاساژ</h2>

    <label>برند (در پیامکِ ورود نمایش داده می‌شود)</label>
    <input v-model="form.brand" placeholder="مثلاً پاساژ الماس" />

    <h3>پیامک (کدِ ورود)</h3>
    <label>درایور</label>
    <select v-model="form.sms.driver">
      <option value="fake">آزمایشی (fake)</option>
      <option value="kavenegar">کاوه‌نگار</option>
    </select>
    <label>کلید API <small v-if="smsKeySet">(تنظیم‌شده — برای حفظ، خالی بگذارید)</small></label>
    <input v-model="form.sms.kavenegar_key" :placeholder="smsKeySet ? '••••••••' : 'کلید کاوه‌نگار'" />

    <h3>درگاه پرداخت</h3>
    <label>درایور</label>
    <select v-model="form.payment.driver">
      <option value="fake">آزمایشی (fake)</option>
      <option value="zarinpal">زرین‌پال</option>
    </select>
    <label>مرچنت <small v-if="merchantSet">(تنظیم‌شده)</small></label>
    <input v-model="form.payment.zarinpal_merchant" :placeholder="merchantSet ? '••••••••' : 'کد مرچنت زرین‌پال'" />

    <button @click="save" style="margin-top:18px">ذخیره</button>
    <p v-if="msg" style="color:#2e7d32">{{ msg }}</p>
    <p v-if="error" class="err">{{ error }}</p>
  </section>
</template>

<style scoped>
label { display:block; margin:12px 0 4px; font-size:14px; color:#444; }
small { color:#888; }
select { padding:10px; border:1px solid #ccc; border-radius:8px; width:100%; box-sizing:border-box; background:#fff; }
h3 { margin-top:20px; color:#1565C0; }
.err { color:#c62828; }
</style>
