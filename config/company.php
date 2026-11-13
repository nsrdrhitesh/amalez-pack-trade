<?php

return [
    // Confirmed display name; the registered legal identity remains unspecified.
    'name' => env('COMPANY_NAME', 'Amlez Pack Trade'),
    'domain' => 'https://amalezpacktrade.com',
    'legal_name' => null,
    'logo' => null,
    'email' => null,
    'phone' => null,
    'address' => null,
    'social_links' => [],
    // Keep this preview out of search results until client content is approved.
    'indexable' => env('COMPANY_SITE_INDEXABLE', false),
    'products' => [
        ['id' => 'stand-up', 'name' => 'Stand-up pouches', 'family' => 'Flexible packaging', 'number' => '01', 'image' => 'pouches', 'description' => 'A freestanding format with space for a distinctive shelf presence.', 'application' => 'Example application: dry goods', 'detail' => 'Discuss fill weight, dimensions, zipper or tear-notch options, barrier requirements, and compatibility with the filling process. Material suitability and availability require confirmation.', 'confirmed' => false],
        ['id' => 'spout', 'name' => 'Spout pouches', 'family' => 'Flexible packaging', 'number' => '02', 'image' => 'spout', 'description' => 'A flexible format built around controlled pouring and convenient handling.', 'application' => 'Example application: pourable products', 'detail' => 'Consider viscosity, spout position, closure type, filling temperature, and transport conditions. Food-contact and chemical compatibility must be assessed for the actual product.', 'confirmed' => false],
        ['id' => 'labels', 'name' => 'Labels & print', 'family' => 'Labels & printing', 'number' => '03', 'image' => 'labels', 'description' => 'The finishing detail that connects a pack to its product identity.', 'application' => 'Example application: product identification', 'detail' => 'Define substrate, adhesive, container surface, exposure conditions, artwork, and finishing needs. Flexographic printing, variable data, and security features are evaluation options, not confirmed services.', 'confirmed' => false],
        ['id' => 'rolls', 'name' => 'Printed roll stock', 'family' => 'Flexible packaging', 'number' => '04', 'image' => 'rolls', 'description' => 'Continuous packaging material considered around your filling line.', 'application' => 'Example application: automated packing', 'detail' => 'Specify web width, repeat length, roll diameter, core size, winding direction, and sealing conditions. Final structure and supply capability must be confirmed before quotation.', 'confirmed' => false],
    ],
    // Add only client-approved segments. The view has a designed empty state.
    'industries' => [],
    'capabilities' => [],
];
