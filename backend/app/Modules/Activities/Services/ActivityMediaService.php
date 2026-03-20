<?php

declare(strict_types=1);

namespace Modules\Activities\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Activities\Models\Activity;
use Modules\Files\Models\File;
use Modules\Files\Models\FileReference;
use Modules\Files\Repositories\FilesRepositoryInterface;
use Modules\Files\Services\FileReferenceService;
use Modules\Files\Services\FilesService;
use RuntimeException;

final class ActivityMediaService
{
    public function __construct(
        private readonly FilesService $filesService,
        private readonly FilesRepositoryInterface $filesRepository,
        private readonly FileReferenceService $fileReferenceService,
    ) {}

    public function attachCover(Activity $activity, UploadedFile $uploadedFile): File
    {
        return $this->filesService->attachUploadedFile(
            $uploadedFile,
            $activity,
            Activity::FILE_COLLECTION_COVER,
            "public",
            true,
        );
    }

    public function removeCover(Activity $activity): void
    {
        $this->filesService->removeAttachedFile($activity, Activity::FILE_COLLECTION_COVER, true);
    }

    public function attachGalleryItem(Activity $activity, UploadedFile $uploadedFile): File
    {
        return DB::transaction(function () use ($activity, $uploadedFile): File {
            $path = $uploadedFile->store("uploads/" . now()->format("Y/m"), "public");

            $created = $this->filesRepository->create([
                "disk" => "public",
                "path" => $path,
                "original_name" => $uploadedFile->getClientOriginalName(),
                "mime_type" => $uploadedFile->getClientMimeType(),
                "size" => $uploadedFile->getSize() ?? 0,
                "collection" => Activity::FILE_COLLECTION_GALLERY,
                "fileable_type" => $activity->getMorphClass(),
                "fileable_id" => (string) $activity->getKey(),
            ]);

            $currentIds = $activity
                ->gallery()
                ->pluck("files.id")
                ->map(fn($id) => (string) $id)
                ->all();
            $currentIds[] = (string) $created->id;

            $this->fileReferenceService->syncOwnerFileIds(
                $this->resolveLiveOwnerType($activity, Activity::FILE_COLLECTION_GALLERY),
                (string) $activity->getKey(),
                $currentIds,
                ["collection" => Activity::FILE_COLLECTION_GALLERY],
            );

            $this->reorderGallery($activity, $currentIds);

            return $created;
        });
    }

    public function removeGalleryItem(Activity $activity, string $fileId): void
    {
        DB::transaction(function () use ($activity, $fileId): void {
            $file = $activity->gallery()->whereKey($fileId)->first();
            if (!$file instanceof File) {
                throw new RuntimeException("Gallery file not found");
            }

            $remainingIds = $activity
                ->gallery()
                ->whereKeyNot($fileId)
                ->pluck("files.id")
                ->map(fn($id) => (string) $id)
                ->all();

            $this->fileReferenceService->syncOwnerFileIds(
                $this->resolveLiveOwnerType($activity, Activity::FILE_COLLECTION_GALLERY),
                (string) $activity->getKey(),
                $remainingIds,
                ["collection" => Activity::FILE_COLLECTION_GALLERY],
            );

            $this->reorderGallery($activity, $remainingIds);

            $file
                ->forceFill([
                    "fileable_type" => null,
                    "fileable_id" => null,
                ])
                ->save();

            $this->fileReferenceService->deleteUnreferencedFiles([(string) $file->id]);
        });
    }

    /**
     * @param array<int, string> $orderedFileIds
     */
    public function reorderGallery(Activity $activity, array $orderedFileIds): void
    {
        DB::transaction(function () use ($activity, $orderedFileIds): void {
            $currentIds = $activity
                ->gallery()
                ->pluck("files.id")
                ->map(fn($id): string => (string) $id)
                ->all();

            $normalized = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            fn($id): string => is_string($id) ? trim($id) : "",
                            $orderedFileIds,
                        ),
                    ),
                ),
            );

            $orderedExisting = array_values(array_intersect($normalized, $currentIds));
            $missing = array_values(array_diff($currentIds, $orderedExisting));
            $finalOrder = array_values(array_merge($orderedExisting, $missing));

            $ownerType = $this->resolveLiveOwnerType($activity, Activity::FILE_COLLECTION_GALLERY);
            $ownerId = (string) $activity->getKey();

            foreach ($finalOrder as $index => $fileId) {
                FileReference::query()
                    ->where("owner_type", $ownerType)
                    ->where("owner_id", $ownerId)
                    ->where("file_id", $fileId)
                    ->update([
                        "meta" => [
                            "collection" => Activity::FILE_COLLECTION_GALLERY,
                            "order" => $index,
                        ],
                    ]);
            }
        });
    }

    public function purge(Activity $activity): void
    {
        DB::transaction(function () use ($activity): void {
            $galleryIds = $activity
                ->gallery()
                ->pluck("files.id")
                ->map(fn($id): string => (string) $id)
                ->all();

            $this->removeCover($activity);

            foreach ($galleryIds as $fileId) {
                $this->removeGalleryItem($activity, $fileId);
            }
        });
    }

    private function resolveLiveOwnerType(Activity $activity, string $collection): string
    {
        return sprintf("live:%s:%s", $activity->getMorphClass(), $collection);
    }
}
