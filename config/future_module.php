<?php

return [
    'tracks' => [
        [
            'name' => 'Route Contract',
            'owner' => 'Platform Core',
            'status' => 'ready',
            'notes' => 'Landing route, dashboard route, and catalog registration are prepared.',
        ],
        [
            'name' => 'Auth Handshake',
            'owner' => 'Shared Login',
            'status' => 'ready',
            'notes' => 'Safe redirect and logout return-to-catalog flow already follow the shared policy.',
        ],
        [
            'name' => 'Navigation Shell',
            'owner' => 'UX System',
            'status' => 'ready',
            'notes' => 'Layouts, popups, toast, and dark mode run from the shared runtime contract.',
        ],
        [
            'name' => 'Domain Launch',
            'owner' => 'Infrastructure',
            'status' => 'queued',
            'notes' => 'Subdomain can be assigned once the next module scope is approved.',
        ],
        [
            'name' => 'Business Slice',
            'owner' => 'Product Owner',
            'status' => 'queued',
            'notes' => 'The next team only needs to replace the placeholder cards with domain-specific features.',
        ],
    ],
];
