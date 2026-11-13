# Research and original assets

## Reference analysis

The actual homepages were opened and read during implementation on November 7, 2026:

- https://www.vivaanflexipack.com/ — pouch formats, laminated roll stock, customization inputs, and a quote journey focused on product and format requirements. This informed editable format groupings and enquiry questions, without adopting its business-specific figures, locations, equipment, or claims.
- https://www.swapnalilabels.com/ — label categories, material options, print/finish considerations, and suitability by use. This informed the distinction between packaging structure, label substrate/adhesive, and printing. Its equipment, certifications, customers, and specifications were not attributed to Amlex.
- https://www.shwetaprintpack.me/ — broad information sequence: introduction, offer, strengths, product imagery, about, and contact. Only general content organization was considered; its layout, styles, copy, photography, statistics, and identity were not reused.

No reference-site images, logos, testimonials, paragraphs, or company-specific business claims were copied. Product and process examples are illustrative. No customer, certification, capacity, sustainability, safety, or geographical claim is asserted without confirmation.

## Identity concept

The original SVG mark combines a carton outline and folded edge with a contrasting layered line suggesting print sheets. The source wordmark concept is `public/images/branding/amlex-logo-concept.svg`; a square adaptation is `public/images/branding/mark.svg`. Both are working concepts, not an official logo or a trademark-clearance assertion. The live wordmark uses configurable text, so a business name change does not require recreating a raster asset.

## Image provenance

Two original illustrative images were created with the **built-in ImageGen tool**, rather than obtained from the reference websites. They portray generic unbranded packaging objects. They are not actual Amlex products, completed customer work, manufacturing equipment, or company facilities. Page copy and captions distinguish them as concepts.

Original sources are retained at:

- `resources/images/source/packaging.png`
- `resources/images/source/materials.png`

Optimized delivery variants are under `public/images/hero/`, `public/images/products/`, and `public/images/gallery/`. The reproducible GD script resamples and crops these studies to the declared image dimensions and outputs WebP at quality 82. No image service is needed at runtime.

### Final prompt — packaging study

> Use case: product-mockup. Create an ultra premium editorial still-life photograph for an industrial packaging and printing corporate website. Landscape 3:2 composition, no typography, no logos, no people, no machinery. A sophisticated arranged family of unbranded flexible packaging on a warm ivory matte studio surface: one tall charcoal black flat-bottom coffee pouch, one warm burnt orange stand-up zipper pouch, one small cream spout pouch, an ivory gusseted pouch, and a roll of blank cream adhesive labels resting on its side. Subtle seam and material details, accurately formed packaging. Staggered rectilinear warm beige plinths, objects arranged on right and center, dramatic natural directional light from upper left, long soft shadows, warm neutral backdrop. Refined high-end commercial product photography, beautiful tactile matte paper and laminated film, realistic folds, 85mm lens, extremely clean art direction. Keep every object fully in frame with breathing room. These are illustrative generic packaging concepts, not real company products. Save generated image locally and return file path if available.

### Final prompt — materials study

> Use case: product-mockup. Original commercial packaging still-life photograph, wide landscape 3:2. A large cylindrical roll of ivory flexible laminated packaging film on a cardboard core at left, two smaller rolls of matte burnt-orange and warm-white self-adhesive rectangular labels on cardboard cores at right, several blank cream and orange label samples laid flat in the foreground, and a fan of unprinted kraft paper and ivory film material swatches in the background. Restrained industrial design art direction on a warm pale grey studio worktable with charcoal backdrop, natural directional light, precise material texture, elevated editorial product photography. No typography, no logos, no printed text, no machines, no people. Entire objects visible. A generic illustrative material study, not a real factory or company-owned product. Return local saved file path.
