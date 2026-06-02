// Dev-only mock backend: lets the whole PWA run with no server.
// Activated from main.js when import.meta.env.DEV && localStorage.real !== '1'.
// In production builds this module is never imported (tree-shaken).
import api from './api'

const ROLE_BY_PHONE = {
  '09120000001': 'customer',
  '09120000002': 'mall-manager',
  '09120000003': 'business-owner',
}

const DB = {
  points: { balance: 180, tier: 'silver' },
  stores: [
    { id: 1, name: 'بوتیک رویا', member_discount_pct: 15, category_id: 2, status: 'active' },
    { id: 2, name: 'رستوران سنتیِ شهر', member_discount_pct: 10, category_id: 1, status: 'active' },
    { id: 3, name: 'دیجیتال پارس', member_discount_pct: 5, category_id: 3, status: 'active' },
    { id: 4, name: 'لوازم خانگی مهر', member_discount_pct: 8, category_id: 3, status: 'active' },
  ],
  plans: [
    { key: 'silver', name: 'پکیج نقره‌ای', price: 12000000, store_quota: 50, duration_days: 180, features: ['تا ۵۰ فروشگاه', '۶ ماه'] },
    { key: 'gold', name: 'پکیج طلایی', price: 30000000, store_quota: 150, duration_days: 365, features: ['تا ۱۵۰ فروشگاه', '۱۲ ماه'] },
  ],
  settings: {
    name: 'مجتمع تجاری الماس',
    brand: 'پاساژ الماس',
    sms: { driver: 'fake', kavenegar_key_set: false },
    payment: { driver: 'fake', zarinpal_merchant_set: false },
  },
}

function userFor(phone) {
  const role = ROLE_BY_PHONE[phone] || 'customer'
  return { id: 1, phone, type: role === 'customer' ? 'customer' : 'staff', status: 'active', roles: [role] }
}

function route(method, url, body) {
  const u = (url || '').toLowerCase()
  const is = (m, suffix) => method === m && u.endsWith(suffix)

  if (is('post', 'dev/login')) {
    const phone = body?.phone || '09120000001'
    return { data: { data: userFor(phone), token: 'demo-token-' + phone, is_new: false }, status: 200 }
  }
  if (is('post', 'auth/request-otp')) return { data: { message: 'کد ارسال شد (دمو: ۱۲۳۴۵۶)' }, status: 200 }
  if (is('post', 'auth/verify-otp')) {
    return { data: { data: userFor(body?.phone || '09120000001'), token: 'demo-token', is_new: false }, status: 200 }
  }
  if (is('post', 'auth/logout')) return { data: { message: 'خروج انجام شد.' }, status: 200 }

  if (is('get', 'me/points')) return { data: DB.points, status: 200 }
  if (is('get', 'me/redeem-token')) {
    return { data: { token: 'SL-' + Math.random().toString(36).slice(2, 10).toUpperCase() + '-' + Math.random().toString(36).slice(2, 8).toUpperCase() }, status: 200 }
  }
  if (is('get', 'stores')) return { data: { data: DB.stores }, status: 200 }

  if (is('get', 'admin/overview')) {
    return { data: { data: { plan: 'silver', quota: 50, stores_used: 4, stores_remaining: 46, ends_at: '2026-09-01T00:00:00Z' } }, status: 200 }
  }
  if (is('get', 'admin/mall/settings')) return { data: { data: DB.settings }, status: 200 }
  if (is('put', 'admin/mall/settings')) {
    const b = body || {}
    if ('brand' in b) DB.settings.brand = b.brand
    if (b.sms) {
      if (b.sms.driver) DB.settings.sms.driver = b.sms.driver
      if (b.sms.kavenegar_key) DB.settings.sms.kavenegar_key_set = true
    }
    if (b.payment) {
      if (b.payment.driver) DB.settings.payment.driver = b.payment.driver
      if (b.payment.zarinpal_merchant) DB.settings.payment.zarinpal_merchant_set = true
    }
    return { data: { data: DB.settings }, status: 200 }
  }

  if (is('get', 'plans')) return { data: { data: DB.plans }, status: 200 }
  if (is('post', 'admin/plans')) {
    const b = body || {}
    DB.plans.push({ key: b.key, name: b.name, price: Number(b.price), store_quota: Number(b.store_quota), duration_days: Number(b.duration_days), features: [] })
    return { data: { data: b }, status: 201 }
  }

  return { data: { message: 'mock: مسیر یافت نشد' }, status: 404 }
}

export function installMock() {
  api.defaults.adapter = async (config) => {
    const method = (config.method || 'get').toLowerCase()
    let body = config.data
    if (typeof body === 'string') {
      try { body = JSON.parse(body) } catch { body = {} }
    }
    const { data, status } = route(method, config.url || '', body)
    const response = { data, status, statusText: 'OK', headers: {}, config, request: {} }
    if (status >= 400) {
      const err = new Error('mock ' + status)
      err.response = response
      throw err
    }
    return response
  }
  // eslint-disable-next-line no-console
  console.info('%c[SmartLocal] mock backend ON (dev). Real API: localStorage.setItem("real","1") then reload.', 'color:#7C3AED')
}
