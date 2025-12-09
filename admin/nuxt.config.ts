// https://nuxt.com/docs/api/configuration/nuxt-config
const apiBase =
  (globalThis as { process?: { env?: Record<string, string | undefined> } })
    .process?.env?.NUXT_PUBLIC_API_BASE || 'http://localhost:8080';

export default defineNuxtConfig({
  modules: ['@nuxt/eslint', '@nuxt/ui', '@nuxt/image'],

  ssr: false, // 禁用 SSR，只使用客戶端渲染

  // 前後端分離：透過環境變數或預設的 API Base URL 呼叫後端
  runtimeConfig: {
    public: {
      apiBase
    }
  },

  // 開發時將 /api 代理到後端（避免拿到 Nuxt 的 HTML）
  nitro: {
    devProxy: {
      '/api': {
        target: apiBase,
        changeOrigin: true
      }
    }
  },

  devtools: {
    enabled: true
  },

  css: ['~/assets/css/main.css'],

  compatibilityDate: '2025-01-15',

  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs'
      }
    }
  }
})