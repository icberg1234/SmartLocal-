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
  </section>
</template>
