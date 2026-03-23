<template>
  <div class="submission-layout">
    <header class="submission-header">
      <h1>活动提交向导</h1>
      <p>请按分区补全信息，提交后将进入待审核队列。</p>
    </header>

    <n-card class="submission-steps" :bordered="false">
      <n-steps :current="currentStep + 1" size="small">
        <n-step v-for="(title, index) in stepTitles" :key="`${index}-${title}`" :title="title" />
      </n-steps>
    </n-card>

    <slot />
  </div>
</template>

<script setup lang="ts">
import { NCard, NStep, NSteps } from 'naive-ui'

defineProps<{
  currentStep: number
  stepTitles: string[]
}>()
</script>
