<?php

declare(strict_types=1);

namespace App\Controllers\Storefront;

use App\Content\Journal;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\ContentService;
use DateTimeImmutable;
use DateTimeZone;

/** Editorial and service pages that must work on existing installations too.
 *  They are intentionally file-backed: deploying these routes does not require
 *  reseeding the production database or overwriting merchant-edited CMS pages.
 */
final class ResourceController
{
    public function servicePage(string $slug): string
    {
        $pages = require TM_ROOT . '/app/Content/Services.php';
        $page = $pages[$slug];
        return $this->render('service', $page['title'], $page['intro'], '/' . $slug, ['servicePage' => $page]);
    }

    public function story(): string
    {
        return $this->render('story', 'The Story of Thunder Mounts',
            'Meet Gustavo Estravit and learn how a better garage installation began with a home project in 1991.',
            '/about/inventor', ['ogImage' => '/assets/img/gustavo-estravit-owner.jpg']);
    }

    public function portfolio(): string
    {
        return $this->render('portfolio', 'Installation Portfolio',
            'See real Thunder Mounts track and opener mounting installations, including projects in Malibu.',
            '/portfolio');
    }

    public function sizeGuide(): string
    {
        return $this->render('size-guide', 'Mount Sizing Guide',
            'Measure your opener and track drop and compare 12, 19, 24 and 29 inch Thunder Mounts models.',
            '/size-guide');
    }

    public function instructions(): string
    {
        return $this->render('instructions', 'Mounting Instructions',
            'Download the original Thunder Mounts track mounting instructions and view the May 2026 installation chart.',
            '/resources/instructions');
    }

    public function salesSheet(): string
    {
        return $this->render('sales-sheet', 'Thunder Mounts Sales Sheet',
            'A printable overview of Thunder Mounts opener and track mounting systems for customers and installers.',
            '/sales-sheet');
    }

    public function blog(): string
    {
        return $this->render('blog', 'Garage Journal',
            'Practical guides to garage mounting hardware, sizing and finished installations.',
            '/blog', ['posts' => Journal::all()]);
    }

    public function article(string $slug): string
    {
        $post = Journal::find($slug);
        if ($post === null) {
            Response::html(View::render('errors/404', ['pageTitle' => 'Article not found'], 'layouts/storefront'), 404);
        }
        return $this->render('article', (string) $post['title'], (string) $post['summary'],
            '/blog/' . $slug, ['post' => $post, 'posts' => Journal::all(), 'ogImage' => $post['image']]);
    }

    public function bookingForm(): string
    {
        return $this->render('booking', 'Request an Appointment',
            'Request a garage door installation design consultation or a Thunder Mount installation conversation.',
            '/book-online', ['robots' => 'index,follow']);
    }

    public function booking(): never
    {
        Csrf::require();
        $data = [];
        foreach (['name', 'email', 'phone', 'service', 'preferred_date', 'time_window', 'notes'] as $field) {
            $value = Request::post($field, '');
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        $validator = (new Validator($data, [
            'name' => 'Name',
            'email' => 'Email address',
            'phone' => 'Phone number',
            'service' => 'Service',
            'preferred_date' => 'Preferred date',
            'time_window' => 'Time window',
            'notes' => 'Project details',
        ]))->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|phone|max:30',
            'service' => 'required|in:design,installation',
            'preferred_date' => 'required|date',
            'time_window' => 'required|in:morning,afternoon,flexible',
            'notes' => 'max:1000',
        ]);
        $errors = $validator->errors();
        $zone = new DateTimeZone('America/Los_Angeles');
        $dateText = (string) ($data['preferred_date'] ?? '');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateText, $zone);
        if (!isset($errors['preferred_date']) && (!$date || $date->format('Y-m-d') !== $dateText || $date < new DateTimeImmutable('today', $zone))) {
            $errors['preferred_date'] = 'Choose today or a future date in Pacific time.';
        } elseif (!isset($errors['preferred_date']) && $date->format('N') === '7') {
            $errors['preferred_date'] = 'We are closed Sundays. Please choose another day.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($data);
            Response::redirect(url('/book-online'));
        }

        $services = [
            'design' => 'Garage door installation design',
            'installation' => 'Thunder Mount installation request',
        ];
        $windows = [
            'morning' => 'Morning (Pacific time)',
            'afternoon' => 'Afternoon (Pacific time)',
            'flexible' => 'Flexible',
        ];
        $message = "Appointment request — not a confirmed booking\n"
            . 'Service: ' . $services[$data['service']] . "\n"
            . 'Preferred date: ' . $dateText . "\n"
            . 'Time window: ' . $windows[$data['time_window']] . "\n"
            . 'Project details: ' . (trim((string) ($data['notes'] ?? '')) ?: 'Not provided.');
        $result = (new ContentService())->contact([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => 'Appointment request: ' . $services[$data['service']],
            'message' => $message,
        ]);
        if (!$result['ok']) {
            Session::set('_errors', $result['errors']);
            remember_input($data);
            Response::redirect(url('/book-online'));
        }
        clear_input();
        flash('success', 'Thanks — your appointment request is in. We will contact you during business hours to confirm a time.');
        Response::redirect(url('/book-online'));
    }

    /** Redirect only the legacy articles actually recreated in this release. */
    public function legacyPost(string $slug): never
    {
        $path = Journal::legacyPath($slug);
        if ($path === null) {
            Response::html(View::render('errors/404', ['pageTitle' => 'Article not found'], 'layouts/storefront'), 404);
        }
        Response::redirect(url($path), 301);
    }

    private function render(string $view, string $title, string $description, string $path, array $data = []): string
    {
        return View::render('storefront/resources/' . $view, array_merge([
            'pageTitle' => $title,
            'metaDescription' => $description,
            'canonical' => absolute_url($path),
        ], $data), 'layouts/storefront');
    }
}
