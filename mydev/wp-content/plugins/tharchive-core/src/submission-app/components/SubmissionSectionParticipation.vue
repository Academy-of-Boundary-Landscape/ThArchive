<template>
  <n-card title="2. 活动说明" class="submission-card">
    <n-form class="submission-section-form" label-placement="top" :show-require-mark="true">
      <n-alert type="info" :bordered="false">
        报名方式、截止方式、发布平台、活动规则和补充说明，都可以直接写在这里。
      </n-alert>

      <n-form-item
        label="活动说明（Markdown）"
        required
        :validation-status="errors.content ? 'error' : undefined"
        :feedback="errors.content"
        data-field="content"
      >
        <n-input
          v-model:value="form.content"
          type="textarea"
          :autosize="{ minRows: 12 }"
          placeholder="建议至少包含：活动简介、参与方式、投稿要求、截止时间和发布说明。"
          @update:value="clearError('content')"
        />
      </n-form-item>
    </n-form>
  </n-card>
</template>

<script setup lang="ts">
import { NAlert, NCard, NForm, NFormItem, NInput } from 'naive-ui'
import type { SubmissionFormState } from '@submission/types'

defineProps<{
  form: SubmissionFormState
  errors: Record<string, string>
  clearError: (field: string) => void
}>()
</script>
