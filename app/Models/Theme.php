<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'is_default',
        'variables', 'custom_css', 'preview_image',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Scope: lấy theme đang active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Lấy theme đang active
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Kích hoạt theme này (tắt tất cả theme khác)
     */
    public function activate()
    {
        static::where('is_active', true)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
        
        // Clear cache
        cache()->forget('active_theme');
        cache()->forget('active_theme_css');
    }

    /**
     * Lấy 1 biến CSS từ JSON
     */
    public function getVariable($key, $default = null)
    {
        return $this->variables[$key] ?? $default;
    }

    /**
     * Render CSS variables block
     */
    public function generateCssVariables()
    {
        if (!$this->variables || !is_array($this->variables)) {
            return '';
        }

        $css = ":root {\n";
        foreach ($this->variables as $key => $value) {
            $cssVar = str_replace('_', '-', $key);
            $css .= "    --theme-{$cssVar}: {$value};\n";
        }
        $css .= "}\n";

        return $css;
    }

    /**
     * Render full CSS output (variables + custom CSS)
     */
    public function generateFullCss()
    {
        $css = "<style id=\"theme-variables\">\n";
        $css .= $this->generateCssVariables();
        
        if ($this->custom_css) {
            $css .= "\n/* Custom Theme CSS */\n";
            $css .= $this->custom_css . "\n";
        }
        
        $css .= "</style>\n";

        return $css;
    }
}
