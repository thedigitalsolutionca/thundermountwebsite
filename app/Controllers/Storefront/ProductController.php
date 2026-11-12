<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CatalogService;
use App\Services\ContentService;

final class ProductController
{
    public function show(string $slug): string
    {
        $catalog = CatalogService::instance();
        $product = $catalog->find($slug);
        if (!$product) {
            Response::html(View::render('errors/404', ['title' => 'Product not found'], 'layouts/storefront'), 404);
        }
        return View::render('storefront/product', [
            'pageTitle' => $product['name'] . ' — ' . $product['sku'],
            'metaDescription' => mb_substr((string) ($product['short_description'] ?: $product['description']), 0, 300),
            'canonical' => absolute_url($product['url']),
            'ogType' => 'product',
            'ogImage' => absolute_url($catalog->primaryImage($product)),
            'product' => $product,
            'reviews' => $catalog->reviews((int) $product['id']),
            'stats' => $catalog->reviewStats((int) $product['id']),
            'related' => $catalog->related($product, 4),
        ], 'layouts/storefront');
    }

    public function review(string $slug): never
    {
        Csrf::require();
        if (!Auth::check()) {
            flash('error', 'Sign in to review a product.');
            Response::redirect(url('/login?next=' . urlencode('/product/' . $slug)));
        }
        $product = CatalogService::instance()->find($slug);
        if (!$product) {
            Response::redirect(url('/shop'));
        }
        $data = Request::all();
        $data['product_id'] = $product['id'];
        $result = (new ContentService())->submitReview(Auth::user(), $data);
        if (!$result['ok']) {
            Session::set('_errors', ['review' => reset($result['errors']) ?: 'Your review could not be saved.']);
            remember_input(['rating' => $data['rating'] ?? '', 'title' => $data['title'] ?? '', 'body' => $data['body'] ?? '']);
            Response::redirect(url('/product/' . $slug . '#reviews'));
        }
        clear_input();
        flash('success', $result['message'] ?? 'Thank you — your review was submitted for moderation.');
        Response::redirect(url('/product/' . $slug . '#reviews'));
    }

    public function notify(string $slug): never
    {
        Csrf::require();
        $product = CatalogService::instance()->find($slug);
        if (!$product) {
            Response::redirect(url('/shop'));
        }
        $email = (string) Request::input('email', '');
        $result = (new ContentService())->joinWaitlist((int) $product['id'], $email);
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/product/' . $slug . '#notify'));
    }
}
