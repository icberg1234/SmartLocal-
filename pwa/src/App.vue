<script setup>
import { RouterView, RouterLink } from 'vue-router'
import { useAuth } from './stores/auth'

const auth = useAuth()
</script>

<template>
  <div class="app" dir="rtl">
    <header class="bar">
      <RouterLink to="/" class="brand">SmartLocal</RouterLink>
      <nav>
        <RouterLink v-if="auth.isAuthed" to="/home">خانه</RouterLink>
        <RouterLink v-if="auth.isAuthed" to="/my-qr">کد تخفیف</RouterLink>
        <RouterLink v-else to="/login">ورود</RouterLink>
      </nav>
    </header>
    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style>
body { margin: 0; font-family: Tahoma, sans-serif; background: #f4f6f8; }
.bar { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #1565C0; color: #fff; }
.brand { color: #fff; text-decoration: none; font-weight: bold; }
.bar nav a { color: #fff; margin-inline-start: 12px; text-decoration: none; }
.content { padding: 16px; max-width: 680px; margin: 0 auto; }
button { background: #1565C0; color: #fff; border: 0; padding: 10px 16px; border-radius: 8px; font: inherit; }
input { padding: 10px; border: 1px solid #ccc; border-radius: 8px; width: 100%; box-sizing: border-box; }
</style>
