<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserSwaggerContractTest extends TestCase
{
    #[Test]
    public function user_swagger_schema_has_presence_fields(): void
    {
        $candidatePaths = [
            dirname(base_path()) . "/docker/swagger/schemas/user.yaml",
            base_path("docker/swagger/schemas/user.yaml"),
            base_path("../docker/swagger/schemas/user.yaml"),
            "/var/www/docker/swagger/schemas/user.yaml",
            "/var/docker/swagger/schemas/user.yaml",
            "/docker/swagger/schemas/user.yaml",
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
                "Swagger schema file is unavailable in test environment. Expected to find one of: " .
                    implode(", ", $candidatePaths),
            );
        }

        $this->assertStringContainsString("last_seen_at:", (string) $schema);
        $this->assertStringContainsString("is_online:", (string) $schema);
    }
}
