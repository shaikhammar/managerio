<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        $logs = AuditLog::query()
            ->where('business_id', $business->id)
            ->with('user:id,name')
            ->when($request->event, fn ($q) => $q->where('event', $request->event))
            ->when($request->type, fn ($q) => $q->where('auditable_type', 'like', "%{$request->type}%"))
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('audit-log/index', [
            'logs' => $logs,
            'filters' => $request->only('event', 'type'),
        ]);
    }
}
