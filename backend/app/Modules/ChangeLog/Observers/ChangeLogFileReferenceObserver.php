<?php

declare(strict_types=1);

namespace Modules\ChangeLog\Observers;

use Modules\ChangeLog\Models\ChangeLog;
use Modules\Files\Services\FileReferenceService;

final class ChangeLogFileReferenceObserver
{
    public function __construct(private readonly FileReferenceService $fileReferenceService) {}

    public function created(ChangeLog $entry): void
    {
        $this->syncReferences($entry);
    }

    public function updated(ChangeLog $entry): void
    {
        $this->syncReferences($entry);
    }

    public function deleting(ChangeLog $entry): void
    {
        $detachedBefore = $this->fileReferenceService->detachOwner(
            "changelog:before",
            (string) $entry->id,
        );
        $detachedAfter = $this->fileReferenceService->detachOwner(
            "changelog:after",
            (string) $entry->id,
        );

        $this->fileReferenceService->deleteUnreferencedFiles([
            ...$detachedBefore,
            ...$detachedAfter,
        ]);
    }

    private function syncReferences(ChangeLog $entry): void
    {
        $ownerId = (string) $entry->id;
        $beforeFileIds = $this->extractFileIds($entry->media_before, $entry->before);
        $afterFileIds = $this->extractFileIds($entry->media_after, $entry->after);

        $detachedBefore = $this->fileReferenceService->syncOwnerFileIds(
            "changelog:before",
            $ownerId,
            $beforeFileIds,
            [
                "event" => $entry->event,
                "side" => "before",
            ],
        );
        $detachedAfter = $this->fileReferenceService->syncOwnerFileIds(
            "changelog:after",
            $ownerId,
            $afterFileIds,
            [
                "event" => $entry->event,
                "side" => "after",
            ],
        );

        $this->fileReferenceService->deleteUnreferencedFiles([
            ...$detachedBefore,
            ...$detachedAfter,
        ]);
    }

    private function extractFileIds(?array $mediaPayload, ?array $statePayload): array
    {
        $ids = [];
        $ids = [...$ids, ...$this->extractFileIdsRecursively($mediaPayload)];

        $legacyAvatarId = $statePayload["avatar_id"] ?? null;
        if (is_string($legacyAvatarId) && trim($legacyAvatarId) !== "") {
            $ids[] = trim($legacyAvatarId);
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed>|array<int, mixed>|null $payload
     * @return array<int, string>
     */
    private function extractFileIdsRecursively(array|null $payload): array
    {
        if ($payload === null) {
            return [];
        }

        $ids = [];
        foreach ($payload as $key => $value) {
            if ($key === "file_id" && is_string($value) && trim($value) !== "") {
                $ids[] = trim($value);
                continue;
            }

            if (is_array($value)) {
                $ids = [...$ids, ...$this->extractFileIdsRecursively($value)];
            }
        }

        return array_values(array_unique($ids));
    }
}
