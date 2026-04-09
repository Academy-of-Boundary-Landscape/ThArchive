<template>
  <n-card class="cyber-card" hoverable size="small" :bordered="false" :content-style="{ padding: '0' }">
    <div class="event-badges" v-if="hasTerms(event, 'event_status')">
      <n-tag
        v-for="status in getTerms(event, 'event_status').slice(0, 2)"
        :key="status.id"
        type="default"
        size="small"
        bordered
        class="cyber-tag"
        :class="getStatusClass(status.name)"
      >
        {{ status.name }}
      </n-tag>
    </div>

    <div class="event-row">
      <a class="event-thumbnail-link" :href="event.link">
        <img v-if="hasThumbnail(event)" :src="getThumbnailUrl(event)" :alt="event.title?.rendered || '活动封面'" />
        <div v-else class="no-thumbnail">NULL</div>
      </a>

      <div class="cyber-card-content">
        <h4 class="event-title">
          <a :href="event.link">{{ stripHtml(event.title?.rendered) || '未命名活动' }}</a>
        </h4>

        <div class="event-meta-row">
          <div class="event-meta">
            <span v-if="event.meta && (event.meta.event_date || event.meta.event_year)" class="meta-item cyber-text">
              {{ event.meta.event_date || event.meta.event_year }}
            </span>
          </div>
        </div>

        <div class="event-excerpt">{{ stripHtml(event.excerpt?.rendered) }}</div>
      </div>
    </div>
  </n-card>
</template>

<script setup lang="ts">
import { NCard, NTag } from 'naive-ui';
import { getTerms, getThumbnailUrl, hasTerms, hasThumbnail } from '../utils/event-utils';
import type { RelayEvent } from '@archive/types';

defineProps<{
  event: RelayEvent;
}>();

function stripHtml(value?: string): string {
  return (value ?? '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()
}

function getStatusClass(name: string): string {
  const normalized = name.trim();

  if (normalized === '准备中') {
    return 'cyber-tag--preparing';
  }

  if (normalized === '进行中') {
    return 'cyber-tag--active';
  }

  if (normalized === '已结束') {
    return 'cyber-tag--finished';
  }

  return 'cyber-tag--default';
}
</script>

<style scoped>
.cyber-card {
  background: transparent;
  transition: transform 0.3s, border-color 0.3s;
  overflow: hidden;
  min-height: 72px;
  height: auto;
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 0;
  position: relative;
}
.cyber-card:hover {
  transform: translateY(-1px);
  border-color: rgba(255, 255, 255, 0.35);
}

.event-row {
  position: relative;
  min-height: 72px;
}

.event-thumbnail-link {
  position: absolute;
  left: 2px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  margin: 0;
  background: transparent;
  overflow: hidden;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.event-thumbnail-link img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  opacity: 0.9;
  transition: opacity 0.3s, transform 0.4s;
  filter: grayscale(10%);
}

.cyber-card:hover .event-thumbnail-link img {
  opacity: 1;
  transform: scale(1.04);
  filter: grayscale(0%);
}

.no-thumbnail {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: rgba(255, 255, 255, 0.3);
  font-size: 0.8rem;
  letter-spacing: 2px;
}

.event-badges {
  position: absolute;
  right: 8px;
  bottom: 8px;
  z-index: 3;
  display: flex;
  gap: 0.22rem;
  max-width: calc(100% - 88px);
  overflow: hidden;
  opacity: 1;
  transform: translateY(0);
  justify-content: flex-end;
}

.cyber-tag {
  color: #eff6ff;
  background: rgba(10, 16, 26, 0.68);
  border: 1px solid rgba(188, 226, 255, 0.28);
  font-size: 10px;
  box-shadow: 0 6px 16px rgba(4, 10, 18, 0.2);
}

.cyber-tag--preparing {
  color: #fef3c7;
  background: linear-gradient(180deg, rgba(96, 68, 12, 0.72) 0%, rgba(56, 38, 8, 0.54) 100%);
  border-color: rgba(251, 191, 36, 0.38);
}

.cyber-tag--active {
  color: #dcfce7;
  background: linear-gradient(180deg, rgba(18, 92, 54, 0.72) 0%, rgba(8, 52, 30, 0.54) 100%);
  border-color: rgba(52, 211, 153, 0.4);
}

.cyber-tag--finished {
  color: #dbeafe;
  background: linear-gradient(180deg, rgba(42, 60, 92, 0.72) 0%, rgba(18, 28, 48, 0.54) 100%);
  border-color: rgba(96, 165, 250, 0.34);
}

.cyber-tag--default {
  color: #e2e8f0;
  background: linear-gradient(180deg, rgba(24, 34, 50, 0.72) 0%, rgba(12, 18, 30, 0.54) 100%);
  border-color: rgba(188, 226, 255, 0.24);
}

.cyber-card-content {
  padding: 0.16rem 0.22rem 1.7rem 72px;
  min-width: 0;
}
.event-title {
  margin: 0 0 0.16rem;
  font-size: 0.82rem;
  font-weight: 500;
  line-height: 1.28;
}
.event-title a {
  text-decoration: none;
  color: #f8fafc;
  transition: color 0.3s;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cyber-card:hover .event-title a {
  color: #ffffff;
}

.event-meta-row {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.4rem;
}

.cyber-text {
  font-family: 'Consolas', 'Courier New', monospace;
  color: #cbd5e1;
  font-size: 0.7rem;
}
.event-meta {
  margin-bottom: 0.08rem;
}
.event-excerpt {
  font-size: 0.72rem;
  color: #94a3b8;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.45;
  margin-top: 0.1rem;
  padding-bottom: 1px;
  max-height: 1.1rem;
  transition: max-height 0.25s ease, margin-top 0.25s ease;
}

.event-excerpt :deep(p) {
  margin: 0;
  display: inline;
}

.event-excerpt :deep(br) {
  display: none;
}

.cyber-card:hover .event-excerpt,
.cyber-card:focus-within .event-excerpt {
  margin-top: 0.12rem;
  white-space: normal;
  text-overflow: clip;
  max-height: calc(1.45em * 6.2);
  overflow: hidden;
}

@media (max-width: 640px) {
  .event-row {
    min-height: 94px;
  }

  .event-thumbnail-link {
    width: 72px;
    height: 72px;
    left: 2px;
    top: 50%;
    transform: translateY(-50%);
  }

  .cyber-card-content {
    padding-left: 84px;
  }

  .event-title {
    font-size: 0.78rem;
  }

  .event-badges {
    right: 6px;
    bottom: 6px;
    max-width: calc(100% - 92px);
  }
}
</style>
