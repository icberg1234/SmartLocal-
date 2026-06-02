<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'granted_at';

    protected $fillable = ['user_id', 'scope', 'granted_at'];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime'];
    }
}
