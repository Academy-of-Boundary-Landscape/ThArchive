# THArchive 组件与 Shortcode 挂载说明

本文档用于给协作者快速说明：
1. 现有前端组件分别是什么
2. 在 WordPress 站点里如何用 Shortcode 挂载
3. 常见参数如何配置

---

## 1. 组件总览

### A. 活动列表应用（Archive App）
- 作用：展示接力活动列表，支持筛选、分页、视图切换（列表/日历/轮播）。
- 适用页面：接力活动总览页、归档页。
- 前端入口产物：
  - assets/dist/archive-app.js
  - assets/dist/archive-app.css
- 已注册 Shortcode：
  - [tharchive_archive_app]
  - [tharchive_relay_index]（别名）

### B. 投稿应用（Submission App）
- 作用：提供投稿表单，提交接力活动信息到后台审核流程。
- 适用页面：投稿页。
- 前端入口产物：
  - assets/dist/submission-app.js
  - assets/dist/submission-app.css
- 已注册 Shortcode：
  - [tharchive_event_submission_form]

### C. 独立轮播应用（Carousel App）
- 作用：单独展示活动轮播，可按近期或年份过滤，可排序。
- 适用页面：首页模块、专题页、活动推荐页、侧边内容区。
- 前端入口产物：
  - assets/dist/carousel-app.js
  - assets/dist/carousel-app.css
- 已注册 Shortcode：
  - [tharchive_event_carousel]
  - [tharchive_relay_carousel]（别名）

---

## 2. 构建与更新

在插件目录执行：

~~~bash
cd mydev/wp-content/plugins/tharchive-core
npm run build
~~~

说明：
- npm run build 会依次构建 submission-app、archive-app、carousel-app。
- 每次修改前端组件后，建议重新构建一次。

---

## 3. 在 WordPress 页面中挂载

### 方式 1：页面编辑器直接写 Shortcode（推荐）
在区块编辑器或经典编辑器正文中粘贴 Shortcode 即可。

### 方式 2：PHP 模板中挂载
在主题模板文件中调用：

~~~php
echo do_shortcode('[tharchive_event_carousel mode="recent"]');
~~~

---

## 4. Shortcode 用法

### 4.1 活动列表应用

Shortcode：
- [tharchive_archive_app]
- [tharchive_relay_index]

示例：
~~~text
[tharchive_archive_app]
~~~

说明：
- 该应用会挂载完整活动列表页面功能（筛选、分页、日历等）。
- 如需直接打开日历视图，可通过 URL 参数：
  - ?view=calendar
  - 示例：/relay-list/?view=calendar

---

### 4.2 投稿应用

Shortcode：
- [tharchive_event_submission_form]

示例：
~~~text
[tharchive_event_submission_form]
~~~

说明：
- 该应用会渲染投稿表单。
- 提交后由插件后端处理 nonce 校验与入库。

---

### 4.3 独立轮播应用

Shortcode：
- [tharchive_event_carousel]
- [tharchive_relay_carousel]

支持参数：
- mode：recent 或 year
- year：年份（仅 mode=year 时有效）
- per_page：数量，范围 1-30，默认 12
- orderby：date、modified、title，默认 date
- order：desc 或 asc，默认 desc
- title：轮播标题文本（可选）
- empty_text：无数据时显示文本（可选）

示例 1：近期活动（默认按日期倒序）
~~~text
[tharchive_event_carousel mode="recent" per_page="10" orderby="date" order="desc" title="近期接力"]
~~~

示例 2：某一年活动
~~~text
[tharchive_event_carousel mode="year" year="2024" per_page="18" orderby="title" order="asc" title="2024 年活动轮播"]
~~~

示例 3：自定义空数据文案
~~~text
[tharchive_event_carousel mode="year" year="2023" empty_text="2023 年暂无可展示活动"]
~~~

---

## 5. 页面推荐挂载策略

### 首页
- 可挂载 1-2 个轮播：
  - 一个 recent（近期）
  - 一个 year（指定年份精选）

### 接力列表页
- 挂载 [tharchive_archive_app] 作为主应用。

### 投稿页
- 挂载 [tharchive_event_submission_form]。

### 关于页或专题页
- 可挂载单个 [tharchive_event_carousel] 用于活动展示。

---

## 6. 常见问题排查

### Q1：页面显示“功能暂未就绪”
原因：前端产物未构建或未找到。

处理：
1. 进入插件目录执行 npm run build
2. 确认 assets/dist 下存在对应 js/css 文件

### Q2：轮播没有数据
检查项：
1. 站点里是否已有 relay_event 数据
2. mode=year 时 year 参数是否有对应年份数据
3. orderby/order 参数是否拼写正确

### Q3：短代码写了但页面没有显示
检查项：
1. 短代码是否写在页面正文而非代码注释里
2. 模板中是否正确调用 do_shortcode
3. 缓存是否未刷新

---

## 7. 给协作者的最小记忆版

- 列表页用：[tharchive_archive_app]
- 投稿页用：[tharchive_event_submission_form]
- 轮播用：[tharchive_event_carousel ...参数...]
- 修改前端后记得运行：npm run build
