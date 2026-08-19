<?php

namespace AiElementorAgent\MCP\Tools;

use AiElementorAgent\MCP\AbstractTool;
use AiElementorAgent\Security\AccessControlManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class StockImageSideloadTool
 * Search Unsplash, Pexels, or Pixabay stock photos and sideload them directly into the WordPress Media Library.
 */
class StockImageSideloadTool extends AbstractTool
{
    public function get_name(): string
    {
        return 'stock_image_sideload';
    }

    public function get_description(): string
    {
        return 'Search stock photos (Unsplash, Pexels, Pixabay) using saved API keys and sideload high-res images directly into the WP Media Library.';
    }

    public function get_schema(): array
    {
        return [
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'action' => [
                        'type'        => 'string',
                        'enum'        => ['search', 'sideload'],
                        'description' => 'Operation: "search" stock photos or "sideload" an image URL into Media Library.',
                    ],
                    'provider' => [
                        'type'        => 'string',
                        'enum'        => ['unsplash', 'pexels', 'pixabay'],
                        'description' => 'Stock photo provider (default: "unsplash").',
                    ],
                    'query' => [
                        'type'        => 'string',
                        'description' => 'Search keywords (e.g., "modern office", "technology hero").',
                    ],
                    'image_url' => [
                        'type'        => 'string',
                        'description' => 'Image URL to download and import into Media Library (required for sideload).',
                    ],
                    'filename' => [
                        'type'        => 'string',
                        'description' => 'Custom title/filename for imported media file.',
                    ],
                ],
                'required'   => ['action'],
            ],
        ];
    }

    public function execute(array $params, array $context = []): array
    {
        $action   = sanitize_text_field($params['action'] ?? '');
        $provider = sanitize_text_field($params['provider'] ?? 'unsplash');

        if (!current_user_can('upload_files')) {
            return $this->error('Permission denied. Capability upload_files required.');
        }

        if ($action === 'search') {
            $query = sanitize_text_field($params['query'] ?? 'business');
            return $this->search_stock_photos($provider, $query);
        }

        if ($action === 'sideload') {
            $url  = sanitize_url($params['image_url'] ?? '');
            $title = sanitize_text_field($params['filename'] ?? 'stock-photo-' . time());
            return $this->sideload_image_to_media($url, $title);
        }

        return $this->error('Invalid stock photo action.');
    }

    private function search_stock_photos(string $provider, string $query): array
    {
        $keys = AccessControlManager::get_api_keys();
        $api_key = $keys[$provider] ?? '';

        if (empty($api_key)) {
            // Fallback placeholder search
            return $this->success([
                'provider' => $provider,
                'query'    => $query,
                'notice'   => "No API key configured for {$provider}. Add your key in WP Admin > MCP Access Control for full high-res results.",
                'results'  => [
                    [
                        'id'          => 'demo-1',
                        'title'       => $query . ' showcase photo',
                        'thumb'       => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=400',
                        'full_res'    => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1600',
                        'author'      => 'Unsplash Demo',
                    ],
                    [
                        'id'          => 'demo-2',
                        'title'       => $query . ' modern visual',
                        'thumb'       => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400',
                        'full_res'    => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1600',
                        'author'      => 'Unsplash Demo',
                    ],
                ],
            ]);
        }

        // Live Provider Request
        $endpoint = '';
        $headers  = [];

        if ($provider === 'unsplash') {
            $endpoint = 'https://api.unsplash.com/search/photos?query=' . urlencode($query) . '&per_page=8';
            $headers  = ['Authorization' => 'Client-ID ' . $api_key];
        } elseif ($provider === 'pexels') {
            $endpoint = 'https://api.pexels.com/v1/search?query=' . urlencode($query) . '&per_page=8';
            $headers  = ['Authorization' => $api_key];
        } elseif ($provider === 'pixabay') {
            $endpoint = 'https://pixabay.com/api/?key=' . urlencode($api_key) . '&q=' . urlencode($query) . '&per_page=8';
        }

        $response = wp_remote_get($endpoint, ['headers' => $headers, 'timeout' => 10]);
        if (is_wp_error($response)) {
            return $this->error($response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $results = [];

        if ($provider === 'unsplash' && !empty($data['results'])) {
            foreach ($data['results'] as $item) {
                $results[] = [
                    'id'       => $item['id'],
                    'title'    => $item['alt_description'] ?? $query,
                    'thumb'    => $item['urls']['small'] ?? '',
                    'full_res' => $item['urls']['regular'] ?? $item['urls']['full'] ?? '',
                    'author'   => $item['user']['name'] ?? 'Unsplash Author',
                ];
            }
        } elseif ($provider === 'pexels' && !empty($data['photos'])) {
            foreach ($data['photos'] as $item) {
                $results[] = [
                    'id'       => $item['id'],
                    'title'    => $item['alt'] ?? $query,
                    'thumb'    => $item['src']['medium'] ?? '',
                    'full_res' => $item['src']['large2x'] ?? $item['src']['original'] ?? '',
                    'author'   => $item['photographer'] ?? 'Pexels Author',
                ];
            }
        } elseif ($provider === 'pixabay' && !empty($data['hits'])) {
            foreach ($data['hits'] as $item) {
                $results[] = [
                    'id'       => $item['id'],
                    'title'    => $item['tags'] ?? $query,
                    'thumb'    => $item['previewURL'] ?? '',
                    'full_res' => $item['largeImageURL'] ?? '',
                    'author'   => $item['user'] ?? 'Pixabay Author',
                ];
            }
        }

        return $this->success([
            'provider' => $provider,
            'query'    => $query,
            'count'    => count($results),
            'results'  => $results,
        ]);
    }

    private function sideload_image_to_media(string $url, string $title): array
    {
        if (empty($url)) {
            return $this->error('image_url is required for sideloading.');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return $this->error('Failed to download stock photo: ' . $tmp->get_error_message());
        }

        $file_array = [
            'name'     => sanitize_title($title) . '.jpg',
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, 0, $title);
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return $this->error($attachment_id->get_error_message());
        }

        return $this->success([
            'message'       => 'Stock photo sideloaded into WP Media Library successfully.',
            'attachment_id' => $attachment_id,
            'url'           => wp_get_attachment_url($attachment_id),
            'title'         => get_the_title($attachment_id),
        ]);
    }
}
