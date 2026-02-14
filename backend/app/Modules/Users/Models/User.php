<?php

declare(strict_types=1);

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use App\Shared\Traits\HasChangeLog;
use Modules\Children\Models\Child;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;
use Modules\Files\Traits\HasFiles;

final class User extends Authenticatable
{
    use Notifiable, HasUuid, HasApiTokens, HasFactory, HasFiles, HasChangeLog;

    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "first_name",
        "last_name",
        "middle_name",
        "email",
        "password",
        "phone",
        "settings",
    ];

    protected $hidden = ["password", "remember_token"];

    protected $casts = [
        "email_verified_at" => "datetime",
        "settings" => "array",
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            $user->files()->delete();
        });
    }

    protected function password(): Attribute
    {
        return Attribute::make(set: fn(?string $value) => $value ? Hash::make($value) : null);
    }

    public function children()
    {
        return $this->hasMany(Child::class, "user_id");
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, "role_user", "user_id", "role_id");
    }

    public function avatar()
    {
        return $this->fileFromCollection("avatar");
    }

    public function hasRole(string $code): bool
    {
        return $this->roles->contains("code", $code);
    }

    public function hasAnyRole(array $codes): bool
    {
        return $this->roles->whereIn("code", $codes)->isNotEmpty();
    }
    public function isAdmin(): bool
    {
        return $this->roles->contains("code", "admin");
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->roles->where("code", "!=", "participant")->isNotEmpty();
    }

    public function changeLogExcludedAttributes(): array
    {
        return ["password", "remember_token"];
    }
}
