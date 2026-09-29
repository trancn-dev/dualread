/**
 * $fetch bound to the Laravel API. During SSR it talks to the API over the
 * Docker network; in the browser it uses the publicly reachable URL.
 */
export function useApi() {
  const config = useRuntimeConfig();

  return $fetch.create({
    baseURL: import.meta.server ? config.apiBaseServer : config.public.apiBase,
    headers: { Accept: 'application/json' },
  });
}
