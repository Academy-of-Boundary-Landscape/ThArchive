<template>
  <section class="carousel-block">
    <header class="carousel-block__header">
      <h3>{{ year }} 年活动轮播</h3>
    </header>

    <div v-if="loading" class="carousel-block__state">
      <n-spin size="large" stroke="#fff" />
    </div>

    <div v-else-if="error" class="carousel-block__state">
      <n-empty :description="error">
        <template #extra>
          <n-button ghost class="console-btn console-btn--dashed" @click="fetchYearEvents">重试</n-button>
        </template>
      </n-empty>
    </div>

    <EventCarousel
      v-else
      :items="items"
      :empty-text="`${year} 年暂无可展示活动。`"
    />
  </section>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { NButton, NEmpty, NSpin } from 'naive-ui'
import EventCarousel from './EventCarousel.vue'
import { useCarouselData } from '@archive/composables/useCarouselData'

const props = defineProps<{ year: number }>()
const { loading, error, items, load } = useCarouselData()

function fetchYearEvents() {
  load({
    perPage: 18,
    extraParams: { event_year: String(props.year) },
    metaLabel: () => `${props.year}年`,
    errorText: `${props.year} 年活动加载失败，请稍后重试。`
  })
}

watch(() => props.year, fetchYearEvents)
onMounted(fetchYearEvents)
</script>
