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

async function sendOtp() {
  error.value = ''
  try {
    await auth.requestOtp(phone.value)
    step.value = 'code'
  } catch (e) {
    error.value = e?.response?.data?.message || 'خطا در ارسال کد'
  }
}

async function verify() {
  error.value = ''
  try {
    await auth.verifyOtp(phone.value, code.value)
    router.push('/home')
  } catch (e) {
    error.value = e?.response?.data?.message || 'کد نادرست است'
  }
}
</script>

<template>
  <section>
    <h2>ورود با شماره موبایل</h2>
    <template v-if="step === 'phone'">
      <input v-model="phone" placeholder="09xxxxxxxxx" inputmode="numeric" />
      <button @click="sendOtp" style="margin-top:12px">ارسال کد</button>
    </template>
    <template v-else>
      <input v-model="code" placeholder="کد ۶ رقمی" inputmode="numeric" />
      <button @click="verify" style="margin-top:12px">تأیید</button>
    </template>
    <p v-if="error" style="color:#c62828">{{ error }}</p>
  </section>
</template>
