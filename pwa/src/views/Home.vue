<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../api'

const points = ref(0)
const tier = ref('bronze')
const tierFa = { bronze: 'برنزی', silver: 'نقره‌ای', gold: 'طلایی' }

// Brand stores — tapping a card opens the 3D map and routes to that shop.
const STORES = [
  { key: 'hm', name: 'H&M', slug: 'handm', kw: 'fashion,clothing,store', discount: 15, intro: 'مدِ روز با قیمتِ مناسب.' },
  { key: 'zara', name: 'Zara', slug: 'zara', kw: 'boutique,fashion', discount: 12, intro: 'کالکشنِ روزِ پوشاک.' },
  { key: 'nike', name: 'Nike', slug: 'nike', kw: 'sneakers,sportswear', discount: 10, intro: 'کفش و پوشاکِ ورزشی.' },
  { key: 'apple', name: 'Apple', slug: 'apple', kw: 'apple-store,gadgets', discount: 5, intro: 'جدیدترین محصولاتِ اپل.' },
  { key: 'samsung', name: 'Samsung', slug: 'samsung', kw: 'electronics,smartphone', discount: 8, intro: 'موبایل و لوازمِ دیجیتال.' },
  { key: 'starbucks', name: 'Starbucks', slug: 'starbucks', kw: 'coffee,cafe', discount: 10, intro: 'قهوه و نوشیدنی.' },
  { key: 'mcdonalds', name: "McDonald's", slug: 'mcdonalds', kw: 'burger,fastfood', discount: 0, intro: 'فست‌فودِ سریع.' },
  { key: 'xiaomi', name: 'Xiaomi', slug: 'xiaomi', kw: 'gadgets,smarthome', discount: 7, intro: 'گجت و خانهٔ هوشمند.' },
]
const img = (s) => `https://loremflickr.com/640/420/${s.kw}?lock=${s.key}`
const onImgErr = (e, s) => { e.target.src = `https://picsum.photos/seed/${s.key}/640/420` }

onMounted(async () => {
  try { const { data } = await api.get('/me/points'); points.value = data.balance; tier.value = data.tier } catch (e) { /* guest */ }
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
      <RouterLink v-for="s in STORES" :key="s.key" class="scard" :to="{ path: '/map', query: { store: s.key } }">
        <div class="scard-media">
          <img :src="img(s)" @error="(e) => onImgErr(e, s)" :alt="s.name" loading="lazy" />
          <img class="scard-logo" :src="`https://cdn.simpleicons.org/${s.slug}`" :alt="s.name" @error="(e) => (e.target.style.display = 'none')" />
          <span v-if="s.discount" class="scard-badge">{{ s.discount }}٪ تخفیفِ عضو</span>
        </div>
        <div class="scard-body">
          <b>{{ s.name }}</b>
          <p>{{ s.intro }}</p>
          <span class="scard-go">🧭 مسیریابی در نقشهٔ سه‌بعدی →</span>
        </div>
      </RouterLink>
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
.scard { display: block; color: inherit; text-decoration: none; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow-sm); }
.scard-media { position: relative; height: 168px; overflow: hidden; background: var(--grad); }
.scard-logo { position: absolute; top: 12px; inset-inline-end: 12px; width: 34px; height: 34px; object-fit: contain; background: #fff; border-radius: 9px; padding: 5px; box-shadow: 0 2px 8px rgba(0,0,0,.22); }
.scard-media img { width: 100%; height: 100%; object-fit: cover; display: block; animation: kenburns 14s ease-in-out infinite alternate; }
@keyframes kenburns { from { transform: scale(1) translate(0, 0); } to { transform: scale(1.14) translate(-2%, -2%); } }
.scard-badge { position: absolute; top: 12px; inset-inline-start: 12px; background: rgba(21,163,74,.95); color: #fff; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 999px; box-shadow: 0 4px 12px rgba(0,0,0,.25); }
.scard-body { padding: 14px 16px 16px; }
.scard-body b { font-size: 17px; }
.scard-body p { color: var(--muted); font-size: 14px; margin: 4px 0 12px; }
.scard-go { display: inline-block; background: var(--primary-50); color: var(--primary); font-weight: 700; font-size: 14px; padding: 9px 16px; border-radius: 12px; }
.empty { text-align: center; color: var(--muted); padding: 26px 0; }
</style>
