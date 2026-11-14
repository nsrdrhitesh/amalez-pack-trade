<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Service;
use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', NavigationMenu::class);

        return view('admin.cms.menus', ['menus' => NavigationMenu::with('items')->get(), 'pages' => Page::orderBy('title')->get(), 'services' => Service::orderBy('title')->get()]);
    }

    public function storeMenu(Request $r)
    {
        Gate::authorize('create', NavigationMenu::class);
        NavigationMenu::create($r->validate(['name' => 'required|string|max:120', 'location' => 'required|regex:/^[a-z][a-z0-9_-]*$/|max:30|unique:navigation_menus,location']));
        Cms::forget();

        return back()->with('status', 'Menu created.');
    }

    public function saveItem(Request $r, ?NavigationItem $item = null)
    {
        $item ??= new NavigationItem;
        Gate::authorize($item->exists ? 'update' : 'create', $item->exists ? $item : NavigationItem::class);
        $data = $r->validate(['navigation_menu_id' => 'required|exists:navigation_menus,id', 'parent_id' => 'nullable|exists:navigation_items,id', 'label' => 'required|string|max:100', 'url' => 'required|string|max:2048', 'new_tab' => 'nullable|boolean', 'enabled' => 'nullable|boolean', 'sort_order' => 'required|integer|min:0|max:100000']);
        if ($item->exists && $item->navigation_menu_id != (int) $data['navigation_menu_id']) {
            throw ValidationException::withMessages(['navigation_menu_id' => 'Keep an existing item in its original menu.']);
        }
        if (! Cms::safeUrl($data['url'])) {
            throw ValidationException::withMessages(['url' => 'Use a relative site path or a complete HTTPS URL.']);
        }$parent = isset($data['parent_id']) ? NavigationItem::find($data['parent_id']) : null;
        $seen = [];
        while ($parent) {
            if ($parent->navigation_menu_id != (int) $data['navigation_menu_id'] || $parent->id === $item->id || isset($seen[$parent->id])) {
                throw ValidationException::withMessages(['parent_id' => 'Choose a parent in the same menu without a circular relationship.']);
            }$seen[$parent->id] = true;
            $parent = $parent->parent;
        }$data['new_tab'] = $r->boolean('new_tab');
        $data['enabled'] = $r->boolean('enabled');
        $item->fill($data)->save();
        Cms::log('navigation.saved', $item);
        Cms::forget();

        return back()->with('status', 'Navigation saved.');
    }

    public function destroyItem(NavigationItem $item)
    {
        Gate::authorize('delete', $item);
        $item->delete();
        Cms::forget();

        return back()->with('status', 'Menu item and its children deleted.');
    }

    public function updateMenu(Request $r, NavigationMenu $menu)
    {
        Gate::authorize('update', $menu);
        $menu->update($r->validate(['name' => 'required|string|max:120']));
        Cms::log('navigation-menu.updated', $menu);
        Cms::forget();

        return back()->with('status', 'Menu renamed.');
    }

    public function destroyMenu(NavigationMenu $menu)
    {
        Gate::authorize('delete', $menu);
        abort_if(in_array($menu->location, ['header', 'footer'], true), 422, 'Keep the default website menus. Edit their links instead.');
        Cms::log('navigation-menu.deleted', $menu);
        $menu->delete();
        Cms::forget();

        return back()->with('status', 'Custom menu deleted.');
    }
}
