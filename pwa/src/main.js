import './styles.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { setMall } from './api'
import { useAuth } from './stores/auth'

async function boot() {
  // Dev demo: mock the backend so the app is fully clickable with no server.
  // Disable with: localStorage.setItem('real', '1')
  if (import.meta.env.DEV && localStorage.getItem('real') !== '1') {
    const { installMock } = await import('./mock')
    installMock()
  }

  const app = createApp(App)
  app.use(createPinia())
  app.use(router)

  // Mall comes from the scanned QR link (?mall=ID), then persists.
  const mall = new URLSearchParams(location.search).get('mall') || localStorage.getItem('mall') || '1'
  localStorage.setItem('mall', mall)
  setMall(mall)

  useAuth().init()

  app.mount('#app')
}

boot()
