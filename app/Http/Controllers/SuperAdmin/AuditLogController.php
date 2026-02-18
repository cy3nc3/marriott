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
                $query->where('action', 'like', "%{$search}%")
                    ->orWhere('model_type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->input('date'), function ($query, $date) {
                $query->whereDate('created_at', $date);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('super_admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'date']),
        ]);
    }
}
