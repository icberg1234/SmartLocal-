<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <section>
    <div class="hero">
      <span class="pill">🛍️ پاساژِ هوشمند</span>
      <h1>به پاساژ خوش آمدید</h1>
      <p>عضو شو، سرِ صندوق تخفیفِ عضو بگیر، امتیازِ کل‌پاساژ جمع کن و جشنواره‌ها را از دست نده.</p>
      <RouterLink to="/login" class="btn btn--light btn--block">ورود / عضویت</RouterLink>
    </div>

    <div class="feats">
      <div class="feat"><div class="fic">٪</div><b>تخفیفِ عضو</b><span>سرِ صندوق</span></div>
      <div class="feat"><div class="fic">★</div><b>امتیاز</b><span>کلِ پاساژ</span></div>
      <div class="feat"><div class="fic">🎉</div><b>جشنواره</b><span>اعلانِ زنده</span></div>
    </div>
  </section>
</template>

<style scoped>
.feats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 18px; }
.feat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 16px 8px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 3px; box-shadow: var(--shadow-sm); }
.feat .fic { width: 40px; height: 40px; border-radius: 12px; background: var(--primary-50); color: var(--primary); display: grid; place-items: center; font-size: 19px; font-weight: 800; margin-bottom: 4px; }
.feat b { font-size: 14px; }
.feat span { font-size: 12px; color: var(--muted); }
</style>
