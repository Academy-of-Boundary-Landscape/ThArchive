# tharchive 前端系统审阅与更新 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在不破坏现有功能的前提下，分阶段消除前端重复代码、修复已验证的真实缺陷、建立 CSS design token，提升 tharchive 插件与主题前端的可维护性和移动端体验。

**Architecture:** 前端由三个独立 Vue 3 + TypeScript + naive-ui 应用（archive-app / submission-app / carousel-app，构建后挂载到主题页面）和 WordPress 主题模板/CSS 组成。本计划遵循小步、可验证、可回滚原则，每个任务独立提交，不做大规模架构重构。

**Tech Stack:** Vue 3 (`<script setup>`)、TypeScript、naive-ui、Vite（多 mode 构建）、WordPress 主题（PHP + 原生 CSS/JS）。

**验证方式（本仓库无单测框架，遵循 AGENTS.md）：**
- 所有 TS/Vue 改动：在 `wp-content/plugins/tharchive-core/` 下运行 `npm run build`（先 `vue-tsc --noEmit` 类型检查，再三个 vite build），必须零错误。
- CSS/PHP 改动：手动打开相关页面（首页 `/`、活动详情 `single-relay_event`、归档列表、提交表单页）目测，并检查浏览器控制台无新增报错。
- 每个任务完成后单独 `git commit`，便于回滚。

---

## ⚠️ 审阅验证说明（重要，先读）

初轮多代理审阅报告把若干项标为"高严重度 bug"，经逐行核对**多为误报**，已从本计划剔除，避免基于幻觉改代码：

| 原"高危"项 | 核对结论 |
|---|---|
| 提交失败"卡死"在 loading | ❌ 不成立。`App.vue:178` 是**原生表单 POST**（`formRef.submit()` 整页跳转），`isSubmitting` 卡住无意义 |
| `ObjectURL` 内存泄漏 | ❌ 不成立。`onCoverChange`/`resetGalleryPreviews`/`onBeforeUnmount` 均已 `revokeObjectURL` |
| 轮播卸载未中止 AbortController | ❌ 不成立。这些组件根本没用 AbortController，Vue 3 卸载后赋值 ref 无害 |
| `target="_blank"` 缺 `rel="noopener"` | ❌ 不成立。`tharchive-template-tags.php:176/185/244` **早已有** `rel="noreferrer noopener"` |

**经验证确实成立、纳入本计划的项：** 大量重复代码（P1）、CSS 无 design token（P2）、上传缺文件大小校验、首页 `100vw` 横向溢出隐患、移动端缩略图列数、三处死代码。

执行各阶段时，**对每条改动仍需先打开目标文件确认当前代码与计划一致**（计划基于 2026-06-11 的代码快照）。

---

## 阶段总览

- **Phase A — 已验证小修复**（低风险、快速见效）：3 个任务
- **Phase B — 消除重复代码**（可维护性收益最高）：5 个任务
- **Phase C — CSS design token + 响应式**：3 个任务
- **Phase D — 打磨项**：按需

建议按 A → B → C → D 顺序执行，每阶段独立可交付、可暂停。

---

# Phase A — 已验证小修复

### Task A1: 首页轮播 `100vw` 横向溢出改为 `100%`

**问题：** `.front-hero__carousel` 用 `min(1100px, calc(100vw - 40px))`，`100vw` 在有竖向滚动条的桌面端会比内容区宽，可能触发整页横向滚动。父级 `.front-hero` 已是 `width:100%`，改用 `100%` 即可，移动端断点同理。

**Files:**
- Modify: `wp-content/themes/tharchive-theme/assets/css/front-page.css:95`
- Modify: `wp-content/themes/tharchive-theme/assets/css/front-page.css:152`

- [ ] **Step 1: 改桌面端宽度**

把第 95 行：
```css
  width: min(1100px, calc(100vw - 40px));
```
改为：
```css
  width: min(1100px, calc(100% - 40px));
```

- [ ] **Step 2: 改移动端断点宽度**

把第 152 行（`@media (max-width: 720px)` 内）：
```css
    width: min(1100px, calc(100vw - 20px));
```
改为：
```css
    width: min(1100px, calc(100% - 20px));
```

- [ ] **Step 3: 验证**

打开 `http://localhost:8080/` 首页，在桌面宽度和手机模拟（375px / 320px）下确认：轮播居中、**页面底部无横向滚动条**、轮播未被截断。控制台无报错。

- [ ] **Step 4: 提交**

```bash
git add wp-content/themes/tharchive-theme/assets/css/front-page.css
git commit -m "fix(theme): :bug: 首页轮播改用 100% 避免横向滚动"
```

---

### Task A2: 图片上传增加文件大小校验

**问题：** `SubmissionSectionImages.vue` 仅靠 `accept` 属性提示类型，**完全没有文件大小校验**，超大图片会被前端放行、提交后被服务器拒绝，用户无反馈。`SubmissionBootstrap.upload` 也没有大小上限字段。

**方案：** 在 bootstrap 的 `upload` 配置加 `maxFileSizeBytes`（前端默认 8MB；后续可由 PHP 注入真实 `upload_max_filesize`），在封面与图集选择时校验，超限用 `errors` 提示并拒绝该文件。

**Files:**
- Modify: `wp-content/plugins/tharchive-core/src/submission-app/types.ts:26-29`
- Modify: `wp-content/plugins/tharchive-core/src/submission-app/composables/useBootstrap.ts:13-16`
- Modify: `wp-content/plugins/tharchive-core/src/submission-app/components/SubmissionSectionImages.vue`

- [ ] **Step 1: 类型增加 `maxFileSizeBytes`**

在 `types.ts` 的 `upload` 块（第 26-29 行）由：
```ts
  upload: {
    acceptedImageTypes: string[]
    maxGalleryFiles: number
  }
```
改为：
```ts
  upload: {
    acceptedImageTypes: string[]
    maxGalleryFiles: number
    maxFileSizeBytes: number
  }
```

- [ ] **Step 2: fallback 默认值**

在 `useBootstrap.ts` 的 `upload`（第 13-16 行）由：
```ts
  upload: {
    acceptedImageTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    maxGalleryFiles: 20
  },
```
改为：
```ts
  upload: {
    acceptedImageTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    maxGalleryFiles: 20,
    maxFileSizeBytes: 8 * 1024 * 1024
  },
```

- [ ] **Step 3: 组件接收 prop 并实现校验**

在 `SubmissionSectionImages.vue` 的 `defineProps`（第 111-117 行）增加 `maxFileSizeBytes: number`：
```ts
const props = defineProps<{
  form: SubmissionFormState
  errors: Record<string, string>
  clearError: (field: string) => void
  acceptedImageTypes: string[]
  maxGalleryFiles: number
  maxFileSizeBytes: number
}>()
```

在 `formatAcceptedTypeHint` 函数后（约第 156 行后）新增辅助函数：
```ts
function isFileSizeValid(file: File): boolean {
  return file.size <= props.maxFileSizeBytes
}

function formatMaxSize(): string {
  return `${Math.round(props.maxFileSizeBytes / (1024 * 1024))}MB`
}
```

修改 `onCoverChange`（第 218-227 行）加入大小校验：
```ts
function onCoverChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  if (file && !isFileSizeValid(file)) {
    props.errors.coverFile = `封面图超过 ${formatMaxSize()} 上限，请压缩后再上传。`
    input.value = ''
    return
  }
  emit('update:coverFile', file)
  props.clearError('coverFile')
  revokeCoverPreview()
  if (file) {
    coverPreviewUrl.value = URL.createObjectURL(file)
  }
}
```

修改 `onGalleryChange`（第 229-246 行）在合并前过滤超大文件：
```ts
function onGalleryChange(event: Event) {
  const input = event.target as HTMLInputElement
  const incomingFiles = Array.from(input.files ?? [])
  const oversized = incomingFiles.filter((file) => !isFileSizeValid(file))
  const validFiles = incomingFiles.filter(isFileSizeValid)
  galleryError.value = oversized.length
    ? `有 ${oversized.length} 张图片超过 ${formatMaxSize()} 上限，已跳过。`
    : ''
  const mergedFiles = mergeGalleryFiles(props.form.galleryFiles, validFiles, props.maxGalleryFiles)

  emit('update:galleryFiles', mergedFiles)
  syncGalleryInputFiles(mergedFiles)
  resetGalleryPreviews()
  galleryPreviewItems.value = mergedFiles.map((file) => ({
    key: fileKey(file),
    name: file.name,
    url: URL.createObjectURL(file)
  }))

  if (galleryInputRef.value) {
    galleryInputRef.value.value = ''
  }
}
```

在响应式状态区（约第 130 行后）新增 `galleryError`：
```ts
const galleryError = ref('')
```

在模板的图集上限提示后（第 76-78 行 `<n-text v-if="isGalleryAtLimit"...>` 之后）新增超限提示：
```html
            <n-text v-if="galleryError" depth="3" class="submission-gallery-limit-note">
              {{ galleryError }}
            </n-text>
```

- [ ] **Step 4: 类型检查 + 构建**

```bash
cd wp-content/plugins/tharchive-core && npm run build
```
Expected: `vue-tsc` 零类型错误，三个 vite build 成功。

- [ ] **Step 5: 手动验证**

打开提交表单页第 4 步，选一张 >8MB 的图：封面应显示红色错误且不预览；图集选含超大图的多张应跳过超大者并提示。选正常图片应正常预览。

- [ ] **Step 6: 提交**

```bash
git add wp-content/plugins/tharchive-core/src/submission-app/ wp-content/plugins/tharchive-core/assets/dist/
git commit -m "feat(submission): :sparkles: 上传增加文件大小校验与超限提示"
```

> **后续可选（不在本任务内）：** 在 PHP 端把服务器真实 `wp_max_upload_size()` 注入到 `THARCHIVE_SUBMISSION_BOOTSTRAP.upload.maxFileSizeBytes`，使前端上限与后端一致。

---

### Task A3: 移动端图集缩略图列数优化

**问题：** `single-relay-event.css:766` 在 `@media (max-width: 640px)` 写死 `repeat(4, ...)`，320px 手机上每个缩略图仅 ~80px，过小难点按。

**Files:**
- Modify: `wp-content/themes/tharchive-theme/assets/css/single-relay-event.css:766`（执行前用 `grep -n "repeat(4" single-relay-event.css` 确认行号）

- [ ] **Step 1: 确认当前代码**

```bash
cd wp-content/themes/tharchive-theme/assets/css && grep -n "repeat(4" single-relay-event.css
```
找到 `@media (max-width: 640px)` 内的 `grid-template-columns: repeat(4, minmax(0, 1fr));`。

- [ ] **Step 2: 改为自适应列数**

把该行改为：
```css
    grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
```

- [ ] **Step 3: 验证**

打开任一活动详情页，在 320px / 375px / 480px 宽度下确认缩略图不小于 ~72px、排列整齐、可正常点按切换主图。

- [ ] **Step 4: 提交**

```bash
git add wp-content/themes/tharchive-theme/assets/css/single-relay-event.css
git commit -m "fix(theme): :iphone: 图集缩略图移动端改用自适应列数"
```

---

# Phase B — 消除重复代码

> 本阶段是可维护性收益最高的部分。核心是把跨组件/跨应用复制的逻辑收敛到共享位置。**每个任务务必先 `npm run build` 确认改动前后类型与构建均通过，再提交。**

### Task B1: 抽取共享文本工具 `toText`/`stripHtml`

**问题：** `toText`（去 HTML + 规整空格）在 `RecentEventsCarousel.vue:37`、`YearEventsCarousel.vue:45`、`carousel-app/App.vue` 各写一遍；`EventCard.vue`、`CalendarView.vue` 等也有等价 `stripHtml`。

**方案：** 在 archive-app 的 `utils/event-utils.ts` 增加导出 `stripHtmlToText`，各处改为导入。（carousel-app 与 archive-app 是独立构建，若 carousel-app 也要复用，放到 `src/shared/` 更合适——见 B5 统一规划；本任务先收敛 archive-app 内部。）

**Files:**
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/utils/event-utils.ts`
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/RecentEventsCarousel.vue:37-39`
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/YearEventsCarousel.vue:45-47`
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/EventCard.vue`（其内 `stripHtml`）
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/CalendarView.vue`（其内 `stripHtml`）
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/RelayIndex.vue`（其内 `stripHtml`，若有）

- [ ] **Step 1: 在 event-utils.ts 增加导出**

```ts
export function stripHtmlToText(value?: string): string {
  return (value ?? '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()
}
```

- [ ] **Step 2: 各组件删除本地实现，改为导入**

在每个组件中删除本地 `function toText(...)` / `function stripHtml(...)`，在 `import { ... } from '@archive/utils/event-utils'` 中加入 `stripHtmlToText`，并把调用处 `toText(x)` / `stripHtml(x)` 替换为 `stripHtmlToText(x)`。

执行前对每个文件先 `grep -n "stripHtml\|toText" <file>` 确认实现完全一致（都是 `replace(/<[^>]+>/g,'').replace(/\s+/g,' ').trim()`）；若某处实现不同，单独保留并在提交说明里注明。

- [ ] **Step 3: 构建验证**

```bash
cd wp-content/plugins/tharchive-core && npm run build
```
Expected: 零类型错误、构建成功。

- [ ] **Step 4: 手动验证**

打开归档页/日历/轮播，确认标题与摘要文本显示与之前一致（无残留 HTML 标签、无多余空格）。

- [ ] **Step 5: 提交**

```bash
git add wp-content/plugins/tharchive-core/src/archive-app/ wp-content/plugins/tharchive-core/assets/dist/
git commit -m "refactor(archive): :recycle: 抽取共享 stripHtmlToText 消除重复"
```

---

### Task B2: 抽取 `useCarouselData` 合并 Recent/Year 轮播取数逻辑

**问题：** `RecentEventsCarousel.vue` 与 `YearEventsCarousel.vue` 的 `loading/error/items` 状态、`mapEventToCarouselItem`、`fetch*` 取数逻辑、`<style>` 块几乎逐行相同，仅 URL 参数和文案不同。

**方案：** 新建 composable `useCarouselData`，封装 state + 取数 + 映射；两个组件改为薄包装（仅传入查询参数和文案）。`<style>` 块抽到 archive-app `styles.css` 的共享 `.carousel-block__*`。

**Files:**
- Create: `wp-content/plugins/tharchive-core/src/archive-app/composables/useCarouselData.ts`
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/RecentEventsCarousel.vue`
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/YearEventsCarousel.vue`
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/styles.css`

- [ ] **Step 1: 新建 composable**

`useCarouselData.ts`：
```ts
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
```

- [ ] **Step 2: 改写 RecentEventsCarousel.vue 的 `<script setup>`**

```ts
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
```
模板中 `@click="fetchRecentEvents"` 保持不变。删除该文件 `<style scoped>` 块（移到 styles.css，见 Step 4）。

- [ ] **Step 3: 改写 YearEventsCarousel.vue 的 `<script setup>`**

```ts
import { onMounted, watch } from 'vue'
import { NButton, NEmpty, NSpin } from 'naive-ui'
import EventCarousel from './EventCarousel.vue'
import { useCarouselData } from '@archive/composables/useCarouselData'

const props = defineProps<{ year: number }>()
const { loading, error, items, load } = useCarouselData()

function fetchYearEvents() {
  load({
    perPage: 18,
    extraParams: { event_year: String(props.year) },
    metaLabel: () => `${props.year}年`,
    errorText: `${props.year} 年活动加载失败，请稍后重试。`
  })
}

watch(() => props.year, fetchYearEvents)
onMounted(fetchYearEvents)
```
删除该文件 `<style scoped>` 块。

- [ ] **Step 4: 共享样式移入 styles.css**

在 `archive-app/styles.css` 追加（若已有同名类先确认不冲突）：
```css
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
```

- [ ] **Step 5: 构建验证**

```bash
cd wp-content/plugins/tharchive-core && npm run build
```
Expected: 零类型错误、构建成功。

- [ ] **Step 6: 手动验证**

打开包含"近期活动轮播"和"年度活动轮播"的页面，确认两者数据正常加载、加载态/错误态/重试按钮、样式均与之前一致；切换年份能正确刷新。

- [ ] **Step 7: 提交**

```bash
git add wp-content/plugins/tharchive-core/src/archive-app/ wp-content/plugins/tharchive-core/assets/dist/
git commit -m "refactor(archive): :recycle: 抽取 useCarouselData 合并轮播取数逻辑"
```

---

### Task B3: 抽取 `useCarousel` 合并两个 EventCarousel 的交互逻辑

**问题：** `carousel-app/components/EventCarousel.vue`(507行) 与 `archive-app/components/EventCarousel.vue`(382行) 的 `getOffset/slideClass/slideStyle/goPrev/goNext/watch` 等交互逻辑约 300 行高度重复，仅视觉样式（aura 动画、缩略图标题、prefers-reduced-motion）不同。

**方案（谨慎、分两步降低风险）：**
1. 先把**纯交互逻辑**抽成 composable `useCarousel(itemsRef)`（返回 `currentIndex/getOffset/slideClass/slideStyle/goPrev/goNext` 等），两个组件各自保留模板与样式，仅 import 该 composable。**不合并模板、不动样式**，把视觉差异留在各自组件。
2. 模板与样式的进一步合并（BaseCarousel + slot）风险较高，**留待 B3 验证稳定后单独评估**，不在本任务强行合并。

> ⚠️ 两个 EventCarousel 分属不同 Vite 构建（archive-app / carousel-app）。composable 要被两者共享须放在 `src/shared/`，并确认两个 app 的 `tsconfig`/vite alias 都能解析 `@shared`（执行前先 `grep -n "@shared\|shared" vite.config.ts tsconfig.json` 确认；若无 alias，本任务需先补 alias 或改用相对路径）。

**Files:**
- Inspect first: `wp-content/plugins/tharchive-core/vite.config.ts`、`tsconfig.json`（确认/新增 `@shared` alias）
- Create: `wp-content/plugins/tharchive-core/src/shared/useCarousel.ts`
- Modify: `wp-content/plugins/tharchive-core/src/carousel-app/components/EventCarousel.vue`
- Modify: `wp-content/plugins/tharchive-core/src/archive-app/components/EventCarousel.vue`

- [ ] **Step 1: 核对两个 EventCarousel 的交互函数逐行差异**

```bash
cd wp-content/plugins/tharchive-core/src
# 对照两个文件的 script 部分，列出 getOffset/slideClass/slideStyle/goPrev/goNext/watch 的实现差异
```
把**完全一致**的函数纳入 composable；**有差异**的保留在组件内或通过参数注入。在本步把差异清单写进提交说明，避免误合并。

- [ ] **Step 2: 确认/补 `@shared` alias**

```bash
grep -n "alias\|@shared\|@archive\|@carousel" vite.config.ts tsconfig.json
```
若已有 `@archive`/`@carousel` 模式，仿照新增 `@shared` 指向 `src/shared`（vite.config.ts 的 `resolve.alias` 与 tsconfig.json 的 `compilerOptions.paths` 都要加）。

- [ ] **Step 3: 新建 `shared/useCarousel.ts`**

（按 Step 1 的差异清单，仅纳入一致逻辑。骨架示例，实际签名以 Step 1 结论为准：）
```ts
import { ref, watch, type Ref } from 'vue'

export function useCarousel<T extends { id: number }>(items: Ref<T[]>) {
  const currentIndex = ref(0)

  function getOffset(index: number): number {
    const total = items.value.length
    if (total === 0) return 0
    let offset = index - currentIndex.value
    if (offset > total / 2) offset -= total
    if (offset < -total / 2) offset += total
    return offset
  }

  function goPrev() {
    const total = items.value.length
    if (total === 0) return
    currentIndex.value = (currentIndex.value - 1 + total) % total
  }

  function goNext() {
    const total = items.value.length
    if (total === 0) return
    currentIndex.value = (currentIndex.value + 1) % total
  }

  watch(items, () => { currentIndex.value = 0 })

  return { currentIndex, getOffset, goPrev, goNext }
}
```
> `slideClass`/`slideStyle` 若两组件视觉不同，**不要**放进 composable，保留在各自组件，仅复用上面的 index/offset/导航逻辑。

- [ ] **Step 4: 两个组件改为使用 composable**

各组件删除已纳入 composable 的本地函数，改为 `const { currentIndex, getOffset, goPrev, goNext } = useCarousel(itemsRef)`，模板与样式不动。

- [ ] **Step 5: 构建验证**

```bash
cd wp-content/plugins/tharchive-core && npm run build
```

- [ ] **Step 6: 手动验证**

分别打开 archive-app 轮播与 carousel-app（首页）轮播，确认左右切换、循环、视觉动画均与改动前一致。

- [ ] **Step 7: 提交**

```bash
git add wp-content/plugins/tharchive-core/ -- ':!*/node_modules/*'
git commit -m "refactor(carousel): :recycle: 抽取 useCarousel 复用轮播交互逻辑"
```

---

### Task B4: 删除已确认的死代码

**已 grep 确认无任何引用：**
- `SubmissionSectionSource.vue`（App.vue 未 import，全局无引用）
- `submission-app/utils/formData.ts`（`createSubmissionPayload` 仅定义、从未导入；表单走 hidden input）
- `submission-app/utils/topicParser.ts`（`parseTopic`/`topicParser` 全局无引用）

**Files:**
- Delete: `wp-content/plugins/tharchive-core/src/submission-app/components/SubmissionSectionSource.vue`
- Delete: `wp-content/plugins/tharchive-core/src/submission-app/utils/formData.ts`
- Delete: `wp-content/plugins/tharchive-core/src/submission-app/utils/topicParser.ts`

- [ ] **Step 1: 删除前再次确认零引用**

```bash
cd wp-content/plugins/tharchive-core/src
grep -rn "SubmissionSectionSource\|createSubmissionPayload\|formData\|topicParser\|parseTopic" . | grep -v "utils/formData.ts:\|utils/topicParser.ts:\|components/SubmissionSectionSource.vue:"
```
Expected: 无输出（除被删文件自身定义）。若有任何外部引用，**停止**并保留该文件。

- [ ] **Step 2: 删除文件**

```bash
git rm wp-content/plugins/tharchive-core/src/submission-app/components/SubmissionSectionSource.vue \
       wp-content/plugins/tharchive-core/src/submission-app/utils/formData.ts \
       wp-content/plugins/tharchive-core/src/submission-app/utils/topicParser.ts
```

- [ ] **Step 3: 构建验证**

```bash
cd wp-content/plugins/tharchive-core && npm run build
```
Expected: 零错误（确认没有任何隐式依赖）。

- [ ] **Step 4: 提交**

```bash
git add wp-content/plugins/tharchive-core/assets/dist/
git commit -m "chore(submission): :fire: 删除未引用的死代码文件"
```

---

### Task B5: 统一类型定义 `RelayEvent`（评估后执行）

**问题：** `carousel-app/types.ts` 的 `RelayEventLite` 与 `archive-app/types.ts` 的 `RelayEvent` 结构基本相同，重复定义有不一致风险。

**方案：** 在 `src/shared/types.ts` 定义单一 `RelayEvent` 与 `CarouselItem`，两 app 引用。

> ⚠️ 此任务**依赖 B3 已建立 `@shared` alias**。若 B3 未做或评估后认为合并收益不足（两 app 字段确有差异），**可跳过**，仅在 B3 完成后视情况执行。执行细节在 B3 落地后、本任务开始前补充确认（字段并集、可选性）。

- [ ] **Step 1: 对比两处类型字段，列出并集与差异**
- [ ] **Step 2: 在 `shared/types.ts` 定义统一类型**
- [ ] **Step 3: 两 app 改为 import 共享类型，删除本地重复定义**
- [ ] **Step 4: `npm run build` 验证零类型错误**
- [ ] **Step 5: 提交**

---

# Phase C — CSS design token + 响应式

> 目标：把散落在多个 CSS 文件里的硬编码颜色/间距/字号收敛为 `:root` 变量，并补齐响应式中间断点。**纯样式改动，逐文件小步提交，每步打开页面目测。**

### Task C1: 在主题建立 design token（颜色）

**问题：** `rgba(140, 230, 255, ...)`（accent 冷色）出现 30+ 次，`rgba(10, 15, 28, ...)`（深底）等多次硬编码，改色需改多处。

**Files:**
- Modify: `wp-content/themes/tharchive-theme/style.css`（`:root` 区，执行前 `grep -n ":root" style.css` 定位）

- [ ] **Step 1: 统计高频颜色值**

```bash
cd wp-content/themes/tharchive-theme
grep -rhoE "rgba\([0-9]+, ?[0-9]+, ?[0-9]+, ?[0-9.]+\)" assets/css style.css | sort | uniq -c | sort -rn | head -30
```
据输出确定要抽取的 token 清单（出现 ≥3 次的优先）。

- [ ] **Step 2: 在 `:root` 增加颜色变量**

在 `style.css` 现有 `:root` 块末尾追加（变量值取 Step 1 实测的高频值，示例）：
```css
  --accent-glow-strong: rgba(140, 230, 255, 0.6);
  --accent-glow-soft: rgba(140, 230, 255, 0.24);
  --surface-veil: rgba(10, 15, 28, 0.4);
```

- [ ] **Step 3: 逐文件替换高频值为 `var(--...)`**

对 `style.css`、`front-page.css`、`single-relay-event.css`、`archive.css` 中**完全相同**的高频 rgba 值替换为对应变量。一次只替换一个变量、一个文件，替换后立即目测页面。

- [ ] **Step 4: 验证 + 提交（每文件一次）**

打开首页/详情/归档页，确认配色无可见变化。
```bash
git add wp-content/themes/tharchive-theme/style.css wp-content/themes/tharchive-theme/assets/css/<改动的文件>
git commit -m "refactor(theme): :art: 抽取颜色 design token（<文件名>）"
```

---

### Task C2: 间距与字号 token

**问题：** `gap`/`padding` 用 16/18/20/24/32px 混用；字号 0.66/0.72/0.76/0.84rem 等过多微调值。

**Files:**
- Modify: `wp-content/themes/tharchive-theme/style.css`（`:root`）

- [ ] **Step 1: 统计间距/字号频次**

```bash
cd wp-content/themes/tharchive-theme
grep -rhoE "(gap|padding|margin): ?[0-9]+px" assets/css style.css | sort | uniq -c | sort -rn | head -20
grep -rhoE "font-size: ?[0-9.]+rem" assets/css style.css | sort | uniq -c | sort -rn | head -20
```

- [ ] **Step 2: 定义 spacing/text scale**

```css
  --space-xs: 8px;
  --space-sm: 12px;
  --space-md: 16px;
  --space-lg: 24px;
  --space-xl: 32px;
  --text-xs: 0.72rem;
  --text-sm: 0.85rem;
  --text-base: 1rem;
  --text-lg: 1.15rem;
```

- [ ] **Step 3: 仅替换"恰好等于 scale 值"的硬编码**

只替换数值正好相等的（如 `16px`→`var(--space-md)`）；不为了套用 scale 而改变实际像素值（避免视觉漂移）。其余非标准值保持原样或留待人工设计决定。

- [ ] **Step 4: 验证 + 提交**

目测各页面间距/字号无可见变化后提交。

---

### Task C3: 补齐响应式中间断点 + 收敛 vh 单位

**问题：** footer 在 768–980px 平板档为 2 列偏密；多处图集 `min-height: min(68vh, 760px)` 在竖屏手机过高。

**Files:**
- Modify: `wp-content/themes/tharchive-theme/style.css`（footer 媒体查询，`grep -n "@media" style.css` 定位）
- Modify: `wp-content/themes/tharchive-theme/assets/css/single-relay-event.css`（`grep -n "vh" single-relay-event.css` 定位）

- [ ] **Step 1: footer 增加 768px 断点**

在现有 980px / 640px 断点之间新增：
```css
@media (max-width: 768px) {
  /* 选择器需与现有 footer grid 选择器一致，执行前确认 */
  .tharchive-site-footer__grid {
    grid-template-columns: 1fr;
  }
}
```

- [ ] **Step 2: 图集高度收敛**

把详情页图集主图 `min-height: min(68vh, 760px)` 改为更保守值，并在 640px 以下加上限，例如：
```css
  min-height: min(56vh, 640px);
```
移动端断点内补：
```css
    max-height: 460px;
```

- [ ] **Step 3: 验证**

在 768px / 480px / 320px 宽度逐一确认 footer 单列、图集主图不过高、无横向滚动。

- [ ] **Step 4: 提交**

```bash
git add wp-content/themes/tharchive-theme/style.css wp-content/themes/tharchive-theme/assets/css/single-relay-event.css
git commit -m "fix(theme): :iphone: 补响应式中间断点并收敛图集高度"
```

---

# Phase D — 打磨项（按需，低优先）

以下为低优先打磨项，可在 A–C 完成后按需挑选，每项独立提交。**执行前同样逐条核对当前代码：**

- [ ] **D1:** `archive-app` 的 EventCarousel 补 `@media (prefers-reduced-motion: reduce)`，与 carousel-app 版本对齐（无障碍）。
- [ ] **D2:** `submission-app/App.vue:276` 的 `window.confirm` 改为 naive-ui `useDialog`（体验一致性）。
- [ ] **D3:** `single-relay-gallery.js` 现代化：`Array.prototype.slice.call` → `[...]`；键盘导航增加输入框焦点判断（避免在表单内误触发）。
- [ ] **D4:** `archive-app/types.ts` 收紧 `_embedded` 类型，减少 `any`（类型安全）。
- [ ] **D5:** 轮播魔法数字（`14deg`、`42%` 等）提取为命名常量并加注释。

---

## Self-Review 检查记录

- **Spec 覆盖：** 已确认方向（P0→P3）均有对应任务；经验证 P0"紧急 bug"多为误报，已在顶部说明并替换为真实成立项（文件大小校验、100vw、缩略图列数）。
- **Placeholder 扫描：** Phase A/B 含完整可执行代码；C/D 含精确文件定位命令与具体改动，依赖动态 grep 结果的步骤已写明"执行前确认行号/选择器"，非空泛占位。
- **类型一致性：** `stripHtmlToText`（B1）在 B2 的 composable 中被复用，命名一致；`maxFileSizeBytes`（A2）在 types/useBootstrap/组件三处命名一致；`useCarousel`/`useCarouselData` 为两个不同 composable，职责不重叠。
- **风险标注：** B3/B5 涉及跨构建共享，已标注需先确认 `@shared` alias，并把模板/样式合并的高风险部分明确排除在外、留待单独评估。
