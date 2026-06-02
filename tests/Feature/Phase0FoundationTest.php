<?php

declare(strict_types=1);

use App\Modules\Core\Models\Event;
use App\Modules\Core\Models\Mall;
use App\Modules\Core\Support\EventRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs migrations and seeds a sample mall with floors', function () {
    $this->seed();

    expect(Mall::query()->count())->toBe(1)
        ->and(Mall::query()->first()->floors()->count())->toBe(3);
});

it('records an event into the append-only store', function () {
    $event = app(EventRecorder::class)->record('Scanned', ['qr_source' => 'entrance']);

    expect(Event::query()->count())->toBe(1)
        ->and($event->type)->toBe('Scanned')
        ->and($event->payload)->toMatchArray(['qr_source' => 'entrance']);
});

it('enforces event-store immutability', function () {
    $event = app(EventRecorder::class)->record('Registered', ['channel' => 'otp']);

    expect(fn () => $event->update(['type' => 'Tampered']))
        ->toThrow(RuntimeException::class)
        ->and(fn () => $event->delete())
        ->toThrow(RuntimeException::class);
});

it('exposes a healthy /api/v1/health endpoint', function () {
    $this->seed();

    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJson(['status' => 'ok', 'service' => 'smartlocal'])
        ->assertJsonPath('malls', 1);
});

it('scopes tenant-owned models to the current mall', function () {
    $a = Mall::factory()->create();
    $b = Mall::factory()->create();

    \App\Modules\Core\Models\Floor::query()->create(['mall_id' => $a->id, 'level' => 1, 'name' => 'L1']);
    \App\Modules\Core\Models\Floor::query()->create(['mall_id' => $b->id, 'level' => 1, 'name' => 'L1']);

    app(\App\Modules\Core\Support\CurrentMall::class)->set($a->id);

    expect(\App\Modules\Core\Models\Floor::query()->count())->toBe(1);
});
