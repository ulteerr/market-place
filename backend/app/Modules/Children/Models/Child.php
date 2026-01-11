<?php
declare(strict_types=1);

namespace Modules\Children\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Shared\Traits\HasUuid;

final class Child extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'parent_id',
        'name',
        'birth_date',
        'gender',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
