// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2026-09-29',
  devtools: { enabled: true },

  css: ['~/assets/css/main.css'],

  // Values are overridden at runtime by NUXT_API_BASE_SERVER / NUXT_PUBLIC_API_BASE.
  runtimeConfig: {
    // Used during SSR, inside the Docker network.
    apiBaseServer: '',
    public: {
      // Used by the browser.
      apiBase: '',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'vi' },
      title: 'DualRead',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'DualRead media library' },
      ],
    },
  },
});
