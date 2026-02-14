<?php

declare(strict_types=1);

namespace Modules\Files\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class FileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "url" => $this->url,
            "original_name" => $this->original_name,
            "mime_type" => $this->mime_type,
            "size" => $this->size,
            "collection" => $this->collection,
        ];
    }
}
