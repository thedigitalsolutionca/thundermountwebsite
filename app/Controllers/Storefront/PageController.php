<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Services\CatalogService;
use App\Services\ContentService;

final class PageController
{
    public function show(string $slug): string
    {
        $page = (new ContentService())->page($slug);
        if (!$page) {
            Response::html(View::render('errors/404', ['title' => 'Page not found'], 'layouts/storefront'), 404);
        }
        return View::render('storefront/page', [
            'pageTitle' => $page['meta_title'] ?: $page['title'],
            'metaDescription' => $page['meta_description'],
            'canonical' => absolute_url('/page/' . $page['slug']),
            'page' => $page,
        ], 'layouts/storefront');
    }

    public function contactForm(): string
    {
        $user = Auth::user();
        return View::render('storefront/contact', [
            'pageTitle' => 'Contact Us',
            'metaDescription' => 'Talk to Thunder Mounts about fitment, orders or dealer accounts. Call 805-338-6680 or email thunder@thundermounts.com.',
            'prefillSubject' => (string) Request::query('subject', ''),
        ], 'layouts/storefront');
    }

    public function contact(): never
    {
        Csrf::require();
        $data = Request::all();
        $result = (new ContentService())->contact($data);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input($data);
            Response::redirect(url('/contact'));
        }
        clear_input();
        flash('success', 'Thanks — your message is with us. We reply during business hours.');
        Response::redirect(url('/contact'));
    }

    public function newsletter(): never
    {
        Csrf::require();
        $email = (string) Request::input('email', '');
        $result = (new ContentService())->subscribe($email);
        if (Request::wantsJson() || Request::header('X-Requested-With') !== null) {
            Response::json($result, $result['ok'] ? 200 : 422);
        }
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::back(url('/'));
    }

    public function sitemap(): never
    {
        $catalog = CatalogService::instance();
        $urls = [];
        foreach ([['/', '1.0', 'weekly'], ['/shop', '0.9', 'daily'], ['/dealer-program', '0.7', 'monthly'], ['/contact', '0.6', 'monthly'], ['/order-lookup', '0.3', 'yearly']] as $entry) {
            $urls[] = ['loc' => absolute_url($entry[0]), 'priority' => $entry[1], 'changefreq' => $entry[2], 'lastmod' => null];
        }
        foreach ($catalog->categories() as $category) {
            $urls[] = ['loc' => absolute_url('/shop?category=' . urlencode($category['slug'])), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => null];
        }
        foreach ($catalog->search(['limit' => 48, 'page' => 1, 'sort' => 'name-asc'])['items'] as $product) {
            $urls[] = ['loc' => absolute_url($product['url']), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $product['updated_at'] ?? null];
        }
        foreach ((new ContentService())->pages() as $page) {
            $urls[] = ['loc' => absolute_url('/page/' . $page['slug']), 'priority' => '0.4', 'changefreq' => 'monthly', 'lastmod' => $page['updated_at'] ?? null];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $entry) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                $timestamp = strtotime((string) $entry['lastmod']);
                if ($timestamp) {
                    $xml .= '    <lastmod>' . date('Y-m-d', $timestamp) . "</lastmod>\n";
                }
            }
            $xml .= '    <changefreq>' . $entry['changefreq'] . "</changefreq>\n    <priority>" . $entry['priority'] . "</priority>\n  </url>\n";
        }
        $xml .= '</urlset>';
        Response::xml($xml);
    }

    public function robots(): never
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /checkout',
            'Disallow: /cart',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /reset-password',
            'Disallow: /forgot-password',
            'Disallow: /order-lookup',
            'Disallow: /dealer/account',
            '',
            'Sitemap: ' . absolute_url('/sitemap.xml'),
        ];
        Response::text(implode("\n", $lines) . "\n");
    }

    public function appleDomain(): never
    {
        $file = TM_ROOT . '/.well-known/apple-developer-merchantid-domain-association';
        if (!is_file($file)) {
            Response::text('Not found', 404);
        }
        header('Content-Type: text/plain');
        readfile($file);
        exit;
    }
}
