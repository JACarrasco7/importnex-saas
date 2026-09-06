<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $table = 'message_templates';

    protected $fillable = [
        'name', 'content', 'language', 'category', 'placeholders',
    ];

    protected $casts = [
        'placeholders' => 'array',
    ];

    public function scopeLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function resolvePlaceholders(array $data): string
    {
        $content = $this->content;

        foreach ($this->placeholders ?? [] as $placeholder) {
            $content = str_replace('{{' . $placeholder . '}}', $data[$placeholder] ?? '', $content);
        }

        return $content;
    }
}
