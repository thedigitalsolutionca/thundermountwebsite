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
use App\Services\TaxService;

final class TaxController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function index(): string
    {
        $editId = Request::int('edit', 0);
        return View::render('admin/tax', [
            'pageTitle' => 'Sales tax',
            'rates' => (new TaxService())->rates(),
            'editRate' => $editId > 0 ? Database::first('SELECT * FROM tax_rates WHERE id = ?', [$editId]) : null,
            'enabled' => (bool) setting('tax.enabled', '1'),
            'shippingTaxable' => (bool) setting('tax.shipping_taxable', '0'),
            'configured' => (new TaxService())->configured(),
        ], 'layouts/admin');
    }

    public function saveSettings(): never
    {
        Csrf::require();
        Settings::set('tax.enabled', Request::bool('tax_enabled') ? '1' : '0', 'tax');
        Settings::set('tax.shipping_taxable', Request::bool('shipping_taxable') ? '1' : '0', 'tax');
        flash('success', 'Tax settings saved.');
        Response::redirect(url('/admin/tax'));
    }

    public function saveRate(?string $id = null): never
    {
        Csrf::require();
        $rateId = $id !== null ? (int) $id : 0;
        $input = Request::all();
        $errors = [];
        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'Rate name is required.';
        }
        $country = strtoupper(trim((string) ($input['country'] ?? 'US')));
        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            $errors['country'] = 'Use a two-letter country code.';
        }
        if (trim((string) ($input['state'] ?? '')) === '') {
            $errors['state'] = 'State / province is required.';
        }
        if (!is_numeric($input['rate'] ?? '') || (float) $input['rate'] < 0 || (float) $input['rate'] > 100) {
            $errors['rate'] = 'Enter a percentage between 0 and 100.';
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/admin/tax' . ($rateId > 0 ? '?edit=' . $rateId : '')));
        }
        $fields = [
            'name' => trim((string) $input['name']),
            'country' => $country,
            'state' => strtoupper(trim((string) $input['state'])),
            'rate' => number_format((float) $input['rate'], 4, '.', ''),
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'updated_at' => now(),
        ];
        if ($rateId > 0) {
            Database::update('tax_rates', $fields, 'id = ?', [$rateId]);
            flash('success', 'Tax rate updated.');
        } else {
            $fields['created_at'] = now();
            Database::insert('tax_rates', $fields);
            flash('success', 'Tax rate created.');
        }
        clear_input();
        Response::redirect(url('/admin/tax'));
    }

    public function deleteRate(string $id): never
    {
        Csrf::require();
        Database::execute('DELETE FROM tax_rates WHERE id = ?', [(int) $id]);
        flash('success', 'Tax rate deleted.');
        Response::redirect(url('/admin/tax'));
    }
}
