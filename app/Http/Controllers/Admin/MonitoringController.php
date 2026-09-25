<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EarlyWarningService;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function __invoke(EarlyWarningService $earlyWarning): View
    {
        $summary = $earlyWarning->summary();

        return view('admin.monitoring', [
            'atRisk' => collect($summary['atRisk']),
            'classStats' => collect($summary['classStats']),
            'periodLabel' => $summary['periodLabel'],
        ]);
    }
}
