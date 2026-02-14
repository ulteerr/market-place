<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Files\Models\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MeAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function fakePng(string $name = "avatar.png"): UploadedFile
    {
        $png = base64_decode(
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlH0J8AAAAASUVORK5CYII=",
            true,
        );

        return UploadedFile::fake()->createWithContent($name, $png ?: "");
    }

    #[Test]
    public function authenticated_user_can_upload_avatar(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsUser();

        $response = $this->withHeaders($auth["headers"])->post("/api/me/avatar", [
            "avatar" => $this->fakePng("avatar.png"),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath("status", "ok")
            ->assertJsonPath("user.id", (string) $auth["user"]->id)
            ->assertJsonPath("user.avatar.original_name", "avatar.png");

        $file = File::query()
            ->where("fileable_type", $auth["user"]->getMorphClass())
            ->where("fileable_id", (string) $auth["user"]->id)
            ->where("collection", "avatar")
            ->first();

        $this->assertNotNull($file);
        Storage::disk("public")->assertExists($file->path);
    }

    #[Test]
    public function uploading_new_avatar_replaces_previous_one(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->post("/api/me/avatar", [
                "avatar" => $this->fakePng("first.png"),
            ])
            ->assertOk();

        $firstFile = File::query()->where("fileable_id", (string) $auth["user"]->id)->firstOrFail();
        Storage::disk("public")->assertExists($firstFile->path);

        $this->withHeaders($auth["headers"])
            ->post("/api/me/avatar", [
                "avatar" => $this->fakePng("second.png"),
            ])
            ->assertOk()
            ->assertJsonPath("user.avatar.original_name", "second.png");

        $this->assertDatabaseCount("files", 1);
        $secondFile = File::query()
            ->where("fileable_id", (string) $auth["user"]->id)
            ->firstOrFail();

        $this->assertNotSame($firstFile->id, $secondFile->id);
        Storage::disk("public")->assertMissing($firstFile->path);
        Storage::disk("public")->assertExists($secondFile->path);
    }

    #[Test]
    public function authenticated_user_can_delete_avatar(): void
    {
        Storage::fake("public");
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->post("/api/me/avatar", [
                "avatar" => $this->fakePng("avatar.png"),
            ])
            ->assertOk();

        $file = File::query()->where("fileable_id", (string) $auth["user"]->id)->firstOrFail();
        Storage::disk("public")->assertExists($file->path);

        $this->withHeaders($auth["headers"])
            ->deleteJson("/api/me/avatar")
            ->assertOk()
            ->assertJsonPath("user.avatar", null);

        $this->assertDatabaseCount("files", 0);
        Storage::disk("public")->assertMissing($file->path);
    }

    #[Test]
    public function avatar_upload_requires_image_file(): void
    {
        $auth = $this->actingAsUser();

        $this->withHeaders($auth["headers"])
            ->post("/api/me/avatar", [
                "avatar" => UploadedFile::fake()->create("document.pdf", 64, "application/pdf"),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(["avatar"]);
    }

    #[Test]
    public function guest_cannot_upload_or_delete_avatar(): void
    {
        $this->postJson("/api/me/avatar", [])->assertUnauthorized();
        $this->deleteJson("/api/me/avatar")->assertUnauthorized();
    }
}
