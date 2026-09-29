<?php

namespace Database\Seeders;

use App\Enums\MediaFileType;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\TranscriptSource;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Local demo content. Writes small generated files (SVG images, WAV tones)
 * to the "public" disk so the frontend has something real to display/play.
 * Demo video originals are metadata only: no MP4 is generated.
 *
 * Safe to re-run: media is matched by slug and its files/transcripts rebuilt.
 */
class DemoMediaSeeder extends Seeder
{
    private const WAV_SAMPLE_RATE = 8000;

    public function run(): void
    {
        $authors = collect([
            'lan' => ['name' => 'Nguyễn Thị Lan', 'email' => 'lan@example.com'],
            'minh' => ['name' => 'Trần Văn Minh', 'email' => 'minh@example.com'],
            'alex' => ['name' => 'Alex Carter', 'email' => 'alex@example.com'],
        ])->map(fn (array $attributes) => User::firstOrCreate(
            ['email' => $attributes['email']],
            ['name' => $attributes['name'], 'password' => 'password'],
        ));

        foreach ($this->items() as $index => $item) {
            $media = Media::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'author_id' => $authors[$item['author']]->id,
                    'duration_seconds' => $item['duration_seconds'] ?? null,
                    'published_at' => $item['status'] === MediaStatus::Published
                        ? now()->subDays(($index + 1) * 3)->startOfHour()
                        : null,
                    'status' => $item['status'],
                ],
            );

            $media->files()->delete();
            $media->transcripts()->delete();

            $this->createFiles($media, $item['hue']);

            foreach ($item['transcripts'] ?? [] as $language => $content) {
                $media->transcripts()->create([
                    'language' => $language,
                    'content' => $content,
                    'source' => TranscriptSource::Manual,
                ]);
            }
        }
    }

    private function createFiles(Media $media, int $hue): void
    {
        $directory = "media/{$media->slug}";

        $this->storeFile($media, MediaFileType::Thumbnail, "{$directory}/thumbnail.svg", 'image/svg+xml',
            $this->svg($media->title, $hue, 640, 360), 640, 360);

        match ($media->type) {
            MediaType::Image => $this->storeFile($media, MediaFileType::Original, "{$directory}/original.svg", 'image/svg+xml',
                $this->svg($media->title, $hue, 1920, 1080), 1920, 1080),
            MediaType::Audio => $this->storeFile($media, MediaFileType::Original, "{$directory}/original.wav", 'audio/wav',
                $this->wav($media->duration_seconds, 220 + $hue)),
            MediaType::Video => $media->files()->create([
                'type' => MediaFileType::Original,
                'path' => "{$directory}/original.mp4",
                'mime_type' => 'video/mp4',
                'width' => 1920,
                'height' => 1080,
            ]),
        };
    }

    private function storeFile(
        Media $media,
        MediaFileType $type,
        string $path,
        string $mimeType,
        string $contents,
        ?int $width = null,
        ?int $height = null,
    ): void {
        Storage::disk('public')->put($path, $contents);

        $media->files()->create([
            'type' => $type,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($contents),
            'width' => $width,
            'height' => $height,
        ]);
    }

    private function svg(string $title, int $hue, int $width, int $height): string
    {
        $label = htmlspecialchars($title, ENT_XML1);
        $fontSize = (int) round($width / 22);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
          <defs>
            <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="hsl({$hue},55%,42%)"/>
              <stop offset="1" stop-color="hsl({$hue},60%,18%)"/>
            </linearGradient>
          </defs>
          <rect width="100%" height="100%" fill="url(#g)"/>
          <text x="50%" y="50%" fill="#fff" font-family="sans-serif" font-size="{$fontSize}" text-anchor="middle" dominant-baseline="middle">{$label}</text>
        </svg>
        SVG;
    }

    /**
     * A gently pulsing sine tone as 8-bit mono PCM WAV.
     */
    private function wav(int $seconds, int $frequency): string
    {
        $samples = '';
        $count = $seconds * self::WAV_SAMPLE_RATE;

        for ($i = 0; $i < $count; $i++) {
            $t = $i / self::WAV_SAMPLE_RATE;
            $envelope = 0.5 + 0.5 * sin(2 * M_PI * 0.5 * $t);
            $samples .= chr((int) (128 + 60 * $envelope * sin(2 * M_PI * $frequency * $t)));
        }

        $header = 'RIFF'.pack('V', 36 + $count).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, self::WAV_SAMPLE_RATE, self::WAV_SAMPLE_RATE, 1, 8)
            .'data'.pack('V', $count);

        return $header.$samples;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        return [
            [
                'slug' => 'mua-xuan-tren-pho-co',
                'type' => MediaType::Video,
                'title' => 'Mùa xuân trên phố cổ',
                'description' => 'Một buổi sáng đầu xuân dạo quanh phố cổ Hà Nội: chợ hoa, hàng quà và nhịp sống chậm rãi.',
                'author' => 'lan',
                'duration_seconds' => 272,
                'status' => MediaStatus::Published,
                'hue' => 350,
                'transcripts' => [
                    'vi' => "Xin chào các bạn. Hôm nay mình sẽ đưa các bạn dạo quanh phố cổ vào những ngày đầu xuân.\nChợ hoa Hàng Lược lúc này đông nhất, đào và quất được chở về từ Nhật Tân.",
                    'en' => "Hello everyone. Today I'll take you around the Old Quarter in the first days of spring.\nThe Hang Luoc flower market is at its busiest, with peach blossoms and kumquat trees brought in from Nhat Tan.",
                ],
            ],
            [
                'slug' => 'bai-hat-xuan-ve',
                'type' => MediaType::Audio,
                'title' => 'Xuân về (demo tone)',
                'description' => 'Bản thu âm mẫu dùng để kiểm thử trình phát audio.',
                'author' => 'minh',
                'duration_seconds' => 20,
                'status' => MediaStatus::Published,
                'hue' => 30,
                'transcripts' => [
                    'vi' => "Xuân về trên khắp nẻo đường,\nnắng vàng ươm trên mái nhà quen.",
                ],
            ],
            [
                'slug' => 'ha-long-bay-at-dawn',
                'type' => MediaType::Image,
                'title' => 'Ha Long Bay at dawn',
                'description' => 'Limestone karsts emerging from the morning mist.',
                'author' => 'alex',
                'status' => MediaStatus::Published,
                'hue' => 200,
            ],
            [
                'slug' => 'intro-to-laravel-apis',
                'type' => MediaType::Video,
                'title' => 'Intro to versioned Laravel APIs',
                'description' => 'Why DualRead exposes /api/v1 and how clients should consume it.',
                'author' => 'alex',
                'duration_seconds' => 845,
                'status' => MediaStatus::Published,
                'hue' => 260,
                'transcripts' => [
                    'en' => "In this talk we look at why an API should describe entities rather than UI state.\nWe return duration_seconds as a raw integer and let each client format it.",
                ],
            ],
            [
                'slug' => 'podcast-doc-sach-moi-ngay',
                'type' => MediaType::Audio,
                'title' => 'Podcast: Đọc sách mỗi ngày',
                'description' => 'Tập 1 — thói quen đọc 20 trang mỗi ngày.',
                'author' => 'lan',
                'duration_seconds' => 35,
                'status' => MediaStatus::Published,
                'hue' => 120,
                'transcripts' => [
                    'vi' => 'Chào mừng bạn đến với tập đầu tiên. Hôm nay chúng ta nói về việc đọc hai mươi trang sách mỗi ngày.',
                    'en' => 'Welcome to the first episode. Today we talk about reading twenty pages a day.',
                ],
            ],
            [
                'slug' => 'hoi-an-lanterns',
                'type' => MediaType::Image,
                'title' => 'Đèn lồng Hội An',
                'description' => 'Phố Hội về đêm với hàng trăm chiếc đèn lồng.',
                'author' => 'minh',
                'status' => MediaStatus::Published,
                'hue' => 45,
            ],
            [
                'slug' => 'street-food-saigon',
                'type' => MediaType::Video,
                'title' => 'Street food in Saigon',
                'description' => 'Five dishes to try in District 1 after sunset.',
                'author' => 'alex',
                'duration_seconds' => 1260,
                'status' => MediaStatus::Published,
                'hue' => 15,
            ],
            [
                'slug' => 'ban-nhap-chua-cong-bo',
                'type' => MediaType::Video,
                'title' => 'Bản nháp chưa công bố',
                'description' => 'Draft media: must not appear in the public API.',
                'author' => 'lan',
                'duration_seconds' => 60,
                'status' => MediaStatus::Draft,
                'hue' => 0,
            ],
        ];
    }
}
