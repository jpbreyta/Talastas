<?php

return [
    // Account acknowledgement is mandatory; developer publication is distinct from institutional approval.
    'enforce_terms' => env('LEGAL_ENFORCE_TERMS', true),
    'current_terms' => 'draft-2026-10-19',
    'current_privacy' => 'draft-2026-10-19',
    // Explicitly authorized by the project developers for startup use. This is not legal certification.
    // Keep release identities/content bindings immutable; a changed document requires a new release.
    'acknowledgement_release' => 'startup-2026-10-23',
    'acknowledgement_releases' => [
        'startup-2026-10-23' => [
            'status' => 'published',
            'authority' => 'project-developers',
            'published_by' => 'TALASTAS development team',
            'published_at' => '2026-10-23T10:08:10Z',
            'effective_at' => '2026-10-23T10:08:10Z',
            'documents' => [
                'terms' => [
                    'version' => 'draft-2026-10-19',
                    'sha256' => '179f35ad21c9f98b1d32de3649cc82c686bc4c2b83fb4ac8cb9ce835200bedb5',
                ],
                'privacy' => [
                    'version' => 'draft-2026-10-19',
                    'sha256' => 'bda6eb8dce41b46b573a62f63a99f86618325b291490d81880c276168ad37835',
                ],
            ],
        ],
    ],
    // Keep published entries immutable and retain historical versions.
    'documents' => [
        'terms' => [
            'draft-2026-10-19' => [
                'path' => resource_path('policies/terms/draft-2026-10-19.json'),
                'sha256' => '179f35ad21c9f98b1d32de3649cc82c686bc4c2b83fb4ac8cb9ce835200bedb5',
            ],
        ],
        'privacy' => [
            'draft-2026-10-19' => [
                'path' => resource_path('policies/privacy/draft-2026-10-19.json'),
                'sha256' => 'bda6eb8dce41b46b573a62f63a99f86618325b291490d81880c276168ad37835',
            ],
        ],
    ],
];
