<?php

declare(strict_types=1);

namespace Modules\Users\Tests\Unit;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mark_last_seen_updates_only_last_seen_at_field(): void
    {
        $user = User::factory()->create(["first_name" => "Alex"]);

        Carbon::setTestNow(Carbon::parse("2026-03-06 12:00:00"));
        $user->markLastSeen(Carbon::parse("2026-03-06 11:50:00"));

        $user->refresh();

        $this->assertSame("Alex", $user->first_name);
        $this->assertTrue(
            $user->last_seen_at?->equalTo(Carbon::parse("2026-03-06 11:50:00")) ?? false,
        );
    }

    #[Test]
    public function last_seen_at_cannot_be_set_via_mass_assignment(): void
    {
        $initial = Carbon::parse("2026-03-06 11:00:00");
        Carbon::setTestNow(Carbon::parse("2026-03-06 11:05:00"));

        $user = User::factory()->create([
            "first_name" => "Alex",
        ]);
        $user->markLastSeen($initial);

        $threw = false;
        try {
            $user->update([
                "last_seen_at" => Carbon::parse("2026-03-06 11:10:00"),
                "first_name" => "Ivan",
            ]);
        } catch (MassAssignmentException) {
            $threw = true;
        }

        $user->refresh();

        $this->assertFalse($threw);
        $this->assertTrue($user->last_seen_at?->equalTo($initial) ?? false);
    }
}
