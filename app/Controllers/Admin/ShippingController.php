<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Services\ShippingService;

final class ShippingController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): string
    {
        $service = new ShippingService();
        $zones = $service->zones();
        $methods = Database::select('SELECT * FROM shipping_methods ORDER BY zone_id, sort_order, id');
        $grouped = [];
        foreach ($methods as $method) {
            $grouped[(int) $method['zone_id']][] = $method;
        }
        $editMethodId = Request::int('method', 0);
        return View::render('admin/shipping', [
            'pageTitle' => 'Shipping',
            'zones' => $zones,
            'methodsByZone' => $grouped,
            'editZone' => Request::int('zone', 0) > 0 ? Database::first('SELECT * FROM shipping_zones WHERE id = ?', [Request::int('zone')]) : null,
            'editMethod' => $editMethodId > 0 ? Database::first('SELECT * FROM shipping_methods WHERE id = ?', [$editMethodId]) : null,
            'configured' => $service->configured(),
            'origin' => [
                'country' => (string) setting('shipping.origin_country', 'US'),
                'state' => (string) setting('shipping.origin_state', 'CA'),
                'city' => (string) setting('shipping.origin_city', 'Simi Valley'),
                'postcode' => (string) setting('shipping.origin_postcode', '93063'),
            ],
        ], 'layouts/admin');
    }

    public function saveOrigin(): never
    {
        Csrf::require();
        $input = Request::all();
        $country = strtoupper(trim((string) ($input['origin_country'] ?? '')));
        $state = strtoupper(trim((string) ($input['origin_state'] ?? '')));
        $city = trim((string) ($input['origin_city'] ?? ''));
        $postcode = trim((string) ($input['origin_postcode'] ?? ''));
        $errors = [];
        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            $errors['origin_country'] = 'Use a two-letter country code, for example US.';
        }
        if ($state === '') {
            $errors['origin_state'] = 'State / province is required.';
        }
        if ($postcode === '') {
            $errors['origin_postcode'] = 'ZIP / postal code is required.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/admin/shipping'));
        }
        Settings::set('shipping.origin_country', $country, 'shipping');
        Settings::set('shipping.origin_state', $state, 'shipping');
        Settings::set('shipping.origin_city', $city, 'shipping');
        Settings::set('shipping.origin_postcode', $postcode, 'shipping');
        clear_input();
        flash('success', 'Shipping base location saved.');
        Response::redirect(url('/admin/shipping'));
    }

    public function saveZone(?string $id = null): never
    {
        Csrf::require();
        $zoneId = $id !== null ? (int) $id : 0;
        $input = Request::all();
        $errors = [];
        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Zone name is required.';
        }
        $countries = strtoupper(preg_replace('/[^A-Za-z,]/', '', (string) ($input['countries'] ?? 'US')) ?: 'US');
        if ($countries === '') {
            $errors['countries'] = 'Enter at least one country code.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/admin/shipping'));
        }
        $fields = [
            'name' => trim((string) $input['name']),
            'countries' => $countries,
            'states' => trim((string) ($input['states'] ?? '')),
            'is_default' => !empty($input['is_default']) ? 1 : 0,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($fields['is_default']) {
            Database::execute('UPDATE shipping_zones SET is_default = 0');
        }
        if ($zoneId > 0) {
            Database::update('shipping_zones', $fields, 'id = ?', [$zoneId]);
            flash('success', 'Shipping zone updated.');
        } else {
            $fields['created_at'] = now();
            Database::insert('shipping_zones', $fields);
            flash('success', 'Shipping zone created.');
        }
        clear_input();
        Response::redirect(url('/admin/shipping'));
    }

    public function deleteZone(string $id): never
    {
        Csrf::require();
        $methods = (int) Database::value('SELECT COUNT(*) FROM shipping_methods WHERE zone_id = ?', [(int) $id]);
        if ($methods > 0) {
            flash('error', 'Delete the ' . $methods . ' method(s) in this zone first.');
        } else {
            Database::execute('DELETE FROM shipping_zones WHERE id = ?', [(int) $id]);
            flash('success', 'Shipping zone deleted.');
        }
        Response::redirect(url('/admin/shipping'));
    }

    public function saveMethod(?string $id = null): never
    {
        Csrf::require();
        $methodId = $id !== null ? (int) $id : 0;
        $input = Request::all();
        $errors = [];
        $zoneId = (int) ($input['zone_id'] ?? 0);
        if (!Database::first('SELECT id FROM shipping_zones WHERE id = ?', [$zoneId])) {
            $errors['zone_id'] = 'Choose a zone.';
        }
        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Method name is required.';
        }
        $type = in_array($input['type'] ?? '', ['flat', 'weight', 'free'], true) ? $input['type'] : 'flat';
        foreach (['rate', 'per_unit', 'min_weight', 'max_weight', 'free_over'] as $numeric) {
            if (($input[$numeric] ?? '') !== '' && (!is_numeric($input[$numeric]) || (float) $input[$numeric] < 0)) {
                $errors[$numeric] = 'Enter a valid number or leave blank.';
            }
        }
        foreach (['est_days_min', 'est_days_max'] as $integer) {
            if (($input[$integer] ?? '') !== '' && (!is_numeric($input[$integer]) || (int) $input[$integer] < 0)) {
                $errors[$integer] = 'Enter a whole number of days.';
            }
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/admin/shipping'));
        }
        $fields = [
            'zone_id' => $zoneId,
            'name' => trim((string) $input['name']),
            'type' => $type,
            'rate' => number_format((float) ($input['rate'] ?? 0), 2, '.', ''),
            'per_unit' => number_format((float) ($input['per_unit'] ?? 0), 2, '.', ''),
            'min_weight' => number_format((float) ($input['min_weight'] ?? 0), 2, '.', ''),
            'max_weight' => ($input['max_weight'] ?? '') !== '' ? number_format((float) $input['max_weight'], 2, '.', '') : null,
            'free_over' => ($input['free_over'] ?? '') !== '' ? number_format((float) $input['free_over'], 2, '.', '') : null,
            'est_days_min' => ($input['est_days_min'] ?? '') !== '' ? (int) $input['est_days_min'] : null,
            'est_days_max' => ($input['est_days_max'] ?? '') !== '' ? (int) $input['est_days_max'] : null,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
            'updated_at' => now(),
        ];
        if ($methodId > 0) {
            Database::update('shipping_methods', $fields, 'id = ?', [$methodId]);
            flash('success', 'Shipping method updated.');
        } else {
            $fields['created_at'] = now();
            Database::insert('shipping_methods', $fields);
            flash('success', 'Shipping method created.');
        }
        clear_input();
        Response::redirect(url('/admin/shipping'));
    }

    public function deleteMethod(string $id): never
    {
        Csrf::require();
        Database::execute('DELETE FROM shipping_methods WHERE id = ?', [(int) $id]);
        flash('success', 'Shipping method deleted.');
        Response::redirect(url('/admin/shipping'));
    }
}
