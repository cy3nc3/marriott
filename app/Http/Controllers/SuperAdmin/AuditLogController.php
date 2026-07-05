<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $normalizedSearch = mb_strtolower($search);
        $searchPattern = "%{$normalizedSearch}%";

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($search !== '', function ($query) use ($searchPattern) {
                $query->where(function ($searchQuery) use ($searchPattern) {
                    $searchQuery->whereRaw('LOWER(action) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(model_type) LIKE ?', [$searchPattern])
                        ->orWhereHas('user', function ($q) use ($searchPattern) {
                            $q->whereRaw('LOWER(name) LIKE ?', [$searchPattern]);
                        });
                });
            })
            ->when($request->input('date_from') || $request->input('date_to'), function ($query) use ($request) {
                $from = $request->input('date_from');
                $to = $request->input('date_to');

                if ($from && $to) {
                    $query->whereBetween('created_at', [
                        "{$from} 00:00:00",
                        "{$to} 23:59:59",
                    ]);
                } elseif ($from) {
                    $query->where('created_at', '>=', "{$from} 00:00:00");
                } elseif ($to) {
                    $query->where('created_at', '<=', "{$to} 23:59:59");
                }
            })
            ->latest()
            ->paginate(20)
            ->through(fn (AuditLog $log) => $log->makeHidden('ip_address'))
            ->withQueryString();

        return Inertia::render('super_admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
        ]);
    }
}
