<?php

namespace App\Http\Controllers;

use App\Models\AuditRecord;
use App\Models\Institution;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; 
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        $query = AuditRecord::query()
            ->with(['institution', 'auditor'])
            ->latest('audit_date');

        if ($user->isEditor()) {
            $query->whereHas('institution', function ($q) use ($user): void {
                $q->whereHas('assignedUsers', fn ($a) => $a->whereKey($user->id));
            });
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->whereHas('institution', function ($q) use ($search): void {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($search) . '%']);
            });
        }

        $status = trim((string) $request->query('status', ''));
        if (in_array($status, AuditRecord::STATUSES, true)) {
            $query->where('validation_status', $status);
        }

        $audits = $query->paginate(20)->withQueryString();

        return view('audits.index', compact('audits'));
    }

    public function create(Institution $institution): View
    {
        abort_unless(
            in_array($institution->type, ['School', 'Barangay Hall'], true),
            404
        );

        $user = request()->user();

        if ($user->isEditor() && ! $user->canAccessInstitution($institution)) {
            abort(403, 'You are not assigned to this institution.');
        }

        return view('audits.create', compact('institution'));
    }

    public function store(
        Request $request,
        Institution $institution,
        NotificationService $notificationService
    ): RedirectResponse {
        abort_unless(
            in_array($institution->type, ['School', 'Barangay Hall'], true),
            404
        );

        $user = $request->user();

        if ($user->isEditor() && ! $user->canAccessInstitution($institution)) {
            abort(403, 'You are not assigned to this institution.');
        }

        $validated = $request->validate([
            'audit_date' => ['required', 'date', 'before_or_equal:today'],
            'validation_status' => ['required', Rule::in(AuditRecord::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($institution, $user, $validated): void {
            $audit = AuditRecord::create([
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'audit_date' => $validated['audit_date'],
                'validation_status' => $validated['validation_status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $institution->update([
                'last_audited_at' => $validated['audit_date'],
                'audited_by' => $user->id,
                'audit_notes' => $validated['notes'] ?? null,
                'requires_audit' => false,
            ]);
        });

        $notificationService->auditSubmitted(
            $user,
            $institution->name,
            $institution->id,
            null,
            route('institutions.show', $institution)
        );

        return redirect()
            ->route('institutions.show', $institution)
            ->with('success', 'Audit submitted successfully.');
    }

    public function edit(AuditRecord $audit): View
    {
        $user = request()->user();

        if ($user->isEditor() && ! $user->canAccessInstitution($audit->institution)) {
            abort(403, 'You are not assigned to this institution.');
        }

        $audit->load('institution');

        return view('audits.edit', compact('audit'));
    }

    public function update(
        Request $request,
        AuditRecord $audit,
        NotificationService $notificationService
    ): RedirectResponse {
        $user = $request->user();

        if ($user->isEditor() && ! $user->canAccessInstitution($audit->institution)) {
            abort(403, 'You are not assigned to this institution.');
        }

        $validated = $request->validate([
            'audit_date' => ['required', 'date', 'before_or_equal:today'],
            'validation_status' => ['required', Rule::in(AuditRecord::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $previousStatus = $audit->validation_status;

        DB::transaction(function () use ($audit, $validated): void {
            $audit->update([
                'audit_date' => $validated['audit_date'],
                'validation_status' => $validated['validation_status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $audit->institution->update([
                'last_audited_at' => $validated['audit_date'],
                'audit_notes' => $validated['notes'] ?? null,
            ]);
        });

        if ($previousStatus !== $validated['validation_status']) {
            $notificationService->auditStatusChanged(
                $audit->auditor,
                $audit->institution->name,
                $validated['validation_status'],
                $audit->institution_id,
                $audit->audit_id,
                route('institutions.show', $audit->institution)
            );
        }

        return redirect()
            ->route('audits.index')
            ->with('success', 'Audit record updated successfully.');
    }

    public function destroy(AuditRecord $audit): RedirectResponse
    {
        $user = request()->user();

        if ($user->isEditor() && ! $user->canAccessInstitution($audit->institution)) {
            abort(403, 'You are not assigned to this institution.');
        }

        $institutionId = $audit->institution_id;
        $audit->delete();

        return redirect()
            ->route('institutions.show', $institutionId)
            ->with('success', 'Audit record deleted successfully.');
    }
}