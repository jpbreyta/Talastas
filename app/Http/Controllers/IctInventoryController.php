<?php

namespace App\Http\Controllers;

use App\Models\IctInventory;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IctInventoryController extends Controller
{
    public function index()
    {
        $institutions = Institution::query()
            ->with([
                'barangay:id,name',
                'ictInventory' => function ($query): void {
                    $query->latest('asset_id');
                },
            ])
            ->withSum('ictInventory as total_assets_count', 'quantity')
            ->withSum([
                'ictInventory as active_assets_count' => function (
                    Builder $query
                ): void {
                    $query->whereRaw('LOWER(status) = ?', [
                        IctInventory::STATUS_ACTIVE,
                    ]);
                },
            ], 'quantity')
            ->withSum([
                'ictInventory as attention_assets_count' => function (
                    Builder $query
                ): void {
                    $query->where(function (Builder $query): void {
                        $query
                            ->whereIn('status', [
                                'under_repair',
                                'inactive',
                            ])
                            ->orWhereIn('condition', [
                                'poor',
                                'needs_repair',
                            ])
                            ->orWhere('needs_maintenance', true);
                    });
                },
            ], 'quantity')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $institutions->getCollection()->transform(
            function (Institution $institution): Institution {
                $institution->setAttribute(
                    'total_assets_count',
                    (int) ($institution->total_assets_count ?? 0)
                );
                $institution->setAttribute(
                    'active_assets_count',
                    (int) ($institution->active_assets_count ?? 0)
                );
                $institution->setAttribute(
                    'attention_assets_count',
                    (int) ($institution->attention_assets_count ?? 0)
                );

                return $institution;
            }
        );

        return view('ict-inventory.index', [
            'institutions' => $institutions,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }

    public function create(Request $request)
    {
        $selectedInstitution = null;

        if ($request->filled('institution_id')) {
            $selectedInstitution = Institution::with('barangay')
                ->findOrFail($request->integer('institution_id'));
        }

        $institutions = Institution::with('barangay')
            ->orderBy('name')
            ->get();

        return view('ict-inventory.create', [
            'institutions' => $institutions,
            'selectedInstitution' => $selectedInstitution,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInventory($request);

        $inventory = IctInventory::create($validated);

        return $this->redirectAfterWrite(
            $request,
            $inventory,
            'ICT inventory item added successfully.'
        );
    }

    public function show(IctInventory $ictInventory)
    {
        $ictInventory->load(['institution.barangay']);

        return view('ict-inventory.show', [
            'ictInventory' => $ictInventory,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }

    public function edit(IctInventory $ictInventory)
    {
        $ictInventory->load('institution');

        $institutions = Institution::with('barangay')
            ->orderBy('name')
            ->get();

        return view('ict-inventory.edit', [
            'ictInventory' => $ictInventory,
            'institutions' => $institutions,
            'inventoryConfig' => config('ict_inventory'),
        ]);
    }

    public function update(
        Request $request,
        IctInventory $ictInventory
    ) {
        $validated = $this->validateInventory(
            $request,
            false,
            $ictInventory
        );

        $ictInventory->update($validated);

        return $this->redirectAfterWrite(
            $request,
            $ictInventory,
            'ICT inventory item updated successfully.'
        );
    }

    public function destroy(IctInventory $ictInventory)
    {
        $institutionId = (int) $ictInventory->institution_id;
        $returnTo = request('return_to');

        $ictInventory->delete();

        if ($returnTo === 'institution') {
            return redirect()
                ->route('institutions.show', $institutionId)
                ->with(
                    'success',
                    'ICT inventory item deleted successfully.'
                );
        }

        return redirect()
            ->route('ict-inventory.index')
            ->with(
                'success',
                'ICT inventory item deleted successfully.'
            );
    }

    public function storeApi(Request $request)
    {
        $validated = $this->validateInventory($request);

        $inventory = IctInventory::create($validated);

        return response()->json(
            $inventory->load(['institution.barangay']),
            201
        );
    }

    public function updateApi(
        Request $request,
        IctInventory $ictInventory
    ) {
        $validated = $this->validateInventory(
            $request,
            true,
            $ictInventory
        );

        $ictInventory->update($validated);

        return response()->json(
            $ictInventory
                ->fresh()
                ->load(['institution.barangay'])
        );
    }

    public function destroyApi(IctInventory $ictInventory)
    {
        $ictInventory->delete();

        return response()->json([
            'message' => 'ICT inventory item deleted successfully.',
        ]);
    }

    /**
     * Validate an ICT inventory create/update request.
     *
     * The same method is shared by web and API endpoints so category,
     * condition, status, and specification validation cannot drift apart.
     */
    private function validateInventory(
        Request $request,
        bool $partial = false,
        ?IctInventory $existing = null
    ): array {
        $this->normalizeInventoryRequest($request, $partial);

        $category = (string) (
            $request->input('category')
            ?? $existing?->category
            ?? ''
        );

        $required = $partial ? ['sometimes', 'required'] : ['required'];
        $nullable = $partial ? ['sometimes', 'nullable'] : ['nullable'];

        $categories = array_keys(
            (array) config('ict_inventory.categories', [])
        );
        $conditions = array_keys(
            (array) config('ict_inventory.conditions', [])
        );
        $statuses = array_keys(
            (array) config('ict_inventory.statuses', [])
        );

        $rules = [
            'institution_id' => [
                ...$required,
                'integer',
                Rule::exists('institutions', 'id'),
            ],

            'category' => [
                ...$required,
                'string',
                Rule::in($categories),
            ],

            'asset_name' => [
                ...$nullable,
                'string',
                'max:150',
            ],

            'brand' => [
                ...$nullable,
                'string',
                'max:100',
            ],

            'model' => [
                ...$nullable,
                'string',
                'max:150',
            ],

            'quantity' => [
                ...($partial ? ['sometimes'] : ['required']),
                'integer',
                'min:1',
                'max:1000000',
            ],

            'condition' => [
                ...$required,
                'string',
                Rule::in($conditions),
            ],

            'acquisition_year' => [
                ...$nullable,
                'integer',
                'min:1900',
                'max:' . ((int) date('Y') + 1),
            ],

            'status' => [
                ...$required,
                'string',
                Rule::in($statuses),
            ],

            'location' => [
                ...$nullable,
                'string',
                'max:150',
            ],

            'needs_maintenance' => [
                ...($partial ? ['sometimes'] : ['required']),
                'boolean',
            ],

            'notes' => [
                ...$nullable,
                'string',
                'max:5000',
            ],
        ];

        $specDefinitions = (array) config(
            "ict_inventory.categories.{$category}.specifications",
            []
        );

        if ($partial) {
            $rules['specifications'] = ['sometimes', 'nullable', 'array'];
        } else {
            $rules['specifications'] = ['nullable', 'array'];
        }

        if ($specDefinitions !== []) {
            $allowedKeys = array_keys($specDefinitions);

            $rules['specifications'][] =
                'array:' . implode(',', $allowedKeys);

            foreach ($specDefinitions as $key => $definition) {
                $definitionRules = $definition['rules']
                    ?? 'nullable';

                $rules["specifications.{$key}"] =
                    is_array($definitionRules)
                        ? $definitionRules
                        : explode('|', $definitionRules);
            }
        }

        $validated = $request->validate($rules);

        if (array_key_exists('specifications', $validated)) {
            $validated['specifications'] =
                $this->cleanSpecifications(
                    $validated['specifications']
                );
        }

        /**
         * A repair state must always imply maintenance even if a client sends
         * needs_maintenance=false.
         */
        $condition = (string) (
            $validated['condition']
            ?? $existing?->condition
            ?? ''
        );

        $status = (string) (
            $validated['status']
            ?? $existing?->status
            ?? ''
        );

        if (
            $condition === 'needs_repair'
            || $status === 'under_repair'
        ) {
            $validated['needs_maintenance'] = true;
        }

        return $validated;
    }

    /**
     * Normalize values before validation so "Smart TV", "smart-tv", and
     * "smart_tv" resolve to the same stable database key.
     */
    private function normalizeInventoryRequest(
        Request $request,
        bool $partial
    ): void {
        foreach (['category', 'condition', 'status'] as $field) {
            if ($request->filled($field)) {
                $request->merge([
                    $field => (string) Str::of(
                        (string) $request->input($field)
                    )
                        ->trim()
                        ->lower()
                        ->replace([' ', '-'], '_'),
                ]);
            }
        }

        if (!$partial || $request->has('needs_maintenance')) {
            $request->merge([
                'needs_maintenance' =>
                    $request->boolean('needs_maintenance'),
            ]);
        }

        if ($request->has('specifications')) {
            $specifications = $request->input('specifications');

            if (is_array($specifications)) {
                $request->merge([
                    'specifications' => $specifications,
                ]);
            }
        }
    }

    private function cleanSpecifications(?array $specifications): ?array
    {
        if (!$specifications) {
            return null;
        }

        $cleaned = collect($specifications)
            ->map(function (mixed $value): mixed {
                if (is_string($value)) {
                    $value = trim($value);
                }

                return $value;
            })
            ->reject(
                fn (mixed $value): bool =>
                    $value === null || $value === ''
            )
            ->all();

        return $cleaned === [] ? null : $cleaned;
    }

    private function redirectAfterWrite(
        Request $request,
        IctInventory $inventory,
        string $message
    ) {
        if ($request->input('return_to') === 'institution') {
            return redirect()
                ->route(
                    'institutions.show',
                    $inventory->institution_id
                )
                ->with('success', $message);
        }

        return redirect()
            ->route('ict-inventory.index')
            ->with('success', $message);
    }
}
