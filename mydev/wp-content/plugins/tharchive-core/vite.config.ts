// @ts-nocheck
import { defineConfig, type UserConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'node:path'

const aliases = {
  '@submission': resolve(__dirname, 'src/submission-app'),
  '@archive': resolve(__dirname, 'src/archive-app'),
  '@carousel': resolve(__dirname, 'src/carousel-app'),
  '@shared': resolve(__dirname, 'src/shared')
}

function createAppConfig(entryName: 'submission-app' | 'archive-app' | 'carousel-app', entryFile: string, globalName: string): UserConfig {
  return {
    plugins: [vue()],
    define: {
      'process.env.NODE_ENV': JSON.stringify('production')
    },
    resolve: {
      alias: aliases
    },
    build: {
      emptyOutDir: false,
      outDir: resolve(__dirname, 'assets/dist'),
      cssCodeSplit: false,
      rollupOptions: {
        input: resolve(__dirname, entryFile),
        output: {
          entryFileNames: `${entryName}.js`,
          assetFileNames: `${entryName}[extname]`,
          format: 'iife',
          name: globalName,
          inlineDynamicImports: true
        }
      }
    }
  }
}

const buildTarget = process.argv.includes('archive-app')
  ? 'archive-app'
  : process.argv.includes('carousel-app')
    ? 'carousel-app'
    : 'submission-app'

const config: UserConfig = buildTarget === 'archive-app'
  ? createAppConfig('archive-app', 'src/archive-app/main.ts', 'THArchiveArchiveApp')
  : buildTarget === 'carousel-app'
    ? createAppConfig('carousel-app', 'src/carousel-app/main.ts', 'THArchiveCarouselApp')
    : createAppConfig('submission-app', 'src/submission-app/main.ts', 'THArchiveSubmissionApp')

export default defineConfig(config)
