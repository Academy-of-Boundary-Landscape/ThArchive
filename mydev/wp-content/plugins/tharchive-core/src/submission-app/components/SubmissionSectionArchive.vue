<template>
  <n-card title="3. 归档信息" class="submission-card">
    <n-form class="submission-section-form" label-placement="top">
      <n-alert type="info" :bordered="false">
        如果只有一个总结专栏链接，也可以直接提交；管理员会在审核时补充分类与标签。
      </n-alert>

      <n-form-item label="主归档链接 / 总结链接">
        <n-input
          v-model:value="form.bilibiliSummaryUrl"
          placeholder="https://"
        />
      </n-form-item>
      <n-form-item label="独立归档站链接">
        <n-input v-model:value="form.archiveSiteUrl" placeholder="https://" />
      </n-form-item>
    </n-form>
  </n-card>
</template>

<script setup lang="ts">
import { NAlert, NCard, NForm, NFormItem, NInput } from 'naive-ui'
import type { SubmissionFormState } from '@submission/types'

defineProps<{
  form: SubmissionFormState
}>()
</script>
