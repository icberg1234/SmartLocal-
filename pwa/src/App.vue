<script setup>
import { RouterView, RouterLink, useRouter } from 'vue-router'
import { useAuth } from './stores/auth'

const auth = useAuth()
const router = useRouter()

function logout() {
  auth.logout()
  router.push('/')
}
</script>

<template>
  <div class="app" dir="rtl">
    <header class="topbar">
      <RouterLink to="/" class="brand">SmartLocal</RouterLink>
      <button v-if="auth.isAuthed" class="chip" @click="logout">خروج</button>
      <RouterLink v-else to="/login" class="chip">ورود</RouterLink>
    </header>

    <main class="content">
      <RouterView />
    </main>

    <nav class="bottomnav">
      <RouterLink to="/home"><span class="ic">🏠</span><span>خانه</span></RouterLink>
      <RouterLink to="/map"><span class="ic">🗺️</span><span>نقشه</span></RouterLink>
      <RouterLink to="/my-qr"><span class="ic">🎟️</span><span>کد تخفیف</span></RouterLink>
      <RouterLink to="/redeem"><span class="ic">🧾</span><span>صندوق</span></RouterLink>
      <RouterLink to="/admin"><span class="ic">🧑‍💼</span><span>پنل</span></RouterLink>
    </nav>
  </div>
</template>
