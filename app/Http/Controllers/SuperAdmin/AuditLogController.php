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
        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('action', 'like', "%{$search}%")
                        ->orWhere('model_type', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
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
            ->withQueryString();

        return Inertia::render('super_admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date_from', 'date_to']),
        ]);
    }
}
