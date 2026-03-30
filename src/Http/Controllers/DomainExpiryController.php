<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Http\Controllers;

use CranleighSchool\DomainExpiry\DomainExpiryService;
use CranleighSchool\DomainExpiry\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DomainExpiryController extends Controller
{
    public function __construct(private readonly DomainExpiryService $service) {}

    public function index(): View
    {
        return view('domain-expiry::dashboard', $this->getAllCheckManyAndSummarise());
    }

    public function json(): JsonResponse
    {
        ['results' => $results, 'summary' => $summary] = $this->getAllCheckManyAndSummarise();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'summary' => $summary,
            'domains' => $results->map->toArray()->all(),
        ]);
    }

    /**
     * @return array{results: Collection, summary: array<string, int>}
     */
    private function getAllCheckManyAndSummarise(): array
    {
        $domains = Domain::query()->active()->pluck('domain')->all();
        $results = $this->service->checkMany($domains);
        $summary = $this->service->summarise($results);

        return compact('results', 'summary');
    }

    public function refresh(Request $request): RedirectResponse
    {
        $domain = $request->string('domain')->toString();

        if (blank($domain)) {
            return redirect()->route('domain-expiry.index');
        }

        $this->service->refresh($domain);

        return redirect()
            ->route('domain-expiry.index')
            ->with('refreshed', $domain);
    }

    public function refreshAll(): RedirectResponse
    {
        $domains = Domain::query()->active()->pluck('domain')->all();
        $this->service->refreshMany($domains);

        return redirect()
            ->route('domain-expiry.index')
            ->with('refreshed_all', true);
    }
}
