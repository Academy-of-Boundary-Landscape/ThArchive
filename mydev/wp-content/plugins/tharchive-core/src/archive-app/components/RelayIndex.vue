<template>
  <div class="relay-index-wrapper">
    <div class="relay-index">
        <!-- 筛选器与视图切换区域 -->
        <div class="scifi-panel filter-panel">
          <div class="panel-header-row">
            <div class="panel-header">
              <h3>接力活动列表</h3>
            </div>
            
            <div class="view-switch">
              <n-radio-group v-model:value="currentView" size="medium" class="console-switch">
                <n-radio-button value="list">列表视图</n-radio-button>
                <n-radio-button value="calendar">日历视图</n-radio-button>
              </n-radio-group>
            </div>
          </div>
          
          <n-collapse-transition :show="currentView === 'list'">
            <n-space align="center" size="large" class="filter-controls" wrap>
              <div class="cyber-form-item">
                <label>状态</label>
                <n-select
                  v-model:value="filters.event_status"
                  :options="statusOptions"
                  placeholder="全部状态"
                  class="filter-select"
                  @update:value="applyFilters"
                />
              </div>
              
              <div class="cyber-form-item">
                <label>类型</label>
                <n-select
                  v-model:value="filters.event_type"
                  :options="typeOptions"
                  placeholder="全部分类"
                  class="filter-select"
                  @update:value="applyFilters"
                />
              </div>

              <div class="cyber-form-item">
                <label>年份</label>
                <n-select
                  v-model:value="filters.year"
                  :options="yearOptions"
                  placeholder="全部年份"
                  class="filter-select"
                  @update:value="applyFilters"
                />
              </div>

              <div class="cyber-form-item">
                <label>关键词</label>
                <n-input
                  v-model:value="filters.search"
                  type="text"
                  placeholder="搜索记录..."
                  class="filter-search"
                  clearable
                  @keyup.enter="applyFilters"
                />
              </div>

              <div class="cyber-form-item cyber-form-item--range">
                <label>时间段</label>
                <n-date-picker
                  :formatted-value="filters.date_range"
                  type="daterange"
                  value-format="yyyy-MM-dd"
                  clearable
                  class="filter-date-range"
                  @update:formatted-value="onDateRangeChange"
                />
              </div>

              <n-button type="default" ghost @click="applyFilters" class="cyber-btn console-btn console-btn--corner">
                执行检索
              </n-button>
            </n-space>
          </n-collapse-transition>
        </div>

        <!-- 当前视图渲染 -->
        <div v-if="currentView === 'list'" class="events-list">
          <div v-if="loading" class="state-container">
            <n-spin size="large" stroke="#fff">
              <template #description>
                <span class="muted-text">LOADING...</span>
              </template>
            </n-spin>
          </div>

          <div v-else-if="loadError" class="state-container">
            <n-empty :description="loadError">
              <template #extra>
                <n-button ghost size="small" class="console-btn console-btn--dashed" @click="fetchEvents">重新加载</n-button>
              </template>
            </n-empty>
          </div>
          
          <div v-else-if="displayEvents.length === 0" class="state-container">
            <n-empty description="未找到匹配的归档记录">
              <template #extra>
                <n-button ghost size="small" class="console-btn console-btn--dashed" @click="resetFilters">重置终端</n-button>
              </template>
            </n-empty>
          </div>

          <div v-else class="relay-card-grid">
            <EventCard v-for="event in displayEvents" :key="event.id" :event="event" />
          </div>

          <!-- 极简分页 -->
          <div v-if="totalPages > 1" class="pagination-wrapper">
            <n-pagination
              v-model:page="currentPage"
              :page-count="totalPages"
              show-quick-jumper
              @update:page="changePage"
            />
          </div>
        </div>
        
        <div v-else-if="currentView === 'calendar'">
          <CalendarView />
        </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { 
  NSpace, 
  NSelect, 
  NInput, 
  NButton, 
  NDatePicker,
  NPagination, 
  NSpin, 
  NEmpty,
  NRadioGroup,
  NRadioButton,
  NCollapseTransition
} from 'naive-ui';
import EventCard from './EventCard.vue';
import CalendarView from './CalendarView.vue';
import type { ArchiveFilters, ArchiveTaxData, EventTerm, RelayEvent } from '@archive/types';
import { useArchiveApi } from '@archive/composables/useArchiveApi';

const { buildWpApiUrl, fetchJson, fetchRaw } = useArchiveApi();

type ArchiveView = 'list' | 'calendar';

const currentView = ref<ArchiveView>('list');

function getInitialViewFromUrl(): ArchiveView {
  if (typeof window === 'undefined') {
    return 'list';
  }

  const params = new URLSearchParams(window.location.search);
  const raw = (params.get('view') || params.get('tab') || window.location.hash.replace('#', '')).trim().toLowerCase();

  if (raw === 'calendar' || raw === 'recent') {
    return 'calendar';
  }

  return 'list';
}

const allEvents = ref<RelayEvent[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const loadError = ref('');
const pageSize = 12;

function createDefaultYears(span = 8): number[] {
  const currentYear = new Date().getFullYear();
  return Array.from({ length: span }, (_, index) => currentYear - index);
}

const availableYears = ref(createDefaultYears());
const filters = reactive<ArchiveFilters>({
  event_status: null,
  event_type: null,
  year: null,
  date_range: null,
  search: ''
});

const taxData = reactive<ArchiveTaxData>({
  event_status: [],
  event_type: []
});

const statusOptions = computed(() => {
  return [{ label: '全部状态', value: '' }, ...taxData.event_status.map((t: EventTerm) => ({ label: t.name, value: t.id }))];
});
const typeOptions = computed(() => {
  return [{ label: '全部分类', value: '' }, ...taxData.event_type.map((t: EventTerm) => ({ label: t.name, value: t.id }))];
});
const yearOptions = computed(() => {
  return [{ label: '全部年份', value: '' }, ...availableYears.value.map(y => ({ label: `${y}年`, value: y }))];
});

function normalizeDateLike(value: string): string {
  return value.replace(/\//g, '-').trim();
}

function parseEventDate(value?: string): Date | null {
  if (!value) {
    return null;
  }

  const normalized = normalizeDateLike(value);
  const match = normalized.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) {
    return null;
  }

  const parsed = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

const filteredEvents = computed(() => {
  const range = filters.date_range;
  if (!range?.[0] || !range?.[1]) {
    return allEvents.value;
  }

  const filterStart = parseEventDate(range[0]);
  const filterEnd = parseEventDate(range[1]);

  if (!filterStart || !filterEnd) {
    return allEvents.value;
  }

  return allEvents.value.filter((event) => {
    const eventStart = parseEventDate(event.meta?.event_date);
    if (!eventStart) {
      return false;
    }

    const eventEnd = parseEventDate(event.meta?.event_date_end) ?? eventStart;
    return eventStart <= filterEnd && eventEnd >= filterStart;
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredEvents.value.length / pageSize)));

const displayEvents = computed(() => {
  const startIndex = (currentPage.value - 1) * pageSize;
  return filteredEvents.value.slice(startIndex, startIndex + pageSize);
});

const fetchTaxonomies = async () => {
  try {
    const statusUrl = buildWpApiUrl('wp/v2/event_status');
    statusUrl.searchParams.set('per_page', '100');
    statusUrl.searchParams.set('orderby', 'name');
    statusUrl.searchParams.set('order', 'asc');
    taxData.event_status = await fetchJson<EventTerm[]>(statusUrl);

    const typeUrl = buildWpApiUrl('wp/v2/event_type');
    typeUrl.searchParams.set('per_page', '100');
    typeUrl.searchParams.set('orderby', 'name');
    typeUrl.searchParams.set('order', 'asc');
    taxData.event_type = await fetchJson<EventTerm[]>(typeUrl);
  } catch (error) {
    console.error('Failed to load taxonomies:', error);
  }
};

const fetchEvents = async () => {
  loading.value = true;
  loadError.value = '';
  allEvents.value = [];
  try {
    const aggregatedEvents: RelayEvent[] = [];
    let page = 1;
    let totalRemotePages = 1;

    while (page <= totalRemotePages) {
      const url = buildWpApiUrl('wp/v2/relay_event');
      url.searchParams.append('_embed', '1');
      url.searchParams.append('page', page.toString());
      url.searchParams.append('per_page', '100');

      if (filters.search.trim()) url.searchParams.append('search', filters.search.trim());
      if (filters.event_status) url.searchParams.append('event_status', filters.event_status.toString());
      if (filters.event_type) url.searchParams.append('event_type', filters.event_type.toString());
      if (filters.year) {
        url.searchParams.append('event_year', filters.year.toString());
      }

      const response = await fetchRaw(url);
      const totalPagesHeader = response.headers.get('X-WP-TotalPages');
      totalRemotePages = totalPagesHeader ? parseInt(totalPagesHeader, 10) : 1;

      const data = await response.json();
      if (Array.isArray(data)) {
        aggregatedEvents.push(...data);
      }

      page += 1;
    }

    allEvents.value = aggregatedEvents;

    const discoveredYears = aggregatedEvents
      .map((item: RelayEvent) => Number(item.meta?.event_year))
      .filter((year): year is number => Number.isInteger(year) && year > 0);

    if (discoveredYears.length > 0) {
      const mergedYears = new Set([...availableYears.value, ...discoveredYears]);
      availableYears.value = Array.from(mergedYears).sort((a, b) => b - a);
    }
  } catch (error) {
    loadError.value = '归档数据加载失败，请稍后重试。';
    console.error("Fetch error:", error);
  } finally {
    loading.value = false;
  }
};

const changePage = (page: number) => {
  currentPage.value = page;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const applyFilters = () => {
  currentPage.value = 1;
  fetchEvents();
};

const onDateRangeChange = (value: [string, string] | null) => {
  filters.date_range = value;
  applyFilters();
};

const resetFilters = () => {
  filters.event_status = null;
  filters.event_type = null;
  filters.year = null;
  filters.date_range = null;
  filters.search = '';
  currentPage.value = 1;
  fetchEvents();
};

onMounted(() => {
  currentView.value = getInitialViewFromUrl();
  fetchTaxonomies();
  fetchEvents();
});
</script>
