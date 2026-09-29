<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListMediaRequest;
use App\Http\Resources\V1\MediaCollection;
use App\Http\Resources\V1\MediaResource;
use App\Models\Media;

class MediaController extends Controller
{
    /**
     * GET /api/v1/media
     */
    public function index(ListMediaRequest $request): MediaCollection
    {
        [$sortColumn, $sortDirection] = $request->sort();

        $media = Media::query()
            ->published()
            ->with([
                'author:id,name',
                'files',
                // Transcript bodies can be large; the detail endpoint returns them.
                'transcripts:id,media_id,language,source,updated_at',
            ])
            ->when($request->validated('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->validated('author_id'), fn ($query, $authorId) => $query->where('author_id', $authorId))
            ->when($request->validated('search'), fn ($query, string $search) => $query->search($search))
            // $sortColumn is whitelisted by ListMediaRequest; media without a value (e.g. image duration) goes last.
            ->orderByRaw("{$sortColumn} {$sortDirection} nulls last")
            ->orderBy('id', $sortDirection)
            ->paginate($request->perPage())
            ->withQueryString();

        return new MediaCollection($media);
    }

    /**
     * GET /api/v1/media/{slug}
     */
    public function show(string $slug): MediaResource
    {
        $media = Media::query()
            ->published()
            ->where('slug', $slug)
            ->with(['author:id,name', 'files', 'transcripts'])
            ->firstOrFail();

        return new MediaResource($media);
    }
}
