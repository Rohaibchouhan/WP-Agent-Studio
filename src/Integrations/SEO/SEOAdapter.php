<?php

namespace AiElementorAgent\Integrations\SEO;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SEOAdapter
 * Handles SEO meta optimization (titles, descriptions, focus keywords) and structured JSON-LD schema generation.
 */
class SEOAdapter
{
    /**
     * Detect active SEO provider plugin.
     */
    public function get_active_provider(): string
    {
        if (defined('RANK_MATH_VERSION')) {
            return 'rankmath';
        }
        if (defined('WPSEO_VERSION')) {
            return 'yoast';
        }
        if (defined('AIOSEO_VERSION')) {
            return 'aioseo';
        }
        return 'native';
    }

    /**
     * Set SEO meta for a post/page.
     */
    public function set_post_seo(int $post_id, array $seo_data): array
    {
        $provider = $this->get_active_provider();

        $title       = sanitize_text_field($seo_data['title'] ?? '');
        $description = sanitize_text_field($seo_data['description'] ?? '');
        $keyword     = sanitize_text_field($seo_data['focus_keyword'] ?? '');

        if ($provider === 'rankmath') {
            if ($title)       update_post_meta($post_id, 'rank_math_title', $title);
            if ($description) update_post_meta($post_id, 'rank_math_description', $description);
            if ($keyword)     update_post_meta($post_id, 'rank_math_focus_keyword', $keyword);
        } elseif ($provider === 'yoast') {
            if ($title)       update_post_meta($post_id, '_yoast_wpseo_title', $title);
            if ($description) update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
            if ($keyword)     update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword);
        } else {
            // Native fallback storage
            if ($title)       update_post_meta($post_id, '_wp_agent_seo_title', $title);
            if ($description) update_post_meta($post_id, '_wp_agent_seo_description', $description);
            if ($keyword)     update_post_meta($post_id, '_wp_agent_seo_keyword', $keyword);
        }

        return [
            'success'       => true,
            'post_id'       => $post_id,
            'provider'      => $provider,
            'title'         => $title,
            'description'   => $description,
            'focus_keyword' => $keyword,
        ];
    }

    /**
     * Generate structured JSON-LD schema markup.
     */
    public function generate_json_ld_schema(string $type, array $data): string
    {
        $type = ucfirst(strtolower($type));
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $type,
        ];

        switch ($type) {
            case 'Organization':
            case 'Localbusiness':
                $schema['@type'] = 'Organization';
                $schema['name']  = sanitize_text_field($data['name'] ?? get_bloginfo('name'));
                $schema['url']   = sanitize_url($data['url'] ?? get_site_url());
                if (!empty($data['logo'])) $schema['logo'] = sanitize_url($data['logo']);
                break;

            case 'Article':
                $schema['headline']      = sanitize_text_field($data['title'] ?? '');
                $schema['description']   = sanitize_text_field($data['description'] ?? '');
                $schema['datePublished'] = current_time('c');
                break;

            case 'Product':
                $schema['name']        = sanitize_text_field($data['name'] ?? '');
                $schema['description'] = sanitize_text_field($data['description'] ?? '');
                if (!empty($data['price'])) {
                    $schema['offers'] = [
                        '@type'         => 'Offer',
                        'price'         => (float) $data['price'],
                        'priceCurrency' => sanitize_text_field($data['currency'] ?? 'USD'),
                        'availability'  => 'https://schema.org/InStock',
                    ];
                }
                break;

            case 'Faqpage':
                $schema['@type'] = 'FAQPage';
                $mainEntity = [];
                if (!empty($data['questions']) && is_array($data['questions'])) {
                    foreach ($data['questions'] as $q) {
                        $mainEntity[] = [
                            '@type'          => 'Question',
                            'name'           => sanitize_text_field($q['question']),
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => wp_kses_post($q['answer']),
                            ],
                        ];
                    }
                }
                $schema['mainEntity'] = $mainEntity;
                break;
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
