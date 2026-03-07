<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Shared\Services\ObservabilityService;
use Modules\Auth\Contracts\TokenServiceInterface;
use Modules\Users\Contracts\UsersServiceInterface;
use Modules\Users\Events\UserWentOffline;
use Modules\Users\Events\UserWentOnline;
use Modules\Users\Models\User;
use Modules\Users\Services\PresenceService;
use Throwable;

final class AuthService
{
    public function __construct(
        private readonly UsersServiceInterface $usersService,
        private readonly TokenServiceInterface $tokenService,
        private readonly PresenceService $presenceService,
        private readonly ObservabilityService $observabilityService,
    ) {}

    public function login(array $credentials): array
    {
        $user = $this->usersService->findByEmail($credentials["email"]);

        if (!$user || !Hash::check($credentials["password"], $user->password)) {
            throw ValidationException::withMessages([
                "email" => ["Invalid credentials"],
            ]);
        }

        $user->markLastSeen();
        $this->presenceService->setOnline($user);
        $this->dispatchPresenceBroadcastEvent(new UserWentOnline($user));

        return $this->buildAuthResponse($user);
    }

    public function logout(User $user): void
    {
        $user->markLastSeen();
        $this->presenceService->setOffline($user);
        $this->dispatchPresenceBroadcastEvent(new UserWentOffline($user));
    }

    public function register(array $data): array
    {
        $user = $this->usersService->createUser($data);
        return $this->buildAuthResponse($user);
    }

    private function buildAuthResponse(User $user): array
    {
        return [
            "user" => $user,
            "token" => $this->tokenService->createToken($user),
        ];
    }

    private function dispatchPresenceBroadcastEvent(object $event): void
    {
        try {
            event($event);
            $this->observabilityService->recordEvent(
                "realtime",
                "realtime.broadcast",
                "broadcast_dispatch_ok",
                "ok",
                "info",
                null,
                ["event" => get_class($event)],
            );
        } catch (Throwable $exception) {
            $this->observabilityService->recordEvent(
                "realtime",
                "realtime.broadcast",
                "broadcast_dispatch_error",
                "error",
                "warning",
                null,
                [
                    "event" => get_class($event),
                    "error" => $exception->getMessage(),
                ],
            );
        }
    }
}
