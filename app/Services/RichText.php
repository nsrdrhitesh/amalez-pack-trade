<?php

namespace App\Services;

use App\Models\Media;

class RichText
{
    public function clean(?string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,div,br,strong,b,em,i,u,s,del,blockquote,pre,code,h2,h3,h4,ul,ol,li,a[href|title],img[src|alt|width|height],figure,figcaption');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('Attr.EnableID', false);
        $def = $config->getHTMLDefinition(true);
        $def->addElement('figure', 'Block', 'Flow', 'Common');
        $def->addElement('figcaption', 'Block', 'Flow', 'Common');
        $clean = (new \HTMLPurifier($config))->purify($html ?? '');
        $dom = new \DOMDocument;
        $old = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$clean.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($old);
        foreach (iterator_to_array($dom->getElementsByTagName('img')) as $img) {
            $src = $img->getAttribute('src');
            $path = parse_url($src, PHP_URL_PATH);
            $host = parse_url($src, PHP_URL_HOST);
            if (($host && $host !== parse_url(config('app.url'), PHP_URL_HOST)) || ! preg_match('~^/(media/[a-f0-9-]{36}|images/[a-zA-Z0-9/_.-]+)$~', $path ?? '')) {
                $img->parentNode->removeChild($img);

                continue;
            }
            if (str_starts_with($path, '/media/')) {
                $media = Media::where('uuid', substr($path, 7))->first();
                if (! $media) {
                    $img->parentNode->removeChild($img);

                    continue;
                }
                $img->setAttribute('alt', $media->alt);
                $img->setAttribute('width', (string) $media->width);
                $img->setAttribute('height', (string) $media->height);
            } elseif (! filled($img->getAttribute('alt'))) {
                $img->parentNode->removeChild($img);

                continue;
            }
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('decoding', 'async');
        }
        $wrapper = $dom->getElementsByTagName('div')->item(0);
        $output = '';
        if ($wrapper) {
            foreach ($wrapper->childNodes as $node) {
                $output .= $dom->saveHTML($node);
            }
        }

        return $output;
    }
}
