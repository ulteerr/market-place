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
    public function __construct(private readonly FilesRepositoryInterface $filesRepository) {}

    public function attachUploadedFile(
        UploadedFile $uploadedFile,
        Model $fileable,
        string $collection = "default",
        string $disk = "public",
    ): File {
        return DB::transaction(function () use (
            $uploadedFile,
            $fileable,
            $collection,
            $disk,
        ): File {
            $existing = $this->findSingleCollectionFile($fileable, $collection);
            if ($existing) {
                $this->delete($existing);
            }

            $path = $uploadedFile->store("uploads/" . now()->format("Y/m"), $disk);

            return $this->filesRepository->create([
                "disk" => $disk,
                "path" => $path,
                "original_name" => $uploadedFile->getClientOriginalName(),
                "mime_type" => $uploadedFile->getClientMimeType(),
                "size" => $uploadedFile->getSize() ?? 0,
                "collection" => $collection,
                "fileable_type" => $fileable->getMorphClass(),
                "fileable_id" => (string) $fileable->getKey(),
            ]);
        });
    }

    public function removeAttachedFile(Model $fileable, string $collection = "default"): void
    {
        $file = $this->findSingleCollectionFile($fileable, $collection);
        if (!$file) {
            return;
        }

        $this->delete($file);
    }

    public function delete(File $file): void
    {
        if (Storage::disk($file->disk)->exists($file->path)) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $this->filesRepository->delete($file);
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
