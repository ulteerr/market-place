<?php

declare(strict_types=1);

namespace Modules\Files\Repositories;

use Modules\Files\Models\File;

interface FilesRepositoryInterface
{
    public function create(array $data): File;

    public function delete(File $file): void;
}
