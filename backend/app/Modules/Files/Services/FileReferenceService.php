<?php

declare(strict_types=1);

namespace Modules\Files\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\File;
use Modules\Files\Models\FileReference;

final class FileReferenceService
{
    /**
     * @param array<int, string> $fileIds
     * @param array<string, mixed> $meta
     * @return array<int, string>
     */
    public function syncOwnerFileIds(
        string $ownerType,
        string $ownerId,
        array $fileIds,
        array $meta = [],
    ): array {
        $normalized = array_values(
            array_unique(
                array_filter(array_map(fn($id) => is_string($id) ? trim($id) : "", $fileIds)),
            ),
        );

        return DB::transaction(function () use ($ownerType, $ownerId, $normalized, $meta): array {
            $existing = FileReference::query()
                ->where("owner_type", $ownerType)
                ->where("owner_id", $ownerId)
                ->pluck("file_id")
                ->all();

            $toDetach = array_values(array_diff($existing, $normalized));
            $toAttach = array_values(array_diff($normalized, $existing));
            if ($toAttach !== []) {
                $existingToAttach = File::query()->whereIn("id", $toAttach)->pluck("id")->all();
                $toAttach = array_values(array_intersect($toAttach, $existingToAttach));
            }

            if ($toDetach !== []) {
                FileReference::query()
                    ->where("owner_type", $ownerType)
                    ->where("owner_id", $ownerId)
                    ->whereIn("file_id", $toDetach)
                    ->delete();
            }

            foreach ($toAttach as $fileId) {
                FileReference::query()->create([
                    "file_id" => $fileId,
                    "owner_type" => $ownerType,
                    "owner_id" => $ownerId,
                    "meta" => $meta === [] ? null : $meta,
                ]);
            }

            return $toDetach;
        });
    }

    /**
     * @return array<int, string>
     */
    public function detachOwner(string $ownerType, string $ownerId): array
    {
        return DB::transaction(function () use ($ownerType, $ownerId): array {
            $fileIds = FileReference::query()
                ->where("owner_type", $ownerType)
                ->where("owner_id", $ownerId)
                ->pluck("file_id")
                ->all();

            FileReference::query()
                ->where("owner_type", $ownerType)
                ->where("owner_id", $ownerId)
                ->delete();

            return $fileIds;
        });
    }

    /**
     * @param array<int, string> $fileIds
     */
    public function deleteUnreferencedFiles(array $fileIds): void
    {
        $normalized = array_values(
            array_unique(
                array_filter(array_map(fn($id) => is_string($id) ? trim($id) : "", $fileIds)),
            ),
        );

        foreach ($normalized as $fileId) {
            $this->deleteFileIfUnreferenced($fileId);
        }
    }

    public function deleteFileIfUnreferenced(string $fileId): bool
    {
        return DB::transaction(function () use ($fileId): bool {
            /** @var File|null $file */
            $file = File::query()->whereKey($fileId)->lockForUpdate()->first();
            if (!$file) {
                return false;
            }

            $hasReferences = FileReference::query()->where("file_id", $fileId)->exists();
            if ($hasReferences || $file->fileable_id !== null || $file->fileable_type !== null) {
                return false;
            }

            if (Storage::disk($file->disk)->exists($file->path)) {
                Storage::disk($file->disk)->delete($file->path);
            }

            $file->delete();

            return true;
        });
    }
}
