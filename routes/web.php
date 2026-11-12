<?php

declare(strict_types=1);

use App\Controllers\Storefront\AccountController;
use App\Controllers\Storefront\AuthController;
use App\Controllers\Storefront\CartController;
use App\Controllers\Storefront\CheckoutController;
use App\Controllers\Storefront\DealerController;
use App\Controllers\Storefront\HomeController;
use App\Controllers\Storefront\PageController;
use App\Controllers\Storefront\ProductController;
use App\Controllers\Storefront\ShopController;
use App\Controllers\Storefront\WebhookController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', static fn () => (new HomeController())->index());
    $router->get('/shop', static fn () => (new ShopController())->index());
    $router->get('/search', static fn () => (new ShopController())->index());
    $router->get('/search/suggest', static fn () => (new ShopController())->suggestions());

    $router->get('/product/{slug}', static fn (string $slug) => (new ProductController())->show($slug));
    $router->post('/product/{slug}/review', static fn (string $slug) => (new ProductController())->review($slug));
    $router->post('/product/{slug}/notify', static fn (string $slug) => (new ProductController())->notify($slug));

    $router->get('/cart', static fn () => (new CartController())->show());
    $router->post('/cart/add', static fn () => (new CartController())->add());
    $router->post('/cart/update', static fn () => (new CartController())->update());
    $router->post('/cart/remove', static fn () => (new CartController())->remove());
    $router->post('/cart/discount', static fn () => (new CartController())->discount());
    $router->post('/cart/discount/remove', static fn () => (new CartController())->removeDiscount());
    $router->get('/cart/drawer', static fn () => (new CartController())->drawer());

    $router->get('/checkout', static fn () => (new CheckoutController())->index());
    $router->post('/checkout/shipping', static fn () => (new CheckoutController())->shipping());
    $router->post('/checkout/paypal/create', static fn () => (new CheckoutController())->paypalCreate());
    $router->post('/checkout/paypal/capture', static fn () => (new CheckoutController())->paypalCapture());
    $router->get('/checkout/paypal/return', static fn () => (new CheckoutController())->paypalReturn());
    $router->post('/checkout/manual', static fn () => (new CheckoutController())->manual());
    $router->get('/checkout/success', static fn () => (new CheckoutController())->success());
    $router->get('/checkout/cancel', static fn () => (new CheckoutController())->cancel());
    $router->get('/order-lookup', static fn () => (new CheckoutController())->lookupForm());
    $router->post('/order-lookup', static fn () => (new CheckoutController())->lookup());

    $router->get('/login', static fn () => (new AuthController())->loginForm());
    $router->post('/login', static fn () => (new AuthController())->login());
    $router->get('/register', static fn () => (new AuthController())->registerForm());
    $router->post('/register', static fn () => (new AuthController())->register());
    $router->post('/logout', static fn () => (new AuthController())->logout());
    $router->get('/forgot-password', static fn () => (new AuthController())->forgotForm());
    $router->post('/forgot-password', static fn () => (new AuthController())->forgot());
    $router->get('/reset-password', static fn () => (new AuthController())->resetForm());
    $router->post('/reset-password', static fn () => (new AuthController())->reset());

    $router->get('/account', static fn () => (new AccountController())->dashboard());
    $router->get('/account/orders', static fn () => (new AccountController())->orders());
    $router->get('/account/orders/{number}', static fn (string $number) => (new AccountController())->order($number));
    $router->post('/account/orders/{number}/reorder', static fn (string $number) => (new AccountController())->reorder($number));
    $router->get('/account/addresses', static fn () => (new AccountController())->addresses());
    $router->post('/account/addresses', static fn () => (new AccountController())->saveAddress());
    $router->post('/account/addresses/{id}/delete', static fn (string $id) => (new AccountController())->deleteAddress((int) $id));
    $router->post('/account/addresses/{id}/default', static fn (string $id) => (new AccountController())->defaultAddress((int) $id));
    $router->get('/account/profile', static fn () => (new AccountController())->profile());
    $router->post('/account/profile', static fn () => (new AccountController())->updateProfile());
    $router->post('/account/password', static fn () => (new AccountController())->updatePassword());

    $router->get('/dealer-program', static fn () => (new DealerController())->program());
    $router->get('/dealer/login', static fn () => (new DealerController())->loginForm());
    $router->get('/dealer/apply', static fn () => (new DealerController())->applyForm());
    $router->post('/dealer/apply', static fn () => (new DealerController())->apply());
    $router->get('/dealer/account', static fn () => (new DealerController())->dashboard());
    $router->get('/dealer/orders', static fn () => (new DealerController())->orders());

    $router->get('/page/{slug}', static fn (string $slug) => (new PageController())->show($slug));
    $router->get('/contact', static fn () => (new PageController())->contactForm());
    $router->post('/contact', static fn () => (new PageController())->contact());
    $router->post('/newsletter', static fn () => (new PageController())->newsletter());
    $router->get('/sitemap.xml', static fn () => (new PageController())->sitemap());
    $router->get('/robots.txt', static fn () => (new PageController())->robots());
    $router->get('/.well-known/apple-developer-merchantid-domain-association', static fn () => (new PageController())->appleDomain());

    $router->post('/webhooks/paypal', static fn () => (new WebhookController())->paypal());
};
