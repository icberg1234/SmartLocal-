<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="landing">
    <h1>به پاساژ خوش آمدید 👋</h1>
    <p>عضو شوید تا تخفیف فروشگاه‌ها و امتیاز جمع کنید.</p>
    <RouterLink to="/login"><button>ورود / عضویت</button></RouterLink>
  </section>
</template>

<style scoped>
.landing { text-align: center; padding-top: 40px; }
</style>
