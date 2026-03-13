<?php

declare(strict_types=1);

namespace Modules\Users\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

final class AdminCacheResetService
{
    /**
     * @param array<int, string> $scopes
     * @return array<int, string>
     */
    public function reset(array $scopes): array
    {
        $normalized = array_values(
            array_unique(
                array_filter(
                    $scopes,
                    static fn($scope): bool => is_string($scope) && $scope !== "",
                ),
            ),
        );

        foreach ($normalized as $scope) {
            if ($scope === "backend") {
                $this->resetBackendCaches();
                continue;
            }

            if ($scope === "frontend-ssr") {
                $this->resetFrontendSsrArtifacts();
            }
        }

        return $normalized;
    }

    private function resetBackendCaches(): void
    {
        foreach (
            ["optimize:clear", "cache:clear", "config:clear", "route:clear", "view:clear"]
            as $command
        ) {
            Artisan::call($command);
        }
    }

    private function resetFrontendSsrArtifacts(): void
    {
        $frontendRoot = $this->resolveFrontendRoot();
        if ($frontendRoot === null) {
            return;
        }

        /** @var array<int, string> $paths */
        $paths = Arr::wrap(config("admin-cache-reset.frontend_paths", []));
        foreach ($paths as $relativePath) {
            $absolutePath =
                $frontendRoot . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
            if (!File::exists($absolutePath)) {
                continue;
            }

            if (File::isDirectory($absolutePath)) {
                File::deleteDirectory($absolutePath);
                continue;
            }

            File::delete($absolutePath);
        }
    }

    private function resolveFrontendRoot(): ?string
    {
        $configured = config("admin-cache-reset.frontend_root");
        if (is_string($configured) && $configured !== "") {
            return rtrim($configured, DIRECTORY_SEPARATOR);
        }

        $candidates = [
            base_path("frontend"),
            base_path("../frontend"),
            dirname(base_path()) . DIRECTORY_SEPARATOR . "frontend",
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && File::isDirectory($candidate)) {
                return rtrim($candidate, DIRECTORY_SEPARATOR);
            }
        }

        return null;
    }
}
