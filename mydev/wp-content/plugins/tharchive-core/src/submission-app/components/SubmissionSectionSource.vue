<template>
  <n-card title="原始来源（备用组件）" class="submission-card">
    <n-form class="submission-section-form" label-placement="top">
      <n-form-item label="原始文本摘录">
        <n-input v-model:value="form.sourceRawText" type="textarea" :autosize="{ minRows: 4 }" />
      </n-form-item>
      <n-form-item label="其它备注">
        <n-input v-model:value="form.otherNotes" type="textarea" :autosize="{ minRows: 3 }" />
      </n-form-item>
    </n-form>
  </n-card>
</template>

<script setup lang="ts">
import { NCard, NForm, NFormItem, NInput } from 'naive-ui'
import type { SubmissionFormState } from '@submission/types'

defineProps<{
  form: SubmissionFormState
}>()
</script>
