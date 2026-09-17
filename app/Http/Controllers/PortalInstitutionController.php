<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;

class PortalInstitutionController extends Controller
{
    public function index(Request $request)
    {
        $query = Institution::query()
            ->select([
                'id',
                'name',
                'type',
                'barangay_id',
                'student_population',
                'staff_population',
                'is_active',
                'is_pilot_site',
                'created_at',
            ])
            ->with('barangay:id,name')
            ->latest();

        $user = $request->user();

        if ($user->isEditor()) {
            $query->whereHas(
                'assignedUsers',
                fn ($assigned) => $assigned->whereKey($user->id)
            );
        }

        $institutions = $query
            ->paginate(20)
            ->withQueryString();

        return view('institutions.index', compact('institutions'));
    }

    public function show(Request $request, Institution $institution)
    {
        $user = $request->user();

        if ($user->isEditor() && ! $user->canAccessInstitution($institution)) {
            abort(403, 'You are not assigned to this institution.');
        }

        $institution->load([
            'barangay',
            'ictInventory' => function ($query): void {
                $query->latest('asset_id');
            },
            'institutionAnalytics',
            'auditor',
            'auditRecords' => function ($query): void {
                $query->latest('audit_date');
            },
        ]);

        return view('institutions.show', compact('institution'));
    }
}
