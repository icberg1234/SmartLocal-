<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Core\Models\Mall;
use App\Modules\Core\Support\CurrentMall;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active mall (tenant) from the `X-Mall-Id` header or subdomain,
 * and stores it in the CurrentMall singleton so MallScope can apply it.
 */
final class ResolveTenant
{
    public function __construct(private readonly CurrentMall $currentMall) {}

    public function handle(Request $request, Closure $next): Response
    {
        $mallId = $this->fromHeader($request) ?? $this->fromSubdomain($request);

        if ($mallId !== null) {
            $this->currentMall->set($mallId);
        }

        return $next($request);
    }

    private function fromHeader(Request $request): ?int
    {
        $id = $request->header('X-Mall-Id');

        if ($id !== null && ctype_digit((string) $id)
            && Mall::query()->whereKey((int) $id)->exists()) {
            return (int) $id;
        }

        return null;
    }

    private function fromSubdomain(Request $request): ?int
    {
        $parts = explode('.', $request->getHost());

        if (count($parts) < 2) {
            return null; // no subdomain (e.g. localhost / bare host)
        }

        $id = Mall::query()->where('subdomain', $parts[0])->value('id');

        return $id !== null ? (int) $id : null;
    }
}
