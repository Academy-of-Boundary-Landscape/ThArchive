import type { SubmissionBootstrap, SubmissionFormState } from '@submission/types'

export function createSubmissionPayload(
  form: SubmissionFormState,
  bootstrap: SubmissionBootstrap
): Record<string, string | number> {
  return {
    action: bootstrap.action,
    _tharchive_return_url: bootstrap.returnUrl,
    tharchive_front_submit_nonce: bootstrap.nonce,
    tharchive_title: form.title,
    tharchive_excerpt: form.excerpt,
    tharchive_content: form.content,
    tharchive_character: form.character,
    tharchive_organizer: form.organizer,
    tharchive_event_date: form.eventDate,
    tharchive_bilibili_summary_url: form.bilibiliSummaryUrl,
    tharchive_archive_site_url: form.archiveSiteUrl,
    tharchive_source_raw_text: form.sourceRawText,
    tharchive_other_notes: form.otherNotes
  }
}
