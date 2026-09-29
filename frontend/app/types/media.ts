// Mirrors the Laravel /api/v1 contract (App\Http\Resources\V1\*).

export type MediaType = 'video' | 'audio' | 'image';

export type MediaFileType = 'original' | 'thumbnail' | 'preview';

export interface Author {
  id: number;
  name: string;
}

export interface MediaFile {
  id: number;
  type: MediaFileType | string;
  url: string;
  mime_type: string | null;
  size_bytes: number | null;
  width: number | null;
  height: number | null;
}

export interface MediaTranscript {
  language: string;
  source: string;
  /** Only present on the detail endpoint. */
  content?: string;
  updated_at: string | null;
}

export interface Media {
  id: number;
  slug: string;
  type: MediaType | string;
  title: string;
  description: string | null;
  author: Author | null;
  duration_seconds: number | null;
  published_at: string | null;
  updated_at: string | null;
  files: MediaFile[];
  transcripts: MediaTranscript[];
  links: { self: string };
}

export interface Paginated<T> {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
}
