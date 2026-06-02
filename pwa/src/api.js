import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api/v1',
})

export function setMall(mallId) {
    api.defaults.headers.common['X-Mall-Id'] = mallId
}

export function setToken(token) {
    if (token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`
    } else {
        delete api.defaults.headers.common['Authorization']
    }
}

export default api
