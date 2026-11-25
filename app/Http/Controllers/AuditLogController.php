<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin: read-only audit trail with filters and CSV export. */
class AuditLogController extends Controller
{
    public const CATEGORIES = [
        'change' => 'Record changes',
        'view' => 'Viewed / sent patient data',
        'auth' => 'Sign-ins',
    ];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $logs = $this->query($filters)->paginate(50)->withQueryString();

        $staff = User::whereIn('role', ['admin', 'pharmacist'])->orderBy('name')->get(['id', 'name', 'role']);
        $patients = Patient::withoutGlobalScopes()->with('user:id,name')->get()
            ->sortBy(fn ($p) => strtolower($p->user->name ?? ''))->values();

        $stats = [
            'today' => AuditLog::where('created_at', '>=', today())->count(),
            'failedLogins7d' => AuditLog::where('action', 'auth.login_failed')->where('created_at', '>=', now()->subDays(7))->count(),
            'patientViews7d' => AuditLog::where('action', 'like', '%.viewed')->whereNotNull('patient_id')->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('admin.audit.index', [
            'logs' => $logs,
            'filters' => $filters,
            'staff' => $staff,
            'patients' => $patients,
            'categories' => self::CATEGORIES,
            'stats' => $stats,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        Audit::record('audit_log.exported', 'Exported the audit log to CSV', $filters['patient'] ?: null);

        $filename = 'pharmatrack-audit-log-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
            fputcsv($out, ['Date/time', 'User', 'Role', 'Action', 'Description', 'Patient ID', 'Patient', 'Changes', 'IP address']);

            $this->query($filters)->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, array_map([self::class, 'csvSafe'], [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->actor_name,
                        $log->actor_role,
                        $log->action,
                        $log->description,
                        $log->patient_id,
                        $log->patient?->user?->name,
                        $log->changes ? collect($log->changes)->map(fn ($c, $f) => "{$f}: " . json_encode($c['from']) . ' -> ' . json_encode($c['to']))->implode('; ') : '',
                        $log->ip_address,
                    ]));
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $v = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'in:' . implode(',', array_keys(self::CATEGORIES))],
            'user' => ['nullable', 'integer'],
            'patient' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return [
            'q' => $v['q'] ?? '',
            'category' => $v['category'] ?? '',
            'user' => (int) ($v['user'] ?? 0),
            'patient' => (int) ($v['patient'] ?? 0),
            'from' => $v['from'] ?? '',
            'to' => $v['to'] ?? '',
        ];
    }

    private function query(array $f): Builder
    {
        return AuditLog::query()
            ->with('patient.user:id,name')
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('description', 'like', "%{$f['q']}%")
                ->orWhere('actor_name', 'like', "%{$f['q']}%")
                ->orWhere('action', 'like', "%{$f['q']}%")
                ->orWhere('ip_address', 'like', "%{$f['q']}%")))
            ->when($f['category'] === 'auth', fn ($q) => $q->where('action', 'like', 'auth.%'))
            ->when($f['category'] === 'view', fn ($q) => $q->where(fn ($w) => $w
                ->where('action', 'like', '%.viewed')->orWhere('action', 'like', '%.downloaded')
                ->orWhere('action', 'like', '%.emailed')->orWhere('action', 'like', '%.matched')))
            ->when($f['category'] === 'change', fn ($q) => $q
                ->where('action', 'not like', 'auth.%')->where('action', 'not like', '%.viewed')
                ->where('action', 'not like', '%.downloaded')->where('action', 'not like', '%.emailed')
                ->where('action', 'not like', '%.matched'))
            ->when($f['user'], fn ($q) => $q->where('user_id', $f['user']))
            ->when($f['patient'], fn ($q) => $q->where('patient_id', $f['patient']))
            ->when($f['from'] !== '', fn ($q) => $q->where('created_at', '>=', $f['from'] . ' 00:00:00'))
            ->when($f['to'] !== '', fn ($q) => $q->where('created_at', '<=', $f['to'] . ' 23:59:59'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /** Stop spreadsheet formula injection (a value starting with = + - @ runs as a formula in Excel). */
    private static function csvSafe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
