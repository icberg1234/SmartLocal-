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
    <h1 class="h-title">تنظیمات پاساژ</h1>
    <p class="sub">برند و سرویس‌های اختصاصیِ پاساژت را اینجا تنظیم کن.</p>

    <div class="card">
      <div class="field" style="margin-bottom:0">
        <label>برند (در پیامکِ ورود نمایش داده می‌شود)</label>
        <input class="input" v-model="form.brand" placeholder="مثلاً پاساژ الماس" />
      </div>
    </div>

    <div class="card">
      <h3 class="card-h">📩 پیامک (کدِ ورود)</h3>
      <div class="field">
        <label>سرویس‌دهنده</label>
        <select class="input" v-model="form.sms.driver">
          <option value="fake">آزمایشی (fake)</option>
          <option value="kavenegar">کاوه‌نگار</option>
        </select>
      </div>
      <div class="field" style="margin-bottom:0">
        <label>کلید API <small v-if="smsKeySet">(تنظیم‌شده — برای حفظ، خالی بگذار)</small></label>
        <input class="input" v-model="form.sms.kavenegar_key" :placeholder="smsKeySet ? '••••••••' : 'کلید کاوه‌نگار'" dir="ltr" />
      </div>
    </div>

    <div class="card">
      <h3 class="card-h">💳 درگاه پرداخت</h3>
      <div class="field">
        <label>سرویس‌دهنده</label>
        <select class="input" v-model="form.payment.driver">
          <option value="fake">آزمایشی (fake)</option>
          <option value="zarinpal">زرین‌پال</option>
        </select>
      </div>
      <div class="field" style="margin-bottom:0">
        <label>مرچنت <small v-if="merchantSet">(تنظیم‌شده)</small></label>
        <input class="input" v-model="form.payment.zarinpal_merchant" :placeholder="merchantSet ? '••••••••' : 'کد مرچنت زرین‌پال'" dir="ltr" />
      </div>
    </div>

    <button class="btn btn--block" style="margin-top:18px" @click="save">ذخیره تغییرات</button>
    <p v-if="msg" class="ok">{{ msg }}</p>
    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

<style scoped>
.card-h { font-size: 16px; margin-bottom: 14px; color: var(--text); }
</style>
