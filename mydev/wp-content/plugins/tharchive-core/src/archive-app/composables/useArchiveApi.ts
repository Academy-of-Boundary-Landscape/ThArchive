import { useBootstrap } from '@archive/composables/useBootstrap'
import { requestJson, requestRaw } from '@shared/http'
import { buildWpApiUrl as buildWordPressApiUrl } from '@shared/wordpress'

export function useArchiveApi() {
  const bootstrap = useBootstrap()

  function buildWpApiUrl(path: string): URL {
    return buildWordPressApiUrl(bootstrap.restUrl, path)
  }

  async function fetchJson<T>(url: URL): Promise<T> {
    return requestJson<T>(url)
  }

  async function fetchRaw(url: URL, extra?: { signal?: AbortSignal | null }): Promise<Response> {
    return requestRaw(url, {
      signal: extra?.signal
    })
  }

  return {
    buildWpApiUrl,
    fetchJson,
    fetchRaw
  }
}
