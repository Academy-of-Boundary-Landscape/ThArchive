import { reactive } from 'vue'
import type { SubmissionBootstrap, SubmissionFormState } from '@submission/types'

export function useSubmissionForm(bootstrap: SubmissionBootstrap) {
  const form = reactive<SubmissionFormState>(createInitialFormState(bootstrap))

  function applyImportedPayload(payload: Partial<SubmissionFormState>): void {
    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null) {
        return
      }
      const typedKey = key as keyof SubmissionFormState
      ;(form[typedKey] as SubmissionFormState[keyof SubmissionFormState]) = value as SubmissionFormState[keyof SubmissionFormState]
    })
  }

  function resetForm(): void {
    const nextState = createInitialFormState(bootstrap)
    Object.assign(form, nextState)
  }

  return {
    form,
    applyImportedPayload,
    resetForm
  }
}

function createInitialFormState(bootstrap: SubmissionBootstrap): SubmissionFormState {
  return {
    title: '',
    excerpt: '',
    content: '',
    character: '',
    organizer: '',
    eventDate: '',
    bilibiliSummaryUrl: '',
    archiveSiteUrl: '',
    sourceRawText: '',
    otherNotes: '',
    coverFile: null,
    galleryFiles: []
  }
}
