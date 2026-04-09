# THArchive

东方 Project 同人接力活动归档站开发仓库。

这个项目的目标很简单：把东方同人接力活动整理成一个可提交、可审核、可展示、可归档的网站。

当前站点的核心功能包括：

- 活动投稿
- 后台审核与整理
- 活动列表浏览
- 日历视图浏览
- 首页 / 专题轮播展示
- 单个活动详情页展示

当前项目采用“WordPress 主题 + 自定义插件 + Vue 局部组件”的组合方案，但如果你只是日常维护站点，不需要先理解全部技术细节。

更详细的技术说明见：

- [合作者技术文档](docs/COLLABORATOR_GUIDE.md)

---

## 1. 这个站点现在能做什么

目前主流程已经成型：

1. 用户可以从前台提交接力活动信息。
2. 管理员可以在后台查看待审核投稿。
3. 管理员补充封面、日期、角色、主办方和归档链接后发布。
4. 访客可以通过首页、列表页、日历页和单活动页查看活动。

站点当前最重要的内容对象是：

- `relay_event`

它表示“一条接力活动记录”，不是普通博客文章。

---

## 2. 管理员傻瓜式操作

如果你有 WordPress 后台权限，日常使用只需要记住下面两种情况。

### 2.1 直接录入新活动

1. 进入后台 `活动归档 -> 新建活动`
2. 填写：
   - 标题
   - 一句话简介
   - 活动说明
   - 活动日期
   - 东方角色
   - 主办方
3. 在右侧设置“活动封面图”
4. 如果有更多图片，在“活动图集”里补充
5. 发布

最少建议补齐这些字段：

- 标题
- 简介
- 活动说明
- 封面图
- 活动日期
- 东方角色
- 主办方

### 2.2 审核前台投稿

1. 进入后台 `活动归档 -> 待审核投稿`
2. 打开一篇投稿
3. 重点检查：
   - 标题
   - 简介
   - 活动说明
   - 日期
   - 东方角色
   - 主办方
   - 封面图
   - 归档链接
4. 缺什么就补什么
5. 点击“通过审核并发布”

---

## 3. 项目结构

项目里最重要的四部分：

- `mydev/wp-content/plugins/tharchive-core`
- `mydev/wp-content/plugins/bili-html-cleaner`
- `mydev/wp-content/themes/tharchive-theme`
- `scripts`

可以这样理解：

- `tharchive-core` 插件负责数据结构、投稿、审核、归档、轮播等核心功能
- `bili-html-cleaner` 插件是后台工具，用于将 Bilibili 页面 HTML 清洗成 Markdown
- 主题负责首页、单活动页和全站视觉风格
- `scripts` 负责旧数据整理、Markdown 预处理、大模型抽取和导入前的数据加工

---

## 4. 最近更新

### 2026.4.9

- 前端代码质量整理：移除投稿应用中残留的调试代码，修复 Vue 组件直接修改 prop 的错误数据流，改为通过 `emit` 传递变更。
- 安全修复：移除归档列表和日历视图中 `v-html` 的 XSS 风险，改为纯文本渲染；投稿表单新增 URL 格式校验。
- CSS 质量整理：建立完整的斜切角 CSS 变量体系（`--angle-xs/card/action/media` 等），`single-relay-event.css` 中所有硬编码 clip-path 像素值统一替换为变量；修复 EventCard 的 `max-height` 过渡动画；将 CalendarView 全局样式收进 scoped 作用域。
- CSS 性能优化：sticky 导航栏 `backdrop-filter` 从 14px 降至 6px；移除流星元素上的 `filter: drop-shadow`；修复按钮扫光动画从 `left` 改为 `transform: translateX`（消除每帧 layout reflow）；`transition: all` 收窄为具体属性；添加 `@media (prefers-reduced-motion)` 支持。
- 前台首页性能：鼠标探照灯 CSS 变量更新改为 `requestAnimationFrame` 节流，将 GPU 重绘频率从 100Hz+ 降到屏幕刷新率。
- 新增 `bili-html-cleaner` 工具插件，用于将 Bilibili 专栏/Opus 页面 HTML 清洗为 Markdown，并生成可交给大模型继续整理的 Prompt。

### 2026.3.25

- 优化了 `tharchive-theme` 的单活动页，重点修复 Markdown 正文渲染与链接显示问题，并调整了首屏信息区布局。
- 单活动页的活动图集改成了带大图、缩略图和箭头切换的轻量轮播展示。
- `tharchive-core` 新增旧文章导出与 `relay_event` JSON 导入工具，可配合大模型流水线整理历史 Elementor 数据。
- 后台增加了”待补关键字段”相关筛选与管理入口，方便导入后继续人工补齐信息。

## 5. 文档入口

如果你只是维护站点，先看这个 README 就够了。

如果你要继续开发、排查、构建或部署，再看这些文档：

- [合作者技术文档](docs/COLLABORATOR_GUIDE.md)
- [关于页草稿](docs/about-update-log.md)
