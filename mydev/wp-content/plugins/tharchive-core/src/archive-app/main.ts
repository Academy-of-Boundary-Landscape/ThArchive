import { createApp, h } from 'vue'
import { NConfigProvider, darkTheme, zhCN, dateZhCN } from 'naive-ui'
import App from '@archive/App.vue'
import '@archive/styles.css'
import { useBootstrap } from '@archive/composables/useBootstrap'

const themeOverrides = {
  common: {
    primaryColor: '#ffffff',
    primaryColorHover: '#f1f5f9',
    primaryColorPressed: '#dbe4ef',
    infoColor: '#ffffff',
    bodyColor: 'transparent',
    cardColor: 'rgba(8, 12, 20, 0.35)',
    textColorBase: '#e2e8f0',
    borderColor: 'rgba(255, 255, 255, 0.38)',
    borderRadius: '0px'
  },
  Card: {
    borderColor: 'rgba(255, 255, 255, 0.38)',
    borderRadius: '0px'
  },
  Select: {
    peers: {
      InternalSelection: {
        textColor: '#fff',
        border: '1px solid rgba(255, 255, 255, 0.38)',
        borderRadius: '0px',
        boxShadowFocus: '0 0 0 2px rgba(255, 255, 255, 0.12)'
      }
    }
  },
  Input: {
    textColor: '#fff',
    border: '1px solid rgba(255, 255, 255, 0.38)',
    borderRadius: '0px',
    boxShadowFocus: '0 0 0 2px rgba(255, 255, 255, 0.12)'
  },
  Button: {
    textColor: '#fff',
    borderRadius: '0px',
    borderGhost: '1px solid rgba(255, 255, 255, 0.45)',
    textColorHoverGhost: '#fff',
    borderHoverGhost: '1px solid #fff'
  },
  Pagination: {
    itemBorderRadius: '0px'
  },
  Radio: {
    buttonBorderRadius: '0px'
  }
} as const

const bootstrap = useBootstrap()
const container = document.getElementById(bootstrap.mountId)

if (container) {
  createApp({
    render() {
      return h(
        NConfigProvider,
        { locale: zhCN, dateLocale: dateZhCN, theme: darkTheme, themeOverrides },
        () => h(App)
      )
    }
  }).mount(container)
}
