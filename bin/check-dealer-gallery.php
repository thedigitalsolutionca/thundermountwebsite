<?php

declare(strict_types=1);

/**
 * Local/test-only regression suite for Dealer Program Controls and Gallery & Portfolio Management.
 * Runs on an isolated in-memory SQLite database with zero external network calls or disk mutations.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Admin\SettingsController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Services\CatalogService;
use App\Services\ChatService;
use App\Services\ContentService;
use App\Services\DealerService;
use App\Services\DiscountService;
use App\Services\GalleryService;

if (PHP_SAPI !== 'cli' || !in_array(Config::get('app.env'), ['local', 'test', 'testing'], true)) {
    fwrite(STDERR, "Run only with APP_ENV=local or test.\n");
    exit(1);
}

Database::init(['driver' => 'sqlite', 'sqlite_path' => ':memory:']);
Settings::forget();
$sql = (string) file_get_contents(TM_ROOT . '/database/schema.sqlite.sql');
$sql = (string) preg_replace('/^--.*$/m', '', $sql);
$sql = (string) preg_replace('/^SET .*;$/m', '', $sql);
Database::pdo()->exec($sql);

try {

$seed = require TM_ROOT . '/database/seed.php';
foreach ($seed['settings'] as $s) {
    if ($s[0] === 'gallery.seeded') {
        continue; // Let GalleryService::ensureReady() exercise zero-migration seeding
    }
    Settings::set($s[0], $s[1], $s[2] ?? 'general');
}
foreach ($seed['pages'] as $p) {
    Database::insert('pages', array_merge($p, ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()]));
}
Database::insert('dealer_tiers', [
    'name' => 'Gold Installer',
    'discount_percent' => '20.00',
    'min_order_total' => '0.00',
    'notes' => 'Trade tier',
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

function assert_ok(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException('FAILED: ' . $label);
    }
    echo 'PASS ', $label, PHP_EOL;
}

$resetAuth = static function (?int $userId): void {
    $ref = new ReflectionClass(Auth::class);
    $p = $ref->getProperty('user');
    $p->setValue(null, null);
    if ($userId === null) {
        Session::forget('user_id');
    } else {
        Session::set('user_id', $userId);
    }
};

// 1. Seed admin, approved dealer, and sample product + dealer-only coupon
$adminId = Database::insert('users', [
    'email' => 'admin@example.com',
    'password_hash' => password_hash('AdminPass123!', PASSWORD_DEFAULT),
    'first_name' => 'Store',
    'last_name' => 'Admin',
    'role' => 'admin',
    'status' => 'active',
    'created_at' => now(),
    'updated_at' => now(),
]);
$tier = Database::first('SELECT * FROM dealer_tiers ORDER BY discount_percent DESC LIMIT 1');
assert_ok($tier !== null, 'Seeded dealer tiers available');

$dealerUserId = Database::insert('users', [
    'email' => 'pro@installer.example',
    'password_hash' => password_hash('DealerPass123!', PASSWORD_DEFAULT),
    'first_name' => 'Alex',
    'last_name' => 'Installer',
    'role' => 'dealer',
    'status' => 'active',
    'created_at' => now(),
    'updated_at' => now(),
]);
$dealerId = Database::insert('dealers', [
    'user_id' => $dealerUserId,
    'tier_id' => (int) $tier['id'],
    'company' => 'Pacific Overhead Doors',
    'contact_name' => 'Alex Installer',
    'email' => 'pro@installer.example',
    'phone' => '555-010-2020',
    'business_type' => 'Garage Door Installer',
    'status' => 'approved',
    'applied_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);
Database::execute('UPDATE users SET dealer_id = ? WHERE id = ?', [$dealerId, $dealerUserId]);

$productId = Database::insert('products', [
    'sku' => 'TM-TEST-14',
    'name' => '14" Heavy Duty Track Mount',
    'slug' => '14-heavy-duty-track-mount',
    'price' => '100.00',
    'dealer_price' => '75.00',
    'stock_qty' => 25,
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);
$product = Database::first('SELECT * FROM products WHERE id = ?', [$productId]);

Database::insert('discounts', [
    'code' => 'DEALER10',
    'description' => 'Dealer bonus 10%',
    'type' => 'percent',
    'value' => '10.00',
    'dealer_only' => 1,
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

// 2. Verify active Dealer Program behavior
assert_ok(dealer_program_enabled() && dealer_pages_enabled() && dealer_applications_enabled() && dealer_pricing_enabled(), 'Dealer helpers enabled by default');
$resetAuth($dealerUserId);
assert_ok(Auth::isDealer() === true, 'Approved dealer recognized when program active');

$catalog = new CatalogService();
$discounts = new DiscountService();
$content = new ContentService();

$priceActive = $catalog->priceFor($product);
assert_ok($priceActive['unit_cents'] === 7500 && $priceActive['is_dealer'] === true, 'Dealer trade price applied when active');
$couponActive = $discounts->validate('DEALER10', 15000, 'pro@installer.example', $dealerUserId);
assert_ok($couponActive['ok'] === true, 'Dealer-only coupon accepted when active');

$faqPageOpen = $content->page('faq');
assert_ok($faqPageOpen !== null && str_contains((string) $faqPageOpen['content'], 'Do you offer dealer pricing?'), 'Dealer FAQ visible when program active');

$decorated = $catalog->decorate($product);
$homeOpen = View::partial('storefront/home', [
    'featured' => [$decorated],
    'bestSellers' => [$decorated],
    'categories' => [],
    'reviews' => [],
]);
assert_ok(str_contains($homeOpen, '/dealer/apply'), 'Home page shows dealer program CTA when active');

// 3. Turn OFF master dealer switch (`dealer.enabled = 0`) and verify complete suppression
Settings::set('dealer.enabled', '0', 'dealer');
$resetAuth($dealerUserId);

assert_ok(!dealer_program_enabled() && !dealer_pages_enabled() && !dealer_applications_enabled() && !dealer_pricing_enabled(), 'Master dealer switch disables all sub-flags');
assert_ok(Auth::isDealer() === false, 'Active dealer session privileges suspended while program closed');

$priceClosed = $catalog->priceFor($product);
assert_ok($priceClosed['unit_cents'] === 10000 && $priceClosed['is_dealer'] === false, 'Retail pricing enforced when dealer program closed');
$couponClosed = $discounts->validate('DEALER10', 15000, 'pro@installer.example', $dealerUserId);
assert_ok($couponClosed['ok'] === false, 'Dealer-only coupon rejected when dealer program closed');

$faqPageClosed = $content->page('faq');
assert_ok($faqPageClosed !== null && !str_contains((string) $faqPageClosed['content'], 'Do you offer dealer pricing?'), 'CMS FAQ page strips dealer block when program closed');
$aboutPageClosed = $content->page('about');
assert_ok($aboutPageClosed !== null && !str_contains((string) $aboutPageClosed['content'], 'dealer program'), 'CMS About page strips dealer mention when program closed');
assert_ok($content->page('dealer-program') === null, 'CMS dealer-program page returns null when closed');

$decoratedClosed = $catalog->decorate($product);
$homeClosed = View::partial('storefront/home', [
    'featured' => [$decoratedClosed],
    'bestSellers' => [$decoratedClosed],
    'categories' => [],
    'reviews' => [],
]);
assert_ok(!str_contains($homeClosed, '/dealer-program') && !str_contains($homeClosed, '/dealer/apply') && !str_contains($homeClosed, '/dealer/login'), 'Home page hides all dealer CTAs when closed');

$salesSheetClosed = View::partial('storefront/resources/sales-sheet');
assert_ok(!str_contains($salesSheetClosed, '/dealer/apply'), 'Sales sheet hides dealer CTA when closed');

$contactClosed = View::partial('storefront/contact', ['prefillSubject' => '']);
assert_ok(!str_contains($contactClosed, '/dealer/apply'), 'Contact page hides dealer CTA when closed');

$sidebarClosed = View::partial('storefront/account/sidebar', ['active' => 'dashboard']);
assert_ok(!str_contains($sidebarClosed, '/dealer/dashboard') && !str_contains($sidebarClosed, '/dealer/apply'), 'Account sidebar hides dealer link when closed');

$dealerService = new DealerService();
$blockedApply = $dealerService->apply($dealerUserId, [
    'company' => 'Blocked Co',
    'contact_name' => 'Test',
    'email' => 'test@example.com',
    'phone' => '555-111-2222',
    'business_type' => 'Installer',
    'address1' => '1 Main St',
    'city' => 'Austin',
    'state' => 'TX',
    'postcode' => '78701',
]);
assert_ok($blockedApply['ok'] === false, 'DealerService::apply rejects applications when closed');

Settings::set('chat.provider', 'website', 'chat');
$chatClosed = (new ChatService())->answer('How do I join the dealer program?');
$chatUrls = array_column($chatClosed['sources'] ?? [], 'url');
assert_ok(!in_array(url('/dealer-program'), $chatUrls, true), 'Website assistant omits dealer program source when closed');

// 4. Test granular switches (`applications_enabled = 0` while `enabled = 1`)
Settings::set('dealer.enabled', '1', 'dealer');
Settings::set('dealer.applications_enabled', '0', 'dealer');
Settings::set('dealer.pages_enabled', '1', 'dealer');
Settings::set('dealer.pricing_enabled', '1', 'dealer');
$resetAuth($dealerUserId);

assert_ok(dealer_program_enabled() && dealer_pages_enabled() && dealer_pricing_enabled() && !dealer_applications_enabled(), 'Granular applications pause keeps existing dealer pricing active');
assert_ok($catalog->priceFor($product)['unit_cents'] === 7500, 'Approved dealer retains trade pricing while new applications are paused');
$programPausedHtml = View::partial('storefront/dealer/program', ['tiers' => $dealerService->tiers(true)]);
assert_ok(!str_contains($programPausedHtml, 'href="' . url('/dealer/apply') . '"'), 'Dealer program landing page hides Apply button when applications paused');

// Restore granular switches via SettingsController::applyGroup('dealer', ...)
$refSettings = new ReflectionClass(SettingsController::class);
$settingsController = $refSettings->newInstanceWithoutConstructor();
$applyGroup = $refSettings->getMethod('applyGroup');
$errors = $applyGroup->invoke($settingsController, 'dealer', [
    'dealer.enabled' => '1',
    'dealer.pages_enabled' => '1',
    'dealer.applications_enabled' => '1',
    'dealer.pricing_enabled' => '1',
    'dealer.closed_notice' => 'Custom notice text.',
]);
assert_ok($errors === [] && dealer_applications_enabled(), 'SettingsController dealer tab persists all flags cleanly');

// 5. Test individual & bulk dealer deactivation and reactivation
$deact = $dealerService->deactivate($dealerId, $adminId, 'Seasonal pause');
assert_ok($deact['ok'] === true, 'Individual dealer deactivation succeeds');
$dealerRow = $dealerService->find($dealerId);
assert_ok($dealerRow !== null && $dealerRow['status'] === 'inactive' && (int) $dealerRow['tier_id'] === (int) $tier['id'], 'Deactivated dealer keeps tier assignment and history');

$resetAuth($dealerUserId);
assert_ok(Auth::isDealer() === false, 'Deactivated dealer loses active session dealer status immediately');
assert_ok($catalog->priceFor($product)['unit_cents'] === 10000, 'Deactivated dealer receives retail price');

$react = $dealerService->reactivate($dealerId, $adminId, null, 'Restored for new project');
assert_ok($react['ok'] === true, 'Dealer reactivation succeeds');
$resetAuth($dealerUserId);
assert_ok(Auth::isDealer() === true && $catalog->priceFor($product)['unit_cents'] === 7500, 'Reactivated dealer regains trade pricing');

$bulkCount = $dealerService->deactivateAllApproved($adminId, 'End-of-year audit');
assert_ok($bulkCount === 1 && $dealerService->counts()['inactive'] === 1 && $dealerService->counts()['approved'] === 0, 'Bulk dealer deactivation suspends all approved dealers');
$dealerService->reactivate($dealerId, $adminId);

// 6. Test Gallery & Portfolio Management (Photos + Uploaded Videos + YouTube/Vimeo URLs)
$gallery = new GalleryService();
$seeded = $gallery->publicItems();
assert_ok(count($seeded) === 6, 'GalleryService auto-creates schema and seeds 6 default portfolio photos');

// Verify YouTube, Shorts, youtu.be, Vimeo, and insecure URL parsing
$ytWatch = GalleryService::parseVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
assert_ok($ytWatch !== null && $ytWatch['video_source'] === 'youtube' && $ytWatch['embed_url'] === 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', 'YouTube watch URL parsed to nocookie embed');
assert_ok(str_starts_with($ytWatch['auto_poster'], 'https://i.ytimg.com/vi/dQw4w9WgXcQ/'), 'YouTube auto-poster generated');

$ytShort = GalleryService::parseVideoUrl('https://www.youtube.com/shorts/abcDEF12345');
assert_ok($ytShort !== null && $ytShort['embed_url'] === 'https://www.youtube-nocookie.com/embed/abcDEF12345?rel=0', 'YouTube Shorts URL parsed');

$ytBe = GalleryService::parseVideoUrl('https://youtu.be/xyz98765432');
assert_ok($ytBe !== null && $ytBe['embed_url'] === 'https://www.youtube-nocookie.com/embed/xyz98765432?rel=0', 'youtu.be short link parsed');

$vimeo = GalleryService::parseVideoUrl('https://vimeo.com/76979871');
assert_ok($vimeo !== null && $vimeo['video_source'] === 'vimeo' && $vimeo['embed_url'] === 'https://player.vimeo.com/video/76979871', 'Vimeo URL parsed');

assert_ok(GalleryService::parseVideoUrl('http://insecure.example.com/video.mp4') === null, 'Non-HTTPS video URL rejected');
assert_ok(GalleryService::parseVideoUrl('javascript:alert(1)') === null, 'Javascript pseudo-URL rejected');

// Save a YouTube video item
$savedYt = $gallery->save([
    'source_mode' => 'video_url',
    'title' => 'Malibu Glass Door Walkthrough',
    'caption' => 'Full ceiling mount tour in 4K.',
    'category' => 'Video Walkthroughs',
    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'is_featured' => '1',
    'is_active' => '1',
], []);
assert_ok($savedYt['ok'] === true && is_int($savedYt['id']), 'Save YouTube video gallery item');

// Save a local MP4 video item (using local /assets/ or /uploads/ video path)
$savedLocalVid = $gallery->save([
    'source_mode' => 'video_url',
    'title' => 'Shop Powder Coat Cure Cycle',
    'caption' => 'Direct MP4 clip from shop floor.',
    'category' => 'Powder Coating',
    'video_url' => '/uploads/gallery/sample-walkthrough.mp4',
    'is_active' => '1',
], []);
assert_ok($savedLocalVid['ok'] === true && is_int($savedLocalVid['id']), 'Save direct MP4 video gallery item');

$counts = $gallery->counts();
assert_ok($counts['all'] === 8 && $counts['image'] === 6 && $counts['video'] === 2, 'Gallery counts distinguish photos and videos');

// Render storefront portfolio page with all items and video filter
$allItems = $gallery->publicItems();
$portfolioHtml = View::partial('storefront/resources/portfolio', [
    'items' => $allItems,
    'activeType' => 'all',
    'activeCategory' => '',
    'categories' => $gallery->categories($allItems),
    'photoCount' => 6,
    'videoCount' => 2,
    'totalCount' => 8,
]);
assert_ok(
    str_contains($portfolioHtml, 'data-lightbox-type="embed"')
    && str_contains($portfolioHtml, 'data-lightbox-type="video"')
    && str_contains($portfolioHtml, 'Malibu Glass Door Walkthrough')
    && str_contains($portfolioHtml, 'Shop Powder Coat Cure Cycle'),
    'Storefront Portfolio & Gallery renders photos, uploaded MP4 video, and YouTube embed triggers'
);

// Verify ChatService indexes live gallery items
$chatGallery = (new ChatService())->answer('Where can I watch installation videos in the gallery?');
assert_ok(str_ends_with($chatGallery['sources'][0]['url'] ?? '', '/portfolio'), 'Website assistant routes gallery & video queries to /portfolio');

// Verify visibility toggle, reorder, and delete (plus one-time seed idempotency)
assert_ok($gallery->toggle($savedLocalVid['id']) === true && $gallery->counts()['hidden'] === 1, 'Gallery visibility toggle hides item from public storefront');
assert_ok(count($gallery->publicItems('video')) === 1, 'Hidden video excluded from publicItems()');
assert_ok($gallery->move($savedYt['id'], 'up') === true, 'Gallery item reorder succeeds');
assert_ok($gallery->delete($savedYt['id']) === true && $gallery->delete($savedLocalVid['id']) === true, 'Gallery item deletion succeeds');

echo "Dealer & Gallery regression suite passed completely.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
    exit(1);
}
