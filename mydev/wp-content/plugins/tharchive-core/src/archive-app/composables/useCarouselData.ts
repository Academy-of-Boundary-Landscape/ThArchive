import { ref } from 'vue'
import { useArchiveApi } from '@archive/composables/useArchiveApi'
import type { CarouselItem, RelayEvent } from '@archive/types'
import { getThumbnailUrl, hasThumbnail, stripHtmlToText } from '@archive/utils/event-utils'

export interface CarouselQuery {
  perPage: number
  extraParams?: Record<string, string>
  metaLabel: (event: RelayEvent) => string
  errorText: string
}

export function useCarouselData() {
  const { buildWpApiUrl, fetchJson } = useArchiveApi()
  const loading = ref(false)
  const error = ref('')
  const items = ref<CarouselItem[]>([])

  function mapEvent(event: RelayEvent, metaLabel: (e: RelayEvent) => string): CarouselItem {
    return {
      id: event.id,
      title: stripHtmlToText(event.title?.rendered) || '未命名活动',
      description: stripHtmlToText(event.excerpt?.rendered),
      imageUrl: hasThumbnail(event) ? getThumbnailUrl(event) : '',
      href: event.link,
      meta: metaLabel(event)
    }
  }

  async function load(query: CarouselQuery) {
    loading.value = true
    error.value = ''
    try {
      const url = buildWpApiUrl('wp/v2/relay_event')
      url.searchParams.set('_embed', '1')
      url.searchParams.set('per_page', String(query.perPage))
      url.searchParams.set('orderby', 'date')
      url.searchParams.set('order', 'desc')
      Object.entries(query.extraParams ?? {}).forEach(([k, v]) => url.searchParams.set(k, v))
      const events = await fetchJson<RelayEvent[]>(url)
      items.value = events.map((e) => mapEvent(e, query.metaLabel))
    } catch (err) {
      error.value = query.errorText
      console.error('[THArchive][Carousel] fetch failed', err)
    } finally {
      loading.value = false
    }
  }

  return { loading, error, items, load }
}
