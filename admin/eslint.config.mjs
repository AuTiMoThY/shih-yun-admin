// @ts-check
import withNuxt from './.nuxt/eslint.config.mjs'

export default withNuxt(
  // 自訂規則：統一縮排為 4 格空白
  {
    rules: {
      // Nuxt 使用 TypeScript，因此需要同時設定 TS 與一般 JS 的縮排
      '@typescript-eslint/indent': ['error', 4],
      indent: ['error', 4],
    },
  },
)
