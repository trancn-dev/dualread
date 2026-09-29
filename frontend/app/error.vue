<template>
  <div class="shell">
    <main class="container error-page">
      <h1>{{ error?.statusCode === 404 ? 'Không tìm thấy' : 'Đã xảy ra lỗi' }}</h1>
      <p class="muted">{{ error?.statusCode === 404 ? 'Media này không tồn tại hoặc chưa được công bố.' : error?.message }}</p>
      <button class="button" @click="clearError({ redirect: '/' })">Về trang chủ</button>
    </main>
  </div>
</template>

<script setup lang="ts">
  import type { NuxtError } from '#app';

  defineProps<{ error: NuxtError }>();
</script>
