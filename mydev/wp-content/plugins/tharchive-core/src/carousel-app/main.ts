import { createApp, h } from 'vue'
import { NConfigProvider, darkTheme, zhCN, dateZhCN } from 'naive-ui'
import App from '@carousel/App.vue'
import type { CarouselMode, CarouselMountConfig } from '@carousel/types'

const defaultConfig: CarouselMountConfig = {
  restUrl: '/wp-json/',
  mode: 'recent',
  perPage: 12,
  orderby: 'date',
  order: 'desc'
}

function toInt(value: string | undefined, fallback: number): number {
  if (!value) {
    return fallback
  }
  const parsed = Number.parseInt(value, 10)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback
}

function normalizeMode(value: string | undefined): CarouselMode {
  return value === 'year' ? 'year' : 'recent'
}

function normalizeOrder(value: string | undefined): 'asc' | 'desc' {
  return value === 'asc' ? 'asc' : 'desc'
}

function normalizeOrderby(value: string | undefined): 'date' | 'modified' | 'title' {
  if (value === 'modified' || value === 'title') {
    return value
  }
  return 'date'
}

function readConfig(container: HTMLElement): CarouselMountConfig {
  const raw = container.dataset.config
  if (!raw) {
    return defaultConfig
  }

  try {
    const data = JSON.parse(raw) as Partial<CarouselMountConfig>
    return {
      restUrl: data.restUrl || defaultConfig.restUrl,
      mode: normalizeMode(data.mode),
      year: data.year ? toInt(String(data.year), new Date().getFullYear()) : undefined,
      perPage: toInt(String(data.perPage || defaultConfig.perPage), defaultConfig.perPage),
      orderby: normalizeOrderby(data.orderby),
      order: normalizeOrder(data.order),
      title: data.title,
      emptyText: data.emptyText
    }
  } catch {
    return defaultConfig
  }
}

const themeOverrides = {
  common: {
    bodyColor: 'transparent',
    cardColor: 'rgba(8, 12, 20, 0.35)',
    textColorBase: '#e2e8f0',
    borderColor: 'rgba(255, 255, 255, 0.38)',
    borderRadius: '0px'
  }
} as const

document.querySelectorAll<HTMLElement>('[data-tharchive-carousel-app="1"]').forEach((container) => {
  const config = readConfig(container)

  createApp({
    render() {
      return h(
        NConfigProvider,
        { locale: zhCN, dateLocale: dateZhCN, theme: darkTheme, themeOverrides },
        () => h(App, { config })
      )
    }
  }).mount(container)
})
