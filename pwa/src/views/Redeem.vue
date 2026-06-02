<script setup>
import { ref } from 'vue'
import api from '../api'

const token = ref('')
const amount = ref(null)
const result = ref(null)
const error = ref('')
const loading = ref(false)

async function redeem() {
  error.value = ''
  result.value = null
  loading.value = true
  try {
    const { data } = await api.post('/redemptions', { redeem_token: token.value.trim(), amount: Number(amount.value) })
    result.value = data.data
  } catch (e) {
    const errs = e?.response?.data?.errors
    error.value = e?.response?.data?.message || (errs && Object.values(errs)[0]?.[0]) || 'خطا در ثبتِ تخفیف'
  } finally {
    loading.value = false
  }
}
function reset() { token.value = ''; amount.value = null; result.value = null; error.value = '' }
const fa = (n) => Number(n || 0).toLocaleString('fa-IR')
</script>

<template>
  <section>
    <h1 class="h-title">صندوقِ فروشگاه</h1>
    <p class="sub">کدِ مشتری را اسکن/وارد کن و مبلغِ خرید را بزن.</p>

    <div v-if="!result" class="card">
      <div class="field">
        <label>کدِ تخفیفِ مشتری (QR)</label>
        <textarea class="input mono" v-model="token" rows="3" placeholder="کدِ روی گوشیِ مشتری را اینجا بزن" dir="ltr"></textarea>
      </div>
      <div class="field">
        <label>مبلغِ خرید (تومان)</label>
        <input class="input" v-model.number="amount" type="number" inputmode="numeric" dir="ltr" placeholder="مثلاً ۲۴۰۰۰۰۰" />
      </div>
      <button class="btn btn--block" :disabled="loading" @click="redeem">{{ loading ? '…' : 'ثبتِ خرید و تخفیف' }}</button>
      <p v-if="error" class="error">{{ error }}</p>
    </div>

    <div v-else class="card receipt">
      <div class="ok-badge">✅ تخفیف ثبت شد</div>
      <div class="row"><span>مبلغِ خرید</span><b>{{ fa(result.amount) }} ت</b></div>
      <div class="row"><span>تخفیفِ عضو ({{ fa(result.discount_pct) }}٪)</span><b class="minus">−{{ fa(result.discount_amount) }} ت</b></div>
      <div class="row total"><span>مبلغِ نهایی</span><b>{{ fa(result.final_amount) }} ت</b></div>
      <div class="row points"><span>امتیازِ مشتری</span><b>+{{ fa(result.points_awarded) }} ★</b></div>
      <button class="btn btn--block" style="margin-top:18px" @click="reset">خریدِ بعدی</button>
    </div>
  </section>
</template>

<style scoped>
.field label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 7px; font-weight: 600; }
textarea.input { width: 100%; resize: vertical; box-sizing: border-box; }
.mono { font-family: ui-monospace, 'Courier New', monospace; font-size: 12px; }
.receipt .ok-badge { background: #e9f8ef; color: var(--success); font-weight: 800; text-align: center; padding: 11px; border-radius: 12px; margin-bottom: 14px; }
.receipt .row { display: flex; justify-content: space-between; align-items: center; padding: 11px 2px; border-bottom: 1px dashed var(--border); font-size: 15px; }
.receipt .row.total { border-bottom: 0; font-size: 19px; font-weight: 800; }
.receipt .row.total b { color: var(--primary); }
.receipt .minus { color: var(--danger); }
.receipt .points b { color: var(--success); }
</style>
