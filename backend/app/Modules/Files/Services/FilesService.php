<?php

declare(strict_types=1);

namespace Modules\Files\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\File;
use Modules\Files\Repositories\FilesRepositoryInterface;

final class FilesService
{
    public function __construct(
        private readonly FilesRepositoryInterface $filesRepository,
        private readonly FileReferenceService $fileReferenceService,
    ) {}

    public function attachUploadedFile(
        UploadedFile $uploadedFile,
        Model $fileable,
        string $collection = "default",
        string $disk = "public",
        bool $cleanupDetached = true,
    ): File {
        return DB::transaction(function () use (
            $uploadedFile,
            $fileable,
            $collection,
            $disk,
            $cleanupDetached,
        ): File {
            $existing = $this->findSingleCollectionFile($fileable, $collection);
            $ownerType = $this->resolveLiveOwnerType($fileable, $collection);
            $ownerId = (string) $fileable->getKey();

            $path = $uploadedFile->store("uploads/" . now()->format("Y/m"), $disk);

            $created = $this->filesRepository->create([
                "disk" => $disk,
                "path" => $path,
                "original_name" => $uploadedFile->getClientOriginalName(),
                "mime_type" => $uploadedFile->getClientMimeType(),
                "size" => $uploadedFile->getSize() ?? 0,
                "collection" => $collection,
                "fileable_type" => $fileable->getMorphClass(),
                "fileable_id" => (string) $fileable->getKey(),
            ]);

            if ($existing && $existing->id !== $created->id) {
                $existing
                    ->forceFill([
                        "fileable_type" => null,
                        "fileable_id" => null,
                    ])
                    ->save();
            }

            $detachedIds = $this->fileReferenceService->syncOwnerFileIds(
                $ownerType,
                $ownerId,
                [$created->id],
                ["collection" => $collection],
            );

            if ($existing && !in_array($existing->id, $detachedIds, true)) {
                $detachedIds[] = $existing->id;
            }

            if ($cleanupDetached) {
                $this->fileReferenceService->deleteUnreferencedFiles($detachedIds);
            }

            return $created;
        });
    }

    public function removeAttachedFile(
        Model $fileable,
        string $collection = "default",
        bool $cleanupDetached = true,
    ): void {
        $file = $this->findSingleCollectionFile($fileable, $collection);
        $ownerType = $this->resolveLiveOwnerType($fileable, $collection);
        $ownerId = (string) $fileable->getKey();

        $detachedIds = $this->fileReferenceService->syncOwnerFileIds($ownerType, $ownerId, []);

        if ($file) {
            $file
                ->forceFill([
                    "fileable_type" => null,
                    "fileable_id" => null,
                ])
                ->save();

            if (!in_array($file->id, $detachedIds, true)) {
                $detachedIds[] = $file->id;
            }
        }

        if ($cleanupDetached) {
            $this->fileReferenceService->deleteUnreferencedFiles($detachedIds);
        }
    }

    public function delete(File $file): void
    {
        if ($file->fileable_type !== null && $file->fileable_id !== null) {
            $this->fileReferenceService->detachOwner(
                "live:{$file->fileable_type}:{$file->collection}",
                (string) $file->fileable_id,
            );
        }

        if (Storage::disk($file->disk)->exists($file->path)) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $this->filesRepository->delete($file);
    }

    public function attachExistingFile(
        File $file,
        Model $fileable,
        string $collection = "default",
        bool $cleanupDetached = true,
    ): void {
        DB::transaction(function () use ($file, $fileable, $collection, $cleanupDetached): void {
            $ownerType = $this->resolveLiveOwnerType($fileable, $collection);
            $ownerId = (string) $fileable->getKey();
            $existing = $this->findSingleCollectionFile($fileable, $collection);

            $file
                ->forceFill([
                    "collection" => $collection,
                    "fileable_type" => $fileable->getMorphClass(),
                    "fileable_id" => $ownerId,
                ])
                ->save();

            if ($existing && $existing->id !== $file->id) {
                $existing
                    ->forceFill([
                        "fileable_type" => null,
                        "fileable_id" => null,
                    ])
                    ->save();
            }

            $detachedIds = $this->fileReferenceService->syncOwnerFileIds(
                $ownerType,
                $ownerId,
                [$file->id],
                ["collection" => $collection],
            );
            if ($existing && !in_array($existing->id, $detachedIds, true)) {
                $detachedIds[] = $existing->id;
            }

            if ($cleanupDetached) {
                $this->fileReferenceService->deleteUnreferencedFiles($detachedIds);
            }
        });
    }

    public function resolveFileFromSnapshot(array $snapshot): ?File
    {
        $fileId = trim((string) ($snapshot["file_id"] ?? ""));
        if ($fileId !== "") {
            $byId = File::query()->find($fileId);
            if ($byId) {
                return $byId;
            }
        }

        $disk = trim((string) ($snapshot["disk"] ?? ""));
        $path = trim((string) ($snapshot["path"] ?? ""));
        if ($disk === "" || $path === "") {
            return null;
        }

        $existing = File::query()->where("disk", $disk)->where("path", $path)->first();
        if ($existing) {
            return $existing;
        }

        if (!Storage::disk($disk)->exists($path)) {
            return null;
        }

        return $this->filesRepository->create([
            "disk" => $disk,
            "path" => $path,
            "original_name" => (string) ($snapshot["original_name"] ?? basename($path)),
            "mime_type" => (string) ($snapshot["mime_type"] ?? "application/octet-stream"),
            "size" => (int) ($snapshot["size"] ?? 0),
            "collection" => (string) ($snapshot["collection"] ?? "default"),
            "fileable_type" => null,
            "fileable_id" => null,
        ]);
    }

    private function resolveLiveOwnerType(Model $fileable, string $collection): string
    {
        return sprintf("live:%s:%s", $fileable->getMorphClass(), $collection);
    }

    private function findSingleCollectionFile(Model $fileable, string $collection): ?File
    {
        return File::query()
            ->where("fileable_type", $fileable->getMorphClass())
            ->where("fileable_id", (string) $fileable->getKey())
            ->where("collection", $collection)
            ->latest("created_at")
            ->first();
    }
}
