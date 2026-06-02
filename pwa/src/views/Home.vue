<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../api'

const points = ref(0)
const tier = ref('bronze')
const stores = ref([])
const tierFa = { bronze: 'برنزی', silver: 'نقره‌ای', gold: 'طلایی' }

// category_id -> media keywords + intro copy
const CAT = {
  1: { kw: 'restaurant,food,kebab', intro: 'غذای اصیل و گرم، سرو در فضایی دنج.' },
  2: { kw: 'fashion,boutique,clothing', intro: 'جدیدترین کالکشنِ مد و پوشاک.' },
  3: { kw: 'electronics,gadgets,technology', intro: 'جدیدترین گجت‌ها و لوازمِ دیجیتال.' },
}
const cat = (s) => CAT[s.category_id] || { kw: 'shop,store', intro: 'فروشگاهی در پاساژ.' }
const img = (s) => `https://loremflickr.com/640/420/${cat(s).kw}?lock=${s.id}`
const onImgErr = (e, s) => { e.target.src = `https://picsum.photos/seed/sl${s.id}/640/420` }

onMounted(async () => {
  try { const { data } = await api.get('/me/points'); points.value = data.balance; tier.value = data.tier } catch (e) { /* guest */ }
  try { const { data } = await api.get('/stores'); stores.value = data.data || [] } catch (e) { /* ignore */ }
})
</script>

<template>
  <section>
    <div class="points">
      <span class="plabel">امتیازِ کلِ پاساژ</span>
      <div class="pval">{{ Number(points).toLocaleString() }}</div>
      <span class="ptier">سطح: {{ tierFa[tier] || tier }}</span>
    </div>

    <!-- cinematic spotlight (video) -->
    <div class="spotlight">
      <video class="sp-media" autoplay muted loop playsinline
             poster="https://picsum.photos/seed/slfest/900/500">
        <source src="https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4" type="video/mp4" />
      </video>
      <div class="sp-overlay">
        <span class="sp-badge">🎉 جشنوارهٔ این هفته</span>
        <h3>تا ۳۰٪ تخفیفِ عضو در کلِ پاساژ</h3>
        <RouterLink to="/map" class="sp-cta">مسیرِ فروشگاه‌ها →</RouterLink>
      </div>
    </div>

    <div class="section-title">
      <h3>فروشگاه‌ها</h3>
      <RouterLink class="link" to="/map">نقشهٔ سه‌بعدی →</RouterLink>
    </div>

    <div class="showcase">
      <article v-for="s in stores" :key="s.id" class="scard">
        <div class="scard-media">
          <img :src="img(s)" @error="(e) => onImgErr(e, s)" :alt="s.name" loading="lazy" />
          <span v-if="s.member_discount_pct" class="scard-badge">{{ s.member_discount_pct }}٪ تخفیفِ عضو</span>
        </div>
        <div class="scard-body">
          <b>{{ s.name }}</b>
          <p>{{ cat(s).intro }}</p>
          <RouterLink to="/map" class="scard-go">🧭 مسیریابی</RouterLink>
        </div>
      </article>
      <div v-if="!stores.length" class="empty">فروشگاهی برای نمایش نیست.</div>
    </div>
  </section>
</template>

<style scoped>
.points { background: var(--grad); color: #fff; border-radius: var(--radius); padding: 22px; text-align: center; box-shadow: var(--shadow-primary); margin-bottom: 18px; }
.plabel { font-size: 13px; color: rgba(255, 255, 255, .85); }
.pval { font-size: 42px; font-weight: 800; line-height: 1.1; margin: 4px 0; }
.ptier { font-size: 14px; color: rgba(255, 255, 255, .92); }

.spotlight { position: relative; border-radius: var(--radius); overflow: hidden; height: 190px; margin-bottom: 20px; box-shadow: var(--shadow); }
.sp-media { width: 100%; height: 100%; object-fit: cover; display: block; }
.sp-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: flex-end; gap: 8px; padding: 16px; background: linear-gradient(to top, rgba(10,12,30,.86), rgba(10,12,30,.15) 60%, transparent); color: #fff; }
.sp-badge { align-self: flex-start; background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.3); padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; backdrop-filter: blur(4px); }
.sp-overlay h3 { color: #fff; font-size: 19px; margin: 0; text-shadow: 0 2px 10px rgba(0,0,0,.5); }
.sp-cta { align-self: flex-start; background: #fff; color: var(--primary); font-weight: 800; padding: 9px 16px; border-radius: 12px; font-size: 14px; }

.showcase { display: flex; flex-direction: column; gap: 14px; }
.scard { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); }
.scard-media { position: relative; height: 168px; overflow: hidden; background: var(--grad); }
.scard-media img { width: 100%; height: 100%; object-fit: cover; display: block; animation: kenburns 14s ease-in-out infinite alternate; }
@keyframes kenburns { from { transform: scale(1) translate(0, 0); } to { transform: scale(1.14) translate(-2%, -2%); } }
.scard-badge { position: absolute; top: 12px; inset-inline-start: 12px; background: rgba(21,163,74,.95); color: #fff; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 999px; box-shadow: 0 4px 12px rgba(0,0,0,.25); }
.scard-body { padding: 14px 16px 16px; }
.scard-body b { font-size: 17px; }
.scard-body p { color: var(--muted); font-size: 14px; margin: 4px 0 12px; }
.scard-go { display: inline-block; background: var(--primary-50); color: var(--primary); font-weight: 700; font-size: 14px; padding: 9px 16px; border-radius: 12px; }
.empty { text-align: center; color: var(--muted); padding: 26px 0; }
</style>
