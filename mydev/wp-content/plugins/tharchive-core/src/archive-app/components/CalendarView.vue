<template>
  <div class="calendar-wrapper">
    <div v-if="loading" class="state-container">
      <n-spin size="large" stroke="#fff">
        <template #description>
          <span class="muted-text">SYNCING CALENDAR...</span>
        </template>
      </n-spin>
    </div>

    <div v-else-if="loadError" class="state-container">
      <n-empty :description="loadError">
        <template #extra>
          <n-button ghost size="small" class="console-btn console-btn--dashed" @click="fetchAllEvents">重新同步</n-button>
        </template>
      </n-empty>
    </div>

    <!-- Naive UI Calendar -->
    <div v-else class="scifi-calendar-panel">
      <n-calendar
        v-model:value="currentDate"
        @update:value="handleDateSelect"
      >
        <template #default="{ year, month, date }">
           <div class="day-cell">
             <template v-if="getEventsForDate(year, month, date).length > 0">
               <div class="event-indicators">
                 <div 
                   v-for="e in getEventsForDate(year, month, date)" 
                   :key="e.id" 
                   class="event-indicator"
                   :style="getEventIndicatorStyle(e)"
                   :title="e.title?.rendered || ''"
                 >
                   <span class="event-dot"></span>
                   <span class="event-title-trunc">{{ stripHtml(e.title?.rendered) }}</span>
                 </div>
               </div>
             </template>
           </div>
        </template>
      </n-calendar>
    </div>

    <!-- Day Events Modal -->
    <n-modal v-model:show="showModal" class="day-events-modal" :mask-closable="true" :mask="false">
      <div class="day-events-panel console-frame console-frame--double">
        <div class="day-events-header">
          <h4>当日归档记录</h4>
          <n-button ghost size="small" class="console-btn console-btn--dashed" @click="showModal = false">关闭</n-button>
        </div>

        <div v-if="selectedEvents.length > 0" class="day-events-list">
          <EventCard v-for="ev in selectedEvents" :key="ev.id" :event="ev" />
        </div>
        <div v-else class="empty-text">该日期暂无记录。</div>
      </div>
    </n-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { NCalendar, NSpin, NModal, NEmpty, NButton } from 'naive-ui';
import EventCard from './EventCard.vue';
import { buildDayEventMap, getDayKey } from '../utils/event-utils';
import type { RelayEvent } from '@archive/types';
import { useArchiveApi } from '@archive/composables/useArchiveApi';

const { buildWpApiUrl, fetchRaw } = useArchiveApi();

const currentDate = ref(Date.now());
const events = ref<RelayEvent[]>([]);
const loading = ref(false);
const loadError = ref('');
const dayEventMap = ref<Map<string, RelayEvent[]>>(new Map());

const showModal = ref(false);
const selectedEvents = ref<RelayEvent[]>([]);

const fetchAllEvents = async () => {
  loading.value = true;
  loadError.value = '';
  try {
    const aggregatedEvents: RelayEvent[] = [];
    let page = 1;
    let totalPages = 1;

    while (page <= totalPages) {
      const url = buildWpApiUrl('wp/v2/relay_event');
      url.searchParams.append('_embed', '1');
      url.searchParams.append('per_page', '100');
      url.searchParams.append('page', page.toString());

      const response = await fetchRaw(url);

      const totalPagesHeader = response.headers.get('X-WP-TotalPages');
      totalPages = totalPagesHeader ? parseInt(totalPagesHeader, 10) : 1;

      const data = (await response.json()) as unknown;
      if (Array.isArray(data)) {
        aggregatedEvents.push(...(data as RelayEvent[]));
      }

      page += 1;
    }

    events.value = aggregatedEvents;
    dayEventMap.value = buildDayEventMap(aggregatedEvents);
  } catch (err) {
    loadError.value = '日历归档同步失败，请稍后重试。';
    console.error('Failed to fetch events for calendar', err);
  } finally {
    loading.value = false;
  }
};

const getEventsForDate = (year: number, month: number, date: number) => {
  const key = getDayKey(year, month, date);
  return dayEventMap.value.get(key) ?? [];
};

const handleDateSelect = (_: number, { year, month, date }: { year: number, month: number, date: number }) => {
  const dayEvents = getEventsForDate(year, month, date);
  if (dayEvents.length > 0) {
    selectedEvents.value = dayEvents;
    showModal.value = true;
  }
};

function stripHtml(value?: string): string {
  return (value ?? '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
}

function hashString(value: string): number {
  let hash = 0;

  for (let index = 0; index < value.length; index += 1) {
    hash = ((hash << 5) - hash + value.charCodeAt(index)) | 0;
  }

  return Math.abs(hash);
}

function getEventIndicatorStyle(event: RelayEvent): Record<string, string> {
  const title = stripHtml(event.title?.rendered) || `event-${event.id}`;
  const hash = hashString(title);
  const hue = hash % 360;
  const accent = `hsla(${hue}, 78%, 72%, 0.92)`;
  const edge = `hsla(${hue}, 82%, 64%, 0.62)`;
  const glow = `hsla(${hue}, 88%, 68%, 0.2)`;
  const surface = `linear-gradient(90deg, hsla(${hue}, 56%, 20%, 0.78) 0%, rgba(10, 16, 26, 0.24) 100%)`;

  return {
    '--th-indicator-accent': accent,
    '--th-indicator-edge': edge,
    '--th-indicator-glow': glow,
    '--th-indicator-surface': surface
  };
}

onMounted(() => {
  fetchAllEvents();
});
</script>

<style scoped>
.calendar-wrapper {
  margin-top: 1rem;
}
.state-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem 0;
  min-height: 400px;
}
.muted-text {
  color: #a0aec0;
  letter-spacing: 1px;
}

.scifi-calendar-panel {
  padding: 1.5rem;
  background:
    linear-gradient(180deg, rgba(8, 14, 24, 0.48) 0%, rgba(6, 10, 18, 0.24) 100%);
  border: 1px solid rgba(156, 192, 236, 0.18);
  box-shadow:
    inset 0 0 0 1px rgba(220, 240, 255, 0.04),
    0 18px 40px rgba(4, 10, 18, 0.16);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.day-cell {
  height: 100%;
  min-height: 72px;
}

.event-indicators {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 8px;
}
.event-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.72rem;
  color: #e2e8f0;
  background: var(--th-indicator-surface, linear-gradient(90deg, rgba(16, 26, 40, 0.68) 0%, rgba(12, 18, 28, 0.26) 100%));
  padding: 4px 6px;
  border-radius: 0;
  border-left: 2px solid var(--th-indicator-accent, rgba(188, 226, 255, 0.82));
  box-shadow:
    inset 0 0 0 1px var(--th-indicator-edge, rgba(220, 240, 255, 0.04)),
    0 0 14px var(--th-indicator-glow, rgba(140, 230, 255, 0.12));
  cursor: pointer;
}
.event-dot {
  width: 5px; height: 5px;
  background: var(--th-indicator-accent, rgba(188, 226, 255, 0.95));
  border-radius: 50%;
  box-shadow: 0 0 8px var(--th-indicator-glow, rgba(140, 230, 255, 0.32));
}
.event-title-trunc {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.empty-text {
  color: #a0aec0;
  text-align: center;
  padding: 2rem;
}

.day-events-panel {
  width: min(760px, calc(100vw - 2rem));
  max-height: min(78vh, 760px);
  overflow: hidden;
  background: rgba(8, 14, 22, 0.66);
  border: 1px solid rgba(255, 255, 255, 0.26);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  padding: 0.9rem;
}

.day-events-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  margin-bottom: 0.75rem;
}

.day-events-header h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 500;
  color: #f8fafc;
}

.day-events-list {
  display: grid;
  gap: 0.5rem;
  overflow-y: auto;
  padding-right: 0.2rem;
  max-height: calc(min(78vh, 760px) - 64px);
}

.day-events-list::-webkit-scrollbar {
  width: 6px;
}

.day-events-list::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.28);
}

:deep(.day-events-modal .n-modal) {
  display: flex;
  justify-content: center;
  align-items: center;
}

@media (max-width: 640px) {
  .day-events-panel {
    width: calc(100vw - 1rem);
    max-height: 84vh;
    padding: 0.7rem;
  }

  .day-events-header h4 {
    font-size: 0.88rem;
  }

  .day-events-list {
    max-height: calc(84vh - 60px);
  }
}

/* 覆盖 Naive UI Calendar 在深色模式的基础颜色 */
.scifi-calendar-panel :deep(.n-calendar) {
  --n-text-color: #e2e8f0;
  --n-title-text-color: #fff;
  --n-day-text-color: rgba(188, 204, 224, 0.72);
  --n-border-color: rgba(156, 192, 236, 0.14);
  --n-cell-color-hover: rgba(22, 36, 54, 0.48);
  --n-bar-color: rgba(140, 230, 255, 0.7);
  --n-date-color-current: rgba(52, 92, 142, 0.72);
  --n-date-text-color-current: #f8fbff;
  --n-text-color-disabled: #475569;
}

.scifi-calendar-panel :deep(.n-calendar-header) {
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(156, 192, 236, 0.12);
  margin-bottom: 1rem;
}

.scifi-calendar-panel :deep(.n-calendar-header__title) {
  letter-spacing: 0.08em;
  text-shadow: 0 0 16px rgba(140, 230, 255, 0.12);
}

.scifi-calendar-panel :deep(.n-calendar-dates) {
  background: linear-gradient(180deg, rgba(6, 12, 20, 0.2) 0%, rgba(4, 8, 16, 0.12) 100%);
}

.scifi-calendar-panel :deep(.n-calendar-cell) {
  background: rgba(8, 14, 24, 0.08);
}

.scifi-calendar-panel :deep(.n-calendar-cell:hover) {
  box-shadow: inset 0 0 0 1px rgba(188, 226, 255, 0.12);
}

.scifi-calendar-panel :deep(.n-calendar-date) {
  gap: 0.4rem;
}

.scifi-calendar-panel :deep(.n-calendar-date__date) {
  color: #dbeafe;
  text-shadow: 0 0 10px rgba(140, 230, 255, 0.08);
}

.scifi-calendar-panel :deep(.n-calendar-cell--current .n-calendar-date__date) {
  box-shadow:
    0 0 0 1px rgba(188, 226, 255, 0.2),
    0 0 18px rgba(82, 168, 255, 0.12);
}

.scifi-calendar-panel :deep(.n-calendar-date__day) {
  color: rgba(188, 204, 224, 0.72);
}
</style>
