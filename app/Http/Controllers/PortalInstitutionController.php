<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortalInstitutionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        $visibleInstitutions = Institution::query()
            ->whereIn('type', ['School', 'Barangay Hall']);

        if ($user->isEditor()) { 
            $visibleInstitutions->whereHas(
                'assignedUsers',
                fn (Builder $assigned) =>
                    $assigned->whereKey($user->id)
            );
        }

        $barangays = Barangay::query()
            ->whereIn('id', (clone $visibleInstitutions)
                ->whereNotNull('barangay_id')
                ->select('barangay_id')
                ->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = (clone $visibleInstitutions)
            ->select([
                'id',
                'name',
                'type',
                'barangay_id',
                'is_active',
                'created_at',
            ])
            ->with('barangay:id,name');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $matches) use ($search): void {
                $matches->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereRaw('LOWER(type) LIKE ?', ['%'.mb_strtolower($search).'%'])
                    ->orWhereHas('barangay', fn (Builder $barangay) => $barangay->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($search).'%']));
            });
        }

        $barangayId = $request->integer('barangay');

        if ($barangayId > 0) {
            $query->where('barangay_id', $barangayId);
        }

        $sort = (string) $request->query(
            'sort',
            'barangay_asc'
        );

        $barangayNameSubquery = Barangay::query()
            ->select('name')
            ->whereColumn(
                'barangays.id',
                'institutions.barangay_id'
            )
            ->limit(1);

        match ($sort) {
            'barangay_desc' => $query
                ->orderByDesc($barangayNameSubquery)
                ->orderBy('name'),

            'name_asc' => $query->orderBy('name'),

            'name_desc' => $query->orderByDesc('name'),

            'newest' => $query->latest(),

            default => $query
                ->orderBy($barangayNameSubquery)
                ->orderBy('name'),
        };

        $institutions = $query
            ->paginate(20)
            ->withQueryString();

        return view(
            $request->header('X-Talastas-Fragment') === 'institutions'
                ? 'institutions._table-results' : 'institutions.index',
            compact('institutions', 'barangays')
        );
    }

    public function show(
        Request $request,
        Institution $institution
    ) {
        abort_unless(
            in_array(
                $institution->type,
                ['School', 'Barangay Hall'],
                true
            ),
            404
        );

        $user = $request->user();

        if (
            $user->isEditor()
            && !$user->canAccessInstitution($institution)
        ) {
            abort(
                403,
                'You are not assigned to this institution.'
            );
        }

        $institution->load([
            'barangay',
            'ictInventory' => function ($query): void {
                $query
                    ->with('component')
                    ->latest('asset_id');
            },
            'institutionAnalytics',
            'internetConnections',
        ]);

        return view(
            'institutions.show',
            compact('institution')
        );
    }
}
