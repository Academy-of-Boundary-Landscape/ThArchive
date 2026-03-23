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
import { onMounted, ref } from 'vue'
import { NButton, NEmpty, NSpin } from 'naive-ui'
import EventCarousel from './EventCarousel.vue'
import { useArchiveApi } from '@archive/composables/useArchiveApi'
import type { CarouselItem, RelayEvent } from '@archive/types'
import { getThumbnailUrl, hasThumbnail } from '@archive/utils/event-utils'

const { buildWpApiUrl, fetchJson } = useArchiveApi()

const loading = ref(false)
const error = ref('')
const items = ref<CarouselItem[]>([])

function toText(value?: string): string {
  return (value ?? '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()
}

function mapEventToCarouselItem(event: RelayEvent): CarouselItem {
  return {
    id: event.id,
    title: toText(event.title?.rendered) || '未命名活动',
    description: toText(event.excerpt?.rendered),
    imageUrl: hasThumbnail(event) ? getThumbnailUrl(event) : '',
    href: event.link,
    meta: event.meta?.event_year ? `${event.meta.event_year}年` : ''
  }
}

async function fetchRecentEvents() {
  loading.value = true
  error.value = ''

  try {
    const url = buildWpApiUrl('wp/v2/relay_event')
    url.searchParams.set('_embed', '1')
    url.searchParams.set('per_page', '12')
    url.searchParams.set('orderby', 'date')
    url.searchParams.set('order', 'desc')

    const events = await fetchJson<RelayEvent[]>(url)
    items.value = events.map(mapEventToCarouselItem)
  } catch (err) {
    error.value = '近期活动加载失败，请稍后重试。'
    console.error('[THArchive][RecentCarousel] fetch failed', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchRecentEvents()
})
</script>

<style scoped>
.carousel-block__header h3 {
  margin: 0 0 0.8rem;
  color: #fff;
  font-size: 1.12rem;
}

.carousel-block__state {
  min-height: 240px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(12, 16, 22, 0.42);
}
</style>
