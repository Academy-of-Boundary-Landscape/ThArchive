<template>
  <submission-layout :current-step="currentStep" :step-titles="stepTitles">
    <form
      ref="formRef"
      id="tharchive-submission-form"
      class="submission-form"
      :action="bootstrap.submitUrl"
      method="post"
      enctype="multipart/form-data"
      @submit.prevent="handleSubmit"
    >
      <div class="submission-stack">
        <n-alert v-if="submitResultMessage" type="info" :bordered="false">
          {{ submitResultMessage }}
        </n-alert>

        <n-alert v-if="hasErrors" type="error" :bordered="false">
          请先补全必填项，再提交活动信息。
        </n-alert>

        <submission-section-basic
          v-if="currentStep === 0"
          :form="form"
          :suggestions="bootstrap.suggestions"
          :errors="errors"
          :clear-error="clearError"
          @update:event-date="form.eventDate = $event"
        />

        <submission-section-participation
          v-if="currentStep === 1"
          :form="form"
          :errors="errors"
          :clear-error="clearError"
        />

        <submission-section-archive
          v-if="currentStep === 2"
          :form="form"
          :errors="errors"
          :clear-error="clearError"
        />

        <submission-section-images
          v-show="currentStep === 3"
          :form="form"
          :errors="errors"
          :clear-error="clearError"
          :accepted-image-types="bootstrap.upload.acceptedImageTypes"
          :max-gallery-files="bootstrap.upload.maxGalleryFiles"
          @update:cover-file="form.coverFile = $event"
          @update:gallery-files="form.galleryFiles = $event"
        />

        <input type="hidden" name="action" :value="bootstrap.action" />
        <input type="hidden" name="_tharchive_return_url" :value="bootstrap.returnUrl" />
        <input type="hidden" name="tharchive_front_submit_nonce" :value="bootstrap.nonce" />

        <input type="hidden" name="tharchive_title" :value="form.title" />
        <input type="hidden" name="tharchive_excerpt" :value="form.excerpt" />
        <input type="hidden" name="tharchive_content" :value="form.content" />
        <input type="hidden" name="tharchive_character" :value="form.character" />
        <input type="hidden" name="tharchive_organizer" :value="form.organizer" />
        <input type="hidden" name="tharchive_event_date" :value="form.eventDate" />

        <input type="hidden" name="tharchive_bilibili_summary_url" :value="form.bilibiliSummaryUrl" />
        <input type="hidden" name="tharchive_archive_site_url" :value="form.archiveSiteUrl" />

        <input type="hidden" name="tharchive_source_raw_text" :value="form.sourceRawText" />
        <input type="hidden" name="tharchive_other_notes" :value="form.otherNotes" />

        <n-card class="submission-pager" :bordered="false">
          <n-space justify="space-between" align="center" wrap>
            <n-text depth="3">第 {{ currentStep + 1 }} 步 / 共 {{ stepTitles.length }} 步</n-text>
            <n-space>
              <n-button quaternary @click="clearDraftManually">清空草稿</n-button>
              <n-button :disabled="currentStep === 0" @click="goPrev">上一步</n-button>
              <n-button v-if="!isLastStep" type="primary" @click="goNext">下一步</n-button>
            </n-space>
          </n-space>
        </n-card>

        <submission-submit-bar
          v-if="isLastStep"
          :submit-text="bootstrap.labels.submitButton"
          :submitting-text="bootstrap.labels.submittingText"
          :submitting="isSubmitting"
        />
      </div>
    </form>
  </submission-layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { NAlert, NButton, NCard, NSpace, NText } from 'naive-ui'
import SubmissionLayout from '@submission/components/SubmissionLayout.vue'
import SubmissionSectionArchive from '@submission/components/SubmissionSectionArchive.vue'
import SubmissionSectionBasic from '@submission/components/SubmissionSectionBasic.vue'
import SubmissionSectionImages from '@submission/components/SubmissionSectionImages.vue'
import SubmissionSectionParticipation from '@submission/components/SubmissionSectionParticipation.vue'
import SubmissionSubmitBar from '@submission/components/SubmissionSubmitBar.vue'
import { useBootstrap } from '@submission/composables/useBootstrap'
import { useSubmissionForm } from '@submission/composables/useSubmissionForm'
import { useSubmissionValidation } from '@submission/composables/useSubmissionValidation'
import type { SubmissionFormState } from '@submission/types'

const bootstrap = useBootstrap()
const { form, resetForm } = useSubmissionForm(bootstrap)
const { errors, hasErrors, validateAll, clearError, clearAllErrors } = useSubmissionValidation(form)

const stepTitles = ['基础信息', '活动说明', '归档信息', '素材与补充']
const currentStep = ref(0)

const formRef = ref<HTMLFormElement | null>(null)
const isSubmitting = ref(false)

const DRAFT_KEY = 'tharchive_submission_draft_v1'
const STEP_KEY = 'tharchive_submission_step_v1'

const DRAFT_FIELDS: Array<keyof SubmissionFormState> = [
  'title',
  'excerpt',
  'content',
  'character',
  'organizer',
  'eventDate',
  'bilibiliSummaryUrl',
  'archiveSiteUrl',
  'sourceRawText',
  'otherNotes'
]

const isLastStep = computed(() => currentStep.value === stepTitles.length - 1)

const submitResultMessage = computed(() => {
  const status = new URLSearchParams(window.location.search).get('tharchive_submit')
  if (!status) {
    return ''
  }

  if (status === 'success') {
    return '活动信息已提交，正在等待审核。'
  }

  return '提交未成功，请检查必填项后再次提交。'
})

function scrollToFirstError() {
  const order = ['title', 'excerpt', 'character', 'organizer', 'eventDate', 'content', 'bilibiliSummaryUrl', 'archiveSiteUrl', 'coverFile']
  const firstError = order.find((key) => errors.value[key])
  if (!firstError) {
    return
  }

  const target = document.querySelector(`[data-field="${firstError}"]`)
  if (target instanceof HTMLElement) {
    target.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

function handleSubmit() {
  if (isSubmitting.value) {
    return
  }

  if (!isLastStep.value) {
    return
  }

  if (!validateAll()) {
    scrollToFirstError()
    return
  }

  isSubmitting.value = true
  clearDraftStorage()
  formRef.value?.submit()
}

function goPrev() {
  currentStep.value = Math.max(0, currentStep.value - 1)
}

function goNext() {
  if (!canMoveToNextStep()) {
    scrollToFirstError()
    return
  }

  currentStep.value = Math.min(stepTitles.length - 1, currentStep.value + 1)
}

function canMoveToNextStep() {
  if (currentStep.value === 0) {
    return validateStep0()
  }
  if (currentStep.value === 1) {
    return validateStep1()
  }
  if (currentStep.value === 2) {
    return validateStep2()
  }
  return true
}

function validateStep0() {
  let valid = true

  if (!form.title.trim()) {
    errors.value.title = '请填写活动标题'
    valid = false
  }
  if (!form.excerpt.trim()) {
    errors.value.excerpt = '请填写一句话简介'
    valid = false
  }
  if (!form.character.trim()) {
    errors.value.character = '请填写东方角色'
    valid = false
  }
  if (!form.organizer.trim()) {
    errors.value.organizer = '请填写主办方'
    valid = false
  }
  if (!form.eventDate.trim()) {
    errors.value.eventDate = '请填写活动日期'
    valid = false
  }

  return valid
}

function validateStep1() {
  if (!form.content.trim()) {
    errors.value.content = '请填写活动说明'
    return false
  }

  return true
}

function isValidUrlOrEmpty(value: string): boolean {
  if (!value.trim()) {
    return true
  }
  try {
    const url = new URL(value.trim())
    return url.protocol === 'http:' || url.protocol === 'https:'
  } catch {
    return false
  }
}

function validateStep2() {
  let valid = true

  if (!isValidUrlOrEmpty(form.bilibiliSummaryUrl)) {
    errors.value.bilibiliSummaryUrl = '请输入有效的 URL（以 http:// 或 https:// 开头）'
    valid = false
  }
  if (!isValidUrlOrEmpty(form.archiveSiteUrl)) {
    errors.value.archiveSiteUrl = '请输入有效的 URL（以 http:// 或 https:// 开头）'
    valid = false
  }

  return valid
}

function clearDraftStorage() {
  localStorage.removeItem(DRAFT_KEY)
  localStorage.removeItem(STEP_KEY)
}

function clearDraftManually() {
  const shouldClear = window.confirm('确定要清空当前草稿并从第一步重新填写吗？')
  if (!shouldClear) {
    return
  }

  clearDraftStorage()
  clearAllErrors()
  resetForm()
  currentStep.value = 0
}

function serializeDraft() {
  const payload: Partial<Record<keyof SubmissionFormState, SubmissionFormState[keyof SubmissionFormState]>> = {}
  const formRecord = form as Record<keyof SubmissionFormState, SubmissionFormState[keyof SubmissionFormState]>
  DRAFT_FIELDS.forEach((field) => {
    payload[field] = formRecord[field]
  })
  return payload as Partial<SubmissionFormState>
}

function restoreDraft() {
  const rawDraft = localStorage.getItem(DRAFT_KEY)
  if (rawDraft) {
    try {
      const parsed = JSON.parse(rawDraft) as Partial<SubmissionFormState>
      const formRecord = form as Record<keyof SubmissionFormState, SubmissionFormState[keyof SubmissionFormState]>
      DRAFT_FIELDS.forEach((field) => {
        const value = parsed[field]
        if (value !== undefined) {
          formRecord[field] = value as SubmissionFormState[keyof SubmissionFormState]
        }
      })
    } catch {
      localStorage.removeItem(DRAFT_KEY)
    }
  }

  const rawStep = Number(localStorage.getItem(STEP_KEY))
  if (Number.isFinite(rawStep)) {
    currentStep.value = Math.min(stepTitles.length - 1, Math.max(0, rawStep))
  }
}

onMounted(() => {
  restoreDraft()
})

watch(
  form,
  () => {
    localStorage.setItem(DRAFT_KEY, JSON.stringify(serializeDraft()))
  },
  { deep: true }
)

watch(currentStep, (step) => {
  localStorage.setItem(STEP_KEY, String(step))
})
</script>
