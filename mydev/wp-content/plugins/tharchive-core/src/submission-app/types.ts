export interface SubmissionFormState {
  title: string
  excerpt: string
  content: string
  character: string
  organizer: string
  eventDate: string
  bilibiliSummaryUrl: string
  archiveSiteUrl: string
  sourceRawText: string
  otherNotes: string
  coverFile: File | null
  galleryFiles: File[]
}

export interface SubmissionBootstrap {
  submitUrl: string
  action: string
  nonce: string
  returnUrl: string
  defaults: Record<string, never>
  suggestions: {
    characters: string[]
    organizers: string[]
  }
  upload: {
    acceptedImageTypes: string[]
    maxGalleryFiles: number
  }
  labels: {
    submitButton: string
    submittingText: string
  }
}

declare global {
  interface Window {
    THARCHIVE_SUBMISSION_BOOTSTRAP?: SubmissionBootstrap
  }
}
