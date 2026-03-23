import { computed, ref } from 'vue'
import type { SubmissionFormState } from '@submission/types'

export function useSubmissionValidation(form: SubmissionFormState) {
  const errors = ref<Record<string, string>>({})

  const hasErrors = computed(() => Object.keys(errors.value).length > 0)

  function validateAll() {
    const nextErrors: Record<string, string> = {}

    if (!form.title.trim()) {
      nextErrors.title = '请填写活动标题'
    }
    if (!form.excerpt.trim()) {
      nextErrors.excerpt = '请填写一句话简介'
    }
    if (!form.character.trim()) {
      nextErrors.character = '请填写东方角色'
    }
    if (!form.organizer.trim()) {
      nextErrors.organizer = '请填写主办方'
    }
    if (!form.eventDate || !form.eventDate.trim()) {
      nextErrors.eventDate = '请填写活动日期'
    }
    if (!form.content.trim()) {
      nextErrors.content = '请填写活动说明'
    }
    if (!form.coverFile) {
      nextErrors.coverFile = '请上传封面图'
    }

    errors.value = nextErrors
    return Object.keys(nextErrors).length === 0
  }

  function clearError(field: string) {
    if (!errors.value[field]) {
      return
    }
    const nextErrors = { ...errors.value }
    delete nextErrors[field]
    errors.value = nextErrors
  }

  function clearAllErrors() {
    errors.value = {}
  }

  return {
    errors,
    hasErrors,
    validateAll,
    clearError,
    clearAllErrors
  }
}
