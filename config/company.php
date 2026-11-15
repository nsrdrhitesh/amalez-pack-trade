<?php

return [
    // Confirmed display name; the registered legal identity remains unspecified.
    'name' => env('COMPANY_DISPLAY_NAME', 'Amalez PackTrade'),
    'domain' => 'https://amalezpacktrade.com',
    'legal_name' => null,
    'logo' => null,
    'email' => null,
    'phone' => null,
    'address' => null,
    'social_links' => [],
    // Keep this preview out of search results until client content is approved.
    'indexable' => env('COMPANY_SITE_INDEXABLE', false),
    // Stateless fallback only; the enabled CMS reads editable database records.
    'products' => array_values(array_map(fn ($row) => [
        'id' => $row[0], 'name' => $row[1], 'family' => 'Packaging & printing',
        'number' => '', 'image' => $row[3], 'image_path' => 'images/business/'.$row[3].'.webp',
        'description' => $row[4], 'application' => 'Discuss your product and artwork requirements',
        'detail' => $row[7], 'confirmed' => true,
    ], array_filter((require database_path('content/catalogue.php'))['services'], fn ($row) => $row[2] === null))),
    // Add only client-approved segments. The view has a designed empty state.
    'industries' => [],
    'capabilities' => [],
];
