<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Database;
use App\Core\View;
use App\Services\CatalogService;

final class HomeController
{
    public function index(): string
    {
        $catalog = CatalogService::instance();
        $reviews = Database::select(
            "SELECT r.*, p.name AS product_name FROM reviews r
             INNER JOIN products p ON p.id = r.product_id
             WHERE r.status = 'approved' AND p.is_active = 1
             ORDER BY r.created_at DESC LIMIT 6"
        );
        return View::render('storefront/home', [
            'pageTitle' => setting('seo.title', 'Thunder Mounts | Garage Door & Opener Mounting Systems'),
            'metaDescription' => setting('seo.description', 'Engineered mounting systems for garage doors and openers. Value, Symmetry, Safety — designed and made in Simi Valley, California.'),
            'featured' => $catalog->featured(8),
            'categories' => $catalog->categories(),
            'reviews' => $reviews,
        ], 'layouts/storefront');
    }
}
