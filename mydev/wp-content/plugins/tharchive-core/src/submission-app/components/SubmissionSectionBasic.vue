<template>
  <n-card title="1. 基础信息" class="submission-card">
    <n-form class="submission-section-form" label-placement="top" :show-require-mark="true">
      <n-form-item
        label="标题"
        required
        :validation-status="errors.title ? 'error' : undefined"
        :feedback="errors.title"
        data-field="title"
      >
        <n-input v-model:value="form.title" placeholder="例如：秘封组角色日接力 2026" @update:value="clearError('title')" />
      </n-form-item>

      <n-form-item
        label="一句话简介"
        required
        :validation-status="errors.excerpt ? 'error' : undefined"
        :feedback="errors.excerpt"
        data-field="excerpt"
      >
        <n-input
          v-model:value="form.excerpt"
          type="textarea"
          :autosize="{ minRows: 2 }"
          placeholder="一句话概括活动内容"
          @update:value="clearError('excerpt')"
        />
      </n-form-item>

      <n-grid cols="1 s:2" responsive="screen" :x-gap="16">
        <n-form-item-gi
          label="东方角色"
          required
          :validation-status="errors.character ? 'error' : undefined"
          :feedback="errors.character"
          data-field="character"
        >
          <n-auto-complete
            v-model:value="form.character"
            :options="characterOptions"
            :menu-props="autoCompleteMenuProps"
            placeholder="例如：博丽灵梦"
            @update:value="clearError('character')"
          />
        </n-form-item-gi>

        <n-form-item-gi
          label="活动日期"
          required
          :validation-status="errors.eventDate ? 'error' : undefined"
          :feedback="errors.eventDate"
          data-field="eventDate"
        >
          <n-date-picker
            :formatted-value="eventDateValue"
            type="date"
            value-format="yyyy-MM-dd"
            clearable
            style="width: 100%"
            @update:formatted-value="onEventDateChange"
          />
        </n-form-item-gi>
      </n-grid>

      <n-form-item
        label="主办方"
        required
        :validation-status="errors.organizer ? 'error' : undefined"
        :feedback="errors.organizer"
        data-field="organizer"
      >
        <n-auto-complete
          v-model:value="form.organizer"
          :options="organizerOptions"
          :menu-props="autoCompleteMenuProps"
          placeholder="例如：XX角色日活动组"
          @update:value="clearError('organizer')"
        />
      </n-form-item>

    </n-form>
  </n-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  NAutoComplete,
  NCard,
  NDatePicker,
  NForm,
  NFormItem,
  NFormItemGi,
  NGrid,
  NInput
} from 'naive-ui'
import type { SubmissionFormState } from '@submission/types'

const props = defineProps<{
  form: SubmissionFormState
  suggestions: {
    characters: string[]
    organizers: string[]
  }
  errors: Record<string, string>
  clearError: (field: string) => void
}>()

type AutoCompleteOption = { label: string; value: string }

const autoCompleteMenuProps = {
  class: 'submission-auto-complete-menu'
} as const

const characterPool = computed(() => normalizeSuggestionList(props.suggestions.characters))
const organizerPool = computed(() => normalizeSuggestionList(props.suggestions.organizers))

const characterOptions = computed(() => buildRankedOptions(characterPool.value, props.form.character))
const organizerOptions = computed(() => buildRankedOptions(organizerPool.value, props.form.organizer))

const eventDateValue = computed(() => toSafeFormattedDate(props.form.eventDate))

function onEventDateChange(value: string | null) {
  props.form.eventDate = value || ''
  props.clearError('eventDate')
}

function toSafeFormattedDate(value: string | undefined): string | null {
  if (!value) {
    return null
  }
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return null
  }
  const parsed = new Date(`${value}T00:00:00`)
  if (Number.isNaN(parsed.getTime())) {
    return null
  }
  return value
}

function normalizeSuggestionList(values: string[]): string[] {
  const normalized: string[] = []
  const visited = new Set<string>()

  values.forEach((rawValue) => {
    const value = rawValue.trim()
    if (!value) {
      return
    }

    const dedupeKey = value.toLocaleLowerCase()
    if (visited.has(dedupeKey)) {
      return
    }

    visited.add(dedupeKey)
    normalized.push(value)
  })

  return normalized
}

function buildRankedOptions(pool: string[], query: string, limit = 12): AutoCompleteOption[] {
  const normalizedQuery = query.trim().toLocaleLowerCase()
  if (!normalizedQuery) {
    return pool.slice(0, limit).map((value) => ({ label: value, value }))
  }

  return pool
    .map((value, index) => {
      const normalizedValue = value.toLocaleLowerCase()
      const exact = normalizedValue === normalizedQuery
      const startsWith = normalizedValue.startsWith(normalizedQuery)
      const includes = normalizedValue.includes(normalizedQuery)
      const score = exact ? 0 : startsWith ? 1 : includes ? 2 : 99
      return {
        value,
        score,
        index
      }
    })
    .filter((item) => item.score < 99)
    .sort((a, b) => {
      if (a.score !== b.score) {
        return a.score - b.score
      }
      return a.index - b.index
    })
    .slice(0, limit)
    .map((item) => ({ label: item.value, value: item.value }))
}
</script>
