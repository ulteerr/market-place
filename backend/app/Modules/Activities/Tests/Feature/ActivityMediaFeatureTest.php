<?php

declare(strict_types=1);

namespace Modules\Activities\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Activities\Models\Activity;
use Modules\Activities\Services\ActivityMediaService;
use Modules\Categories\Models\Category;
use Modules\Files\Models\File;
use Modules\Files\Models\FileReference;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationLocation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ActivityMediaFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function media_service_attaches_cover_and_gallery_and_public_show_returns_gallery_in_saved_order(): void
    {
        Storage::fake("public");

        [$activity] = $this->createActivityContext();
        /** @var ActivityMediaService $mediaService */
        $mediaService = app(ActivityMediaService::class);

        $cover = UploadedFile::fake()->create("cover.jpg", 120, "image/jpeg");
        $galleryA = UploadedFile::fake()->create("gallery-a.jpg", 120, "image/jpeg");
        $galleryB = UploadedFile::fake()->create("gallery-b.jpg", 120, "image/jpeg");

        $mediaService->attachCover($activity, $cover);
        $first = $mediaService->attachGalleryItem($activity, $galleryA);
        $second = $mediaService->attachGalleryItem($activity, $galleryB);
        $mediaService->reorderGallery($activity, [(string) $second->id, (string) $first->id]);

        $activity = $activity->fresh(["cover", "gallery"]);
        $coverFile = $activity->cover;

        $this->assertNotNull($coverFile);
        Storage::disk("public")->assertExists($coverFile->path);
        Storage::disk("public")->assertExists($first->path);
        Storage::disk("public")->assertExists($second->path);
        $this->assertDatabaseCount("file_references", 3);
        $this->assertDatabaseHas("file_references", [
            "file_id" => (string) $second->id,
            "owner_type" => sprintf(
                "live:%s:%s",
                $activity->getMorphClass(),
                Activity::FILE_COLLECTION_GALLERY,
            ),
            "owner_id" => (string) $activity->id,
        ]);

        $this->getJson("/api/activities/" . $activity->slug . "-" . $activity->id)
            ->assertOk()
            ->assertJsonPath("data.cover.id", (string) $coverFile->id)
            ->assertJsonPath("data.gallery.0.id", (string) $second->id)
            ->assertJsonPath("data.gallery.1.id", (string) $first->id);
    }

    #[Test]
    public function media_service_removes_gallery_items_and_purges_all_files_for_activity(): void
    {
        Storage::fake("public");

        [$activity] = $this->createActivityContext();
        /** @var ActivityMediaService $mediaService */
        $mediaService = app(ActivityMediaService::class);

        $mediaService->attachCover(
            $activity,
            UploadedFile::fake()->create("cover.jpg", 120, "image/jpeg"),
        );
        $galleryA = $mediaService->attachGalleryItem(
            $activity,
            UploadedFile::fake()->create("gallery-a.jpg", 120, "image/jpeg"),
        );
        $galleryB = $mediaService->attachGalleryItem(
            $activity,
            UploadedFile::fake()->create("gallery-b.jpg", 120, "image/jpeg"),
        );

        $mediaService->removeGalleryItem($activity, (string) $galleryA->id);

        $this->assertDatabaseMissing("files", ["id" => (string) $galleryA->id]);
        $this->assertDatabaseMissing("file_references", ["file_id" => (string) $galleryA->id]);
        Storage::disk("public")->assertMissing($galleryA->path);

        $remainingFileIds = File::query()->pluck("id")->map(fn($id) => (string) $id)->all();
        $this->assertContains((string) $galleryB->id, $remainingFileIds);

        $mediaService->purge($activity->fresh());

        $this->assertDatabaseCount("files", 0);
        $this->assertDatabaseCount("file_references", 0);
        Storage::disk("public")->assertMissing($galleryB->path);
    }

    private function createActivityContext(): array
    {
        $organization = Organization::factory()->create([
            "name" => "Организация спорта",
            "status" => "active",
        ]);
        $location = OrganizationLocation::factory()->create([
            "organization_id" => (string) $organization->id,
        ]);
        $root = Category::factory()->create([
            "name" => "Спорт",
            "slug" => "sport",
        ]);
        $leaf = Category::factory()
            ->childOf($root)
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
            ]);
        $activity = Activity::factory()
            ->forOrganizationLocation($organization, $location)
            ->published()
            ->create([
                "name" => "Футбол",
                "slug" => "futbol",
                "short_description" => "Секция футбола.",
            ]);

        DB::table("activity_categories")->insert([
            "activity_id" => (string) $activity->id,
            "category_id" => (string) $leaf->id,
        ]);

        return [$activity, $organization, $location, $root, $leaf];
    }
}
