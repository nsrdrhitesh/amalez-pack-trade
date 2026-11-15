<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Validation\ValidationException;

class Seo
{
    public function validate(array $data): array
    {
        if (($data['schema_mode'] ?? 'auto') === 'manual' && empty($data['schema_json'])) {
            throw ValidationException::withMessages(['seo.schema_json' => 'Manual schema mode requires JSON-LD.']);
        }
        foreach (['canonical', 'og_image_url'] as $key) {
            if (! empty($data[$key]) && (! filter_var($data[$key], FILTER_VALIDATE_URL) || parse_url($data[$key], PHP_URL_SCHEME) !== 'https')) {
                throw ValidationException::withMessages(['seo.'.$key => 'Use a complete HTTPS URL.']);
            }
        }
        if (! empty($data['schema_json'])) {
            try {
                $json = json_decode($data['schema_json'], true, 32, JSON_THROW_ON_ERROR);
            } catch (\Throwable$e) {
                throw ValidationException::withMessages(['seo.schema_json' => 'Enter valid JSON-LD.']);
            }if (! is_array($json) || ! isset($json['@context']) || ! in_array($json['@context'], ['https://schema.org', 'https://schema.org/'], true) || (! isset($json['@type']) && ! isset($json['@graph']))) {
                throw ValidationException::withMessages(['seo.schema_json' => 'JSON-LD needs a schema.org context and a type or graph.']);
            }
        }

        return $data;
    }

    public function data($content = null, array $breadcrumbs = []): array
    {
        $data = $content?->seo?->data ?? [];
        $brand = config('cms.brand');
        $url = $content?->url ?? url()->current();
        $meta = ['title' => $data['title'] ?? ($content?->title ? ($content->title.' | '.$brand) : $brand), 'description' => $data['description'] ?? $content?->excerpt ?? '',
            'canonical' => $data['canonical'] ?? $url, 'robots' => ($data['index'] ?? false) && Cms::setting('indexable', false) ? 'index,follow' : 'noindex,nofollow',
            'image' => $content?->seo?->image?->url ?? $content?->featuredMedia?->url ?? asset('images/business/hero.webp')];
        $schema = [];
        if (($data['schema_mode'] ?? 'auto') === 'manual' && ! empty($data['schema_json'])) {
            $schema[] = json_decode($data['schema_json'], true);
        } elseif (($data['schema_mode'] ?? 'auto') !== 'none') {
            $entity = ['@context' => 'https://schema.org', '@type' => $content instanceof Post ? 'BlogPosting' : ($content instanceof Service && $content->confirmed ? 'Service' : 'WebPage'), 'name' => $content?->title ?? $brand, 'url' => $url, 'description' => $meta['description']];
            if ($content instanceof Post) {
                $entity['headline'] = $content->title;
                $entity['datePublished'] = ($content->published_at ?? $content->created_at)->toAtomString();
                $entity['dateModified'] = $content->updated_at->toAtomString();
                $author = $content->author_name ?: $brand.' Editorial';
                $entity['author'] = ['@type' => str_contains($author, 'Editorial') ? 'Organization' : 'Person', 'name' => $author];
                $entity['image'] = url($meta['image']);
                $entity['mainEntityOfPage'] = $url;
                $entity['publisher'] = ['@id' => rtrim(config('company.domain'), '/').'#organization'];
            }
            if ($content instanceof Service && $content->confirmed) {
                $entity['serviceType'] = $content->title;
                $entity['provider'] = ['@id' => rtrim(config('company.domain'), '/').'#organization'];
            }
            $schema[] = $entity;
        }
        $suppliedTypes = collect($schema)->flatMap(fn ($entry) => $entry['@graph'] ?? [$entry])->pluck('@type')->flatten();
        if ($breadcrumbs && ! $suppliedTypes->contains('BreadcrumbList')) {
            $schema[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($b, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $b['title'], 'item' => $b['url']], $breadcrumbs, array_keys($breadcrumbs))];
        }
        $business = Cms::setting('business', []);
        if ($content instanceof Page && $content->slug === 'home' && ($data['schema_mode'] ?? 'auto') === 'auto') {
            $domain = rtrim(config('company.domain'), '/');
            // The display brand and domain are confirmed; legal/contact facts remain opt-in.
            $org = ['@context' => 'https://schema.org', '@type' => 'Organization', '@id' => $domain.'#organization', 'name' => $brand, 'url' => $domain];
            if (! empty($business['legal_name']) && ! empty($business['verified'])) {
                $org['legalName'] = $business['legal_name'];
            }
            foreach (['email', 'telephone'] as $k) {
                if (! empty($business['verified']) && ! empty($business[$k])) {
                    $org[$k] = $business[$k];
                }
            }if (! empty($business['address']) && ! empty($business['local_business_verified']) && ! empty($business['verified'])) {
                $org['@type'] = 'LocalBusiness';
                $org['address'] = $business['address'];
            }$schema[] = $org;
            $schema[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => $domain.'#website', 'name' => $brand, 'url' => $domain, 'publisher' => ['@id' => $domain.'#organization']];
        }
        if ($content && ($data['schema_mode'] ?? 'auto') === 'auto') {
            $faqs = $content->sections->filter(fn ($s) => $s->type === 'faq' && $s->visible() && filled(strip_tags($s->body ?? '')));
            if ($faqs->isNotEmpty()) {
                $schema[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqs->map(fn ($s) => ['@type' => 'Question', 'name' => $s->title, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($s->body))]])->values()->all()];
            }
        }

        return ['meta' => $meta, 'seoData' => $data, 'schema' => $schema];
    }
}
