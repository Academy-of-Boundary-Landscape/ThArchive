<template>
  <div class="event-carousel">
    <div v-if="items.length === 0" class="event-carousel__empty">
      {{ emptyText }}
    </div>

    <template v-else>
      <article class="event-carousel__stage">
        <button type="button" class="event-carousel__nav event-carousel__nav--prev console-btn console-btn--corner console-btn--compact" @click="goPrev">
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
            :aria-label="`切换到 ${item.title}`"
            @click="activeIndex = index"
          >
            <img
              v-if="item.imageUrl"
              :src="item.imageUrl"
              :alt="item.title"
              class="event-carousel__slide-image"
            />
            <div v-else class="event-carousel__slide-image event-carousel__slide-image--placeholder">NO COVER</div>
            <span class="event-carousel__slide-gloss" aria-hidden="true"></span>
            <span class="event-carousel__slide-thickness" aria-hidden="true"></span>
          </button>
        </div>

        <button type="button" class="event-carousel__nav event-carousel__nav--next console-btn console-btn--corner console-btn--compact" @click="goNext">
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
          <img v-if="item.imageUrl" :src="item.imageUrl" :alt="item.title" class="event-carousel__thumb-image" />
          <div v-else class="event-carousel__thumb-image event-carousel__thumb-image--placeholder">NO IMAGE</div>
          <span class="event-carousel__thumb-title">{{ item.title }}</span>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { CarouselItem } from '@archive/types'

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

function slideStyle(index: number): Record<string, string | number> {
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
}

.event-carousel__empty {
  padding: 1.5rem;
  border: 1px dashed rgba(255, 255, 255, 0.2);
  color: #94a3b8;
}

.event-carousel__stage {
  display: grid;
  grid-template-columns: 42px 1fr 42px;
  align-items: center;
  gap: 0.8rem;
  min-height: 420px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  background: rgba(8, 10, 16, 0.65);
  padding: 0.8rem;
}

.event-carousel__viewport {
  position: relative;
  min-height: 400px;
  overflow: hidden;
  perspective: 1400px;
  transform-style: preserve-3d;
}

.event-carousel__slide {
  --offset: 0;
  --abs-offset: 0;
  --depth: calc(var(--abs-offset) * -140px);
  --tilt: calc(var(--offset) * -14deg);
  --light-opacity: calc(0.45 - (var(--abs-offset) * 0.12));
  --edge-opacity: calc(0.65 - (var(--abs-offset) * 0.16));
  position: absolute;
  top: 50%;
  left: 50%;
  width: min(48vw, 560px);
  max-width: 560px;
  aspect-ratio: 16 / 9;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 0;
  background: rgba(7, 10, 16, 0.75);
  overflow: hidden;
  cursor: pointer;
  /* 只保留 compositor 属性；filter/border-color 移出，瞬间切换代替持续重绘 */
  transition: transform 0.35s ease, opacity 0.35s ease;
  transform-style: preserve-3d;
  transform: translate(-50%, -50%)
    translateX(calc(var(--offset) * 42%))
    translateZ(var(--depth))
    rotateY(var(--tilt))
    scale(calc(1 - (var(--abs-offset) * 0.14)));
  opacity: calc(1 - (var(--abs-offset) * 0.35));
  z-index: calc(20 - var(--abs-offset));
  filter: grayscale(calc(var(--abs-offset) * 0.45));
  box-shadow:
    0 26px 45px rgba(0, 0, 0, 0.45),
    0 2px 0 rgba(255, 255, 255, 0.06) inset,
    0 -22px 38px rgba(0, 0, 0, 0.24) inset;
}

.event-carousel__slide.is-active {
  border-color: rgba(255, 255, 255, 0.68);
  opacity: 1;
  filter: none;
  transform: translate(-50%, -50%)
    translateX(0)
    translateZ(0)
    rotateY(0deg)
    scale(1);
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
    linear-gradient(120deg, rgba(255, 255, 255, 0.38) 0%, rgba(255, 255, 255, 0.14) 22%, rgba(255, 255, 255, 0) 48%),
    radial-gradient(120% 90% at 78% 18%, rgba(130, 168, 255, 0.22), rgba(130, 168, 255, 0) 62%);
  opacity: max(0, var(--light-opacity));
  mix-blend-mode: screen;
  transition: opacity 0.35s ease;
}

.event-carousel__slide-thickness {
  position: absolute;
  inset: 0;
  pointer-events: none;
  transform-style: preserve-3d;
}

.event-carousel__slide-thickness::before {
  content: '';
  position: absolute;
  top: 0;
  right: -11px;
  width: 11px;
  height: 100%;
  transform-origin: left center;
  transform: rotateY(92deg);
  background: linear-gradient(to right, rgba(244, 248, 255, 0.35), rgba(18, 24, 34, 0.95));
  opacity: max(0.12, var(--edge-opacity));
}

.event-carousel__slide-thickness::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -10px;
  width: 100%;
  height: 10px;
  transform-origin: center top;
  transform: rotateX(-88deg);
  background: linear-gradient(to bottom, rgba(220, 231, 255, 0.22), rgba(11, 16, 24, 0.94));
  opacity: max(0.15, var(--edge-opacity));
}

.event-carousel__slide.is-active .event-carousel__slide-gloss {
  opacity: 0.58;
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
  width: 42px;
  height: 42px;
  border-radius: 0;
  color: #e2e8f0;
  cursor: pointer;
}

.event-carousel__caption-box {
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(8, 10, 16, 0.7);
  padding: 0.9rem 1rem;
}

.event-carousel__meta {
  margin: 0 0 0.35rem;
  color: #a5b4fc;
  font-size: 0.8rem;
}

.event-carousel__title {
  margin: 0;
  color: #fff;
  font-size: 1.15rem;
}

.event-carousel__title-link {
  color: inherit;
  text-decoration: none;
}

.event-carousel__desc {
  margin: 0.5rem 0 0;
  color: #cbd5e1;
  font-size: 0.9rem;
  max-width: 90%;
}

.event-carousel__thumb-strip {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(108px, 1fr));
  gap: 0.65rem;
}

.event-carousel__thumb {
  padding: 0;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(13, 17, 24, 0.75);
  color: #e2e8f0;
  cursor: pointer;
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
  aspect-ratio: 3 / 4;
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

.event-carousel__thumb-title {
  display: block;
  padding: 0.45rem;
  font-size: 0.74rem;
  line-height: 1.3;
  min-height: 2.2rem;
}

@media (max-width: 760px) {
  .event-carousel__stage {
    grid-template-columns: 1fr;
    min-height: 360px;
  }

  .event-carousel__nav {
    display: none;
  }

  .event-carousel__viewport {
    min-height: 320px;
  }

  .event-carousel__slide {
    width: min(88vw, 460px);
    transform: translate(-50%, -50%)
      translateX(calc(var(--offset) * 22%))
      translateZ(calc(var(--abs-offset) * -90px))
      rotateY(calc(var(--offset) * -11deg))
      scale(calc(1 - (var(--abs-offset) * 0.22)));
  }
}
</style>
