<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Settings;
use App\Core\Validator;
use Throwable;

final class GalleryService
{
    private static bool $ready = false;

    public const DEFAULT_ITEMS = [
        [
            'media_type' => 'image',
            'title' => 'Full-view door, considered support',
            'caption' => 'A close look at the overhead track and clean ceiling lines.',
            'category' => 'Track Mounts',
            'media_path' => '/assets/img/tm-track-glass.jpg',
            'alt_text' => 'Black steel track supports above a glass-panel garage door',
            'sort_order' => 10,
            'is_featured' => 1,
        ],
        [
            'media_type' => 'image',
            'title' => 'The work behind the finish',
            'caption' => 'Installation in progress at a Malibu, California garage.',
            'category' => 'Installations',
            'media_path' => '/assets/img/chi-door-malibu.jpg',
            'alt_text' => 'Installers on ladders working in a Malibu garage',
            'sort_order' => 20,
            'is_featured' => 0,
        ],
        [
            'media_type' => 'image',
            'title' => 'A finished white garage',
            'caption' => 'Track, opener and room working together.',
            'category' => 'Powder Coating',
            'media_path' => '/assets/img/tm-install-white.jpg',
            'alt_text' => 'White garage with door tracks and a ceiling-mounted opener',
            'sort_order' => 30,
            'is_featured' => 1,
        ],
        [
            'media_type' => 'image',
            'title' => 'Clear overhead lines',
            'caption' => 'A compact track-support detail above cabinetry.',
            'category' => 'Track Mounts',
            'media_path' => '/assets/img/after.jpg',
            'alt_text' => 'Overhead track support above fitted cabinets',
            'sort_order' => 40,
            'is_featured' => 0,
        ],
        [
            'media_type' => 'image',
            'title' => 'Opener mounting detail',
            'caption' => 'A close-up of the motor support and ceiling attachment.',
            'category' => 'Opener Mounts',
            'media_path' => '/assets/img/tm-tms-install.jpg',
            'alt_text' => 'Close view of an overhead opener mount in a garage',
            'sort_order' => 50,
            'is_featured' => 0,
        ],
        [
            'media_type' => 'image',
            'title' => 'The finishing detail',
            'caption' => 'The chrome ring around a track mount at the ceiling.',
            'category' => 'Hardware Details',
            'media_path' => '/assets/img/tm-chrome-ring.jpg',
            'alt_text' => 'Track support with a chrome finishing ring at the ceiling',
            'sort_order' => 60,
            'is_featured' => 0,
        ],
    ];

    public static function ensureReady(): void
    {
        if (self::$ready) {
            return;
        }
        self::$ready = true;

        try {
            if (!Database::tableExists('gallery_items')) {
                if (!Database::isSqlite()) {
                    Database::pdo()->exec(
                        "CREATE TABLE IF NOT EXISTS gallery_items (
                          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                          media_type ENUM('image','video') NOT NULL DEFAULT 'image',
                          video_source VARCHAR(20) NOT NULL DEFAULT '',
                          title VARCHAR(190) NOT NULL,
                          caption VARCHAR(500) NOT NULL DEFAULT '',
                          category VARCHAR(80) NOT NULL DEFAULT 'Installations',
                          media_path VARCHAR(500) NOT NULL,
                          embed_url VARCHAR(500) NOT NULL DEFAULT '',
                          poster_path VARCHAR(500) NOT NULL DEFAULT '',
                          alt_text VARCHAR(255) NOT NULL DEFAULT '',
                          sort_order INT NOT NULL DEFAULT 0,
                          is_featured TINYINT(1) NOT NULL DEFAULT 0,
                          is_active TINYINT(1) NOT NULL DEFAULT 1,
                          created_at DATETIME NOT NULL,
                          updated_at DATETIME NOT NULL,
                          PRIMARY KEY (id),
                          KEY idx_gallery_active_sort (is_active, is_featured, sort_order)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                    );
                } else {
                    Database::pdo()->exec(
                        "CREATE TABLE IF NOT EXISTS gallery_items (
                          id INTEGER PRIMARY KEY AUTOINCREMENT,
                          media_type TEXT NOT NULL DEFAULT 'image',
                          video_source VARCHAR(20) NOT NULL DEFAULT '',
                          title VARCHAR(190) NOT NULL,
                          caption VARCHAR(500) NOT NULL DEFAULT '',
                          category VARCHAR(80) NOT NULL DEFAULT 'Installations',
                          media_path VARCHAR(500) NOT NULL,
                          embed_url VARCHAR(500) NOT NULL DEFAULT '',
                          poster_path VARCHAR(500) NOT NULL DEFAULT '',
                          alt_text VARCHAR(255) NOT NULL DEFAULT '',
                          sort_order INT NOT NULL DEFAULT 0,
                          is_featured INTEGER(1) NOT NULL DEFAULT 0,
                          is_active INTEGER(1) NOT NULL DEFAULT 1,
                          created_at TEXT NOT NULL,
                          updated_at TEXT NOT NULL
                        )"
                    );
                }
            }

            if ((string) Settings::get('gallery.seeded', '0') !== '1') {
                $existingCount = (int) Database::value('SELECT COUNT(*) FROM gallery_items');
                if ($existingCount === 0) {
                    $ts = now();
                    foreach (self::DEFAULT_ITEMS as $item) {
                        Database::insert('gallery_items', [
                            'media_type' => $item['media_type'],
                            'video_source' => '',
                            'title' => $item['title'],
                            'caption' => $item['caption'],
                            'category' => $item['category'],
                            'media_path' => $item['media_path'],
                            'embed_url' => '',
                            'poster_path' => '',
                            'alt_text' => $item['alt_text'],
                            'sort_order' => $item['sort_order'],
                            'is_featured' => $item['is_featured'],
                            'is_active' => 1,
                            'created_at' => $ts,
                            'updated_at' => $ts,
                        ]);
                    }
                }
                Settings::set('gallery.seeded', '1', 'gallery');
            }
        } catch (Throwable $e) {
            Logger::error('GalleryService initialization failed', ['error' => $e->getMessage()]);
        }
    }

    public function publicItems(?string $mediaType = null): array
    {
        self::ensureReady();
        $where = ['is_active = 1'];
        $params = [];
        if ($mediaType !== null && in_array($mediaType, ['image', 'video'], true)) {
            $where[] = 'media_type = ?';
            $params[] = $mediaType;
        }
        $rows = Database::select(
            'SELECT * FROM gallery_items WHERE ' . implode(' AND ', $where) . ' ORDER BY sort_order ASC, is_featured DESC, id ASC',
            $params
        );
        return array_map([$this, 'decorate'], $rows);
    }

    public function adminItems(array $filters = []): array
    {
        self::ensureReady();
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filters['type']) && in_array($filters['type'], ['image', 'video'], true)) {
            $where[] = 'media_type = ?';
            $params[] = $filters['type'];
        }
        if (isset($filters['status']) && $filters['status'] === 'hidden') {
            $where[] = 'is_active = 0';
        } elseif (isset($filters['status']) && $filters['status'] === 'active') {
            $where[] = 'is_active = 1';
        }
        if (!empty($filters['q'])) {
            $like = '%' . trim((string) $filters['q']) . '%';
            $where[] = '(title LIKE ? OR caption LIKE ? OR category LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        $rows = Database::select(
            'SELECT * FROM gallery_items WHERE ' . implode(' AND ', $where) . ' ORDER BY sort_order ASC, id DESC',
            $params
        );
        return array_map([$this, 'decorate'], $rows);
    }

    public function counts(): array
    {
        self::ensureReady();
        $rows = Database::select('SELECT media_type, is_active, COUNT(*) AS total FROM gallery_items GROUP BY media_type, is_active');
        $out = ['all' => 0, 'image' => 0, 'video' => 0, 'hidden' => 0];
        foreach ($rows as $row) {
            $cnt = (int) $row['total'];
            $out['all'] += $cnt;
            if ($row['media_type'] === 'video') {
                $out['video'] += $cnt;
            } else {
                $out['image'] += $cnt;
            }
            if ((int) $row['is_active'] === 0) {
                $out['hidden'] += $cnt;
            }
        }
        return $out;
    }

    public function find(int $id): ?array
    {
        self::ensureReady();
        $row = Database::first('SELECT * FROM gallery_items WHERE id = ?', [$id]);
        return $row ? $this->decorate($row) : null;
    }

    public function decorate(array $item): array
    {
        $isVideo = ($item['media_type'] ?? 'image') === 'video';
        $item['is_video'] = $isVideo;
        $item['lightbox_type'] = !$isVideo
            ? 'image'
            : (!empty($item['embed_url']) ? 'embed' : 'video');
        $item['lightbox_href'] = !$isVideo
            ? upload_url((string) $item['media_path'])
            : (!empty($item['embed_url']) ? (string) $item['embed_url'] : upload_url((string) $item['media_path']));

        $poster = trim((string) ($item['poster_path'] ?? ''));
        if ($poster !== '') {
            $item['thumb_url'] = str_starts_with($poster, 'https://') ? $poster : upload_url($poster);
        } elseif (!$isVideo) {
            $item['thumb_url'] = upload_url((string) $item['media_path']);
        } else {
            $item['thumb_url'] = '';
        }

        $sourceLabel = match ((string) ($item['video_source'] ?? '')) {
            'youtube' => 'YouTube',
            'vimeo' => 'Vimeo',
            'upload' => 'Video',
            'direct' => 'Video',
            default => $isVideo ? 'Video' : 'Photo',
        };
        $item['source_label'] = $sourceLabel;
        return $item;
    }

    /**
     * Validate and normalize a YouTube, Vimeo, or direct MP4/WebM URL.
     *
     * @return ?array{video_source:string,media_path:string,embed_url:string,auto_poster:string}
     */
    public static function parseVideoUrl(string $rawUrl): ?array
    {
        $url = trim($rawUrl);
        if ($url === '') {
            return null;
        }

        // Local uploaded/asset MP4 or WebM path
        if ((str_starts_with($url, '/uploads/') || str_starts_with($url, '/assets/'))
            && preg_match('/\.(mp4|webm|mov)$/i', $url)) {
            return [
                'video_source' => 'upload',
                'media_path' => $url,
                'embed_url' => '',
                'auto_poster' => '',
            ];
        }

        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $query = (string) ($parts['query'] ?? '');

        // YouTube URLs
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            $videoId = '';
            if ($host === 'youtu.be' || $host === 'www.youtu.be') {
                $segments = array_values(array_filter(explode('/', trim($path, '/'))));
                $videoId = $segments[0] ?? '';
            } else {
                parse_str($query, $qParams);
                if (!empty($qParams['v']) && is_string($qParams['v'])) {
                    $videoId = $qParams['v'];
                } elseif (preg_match('#^/(?:embed|shorts|live)/([a-zA-Z0-9_-]{11})#', $path, $m)) {
                    $videoId = $m[1];
                }
            }
            if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
                return [
                    'video_source' => 'youtube',
                    'media_path' => 'https://www.youtube.com/watch?v=' . $videoId,
                    'embed_url' => 'https://www.youtube-nocookie.com/embed/' . $videoId . '?rel=0',
                    'auto_poster' => 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
                ];
            }
            return null;
        }

        // Vimeo URLs
        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            if (preg_match('#/(?:video/|channels/[^/]+/|manage/videos/)?(\d{6,14})(?:/([a-zA-Z0-9]+))?#', $path, $m)) {
                $videoId = $m[1];
                $hash = $m[2] ?? '';
                if ($hash === '' && $query !== '') {
                    parse_str($query, $vParams);
                    if (!empty($vParams['h']) && is_string($vParams['h']) && preg_match('/^[a-zA-Z0-9]+$/', $vParams['h'])) {
                        $hash = $vParams['h'];
                    }
                }
                return [
                    'video_source' => 'vimeo',
                    'media_path' => 'https://vimeo.com/' . $videoId . ($hash !== '' ? '/' . $hash : ''),
                    'embed_url' => 'https://player.vimeo.com/video/' . $videoId . ($hash !== '' ? '?h=' . rawurlencode($hash) : ''),
                    'auto_poster' => '',
                ];
            }
            return null;
        }

        // Direct HTTPS MP4 / WebM URL
        if (preg_match('/\.(mp4|webm)(\?.*)?$/i', $url)) {
            return [
                'video_source' => 'direct',
                'media_path' => $url,
                'embed_url' => '',
                'auto_poster' => '',
            ];
        }

        return null;
    }

    public function save(array $data, array $files = [], ?int $id = null): array
    {
        self::ensureReady();
        $existing = $id !== null ? $this->find($id) : null;
        if ($id !== null && !$existing) {
            return ['ok' => false, 'errors' => ['general' => 'Gallery item not found.'], 'id' => null];
        }

        $validator = (new Validator($data, [
            'title' => 'Title',
            'caption' => 'Caption',
            'category' => 'Category',
            'alt_text' => 'Alt text',
            'sort_order' => 'Sort order',
        ]))->validate([
            'title' => 'required|max:190',
            'caption' => 'max:500',
            'category' => 'max:80',
            'alt_text' => 'max:255',
            'sort_order' => 'integer',
        ]);
        if ($validator->fails()) {
            return ['ok' => false, 'errors' => $validator->errors(), 'id' => null];
        }

        $mode = (string) ($data['source_mode'] ?? ($existing ? ($existing['media_type'] === 'video' ? ($existing['video_source'] === 'upload' ? 'video_upload' : 'video_url') : 'image') : 'image'));
        if (!in_array($mode, ['image', 'video_upload', 'video_url'], true)) {
            $mode = 'image';
        }

        $uploader = new UploadService();
        $mediaType = $mode === 'image' ? 'image' : 'video';
        $videoSource = '';
        $mediaPath = (string) ($existing['media_path'] ?? '');
        $embedUrl = (string) ($existing['embed_url'] ?? '');
        $posterPath = (string) ($existing['poster_path'] ?? '');

        if ($mode === 'image') {
            $imageFile = $files['image_file'] ?? null;
            if ($imageFile && ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded = $uploader->image($imageFile, 'gallery', 1920);
                if (!$uploaded['ok']) {
                    return ['ok' => false, 'errors' => ['image_file' => $uploaded['error']], 'id' => null];
                }
                if ($existing && $existing['media_path'] !== $uploaded['path']) {
                    $uploader->delete((string) $existing['media_path']);
                }
                $mediaPath = (string) $uploaded['path'];
            } elseif (!empty($data['existing_image_path'])) {
                $candidatePath = trim((string) $data['existing_image_path']);
                if (str_starts_with($candidatePath, '/assets/img/') || str_starts_with($candidatePath, '/uploads/')) {
                    $mediaPath = $candidatePath;
                }
            }

            if ($mediaPath === '' || ($existing && $existing['media_type'] === 'video' && $mediaPath === $existing['media_path'])) {
                return ['ok' => false, 'errors' => ['image_file' => 'Upload a JPG, PNG or WebP image for this gallery item.'], 'id' => null];
            }
            $embedUrl = '';
            $videoSource = '';
            if (!empty($data['remove_poster']) && $posterPath !== '') {
                $uploader->delete($posterPath);
                $posterPath = '';
            }
        } elseif ($mode === 'video_upload') {
            $videoFile = $files['video_file'] ?? null;
            if ($videoFile && ($videoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded = $uploader->video($videoFile, 'gallery');
                if (!$uploaded['ok']) {
                    return ['ok' => false, 'errors' => ['video_file' => $uploaded['error']], 'id' => null];
                }
                if ($existing && $existing['media_path'] !== $uploaded['path']) {
                    $uploader->delete((string) $existing['media_path']);
                }
                $mediaPath = (string) $uploaded['path'];
            } elseif (!$existing || $existing['video_source'] !== 'upload') {
                return ['ok' => false, 'errors' => ['video_file' => 'Choose an MP4 or WebM video file to upload (up to 50 MB).'], 'id' => null];
            }
            $videoSource = 'upload';
            $embedUrl = '';
        } else {
            // video_url (YouTube, Vimeo, or direct HTTPS MP4/WebM)
            $rawUrl = trim((string) ($data['video_url'] ?? ($existing && $existing['media_type'] === 'video' ? $existing['media_path'] : '')));
            $parsed = self::parseVideoUrl($rawUrl);
            if ($parsed === null) {
                return [
                    'ok' => false,
                    'errors' => ['video_url' => 'Enter a valid YouTube, Vimeo, or direct HTTPS .mp4/.webm video link.'],
                    'id' => null,
                ];
            }
            if ($existing && $existing['video_source'] === 'upload') {
                $uploader->delete((string) $existing['media_path']);
            }
            $videoSource = $parsed['video_source'];
            $mediaPath = $parsed['media_path'];
            $embedUrl = $parsed['embed_url'];
            if (($posterPath === '' || str_starts_with($posterPath, 'https://i.ytimg.com/')) && $parsed['auto_poster'] !== '') {
                $posterPath = $parsed['auto_poster'];
            }
        }

        // Optional custom poster / thumbnail upload for videos
        if ($mediaType === 'video') {
            if (!empty($data['remove_poster']) && $posterPath !== '') {
                $uploader->delete($posterPath);
                $posterPath = '';
                if ($mode === 'video_url') {
                    $parsed = self::parseVideoUrl($mediaPath);
                    if ($parsed && $parsed['auto_poster'] !== '') {
                        $posterPath = $parsed['auto_poster'];
                    }
                }
            }
            $posterFile = $files['poster_file'] ?? null;
            if ($posterFile && ($posterFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploadedPoster = $uploader->image($posterFile, 'gallery', 1600);
                if (!$uploadedPoster['ok']) {
                    return ['ok' => false, 'errors' => ['poster_file' => $uploadedPoster['error']], 'id' => null];
                }
                if ($posterPath !== '' && str_starts_with($posterPath, '/uploads/')) {
                    $uploader->delete($posterPath);
                }
                $posterPath = (string) $uploadedPoster['path'];
            }
        }

        $title = trim((string) $data['title']);
        $altText = trim((string) ($data['alt_text'] ?? ''));
        if ($altText === '') {
            $altText = $title;
        }
        $category = trim((string) ($data['category'] ?? ''));
        if ($category === '') {
            $category = 'Installations';
        }
        $sortOrder = ($data['sort_order'] ?? '') !== ''
            ? (int) $data['sort_order']
            : (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM gallery_items');

        $fields = [
            'media_type' => $mediaType,
            'video_source' => $videoSource,
            'title' => $title,
            'caption' => trim((string) ($data['caption'] ?? '')),
            'category' => $category,
            'media_path' => $mediaPath,
            'embed_url' => $embedUrl,
            'poster_path' => $posterPath,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'is_featured' => !empty($data['is_featured']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($id !== null) {
            Database::update('gallery_items', $fields, 'id = ?', [$id]);
            return ['ok' => true, 'errors' => [], 'id' => $id];
        }
        $fields['created_at'] = now();
        return ['ok' => true, 'errors' => [], 'id' => Database::insert('gallery_items', $fields)];
    }

    public function toggle(int $id): bool
    {
        self::ensureReady();
        $item = Database::first('SELECT id, is_active FROM gallery_items WHERE id = ?', [$id]);
        if (!$item) {
            return false;
        }
        $next = (int) $item['is_active'] === 1 ? 0 : 1;
        Database::execute('UPDATE gallery_items SET is_active = ?, updated_at = ? WHERE id = ?', [$next, now(), $id]);
        return true;
    }

    public function move(int $id, string $direction): bool
    {
        self::ensureReady();
        $current = Database::first('SELECT id, sort_order FROM gallery_items WHERE id = ?', [$id]);
        if (!$current) {
            return false;
        }
        $isUp = $direction === 'up';
        $neighbor = Database::first(
            $isUp
                ? 'SELECT id, sort_order FROM gallery_items WHERE (sort_order < ? OR (sort_order = ? AND id < ?)) ORDER BY sort_order DESC, id DESC LIMIT 1'
                : 'SELECT id, sort_order FROM gallery_items WHERE (sort_order > ? OR (sort_order = ? AND id > ?)) ORDER BY sort_order ASC, id ASC LIMIT 1',
            [(int) $current['sort_order'], (int) $current['sort_order'], $id]
        );
        if (!$neighbor) {
            return false;
        }
        $aOrder = (int) $current['sort_order'];
        $bOrder = (int) $neighbor['sort_order'];
        if ($aOrder === $bOrder) {
            $bOrder = $isUp ? $aOrder - 10 : $aOrder + 10;
        }
        Database::transaction(function () use ($current, $neighbor, $aOrder, $bOrder): void {
            $ts = now();
            Database::execute('UPDATE gallery_items SET sort_order = ?, updated_at = ? WHERE id = ?', [$bOrder, $ts, $current['id']]);
            Database::execute('UPDATE gallery_items SET sort_order = ?, updated_at = ? WHERE id = ?', [$aOrder, $ts, $neighbor['id']]);
        });
        return true;
    }

    public function delete(int $id): bool
    {
        self::ensureReady();
        $item = Database::first('SELECT * FROM gallery_items WHERE id = ?', [$id]);
        if (!$item) {
            return false;
        }
        $uploader = new UploadService();
        $uploader->delete((string) ($item['media_path'] ?? ''));
        $uploader->delete((string) ($item['poster_path'] ?? ''));
        Database::execute('DELETE FROM gallery_items WHERE id = ?', [$id]);
        return true;
    }

    /** @return array<int,string> */
    public function categories(array $items): array
    {
        $seen = [];
        foreach ($items as $item) {
            $cat = trim((string) ($item['category'] ?? ''));
            if ($cat !== '' && !in_array($cat, $seen, true)) {
                $seen[] = $cat;
            }
        }
        return $seen;
    }
}
