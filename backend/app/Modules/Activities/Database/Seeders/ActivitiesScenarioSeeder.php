<?php

declare(strict_types=1);

namespace Modules\Activities\Database\Seeders;

use App\Shared\Support\Transliteration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Modules\Activities\Models\Activity;
use Modules\Activities\Database\Factories\ActivityScheduleFactory;
use Modules\Categories\Models\Category;
use Modules\Files\Models\File;
use Modules\Files\Models\FileReference;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;

final class ActivitiesScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $categoryLeaves = [
            "football" => $this->ensureCategoryLeaf("Спорт", "Футбол", 10, 10),
            "music" => $this->ensureCategoryLeaf("Музыка", "Вокал", 20, 10),
            "drawing" => $this->ensureCategoryLeaf("Рисование", "Живопись", 30, 10),
            "ballet" => $this->ensureCategoryLeaf("Балет", "Классический балет", 40, 10),
        ];

        $football = $this->createOrganizationWithLocation("Футбольный клуб");
        $music = $this->createOrganizationWithLocation("Музыкальная школа");

        $footballActivity = Activity::factory()
            ->published()
            ->featured()
            ->forOrganizationLocation($football["organization"], $football["location"])
            ->create([
                "name" => "Футбольная секция",
                "slug" => Transliteration::slug("Футбольная секция"),
                "short_description" => "Детская футбольная школа",
                "description" =>
                    "Детская футбольная школа с упором на технику, координацию и игровые тренировки.",
                "min_age" => 6,
                "max_age" => 12,
                "capacity" => 20,
                "price_from" => 1500,
                "price_to" => 3000,
            ]);

        $musicActivity = Activity::factory()
            ->published()
            ->featured()
            ->forOrganizationLocation($music["organization"], $music["location"])
            ->create([
                "name" => "Музыкальная студия",
                "slug" => Transliteration::slug("Музыкальная студия"),
                "short_description" => "Занятия музыкой для детей",
                "description" =>
                    "Занятия музыкой для детей с вокальными практиками, сценической подачей и ансамблевыми упражнениями.",
                "min_age" => 5,
                "max_age" => 10,
                "capacity" => 15,
                "price_from" => 2000,
                "price_to" => 4000,
            ]);

        $footballActivity
            ->primaryCategory()
            ->sync([(string) $categoryLeaves["football"]->getKey()]);
        $musicActivity->primaryCategory()->sync([(string) $categoryLeaves["music"]->getKey()]);

        $this->createSchedules($footballActivity, 0);
        $this->createSchedules($musicActivity, 1);
        $this->seedMedia($footballActivity, "Футбол");
        $this->seedMedia($musicActivity, "Музыка");

        $this->seedActivityBatch(
            "music",
            $categoryLeaves["music"],
            [
                "Вокальная студия Crescendo Kids",
                "Музыкальная лаборатория Junior Band",
                "Студия вокала Голос Детства",
                "Музыкальный класс Piano Start",
                "Школа современного вокала Crescendo Teens",
                "Академия сценического вокала Мелодия",
                "Творческая мастерская Музыкальный Экспресс",
                "Семейная школа музыки Дом Ритма",
                "Вокальный клуб Звездный Микрофон",
                "Музыкальная студия Гармония Start",
                "Студия сольфеджио и вокала Нота",
                "Детская школа вокала Эхо",
            ],
            ["Музыкальная школа До-Ре-Ми", "Студия музыки Гармония", "Вокальная академия Ария"],
            [
                "Эстрадный вокал, слух и ритм для детей разных возрастов.",
                "Групповые и индивидуальные занятия с подготовкой к концертам.",
                "Практика сценического движения, дыхания и работы с голосом.",
            ],
            [
                [6, 8, 10, 2600, 4200],
                [7, 10, 12, 2900, 4500],
                [9, 12, 14, 3200, 5200],
                [11, 15, 16, 3500, 5900],
            ],
            10,
        );

        $this->seedActivityBatch(
            "drawing",
            $categoryLeaves["drawing"],
            [
                "Арт-студия Палитра Junior",
                "Школа рисунка Этюд Start",
                "Мастерская живописи Цвет и Свет",
                "Студия академического рисунка Линия",
                "Арт-класс Свободная Кисть",
                "Творческая мастерская Sketch Lab",
                "Студия живописи Холст и Краски",
                "Детская школа рисования Акварель",
                "Мастерская иллюстрации Лисий Карандаш",
                "Арт-лаборатория Графика Kids",
                "Школа живописи Палитра Teens",
                "Студия наброска Арт-Практика",
            ],
            ["Арт-студия Палитра", "Школа рисунка Этюд", "Творческое пространство Холст"],
            [
                "Рисование, композиция и работа с разными художественными материалами.",
                "Практика академического рисунка, живописи и творческих проектов.",
                "Курсы для начинающих и продолжающих с выставочными мини-проектами.",
            ],
            [
                [5, 7, 10, 2200, 3600],
                [7, 10, 12, 2500, 3900],
                [10, 13, 14, 2800, 4300],
                [12, 16, 16, 3100, 4800],
            ],
            20,
        );

        $this->seedActivityBatch(
            "ballet",
            $categoryLeaves["ballet"],
            [
                "Балетная студия Арабеск Start",
                "Академия балета Грация Kids",
                "Студия классического балета Пируэт",
                "Балетный класс Grand Jeté",
                "Школа балета Белый Лебедь",
                "Студия балета Arabesque Junior",
                "Класс хореографии Balance Ballet",
                "Академия сценического движения Prima",
                "Балетная мастерская Пластика",
                "Школа балета Pointe Start",
                "Студия хореографии Allegro Ballet",
                "Классический балет для детей Реверанс",
            ],
            [
                "Академия балета Грация",
                "Балетная студия Арабеск",
                "Школа классического танца Пируэт",
            ],
            [
                "Классический балет, растяжка и сценическая подготовка для детей.",
                "Занятия у станка, координация, осанка и музыкальность.",
                "Балетный класс с мягким входом для новичков и регулярной практикой.",
            ],
            [
                [4, 6, 12, 3000, 4700],
                [6, 9, 12, 3300, 5200],
                [8, 11, 14, 3600, 5800],
                [10, 14, 16, 3900, 6400],
            ],
            30,
        );
    }

    private function ensureCategoryLeaf(
        string $rootName,
        string $leafName,
        int $rootSortOrder,
        int $leafSortOrder,
    ): Category {
        $root = Category::query()->firstOrCreate(
            ["slug" => Transliteration::slug($rootName)],
            [
                "name" => $rootName,
                "parent_id" => null,
                "sort_order" => $rootSortOrder,
                "is_active" => true,
            ],
        );

        /** @var Category $root */

        $leaf = Category::query()->firstOrCreate(
            ["slug" => Transliteration::slug($leafName)],
            [
                "name" => $leafName,
                "parent_id" => (string) $root->getKey(),
                "sort_order" => $leafSortOrder,
                "is_active" => true,
            ],
        );

        /** @var Category $leaf */

        if ((string) $leaf->parent_id !== (string) $root->getKey() || $leaf->name !== $leafName) {
            $leaf
                ->forceFill([
                    "name" => $leafName,
                    "parent_id" => (string) $root->getKey(),
                    "sort_order" => $leafSortOrder,
                    "is_active" => true,
                ])
                ->save();
        }

        return $leaf;
    }

    /**
     * @return array{organization: Organization, location: OrganizationLocation}
     */
    private function createOrganizationWithLocation(string $organizationName): array
    {
        $organization = Organization::factory()->create([
            "name" => $organizationName,
            "status" => "active",
        ]);

        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->getKey(),
        ]);

        return [
            "organization" => $organization,
            "location" => $location,
        ];
    }

    /**
     * @param list<string> $activityNames
     * @param list<string> $organizationNames
     * @param list<string> $descriptions
     * @param list<array{0:int,1:int,2:int,3:int,4:int}> $ageAndPricePresets
     */
    private function seedActivityBatch(
        string $type,
        Category $leafCategory,
        array $activityNames,
        array $organizationNames,
        array $descriptions,
        array $ageAndPricePresets,
        int $scheduleOffset,
    ): void {
        $organizationPool = array_map(
            fn(string $name): array => $this->createOrganizationWithLocation($name),
            $organizationNames,
        );

        foreach ($activityNames as $index => $name) {
            $organizationPair = $organizationPool[$index % count($organizationPool)];
            $preset = $ageAndPricePresets[$index % count($ageAndPricePresets)];
            $status = $this->resolveStatus($index);
            $isPublished = $status === "published";
            $description = $descriptions[$index % count($descriptions)];

            $activity = Activity::factory()
                ->forOrganizationLocation(
                    $organizationPair["organization"],
                    $organizationPair["location"],
                )
                ->state([
                    "name" => $name,
                    "slug" => Transliteration::slug($name),
                    "short_description" => $description,
                    "description" =>
                        $description .
                        " Группа {$type} формируется с учетом возраста и уровня подготовки.",
                    "min_age" => $preset[0],
                    "max_age" => $preset[1],
                    "capacity" => $preset[2],
                    "price_from" => $preset[3],
                    "price_to" => $preset[4],
                    "status" => $status,
                    "is_featured" => $isPublished && $index % 4 === 0,
                    "published_at" => $isPublished ? now()->subDays($index % 14) : null,
                ])
                ->create();

            $activity->primaryCategory()->sync([(string) $leafCategory->getKey()]);
            $this->createSchedules($activity, $scheduleOffset + $index);
            $this->seedMedia($activity, $leafCategory->name);
        }
    }

    private function resolveStatus(int $index): string
    {
        return match ($index % 6) {
            0, 1, 2, 3 => "published",
            4 => "draft",
            default => "pending_review",
        };
    }

    private function createSchedules(Activity $activity, int $offset): void
    {
        $slots = [
            ["day_of_week" => 1, "start_time" => "16:00:00", "end_time" => "17:30:00"],
            ["day_of_week" => 2, "start_time" => "17:00:00", "end_time" => "18:30:00"],
            ["day_of_week" => 3, "start_time" => "18:00:00", "end_time" => "19:30:00"],
            ["day_of_week" => 4, "start_time" => "15:30:00", "end_time" => "17:00:00"],
            ["day_of_week" => 5, "start_time" => "17:30:00", "end_time" => "19:00:00"],
            ["day_of_week" => 6, "start_time" => "11:00:00", "end_time" => "12:30:00"],
        ];

        $firstSlot = $slots[$offset % count($slots)];
        $secondSlot = $slots[($offset + 2) % count($slots)];

        ActivityScheduleFactory::new()->forActivity($activity)->create($firstSlot);

        ActivityScheduleFactory::new()->forActivity($activity)->create($secondSlot);
    }

    private function seedMedia(Activity $activity, string $themeLabel): void
    {
        if ($activity->cover()->exists() || $activity->gallery()->exists()) {
            return;
        }

        $cover = $this->createPlaceholderFile(
            $activity,
            Activity::FILE_COLLECTION_COVER,
            $themeLabel,
            "cover",
            null,
        );

        FileReference::query()->create([
            "file_id" => (string) $cover->getKey(),
            "owner_type" => sprintf(
                "live:%s:%s",
                $activity->getMorphClass(),
                Activity::FILE_COLLECTION_COVER,
            ),
            "owner_id" => (string) $activity->getKey(),
            "meta" => [
                "collection" => Activity::FILE_COLLECTION_COVER,
            ],
        ]);

        foreach (range(1, 5) as $index) {
            $gallery = $this->createPlaceholderFile(
                $activity,
                Activity::FILE_COLLECTION_GALLERY,
                $themeLabel,
                "gallery-{$index}",
                $index - 1,
            );

            FileReference::query()->create([
                "file_id" => (string) $gallery->getKey(),
                "owner_type" => sprintf(
                    "live:%s:%s",
                    $activity->getMorphClass(),
                    Activity::FILE_COLLECTION_GALLERY,
                ),
                "owner_id" => (string) $activity->getKey(),
                "meta" => [
                    "collection" => Activity::FILE_COLLECTION_GALLERY,
                    "order" => $index - 1,
                ],
            ]);
        }
    }

    private function createPlaceholderFile(
        Activity $activity,
        string $collection,
        string $themeLabel,
        string $suffix,
        ?int $order,
    ): File {
        $slug = Transliteration::slug($activity->name);
        $path = sprintf(
            "uploads/%s/%s-%s-%s.svg",
            now()->format("Y/m"),
            $slug,
            $collection,
            $suffix,
        );

        $svg = $this->renderPlaceholderSvg($activity->name, $themeLabel, $collection, $order);

        Storage::disk("public")->put($path, $svg);

        return File::query()->create([
            "disk" => "public",
            "path" => $path,
            "original_name" => basename($path),
            "mime_type" => "image/svg+xml",
            "size" => strlen($svg),
            "collection" => $collection,
            "fileable_type" => $activity->getMorphClass(),
            "fileable_id" => (string) $activity->getKey(),
        ]);
    }

    private function renderPlaceholderSvg(
        string $activityName,
        string $themeLabel,
        string $collection,
        ?int $order,
    ): string {
        $palette = $this->resolvePalette($themeLabel, $collection, $order);
        $title = htmlspecialchars($activityName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $subtitle = htmlspecialchars(
            $themeLabel . " • " . mb_strtoupper($collection, "UTF-8"),
            ENT_QUOTES | ENT_SUBSTITUTE,
            "UTF-8",
        );
        $badge = $order === null ? "COVER" : "GALLERY " . ($order + 1);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900" viewBox="0 0 1200 900" role="img" aria-label="{$title}">
          <defs>
            <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="{$palette["start"]}" />
              <stop offset="100%" stop-color="{$palette["end"]}" />
            </linearGradient>
          </defs>
          <rect width="1200" height="900" fill="url(#bg)" rx="40" />
          <circle cx="1020" cy="180" r="120" fill="{$palette["accent"]}" fill-opacity="0.22" />
          <circle cx="220" cy="720" r="170" fill="#ffffff" fill-opacity="0.08" />
          <rect x="90" y="100" width="240" height="68" rx="34" fill="#ffffff" fill-opacity="0.16" />
          <text x="210" y="143" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" fill="#ffffff">{$badge}</text>
          <text x="90" y="320" font-family="Arial, sans-serif" font-size="86" font-weight="700" fill="#ffffff">{$title}</text>
          <text x="90" y="392" font-family="Arial, sans-serif" font-size="34" fill="#ffffff" fill-opacity="0.86">{$subtitle}</text>
          <text x="90" y="760" font-family="Arial, sans-serif" font-size="28" fill="#ffffff" fill-opacity="0.72">Marketplace demo image</text>
        </svg>
        SVG;
    }

    /**
     * @return array{start:string,end:string,accent:string}
     */
    private function resolvePalette(string $themeLabel, string $collection, ?int $order): array
    {
        $normalized = mb_strtolower($themeLabel, "UTF-8");

        if (str_contains($normalized, "фут")) {
            return ["start" => "#0f4c81", "end" => "#1fb6ff", "accent" => "#f7c948"];
        }

        if (str_contains($normalized, "муз") || str_contains($normalized, "вок")) {
            return ["start" => "#4b2067", "end" => "#c94b8e", "accent" => "#ffd166"];
        }

        if (str_contains($normalized, "жив") || str_contains($normalized, "рис")) {
            return ["start" => "#f97316", "end" => "#ef4444", "accent" => "#22c55e"];
        }

        if (str_contains($normalized, "бал")) {
            return ["start" => "#7c3aed", "end" => "#ec4899", "accent" => "#fde68a"];
        }

        $galleryOffset = $collection === Activity::FILE_COLLECTION_GALLERY ? ($order ?? 0) % 3 : 0;

        return match ($galleryOffset) {
            0 => ["start" => "#0f766e", "end" => "#14b8a6", "accent" => "#fef08a"],
            1 => ["start" => "#1d4ed8", "end" => "#38bdf8", "accent" => "#fca5a5"],
            default => ["start" => "#4338ca", "end" => "#8b5cf6", "accent" => "#fdba74"],
        };
    }
}
