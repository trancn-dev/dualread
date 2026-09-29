import type { Media, MediaFile } from '~/types/media';

/** 272 → "04:32", 3723 → "1:02:03" */
export function formatDuration(seconds: number | null | undefined): string {
  if (seconds == null) return '';

  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  const pad = (n: number) => String(n).padStart(2, '0');

  return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
}

export function formatDate(iso: string | null | undefined): string {
  if (!iso) return '';

  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeZone: 'UTC' }).format(new Date(iso));
}

export function formatBytes(bytes: number | null | undefined): string {
  if (bytes == null) return '';

  const units = ['B', 'KB', 'MB', 'GB'];
  let value = bytes;
  let unit = 0;
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024;
    unit++;
  }

  return `${value.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
}

export function findFile(media: Media, type: string): MediaFile | undefined {
  return media.files.find((file) => file.type === type);
}

const TYPE_LABELS: Record<string, string> = {
  video: 'Video',
  audio: 'Audio',
  image: 'Hình ảnh',
};

export function typeLabel(type: string): string {
  return TYPE_LABELS[type] ?? type;
}

const LANGUAGE_LABELS: Record<string, string> = {
  vi: 'Tiếng Việt',
  en: 'English',
};

export function languageLabel(language: string): string {
  return LANGUAGE_LABELS[language] ?? language;
}
