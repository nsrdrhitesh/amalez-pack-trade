<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentSection;
use App\Models\Media;
use App\Models\MediaLink;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoMetadata;
use App\Models\Service;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('viewAny', Media::class);

        return view('admin.cms.media', ['records' => Media::when($r->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('alt', 'like', '%'.$r->string('q').'%')->orWhere('original_name', 'like', '%'.$r->string('q').'%')))->latest()->paginate(24)->withQueryString()]);
    }

    public function store(Request $r)
    {
        Gate::authorize('create', Media::class);
        $media = $this->upload($r);
        Cms::log('media.uploaded', $media);

        return $r->expectsJson() ? response()->json(['url' => $media->url, 'id' => $media->id, 'alt' => $media->alt]) : back()->with('status', 'Image uploaded.');
    }

    private function upload(Request $r, ?Media $media = null): Media
    {
        $data = $r->validate(['image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'], 'alt' => 'required|string|max:255', 'caption' => 'nullable|string|max:2000']);
        $file = $r->file('image');
        $dimensions = getimagesize($file->getRealPath());
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 24000000) {
            throw ValidationException::withMessages(['image' => 'Choose an image below 24 megapixels.']);
        }
        $uuid = (string) Str::uuid();
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$file->getMimeType()];
        $bytes = file_get_contents($file->getRealPath());
        if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
            $image = @imagecreatefromstring($bytes);
            if (! $image) {
                throw ValidationException::withMessages(['image' => 'Image decoding failed.']);
            }$target = imagecreatetruecolor(min(1920, $dimensions[0]), max(1, (int) round($dimensions[1] * min(1, 1920 / $dimensions[0]))));
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), $dimensions[0], $dimensions[1]);
            ob_start();
            imagewebp($target, null, 85);
            $bytes = ob_get_clean();
            $dimensions = [imagesx($target), imagesy($target)];
            imagedestroy($image);
            imagedestroy($target);
            $extension = 'webp';
        }
        $path = 'cms-media/'.$uuid.'.'.$extension;
        if (! Storage::disk('local')->put($path, $bytes)) {
            throw ValidationException::withMessages(['image' => 'The image could not be stored. Please try again.']);
        }
        try {
            $media ??= new Media(['uuid' => (string) Str::uuid()]);
            $media->fill(['path' => $path, 'original_name' => mb_substr($file->getClientOriginalName(), 0, 255), 'mime' => $extension === 'webp' ? 'image/webp' : $file->getMimeType(), 'size' => strlen($bytes), 'width' => $dimensions[0], 'height' => $dimensions[1], 'alt' => $data['alt'], 'caption' => $data['caption'] ?? null, 'uploaded_by' => auth()->id()])->save();
        } catch (\Throwable$e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return $media;
    }

    public function update(Request $r, Media $media)
    {
        Gate::authorize('update', $media);
        if ($r->hasFile('image')) {
            $this->upload($r, $media);
        } else {
            $media->update($r->validate(['alt' => 'required|string|max:255', 'caption' => 'nullable|string|max:2000']));
        }Cms::log('media.updated', $media);
        Cms::forget();

        return back()->with('status', 'Image updated; references retain the same URL.');
    }

    public function destroy(Media $media)
    {
        Gate::authorize('delete', $media);
        $used = MediaLink::where('media_id', $media->id)->exists() || ContentSection::where('media_id', $media->id)->exists() || SeoMetadata::where('image_id', $media->id)->exists();
        foreach ([Page::class, Service::class, Post::class] as $class) {
            $used = $used || $class::where('featured_media_id', $media->id)->orWhere('body', 'like', '%'.$media->uuid.'%')->exists();
        }$used = $used || ContentSection::where('body', 'like', '%'.$media->uuid.'%')->exists() || in_array($media->id, [Cms::setting('branding', [])['logo_media_id'] ?? null, Cms::setting('branding', [])['footer_logo_media_id'] ?? null, Cms::setting('branding', [])['favicon_media_id'] ?? null]);
        abort_if($used, 422, 'This image is referenced by content or branding. Remove those references first.');
        $path = $media->path;
        Cms::log('media.deleted', $media);
        $media->delete();
        Storage::disk('local')->delete($path);

        return back()->with('status', 'Unused image deleted.');
    }
}
