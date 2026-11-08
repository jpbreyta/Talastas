<?php

namespace App\Http\Controllers;

use App\Models\IctComponent;
use App\Models\IctInventory;
use App\Models\Institution;
use App\Services\InventoryCounts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IctInventoryController extends Controller
{
    private ?Collection $componentCache = null;

    private function inventoryComponents(): Collection
    {
        return $this->componentCache ??= IctComponent::active()->get();
    }

    public const MAX_INTERNET_CONNECTIONS = 255;

    public const COMPONENT_TYPES = [
        IctComponent::TYPE_HARDWARE,
        IctComponent::TYPE_SOFTWARE,
        IctComponent::TYPE_NETWORK,
        IctComponent::TYPE_CONNECTIVITY,
    ];

    public function index(Request $request)
    {
        $sort = $request->query('sort', 'name');
        $sort = is_string($sort) && in_array($sort, \App\Services\InventoryListOrdering::OPTIONS, true) ? $sort : 'name';
        $institutions = Institution::query()
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->with([
                'barangay:id,name',
                'ictInventory' => fn ($query) =>
                    $query
                        ->whereIn(
                            'component_type',
                            self::COMPONENT_TYPES
                        )
                        ->latest('asset_id'),
            ])
            ->withSum( 
                [
                    'ictInventory as total_assets_count' =>
                        fn ($query) => $query->whereIn(
                            'component_type',
                            self::COMPONENT_TYPES
                        ),
                ],
                'total_count'
            )
            ->withSum(
                [
                    'ictInventory as active_assets_count' =>
                        fn ($query) => $query->whereIn(
                            'component_type',
                            self::COMPONENT_TYPES
                        ),
                ],
                'active_count'
            )
            ->withSum(
                [
                    'ictInventory as attention_assets_count' =>
                        fn ($query) => $query->whereIn(
                            'component_type',
                            self::COMPONENT_TYPES
                        ),
                ],
                'defective_count'
            )
            ->when(filled($request->query('search')), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhereHas('ictInventory', fn ($items) => $items->where('asset_name', 'like', $term)));
            })
            ->when($request->query('status') === 'active', fn ($q) => $q->whereHas('ictInventory', fn ($items) => $items->where('active_count', '>', 0)))
            ->when($request->query('status') === 'attention', fn ($q) => $q->whereHas('ictInventory', fn ($items) => $items->where('defective_count', '>', 0)))
            ->when($request->query('status') === 'empty', fn ($q) => $q->whereDoesntHave('ictInventory'))
            ->tap(fn ($q) => app(\App\Services\InventoryListOrdering::class)->apply($q, $sort, self::COMPONENT_TYPES))
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
                    InventoryCounts::sum($institution->ictInventory, 'active_count')
                );
                $institution->setAttribute(
                    'attention_assets_count',
                    InventoryCounts::sum($institution->ictInventory, 'defective_count')
                );

                return $institution;
            }
        );

        return view('ict-inventory.index', [
            'institutions' => $institutions,
        ]);
    }

    public function create(Request $request)
    {
        $selectedInstitution = null;

        $requestedInstitutionId =
            $request->integer('institution_id')
            ?: (int) old('institution_id');

        if ($requestedInstitutionId > 0) {
            $selectedInstitution = Institution::with('barangay')
                ->whereIn('type', ['School', 'Barangay Hall'])
                ->findOrFail($requestedInstitutionId);
        }

        $institutions = Institution::with('barangay')
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->orderBy('name')
            ->get();

        $sections = [];

        foreach (self::COMPONENT_TYPES as $componentType) {
            $components = $this->componentsForSection(
                $componentType
            );

            $sections[$componentType] = [
                'meta' => $this->sectionMeta($componentType),
                'components' => $components,
                'items' => $this->initialItemsForSection(
                    $componentType,
                    $components,
                    $selectedInstitution
                ),
            ];
        }

        return view('ict-inventory.create', [
            'institutions' => $institutions,
            'selectedInstitution' => $selectedInstitution,
            'sections' => $sections,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->boolean('unified_inventory')) {
            return $this->storeUnifiedInventory($request);
        }

        $inventory = $this->persistInventory($request);

        return $this->redirectAfterWrite(
            $request,
            $inventory,
            'ICT inventory record added successfully.'
        );
    }

    public function show(IctInventory $ictInventory)
    {
        $ictInventory->load([
            'institution.barangay',
            'component',
        ]);

        return view('ict-inventory.show', [
            'ictInventory' => $ictInventory,
        ]);
    }

    public function edit(IctInventory $ictInventory)
    {
        $ictInventory->load([
            'institution',
            'component',
        ]);

        $institutions = Institution::with('barangay')
            ->whereIn('type', ['School', 'Barangay Hall'])
            ->orderBy('name')
            ->get();

        return view('ict-inventory.edit', [
            'ictInventory' => $ictInventory,
            'institutions' => $institutions,
            'componentType' => $ictInventory->component_type,
            'sectionMeta' => $this->sectionMeta(
                $ictInventory->component_type
            ),
            'components' => $this->componentsForSection(
                $ictInventory->component_type
            ),
        ]);
    }

    public function update(
        Request $request,
        IctInventory $ictInventory
    ) {
        $this->persistInventory($request, $ictInventory);

        return $this->redirectAfterWrite(
            $request,
            $ictInventory,
            'ICT inventory record updated successfully.'
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
                    'ICT inventory record deleted successfully.'
                );
        }

        return redirect()
            ->route('ict-inventory.index')
            ->with(
                'success',
                'ICT inventory record deleted successfully.'
            );
    }

    public function storeApi(Request $request)
    {
        $inventory = $this->persistInventory($request);

        return response()->json(
            $inventory->load([
                'institution.barangay',
                'component',
            ]),
            201
        );
    }

    public function updateApi(
        Request $request,
        IctInventory $ictInventory
    ) {
        $this->persistInventory($request, $ictInventory);

        return response()->json(
            $ictInventory
                ->fresh()
                ->load([
                    'institution.barangay',
                    'component',
                ])
        );
    }

    public function destroyApi(IctInventory $ictInventory)
    {
        $ictInventory->delete();

        return response()->json([
            'message' =>
                'ICT inventory record deleted successfully.',
        ]);
    }

    /**
     * @return Collection<int, IctComponent>
     */
    protected function componentsForSection(string $section): Collection
    {
        return $this->inventoryComponents()->filter(fn ($component) =>
            $component->component_type === $section && $component->is_standard
        )->sortBy('id')->values();
    }

    /**
     * @param Collection<int, IctComponent> $components
     */
    protected function initialItemsForSection(
        string $section,
        Collection $components,
        ?Institution $institution
    ): array {
        $oldItems = old(
            "sections.{$section}.items",
            session()->hasOldInput("sections.{$section}") ? [] : null
        );

        if (is_array($oldItems)) {
            return array_values($oldItems);
        }

        $existing = collect();

        if ($institution) {
            $institution->loadMissing('ictInventory.component');
            $existing = $institution->ictInventory->where('component_type', $section)->sortBy('asset_name');
        }

        $existingByComponentId = $existing
            ->filter(fn (IctInventory $item) => $item->component_id !== null)
            ->keyBy(fn (IctInventory $item) => (int) $item->component_id);

        $legacyByCategory = $existing
            ->filter(fn (IctInventory $item) => $item->component_id === null)
            ->keyBy('category');

        $items = $components
            ->map(function (IctComponent $component) use (
                $existingByComponentId,
                $legacyByCategory
            ): array {
                $inventory = $existingByComponentId->get($component->id)
                    ?? $legacyByCategory->get($component->key);

                return [
                    'asset_id' => $inventory?->asset_id,
                    'component_id' => $component->id,
                    'asset_name' => $component->name,
                    'subsection' => $inventory?->specifications['inventory_subsection'] ?? $component->subsection,
                    'is_standard' => true,
                    'is_computing_device' =>
                        (bool) $component->is_computing_device,
                    'total_count' =>
                        $inventory?->total_count,
                    'active_count' =>
                        $inventory?->active_count,
                    'retired_count' =>
                        $inventory?->retired_count,
                    'functional_count' =>
                        $inventory?->functional_count,
                    'defective_active_count' => $inventory?->defective_active_count,
                    'defective_count' =>
                        $inventory?->defective_count,
                    'notes' => $inventory?->notes,
                ];
            })
            ->values();

        // Legacy inventory rows may have no component_id and be matched to a
        // standard template by their category key. Keep those rows represented
        // by that template only; otherwise the same saved asset_id is rendered
        // again as a custom row and submitted twice.
        $representedAssetIds = $items
            ->pluck('asset_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $standardComponentIds = $components
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $customItems = $existing
            ->filter(function (IctInventory $item) use (
                $standardComponentIds,
                $representedAssetIds
            ): bool {
                if (in_array((int) $item->asset_id, $representedAssetIds, true)) {
                    return false;
                }

                if ($item->component_id === null) {
                    return true;
                }

                return !in_array(
                    (int) $item->component_id,
                    $standardComponentIds,
                    true
                );
            })
            ->map(function (IctInventory $item): array {
                return [
                    'asset_id' => $item->asset_id,
                    'component_id' => $item->component_id,
                    'asset_name' => $item->asset_name,
                    'subsection' =>
                        $item->specifications['inventory_subsection'] ?? $item->component?->subsection
                        ?? 'Other Equipment',
                    'is_standard' => false,
                    'is_computing_device' =>
                        (bool) $item->is_computing_device,
                    'total_count' =>
                        (int) $item->total_count,
                    'active_count' =>
                        $item->active_count,
                    'retired_count' =>
                        $item->retired_count,
                    'functional_count' =>
                        $item->functional_count,
                    'defective_active_count' => $item->defective_active_count,
                    'defective_count' =>
                        $item->defective_count,
                    'notes' => $item->notes,
                ];
            })
            ->values();

        return $items
            ->concat($customItems)
            ->groupBy('subsection')->flatMap(fn ($group) => $group->all())
            ->values()
            ->all();
    }

    protected function sectionMeta(string $section): array
    {
        return match ($section) {
            IctComponent::TYPE_CONNECTIVITY => [
                'title' => 'Internet Connectivity',
                'description' => 'Record each Internet connection separately.',
                'icon' => 'fa-globe',
            ],
            IctComponent::TYPE_SOFTWARE => [
                'title' => 'Software & Systems',
                'description' =>
                    'Operating systems, applications, specialized tools, and institution-developed systems.',
                'icon' => 'fa-code',
            ],

            IctComponent::TYPE_NETWORK => [
                'title' => 'Network Infrastructure',
                'description' =>
                    'Network services and physical network infrastructure from Attachment C.',
                'icon' => 'fa-network-wired',
            ],

            default => [
                'title' => 'Hardware & Equipment',
                'description' =>
                    'Computing devices, servers, storage, peripherals, and power-support equipment.',
                'icon' => 'fa-computer',
            ],
        };
    }

    protected function storeUnifiedInventory(Request $request)
    {
        $rules = [
            'removed_asset_ids' => ['sometimes', 'array', 'max:800'],
            'removed_asset_ids.*' => ['integer', 'distinct'],
            'q10' => ['sometimes', 'array:has_internet_connection,number_of_connections,connections,removed_connection_numbers'],
            'q10.has_internet_connection' => ['sometimes', 'nullable', 'boolean'],
            'q10.removed_connection_numbers' => ['sometimes', 'array', 'max:255'],
            'q10.removed_connection_numbers.*' => ['integer', 'min:1', 'max:255', 'distinct'],
            'q10.number_of_connections' => ['nullable', 'integer', 'min:0', 'max:'.self::MAX_INTERNET_CONNECTIONS],
            'q10.connections' => ['sometimes', 'array', 'max:'.self::MAX_INTERNET_CONNECTIONS],
            'q10.connections.*' => ['array:isp,plan,allocation,validity,context,advertised_download_mbps,download_speed_mbps,upload_speed_mbps,latency_ms,throughput_mbps'],
            'institution_id' => [
                'required',
                'integer',
                Rule::exists('institutions', 'id'),
            ],
            'sections' => [
                'required',
                'array',
            ],
        ];

        foreach (['isp', 'plan', 'allocation', 'validity', 'context'] as $field) {
            $rules["q10.connections.*.{$field}"] = ['sometimes', 'nullable', 'string', 'max:500'];
        }
        $rules['q10.connections.*.advertised_download_mbps'] = ['sometimes', 'nullable', 'numeric', 'min:0.000001', 'max:1000000', 'decimal:0,6'];
        foreach (['download_speed_mbps', 'upload_speed_mbps', 'latency_ms', 'throughput_mbps'] as $field) {
            $rules["q10.connections.*.{$field}"] = ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000000'];
        }
        foreach (self::COMPONENT_TYPES as $componentType) {
            $prefix = "sections.{$componentType}.items";

            $rules["sections.{$componentType}"] = ['nullable', 'array'];
            $rules["{$prefix}.*"] = ['required', 'array'];

            $rules[$prefix] = [
                'nullable',
                'array',
                'max:200',
            ];

            $rules["{$prefix}.*.asset_id"] = [
                'nullable',
                'integer',
            ];

            $rules["{$prefix}.*.component_id"] = [
                'nullable',
                'integer',
            ];

            $rules["{$prefix}.*.subsection"] = ['sometimes', 'nullable', 'string', 'max:150'];

            $rules["{$prefix}.*.asset_name"] = [
                'nullable',
                'string',
                'max:150',
            ];

            foreach ([
                'total_count',
                'active_count',
                'retired_count',
                'defective_count',
                'defective_active_count',
            ] as $field) {
                $rules["{$prefix}.*.{$field}"] = [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:1000000',
                ];
            }

            $rules["{$prefix}.*.functional_count"] = [
                'sometimes', 'nullable', 'integer', 'min:0', 'max:1000000',
            ];
        }

        $validator = Validator::make(
            $request->all(),
            $rules
        );

        $validator->after(
            function ($validator) use ($request): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                foreach ((array) $request->input('q10.connections', []) as $number => $connection) {
                    if (!preg_match('/^[1-9][0-9]*$/D', (string) $number) || (int) $number > self::MAX_INTERNET_CONNECTIONS) {
                        $validator->errors()->add('q10.connections', 'Invalid connection number.');
                    }
                }
                $institutionId =
                    (int) $request->input('institution_id');

                $existingRows = IctInventory::where('institution_id', $institutionId)->get()->keyBy('asset_id');
                $components = $this->inventoryComponents()->keyBy('id');
                foreach ((array) $request->input('removed_asset_ids', []) as $id) {
                    if (!$request->user()?->isAdmin() || !$existingRows->has($id)) {
                        $validator->errors()->add('removed_asset_ids', 'You cannot delete this inventory record.');
                    }
                }
                foreach (
                    self::COMPONENT_TYPES
                    as $componentType
                ) {
                    $items = (array) $request->input(
                        "sections.{$componentType}.items",
                        []
                    );

                    $seen = [];
                    $seenAssets = [];

                    foreach ($items as $index => $item) {
                        $path =
                            "sections.{$componentType}.items.{$index}";

                        $submittedComponentId = (int) ($item['component_id'] ?? 0);
                        $identity = $submittedComponentId > 0
                            ? $components->get($submittedComponentId)
                            : $components->first(fn (IctComponent $component) =>
                                $component->component_type === $componentType
                                && $this->normalizeInventoryName($component->name)
                                    === $this->normalizeInventoryName((string) ($item['asset_name'] ?? ''))
                            );
                        $name = $this->normalizeInventoryName(
                            $identity?->is_standard
                                ? $identity->name
                                : (string) ($item['asset_name'] ?? '')
                        );
                        $identityKey = $name !== '' ? 'name:'.$name : null;
                        if ($identityKey !== null && collect(['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'])->contains(fn ($field) => isset($item[$field]) && $item[$field] !== '')) {
                            if (isset($seen[$identityKey])) {
                                $validator->errors()->add(
                                    "{$path}.asset_name",
                                    'Each component name may appear only once in the same category.'
                                );
                            }

                            $seen[$identityKey] = true;
                        }

                        $assetId =
                            (int) ($item['asset_id'] ?? 0);

                        if ($assetId > 0) {
                            if (isset($seenAssets[$assetId]) || in_array($assetId, array_map('intval', (array) $request->input('removed_asset_ids', [])), true)) {
                                $validator->errors()->add("{$path}.asset_id", 'Submit each saved item only once.');
                            }
                            $seenAssets[$assetId] = true;
                            $belongsToInstitution = $existingRows->get($assetId)?->component_type === $componentType;

                            if (!$belongsToInstitution) {
                                $validator->errors()->add(
                                    "{$path}.asset_id",
                                    'The saved inventory row does not belong to this institution and category.'
                                );
                            }
                        }

                        $componentId =
                            (int) (
                                $item['component_id'] ?? 0
                            );

                        if ($componentId > 0) {
                            $componentMatches = $components->get($componentId)?->component_type === $componentType;

                            if (!$componentMatches) {
                                $validator->errors()->add(
                                    "{$path}.component_id",
                                    'The component is not valid for this category.'
                                );
                            }
                        }

                        $countFields = ['total_count', 'active_count', 'retired_count', 'functional_count', 'defective_count', 'defective_active_count'];
                        $hasCounts = collect($countFields)->contains(fn ($field) => isset($item[$field]) && $item[$field] !== '');
                        if (! $hasCounts) {
                            continue;
                        }
                        $itemName = $identity?->is_standard
                            ? trim($identity->name)
                            : trim((string) ($item['asset_name'] ?? ''));
                        if ($itemName === '') {
                            $validator->errors()->add("{$path}.asset_name", 'Enter an item name for a recorded row.');
                        }

                        $effective = $item;
                        if ($assetId > 0 && $existingRows->has($assetId)) {
                            foreach (['total_count', ...InventoryCounts::AVAILABILITY_FIELDS] as $field) {
                                if (!array_key_exists($field, $effective)) {
                                    $effective[$field] = $existingRows->get($assetId)->{$field};
                                }
                            }
                        }
                        foreach (InventoryCounts::errors($effective) as $field => $message) {
                            $validator->errors()->add("{$path}.{$field}", $message);
                        }
                    }
                }
            }
        );

        $validated = $validator->validate();

        $institution = Institution::query()
            ->whereIn(
                'type',
                ['School', 'Barangay Hall']
            )
            ->findOrFail(
                (int) $validated['institution_id']
            );

        \App\Observers\IctInventoryObserver::batch(fn () => DB::transaction(
            function () use (
                $request,
                $validated,
                $institution
            ): void {
                $institution = Institution::whereKey($institution->id)->lockForUpdate()->firstOrFail();
                foreach ((array) ($validated['removed_asset_ids'] ?? []) as $id) {
                    IctInventory::where('institution_id', $institution->id)->findOrFail($id)->delete();
                }
                foreach (
                    self::COMPONENT_TYPES
                    as $componentType
                ) {
                    if (! array_key_exists($componentType, $validated['sections'])) {
                        continue;
                    }
                    $items = (array) data_get(
                        $validated,
                        "sections.{$componentType}.items",
                        []
                    );

                    $this->syncInventorySection(
                        (int) $institution->id,
                        $componentType,
                        $items
                    );
                }

                if (array_key_exists('q10', $validated)) {
                    app(\App\Services\InternetConnectionChanges::class)->save($institution, $validated['q10']);
                }
            }
        ));

        $message =
            'Inventory saved.';

        if ($request->expectsJson()) {
            $destination = $request->input('return_to') === 'institution'
                ? route('institutions.show', $institution->id) : route('ict-inventory.index');
            $request->session()->flash('success', $message);
            return response()->json(['redirect' => $destination]);
        }

        if (
            $request->input('return_to')
            === 'institution'
        ) {
            return redirect()
                ->route(
                    'institutions.show',
                    $institution->id
                )
                ->with('success', $message);
        }

        return redirect()
            ->route('ict-inventory.index')
            ->with('success', $message);
    }

    private function syncInventorySection(
        int $institutionId,
        string $componentType,
        array $items
    ): void {
        $existing = IctInventory::where('institution_id', $institutionId)
            ->where('component_type', $componentType)->get();
        $byId = $existing->keyBy('asset_id');
        $byCategory = $existing->keyBy('category');
        foreach ($items as $item) {
            if (!collect(['total_count', ...InventoryCounts::AVAILABILITY_FIELDS])
                ->contains(fn ($field) => isset($item[$field]) && $item[$field] !== '')) {
                continue;
            }
            $component = $this->inventoryComponents()->firstWhere('id', (int) ($item['component_id'] ?? 0));
            $name = $component?->is_standard ? $component->name : trim((string) ($item['asset_name'] ?? ''));
            $inventory = !empty($item['asset_id']) ? $byId->get($item['asset_id']) : null;
            if (!$inventory && $component) {
                $inventory = $existing->first(fn ($row) => (int) $row->component_id === (int) $component->id);
            }
            if (!$inventory && !$component) {
                $normalizedName = $this->normalizeInventoryName($name);
                $inventory = $existing->first(fn ($row) => $this->normalizeInventoryName((string) $row->asset_name) === $normalizedName);
            }
            $functionalOmitted = !array_key_exists('functional_count', $item);
            if ($inventory) {
                // Omitted fields in partial requests retain their recorded values.
                foreach (['total_count', ...InventoryCounts::AVAILABILITY_FIELDS] as $field) {
                    if (!array_key_exists($field, $item)) {
                        $item[$field] = $inventory->{$field};
                    }
                }
            }
            $errors = InventoryCounts::errors($item);
            if ($errors) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sections.'.$componentType => reset($errors),
                ]);
            }
            if ($inventory && $functionalOmitted) {
                $item['functional_count'] = $inventory->getRawOriginal('functional_count');
                $item['functional_count_recorded'] = $inventory->functional_count_recorded;
            }
            $data = $this->buildInventoryData($institutionId, $componentType, $item);
            $categoryMatch = $byCategory->get($data['category']);
            if ($inventory && $categoryMatch && !$inventory->is($categoryMatch)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'sections.'.$componentType => 'This component already has an inventory record.',
                ]);
            }
            $inventory ??= $categoryMatch;
            $inventory ??= new IctInventory;
            if (isset($item['subsection']) && trim($item['subsection']) !== '') {
                $subsection = preg_replace('/\s+/u', ' ', trim($item['subsection']));
                $knownGroups = $this->inventoryComponents()->where('component_type', $componentType)->pluck('subsection')
                    ->merge($existing->map(fn ($row) => $row->specifications['inventory_subsection'] ?? $this->inventoryComponents()->firstWhere('id', $row->component_id)?->subsection));
                $subsection = $knownGroups->first(fn ($name) => $name && Str::lower(trim($name)) === Str::lower($subsection)) ?? $subsection;
                $data['specifications'] = array_replace($inventory->specifications ?? [], ['inventory_subsection' => $subsection]);
            }
            $inventory->fill($data);
            if ($inventory->isDirty()) {
                $inventory->save();
            }
            $byCategory->put($data['category'], $inventory);
        }
        // Omitted and blank templates never delete inventory or hide component templates.
    }

    private function normalizeInventoryName(string $name): string
    {
        return Str::lower(Str::squish($name));
    }

    private function persistInventory(Request $request, ?IctInventory $existing = null): IctInventory
    {
        return DB::transaction(function () use ($request, $existing): IctInventory {
            $request->validate(['institution_id' => ['sometimes', 'required', 'integer', 'min:1']]);
            $ids = array_filter([$existing?->institution_id, $request->input('institution_id')]);
            Institution::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($existing) {
                $existing->refresh();
            }
            $data = $this->validateInventory($request, $existing !== null, $existing);
            $inventory = $existing ?? new IctInventory;
            $inventory->fill($data);
            if ($inventory->isDirty()) {
                $inventory->save();
            }
            return $inventory;
        });
    }

    private function validateInventory(
        Request $request,
        bool $partial = false,
        ?IctInventory $existing = null
    ): array {
        $request->validate(['category' => ['nullable', 'string', 'max:100']]);

        /*
         * Backward compatibility for older API clients that still submit the
         * seeded component key instead of the editable component name.
         */
        if (
            $request->filled('category')
            && (
                !$request->filled('asset_name')
                || !$request->filled('component_type')
            )
        ) {
            $legacyComponent = IctComponent::query()
                ->where(
                    'key',
                    (string) $request->input('category')
                )
                ->first();

            if ($legacyComponent) {
                $request->merge([
                    'asset_name' =>
                        $request->input('asset_name')
                        ?: $legacyComponent->name,
                    'component_type' =>
                        $request->input('component_type')
                        ?: $legacyComponent->component_type,
                ]);
            }
        }

        $required = $partial
            ? ['sometimes', 'required']
            : ['required'];

        $counts = $partial
            ? ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000000']
            : ['nullable', 'integer', 'min:0', 'max:1000000'];

        $validator = Validator::make(
            $request->all(),
            [
                'institution_id' => [
                    ...$required,
                    'integer',
                    Rule::exists('institutions', 'id'),
                ],
                'component_type' => [
                    ...$required,
                    Rule::in(self::COMPONENT_TYPES),
                ],
                'asset_name' => [
                    ...$required,
                    'string',
                    'max:150',
                ],
                'total_count' => [...$required, 'integer', 'min:0', 'max:1000000'],
                'active_count' => $counts,
                'retired_count' => $counts,
                'functional_count' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000000'],
                'defective_count' => $counts,
                'defective_active_count' => $counts,
            ]
        );

        $validator->after(
            function ($validator) use (
                $request,
                $existing,
                $partial
            ): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $effectiveCounts = [];
                foreach (['total_count', ...InventoryCounts::AVAILABILITY_FIELDS] as $field) {
                    $effectiveCounts[$field] = $request->exists($field)
                        ? $request->input($field) : ($partial ? $existing?->{$field} : null);
                }
                foreach (InventoryCounts::errors($effectiveCounts) as $field => $message) {
                    $validator->errors()->add($field, $message);
                }

                $institutionId = (int) (
                    $request->input('institution_id')
                    ?? $existing?->institution_id
                    ?? 0
                );

                $componentType = (string) (
                    $request->input('component_type')
                    ?? $existing?->component_type
                    ?? ''
                );

                $assetName = trim((string) (
                    $request->input('asset_name')
                    ?? $existing?->asset_name
                    ?? ''
                ));

                if (
                    $institutionId > 0
                    && $componentType !== ''
                    && $assetName !== ''
                ) {
                    $duplicate = IctInventory::query()
                        ->where(
                            'institution_id',
                            $institutionId
                        )
                        ->where(
                            'component_type',
                            $componentType
                        )
                        ->whereRaw(
                            'LOWER(asset_name) = ?',
                            [Str::lower($assetName)]
                        )
                        ->when(
                            $existing,
                            fn ($query) =>
                                $query->whereKeyNot(
                                    $existing->getKey()
                                )
                        )
                        ->exists();

                    if ($duplicate) {
                        $validator->errors()->add(
                            'asset_name',
                            'This component already exists for the selected institution and category.'
                        );
                    }
                }
            }
        );

        $validated = $validator->validate();

        $institutionId = (int) (
            $validated['institution_id']
            ?? $existing?->institution_id
            ?? 0
        );

        $componentType = (string) (
            $validated['component_type']
            ?? $existing?->component_type
            ?? IctComponent::TYPE_HARDWARE
        );

        $item = [
            // Keep the saved component identity when editing a record. Without
            // this, a renamed/custom item can be resolved as a different
            // component and the update may change its category association.
            'component_id' => $partial
                ? $existing?->component_id
                : null,
            'defective_active_count' => array_key_exists('defective_active_count', $validated)
                ? $validated['defective_active_count'] : ($partial ? $existing?->defective_active_count : null),
            'functional_count' => array_key_exists('functional_count', $validated)
                ? $validated['functional_count'] : ($partial ? $existing?->getRawOriginal('functional_count') : null),
            'functional_count_recorded' => array_key_exists('functional_count', $validated)
                ? InventoryCounts::quantity($validated['functional_count']) !== null : ($partial && (bool) $existing?->functional_count_recorded),
            'asset_name' =>
                $validated['asset_name']
                ?? $existing?->asset_name,
            'total_count' =>
                array_key_exists('total_count', $validated)
                    ? ($validated['total_count'] ?? 0)
                    : ($existing?->total_count ?? 0),
            'active_count' =>
                array_key_exists('active_count', $validated)
                    ? $validated['active_count']
                    : ($partial ? $existing?->active_count : null),
            'retired_count' =>
                array_key_exists('retired_count', $validated)
                    ? $validated['retired_count']
                    : ($partial ? $existing?->retired_count : null),
            'defective_count' =>
                array_key_exists('defective_count', $validated)
                    ? $validated['defective_count']
                    : ($partial ? $existing?->defective_count : null),
        ];

        return $this->buildInventoryData(
            $institutionId,
            $componentType,
            $item
        );
    }

    private function buildInventoryData(
        int $institutionId,
        string $componentType,
        array $item
    ): array {
        $assetName = trim(
            (string) ($item['asset_name'] ?? '')
        );

        $component = null;
        $componentId =
            (int) ($item['component_id'] ?? 0);

        $components = $this->inventoryComponents();
        if ($componentId > 0) {
            $component = $components->first(fn ($row) => $row->id === $componentId && $row->component_type === $componentType);
            if ($component?->is_standard) {
                $assetName = $component->name;
            }
        }
        $component ??= $components->first(fn ($row) =>
            $row->component_type === $componentType
            && $this->normalizeInventoryName($row->name) === $this->normalizeInventoryName($assetName)
        );

        if (!$component && $componentType === IctComponent::TYPE_CONNECTIVITY) {
            foreach (\App\Services\ConnectivityInventory::COMPONENTS as $key => $definition) {
                if (Str::lower($assetName) !== Str::lower($definition['name'])) continue;
                $component = IctComponent::firstOrCreate(['key' => $key], [
                    'name' => $definition['name'], 'component_type' => IctComponent::TYPE_CONNECTIVITY,
                    'subsection' => 'Connectivity Capacity', 'is_standard' => true,
                    'counts_toward_infrastructure' => false, 'is_computing_device' => false, 'is_active' => true,
                ]);
                break;
            }
        }

        if (!$component) {
            $category = $this->customCategoryKey(
                $componentType,
                $assetName
            );

            $component = IctComponent::query()
                ->firstOrCreate(
                    ['key' => $category],
                    [
                        'name' => $assetName,
                        'component_type' => $componentType,
                        'subsection' => 'Other Equipment',
                        'is_standard' => false,
                        'counts_toward_infrastructure' =>
                            in_array($componentType, [IctComponent::TYPE_HARDWARE, IctComponent::TYPE_NETWORK], true),
                        'is_computing_device' => false,
                        'is_active' => true,
                    ]
                );
        }

        if (!$components->contains('id', $component->id)) {
            $components->push($component);
        }

        $category = $component->key;

        $isComputing =
            (bool) $component->is_computing_device;

        $total = (int) ($item['total_count'] ?? 0);
        $active = InventoryCounts::quantity($item['active_count'] ?? null);
        $retired = InventoryCounts::quantity($item['retired_count'] ?? null);
        $defective =
            InventoryCounts::quantity($item['defective_count'] ?? null);
        $functional = isset($item['functional_count']) && $item['functional_count'] !== ''
            ? (int) $item['functional_count'] : null;
        $functionalRecorded = $item['functional_count_recorded'] ?? ($functional !== null);

        return [
            'institution_id' => $institutionId,
            'component_id' => $component->id,
            'category' => $category,
            'component_type' => $componentType,
            'is_computing_device' => $isComputing,
            'asset_name' => $assetName,

            'total_count' => $total,
            'active_count' => $active,
            'retired_count' => $retired,
            'functional_count' => $functional,
            'defective_count' => $defective,
            'defective_active_count' => InventoryCounts::quantity($item['defective_active_count'] ?? null),
            'functional_count_recorded' => $functionalRecorded,

            // Legacy fields retained for compatibility.
            'quantity' => $total,
            'status' => $active > 0
                ? 'active'
                : ($retired > 0 ? 'retired' : ($active !== null && $retired !== null ? 'inactive' : 'unrecorded')),
            'condition' => $defective === null ? 'unrecorded' : ($defective > 0
                ? 'poor'
                : 'good'),
            'needs_maintenance' => $defective === null ? null : $defective > 0,

        ];
    }

    private function customCategoryKey(
        string $componentType,
        string $assetName
    ): string {
        $slug = Str::of($assetName)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->limit(45, '')
            ->toString();

        if ($slug === '') {
            $slug = 'component';
        }

        return Str::limit(
            'custom_'
            . $componentType
            . '_'
            . $slug
            . '_'
            . substr(
                md5(
                    $componentType
                    . '|'
                    . Str::lower($assetName)
                ),
                0,
                8
            ),
            100,
            ''
        );
    }

    private function redirectAfterWrite(
        Request $request,
        IctInventory $inventory,
        string $message
    ) {
        if (
            $request->input('return_to')
            === 'institution'
        ) {
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
