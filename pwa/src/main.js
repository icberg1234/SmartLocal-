import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { setMall } from './api'
import { useAuth } from './stores/auth'

const app = createApp(App)
app.use(createPinia())
app.use(router)

// Mall comes from the scanned QR link (?mall=ID), then persists.
const mall = new URLSearchParams(location.search).get('mall') || localStorage.getItem('mall') || '1'
localStorage.setItem('mall', mall)
setMall(mall)

useAuth().init()

app.mount('#app')
