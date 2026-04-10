import type { ArchiveBootstrap } from '@archive/types'

const fallbackBootstrap: ArchiveBootstrap = {
  restUrl: '/wp-json/',
  archiveUrl: '',
  mountId: 'tharchive-relay-index'
}

function normalizeRestUrl(restUrl: string): string {
  const value = (restUrl || '').trim()
  if (!value) {
    return '/wp-json/'
  }

  return value.endsWith('/') ? value : `${value}/`
}

export function useBootstrap(): ArchiveBootstrap {
  const fromWindow = window.THARCHIVE_ARCHIVE_BOOTSTRAP
  if (!fromWindow) {
    return fallbackBootstrap
  }

  return {
    ...fallbackBootstrap,
    ...fromWindow,
    restUrl: normalizeRestUrl(fromWindow.restUrl || fallbackBootstrap.restUrl)
  }
}
