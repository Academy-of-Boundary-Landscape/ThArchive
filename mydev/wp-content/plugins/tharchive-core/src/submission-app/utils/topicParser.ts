export function normalizeTopicText(topicsText: string): string {
  return topicsText
    .split(/[\n,，、;；]+/)
    .map((item) => item.trim())
    .filter(Boolean)
    .join(', ')
}
