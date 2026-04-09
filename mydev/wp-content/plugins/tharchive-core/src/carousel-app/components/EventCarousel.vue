<template>
  <div class="event-carousel">
    <div v-if="items.length === 0" class="event-carousel__empty">
      {{ emptyText }}
    </div>

    <template v-else>
      <article class="event-carousel__stage">
        <button type="button" class="event-carousel__nav event-carousel__nav--prev" @click="goPrev" aria-label="上一张">
          <span aria-hidden="true">&lt;</span>
        </button>

        <div class="event-carousel__viewport">
          <button
            v-for="(item, index) in items"
            :key="item.id"
            type="button"
            class="event-carousel__slide"
            :class="slideClass(index)"
            :style="slideStyle(index)"
            :aria-label="getSlideAriaLabel(item, index)"
            @click="onSlideClick(item, index)"
          >
            <span class="event-carousel__slide-aura" aria-hidden="true"></span>
            <span class="event-carousel__slide-media">
              <img
                v-if="item.imageUrl"
                :src="item.imageUrl"
                :alt="item.title"
                class="event-carousel__slide-image"
                :loading="Math.abs(getOffset(index)) <= 1 ? 'eager' : 'lazy'"
                decoding="async"
              />
              <div v-else class="event-carousel__slide-image event-carousel__slide-image--placeholder">NO COVER</div>
            </span>
            <span class="event-carousel__slide-gloss" aria-hidden="true"></span>
            <span class="event-carousel__slide-thickness" aria-hidden="true"></span>
          </button>
        </div>

        <button type="button" class="event-carousel__nav event-carousel__nav--next" @click="goNext" aria-label="下一张">
          <span aria-hidden="true">&gt;</span>
        </button>
      </article>

      <div class="event-carousel__caption-box">
        <p v-if="activeItem.meta" class="event-carousel__meta">{{ activeItem.meta }}</p>
        <h3 class="event-carousel__title">
          <a v-if="activeItem.href" :href="activeItem.href" class="event-carousel__title-link">{{ activeItem.title }}</a>
          <span v-else>{{ activeItem.title }}</span>
        </h3>
        <p v-if="activeItem.description" class="event-carousel__desc">{{ activeItem.description }}</p>
      </div>

      <div class="event-carousel__thumb-strip">
        <button
          v-for="(item, index) in items"
          :key="item.id"
          type="button"
          class="event-carousel__thumb"
          :class="{ 'is-active': index === activeIndex }"
          @click="activeIndex = index"
        >
          <img v-if="item.imageUrl" :src="item.imageUrl" :alt="item.title" class="event-carousel__thumb-image" loading="lazy" decoding="async" />
          <div v-else class="event-carousel__thumb-image event-carousel__thumb-image--placeholder">NO IMAGE</div>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { CarouselItem } from '@carousel/types'

const props = withDefaults(
  defineProps<{
    items: CarouselItem[]
    emptyText?: string
  }>(),
  {
    emptyText: '暂无可展示的活动。'
  }
)

const activeIndex = ref(0)

function getSlideAriaLabel(item: CarouselItem, index: number): string {
  if (index === activeIndex.value && item.href) {
    return `打开 ${item.title}`
  }

  return `切换到 ${item.title}`
}

function getOffset(index: number): number {
  const total = props.items.length
  let diff = index - activeIndex.value
  if (total > 1) {
    if (diff > total / 2) {
      diff -= total
    }
    if (diff < -total / 2) {
      diff += total
    }
  }
  return diff
}

function slideClass(index: number): Record<string, boolean> {
  const offset = getOffset(index)
  return {
    'is-active': offset === 0,
    'is-side': Math.abs(offset) > 0 && Math.abs(offset) <= 2,
    'is-hidden': Math.abs(offset) > 2
  }
}

function slideStyle(index: number): Record<string, string> {
  const offset = getOffset(index)
  return {
    '--offset': String(offset),
    '--abs-offset': String(Math.abs(offset))
  }
}

function goPrev(): void {
  const total = props.items.length
  activeIndex.value = (activeIndex.value - 1 + total) % total
}

function goNext(): void {
  const total = props.items.length
  activeIndex.value = (activeIndex.value + 1) % total
}

function onSlideClick(item: CarouselItem, index: number): void {
  if (index === activeIndex.value && item.href) {
    window.location.href = item.href
    return
  }

  activeIndex.value = index
}

watch(
  () => props.items.length,
  () => {
    activeIndex.value = 0
  }
)

const activeItem = computed(() => props.items[activeIndex.value] ?? props.items[0])
</script>

<style scoped>
.event-carousel {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  width: 100%;
}

.event-carousel__empty {
  padding: 1.5rem;
  border: 1px dashed rgba(255, 255, 255, 0.2);
  color: #94a3b8;
}

.event-carousel__stage {
  position: relative;
  width: 100%;
  min-height: 620px;
  border: none;
  background: transparent;
  padding: 0 64px;
  box-sizing: border-box;
}

.event-carousel__viewport {
  position: relative;
  width: 100%;
  min-height: 600px;
  overflow: visible;
  perspective: 1400px;
  transform-style: preserve-3d;
}

.event-carousel__slide {
  --offset: 0;
  --abs-offset: 0;
  --depth: calc(var(--abs-offset) * -90px);
  --tilt: calc(var(--offset) * -17deg);
  --light-opacity: calc(0.45 - (var(--abs-offset) * 0.12));
  --edge-opacity: calc(0.65 - (var(--abs-offset) * 0.16));
  --shadow-opacity: calc(0.42 - (var(--abs-offset) * 0.08));
  position: absolute;
  top: 50%;
  left: 50%;
  display: block;
  width: clamp(220px, 24vw, 340px);
  max-width: 340px;
  aspect-ratio: 3 / 4;
  margin: 0;
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0;
  background: rgba(7, 10, 16, 0.24);
  clip-path: none;
  overflow: visible;
  cursor: pointer;
  appearance: none;
  -webkit-appearance: none;
  font: inherit;
  letter-spacing: normal;
  text-transform: none;
  text-shadow: none;
  /* 只保留 compositor 属性；filter/border-color 移出，瞬间切换代替持续重绘 */
  transition: transform 0.35s ease, opacity 0.35s ease;
  transform-style: preserve-3d;
  transform: translate(-50%, -50%)
    translateX(calc(var(--offset) * 68%))
    translateZ(var(--depth))
    rotateY(var(--tilt))
    scale(calc(1 - (var(--abs-offset) * 0.12)));
  opacity: calc(1 - (var(--abs-offset) * 0.28));
  z-index: calc(20 - var(--abs-offset));
  filter: grayscale(calc(var(--abs-offset) * 0.45));
  box-shadow:
    0 34px 64px rgba(0, 0, 0, var(--shadow-opacity)),
    0 18px 36px rgba(6, 14, 28, 0.24),
    0 0 10px rgba(140, 230, 255, 0.05),
    0 0 22px rgba(140, 230, 255, 0.03);
  isolation: isolate;
}

.event-carousel__slide-media {
  position: absolute;
  inset: 0;
  overflow: hidden;
  background:
    linear-gradient(180deg, rgba(15, 20, 30, 0.12), rgba(6, 9, 16, 0.28)),
    rgba(7, 10, 16, 0.24);
  z-index: 2;
}

.event-carousel__slide-aura {
  position: absolute;
  inset: -18%;
  pointer-events: none;
  background:
    radial-gradient(circle at 50% 50%, rgba(140, 230, 255, 0.16) 0%, rgba(140, 230, 255, 0.09) 18%, rgba(140, 230, 255, 0.045) 36%, rgba(140, 230, 255, 0.014) 54%, rgba(140, 230, 255, 0) 78%);
  opacity: calc(0.1 - (var(--abs-offset) * 0.025));
  z-index: 1;
  transition: opacity 0.35s ease, transform 0.35s ease;
  transform: scale(calc(1 - (var(--abs-offset) * 0.02)));
}

.event-carousel__slide.is-active {
  border-color: rgba(140, 230, 255, 0.2);
  opacity: 1;
  filter: none;
  transform: translate(-50%, -50%)
    translateX(0)
    translateZ(0)
    rotateY(0deg)
    scale(1);
  box-shadow:
    0 40px 78px rgba(0, 0, 0, 0.46),
    0 20px 44px rgba(6, 14, 28, 0.3),
    0 0 14px rgba(140, 230, 255, 0.12),
    0 0 34px rgba(140, 230, 255, 0.06);
}

.event-carousel__slide.is-hidden {
  opacity: 0;
  pointer-events: none;
}

.event-carousel__slide-gloss {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(120deg, rgba(255, 255, 255, 0.32) 0%, rgba(255, 255, 255, 0.11) 22%, rgba(255, 255, 255, 0) 48%),
    radial-gradient(120% 90% at 78% 18%, rgba(130, 168, 255, 0.18), rgba(130, 168, 255, 0) 62%);
  opacity: max(0, var(--light-opacity));
  /* mix-blend-mode: screen 在深色背景下效果与 normal 几乎相同，
     但会强制为每个可见 slide 创建独立 GPU 合成层。移除以减少合成层数量。
     光效透明度微调 0.38→0.32 / 0.22→0.18 以视觉补偿。 */
  transition: opacity 0.35s ease;
  z-index: 3;
}

.event-carousel__slide-thickness {
  position: absolute;
  inset: 0;
  pointer-events: none;
  /* 移除 preserve-3d：伪元素改为 2D 平面渐变条，消除 3D 渲染上下文开销 */
  z-index: 4;
}

.event-carousel__slide-thickness::before {
  content: '';
  position: absolute;
  top: 0;
  right: -8px;
  width: 8px;
  height: 100%;
  /* 移除 rotateY(92deg)：改为 2D 平面渐变，视觉效果相近，无 3D 变换开销 */
  background: linear-gradient(to right, rgba(244, 248, 255, 0.35), rgba(18, 24, 34, 0.95));
  opacity: max(0.12, var(--edge-opacity));
}

.event-carousel__slide-thickness::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -8px;
  width: 100%;
  height: 8px;
  /* 移除 rotateX(-88deg)：同上 */
  background: linear-gradient(to bottom, rgba(220, 231, 255, 0.22), rgba(11, 16, 24, 0.94));
  opacity: max(0.15, var(--edge-opacity));
}

.event-carousel__slide.is-active .event-carousel__slide-gloss {
  opacity: 0.64;
}

.event-carousel__slide.is-active .event-carousel__slide-aura {
  opacity: 0.18;
  transform: scale(1.08);
}

.event-carousel__slide-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.event-carousel__slide-image--placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.42);
  letter-spacing: 1px;
}

.event-carousel__nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  appearance: none;
  -webkit-appearance: none;
  width: 42px;
  height: 42px;
  margin: 0;
  border-radius: 0;
  color: #e2e8f0;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.45);
  background: rgba(10, 14, 22, 0.18);
  clip-path: none;
  overflow: visible;
  text-shadow: none;
  font-size: 0.95rem;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  z-index: 40;
}

.event-carousel__nav--prev {
  left: 0;
}

.event-carousel__nav--next {
  right: 0;
}

.event-carousel__caption-box {
  display: grid;
  gap: 0.42rem;
  justify-items: center;
  text-align: center;
  padding: 0.35rem 0 0.15rem;
}

.event-carousel__meta {
  margin: 0;
  color: #a5b4fc;
  font-size: 0.8rem;
  letter-spacing: 0.08em;
}

.event-carousel__title {
  margin: 0;
  color: #fff;
  font-size: 1.24rem;
  line-height: 1.2;
}

.event-carousel__title-link {
  color: inherit;
  text-decoration: none;
}

.event-carousel__desc {
  margin: 0;
  color: #cbd5e1;
  font-size: 0.9rem;
  line-height: 1.65;
  max-width: min(72ch, 100%);
}

.event-carousel__thumb-strip {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(108px, 1fr));
  gap: 0.65rem;
}

.event-carousel__thumb {
  appearance: none;
  -webkit-appearance: none;
  display: block;
  margin: 0;
  padding: 0;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(13, 17, 24, 0.75);
  color: #e2e8f0;
  cursor: pointer;
  clip-path: none;
  overflow: hidden;
  aspect-ratio: 3 / 4;
  text-shadow: none;
  font: inherit;
  letter-spacing: normal;
  text-transform: none;
  transition: transform 0.2s ease; /* border-color 移出 */
  text-align: left;
}

.event-carousel__thumb:hover,
.event-carousel__thumb.is-active {
  border-color: rgba(255, 255, 255, 0.55);
  transform: translateY(-1px);
}

.event-carousel__thumb-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.event-carousel__thumb-image--placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.7rem;
}

@media (max-width: 760px) {
  .event-carousel__stage {
    min-height: 520px;
    padding: 0;
  }

  .event-carousel__nav {
    display: none;
  }

  .event-carousel__viewport {
    min-height: 500px;
  }

  .event-carousel__slide {
    width: clamp(180px, 46vw, 260px);
    transform: translate(-50%, -50%)
      translateX(calc(var(--offset) * 52%))
      translateZ(calc(var(--abs-offset) * -70px))
      rotateY(calc(var(--offset) * -13deg))
      scale(calc(1 - (var(--abs-offset) * 0.18)));
  }
}

@media (prefers-reduced-motion: reduce) {
  .event-carousel__slide,
  .event-carousel__slide-aura,
  .event-carousel__slide-gloss,
  .event-carousel__thumb {
    transition: none;
  }

  .event-carousel__slide {
    transform: translate(-50%, -50%) translateX(calc(var(--offset) * 68%));
    opacity: calc(1 - (var(--abs-offset) * 0.28));
    filter: none;
  }

  .event-carousel__slide.is-active {
    transform: translate(-50%, -50%);
    opacity: 1;
  }
}
</style>
