import type { SubmissionBootstrap } from '@submission/types'

const fallbackBootstrap: SubmissionBootstrap = {
  submitUrl: '/wp-admin/admin-post.php',
  action: 'tharchive_submit_event',
  nonce: '',
  returnUrl: '/',
  defaults: {},
  suggestions: {
    characters: [],
    organizers: []
  },
  upload: {
    acceptedImageTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    maxGalleryFiles: 20
  },
  labels: {
    submitButton: '提交活动信息',
    submittingText: '正在提交...'
  }
}

export function useBootstrap(): SubmissionBootstrap {
  const fromWindow = window.THARCHIVE_SUBMISSION_BOOTSTRAP
  if (!fromWindow) {
    return fallbackBootstrap
  }

  return {
    ...fallbackBootstrap,
    ...fromWindow,
    defaults: {
      ...fallbackBootstrap.defaults,
      ...(fromWindow.defaults ?? {})
    },
    suggestions: {
      ...fallbackBootstrap.suggestions,
      ...(fromWindow.suggestions ?? {})
    },
    upload: {
      ...fallbackBootstrap.upload,
      ...(fromWindow.upload ?? {})
    },
    labels: {
      ...fallbackBootstrap.labels,
      ...(fromWindow.labels ?? {})
    }
  }
}
