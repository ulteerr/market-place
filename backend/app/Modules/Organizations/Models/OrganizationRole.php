<?php

declare(strict_types=1);

namespace Modules\Organizations\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrganizationRole extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = "organization_roles";

    protected $keyType = "string";

    public $incrementing = false;

    protected $fillable = ["code"];
}
