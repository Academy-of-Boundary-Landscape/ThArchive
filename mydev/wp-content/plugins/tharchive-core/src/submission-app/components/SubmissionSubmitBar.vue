<template>
  <n-card class="submission-submit-bar" :bordered="false">
    <n-space justify="space-between" align="center" wrap>
      <n-text depth="3">提交后将进入审核流程，可稍后回到本页查看处理结果。</n-text>
      <n-button type="primary" attr-type="submit" :loading="submitting" size="large">
        {{ submitting ? submittingText : submitText }}
      </n-button>
    </n-space>
  </n-card>
</template>

<script setup lang="ts">
import { NButton, NCard, NSpace, NText } from 'naive-ui'

defineProps<{
  submitText: string
  submittingText: string
  submitting: boolean
}>()
</script>
