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
                  clearable
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
                  clearable
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
                  clearable
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
                  @clear="applyFilters"
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
                <n-button ghost size="small" class="console-btn console-btn--dashed" @click="() => fetchEvents()">重新加载</n-button>
              </template>
            </n-empty>
          </div>
          
          <div v-else-if="events.length === 0" class="state-container">
            <n-empty description="未找到匹配的归档记录">
              <template #extra>
                <n-button ghost size="small" class="console-btn console-btn--dashed" @click="resetFilters">重置终端</n-button>
              </template>
            </n-empty>
          </div>

          <template v-else>
            <div class="relay-card-grid">
              <EventCard v-for="event in events" :key="event.id" :event="event" />
            </div>

            <!-- 极简分页 -->
            <div v-if="totalPages > 1" class="pagination-wrapper">
              <n-pagination
                :page="currentPage"
                :page-count="totalPages"
                show-quick-jumper
                @update:page="changePage"
              />
            </div>
          </template>
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

const events = ref<RelayEvent[]>([]);
const loading = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const loadError = ref('');
const pageSize = 12;
let fetchAbortController: AbortController | null = null;

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
  return taxData.event_status.map((t: EventTerm) => ({ label: t.name, value: t.id }));
});
const typeOptions = computed(() => {
  return taxData.event_type.map((t: EventTerm) => ({ label: t.name, value: t.id }));
});
const yearOptions = computed(() => {
  return availableYears.value.map(y => ({ label: `${y}年`, value: y }));
});

const fetchTaxonomies = async () => {
  try {
    const statusUrl = buildWpApiUrl('wp/v2/event_status');
    statusUrl.searchParams.set('per_page', '100');
    statusUrl.searchParams.set('orderby', 'name');
    statusUrl.searchParams.set('order', 'asc');

    const typeUrl = buildWpApiUrl('wp/v2/event_type');
    typeUrl.searchParams.set('per_page', '100');
    typeUrl.searchParams.set('orderby', 'name');
    typeUrl.searchParams.set('order', 'asc');

    const [statusData, typeData] = await Promise.all([
      fetchJson<EventTerm[]>(statusUrl),
      fetchJson<EventTerm[]>(typeUrl),
    ]);
    taxData.event_status = statusData;
    taxData.event_type = typeData;
  } catch (error) {
    console.error('Failed to load taxonomies:', error);
  }
};

const fetchEvents = async (page = currentPage.value) => {
  if (fetchAbortController) {
    fetchAbortController.abort();
  }
  const controller = new AbortController();
  fetchAbortController = controller;

  // Snapshot reactive filters to prevent mid-request mutation.
  const snap = {
    search: filters.search.trim(),
    event_status: filters.event_status,
    event_type: filters.event_type,
    year: filters.year,
    date_range: filters.date_range ? [...filters.date_range] as [string, string] : null,
  };

  loading.value = true;
  loadError.value = '';
  try {
    const url = buildWpApiUrl('wp/v2/relay_event');
    url.searchParams.append('_embed', 'wp:featuredmedia,wp:term');
    url.searchParams.append('_fields', 'id,title,excerpt,link,meta,_links');
    url.searchParams.append('page', page.toString());
    url.searchParams.append('per_page', pageSize.toString());

    if (snap.search) url.searchParams.append('search', snap.search);
    if (snap.event_status) url.searchParams.append('event_status', snap.event_status.toString());
    if (snap.event_type) url.searchParams.append('event_type', snap.event_type.toString());
    if (snap.year) url.searchParams.append('event_year', snap.year.toString());
    if (snap.date_range?.[0]) url.searchParams.append('event_date_after', snap.date_range[0]);
    if (snap.date_range?.[1]) url.searchParams.append('event_date_before', snap.date_range[1]);

    const response = await fetchRaw(url, { signal: controller.signal });

    const remoteTotalPages = parseInt(response.headers.get('X-WP-TotalPages') ?? '1', 10);
    totalPages.value = Math.max(1, remoteTotalPages);

    const data = await response.json();
    events.value = Array.isArray(data) ? data : [];

    // Discover years for the year dropdown from the current page.
    const discoveredYears = events.value
      .map((item: RelayEvent) => Number(item.meta?.event_year))
      .filter((year): year is number => Number.isInteger(year) && year > 0);

    if (discoveredYears.length > 0) {
      const mergedYears = new Set([...availableYears.value, ...discoveredYears]);
      availableYears.value = Array.from(mergedYears).sort((a, b) => b - a);
    }
  } catch (error) {
    if ((error as DOMException)?.name === 'AbortError') {
      return;
    }
    loadError.value = '归档数据加载失败，请稍后重试。';
    console.error("Fetch error:", error);
  } finally {
    if (fetchAbortController === controller) {
      loading.value = false;
      fetchAbortController = null;
    }
  }
};

const changePage = (page: number) => {
  currentPage.value = page;
  window.scrollTo({ top: 0, behavior: 'smooth' });
  fetchEvents(page);
};

const applyFilters = () => {
  currentPage.value = 1;
  fetchEvents(1);
};

const onDateRangeChange = (value: [string, string] | null) => {
  filters.date_range = value;
  currentPage.value = 1;
  fetchEvents(1);
};

const resetFilters = () => {
  filters.event_status = null;
  filters.event_type = null;
  filters.year = null;
  filters.date_range = null;
  filters.search = '';
  currentPage.value = 1;
  fetchEvents(1);
};

onMounted(() => {
  currentView.value = getInitialViewFromUrl();
  fetchTaxonomies();
  fetchEvents();
});
</script>
