<?php

namespace Tests\Feature;

use Tests\TestCase;

class LazyImageTest extends TestCase
{
    /**
     * Verify LazyImage.vue has the expected perf attributes.
     */
    public function test_lazy_image_component_has_loading_lazy_attribute(): void
    {
        $content = file_get_contents(resource_path('js/Components/LazyImage.vue'));

        $this->assertStringContainsString('loading="lazy"', $content, 'LazyImage must use native loading=lazy');
        $this->assertStringContainsString('decoding="async"', $content, 'LazyImage must decode async to avoid blocking main thread');
    }

    public function test_lazy_image_supports_srcset_responsive(): void
    {
        $content = file_get_contents(resource_path('js/Components/LazyImage.vue'));

        $this->assertStringContainsString('srcset', $content, 'LazyImage should support srcset for responsive images');
        $this->assertStringContainsString('sizes', $content, 'LazyImage should support sizes for responsive images');
    }

    public function test_lazy_image_has_error_fallback(): void
    {
        $content = file_get_contents(resource_path('js/Components/LazyImage.vue'));

        $this->assertStringContainsString('handleError', $content, 'LazyImage must handle image errors');
        $this->assertStringContainsString('errored', $content, 'LazyImage must show fallback on error');
    }

    public function test_lazy_image_has_skeleton_placeholder(): void
    {
        $content = file_get_contents(resource_path('js/Components/LazyImage.vue'));

        $this->assertStringContainsString('animate-pulse', $content, 'LazyImage should show pulse skeleton while loading');
        $this->assertStringContainsString('shimmer', $content, 'LazyImage should have shimmer animation');
    }

    public function test_marketplace_index_uses_lazy_image(): void
    {
        $content = file_get_contents(resource_path('js/Pages/Public/MarketplaceIndex.vue'));

        $this->assertStringContainsString("import LazyImage from '@/Components/LazyImage.vue'", $content);
        $this->assertStringContainsString('<LazyImage', $content);
    }

    public function test_marketplace_show_uses_lazy_image(): void
    {
        $content = file_get_contents(resource_path('js/Pages/Public/MarketplaceShow.vue'));

        $this->assertStringContainsString("import LazyImage from '@/Components/LazyImage.vue'", $content);
        $this->assertStringContainsString('<LazyImage', $content);
    }

    public function test_marketplace_compare_uses_lazy_image(): void
    {
        $content = file_get_contents(resource_path('js/Pages/Public/MarketplaceCompare.vue'));

        $this->assertStringContainsString("import LazyImage from '@/Components/LazyImage.vue'", $content);
        $this->assertStringContainsString('<LazyImage', $content);
    }
}
