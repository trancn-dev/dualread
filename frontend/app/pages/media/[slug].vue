<template>
  <article v-if="media" class="detail">
    <NuxtLink to="/" class="muted small">← Tất cả media</NuxtLink>

    <div class="player">
      <template v-if="original">
        <video
          v-if="media.type === 'video'"
          :src="original.url"
          :poster="thumbnail?.url"
          controls
          preload="metadata"
        />
        <div v-else-if="media.type === 'audio'" class="audio-player">
          <img v-if="thumbnail" :src="thumbnail.url" :alt="media.title" />
          <audio :src="original.url" controls preload="metadata" />
        </div>
        <img
          v-else-if="media.type === 'image'"
          :src="original.url"
          :alt="media.title"
          :width="original.width ?? undefined"
          :height="original.height ?? undefined"
        />
        <a v-else :href="original.url" class="button">Mở tệp</a>
      </template>
      <img v-else-if="thumbnail" :src="thumbnail.url" :alt="media.title" />
    </div>

    <header>
      <p class="muted small">
        <span class="badge static">{{ typeLabel(media.type) }}</span>
        <span v-if="media.duration_seconds != null"> · {{ formatDuration(media.duration_seconds) }}</span>
        <span v-if="media.published_at">
          · <time :datetime="media.published_at">{{ formatDate(media.published_at) }}</time>
        </span>
      </p>
      <h1>{{ media.title }}</h1>
      <p v-if="media.author" class="muted">{{ media.author.name }}</p>
    </header>

    <p v-if="media.description" class="description">{{ media.description }}</p>

    <section v-if="media.transcripts.length" class="transcripts">
      <h2>Lời thoại / Transcript</h2>
      <div class="type-tabs" role="tablist">
        <button
          v-for="transcript in media.transcripts"
          :key="transcript.language"
          type="button"
          role="tab"
          :aria-selected="transcript.language === activeLanguage"
          :class="['tab', { active: transcript.language === activeLanguage }]"
          @click="activeLanguage = transcript.language"
        >
          {{ languageLabel(transcript.language) }}
        </button>
      </div>
      <p class="transcript-content">{{ activeTranscript?.content }}</p>
    </section>

    <section class="files">
      <h2>Tệp</h2>
      <table>
        <thead>
          <tr>
            <th>Loại</th>
            <th>MIME</th>
            <th>Kích thước</th>
            <th>Độ phân giải</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="file in media.files" :key="file.id">
            <td>
              <a :href="file.url">{{ file.type }}</a>
            </td>
            <td>{{ file.mime_type }}</td>
            <td>{{ formatBytes(file.size_bytes) }}</td>
            <td>{{ file.width && file.height ? `${file.width}×${file.height}` : '' }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <p class="muted small">
      JSON cho AI / client khác: <a :href="media.links.self" rel="nofollow">{{ media.links.self }}</a>
    </p>
  </article>
</template>

<script setup lang="ts">
  import type { Media } from '~/types/media';

  const route = useRoute();
  const api = useApi();
  const slug = computed(() => String(route.params.slug));

  const { data, error } = await useAsyncData(
    () => `media:${slug.value}`,
    () => api<{ data: Media }>(`/media/${encodeURIComponent(slug.value)}`),
  );

  if (error.value) {
    const statusCode = error.value.statusCode ?? 500;
    throw createError({ statusCode, statusMessage: statusCode === 404 ? 'Not Found' : 'API error', fatal: true });
  }

  const media = computed(() => data.value?.data);
  const original = computed(() => (media.value ? findFile(media.value, 'original') : undefined));
  const thumbnail = computed(() => (media.value ? findFile(media.value, 'thumbnail') : undefined));

  const activeLanguage = ref(media.value?.transcripts[0]?.language);
  const activeTranscript = computed(() =>
    media.value?.transcripts.find((transcript) => transcript.language === activeLanguage.value),
  );

  useSeoMeta({
    title: () => (media.value ? `${media.value.title} — DualRead` : 'DualRead'),
    description: () => media.value?.description ?? '',
    ogTitle: () => media.value?.title,
    ogImage: () => thumbnail.value?.url,
  });
</script>
