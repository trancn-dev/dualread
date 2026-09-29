<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * "media" is already plural; don't let Laravel guess.
     *
     * @var string
     */
    protected $table = 'media';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'type',
        'title',
        'description',
        'author_id',
        'duration_seconds',
        'published_at',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'status' => MediaStatus::class,
            'duration_seconds' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Resolve route bindings by the public slug.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return HasMany<MediaFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    /**
     * @return HasMany<MediaTranscript, $this>
     */
    public function transcripts(): HasMany
    {
        return $this->hasMany(MediaTranscript::class);
    }

    /**
     * Media that is publicly visible: published and not scheduled in the future.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', MediaStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Case-insensitive match on title/description; accent-insensitive on PostgreSQL.
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $pattern = '%'.addcslashes($term, '%_\\').'%';

        if ($query->getConnection()->getDriverName() === 'pgsql') {
            $query->where(fn (Builder $query) => $query
                ->whereRaw('unaccent(title) ILIKE unaccent(?)', [$pattern])
                ->orWhereRaw('unaccent(description) ILIKE unaccent(?)', [$pattern]));

            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereLike('title', $pattern)
            ->orWhereLike('description', $pattern));
    }
}
