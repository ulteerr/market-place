<?php
declare(strict_types=1);

namespace Modules\Children\Models;

use Illuminate\Database\Eloquent\Model;
use App\Shared\Traits\HasUuid;
use App\Shared\Traits\HasActionLog;
use App\Shared\Traits\HasChangeLog;
use Modules\Children\Database\Factories\ChildFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Users\Models\User;

final class Child extends Model
{
    use HasFactory;
    use HasUuid;
    use HasActionLog;
    use HasChangeLog;

    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "user_id",
        "first_name",
        "last_name",
        "middle_name",
        "gender",
        "birth_date",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function parent(): BelongsTo
    {
        return $this->user();
    }

    protected static function newFactory(): ChildFactory
    {
        return ChildFactory::new();
    }
}
