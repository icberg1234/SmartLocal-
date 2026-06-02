<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Http\Controllers;

use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Core\Support\CurrentMall;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class MallAnalyticsController
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly CurrentMall $currentMall,
    ) {}

    public function summary(): JsonResponse
    {
        $mallId = $this->currentMall->id() ?? throw new HttpException(422, 'پاساژ مشخص نیست.');

        return response()->json($this->analytics->summary($mallId));
    }
}
