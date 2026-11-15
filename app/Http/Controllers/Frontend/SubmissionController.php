<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\Inquiry;
use App\Models\Post;
use App\Models\Service;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    private function spam(Request $r): void
    {
        $r->validate(['website' => 'prohibited', '_form_token' => 'required|string', 'consent' => 'accepted']);
        try {
            $time = (int) Crypt::decryptString($r->input('_form_token'));
        } catch (\Throwable$e) {
            throw ValidationException::withMessages(['message' => 'Please reload the form and try again.']);
        }if ($time > now()->timestamp - 2 || $time < now()->timestamp - 7200) {
            throw ValidationException::withMessages(['message' => 'Please reload the form, complete it, and try again.']);
        }
    }

    public function inquiry(Request $r)
    {
        $this->spam($r);
        $fields = FormField::where('enabled', true)->orderBy('sort_order')->get();
        $rules = [];
        foreach ($fields as $f) {
            $rule = [$f->required ? 'required' : 'nullable', 'string', $f->type === 'textarea' ? 'max:10000' : 'max:1000'];
            if ($f->type === 'email') {
                $rule[] = 'email';
            }if ($f->type === 'select') {
                $rule[] = Rule::in($f->options ?? []);
            }if ($f->type === 'service') {
                $rule[] = Rule::in(Service::published()->get()->filter->isPublic()->pluck('slug')->all());
            }$rules['fields.'.$f->key] = $rule;
        }$data = $r->validate($rules);
        $inquiry = DB::transaction(function () use ($fields, $data) {
            $i = Inquiry::create(['reference' => (string) Str::uuid(), 'status' => 'new', 'consented_at' => now()]);
            foreach ($fields as $f) {
                if (filled($data['fields'][$f->key] ?? null)) {
                    $i->values()->create(['key' => $f->key, 'label' => $f->label, 'value' => $data['fields'][$f->key]]);
                }
            }

            return $i;
        });
        $recipient = Cms::setting('notification_email');
        if (config('mail.default') === 'smtp' && filled(config('mail.mailers.smtp.host')) && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw('A new website inquiry is available in the protected admin panel. Reference: '.$inquiry->reference, fn ($m) => $m->to($recipient)->subject('New Amalez PackTrade inquiry'));
            } catch (\Throwable$e) {
                Log::warning('Inquiry stored; optional SMTP notification failed.');
            }
        }

        return redirect()->to('/contact')->with('inquiry_reference', $inquiry->reference);
    }

    public function comment(Request $r, Post $post)
    {
        abort_unless($post->isPublic() && $post->comments_enabled, 404);
        $this->spam($r);
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255', 'body' => 'required|string|min:5|max:3000']);
        $post->comments()->create($data + ['status' => 'pending']);

        return redirect()->to($post->url.'#comments')->with('comment_status', 'Your comment was received and is awaiting moderation.');
    }
}
