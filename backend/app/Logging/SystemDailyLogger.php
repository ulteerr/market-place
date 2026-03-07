<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Support\Carbon;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class SystemDailyLogger
{
    /**
     * @param array<string, mixed> $config
     */
    public function __invoke(array $config): Logger
    {
        $directory = (string) ($config["path"] ?? storage_path("logs/system"));
        $days = max(1, (int) ($config["days"] ?? 14));
        $channelName = (string) ($config["name"] ?? "system_daily");
        $level = (string) ($config["level"] ?? "debug");

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $this->cleanupOldFiles($directory, $days);

        $logger = new Logger($channelName);
        $logger->pushHandler(
            new StreamHandler(
                sprintf("%s/%s.log", rtrim($directory, "/"), Carbon::now()->format("d-m-Y")),
                $level,
                true,
                0666,
            ),
        );

        return $logger;
    }

    private function cleanupOldFiles(string $directory, int $days): void
    {
        $files = glob(rtrim($directory, "/") . "/*.log");
        if (!is_array($files) || $files === []) {
            return;
        }

        $threshold = Carbon::today()->subDays($days - 1);
        foreach ($files as $filePath) {
            $fileName = basename($filePath, ".log");
            if (!preg_match("/^\d{2}-\d{2}-\d{4}$/", $fileName)) {
                continue;
            }

            $date = Carbon::createFromFormat("d-m-Y", $fileName);
            if (!$date instanceof Carbon || $date->lessThan($threshold)) {
                @unlink($filePath);
            }
        }
    }
}
