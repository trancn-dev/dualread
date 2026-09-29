<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMediaRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /**
     * Sortable columns. A leading "-" on the query value means descending.
     */
    public const SORTABLE = ['published_at', 'title', 'duration_seconds', 'id'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sorts = collect(self::SORTABLE)->flatMap(fn (string $column) => [$column, "-{$column}"]);

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'type' => ['sometimes', Rule::enum(MediaType::class)],
            'author_id' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:200'],
            'sort' => ['sometimes', Rule::in($sorts->all())],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', self::DEFAULT_PER_PAGE);
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    public function sort(): array
    {
        $sort = $this->validated('sort', '-published_at');

        return str_starts_with($sort, '-')
            ? [substr($sort, 1), 'desc']
            : [$sort, 'asc'];
    }
}
