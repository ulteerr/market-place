<?php

declare(strict_types=1);

namespace Modules\Files\Repositories;

use Modules\Files\Models\File;

final class FilesRepository implements FilesRepositoryInterface
{
    public function create(array $data): File
    {
        return File::query()->create($data);
    }

    public function delete(File $file): void
    {
        $file->delete();
    }
}
