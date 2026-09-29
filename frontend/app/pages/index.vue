<template>
  <section>
    <form class="filters" @submit.prevent="applySearch">
      <nav class="type-tabs" aria-label="Loại media">
        <NuxtLink
          v-for="option in typeOptions"
          :key="option.value"
          :to="{ query: withQuery({ type: option.value || undefined }) }"
          :class="['tab', { active: (query.type || '') === option.value }]"
        >
          {{ option.label }}
        </NuxtLink>
      </nav>

      <input v-model="searchInput" type="search" class="input" placeholder="Tìm theo tiêu đề, mô tả…" />

      <select
        class="input"
        :value="query.sort || '-published_at'"
        aria-label="Sắp xếp"
        @change="navigateTo({ query: withQuery({ sort: ($event.target as HTMLSelectElement).value }) })"
      >
        <option value="-published_at">Mới nhất</option>
        <option value="published_at">Cũ nhất</option>
        <option value="title">Tiêu đề A–Z</option>
        <option value="-duration_seconds">Dài nhất</option>
        <option value="duration_seconds">Ngắn nhất</option>
      </select>
    </form>

    <p v-if="error" class="notice">Không tải được dữ liệu từ API.</p>

    <template v-else-if="data">
      <p class="muted small">{{ data.meta.total }} media</p>

      <p v-if="data.data.length === 0" class="notice">Không có media phù hợp.</p>

      <div class="grid">
        <MediaCard v-for="media in data.data" :key="media.id" :media="media" />
      </div>

      <nav v-if="data.meta.last_page > 1" class="pagination" aria-label="Phân trang">
        <NuxtLink
          v-if="data.meta.current_page > 1"
          class="button"
          :to="{ query: withQuery({ page: String(data.meta.current_page - 1) }, false) }"
        >
          ← Trước
        </NuxtLink>
        <span class="muted">Trang {{ data.meta.current_page }} / {{ data.meta.last_page }}</span>
        <NuxtLink
          v-if="data.meta.current_page < data.meta.last_page"
          class="button"
          :to="{ query: withQuery({ page: String(data.meta.current_page + 1) }, false) }"
        >
          Sau →
        </NuxtLink>
      </nav>
    </template>
  </section>
</template>

<script setup lang="ts">
  import type { Media, Paginated } from '~/types/media';

  const PER_PAGE = 12;

  const route = useRoute();
  const api = useApi();

  const query = computed(() => ({
    type: typeof route.query.type === 'string' ? route.query.type : '',
    search: typeof route.query.search === 'string' ? route.query.search : '',
    sort: typeof route.query.sort === 'string' ? route.query.sort : '',
    page: typeof route.query.page === 'string' ? route.query.page : '',
  }));

  const typeOptions = [
    { value: '', label: 'Tất cả' },
    { value: 'video', label: 'Video' },
    { value: 'audio', label: 'Audio' },
    { value: 'image', label: 'Hình ảnh' },
  ];

  const searchInput = ref(query.value.search);
  watch(() => query.value.search, (value) => (searchInput.value = value));

  /** Current query merged with changes; filter changes reset to page 1. */
  function withQuery(changes: Record<string, string | undefined>, resetPage = true) {
    const next: Record<string, string> = {};
    for (const [key, value] of Object.entries({ ...query.value, ...(resetPage ? { page: '' } : {}), ...changes })) {
      if (value) next[key] = value;
    }
    return next;
  }

  function applySearch() {
    navigateTo({ query: withQuery({ search: searchInput.value.trim() || undefined }) });
  }

  const { data, error } = await useAsyncData(
    () => `media-list:${JSON.stringify(query.value)}`,
    () =>
      api<Paginated<Media>>('/media', {
        query: { per_page: PER_PAGE, ...withQuery({}, false) },
      }),
  );

  useHead({ title: 'DualRead — Thư viện media' });
</script>
