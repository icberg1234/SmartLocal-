<?php

declare(strict_types=1);

namespace App\Modules\Festival\Http\Controllers;

use App\Modules\BusinessUnits\Http\Controllers\Concerns\ResolvesOwnStore;
use App\Modules\Festival\Http\Requests\CreateFestivalRequest;
use App\Modules\Festival\Http\Requests\ParticipateRequest;
use App\Modules\Festival\Models\Festival;
use App\Modules\Festival\Services\FestivalService;
use Illuminate\Http\JsonResponse;

final class FestivalController
{
    use ResolvesOwnStore;

    public function __construct(private readonly FestivalService $service) {}

    public function store(CreateFestivalRequest $request): JsonResponse
    {
        $festival = $this->service->create($request->validated());

        return response()->json([
            'data' => ['id' => $festival->id, 'title' => $festival->title, 'status' => $festival->status],
        ], 201);
    }

    public function participate(ParticipateRequest $request, Festival $festival): JsonResponse
    {
        $store = $this->ownStore($request);
        $this->service->participate($festival, $store, (string) $request->validated()['decision']);

        return response()->json(['message' => 'ثبت شد.']);
    }

    public function activate(Festival $festival): JsonResponse
    {
        return response()->json(['notified' => $this->service->activate($festival)]);
    }
}
