import { createRouter, createWebHistory } from 'vue-router'
import Landing from './views/Landing.vue'
import Login from './views/Login.vue'
import Home from './views/Home.vue'
import MyQr from './views/MyQr.vue'

const routes = [
    { path: '/', name: 'landing', component: Landing },
    { path: '/login', name: 'login', component: Login },
    { path: '/home', name: 'home', component: Home },
    { path: '/my-qr', name: 'my-qr', component: MyQr },
]

export default createRouter({
    history: createWebHistory(),
    routes,
})
