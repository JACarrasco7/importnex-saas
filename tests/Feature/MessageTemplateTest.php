<?php

namespace Tests\Feature;

use App\Models\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_resolve_placeholders(): void
    {
        $template = MessageTemplate::create([
            'name' => 'Greeting',
            'content' => 'Hello {{client}}, your car {{car}} is ready for {{price}}.',
            'language' => 'en',
            'placeholders' => ['client', 'car', 'price'],
        ]);

        $result = $template->resolvePlaceholders([
            'client' => 'John',
            'car' => 'BMW 3 Series',
            'price' => '25,000€',
        ]);

        $this->assertEquals(
            'Hello John, your car BMW 3 Series is ready for 25,000€.',
            $result
        );
    }

    public function test_empty_placeholders_replaced_with_empty_string(): void
    {
        $template = MessageTemplate::create([
            'name' => 'Greeting',
            'content' => 'Hello {{client}}, welcome.',
            'language' => 'en',
            'placeholders' => ['client'],
        ]);

        $result = $template->resolvePlaceholders([]);

        $this->assertEquals('Hello , welcome.', $result);
    }

    public function test_scope_language_filters_correctly(): void
    {
        MessageTemplate::create(['name' => 'ES 1', 'content' => 'X', 'language' => 'es']);
        MessageTemplate::create(['name' => 'EN 1', 'content' => 'Y', 'language' => 'en']);
        MessageTemplate::create(['name' => 'DE 1', 'content' => 'Z', 'language' => 'de']);

        $es = MessageTemplate::language('es')->count();
        $en = MessageTemplate::language('en')->count();
        $de = MessageTemplate::language('de')->count();

        $this->assertEquals(1, $es);
        $this->assertEquals(1, $en);
        $this->assertEquals(1, $de);
    }

    public function test_scope_category_filters_correctly(): void
    {
        MessageTemplate::create(['name' => 'A', 'content' => 'X', 'language' => 'en', 'category' => 'contact']);
        MessageTemplate::create(['name' => 'B', 'content' => 'Y', 'language' => 'en', 'category' => 'reminder']);

        $this->assertEquals(1, MessageTemplate::category('contact')->count());
        $this->assertEquals(1, MessageTemplate::category('reminder')->count());
    }

    public function test_placeholders_is_array(): void
    {
        $template = MessageTemplate::create([
            'name' => 'Test',
            'content' => 'Hello {{client}}',
            'language' => 'en',
            'placeholders' => ['client'],
        ]);

        $this->assertIsArray($template->placeholders);
        $this->assertEquals(['client'], $template->placeholders);
    }
}
