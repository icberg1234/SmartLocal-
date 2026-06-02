<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const router = useRouter()

const phone = ref('')
const code = ref('')
const step = ref('phone')
const error = ref('')
const loading = ref(false)
const isDev = import.meta.env.DEV

async function sendOtp() {
  error.value = ''
  loading.value = true
  try {
    await auth.requestOtp(phone.value)
    step.value = 'code'
  } catch (e) {
    error.value = e?.response?.data?.message || 'خطا در ارسال کد'
  } finally {
    loading.value = false
  }
}

async function verify() {
  error.value = ''
  loading.value = true
  try {
    await auth.verifyOtp(phone.value, code.value)
    router.push('/home')
  } catch (e) {
    error.value = e?.response?.data?.message || 'کد نادرست است'
  } finally {
    loading.value = false
  }
}

async function demo(devPhone, to) {
  error.value = ''
  loading.value = true
  try {
    await auth.devLogin(devPhone)
    router.push(to)
  } catch (e) {
    error.value = 'ورودِ آزمایشی ناموفق — آیا «php artisan db:seed» اجرا شده؟'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section>
    <h1 class="h-title">ورود به حساب</h1>
    <p class="sub">{{ step === 'phone' ? 'شماره موبایلت را وارد کن تا کدِ یک‌بارمصرف بفرستیم.' : 'کدِ ۶ رقمیِ پیامک‌شده را وارد کن.' }}</p>

    <div class="card">
      <template v-if="step === 'phone'">
        <div class="field">
          <label>شماره موبایل</label>
          <input class="input" v-model="phone" placeholder="09xxxxxxxxx" inputmode="numeric" dir="ltr" style="text-align:right" />
        </div>
        <button class="btn btn--block" :disabled="loading" @click="sendOtp">{{ loading ? '…' : 'ارسال کد' }}</button>
      </template>

      <template v-else>
        <div class="field">
          <label>کدِ تأیید</label>
          <input class="input otp" v-model="code" placeholder="••••••" inputmode="numeric" dir="ltr" maxlength="6" />
        </div>
        <button class="btn btn--block" :disabled="loading" @click="verify">{{ loading ? '…' : 'تأیید و ورود' }}</button>
        <button class="btn btn--ghost btn--block" style="margin-top:10px" @click="step = 'phone'">تغییر شماره</button>
      </template>

      <p v-if="error" class="error">{{ error }}</p>
    </div>

    <div v-if="isDev" class="card demo">
      <div class="demo-h">⚡ ورودِ سریعِ آزمایشی <small class="muted">(بدون پیامک)</small></div>
      <button class="btn btn--ghost btn--block" :disabled="loading" @click="demo('09120000001', '/home')">👤 ورود به‌عنوان مشتری</button>
      <button class="btn btn--ghost btn--block" :disabled="loading" style="margin-top:8px" @click="demo('09120000002', '/admin')">🧑‍💼 ورود به‌عنوان مدیر پاساژ</button>
      <button class="btn btn--ghost btn--block" :disabled="loading" style="margin-top:8px" @click="demo('09120000003', '/redeem')">🏬 ورود به‌عنوان فروشنده (صندوق)</button>
    </div>
  </section>
</template>

<style scoped>
.demo { margin-top: 14px; }
.demo-h { font-weight: 700; margin-bottom: 12px; }
</style>
