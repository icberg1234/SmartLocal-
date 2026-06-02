<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\Event;
use Illuminate\Database\Eloquent\Model;

/**
 * Single entry point for appending to the Event Store.
 * Every meaningful action in the system should be recorded here so that
 * CRM/Analytics/Billing/Notification consumers can be built off the stream.
 */
final class EventRecorder
{
    public function __construct(private readonly CurrentMall $currentMall) {}

    /**
     * @param  array<string,mixed>  $payload
     * @param  array{actor?:?Model, subject?:?Model, mall_id?:?int, schema_version?:int}  $opts
     */
    public function record(string $type, array $payload = [], array $opts = []): Event
    {
        $actor = $opts['actor'] ?? null;
        $subject = $opts['subject'] ?? null;

        return Event::create([
            'type' => $type,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'mall_id' => $opts['mall_id'] ?? $this->currentMall->id(),
            'payload' => $payload,
            'schema_version' => $opts['schema_version'] ?? 1,
        ]);
    }
}
