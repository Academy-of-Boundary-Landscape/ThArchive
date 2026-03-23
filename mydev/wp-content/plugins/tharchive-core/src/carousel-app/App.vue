<template>
  <section class="tharchive-carousel-app">
    <header v-if="config.title" class="tharchive-carousel-app__header">
      <h3>{{ config.title }}</h3>
    </header>

    <div v-if="loading" class="tharchive-carousel-app__state">LOADING...</div>

    <div v-else-if="error" class="tharchive-carousel-app__state">
      <p>{{ error }}</p>
      <button type="button" class="tharchive-carousel-app__retry" @click="fetchEvents">重试</button>
    </div>

    <EventCarousel
      v-else
      :items="items"
      :empty-text="config.emptyText || '暂无可展示活动。'"
    />
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import EventCarousel from '@carousel/components/EventCarousel.vue'
import type { CarouselItem, CarouselMountConfig, RelayEventLite } from '@carousel/types'
import { requestJson } from '@shared/http'
import { buildWpApiUrl } from '@shared/wordpress'

const props = defineProps<{
  config: CarouselMountConfig
}>()

const loading = ref(false)
const error = ref('')
const items = ref<CarouselItem[]>([])

function toText(value?: string): string {
  return (value ?? '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()
}

function getThumbnailUrl(event: RelayEventLite): string {
  return event._embedded?.['wp:featuredmedia']?.[0]?.source_url ?? ''
}

function mapEventToCarouselItem(event: RelayEventLite): CarouselItem {
  const eventYear = event.meta?.event_year
  const normalizedYear = eventYear ? String(eventYear) : ''

  return {
    id: event.id,
    title: toText(event.title?.rendered) || '未命名活动',
    description: toText(event.excerpt?.rendered),
    imageUrl: getThumbnailUrl(event),
    href: event.link,
    meta: normalizedYear ? `${normalizedYear}年` : ''
  }
}

async function fetchEvents() {
  loading.value = true
  error.value = ''

  try {
    const url = buildWpApiUrl(props.config.restUrl, 'wp/v2/relay_event')
    url.searchParams.set('_embed', '1')
    url.searchParams.set('per_page', String(props.config.perPage))
    url.searchParams.set('orderby', props.config.orderby)
    url.searchParams.set('order', props.config.order)

    if (props.config.mode === 'year' && props.config.year) {
      url.searchParams.set('event_year', String(props.config.year))
    }

    const events = await requestJson<RelayEventLite[]>(url)
    items.value = events.map(mapEventToCarouselItem)
  } catch (err) {
    error.value = '轮播数据加载失败，请稍后重试。'
    console.error('[THArchive][CarouselApp] fetch failed', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchEvents()
})
</script>

<style scoped>
.tharchive-carousel-app {
  display: grid;
  gap: 0.8rem;
}

.tharchive-carousel-app__header h3 {
  margin: 0;
  font-size: 1.12rem;
  color: #fff;
}

.tharchive-carousel-app__state {
  min-height: 220px;
  display: grid;
  place-items: center;
  color: #cbd5e1;
  border: 1px dashed rgba(255, 255, 255, 0.25);
  background: rgba(10, 14, 22, 0.38);
  text-align: center;
  padding: 1rem;
}

.tharchive-carousel-app__retry {
  margin-top: 0.6rem;
  min-height: 34px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  background: rgba(10, 14, 22, 0.25);
  color: #fff;
  padding: 0 0.8rem;
  cursor: pointer;
}
</style>
