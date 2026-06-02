import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import Landing from '../src/views/Landing.vue'

describe('PWA smoke', () => {
    it('renders the landing view with a welcome message', async () => {
        const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/', component: Landing }, { path: '/login', component: { template: '<div />' } }] })
        const wrapper = mount(Landing, { global: { plugins: [router] } })

        expect(wrapper.text()).toContain('خوش آمدید')
    })
})
