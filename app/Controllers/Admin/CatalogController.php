<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Money;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Str;
use App\Core\View;
use App\Services\InventoryService;
use App\Services\UploadService;

final class CatalogController
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    public function products(): string
    {
        $filters = [
            'q' => trim((string) Request::query('q', '')),
            'category' => trim((string) Request::query('category', '')),
            'status' => trim((string) Request::query('status', '')),
            'stock' => trim((string) Request::query('stock', '')),
        ];
        $where = ['1 = 1'];
        $params = [];
        if ($filters['q'] !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.model LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if ($filters['category'] !== '') {
            $where[] = 'p.category_id = ?';
            $params[] = (int) $filters['category'];
        }
        if ($filters['status'] === 'active') {
            $where[] = 'p.is_active = 1';
        } elseif ($filters['status'] === 'inactive') {
            $where[] = 'p.is_active = 0';
        }
        if ($filters['stock'] === 'low') {
            $where[] = 'p.manage_stock = 1 AND p.stock_qty <= p.low_stock_threshold';
        } elseif ($filters['stock'] === 'out') {
            $where[] = "(p.stock_status = 'out_of_stock' OR (p.manage_stock = 1 AND p.stock_qty <= 0))";
        }
        $whereSql = implode(' AND ', $where);
        $perPage = 20;
        $total = (int) Database::value("SELECT COUNT(*) FROM products p WHERE {$whereSql}", $params);
        $paginator = new Paginator($total, $perPage, max(1, Request::int('page', 1)));
        $products = Database::select(
            "SELECT p.*, c.name AS category_name,
                (SELECT path FROM product_images pi WHERE pi.product_id = p.id ORDER BY sort_order, id LIMIT 1) AS image
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$whereSql} ORDER BY p.is_active DESC, p.name ASC
             LIMIT {$paginator->perPage} OFFSET {$paginator->offset()}",
            $params
        );
        return View::render('admin/products', [
            'pageTitle' => 'Products',
            'products' => $products,
            'paginator' => $paginator,
            'filters' => $filters,
            'categories' => Database::select('SELECT id, name FROM categories ORDER BY sort_order, name'),
        ], 'layouts/admin');
    }

    public function create(): string
    {
        return View::render('admin/product-form', [
            'pageTitle' => 'New product',
            'product' => null,
            'images' => [],
            'categories' => Database::select('SELECT id, name FROM categories ORDER BY sort_order, name'),
            'movements' => [],
        ], 'layouts/admin');
    }

    public function edit(string $id): string
    {
        $product = Database::first('SELECT * FROM products WHERE id = ?', [(int) $id]);
        if (!$product) {
            flash('error', 'Product not found.');
            Response::redirect(url('/admin/products'));
        }
        return View::render('admin/product-form', [
            'pageTitle' => $product['name'],
            'product' => $product,
            'images' => Database::select('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$product['id']]),
            'categories' => Database::select('SELECT id, name FROM categories ORDER BY sort_order, name'),
            'movements' => (new InventoryService())->movements((int) $product['id'], 20),
        ], 'layouts/admin');
    }

    public function save(?string $id = null): never
    {
        Csrf::require();
        $productId = $id !== null ? (int) $id : 0;
        $existing = $productId > 0 ? Database::first('SELECT * FROM products WHERE id = ?', [$productId]) : null;
        $input = Request::all();
        $errors = [];

        foreach (['name' => 'Product name', 'sku' => 'SKU'] as $field => $label) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                $errors[$field] = $label . ' is required.';
            }
        }
        $price = $input['price'] ?? '';
        if (!is_numeric($price) || (float) $price < 0) {
            $errors['price'] = 'Enter a valid price (0 or more).';
        }
        foreach (['sale_price', 'dealer_price', 'cost', 'weight', 'length', 'width', 'height'] as $numeric) {
            if (($input[$numeric] ?? '') !== '' && (!is_numeric($input[$numeric]) || (float) $input[$numeric] < 0)) {
                $errors[$numeric] = 'Enter a valid number or leave blank.';
            }
        }
        $sku = trim((string) ($input['sku'] ?? ''));
        $skuTaken = (int) Database::value('SELECT COUNT(*) FROM products WHERE sku = ? AND id <> ?', [$sku, $productId]);
        if ($sku !== '' && $skuTaken > 0) {
            $errors['sku'] = 'That SKU is already used by another product.';
        }
        $slug = trim((string) ($input['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug((string) ($input['name'] ?? ''));
        $slugTaken = (int) Database::value('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?', [$slug, $productId]);
        if ($slugTaken > 0) {
            $slug = $slug . '-' . substr((string) time(), -4);
        }

        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url($productId > 0 ? '/admin/products/' . $productId . '/edit' : '/admin/products/create'));
        }
        clear_input();

        $specs = $this->parseSpecs((string) ($input['specs'] ?? ''));
        $box = $this->parseLines((string) ($input['box_contents'] ?? ''));
        $fields = [
            'category_id' => (int) ($input['category_id'] ?? 0) ?: null,
            'name' => trim((string) $input['name']),
            'slug' => $slug,
            'sku' => $sku,
            'model' => trim((string) ($input['model'] ?? '')),
            'brand' => trim((string) ($input['brand'] ?? 'Thunder Mounts')),
            'short_description' => trim((string) ($input['short_description'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'specs' => json_encode($specs, JSON_UNESCAPED_UNICODE),
            'box_contents' => json_encode($box, JSON_UNESCAPED_UNICODE),
            'compatibility' => trim((string) ($input['compatibility'] ?? '')),
            'price' => number_format((float) $price, 2, '.', ''),
            'sale_price' => ($input['sale_price'] ?? '') !== '' ? number_format((float) $input['sale_price'], 2, '.', '') : null,
            'dealer_price' => ($input['dealer_price'] ?? '') !== '' ? number_format((float) $input['dealer_price'], 2, '.', '') : null,
            'cost' => ($input['cost'] ?? '') !== '' ? number_format((float) $input['cost'], 2, '.', '') : null,
            'stock_status' => in_array($input['stock_status'] ?? '', ['in_stock', 'out_of_stock', 'preorder', 'quote'], true) ? $input['stock_status'] : 'in_stock',
            'backorder_allowed' => !empty($input['backorder_allowed']) ? 1 : 0,
            'manage_stock' => !empty($input['manage_stock']) ? 1 : 0,
            'low_stock_threshold' => max(0, (int) ($input['low_stock_threshold'] ?? 5)),
            'weight' => ($input['weight'] ?? '') !== '' ? number_format((float) $input['weight'], 2, '.', '') : null,
            'length' => ($input['length'] ?? '') !== '' ? number_format((float) $input['length'], 2, '.', '') : null,
            'width' => ($input['width'] ?? '') !== '' ? number_format((float) $input['width'], 2, '.', '') : null,
            'height' => ($input['height'] ?? '') !== '' ? number_format((float) $input['height'], 2, '.', '') : null,
            'material' => trim((string) ($input['material'] ?? '')),
            'finish' => trim((string) ($input['finish'] ?? '')),
            'is_featured' => !empty($input['is_featured']) ? 1 : 0,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'is_taxable' => !empty($input['is_taxable']) ? 1 : 0,
            'meta_title' => trim((string) ($input['meta_title'] ?? '')),
            'meta_description' => mb_substr(trim((string) ($input['meta_description'] ?? '')), 0, 300),
            'updated_at' => now(),
        ];

        if ($existing) {
            Database::update('products', $fields, 'id = ?', [$productId]);
        } else {
            $fields['stock_qty'] = max(0, (int) ($input['stock_qty'] ?? 0));
            $fields['created_at'] = now();
            $productId = Database::insert('products', $fields);
            if ($fields['stock_qty'] > 0) {
                Database::insert('inventory_movements', [
                    'product_id' => $productId,
                    'order_id' => null,
                    'admin_id' => Auth::id(),
                    'delta' => $fields['stock_qty'],
                    'stock_after' => $fields['stock_qty'],
                    'reason' => 'Initial stock',
                    'created_at' => now(),
                ]);
            }
        }

        $this->handleImageUploads($productId, $errors);
        if ($errors !== []) {
            Session::set('_errors', $errors);
            Response::redirect(url('/admin/products/' . $productId . '/edit'));
        }
        flash('success', $existing ? 'Product updated.' : 'Product created.');
        Response::redirect(url('/admin/products/' . $productId . '/edit'));
    }

    public function toggle(string $id): never
    {
        Csrf::require();
        $product = Database::first('SELECT * FROM products WHERE id = ?', [(int) $id]);
        if ($product) {
            Database::execute('UPDATE products SET is_active = ?, updated_at = ? WHERE id = ?', [(int) $product['is_active'] === 1 ? 0 : 1, now(), $product['id']]);
            flash('success', (int) $product['is_active'] === 1 ? 'Product hidden from the store.' : 'Product published.');
        }
        Response::redirect(url('/admin/products'));
    }

    public function delete(string $id): never
    {
        Csrf::require();
        $product = Database::first('SELECT * FROM products WHERE id = ?', [(int) $id]);
        if (!$product) {
            flash('error', 'Product not found.');
            Response::redirect(url('/admin/products'));
        }
        $ordered = (int) Database::value('SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$product['id']]);
        if ($ordered > 0) {
            Database::execute('UPDATE products SET is_active = 0, updated_at = ? WHERE id = ?', [now(), $product['id']]);
            flash('success', 'This product appears on past orders, so it was hidden instead of deleted.');
        } else {
            Database::execute('DELETE FROM products WHERE id = ?', [$product['id']]);
            flash('success', 'Product deleted.');
        }
        Response::redirect(url('/admin/products'));
    }

    public function imageUpload(string $id): never
    {
        Csrf::require();
        $product = Database::first('SELECT * FROM products WHERE id = ?', [(int) $id]);
        if (!$product) {
            Response::redirect(url('/admin/products'));
        }
        $files = $_FILES['images'] ?? null;
        if (!$files) {
            flash('error', 'Choose at least one image.');
            Response::redirect(url('/admin/products/' . $product['id'] . '/edit'));
        }
        $count = is_array($files['name']) ? count($files['name']) : 0;
        $sort = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?', [$product['id']]);
        $uploaded = 0;
        $service = new UploadService();
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $single = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            $result = $service->image($single, 'products');
            if (!$result['ok']) {
                flash('error', $result['error']);
                continue;
            }
            Database::insert('product_images', [
                'product_id' => $product['id'],
                'path' => $result['path'],
                'alt' => $product['name'],
                'sort_order' => ++$sort,
            ]);
            $uploaded++;
        }
        if ($uploaded > 0) {
            flash('success', $uploaded . ' image' . ($uploaded === 1 ? '' : 's') . ' added.');
        }
        Response::redirect(url('/admin/products/' . $product['id'] . '/edit'));
    }

    public function imageDelete(string $id, string $imageId): never
    {
        Csrf::require();
        $image = Database::first('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int) $imageId, (int) $id]);
        if ($image) {
            (new UploadService())->delete((string) $image['path']);
            Database::execute('DELETE FROM product_images WHERE id = ?', [$image['id']]);
            flash('success', 'Image removed.');
        }
        Response::redirect(url('/admin/products/' . (int) $id . '/edit'));
    }

    public function imagePrimary(string $id, string $imageId): never
    {
        Csrf::require();
        $image = Database::first('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int) $imageId, (int) $id]);
        if ($image) {
            Database::execute('UPDATE product_images SET sort_order = sort_order + 1 WHERE product_id = ?', [(int) $id]);
            Database::execute('UPDATE product_images SET sort_order = 0 WHERE id = ?', [$image['id']]);
            flash('success', 'Primary image updated.');
        }
        Response::redirect(url('/admin/products/' . (int) $id . '/edit'));
    }

    public function inventory(): string
    {
        $filter = (string) Request::query('filter', 'all');
        $where = ['p.is_active = 1'];
        if ($filter === 'low') {
            $where[] = 'p.manage_stock = 1 AND p.stock_qty <= p.low_stock_threshold';
        } elseif ($filter === 'out') {
            $where[] = "(p.stock_status = 'out_of_stock' OR (p.manage_stock = 1 AND p.stock_qty <= 0))";
        } elseif ($filter === 'untracked') {
            $where[] = 'p.manage_stock = 0';
        }
        $products = Database::select(
            'SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY p.manage_stock DESC, p.stock_qty ASC, p.name ASC LIMIT 200'
        );
        return View::render('admin/inventory', [
            'pageTitle' => 'Inventory',
            'products' => $products,
            'filter' => $filter,
            'recent' => Database::select(
                'SELECT m.*, p.name, p.sku FROM inventory_movements m INNER JOIN products p ON p.id = m.product_id
                 ORDER BY m.created_at DESC, m.id DESC LIMIT 15'
            ),
        ], 'layouts/admin');
    }

    public function adjustStock(): never
    {
        Csrf::require();
        $productId = Request::int('product_id');
        $mode = (string) Request::input('mode', 'delta');
        $product = Database::first('SELECT * FROM products WHERE id = ?', [$productId]);
        if (!$product) {
            flash('error', 'Product not found.');
            Response::redirect(url('/admin/inventory'));
        }
        if ($mode === 'set') {
            $newQty = max(0, Request::int('quantity'));
            $delta = $newQty - (int) $product['stock_qty'];
            $reason = trim((string) Request::input('reason', 'Stock count'));
        } else {
            $delta = Request::int('delta');
            $reason = trim((string) Request::input('reason', 'Manual adjustment'));
        }
        if ($delta === 0) {
            flash('info', 'No stock change was entered.');
            Response::redirect(url('/admin/inventory'));
        }
        $result = (new InventoryService())->adjust($productId, $delta, $reason !== '' ? $reason : 'Manual adjustment', Auth::id());
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        Response::redirect(url('/admin/inventory'));
    }

    public function categories(): string
    {
        $editId = Request::int('edit', 0);
        return View::render('admin/categories', [
            'pageTitle' => 'Categories',
            'categories' => Database::select(
                'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
                 FROM categories c ORDER BY c.sort_order, c.name'
            ),
            'editCategory' => $editId > 0 ? Database::first('SELECT * FROM categories WHERE id = ?', [$editId]) : null,
        ], 'layouts/admin');
    }

    public function saveCategory(?string $id = null): never
    {
        Csrf::require();
        $categoryId = $id !== null ? (int) $id : 0;
        $input = Request::all();
        $name = trim((string) ($input['name'] ?? ''));
        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Category name is required.';
        }
        $slug = trim((string) ($input['slug'] ?? ''));
        $slug = $slug !== '' ? Str::slug($slug) : Str::slug($name);
        if ((int) Database::value('SELECT COUNT(*) FROM categories WHERE slug = ? AND id <> ?', [$slug, $categoryId]) > 0) {
            $errors['slug'] = 'That URL slug is already used.';
        }
        $image = trim((string) ($input['image'] ?? ''));
        if (Request::files('image_file') && (Request::files('image_file')['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = (new UploadService())->image(Request::files('image_file'), 'categories', 1200);
            if (!$upload['ok']) {
                $errors['image_file'] = $upload['error'];
            } else {
                $image = $upload['path'];
            }
        }
        if ($errors !== []) {
            Session::set('_errors', $errors);
            remember_input($input);
            Response::redirect(url('/admin/categories' . ($categoryId > 0 ? '?edit=' . $categoryId : '')));
        }
        $fields = [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($input['description'] ?? '')),
            'image' => $image,
            'sort_order' => Request::int('sort_order'),
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'meta_title' => trim((string) ($input['meta_title'] ?? '')),
            'meta_description' => mb_substr(trim((string) ($input['meta_description'] ?? '')), 0, 300),
            'updated_at' => now(),
        ];
        if ($categoryId > 0) {
            Database::update('categories', $fields, 'id = ?', [$categoryId]);
            flash('success', 'Category updated.');
        } else {
            $fields['created_at'] = now();
            Database::insert('categories', $fields);
            flash('success', 'Category created.');
        }
        clear_input();
        Response::redirect(url('/admin/categories'));
    }

    public function deleteCategory(string $id): never
    {
        Csrf::require();
        $count = (int) Database::value('SELECT COUNT(*) FROM products WHERE category_id = ?', [(int) $id]);
        if ($count > 0) {
            flash('error', 'Move or delete the ' . $count . ' product(s) in this category first.');
        } else {
            Database::execute('DELETE FROM categories WHERE id = ?', [(int) $id]);
            flash('success', 'Category deleted.');
        }
        Response::redirect(url('/admin/categories'));
    }

    private function handleImageUploads(int $productId, array &$errors): void
    {
        $files = $_FILES['images'] ?? null;
        if (!$files || ($files['error'][0] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        $service = new UploadService();
        $sort = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?', [$productId]);
        $name = (string) Database::value('SELECT name FROM products WHERE id = ?', [$productId], '');
        foreach ($files['name'] as $index => $original) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $single = [
                'name' => $files['name'][$index],
                'type' => $files['type'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index],
            ];
            $result = $service->image($single, 'products');
            if (!$result['ok']) {
                $errors['images'] = $result['error'];
                continue;
            }
            Database::insert('product_images', [
                'product_id' => $productId,
                'path' => $result['path'],
                'alt' => $name,
                'sort_order' => ++$sort,
            ]);
        }
    }

    private function parseSpecs(string $raw): array
    {
        $specs = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key !== '') {
                $specs[$key] = $value;
            }
        }
        return $specs;
    }

    private function parseLines(string $raw): array
    {
        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        return $lines;
    }
}
