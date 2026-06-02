<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Append-only Event Store (CDP-ready). Immutable: updates/deletes are blocked.
 * Schema is intentionally generic + versioned so Customer-360 can be built later
 * without migrating historical events.
 */
class Event extends Model
{
    public const UPDATED_AT = null; // append-only: only created_at

    protected $fillable = [
        'type', 'actor_type', 'actor_id', 'subject_type', 'subject_id',
        'mall_id', 'payload', 'schema_version',
    ];

    protected $casts = [
        'payload' => 'array',
        'schema_version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Event Store is append-only: events cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Event Store is append-only: events cannot be deleted.');
        });
    }
}
