<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    private function query(Request $r)
    {
        return Inquiry::with(['values', 'assignee'])->when($r->filled('status'), fn ($q) => $q->where('status', $r->string('status')))->when($r->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('reference', 'like', '%'.$r->string('q').'%')->orWhereHas('values', fn ($q) => $q->where('value', 'like', '%'.$r->string('q').'%'))))->latest();
    }

    public function index(Request $r)
    {
        Gate::authorize('viewAny', Inquiry::class);

        return view('admin.cms.inquiries', ['records' => $this->query($r)->paginate(20)->withQueryString()]);
    }

    public function show(Inquiry $inquiry)
    {
        Gate::authorize('view', $inquiry);

        return view('admin.cms.inquiry', ['inquiry' => $inquiry->load('values'), 'users' => User::where('is_admin', true)->whereIn('cms_role', ['owner', 'manager'])->get(['id', 'name'])]);
    }

    public function update(Request $r, Inquiry $inquiry)
    {
        Gate::authorize('update', $inquiry);
        $data = $r->validate(['status' => ['required', Rule::in(['new', 'read', 'follow-up', 'closed'])], 'notes' => 'nullable|string|max:20000', 'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_admin', true)->whereIn('cms_role', ['owner', 'manager'])]]);
        $inquiry->update($data);
        Cms::log('inquiry.updated', $inquiry, ['fields' => array_keys($data)]);

        return back()->with('status', 'Inquiry updated.');
    }

    public function destroy(Inquiry $inquiry)
    {
        Gate::authorize('delete', $inquiry);
        Cms::log('inquiry.deleted', $inquiry);
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')->with('status', 'Inquiry deleted.');
    }

    public function export(Request $r)
    {
        Gate::authorize('viewAny', Inquiry::class);
        Cms::log('inquiries.exported');

        return response()->streamDownload(function () use ($r) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Reference', 'Created UTC', 'Status', 'Field', 'Value']);
            foreach ($this->query($r)->reorder()->lazyById(100) as $i) {
                foreach ($i->values as $v) {
                    $safe = fn ($s) => preg_match('/^[=+@\-\t\r\n]/', (string) $s) ? "'".$s : $s;
                    fputcsv($f, array_map($safe, [$i->reference, $i->created_at->toIso8601String(), $i->status, $v->label, $v->value]));
                }
            }fclose($f);
        }, 'inquiries-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv', 'Cache-Control' => 'no-store']);
    }

    public function fields()
    {
        Gate::authorize('cms-settings');

        return view('admin.cms.fields', ['fields' => FormField::orderBy('sort_order')->get()]);
    }

    public function saveField(Request $r, ?FormField $field = null)
    {
        Gate::authorize('cms-settings');
        $field ??= new FormField;
        $data = $r->validate(['key' => ['required', 'regex:/^[a-z][a-z0-9_]*$/', 'max:60', Rule::unique('form_fields', 'key')->ignore($field->id)], 'label' => 'required|string|max:120', 'type' => ['required', Rule::in(['text', 'email', 'tel', 'textarea', 'select', 'service'])], 'options' => 'nullable|string|max:3000', 'enabled' => 'nullable|boolean', 'required' => 'nullable|boolean', 'sort_order' => 'required|integer|min:0|max:100000']);
        if ($field->exists && in_array($field->key, ['name', 'email', 'message'], true)) {
            abort_unless($data['key'] === $field->key, 422, 'Keep core field keys stable.');
        }$data['enabled'] = $r->boolean('enabled');
        $data['required'] = $r->boolean('required');
        $data['options'] = array_values(array_filter(array_map('trim', explode("\n", $data['options'] ?? ''))));
        $field->fill($data)->save();
        Cms::log('contact-field.saved', $field);

        return back()->with('status', 'Contact field saved.');
    }

    public function deleteField(FormField $field)
    {
        Gate::authorize('cms-settings');
        abort_if(in_array($field->key, ['name', 'email', 'message'], true), 422, 'Disable this core field instead of deleting it.');
        $field->delete();

        return back()->with('status', 'Field deleted; previously submitted values preserved.');
    }
}
