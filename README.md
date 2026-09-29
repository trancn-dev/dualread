# DualRead — Project Brief

## 1. Project Overview

DualRead is a media repository / media website designed to manage different types of media content:

* Video
* Audio
* Image
* Other media types may be added later

The project has two main consumers:

1. **Human users**

   * Access the website through the Nuxt frontend.
   * Browse media.
   * View title, description, author, thumbnail, duration, etc.
   * Play or view the actual media.

2. **AI / AI crawlers**

   * Must not depend on the human-facing UI.
   * Must not click Play buttons.
   * Must not depend on HTML structure or changing UI layouts.
   * Must consume structured media data through the Laravel API.

### Core principle

The Laravel `Media` entity/database is the **single source of truth**.

```text
                    Database
                       │
                       ▼
                    Laravel
                 Media Entity
                       │
            ┌──────────┴──────────┐
            │                     │
            ▼                     ▼
        Nuxt Web UI            REST API
            │                     │
            ▼              ┌──────┴──────┐
       Human users          │             │
                         Frontend       AI
```

Nuxt and AI are consumers of the same underlying media data.

Do not create a separate AI database or duplicate media data for AI.

---

# 2. Technology Stack

### Backend

* Laravel
* PHP
* REST API
* PostgreSQL

### Frontend

* Nuxt
* Vue

### Infrastructure

* Docker
* Docker Compose
* PostgreSQL container

The exact PHP, Node.js, PostgreSQL, and base-image versions should be explicitly pinned rather than using uncontrolled `latest` tags.

---

# 3. Docker Environment & Build

DualRead must be developed and run using Docker so the development environment is reproducible.

The project should not depend on manually installed PHP, Node.js, Composer, or PostgreSQL on the host machine.

## Docker services

Use Docker Compose:

```text
Docker Compose
├── app
│   └── Laravel / PHP
├── frontend
│   └── Nuxt / Node.js
└── db
    └── PostgreSQL
```

## Requirements

Create:

```text
docker-compose.yml
Dockerfile(s)
.env.example
```

Docker should provide:

* Laravel/PHP environment
* Composer
* Nuxt/Node.js environment
* PostgreSQL database
* Persistent PostgreSQL volume
* Shared Docker network

Laravel must connect to PostgreSQL using the Docker service name, not `localhost`.

Example:

```env
DB_HOST=db
```

Do not hard-code database credentials or service URLs in source code.

Environment-specific configuration should be handled through `.env`.

## Expected workflow

Build the containers:

```bash
docker compose build
```

Start the environment:

```bash
docker compose up -d
```

Check services:

```bash
docker compose ps
```

Run Laravel migrations:

```bash
docker compose exec app php artisan migrate
```

Stop the environment:

```bash
docker compose down
```

Stopping the containers must not delete PostgreSQL data.

The database should use a named Docker volume.

A clean machine with Docker installed should be able to clone the repository and start the project without manually installing the application runtime dependencies.

---

# 4. Backend Architecture

Laravel is responsible for:

* Media entities
* Database access
* Business logic
* REST API
* Authentication/authorization where required
* Media metadata
* Transcript data

The API must be independent from the Nuxt UI.

The API should describe **media/entity data**, not UI state.

Do not expose UI-specific fields such as:

```text
show_play_button
button_position
theme
layout
```

Instead expose semantic data such as:

```text
title
description
type
duration_seconds
author
files
transcripts
published_at
```

---

# 5. API Versioning

Version the API from the beginning.

Base:

```text
/api/v1
```

Initial endpoints:

```http
GET /api/v1/media
GET /api/v1/media/{slug}
```

The same API can initially be consumed by:

* Nuxt frontend
* AI systems
* AI crawlers
* Other future clients

If UI and AI later require different response contracts, they may be separated, for example:

```text
/api/v1/...
/api/ai/v1/...
```

However, both must still read from the same `Media` entity/source of truth.

Do not duplicate media data for AI.

---

# 6. Media List API

The media list endpoint must support pagination.

Example:

```http
GET /api/v1/media?page=1&per_page=50
```

Response should contain:

```json
{
    "data": [],
    "meta": {
        "current_page": 1,
        "per_page": 50,
        "total": 100,
        "last_page": 2
    }
}
```

Do not return an unlimited number of media records.

The API should provide a foundation for searching and filtering.

Examples:

```http
GET /api/v1/media?type=video
GET /api/v1/media?type=audio
GET /api/v1/media?author_id=45
GET /api/v1/media?search=xuan
GET /api/v1/media?sort=published_at
```

Search/filter implementation can initially be simple but should be designed so it can be expanded later.

---

# 7. Media Detail API

Example:

```http
GET /api/v1/media/{slug}
```

The response should contain the structured information required by consumers.

Example conceptual response:

```json
{
    "id": 1,
    "slug": "example-video",
    "type": "video",
    "title": "Example Video",
    "description": "Example description",
    "author": {
        "id": 45,
        "name": "Example Author"
    },
    "duration_seconds": 272,
    "published_at": "2026-09-29T10:00:00Z",
    "files": [],
    "transcripts": []
}
```

The API should return raw structured data.

For example:

```text
duration_seconds = 272
```

instead of:

```text
duration = "04:32"
```

The frontend can format `272` into `04:32`.

---

# 8. Database Design

Keep the MVP database simple.

Core structure:

```text
users
  │
  └── media
        │
        ├── media_files
        └── media_transcripts
```

Do not create separate tables such as:

```text
videos
audios
images
```

The `media` table is intentionally generic.

---

# 9. `media` Table

Migration:

```php
Schema::create('media', function (Blueprint $table) {
    $table->id();

    $table->string('slug')->unique();

    $table->string('type', 20);

    $table->string('title');

    $table->text('description')->nullable();

    $table->foreignId('author_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->unsignedInteger('duration_seconds')->nullable();

    $table->timestamp('published_at')->nullable();

    $table->string('status', 20)
        ->default('draft');

    $table->timestamps();

    $table->index('type');
    $table->index('author_id');
    $table->index('status');
    $table->index('published_at');
});
```

## Fields

### `id`

Stable internal identity.

### `slug`

Public identifier used by URLs/API.

Example:

```text
/media/my-example-video
```

The numeric `id` should remain stable even if the title changes.

### `type`

Initial supported values:

```text
video
audio
image
```

The design should allow additional media types later.

### `title`

Media title.

### `description`

Optional media description.

### `author_id`

References `users.id`.

The MVP assumes one author per media.

### `duration_seconds`

Raw duration in seconds.

Nullable because images do not have a duration.

### `published_at`

Publication timestamp.

### `status`

At minimum:

```text
draft
published
```

The exact implementation can follow Laravel project conventions.

---

# 10. `media_files` Table

Actual files should not be stored directly as URL columns on `media`.

Do not use:

```text
media.media_url
media.thumbnail_url
```

Instead use a separate `media_files` table.

Migration:

```php
Schema::create('media_files', function (Blueprint $table) {
    $table->id();

    $table->foreignId('media_id')
        ->constrained('media')
        ->cascadeOnDelete();

    $table->string('type', 20);

    $table->string('disk', 50)
        ->default('public');

    $table->string('path');

    $table->string('mime_type', 100)->nullable();

    $table->unsignedBigInteger('size_bytes')->nullable();

    $table->unsignedInteger('width')->nullable();

    $table->unsignedInteger('height')->nullable();

    $table->timestamps();

    $table->index(['media_id', 'type']);
});
```

Initial `type` values:

```text
original
thumbnail
preview
```

This allows future types such as:

```text
mobile
low_quality
4k
audio_only
```

without redesigning the table.

## Examples

Video:

```text
media.type = video
media_files.mime_type = video/mp4
media_files.type = original
```

Audio:

```text
media.type = audio
media_files.mime_type = audio/mpeg
media_files.type = original
```

Image:

```text
media.type = image
media_files.mime_type = image/jpeg
media_files.width = 1920
media_files.height = 1080
```

---

# 11. `media_transcripts` Table

AI may need to understand the actual spoken/sung content of video or audio.

Metadata alone is not enough for this.

Prepare transcript support in the database.

Migration:

```php
Schema::create('media_transcripts', function (Blueprint $table) {
    $table->id();

    $table->foreignId('media_id')
        ->constrained('media')
        ->cascadeOnDelete();

    $table->string('language', 10);

    $table->text('content');

    $table->string('source', 30)
        ->default('manual');

    $table->timestamps();

    $table->unique(['media_id', 'language']);
});
```

Initial `source` values may include:

```text
manual
auto
imported
```

The MVP does not need to implement a speech-to-text pipeline.

The database only needs to be ready for transcript data.

---

# 12. Laravel Models

`Media`:

```php
class Media extends Model
{
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function files()
    {
        return $this->hasMany(MediaFile::class);
    }

    public function transcripts()
    {
        return $this->hasMany(MediaTranscript::class);
    }
}
```

`MediaFile`:

```text
MediaFile belongsTo Media
```

`MediaTranscript`:

```text
MediaTranscript belongsTo Media
```

Enums such as:

```text
MediaType
MediaStatus
```

may be introduced if useful, but they are not mandatory for the initial implementation.

---

# 13. Media Type Design

The system must be media-generic.

The same `media` table handles:

```text
video
audio
image
```

Examples:

### Video

```text
type = video
duration_seconds = 272
mime_type = video/mp4
```

### Audio

```text
type = audio
duration_seconds = 215
mime_type = audio/mpeg
```

### Image

```text
type = image
duration_seconds = null
mime_type = image/jpeg
width = 1920
height = 1080
```

This architecture can later support:

```text
document
pdf
gif
```

or other media types without creating an entirely new media architecture.

---

# 14. Author Design

For the MVP, one media has one author:

```text
media.author_id -> users.id
```

Do not create:

```text
authors
media_authors
```

yet.

If the product later needs:

* multiple creators
* contributors
* co-authors
* performers

then introduce a many-to-many relationship such as:

```text
media_creators
```

at that time.

---

# 15. Nuxt Frontend

Nuxt is the human-facing UI.

Responsibilities:

* Media listing
* Media detail page
* Display title
* Display description
* Display author
* Display thumbnail
* Display duration
* Play video/audio
* Display images
* Display transcripts where appropriate

Nuxt should retrieve data from the Laravel API.

Nuxt is **not** the source of truth.

Example architecture:

```text
Nuxt
  │
  ▼
Laravel API
  │
  ▼
Media Entity
  │
  ▼
PostgreSQL
```

SSR can be used for the website where appropriate.

However, AI should not depend on the SSR HTML.

---

# 16. AI Access

AI / AI crawlers must use the API.

They should not need to:

```text
open the website
click Play
find a button
parse changing HTML
understand frontend components
```

Instead:

```text
AI
 │
 ▼
GET /api/v1/media
 │
 ▼
GET /api/v1/media/{slug}
 │
 ▼
Structured media data
```

The API should provide:

* Media identity
* Type
* Title
* Description
* Author
* Duration
* Publication information
* File metadata
* Transcript data where available

---

# 17. AI and Actual Media Content

There is an important distinction:

### Metadata

AI can understand:

```text
title
description
author
duration
type
```

through the API.

### Actual spoken content

For video/audio, metadata alone does not contain everything that was said or sung.

Therefore, transcripts should be available through the API when needed.

Example:

```text
video/audio
    │
    ├── metadata
    │
    └── transcript
```

Do not require AI to press Play simply to obtain text that could be represented structurally.

---

# 18. Markdown and AI Data

Do not create one Markdown file per media item.

For example, do not create:

```text
media/
  video-001.md
  video-002.md
  video-003.md
```

The database remains the source of truth.

If an AI/RAG workflow later requires Markdown or another document format, generate it dynamically from the `Media` entity.

Example conceptual flow:

```text
PostgreSQL
    ↓
Media Entity
    ↓
API / AI representation
    ↓
Optional generated Markdown/document
```

This avoids duplicated data becoming inconsistent.

---

# 19. MVP — Do Not Over-engineer

Do not create the following tables/features in the initial MVP unless a concrete requirement appears:

```text
media_categories
media_tags
media_views
media_likes
media_comments
media_versions
media_embeddings
media_ai_summaries
media_search_index
media_recommendations
media_collections
ai_media
ai_media_contents
```

Also do not create:

```text
videos
audios
images
```

as separate tables.

Start with the core:

```text
users
media
media_files
media_transcripts
```

Expand only when actual requirements appear.

---

# 20. Possible Future Extensions

The architecture should leave room for future functionality such as:

* Multiple creators
* Tags
* Categories
* Collections
* Likes
* Views
* Comments
* Media versions/history
* Semantic search
* Embeddings
* AI-generated summaries
* AI-generated metadata
* Multiple transcript formats
* Multiple transcript tracks
* OCR for images
* Additional media types
* Media processing pipelines
* CDN/storage integration

These are future extensions, not MVP requirements.

---

# 21. API Design Principles

The API should follow these principles:

### Stable identity

Use both:

```text
id
slug
```

### Semantic data

Return:

```text
duration_seconds
```

not UI-formatted strings.

### Pagination

Never return an unlimited media collection.

### Versioning

Use:

```text
/api/v1
```

from the beginning.

### UI independence

The API must not describe frontend layout or UI state.

### Single source of truth

Both Nuxt and AI read from the same Laravel/domain/database data.

### Extensibility

The API should be designed so new media types and fields can be added without breaking existing consumers.

Breaking API changes should use a new API version.

---

# 22. Authentication and Rate Limiting

The initial GET media API may be public if the product requirements allow it.

Write/admin endpoints should require authentication/authorization.

Rate limiting should be added where appropriate, especially for public API access.

The exact authentication strategy can follow the Laravel application's requirements.

Do not add complicated authentication infrastructure if the MVP does not require it.

---

# 23. Initial Implementation Priority

Build the project in this order:

### Step 1 — Docker

Create:

```text
docker-compose.yml
Dockerfile(s)
.env.example
```

Ensure Laravel, Nuxt, and PostgreSQL can start successfully.

### Step 2 — Laravel

Create the Laravel application and configure:

```text
PHP
Composer
PostgreSQL
environment variables
```

### Step 3 — Database

Create migrations for:

```text
media
media_files
media_transcripts
```

Run:

```bash
php artisan migrate
```

inside the Docker container.

### Step 4 — Models

Create:

```text
Media
MediaFile
MediaTranscript
```

with the required relationships.

### Step 5 — API

Implement:

```http
GET /api/v1/media
GET /api/v1/media/{slug}
```

with:

* pagination
* media type filtering
* search foundation
* author information
* files
* transcripts

### Step 6 — Nuxt

Build the human-facing media UI using the API.

### Step 7 — AI API readiness

Ensure the API provides sufficient structured information for AI clients without depending on the Nuxt UI.

---

# 24. Final Architecture

The target MVP architecture is:

```text
                         ┌──────────────────┐
                         │   PostgreSQL     │
                         │                  │
                         │ users            │
                         │ media            │
                         │ media_files      │
                         │ media_transcripts│
                         └────────┬─────────┘
                                  │
                                  ▼
                         ┌──────────────────┐
                         │     Laravel      │
                         │                  │
                         │ Media Entity     │
                         │ REST API         │
                         └────────┬─────────┘
                                  │
                    ┌─────────────┴─────────────┐
                    │                           │
                    ▼                           ▼
             ┌──────────────┐            ┌──────────────┐
             │    Nuxt      │            │  AI / Crawler│
             │              │            │              │
             │ Human UI     │            │ Structured   │
             │ Media Player │            │ API access   │
             └──────────────┘            └──────────────┘

                    All running through Docker
```

## Core principle

**Media Entity is the single source of truth.**

```text
PostgreSQL
    ↓
Laravel Media
    ├── Nuxt → Human users
    └── REST API → AI / other clients
```

The UI and AI are separate consumers, but they do not maintain separate copies of the media data.

The MVP should remain intentionally simple, with the architecture prepared for future expansion without prematurely building unnecessary systems.

---

# 25. Getting Started

Requirements: Docker with Docker Compose. Nothing else on the host.

```bash
cp .env.example .env            # then set DB_PASSWORD (and UID/GID if not 1000)
docker compose build
docker compose up -d            # first start installs Composer/npm dependencies
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed   # optional demo media
```

| Service  | URL                                  |
| -------- | ------------------------------------ |
| Nuxt UI  | http://localhost:3000                |
| API      | http://localhost:8000/api/v1/media   |
| Postgres | 127.0.0.1:5432 (host tools only)     |

Run the backend tests:

```bash
docker compose exec app php artisan test
```

Demo seed data writes generated SVG images and WAV tones to `backend/storage/app/public`.
Demo video records have metadata only (no MP4 file is generated).

## Layout

```text
docker-compose.yml       app (Laravel), frontend (Nuxt), db (PostgreSQL)
backend/                 Laravel: migrations, models, enums, /api/v1
  app/Http/Controllers/Api/V1/MediaController.php
  app/Http/Resources/V1/  API contract (MediaResource, MediaCollection, ...)
frontend/                Nuxt 4: app/pages (list + /media/[slug]), consumes the API
```

## API quick reference

```http
GET /api/v1/media?page=1&per_page=20      # per_page max 100
    &type=video|audio|image
    &author_id=45
    &search=xuan                          # title/description, case- and accent-insensitive ("xuan" matches "xuân")
    &sort=-published_at                   # published_at|title|duration_seconds|id, "-" = desc
GET /api/v1/media/{slug}
```

Only `published` media with `published_at <= now` is exposed. The list endpoint returns
transcript languages without content; the detail endpoint includes transcript content.
Public API requests are rate limited (`API_RATE_LIMIT` per minute per client).
