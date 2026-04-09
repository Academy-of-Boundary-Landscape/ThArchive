<template>
  <n-card title="4. 素材与补充" class="submission-card">
    <div class="submission-section-stack">
      <n-form class="submission-section-form" label-placement="top">
        <n-form-item
          label="封面图"
          required
          :validation-status="errors.coverFile ? 'error' : undefined"
          :feedback="errors.coverFile"
          data-field="coverFile"
        >
          <label class="submission-upload-panel" :class="{ 'is-selected': !!form.coverFile }">
            <input
              ref="coverInputRef"
              class="submission-file-input submission-file-input--hidden"
              type="file"
              form="tharchive-submission-form"
              name="tharchive_cover_image"
              :accept="acceptAttr"
              @change="onCoverChange"
            />
            <span class="submission-upload-kicker">封面图上传</span>
            <span class="submission-upload-title">{{ form.coverFile ? '点击更换封面图' : '点击选择封面图' }}</span>
            <span class="submission-upload-hint">建议横版比例，{{ acceptedTypeHint }}</span>
          </label>

          <n-space v-if="form.coverFile && coverPreviewUrl" vertical :size="10" class="submission-image-preview-block">
            <n-text depth="3">已选择：{{ form.coverFile.name }}</n-text>
            <n-image
              class="submission-preview-image"
              :src="coverPreviewUrl"
              object-fit="cover"
              width="220"
              height="124"
              preview-disabled
            />
            <n-button size="small" tertiary @click="removeCover">移除封面</n-button>
          </n-space>
        </n-form-item>

        <n-form-item label="原始文本摘录（选填）">
          <n-input
            v-model:value="form.sourceRawText"
            type="textarea"
            :autosize="{ minRows: 4 }"
            placeholder="可粘贴原始专栏文本，便于后续自动解析。"
          />
        </n-form-item>

        <n-form-item label="其它备注（选填）">
          <n-input
            v-model:value="form.otherNotes"
            type="textarea"
            :autosize="{ minRows: 3 }"
            placeholder="补充说明、特殊情况或需要管理员注意的事项。"
          />
        </n-form-item>

        <n-form-item label="图集（活动的海报和宣传图等代表性艺术素材）" class="submission-gallery-form-item">
          <label class="submission-upload-panel submission-upload-panel--gallery" :class="{ 'is-selected': form.galleryFiles.length > 0 }">
            <input
              ref="galleryInputRef"
              class="submission-file-input submission-file-input--hidden"
              type="file"
              form="tharchive-submission-form"
              name="tharchive_gallery_images[]"
              multiple
              :accept="acceptAttr"
              @change="onGalleryChange"
            />
            <span class="submission-upload-kicker">图集上传</span>
            <span class="submission-upload-title">{{ form.galleryFiles.length > 0 ? '点击继续添加图集' : '点击选择图集图片' }}</span>
            <span class="submission-upload-hint">{{ acceptedTypeHint }}</span>
          </label>
          <div class="submission-gallery-meta-block">
            <n-text v-if="isGalleryAtLimit" depth="3" class="submission-gallery-limit-note">
              已达到图集上限（{{ maxGalleryFiles }} 张），如需继续添加请先移除部分图片。
            </n-text>

            <div v-if="galleryPreviewItems.length > 0" class="submission-gallery-scroll-wrap">
              <div class="submission-gallery-scroll-track">
                <n-card v-for="(item, index) in galleryPreviewItems" :key="item.key" embedded size="small" class="submission-gallery-preview-card">
                  <n-space vertical :size="8">
                    <n-image
                      class="submission-preview-image"
                      :src="item.url"
                      object-fit="cover"
                      height="112"
                      preview-disabled
                    />
                    <n-tag type="info" size="small" :bordered="false" class="submission-preview-filename" :title="item.name">
                      {{ item.name }}
                    </n-tag>
                    <n-button size="tiny" quaternary @click="removeGalleryFile(index)">移除</n-button>
                  </n-space>
                </n-card>
              </div>
            </div>
          </div>
        </n-form-item>
      </n-form>
    </div>
  </n-card>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { NButton, NCard, NForm, NFormItem, NImage, NInput, NSpace, NTag, NText } from 'naive-ui'
import type { SubmissionFormState } from '@submission/types'

const props = defineProps<{
  form: SubmissionFormState
  errors: Record<string, string>
  clearError: (field: string) => void
  acceptedImageTypes: string[]
  maxGalleryFiles: number
}>()

const emit = defineEmits<{
  'update:coverFile': [file: File | null]
  'update:galleryFiles': [files: File[]]
}>()

const acceptAttr = computed(() => props.acceptedImageTypes.join(','))
const acceptedTypeHint = computed(() => formatAcceptedTypeHint(props.acceptedImageTypes))
const isGalleryAtLimit = computed(() => props.form.galleryFiles.length >= props.maxGalleryFiles)
const coverInputRef = ref<HTMLInputElement | null>(null)
const galleryInputRef = ref<HTMLInputElement | null>(null)
const coverPreviewUrl = ref<string | null>(null)
const galleryPreviewItems = ref<Array<{ key: string; name: string; url: string }>>([])

function formatAcceptedTypeHint(types: string[]): string {
  if (!types.length) {
    return '支持常见图片格式'
  }

  const pretty = types.map((type) => {
    const normalized = type.trim().toLowerCase()
    if (normalized === 'image/jpeg') {
      return 'JPG'
    }
    if (normalized === 'image/png') {
      return 'PNG'
    }
    if (normalized === 'image/webp') {
      return 'WEBP'
    }
    if (normalized === 'image/gif') {
      return 'GIF'
    }
    const suffix = normalized.split('/').pop()
    return suffix ? suffix.toUpperCase() : normalized.toUpperCase()
  })

  return `支持 ${pretty.join(' / ')}`
}

function fileKey(file: File): string {
  return `${file.name}-${file.size}-${file.lastModified}`
}

function revokeCoverPreview() {
  if (coverPreviewUrl.value) {
    URL.revokeObjectURL(coverPreviewUrl.value)
    coverPreviewUrl.value = null
  }
}

function resetGalleryPreviews() {
  galleryPreviewItems.value.forEach((item) => URL.revokeObjectURL(item.url))
  galleryPreviewItems.value = []
}

function syncGalleryInputFiles(files: File[]) {
  if (!galleryInputRef.value) {
    return
  }
  const transfer = new DataTransfer()
  files.forEach((file) => transfer.items.add(file))
  galleryInputRef.value.files = transfer.files
}

function syncCoverInputFile(file: File | null) {
  if (!coverInputRef.value) {
    return
  }

  if (!file) {
    coverInputRef.value.value = ''
    return
  }

  const transfer = new DataTransfer()
  transfer.items.add(file)
  coverInputRef.value.files = transfer.files
}

function removeCover() {
  emit('update:coverFile', null)
  revokeCoverPreview()
  if (coverInputRef.value) {
    coverInputRef.value.value = ''
  }
}

function removeGalleryFile(index: number) {
  const nextFiles = props.form.galleryFiles.filter((_, fileIndex) => fileIndex !== index)
  emit('update:galleryFiles', nextFiles)
  syncGalleryInputFiles(nextFiles)
  resetGalleryPreviews()
  galleryPreviewItems.value = nextFiles.map((file) => ({
    key: fileKey(file),
    name: file.name,
    url: URL.createObjectURL(file)
  }))
}

function onCoverChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  emit('update:coverFile', file)
  props.clearError('coverFile')
  revokeCoverPreview()
  if (file) {
    coverPreviewUrl.value = URL.createObjectURL(file)
  }
}

function onGalleryChange(event: Event) {
  const input = event.target as HTMLInputElement
  const incomingFiles = Array.from(input.files ?? [])
  const mergedFiles = mergeGalleryFiles(props.form.galleryFiles, incomingFiles, props.maxGalleryFiles)

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

function mergeGalleryFiles(existingFiles: File[], incomingFiles: File[], maxGalleryFiles: number): File[] {
  const merged: File[] = []
  const visited = new Set<string>()

  ;[...existingFiles, ...incomingFiles].forEach((file) => {
    if (merged.length >= maxGalleryFiles) {
      return
    }

    const key = fileKey(file)
    if (visited.has(key)) {
      return
    }

    visited.add(key)
    merged.push(file)
  })

  return merged
}

onBeforeUnmount(() => {
  revokeCoverPreview()
  resetGalleryPreviews()
})

onMounted(() => {
  // Step 切换会导致组件重新挂载，这里把状态中的 File 回填到原生 input，避免提交时 $_FILES 丢失。
  if (props.form.coverFile) {
    syncCoverInputFile(props.form.coverFile)
    coverPreviewUrl.value = URL.createObjectURL(props.form.coverFile)
  }

  if (props.form.galleryFiles.length > 0) {
    syncGalleryInputFiles(props.form.galleryFiles)
    galleryPreviewItems.value = props.form.galleryFiles.map((file) => ({
      key: fileKey(file),
      name: file.name,
      url: URL.createObjectURL(file)
    }))
  }
})
</script>

<style scoped>
.submission-image-preview-block {
  margin-top: 8px;
}

.submission-upload-panel {
  display: grid;
  gap: 6px;
  width: 100%;
  border-radius: 12px;
  border: 1px dashed rgba(104, 139, 177, 0.65);
  background:
    linear-gradient(135deg, rgba(29, 45, 66, 0.9) 0%, rgba(20, 33, 50, 0.95) 100%),
    radial-gradient(circle at 85% 15%, rgba(95, 164, 255, 0.2) 0, rgba(95, 164, 255, 0) 55%);
  padding: 14px 14px 15px;
  cursor: pointer;
  transition: border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}

.submission-upload-panel:hover {
  border-color: rgba(126, 184, 255, 0.9);
  box-shadow: 0 10px 24px rgba(7, 14, 24, 0.36);
  transform: translateY(-1px);
}

.submission-upload-panel:focus-within {
  border-color: rgba(126, 184, 255, 0.95);
  box-shadow: 0 0 0 2px rgba(95, 164, 255, 0.28);
}

.submission-upload-panel.is-selected {
  border-style: solid;
  border-color: rgba(126, 184, 255, 0.95);
  background:
    linear-gradient(135deg, rgba(35, 55, 80, 0.92) 0%, rgba(24, 39, 58, 0.96) 100%),
    radial-gradient(circle at 85% 15%, rgba(95, 164, 255, 0.22) 0, rgba(95, 164, 255, 0) 58%);
}

.submission-upload-kicker {
  font-size: 12px;
  letter-spacing: 0.04em;
  color: rgba(168, 197, 229, 0.9);
}

.submission-upload-title {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.25;
  color: #eaf3ff;
}

.submission-upload-hint {
  font-size: 12px;
  line-height: 1.35;
  color: rgba(173, 201, 232, 0.8);
}

.submission-file-input--hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  clip-path: inset(50%);
  white-space: nowrap;
}

.submission-gallery-meta-block {
  width: 100%;
  display: block;
  margin-top: 10px;
}

.submission-gallery-form-item :deep(.n-form-item-blank) {
  display: block;
  width: 100%;
}

.submission-gallery-limit-note {
  display: block;
  margin-bottom: 8px;
  color: rgba(243, 205, 136, 0.92);
}

.submission-preview-image {
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
}

.submission-gallery-preview-card {
  border: 1px solid rgba(60, 82, 107, 0.45);
  overflow: hidden;
  flex: 0 0 196px;
  width: 196px;
}

.submission-gallery-preview-card :deep(.n-card__content),
.submission-gallery-preview-card :deep(.n-space) {
  min-width: 0;
}

.submission-gallery-preview-card :deep(.n-card__content) {
  padding: 8px !important;
}

.submission-gallery-preview-card :deep(.n-space) {
  gap: 6px !important;
}

.submission-gallery-scroll-wrap {
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 6px;
}

.submission-gallery-scroll-track {
  display: flex;
  gap: 10px;
  min-width: max-content;
}

.submission-gallery-scroll-wrap::-webkit-scrollbar {
  height: 8px;
}

.submission-gallery-scroll-wrap::-webkit-scrollbar-thumb {
  background: rgba(109, 146, 187, 0.72);
  border-radius: 999px;
}

.submission-gallery-scroll-wrap::-webkit-scrollbar-track {
  background: rgba(20, 33, 50, 0.45);
  border-radius: 999px;
}

.submission-preview-filename {
  display: block;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.submission-gallery-preview-card .submission-preview-image {
  width: 100%;
  height: 112px;
  border-radius: 6px;
  overflow: hidden;
}

.submission-gallery-preview-card .submission-preview-image :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
