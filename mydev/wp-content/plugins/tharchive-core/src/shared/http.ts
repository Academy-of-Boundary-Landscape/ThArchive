export interface HttpRequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  headers?: Record<string, string>
  credentials?: RequestCredentials
  body?: BodyInit | null
}

export async function requestJson<T>(url: URL | string, options: HttpRequestOptions = {}): Promise<T> {
  const response = await fetch(typeof url === 'string' ? url : url.toString(), {
    method: options.method ?? 'GET',
    headers: options.headers,
    credentials: options.credentials ?? 'same-origin',
    body: options.body ?? null
  })

  if (!response.ok) {
    throw new Error(`HTTP request failed: ${response.status}`)
  }

  return (await response.json()) as T
}

export async function requestRaw(url: URL | string, options: HttpRequestOptions = {}): Promise<Response> {
  const response = await fetch(typeof url === 'string' ? url : url.toString(), {
    method: options.method ?? 'GET',
    headers: options.headers,
    credentials: options.credentials ?? 'same-origin',
    body: options.body ?? null
  })

  if (!response.ok) {
    throw new Error(`HTTP request failed: ${response.status}`)
  }

  return response
}
