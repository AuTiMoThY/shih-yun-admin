// https://nuxt.com/docs/api/configuration/nuxt-config

// 判斷是否為生產環境（generate/build）
const processEnv = (
    globalThis as { process?: { env?: Record<string, string | undefined> } }
).process?.env;
const isProduction = processEnv?.NODE_ENV === "production";

// 設定 API Base URL
// 優先使用環境變數 NUXT_PUBLIC_API_BASE
// 如果沒有設定，開發環境使用 localhost，生產環境使用正式網址
// 注意：執行 generate 時，Nuxt 會自動設定 NODE_ENV=production
const apiBase =
    processEnv?.NUXT_PUBLIC_API_BASE ||
    (isProduction ? "https://test-sys.srl.tw/api" : "http://localhost:8080");

console.log("========== apiBase ==========", apiBase);
export default defineNuxtConfig({
    modules: [
      "@nuxt/eslint",
      "@nuxt/ui",
      "@nuxt/image",
      "@vueuse/nuxt",
      "nuxt-tiptap-editor",
    ],

    ssr: false, // 禁用 SSR，只使用客戶端渲染

    // 設定基礎路徑：開發時為 /，生產環境為 /admin/
    app: {
        baseURL: isProduction ? "/admin/" : "/"
    },

    devtools: {
        enabled: true
    },

    css: ["~/assets/css/main.css"],

    // 前後端分離：透過環境變數或預設的 API Base URL 呼叫後端
    runtimeConfig: {
        public: {
            apiBase
        }
    },

    compatibilityDate: "2025-01-15",

    // 開發時將 /api 代理到後端（避免拿到 Nuxt 的 HTML）
    nitro: {
        devProxy: {
            "/api": {
                target: apiBase,
                changeOrigin: true
            }
        },
        // 靜態生成時預渲染圖片路徑（當 ssr: false 時需要）
        prerender: {
            routes: isProduction ? ["/_ipx/q_80/images/logo.svg"] : []
        }
    },

    eslint: {
        config: {
            stylistic: {
                commaDangle: "never",
                braceStyle: "1tbs",
                quotes: "double"
            }
        }
    },

    image: {
        // 圖片優化配置
        quality: 80,
        format: ["webp"],
        screens: {
            xs: 320,
            sm: 640,
            md: 768,
            lg: 1024,
            xl: 1280,
            xxl: 1536
        },
        // Nuxt Image 目前僅支援 ipx 等動態 provider，static 不是有效值
        // 若要完全靜態化，請改用原生 <img> 引用 public/ 下的檔案
        provider: "ipx"
    },

    tiptap: {
        prefix: "Tiptap" //prefix for Tiptap imports, composables not included
    },

    
});