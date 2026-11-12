<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\CatalogService;

final class ShopController
{
    public function index(): string
    {
        $catalog = CatalogService::instance();
        $result = $catalog->search([
            'category' => (string) Request::query('category', ''),
            'q' => (string) Request::query('q', ''),
            'length' => (string) Request::query('length', ''),
            'availability' => (string) Request::query('availability', ''),
            'min_price' => Request::query('min_price', ''),
            'max_price' => Request::query('max_price', ''),
            'sort' => (string) Request::query('sort', 'featured'),
            'page' => Request::int('page', 1),
            'limit' => 12,
        ]);
        $category = $result['filters']['category'] !== '' ? $catalog->categoryBySlug($result['filters']['category']) : null;
        $title = $category ? $category['name'] : ($result['filters']['q'] !== '' ? 'Search: ' . $result['filters']['q'] : 'Shop All Products');
        $description = $category && $category['description']
            ? $category['description']
            : 'Shop Thunder Mounts opener mounts, track mount sets, Marantec mounts, garage door openers, doors and lighting.';
        return View::render('storefront/shop', [
            'pageTitle' => $title,
            'metaDescription' => mb_substr((string) $description, 0, 300),
            'canonical' => absolute_url('/shop' . query_string()),
            'result' => $result,
            'categories' => $catalog->categories(),
        ], 'layouts/storefront');
    }

    public function suggestions(): never
    {
        $query = (string) Request::query('q', '');
        $items = CatalogService::instance()->suggestions($query, 6);
        Response::json(['items' => $items]);
    }
}
