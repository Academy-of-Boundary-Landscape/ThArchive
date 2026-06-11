<template>
  <section class="carousel-block">
    <header class="carousel-block__header">
      <h3>近期活动轮播</h3>
    </header>

    <div v-if="loading" class="carousel-block__state">
      <n-spin size="large" stroke="#fff" />
    </div>

    <div v-else-if="error" class="carousel-block__state">
      <n-empty :description="error">
        <template #extra>
          <n-button ghost class="console-btn console-btn--dashed" @click="fetchRecentEvents">重试</n-button>
        </template>
      </n-empty>
    </div>

    <EventCarousel v-else :items="items" empty-text="近期没有可展示活动。" />
  </section>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { NButton, NEmpty, NSpin } from 'naive-ui'
import EventCarousel from './EventCarousel.vue'
import { useCarouselData } from '@archive/composables/useCarouselData'

const { loading, error, items, load } = useCarouselData()

function fetchRecentEvents() {
  load({
    perPage: 12,
    metaLabel: (event) => (event.meta?.event_year ? `${event.meta.event_year}年` : ''),
    errorText: '近期活动加载失败，请稍后重试。'
  })
}

onMounted(fetchRecentEvents)
</script>
