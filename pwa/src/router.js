import { createRouter, createWebHistory } from 'vue-router'
import Landing from './views/Landing.vue'
import Login from './views/Login.vue'
import Home from './views/Home.vue'
import MyQr from './views/MyQr.vue'
import AdminDashboard from './views/admin/AdminDashboard.vue'
import AdminSettings from './views/admin/AdminSettings.vue'
import AdminPlans from './views/admin/AdminPlans.vue'

const routes = [
    { path: '/', name: 'landing', component: Landing },
    { path: '/login', name: 'login', component: Login },
    { path: '/home', name: 'home', component: Home },
    { path: '/my-qr', name: 'my-qr', component: MyQr },
    { path: '/admin', name: 'admin', component: AdminDashboard },
    { path: '/admin/settings', name: 'admin-settings', component: AdminSettings },
    { path: '/admin/plans', name: 'admin-plans', component: AdminPlans },
]

export default createRouter({
    history: createWebHistory(),
    routes,
})
