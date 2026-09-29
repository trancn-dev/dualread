<template>
  <div class="shell">
    <header class="site-header">
      <NuxtLink to="/" class="brand">DualRead</NuxtLink>
      <span class="tagline">Thư viện media</span>
    </header>

    <main class="container">
      <NuxtPage />
    </main>

    <footer class="site-footer container">
      Dữ liệu có cấu trúc cho AI:
      <a :href="`${apiBase}/media`" rel="nofollow">{{ apiBase }}/media</a>
    </footer>
  </div>
</template>

<script setup lang="ts">
  const apiBase = useRuntimeConfig().public.apiBase;
</script>
