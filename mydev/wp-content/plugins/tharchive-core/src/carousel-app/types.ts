export type CarouselMode = 'recent' | 'year'

export interface CarouselMountConfig {
  restUrl: string
  mode: CarouselMode
  year?: number
  perPage: number
  orderby: 'date' | 'modified' | 'title'
  order: 'asc' | 'desc'
  title?: string
  emptyText?: string
}

export type { CarouselItem } from '@shared/types'

export interface RelayEventLite {
  id: number
  date?: string
  link?: string
  title?: { rendered?: string }
  excerpt?: { rendered?: string }
  meta?: {
    event_date?: string
    event_date_end?: string
    event_year?: number | string
  }
  _embedded?: {
    'wp:featuredmedia'?: Array<{ source_url?: string }>
    [key: string]: unknown
  }
}
