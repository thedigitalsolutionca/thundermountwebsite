<?php

declare(strict_types=1);

use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\CatalogController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\MarketingController;
use App\Controllers\Admin\OrderController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\ShippingController;
use App\Controllers\Admin\TaxController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/admin/login', static fn () => (new AdminAuthController())->loginForm());
    $router->post('/admin/login', static fn () => (new AdminAuthController())->login());
    $router->post('/admin/logout', static fn () => (new AdminAuthController())->logout());

    $router->get('/admin', static fn () => (new DashboardController())->index());

    $router->get('/admin/orders', static fn () => (new OrderController())->index());
    $router->get('/admin/orders/export', static fn () => (new OrderController())->export());
    $router->get('/admin/orders/{number}', static fn (string $number) => (new OrderController())->show($number));
    $router->get('/admin/orders/{number}/invoice', static fn (string $number) => (new OrderController())->invoice($number));
    $router->post('/admin/orders/{id}/status', static fn (string $id) => (new OrderController())->updateStatus($id));
    $router->post('/admin/orders/{id}/tracking', static fn (string $id) => (new OrderController())->tracking($id));
    $router->post('/admin/orders/{id}/note', static fn (string $id) => (new OrderController())->note($id));
    $router->post('/admin/orders/{id}/mark-paid', static fn (string $id) => (new OrderController())->markPaid($id));
    $router->post('/admin/orders/{id}/refund', static fn (string $id) => (new OrderController())->refund($id));

    $router->get('/admin/products', static fn () => (new CatalogController())->products());
    $router->get('/admin/products/create', static fn () => (new CatalogController())->create());
    $router->post('/admin/products', static fn () => (new CatalogController())->save());
    $router->get('/admin/products/{id}/edit', static fn (string $id) => (new CatalogController())->edit($id));
    $router->post('/admin/products/{id}', static fn (string $id) => (new CatalogController())->save($id));
    $router->post('/admin/products/{id}/toggle', static fn (string $id) => (new CatalogController())->toggle($id));
    $router->post('/admin/products/{id}/delete', static fn (string $id) => (new CatalogController())->delete($id));
    $router->post('/admin/products/{id}/images', static fn (string $id) => (new CatalogController())->imageUpload($id));
    $router->post('/admin/products/{id}/images/{imageId}/delete', static fn (string $id, string $imageId) => (new CatalogController())->imageDelete($id, $imageId));
    $router->post('/admin/products/{id}/images/{imageId}/primary', static fn (string $id, string $imageId) => (new CatalogController())->imagePrimary($id, $imageId));

    $router->get('/admin/categories', static fn () => (new CatalogController())->categories());
    $router->post('/admin/categories', static fn () => (new CatalogController())->saveCategory());
    $router->post('/admin/categories/{id}', static fn (string $id) => (new CatalogController())->saveCategory($id));
    $router->post('/admin/categories/{id}/delete', static fn (string $id) => (new CatalogController())->deleteCategory($id));

    $router->get('/admin/inventory', static fn () => (new CatalogController())->inventory());
    $router->post('/admin/inventory/adjust', static fn () => (new CatalogController())->adjustStock());

    $router->get('/admin/customers', static fn () => (new CustomerController())->index());
    $router->get('/admin/customers/{id}', static fn (string $id) => (new CustomerController())->show($id));
    $router->post('/admin/customers/{id}/toggle', static fn (string $id) => (new CustomerController())->toggle($id));

    $router->get('/admin/dealers', static fn () => (new CustomerController())->dealers());
    $router->get('/admin/dealers/tiers', static fn () => (new CustomerController())->tiers());
    $router->post('/admin/dealers/tiers', static fn () => (new CustomerController())->saveTier());
    $router->post('/admin/dealers/tiers/{id}', static fn (string $id) => (new CustomerController())->saveTier($id));
    $router->post('/admin/dealers/tiers/{id}/delete', static fn (string $id) => (new CustomerController())->deleteTier($id));
    $router->get('/admin/dealers/{id}', static fn (string $id) => (new CustomerController())->dealer($id));
    $router->post('/admin/dealers/{id}/approve', static fn (string $id) => (new CustomerController())->approveDealer($id));
    $router->post('/admin/dealers/{id}/reject', static fn (string $id) => (new CustomerController())->rejectDealer($id));

    $router->get('/admin/discounts', static fn () => (new MarketingController())->discounts());
    $router->post('/admin/discounts', static fn () => (new MarketingController())->saveDiscount());
    $router->post('/admin/discounts/{id}', static fn (string $id) => (new MarketingController())->saveDiscount($id));
    $router->post('/admin/discounts/{id}/delete', static fn (string $id) => (new MarketingController())->deleteDiscount($id));

    $router->get('/admin/reviews', static fn () => (new MarketingController())->reviews());
    $router->post('/admin/reviews/{id}/status', static fn (string $id) => (new MarketingController())->reviewStatus($id));
    $router->post('/admin/reviews/{id}/delete', static fn (string $id) => (new MarketingController())->deleteReview($id));

    $router->get('/admin/messages', static fn () => (new MarketingController())->messages());
    $router->post('/admin/messages/{id}/status', static fn (string $id) => (new MarketingController())->messageStatus($id));

    $router->get('/admin/pages', static fn () => (new MarketingController())->pages());
    $router->post('/admin/pages', static fn () => (new MarketingController())->savePage());
    $router->post('/admin/pages/{id}', static fn (string $id) => (new MarketingController())->savePage($id));
    $router->post('/admin/pages/{id}/delete', static fn (string $id) => (new MarketingController())->deletePage($id));

    $router->get('/admin/subscribers', static fn () => (new MarketingController())->subscribers());
    $router->get('/admin/subscribers/export', static fn () => (new MarketingController())->exportSubscribers());

    $router->get('/admin/shipping', static fn () => (new ShippingController())->index());
    $router->post('/admin/shipping/origin', static fn () => (new ShippingController())->saveOrigin());
    $router->post('/admin/shipping/zones', static fn () => (new ShippingController())->saveZone());
    $router->post('/admin/shipping/zones/{id}', static fn (string $id) => (new ShippingController())->saveZone($id));
    $router->post('/admin/shipping/zones/{id}/delete', static fn (string $id) => (new ShippingController())->deleteZone($id));
    $router->post('/admin/shipping/methods', static fn () => (new ShippingController())->saveMethod());
    $router->post('/admin/shipping/methods/{id}', static fn (string $id) => (new ShippingController())->saveMethod($id));
    $router->post('/admin/shipping/methods/{id}/delete', static fn (string $id) => (new ShippingController())->deleteMethod($id));

    $router->get('/admin/tax', static fn () => (new TaxController())->index());
    $router->post('/admin/tax/settings', static fn () => (new TaxController())->saveSettings());
    $router->post('/admin/tax', static fn () => (new TaxController())->saveRate());
    $router->post('/admin/tax/{id}', static fn (string $id) => (new TaxController())->saveRate($id));
    $router->post('/admin/tax/{id}/delete', static fn (string $id) => (new TaxController())->deleteRate($id));

    $router->get('/admin/settings', static fn () => (new SettingsController())->index());
    $router->post('/admin/settings', static fn () => (new SettingsController())->save());
    $router->post('/admin/settings/email/test', static fn () => (new SettingsController())->testEmail());
    $router->post('/admin/settings/paypal/test', static fn () => (new SettingsController())->testPayPal());
    $router->post('/admin/settings/users', static fn () => (new SettingsController())->createAdmin());
};
