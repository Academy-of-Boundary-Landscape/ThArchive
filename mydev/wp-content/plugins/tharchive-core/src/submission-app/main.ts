import { createApp, h } from 'vue'
import { NConfigProvider, NMessageProvider, darkTheme, zhCN, dateZhCN } from 'naive-ui'
import App from '@submission/App.vue'
import '@submission/styles.css'

const themeOverrides = {
  common: {
    primaryColor: '#5fa4ff',
    primaryColorHover: '#79b4ff',
    primaryColorPressed: '#4f95f1',
    primaryColorSuppl: '#5fa4ff',
    infoColor: '#5fa4ff',
    successColor: '#39c47a',
    warningColor: '#f2b552',
    errorColor: '#e86d7c'
  },
  Input: {
    color: '#131c28',
    colorFocus: '#1a2433',
    colorFocusWarning: '#2f2a1f',
    colorFocusError: '#2f1f26',
    textColor: '#e5edf8',
    placeholderColor: '#8ea3bd',
    border: '1px solid #2a3a4d',
    borderHover: '1px solid #406084',
    borderFocus: '1px solid #5fa4ff',
    boxShadowFocus: '0 0 0 2px rgba(95, 164, 255, 0.25)'
  },
  Select: {
    menuBoxShadow: '0 12px 28px rgba(2, 8, 18, 0.55)'
  },
  Card: {
    color: '#131c28',
    colorModal: '#131c28',
    textColor: '#e5edf8',
    titleTextColor: '#f2f7ff',
    borderColor: '#2a3a4d'
  },
  Steps: {
    titleTextColor: '#a9bbd1',
    titleTextColorProcess: '#e5edf8',
    titleTextColorFinish: '#d8e7ff',
    descriptionTextColor: '#8ea3bd',
    indicatorColor: '#304257',
    indicatorTextColor: '#e5edf8',
    indicatorColorProcess: '#5fa4ff',
    indicatorColorFinish: '#5fa4ff'
  },
  Alert: {
    colorInfo: '#162235',
    colorError: '#2f1c24',
    textColor: '#e5edf8',
    iconColorInfo: '#7eb7ff',
    iconColorError: '#f08c98',
    borderInfo: '1px solid #2e4460',
    borderError: '1px solid #5a2a35'
  }
} as const

createApp({
  render() {
    return h(
      NConfigProvider,
      { locale: zhCN, dateLocale: dateZhCN, theme: darkTheme, themeOverrides },
      () => h(NMessageProvider, () => h(App))
    )
  }
}).mount('#tharchive-submission-app')
