<?php

declare(strict_types=1);

namespace App\Shared\Testing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

abstract class AdminCrudTestCase extends TestCase
{
    use RefreshDatabase;

    abstract protected function endpoint(): string;

    abstract protected function table(): string;

    abstract protected function seedForList(int $count): void;

    abstract protected function seedForCreate(): void;

    abstract protected function createPayload(): array;

    abstract protected function createDatabaseHas(): array;

    abstract protected function createItem(): mixed;

    abstract protected function itemId(mixed $item): string;

    abstract protected function showIdPath(): string;

    abstract protected function updatePayload(mixed $item): array;

    abstract protected function updateDatabaseHas(mixed $item): array;

    abstract protected function actingAsAdmin(): array;

    protected function afterCreateAssertions(): void
    {
        // no-op
    }

    #[Test]
    public function guest_cannot_access_admin_routes(): void
    {
        $this->getJson($this->endpoint())->assertUnauthorized();
    }

    #[Test]
    public function non_admin_user_cannot_access_admin_routes(): void
    {
        $auth = $this->actingAsUser();

        $this
            ->withHeaders($auth['headers'])
            ->getJson($this->endpoint())
            ->assertForbidden()
            ->assertJson([
                'status' => 'error',
                'message' => 'Forbidden',
            ]);
    }

    #[Test]
    public function admin_can_list_entities(): void
    {
        $auth = $this->actingAsAdmin();

        $this->seedForList(3);

        $this
            ->withHeaders($auth['headers'])
            ->getJson($this->endpoint())
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonCount(4, 'data.data');
    }

    #[Test]
    public function admin_can_create_entity_via_admin_crud(): void
    {
        $auth = $this->actingAsAdmin();
        $this->seedForCreate();

        $this
            ->withHeaders($auth['headers'])
            ->postJson($this->endpoint(), $this->createPayload())
            ->assertStatus(201)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Created successfully');

        $this->assertDatabaseHas(
            $this->table(),
            $this->createDatabaseHas()
        );

        $this->afterCreateAssertions();
    }

    #[Test]
    public function admin_can_show_update_and_delete_entity_via_admin_crud(): void
    {
        $auth = $this->actingAsAdmin();
        $item = $this->createItem();
        $id = $this->itemId($item);

        $this
            ->withHeaders($auth['headers'])
            ->getJson("{$this->endpoint()}/{$id}")
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath($this->showIdPath(), $id);

        $this
            ->withHeaders($auth['headers'])
            ->patchJson("{$this->endpoint()}/{$id}", $this->updatePayload($item))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Updated successfully');

        $this->assertDatabaseHas(
            $this->table(),
            $this->updateDatabaseHas($item)
        );

        $this
            ->withHeaders($auth['headers'])
            ->deleteJson("{$this->endpoint()}/{$id}")
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Deleted successfully');

        $this->assertDatabaseMissing($this->table(), ['id' => $id]);
    }
}
