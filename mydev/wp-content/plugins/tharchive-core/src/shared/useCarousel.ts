import { computed, ref, watch, type Ref } from 'vue'

export function useCarousel<T>(items: Ref<T[]>) {
  const activeIndex = ref(0)

  function getOffset(index: number): number {
    const total = items.value.length
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
    const total = items.value.length
    activeIndex.value = (activeIndex.value - 1 + total) % total
  }

  function goNext(): void {
    const total = items.value.length
    activeIndex.value = (activeIndex.value + 1) % total
  }

  watch(
    () => items.value.length,
    () => {
      activeIndex.value = 0
    }
  )

  const activeItem = computed(() => items.value[activeIndex.value] ?? items.value[0])

  return { activeIndex, getOffset, slideClass, slideStyle, goPrev, goNext, activeItem }
}
