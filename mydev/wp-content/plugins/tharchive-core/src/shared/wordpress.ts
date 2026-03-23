export function toAbsoluteUrl(input: string): URL {
  try {
    return new URL(input)
  } catch {
    return new URL(input, window.location.origin)
  }
}

export function buildWpApiUrl(restBase: string, path: string): URL {
  const normalizedPath = path.startsWith('/') ? path.slice(1) : path
  return new URL(normalizedPath, toAbsoluteUrl(restBase))
}

export function buildWpNonceHeaders(nonce?: string): Record<string, string> | undefined {
  if (!nonce) {
    return undefined
  }

  return {
    'X-WP-Nonce': nonce
  }
}
