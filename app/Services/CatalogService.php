<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Money;
use App\Core\Paginator;

final class CatalogService
{
    private static ?self $instance = null;
    private ?array $categoryCache = null;
    private ?float $tierDiscount = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function categories(bool $activeOnly = true): array
    {
        if ($this->categoryCache === null) {
            $sql = 'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS product_count
                    FROM categories c ORDER BY c.sort_order, c.name';
            $this->categoryCache = Database::select($sql);
        }
        return $activeOnly
            ? array_values(array_filter($this->categoryCache, static fn ($c) => (int) $c['is_active'] === 1))
            : $this->categoryCache;
    }

    public function categoryBySlug(string $slug): ?array
    {
        foreach ($this->categories(false) as $category) {
            if ($category['slug'] === $slug) {
                return $category;
            }
        }
        return Database::first('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    /**
     * @return array{items:array,total:int,paginator:Paginator,filters:array}
     */
    public function search(array $filters = []): array
    {
        $categorySlug = trim((string) ($filters['category'] ?? ''));
        $q = trim((string) ($filters['q'] ?? ''));
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $length = trim((string) ($filters['length'] ?? ''));
        $availability = trim((string) ($filters['availability'] ?? ''));
        $sort = (string) ($filters['sort'] ?? 'featured');
        $featured = !empty($filters['featured']);
        $limit = max(1, min(48, (int) ($filters['limit'] ?? 12)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $where = ['p.is_active = 1'];
        $params = [];

        if ($categorySlug !== '') {
            $category = $this->categoryBySlug($categorySlug);
            if ($category) {
                $where[] = 'p.category_id = ?';
                $params[] = $category['id'];
            } else {
                $where[] = '1 = 0';
            }
        }
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.model LIKE ? OR p.short_description LIKE ? OR p.brand LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($length !== '' && in_array($length, ['12', '19', '24', '29'], true)) {
            $where[] = "(p.model LIKE ? OR p.name LIKE ? OR p.sku LIKE ?)";
            $like = '%-' . $length . '%';
            array_push($params, $like, $like, $like);
        }
        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $where[] = 'COALESCE(NULLIF(p.sale_price, 0), p.price) >= ?';
            $params[] = (float) $minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $where[] = 'COALESCE(NULLIF(p.sale_price, 0), p.price) <= ?';
            $params[] = (float) $maxPrice;
        }
        if ($availability === 'in_stock') {
            $where[] = "p.stock_status = 'in_stock'";
        } elseif ($availability === 'preorder') {
            $where[] = "p.stock_status = 'preorder'";
        } elseif ($availability === 'quote') {
            $where[] = "p.stock_status = 'quote'";
        }
        if ($featured) {
            $where[] = 'p.is_featured = 1';
        }

        $whereSql = implode(' AND ', $where);
        $orderSql = match ($sort) {
            'price-asc' => 'ORDER BY COALESCE(NULLIF(p.sale_price, 0), p.price) ASC, p.name ASC',
            'price-desc' => 'ORDER BY COALESCE(NULLIF(p.sale_price, 0), p.price) DESC, p.name ASC',
            'name-asc' => 'ORDER BY p.name ASC',
            'name-desc' => 'ORDER BY p.name DESC',
            'newest' => 'ORDER BY p.created_at DESC, p.id DESC',
            default => 'ORDER BY p.is_featured DESC, p.id ASC',
        };

        $total = (int) Database::value("SELECT COUNT(*) FROM products p WHERE {$whereSql}", $params);
        $paginator = new Paginator($total, $limit, $page);

        $rows = Database::select(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$whereSql} {$orderSql}
             LIMIT {$paginator->perPage} OFFSET {$paginator->offset()}",
            $params
        );

        return [
            'items' => array_map(fn (array $p) => $this->decorate($p), $rows),
            'total' => $total,
            'paginator' => $paginator,
            'filters' => [
                'category' => $categorySlug,
                'q' => $q,
                'length' => $length,
                'availability' => $availability,
                'sort' => $sort,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ],
        ];
    }

    public function featured(int $limit = 4): array
    {
        $rows = Database::select(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND p.is_featured = 1
             ORDER BY p.id ASC LIMIT {$limit}"
        );
        return array_map(fn (array $p) => $this->decorate($p), $rows);
    }

    public function find(string $slugOrId): ?array
    {
        $row = is_numeric($slugOrId)
            ? Database::first('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.is_active = 1', [(int) $slugOrId])
            : Database::first('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.is_active = 1', [$slugOrId]);
        return $row ? $this->decorate($row) : null;
    }

    public function findAny(int $id): ?array
    {
        $row = Database::first('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ?', [$id]);
        return $row ? $this->decorate($row) : null;
    }

    public function related(array $product, int $limit = 4): array
    {
        $rows = Database::select(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND p.category_id = ? AND p.id <> ?
             ORDER BY p.is_featured DESC, ' . (Database::isSqlite() ? 'RANDOM()' : 'RAND()') . ' LIMIT ' . max(1, $limit),
            [$product['category_id'], $product['id']]
        );
        if (count($rows) < $limit) {
            $exclude = array_merge([$product['id']], array_column($rows, 'id'));
            $placeholders = implode(',', array_fill(0, count($exclude), '?'));
            $more = Database::select(
                "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                 FROM products p LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.is_active = 1 AND p.id NOT IN ({$placeholders})
                 ORDER BY p.is_featured DESC, p.id ASC LIMIT " . ($limit - count($rows)),
                $exclude
            );
            $rows = array_merge($rows, $more);
        }
        return array_map(fn (array $p) => $this->decorate($p), $rows);
    }

    public function images(int $productId): array
    {
        return Database::select(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id',
            [$productId]
        );
    }

    public function primaryImage(array $product): string
    {
        if (!empty($product['images'][0]['path'])) {
            return $product['images'][0]['path'];
        }
        return '/assets/img/tm-mount-1.jpg';
    }

    public function decorate(array $product): array
    {
        $product['specs_array'] = $this->decodeMap($product['specs'] ?? null);
        $product['box_array'] = $this->decodeList($product['box_contents'] ?? null);
        $product['images'] = $this->images((int) $product['id']);
        $product['price_info'] = $this->priceFor($product);
        $product['availability'] = $this->availability($product);
        $product['url'] = '/product/' . $product['slug'];
        $product['can_purchase'] = $this->canPurchase($product);
        return $product;
    }

    /**
     * Price resolution is always performed server-side.
     *
     * @return array{unit_cents:int,retail_cents:int,list_cents:int,is_dealer:bool,on_sale:bool,tier_discount:float}
     */
    public function priceFor(array $product): array
    {
        $list = Money::toCents($product['price'] ?? 0);
        $sale = !empty($product['sale_price']) ? Money::toCents($product['sale_price']) : 0;
        $retail = ($sale > 0 && $sale < $list) ? $sale : $list;
        $unit = $retail;
        $isDealer = false;
        $tierDiscount = 0.0;

        if (Auth::isDealer()) {
            $isDealer = true;
            $override = !empty($product['dealer_price']) ? Money::toCents($product['dealer_price']) : 0;
            if ($override > 0) {
                $unit = $override;
            } else {
                $tierDiscount = $this->tierDiscount();
                if ($tierDiscount > 0) {
                    $unit = $retail - (int) round($retail * ($tierDiscount / 100));
                }
            }
            $unit = max(0, $unit);
        }

        return [
            'unit_cents' => $unit,
            'retail_cents' => $retail,
            'list_cents' => $list,
            'is_dealer' => $isDealer,
            'on_sale' => $sale > 0 && $sale < $list,
            'tier_discount' => $tierDiscount,
        ];
    }

    private function tierDiscount(): float
    {
        if ($this->tierDiscount !== null) {
            return $this->tierDiscount;
        }
        $this->tierDiscount = 0.0;
        $user = Auth::user();
        if ($user && !empty($user['tier_id'])) {
            $value = Database::value(
                'SELECT discount_percent FROM dealer_tiers WHERE id = ? AND is_active = 1',
                [$user['tier_id']]
            );
            if ($value !== null) {
                $this->tierDiscount = (float) $value;
            }
        }
        return $this->tierDiscount;
    }

    /**
     * @return array{key:string,label:string,class:string,detail:string}
     */
    public function availability(array $product): array
    {
        $status = (string) ($product['stock_status'] ?? 'in_stock');
        $managed = (int) ($product['manage_stock'] ?? 0) === 1;
        $qty = (int) ($product['stock_qty'] ?? 0);
        $low = (int) ($product['low_stock_threshold'] ?? 5);

        if ($status === 'quote') {
            return ['key' => 'quote', 'label' => 'Request a quote', 'class' => 'badge-blue', 'detail' => 'Pricing is quoted for each project'];
        }
        if ($status === 'preorder') {
            return ['key' => 'preorder', 'label' => 'Coming soon', 'class' => 'badge-amber', 'detail' => 'Join the notification list'];
        }
        if ($status === 'out_of_stock' || ($managed && $qty <= 0 && !(int) $product['backorder_allowed'])) {
            return ['key' => 'out_of_stock', 'label' => 'Out of stock', 'class' => 'badge-red', 'detail' => 'Contact us for availability'];
        }
        if ($managed && $qty > 0 && $qty <= $low) {
            return ['key' => 'low_stock', 'label' => 'Low stock', 'class' => 'badge-amber', 'detail' => 'Only ' . $qty . ' left'];
        }
        return ['key' => 'in_stock', 'label' => 'In stock', 'class' => 'badge-green', 'detail' => 'Ships from California'];
    }

    public function canPurchase(array $product): bool
    {
        $availability = $product['availability']['key'] ?? $this->availability($product)['key'];
        return in_array($availability, ['in_stock', 'low_stock'], true);
    }

    public function stockAvailable(array $product, int $qty): bool
    {
        if ((int) ($product['manage_stock'] ?? 0) === 0) {
            return true;
        }
        if ($qty <= 0) {
            return false;
        }
        if ((int) $product['backorder_allowed'] === 1) {
            return true;
        }
        return (int) $product['stock_qty'] >= $qty;
    }

    public function suggestions(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $like = '%' . $query . '%';
        $rows = Database::select(
            'SELECT p.id, p.name, p.slug, p.sku, p.price, p.sale_price, p.price AS base_price, p.stock_status, p.manage_stock, p.stock_qty,
                    (SELECT path FROM product_images pi WHERE pi.product_id = p.id ORDER BY sort_order, id LIMIT 1) AS image
             FROM products p
             WHERE p.is_active = 1 AND (p.name LIKE ? OR p.sku LIKE ? OR p.model LIKE ?)
             ORDER BY p.is_featured DESC, p.name ASC LIMIT ' . max(1, min(10, $limit)),
            [$like, $like, $like]
        );
        return array_map(fn ($r) => [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'slug' => $r['slug'],
            'sku' => $r['sku'],
            'price' => Money::format((float) ($r['sale_price'] ?: $r['price'])),
            'image' => $r['image'] ?: '/assets/img/tm-mount-1.jpg',
            'url' => '/product/' . $r['slug'],
        ], $rows);
    }

    public function reviews(int $productId, bool $approvedOnly = true): array
    {
        $sql = 'SELECT * FROM reviews WHERE product_id = ?';
        if ($approvedOnly) {
            $sql .= " AND status = 'approved'";
        }
        $sql .= ' ORDER BY created_at DESC';
        return Database::select($sql, [$productId]);
    }

    public function reviewStats(int $productId): array
    {
        $row = Database::first(
            "SELECT COUNT(*) AS total, AVG(rating) AS average FROM reviews WHERE product_id = ? AND status = 'approved'",
            [$productId]
        );
        return [
            'total' => (int) ($row['total'] ?? 0),
            'average' => round((float) ($row['average'] ?? 0), 1),
        ];
    }

    private function decodeMap(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function decodeList(?string $json): array
    {
        if (!$json) {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
}
