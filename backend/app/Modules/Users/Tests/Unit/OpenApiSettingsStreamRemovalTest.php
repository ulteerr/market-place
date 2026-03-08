<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OpenApiSettingsStreamRemovalTest extends TestCase
{
    #[Test]
    public function openapi_spec_does_not_expose_removed_settings_stream_path(): void
    {
        $candidatePaths = [
            dirname(base_path()) . "/docker/swagger/openapi.yaml",
            base_path("docker/swagger/openapi.yaml"),
            base_path("../docker/swagger/openapi.yaml"),
            "/var/www/docker/swagger/openapi.yaml",
            "/var/docker/swagger/openapi.yaml",
            "/docker/swagger/openapi.yaml",
        ];

        $schema = null;
        foreach ($candidatePaths as $path) {
            if (is_file($path)) {
                $schema = file_get_contents($path);
                break;
            }
        }

        if (!is_string($schema)) {
            $this->markTestSkipped(
                "OpenAPI file is unavailable in test environment. Expected to find one of: " .
                    implode(", ", $candidatePaths),
            );
        }

        $this->assertStringNotContainsString("/api/me/settings/stream:", (string) $schema);
    }
}
