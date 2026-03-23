import type { EventTerm, RelayEvent } from '@archive/types';

export function hasThumbnail(event: RelayEvent): boolean {
  return Boolean(event._embedded?.['wp:featuredmedia']?.[0]?.source_url);
}

export function getThumbnailUrl(event: RelayEvent): string {
  return event._embedded?.['wp:featuredmedia']?.[0]?.source_url ?? '';
}

export function getTerms(event: RelayEvent, taxonomy: string): EventTerm[] {
  const termGroups = event._embedded?.['wp:term'];
  if (!Array.isArray(termGroups)) {
    return [];
  }

  for (const termGroup of termGroups) {
    if (Array.isArray(termGroup) && termGroup.length > 0 && termGroup[0]?.taxonomy === taxonomy) {
      return termGroup as EventTerm[];
    }
  }

  return [];
}

export function hasTerms(event: RelayEvent, taxonomy: string): boolean {
  return getTerms(event, taxonomy).length > 0;
}

export function normalizeDateLike(value: string): string {
  return value.replace(/\//g, '-').trim();
}

export function getDayKey(year: number, month: number, date: number): string {
  const mm = String(month).padStart(2, '0');
  const dd = String(date).padStart(2, '0');
  return `${year}-${mm}-${dd}`;
}

export function getEventsForDay(events: RelayEvent[], year: number, month: number, date: number): RelayEvent[] {
  const targetDate = getDayKey(year, month, date);

  return events.filter((event) => {
    const rawDate = event.meta?.event_date;
    if (!rawDate) {
      return false;
    }

    const normalized = normalizeDateLike(rawDate);
    return normalized.includes(targetDate);
  });
}

function parseDate(value: string): Date | null {
  const normalized = normalizeDateLike(value);
  const exactDate = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!exactDate) {
    return null;
  }

  const year = Number(exactDate[1]);
  const month = Number(exactDate[2]);
  const day = Number(exactDate[3]);
  const parsed = new Date(year, month - 1, day);

  if (Number.isNaN(parsed.getTime())) {
    return null;
  }

  return parsed;
}

export function getEventDayKeys(event: RelayEvent): string[] {
  const startRaw = event.meta?.event_date;
  if (!startRaw) {
    return [];
  }

  const startDate = parseDate(startRaw);
  if (!startDate) {
    return [];
  }

  const endRaw = event.meta?.event_date_end;
  const endDate = endRaw ? parseDate(endRaw) : null;
  const lastDate = endDate && endDate >= startDate ? endDate : startDate;
  const cursor = new Date(startDate);
  const keys: string[] = [];

  while (cursor <= lastDate) {
    keys.push(getDayKey(cursor.getFullYear(), cursor.getMonth() + 1, cursor.getDate()));
    cursor.setDate(cursor.getDate() + 1);
  }

  return keys;
}

export function buildDayEventMap(events: RelayEvent[]): Map<string, RelayEvent[]> {
  const map = new Map<string, RelayEvent[]>();

  for (const event of events) {
    const dayKeys = getEventDayKeys(event);
    for (const key of dayKeys) {
      const existing = map.get(key);
      if (existing) {
        existing.push(event);
      } else {
        map.set(key, [event]);
      }
    }
  }

  return map;
}
