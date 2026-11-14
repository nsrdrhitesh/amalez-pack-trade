<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    public function index(Request $r)
    {
        Gate::authorize('viewAny', Comment::class);

        return view('admin.cms.comments', ['records' => Comment::with('post')->when($r->filled('status'), fn ($q) => $q->where('status', $r->string('status')))->when($r->filled('q'), fn ($q) => $q->where('body', 'like', '%'.$r->string('q').'%'))->latest()->paginate(20)->withQueryString()]);
    }

    public function update(Request $r, Comment $comment)
    {
        Gate::authorize('update', $comment);
        $comment->update($r->validate(['status' => ['required', Rule::in(['pending', 'approved', 'rejected'])]]));
        Cms::log('comment.moderated', $comment);

        return back()->with('status', 'Moderation saved.');
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);
        Cms::log('comment.deleted', $comment);
        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }
}
