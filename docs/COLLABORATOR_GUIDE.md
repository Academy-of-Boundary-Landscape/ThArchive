# THArchive 合作者技术文档

本文档是技术文档，面向参与开发、维护、排查和部署的人。

这里记录的是：

- 项目结构
- 核心数据类型
- 前端组件
- Shortcode 挂载方式
- 字段用途和主次关系
- 后台录入与审核的技术约定

如果你只是想日常录入活动或审核投稿，请先看根目录的 [README.md](../README.md)。

当前站点定位不是普通博客，而是一个以“活动归档”为核心的 WordPress 站点。

---

## 1. 管理员傻瓜式操作流程

这一节是给有后台权限的维护者看的。如果你只是想把活动录入进站点，并完成审核发布，只需要按下面做。

### 1.1 方式 A：后台直接新建活动

适用场景：

- 你自己就是整理者
- 这条活动不是用户前台投稿来的
- 你想直接手动录入

操作步骤：

1. 进入 WordPress 后台。
2. 左侧菜单打开“活动归档”。
3. 点击“新建活动”。
4. 先填写最重要的内容：
   - 标题
   - 一句话简介
   - 活动说明
   - 右侧“活动封面图”
5. 再填写主字段区：
   - 活动日期
   - 东方角色
   - 主办方
   - 活动状态
   - 总结专栏链接
   - 独立归档站链接
6. 如果有更多图片，在“活动图集”里从媒体库选择。
7. 检查无误后直接发布。

最少建议补齐这些字段：

- 标题
- 一句话简介
- 活动说明
- 活动封面图
- 活动日期
- 东方角色
- 主办方

### 1.2 方式 B：审核前台投稿

适用场景：

- 有用户通过前台投稿页提交了活动
- 你需要后台审核后再发布

操作步骤：

1. 进入 WordPress 后台。
2. 左侧菜单打开“活动归档”。
3. 点击“待审核投稿”。
4. 打开一篇待审核活动。
5. 重点检查这些内容：
   - 标题是否清楚
   - 一句话简介是否正常
   - 活动说明是否能读
   - 活动日期是否正确
   - 东方角色是否正确
   - 主办方是否正确
   - 是否上传了封面图
   - 总结专栏 / 独立归档站链接是否可用
6. 如果缺东西，就在编辑页直接补。
7. 检查完成后，点击“通过审核并发布”。

### 1.3 当前后台哪些字段最重要

打开活动编辑页时，不需要把所有字段都填满。优先级如下：

第一优先级：

- 标题
- 一句话简介
- 活动说明
- 活动封面图
- 活动日期
- 东方角色
- 主办方

第二优先级：

- 活动状态
- 总结专栏链接
- 独立归档站链接
- 图集
- 原始文本摘录

第三优先级：

- 其它备用字段
- 历史兼容字段
- 将来可能扩展的字段

### 1.4 管理员最容易搞错的地方

请特别注意这些点：

- `relay_event` 是活动条目，不是普通博客文章。
- 不要把它当成需要 Elementor 排版的页面。
- 右侧“活动封面图”就是主缩略图，不要另外再找一个“缩略图字段”。
- 图集和封面图不是一回事：
  - 封面图是主图
  - 图集是附加图片
- 有些字段现在只是备用字段，不填也不会影响主流程。

### 1.5 发布前最简检查清单

如果你赶时间，发布前至少确认这 6 件事：

1. 有没有标题
2. 有没有简介
3. 有没有活动说明
4. 有没有封面图
5. 有没有活动日期
6. 有没有主办方和东方角色

---

## 2. 项目当前状态

当前项目已经完成了下面这条主链路：

- 定义活动数据结构
- 前台投稿
- 后台待审核
- 单活动页展示
- 活动归档列表 / 日历视图
- 轮播展示

当前建议理解方式：

- `relay_event` 不是“文章内容页”，而是“结构化活动条目”
- WordPress 只负责承载数据、路由、后台录入和模板
- 较复杂的前台交互由 Vue 组件负责，并通过 Shortcode 挂到页面中

---

## 3. 目录结构

### 3.1 插件

主插件目录：

- `mydev/wp-content/plugins/tharchive-core`

它负责：

- 注册 `relay_event` 这个自定义文章类型
- 注册 taxonomy 和 meta 字段
- 提供前台投稿、归档页、轮播图的 Shortcode
- 提供前后端数据接口和资源加载

核心子目录：

- `includes/`
  - WordPress 侧逻辑
- `src/`
  - Vue 源码
- `assets/dist/`
  - 前端构建产物

### 3.2 主题

主题目录：

- `mydev/wp-content/themes/tharchive-theme`

它负责：

- 首页原生 PHP 模板
- 单活动页原生 PHP 模板
- 全站视觉风格
- 一些模板辅助函数

当前约定是：

- 首页：原生 PHP
- 单活动页：原生 PHP
- 其它复杂页面：Shortcode 挂载 Vue 组件

---

## 4. 核心数据类型

### 4.1 Custom Post Type

当前核心数据类型是：

- `relay_event`

定义位置：

- `plugins/tharchive-core/includes/post-types.php`

这个类型当前支持：

- `post_title`
- `post_content`
- `post_excerpt`
- `thumbnail`
- `custom-fields`

目前推荐的语义是：

- `post_title`：活动标题
- `post_excerpt`：一句话简介
- `post_content`：活动说明正文
- `featured image`：活动封面图

### 4.2 Taxonomy

定义位置：

- `plugins/tharchive-core/includes/taxonomies.php`

当前注册的 taxonomy：

- `event_type`
  - 活动类型
- `event_status`
  - 活动状态
- `organizer`
  - 主办方
- `touhou_topic`
  - 东方主题标签
- `touhou_character`
  - 东方角色

其中当前前台真正高频使用的是：

- `event_status`
- `organizer`
- `touhou_character`

### 4.3 Meta 字段

定义位置：

- `plugins/tharchive-core/includes/meta-fields.php`

当前注册的主要 meta：

- `event_year`
- `event_date`
- `event_date_end`
- `bilibili_summary_url`
- `archive_site_url`
- `source_raw_text`
- `other_notes`
- `gallery_images`

此外还保留了一批备用 / 扩展字段：

- `organizer_contact`
- `registration_info`
- `deadline_info`
- `publish_platform_info`
- `rules_markdown`
- `extra_archive_links`
- `archive_summary_markdown`
- `participant_count`
- `source_summary_url`

---

## 5. 当前哪些字段是真正有用的

下面是按“当前是否进入主流程”来划分的。

### 4.1 主流程字段

这些字段已经进入“前台投稿 / 后台审核 / 前台展示”的主流程：

- `post_title`
- `post_excerpt`
- `post_content`
- `featured image`
- `event_date`
- `event_year`
- `touhou_character`
- `organizer`
- `event_status`
- `bilibili_summary_url`
- `archive_site_url`
- `gallery_images`
- `source_raw_text`

### 4.2 当前会显示但不一定由前台投稿填写的字段

- `event_date_end`
- `touhou_topic`

### 4.3 目前主要是备用 / 历史兼容字段

这些字段已经注册，但当前不是主流程核心：

- `organizer_contact`
- `registration_info`
- `deadline_info`
- `publish_platform_info`
- `rules_markdown`
- `extra_archive_links`
- `archive_summary_markdown`
- `participant_count`
- `source_summary_url`
- `other_notes`

这些字段不要删，但协作者应把它们理解成：

- 后台补充信息
- 历史兼容
- 以后可能扩展

而不是“当前前台必须录入的字段”。

---

## 6. 当前前台投稿会提交哪些字段

处理位置：

- `plugins/tharchive-core/includes/submission-handler.php`

前台投稿当前提交的是精简版字段：

- `tharchive_title`
- `tharchive_excerpt`
- `tharchive_content`
- `tharchive_character`
- `tharchive_organizer`
- `tharchive_event_date`
- `tharchive_bilibili_summary_url`
- `tharchive_archive_site_url`
- `tharchive_source_raw_text`
- `tharchive_other_notes`
- `tharchive_cover_image`
- `tharchive_gallery_images[]`

落库后的结果：

- 标题写入 `post_title`
- 简介写入 `post_excerpt`
- 正文写入 `post_content`
- 角色写入 `touhou_character`
- 主办方写入 `organizer`
- 状态自动写成待审核
- 日期写入 `event_date`
- 年份从日期推导写入 `event_year`
- 链接、图集、原始文本写入对应 meta
- 投稿文章状态设为 `pending`

---

## 7. 当前前台展示用到了哪些字段

### 6.1 单活动页

模板位置：

- `themes/tharchive-theme/single-relay_event.php`
- `themes/tharchive-theme/inc/tharchive-template-tags.php`

当前会展示：

- 标题
- 简介
- 正文
- 封面图
- 活动日期 / 日期区间
- 东方角色
- 主办方
- 活动状态
- 主题标签
- 总结专栏按钮
- 独立归档站按钮
- 图集
- 原始文本摘录

说明：

- 单页里“东方角色”“主办方”现在是普通标签，不是可点击链接
- 总结专栏和独立归档站按钮即使没有链接也会占位，但会变成禁用态

### 6.2 归档列表 / 日历视图

Vue 组件位置：

- `plugins/tharchive-core/src/archive-app/components/RelayIndex.vue`
- `plugins/tharchive-core/src/archive-app/components/EventCard.vue`
- `plugins/tharchive-core/src/archive-app/components/CalendarView.vue`

列表卡片当前主要展示：

- 封面图
- 标题
- 活动日期 / 年份
- 摘要
- 状态标签

日历视图当前主要展示：

- 日期格中的活动条
- 活动标题摘要
- 当日活动弹窗列表

### 6.3 轮播

组件位置：

- `plugins/tharchive-core/src/carousel-app/App.vue`
- `plugins/tharchive-core/src/carousel-app/components/EventCarousel.vue`

轮播当前主要展示：

- 封面图
- 标题
- 摘要
- 年份
- 单活动链接

---

## 8. Vue 组件与挂载方式

### 7.1 Archive App

作用：

- 活动列表视图
- 日历视图
- 筛选
- 分页

主要源码：

- `plugins/tharchive-core/src/archive-app/`

前端产物：

- `plugins/tharchive-core/assets/dist/archive-app.js`
- `plugins/tharchive-core/assets/dist/archive-app.css`

Shortcode：

- `[tharchive_archive_app]`
- `[tharchive_relay_index]`

推荐挂载页面：

- `relay-list`

### 7.2 Submission App

作用：

- 前台投稿表单
- 用户提交活动信息

主要源码：

- `plugins/tharchive-core/src/submission-app/`

前端产物：

- `plugins/tharchive-core/assets/dist/submission-app.js`
- `plugins/tharchive-core/assets/dist/submission-app.css`

Shortcode：

- `[tharchive_event_submission_form]`

推荐挂载页面：

- `submit`

### 7.3 Carousel App

作用：

- 独立活动轮播
- 首页近期活动展示
- 专题页 / 说明页内嵌展示

主要源码：

- `plugins/tharchive-core/src/carousel-app/`

前端产物：

- `plugins/tharchive-core/assets/dist/carousel-app.js`
- `plugins/tharchive-core/assets/dist/carousel-app.css`

Shortcode：

- `[tharchive_event_carousel]`
- `[tharchive_relay_carousel]`

常用示例：

```text
[tharchive_event_carousel mode="recent" per_page="7" orderby="date" order="desc" title="近期活动"]
```

```text
[tharchive_event_carousel mode="year" year="2024" per_page="18" orderby="title" order="asc" title="2024 年活动轮播"]
```

---

## 9. 页面路由和当前推荐挂载策略

### 原生模板页面

- 首页
  - 主题原生模板
  - 文件：`themes/tharchive-theme/front-page.php`
- 单活动页
  - 主题原生模板
  - 文件：`themes/tharchive-theme/single-relay_event.php`

### Shortcode 页面

- 投稿页：`submit`
  - 挂 `[tharchive_event_submission_form]`
- 关于页：`about`
  - 可挂纯文案，也可挂轮播
- 活动列表页：`relay-list`
  - 挂 `[tharchive_archive_app]`

主题里当前页面 helper 的约定：

- 投稿页 slug：`submit`
- 关于页 slug：`about`

---

## 10. 后台录入与审核流程

### 9.1 管理后台

当前后台逻辑主要在：

- `includes/admin-meta-boxes.php`
- `includes/admin-save.php`
- `includes/admin-review.php`

目前后台已经做过这些收口：

- `relay_event` 使用经典编辑器，不走 Gutenberg
- 尽量不走 Elementor 页面构建器路线
- “活动封面图”被突出到右侧优先位置
- Meta Box 已按“主字段 / 备用字段”分层
- 图集已改成媒体库选择器

### 9.2 审核流程

前台投稿后：

- 创建 `relay_event`
- 状态为 `pending`
- 写入前台投稿标记

管理员审核入口：

- 后台菜单中的“待审核投稿”

管理员典型操作：

- 打开待审核投稿
- 检查和补齐字段
- 设置封面图 / 图集
- 一键通过并发布

---

## 11. 目前最重要的协作边界

协作者在当前阶段应优先遵守这些原则：

- 不要把 `relay_event` 当普通文章系统来扩展
- 不要轻易删除备用字段，但也不要把它们当主流程字段
- 优先复用现有 Shortcode 和现有 Vue 应用
- 首页和单活动页优先维持原生模板路线
- 复杂列表、日历、轮播继续在 Vue 侧迭代
- 对数据结构的改动要谨慎，尤其是：
  - CPT
  - taxonomy
  - meta key
  - 投稿字段名
  - REST 参数

---

## 12. 构建方式

在插件目录执行：

```bash
cd mydev/wp-content/plugins/tharchive-core
npm run build
```

如果只改单个应用，也可以分别执行：

```bash
npm run build:submission
npm run build:archive
npm run build:carousel
```

---

## 13. 打包与部署

部署到生产环境时，建议分别打包插件和主题，而不是把整个开发目录一起上传。

### 13.1 先构建插件前端产物

```bash
cd mydev/wp-content/plugins/tharchive-core
npm run build
```

构建后应至少确认这些文件存在：

- `assets/dist/archive-app.js`
- `assets/dist/archive-app.css`
- `assets/dist/submission-app.js`
- `assets/dist/submission-app.css`
- `assets/dist/carousel-app.js`
- `assets/dist/carousel-app.css`

### 13.2 打包插件

在插件目录的上一层执行：

```bash
cd mydev/wp-content/plugins
zip -r tharchive-core.zip tharchive-core \
  -x "tharchive-core/node_modules/*" \
  -x "tharchive-core/src/*" \
  -x "tharchive-core/.git/*" \
  -x "tharchive-core/.DS_Store" \
  -x "tharchive-core/*.log"
```

### 13.3 打包主题

在主题目录的上一层执行：

```bash
cd mydev/wp-content/themes
zip -r tharchive-theme.zip tharchive-theme \
  -x "tharchive-theme/.git/*" \
  -x "tharchive-theme/.DS_Store" \
  -x "tharchive-theme/*.log"
```

### 13.4 上传到生产站

推荐走 WordPress 后台：

1. 进入 `插件 -> 安装插件 -> 上传插件`
2. 上传 `tharchive-core.zip`
3. 启用插件
4. 进入 `外观 -> 主题 -> 上传主题`
5. 上传 `tharchive-theme.zip`
6. 启用主题

### 13.5 上线后最少检查

至少检查这些页面和流程：

- 首页
- `event-list` 列表页
- 日历视图
- 投稿页
- 单活动页
- 后台“待审核投稿”
- 图片上传与图集显示

如果生产站已经有旧版本，建议先备份旧插件目录和旧主题目录，再覆盖更新。

---

## 14. 给合作者的最短结论

如果只记住最重要的几件事，可以记下面这些：

- 核心内容类型是 `relay_event`
- 主流程字段是标题、简介、正文、封面图、日期、角色、主办方、状态、归档链接、图集
- 首页和单活动页走主题原生模板
- 列表页、投稿页、轮播模块走 Shortcode + Vue
- 列表页用 `[tharchive_archive_app]`
- 投稿页用 `[tharchive_event_submission_form]`
- 轮播用 `[tharchive_event_carousel]`
- 修改 Vue 后记得重新构建
