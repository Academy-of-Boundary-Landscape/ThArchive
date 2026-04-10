import { useBootstrap } from '@archive/composables/useBootstrap'
import { requestJson, requestRaw } from '@shared/http'
import { buildWpApiUrl as buildWordPressApiUrl, buildWpNonceHeaders } from '@shared/wordpress'

export function useArchiveApi() {
  const bootstrap = useBootstrap()
  const headers = buildWpNonceHeaders(bootstrap.nonce)

  function buildWpApiUrl(path: string): URL {
    return buildWordPressApiUrl(bootstrap.restUrl, path)
  }

  async function fetchJson<T>(url: URL): Promise<T> {
    return requestJson<T>(url, {
      headers
    })
  }

  async function fetchRaw(url: URL, extra?: { signal?: AbortSignal | null }): Promise<Response> {
    return requestRaw(url, {
      headers,
      signal: extra?.signal
    })
  }

  return {
    buildWpApiUrl,
    fetchJson,
    fetchRaw
  }
}
