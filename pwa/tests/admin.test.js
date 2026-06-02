import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AdminSettings from '../src/views/admin/AdminSettings.vue'

describe('Admin panel smoke', () => {
    it('renders the mall settings form with provider fields', () => {
        const wrapper = mount(AdminSettings)
        const text = wrapper.text()
        expect(text).toContain('تنظیمات پاساژ')
        expect(text).toContain('درگاه پرداخت')
    })
})
