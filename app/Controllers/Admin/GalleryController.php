<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\GalleryService;

final class GalleryController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): string
    {
        $service = new GalleryService();
        $type = trim((string) Request::query('type', ''));
        $status = trim((string) Request::query('status', ''));
        $q = trim((string) Request::query('q', ''));
        $editId = Request::int('edit', 0);

        $filters = [
            'type' => in_array($type, ['image', 'video'], true) ? $type : '',
            'status' => in_array($status, ['active', 'hidden'], true) ? $status : '',
            'q' => $q,
        ];

        return View::render('admin/gallery', [
            'pageTitle' => 'Gallery & Portfolio',
            'items' => $service->adminItems($filters),
            'counts' => $service->counts(),
            'filters' => $filters,
            'editItem' => $editId > 0 ? $service->find($editId) : null,
        ], 'layouts/admin');
    }

    public function save(?string $id = null): never
    {
        Csrf::require();
        $service = new GalleryService();
        $input = Request::all();
        $files = [
            'image_file' => Request::files('image_file'),
            'video_file' => Request::files('video_file'),
            'poster_file' => Request::files('poster_file'),
        ];

        $itemId = $id !== null ? (int) $id : null;
        $result = $service->save($input, $files, $itemId);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            unset($input['_token']);
            remember_input($input);
            Response::redirect(url('/admin/gallery' . ($itemId !== null ? '?edit=' . $itemId : '')));
        }

        clear_input();
        Logger::activity(
            Auth::id(),
            $itemId !== null ? 'gallery.updated' : 'gallery.created',
            'gallery_item',
            (string) $result['id'],
            trim((string) ($input['title'] ?? ''))
        );
        flash('success', $itemId !== null ? 'Gallery item updated.' : 'Media item added to Portfolio & Gallery.');
        Response::redirect(url('/admin/gallery'));
    }

    public function toggle(string $id): never
    {
        Csrf::require();
        $ok = (new GalleryService())->toggle((int) $id);
        flash($ok ? 'success' : 'error', $ok ? 'Gallery item visibility updated.' : 'Gallery item not found.');
        Response::redirect(url('/admin/gallery'));
    }

    public function move(string $id): never
    {
        Csrf::require();
        $direction = (string) Request::input('direction', 'up') === 'down' ? 'down' : 'up';
        (new GalleryService())->move((int) $id, $direction);
        Response::redirect(url('/admin/gallery'));
    }

    public function delete(string $id): never
    {
        Csrf::require();
        $ok = (new GalleryService())->delete((int) $id);
        if ($ok) {
            Logger::activity(Auth::id(), 'gallery.deleted', 'gallery_item', (string) (int) $id);
        }
        flash($ok ? 'success' : 'error', $ok ? 'Gallery item deleted.' : 'Gallery item not found.');
        Response::redirect(url('/admin/gallery'));
    }
}
