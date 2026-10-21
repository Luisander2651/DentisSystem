<?php

declare(strict_types=1);

namespace App\Modules\Reports\Infrastructure\Http\Controllers;

use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Spec 019 (CA1, CA9; D18, A35): the Reportes screen. The months to choose and the one chosen
 * come from ReportMonth here, not from the view; a ?mes= that is not one of them is ignored and
 * never written back. It goes straight to the value object, without a use case, because the page
 * carries no data of the report: the figures arrive through the API, whose use cases check
 * reports.view, and only.admin protects the route.
 */
final readonly class ShowReportsPageController
{
    public function __invoke(Request $request): View
    {
        $today = now();
        $months = ReportMonth::selectable($today);

        return view('pages.reportes.index', [
            'months' => array_combine(
                array_map(fn (ReportMonth $month): string => $month->value(), $months),
                array_map(fn (ReportMonth $month): string => $month->optionLabel(), $months),
            ),
            'chosenMonth' => $this->chosen((string) $request->query('mes', ''), $today)->value(),
        ]);
    }

    private function chosen(string $asked, CarbonInterface $today): ReportMonth
    {
        try {
            return ReportMonth::create($asked, $today);
        } catch (ReportMonthException) {
            return ReportMonth::lastComplete($today);
        }
    }
}
