<?php

use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\InquiryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\CmsEnabled;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['admin.enabled', 'auth', 'admin', CmsEnabled::class])->group(function () {
    Route::prefix('cms/{type}')->whereIn('type', ['pages', 'services', 'posts'])->name('cms.')->group(function () {
        Route::get('/', [ContentController::class, 'index'])->name('index');
        Route::get('/create', [ContentController::class, 'create'])->name('create');
        Route::post('/', [ContentController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ContentController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [ContentController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [ContentController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
    Route::get('media', [MediaController::class, 'index'])->name('media.index');
    Route::post('media', [MediaController::class, 'store'])->middleware('throttle:30,1,cms-upload:')->name('media.store');
    Route::put('media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('taxonomy/{type}', [TaxonomyController::class, 'index'])->name('taxonomy.index');
    Route::post('taxonomy/{type}', [TaxonomyController::class, 'store'])->name('taxonomy.store');
    Route::put('taxonomy/{type}/{id}', [TaxonomyController::class, 'update'])->whereNumber('id')->name('taxonomy.update');
    Route::delete('taxonomy/{type}/{id}', [TaxonomyController::class, 'destroy'])->whereNumber('id')->name('taxonomy.destroy');
    Route::get('inquiries/export', [InquiryController::class, 'export'])->name('inquiries.export');
    Route::get('inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::get('inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
    Route::put('inquiries/{inquiry}', [InquiryController::class, 'update'])->name('inquiries.update');
    Route::delete('inquiries/{inquiry}', [InquiryController::class, 'destroy'])->name('inquiries.destroy');
    Route::get('contact-fields', [InquiryController::class, 'fields'])->name('fields.index');
    Route::post('contact-fields', [InquiryController::class, 'saveField'])->name('fields.store');
    Route::put('contact-fields/{field}', [InquiryController::class, 'saveField'])->name('fields.update');
    Route::delete('contact-fields/{field}', [InquiryController::class, 'deleteField'])->name('fields.destroy');
    Route::get('comments', [CommentController::class, 'index'])->name('comments.index');
    Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
    Route::post('menus', [MenuController::class, 'storeMenu'])->name('menus.store');
    Route::put('menus/{menu}', [MenuController::class, 'updateMenu'])->name('menus.update');
    Route::delete('menus/{menu}', [MenuController::class, 'destroyMenu'])->name('menus.destroy');
    Route::post('menu-items', [MenuController::class, 'saveItem'])->name('menu-items.store');
    Route::put('menu-items/{item}', [MenuController::class, 'saveItem'])->name('menu-items.update');
    Route::delete('menu-items/{item}', [MenuController::class, 'destroyItem'])->name('menu-items.destroy');
    Route::get('website-settings', [SettingsController::class, 'edit'])->name('website-settings.edit');
    Route::put('website-settings', [SettingsController::class, 'update'])->name('website-settings.update');
    Route::get('seo', [SettingsController::class, 'seo'])->name('seo.index');
    Route::get('schema', [SettingsController::class, 'seo'])->name('schema.index');
    Route::get('activity', [SettingsController::class, 'activity'])->name('activity.index');
    Route::get('cms-users', [UserController::class, 'index'])->name('users.index');
    Route::post('cms-users', [UserController::class, 'save'])->name('users.store');
    Route::put('cms-users/{user}', [UserController::class, 'save'])->name('users.update');
    Route::delete('cms-users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
