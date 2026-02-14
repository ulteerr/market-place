<?php

declare(strict_types=1);

namespace Modules\Files;

use App\Support\ModuleServiceProvider;
use Modules\Files\Repositories\FilesRepository;
use Modules\Files\Repositories\FilesRepositoryInterface;

final class FilesServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = "Files";

    public function register(): void
    {
        $this->app->bind(FilesRepositoryInterface::class, FilesRepository::class);
    }
}
