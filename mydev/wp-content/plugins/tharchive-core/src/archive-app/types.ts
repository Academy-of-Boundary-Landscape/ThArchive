export type SelectValue = string | number | null
export type DateRangeValue = [string, string] | null

export interface RelayEvent {
  id: number
  title?: { rendered?: string }
  excerpt?: { rendered?: string }
  link?: string
  meta?: {
    event_date?: string
    event_date_end?: string
    event_year?: string | number
  }
  _embedded?: {
    'wp:featuredmedia'?: Array<{ source_url?: string }>
    'wp:term'?: EventTerm[][]
    [key: string]: unknown
  }
}

export interface EventTerm {
  id: number
  name: string
  taxonomy: string
}

export interface ArchiveFilters {
  event_status: SelectValue
  event_type: SelectValue
  year: SelectValue
  date_range: DateRangeValue
  search: string
}

export interface ArchiveTaxData {
  event_status: EventTerm[]
  event_type: EventTerm[]
}

export interface CarouselItem {
  id: number
  title: string
  description?: string
  imageUrl?: string
  href?: string
  meta?: string
}

export interface ArchiveBootstrap {
  restUrl: string
  archiveUrl: string
  mountId: string
}

declare global {
  interface Window {
    THARCHIVE_ARCHIVE_BOOTSTRAP?: Partial<ArchiveBootstrap>
  }
}
