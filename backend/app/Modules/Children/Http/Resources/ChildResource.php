<?php

declare(strict_types=1);

namespace Modules\Children\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ChildResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            "id" => (string) $this->id,
            "user_id" => (string) $this->user_id,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "middle_name" => $this->middle_name,
            "gender" => $this->gender,
            "birth_date" => $this->birth_date,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "user" => $this->whenLoaded("user", function () {
                if (!$this->user) {
                    return null;
                }

                $fullName = collect([
                    $this->user->last_name,
                    $this->user->first_name,
                    $this->user->middle_name,
                ])
                    ->filter(fn(mixed $part): bool => is_string($part) && trim($part) !== "")
                    ->implode(" ");

                return [
                    "id" => (string) $this->user->id,
                    "full_name" => $fullName !== "" ? $fullName : null,
                    "email" => $this->user->email,
                    "first_name" => $this->user->first_name,
                    "last_name" => $this->user->last_name,
                    "middle_name" => $this->user->middle_name,
                ];
            }),
        ];
    }
}
