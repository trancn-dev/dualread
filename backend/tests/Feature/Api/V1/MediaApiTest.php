<?php

namespace Tests\Feature\Api\V1;

use App\Enums\MediaFileType;
use App\Models\Media;
use App\Models\MediaFile;
use App\Models\MediaTranscript;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_returns_paginated_published_media(): void
    {
        Media::factory()->count(3)->create();
        Media::factory()->draft()->create();
        Media::factory()->scheduled()->create();

        $this->getJson('/api/v1/media?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta', [
                'current_page' => 1,
                'per_page' => 2,
                'total' => 3,
                'last_page' => 2,
            ])
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'type', 'title', 'description', 'author' => ['id', 'name'], 'duration_seconds', 'published_at', 'files', 'transcripts']],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }

    public function test_per_page_is_capped(): void
    {
        $this->getJson('/api/v1/media?per_page=1000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_list_filters_by_type_and_author(): void
    {
        $author = User::factory()->create();
        Media::factory()->video()->for($author, 'author')->create(['slug' => 'match']);
        Media::factory()->audio()->for($author, 'author')->create();
        Media::factory()->video()->create();

        $this->getJson("/api/v1/media?type=video&author_id={$author->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'match');

        $this->getJson('/api/v1/media?type=podcast')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_list_searches_title_and_description(): void
    {
        Media::factory()->create(['slug' => 'by-title', 'title' => 'Mùa xuan trên phố']);
        Media::factory()->create(['slug' => 'by-description', 'title' => 'Other', 'description' => 'Tet and XUAN festival']);
        Media::factory()->create(['title' => 'Unrelated', 'description' => 'Nothing here']);

        $slugs = collect($this->getJson('/api/v1/media?search=xuan')->assertOk()->json('data'))->pluck('slug');

        $this->assertEqualsCanonicalizing(['by-title', 'by-description'], $slugs->all());
    }

    public function test_list_sorts(): void
    {
        Media::factory()->create(['slug' => 'short', 'duration_seconds' => 10]);
        Media::factory()->create(['slug' => 'long', 'duration_seconds' => 999]);
        Media::factory()->image()->create(['slug' => 'no-duration']);

        $this->getJson('/api/v1/media?sort=-duration_seconds')
            ->assertJsonPath('data.0.slug', 'long')
            ->assertJsonPath('data.2.slug', 'no-duration');

        $this->getJson('/api/v1/media?sort=duration_seconds')
            ->assertJsonPath('data.0.slug', 'short');

        $this->getJson('/api/v1/media?sort=status')
            ->assertUnprocessable();
    }

    public function test_list_omits_transcript_content(): void
    {
        $media = Media::factory()->create();
        MediaTranscript::factory()->for($media)->create(['language' => 'vi']);

        $this->getJson('/api/v1/media')
            ->assertJsonPath('data.0.transcripts.0.language', 'vi')
            ->assertJsonMissingPath('data.0.transcripts.0.content');
    }

    public function test_show_returns_structured_media_by_slug(): void
    {
        $author = User::factory()->create(['name' => 'Example Author']);
        $media = Media::factory()->video()->for($author, 'author')->create([
            'slug' => 'example-video',
            'title' => 'Example Video',
            'duration_seconds' => 272,
            'published_at' => '2026-09-01 10:00:00',
        ]);
        MediaFile::factory()->for($media)->create(['path' => 'media/example.mp4']);
        MediaFile::factory()->thumbnail()->for($media)->create();
        MediaTranscript::factory()->for($media)->create(['language' => 'en', 'content' => 'Hello world']);

        $this->getJson('/api/v1/media/example-video')
            ->assertOk()
            ->assertJsonPath('data.id', $media->id)
            ->assertJsonPath('data.type', 'video')
            ->assertJsonPath('data.title', 'Example Video')
            ->assertJsonPath('data.duration_seconds', 272)
            ->assertJsonPath('data.published_at', '2026-09-01T10:00:00Z')
            ->assertJsonPath('data.author', ['id' => $author->id, 'name' => 'Example Author'])
            ->assertJsonPath('data.files.0.type', MediaFileType::Original->value)
            ->assertJsonPath('data.files.0.mime_type', 'video/mp4')
            ->assertJsonPath('data.files.0.url', url('storage/media/example.mp4'))
            ->assertJsonPath('data.transcripts.0.content', 'Hello world')
            ->assertJsonMissingPath('data.author.email');
    }

    public function test_show_hides_unpublished_media(): void
    {
        Media::factory()->draft()->create(['slug' => 'secret']);

        $this->get('/api/v1/media/secret')
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->get('/api/v1/media/does-not-exist')->assertNotFound();
    }

    public function test_image_media_has_null_duration(): void
    {
        Media::factory()->image()->create(['slug' => 'photo']);

        $this->getJson('/api/v1/media/photo')
            ->assertJsonPath('data.type', 'image')
            ->assertJsonPath('data.duration_seconds', null);
    }
}
