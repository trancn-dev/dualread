<template>
  <NuxtLink :to="`/media/${media.slug}`" class="card">
    <div class="card-thumb">
      <img v-if="thumbnail" :src="thumbnail.url" :alt="media.title" loading="lazy" />
      <span class="badge">{{ typeLabel(media.type) }}</span>
      <span v-if="media.duration_seconds != null" class="duration">
        {{ formatDuration(media.duration_seconds) }}
      </span>
    </div>
    <div class="card-body">
      <h2 class="card-title">{{ media.title }}</h2>
      <p class="muted small">
        <span v-if="media.author">{{ media.author.name }}</span>
        <span v-if="media.author && media.published_at"> · </span>
        <time v-if="media.published_at" :datetime="media.published_at">{{ formatDate(media.published_at) }}</time>
      </p>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
  import type { Media } from '~/types/media';

  const props = defineProps<{ media: Media }>();

  const thumbnail = computed(() => findFile(props.media, 'thumbnail'));
</script>
