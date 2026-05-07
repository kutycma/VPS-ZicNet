<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id', 'provider_plan_id', 'group_id', 'name', 'type',
        'cpu_cores', 'ram_mb', 'disk_gb', 'bandwidth_mbps',
        'provider_price', 'selling_price', 'pricing_data',
        'status', 'description', 'sort_order',
        'provider_data',
    ];

    protected $casts = [
        'provider_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cpu_cores' => 'integer',
        'ram_mb' => 'integer',
        'disk_gb' => 'integer',
        'pricing_data' => 'array',
        'provider_data' => 'array',
    ];

    // === Relationships ===

    public function provider()
    {
        return $this->belongsTo(VpsProvider::class, 'provider_id');
    }

    public function group()
    {
        return $this->belongsTo(VpsPlanGroup::class, 'group_id');
    }

    public function instances()
    {
        return $this->hasMany(VpsInstance::class, 'plan_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'vps_plan_id');
    }

    // === Helpers ===

    /**
     * Thứ tự & nhãn các chu kỳ thanh toán
     */
    const BILLING_CYCLES = [
        'monthly'        => ['sort' => 1, 'label' => '1 Tháng',  'months' => 1],
        'twomonthly'     => ['sort' => 2, 'label' => '2 Tháng',  'months' => 2],
        'quarterly'      => ['sort' => 3, 'label' => '3 Tháng',  'months' => 3],
        'semi_annually'  => ['sort' => 4, 'label' => '6 Tháng',  'months' => 6],
        'annually'       => ['sort' => 5, 'label' => '1 Năm',    'months' => 12],
        'biennially'     => ['sort' => 6, 'label' => '2 Năm',    'months' => 24],
        'triennially'    => ['sort' => 7, 'label' => '3 Năm',    'months' => 36],
    ];

    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Lấy pricing_data đã sort theo thứ tự chu kỳ
     */
    public function getSortedPricingAttribute(): array
    {
        $data = $this->pricing_data ?? [];
        $sorted = [];
        foreach (self::BILLING_CYCLES as $key => $meta) {
            if (isset($data[$key])) {
                $sorted[$key] = $data[$key];
                $sorted[$key]['label'] = $meta['label']; // luôn dùng label chuẩn
                $sorted[$key]['sort'] = $meta['sort'];
            }
        }
        return $sorted;
    }

    /**
     * Giá hiển thị: ưu tiên monthly selling_price, nếu 0 thì lấy chu kỳ gần nhất
     */
    public function getDisplayPriceAttribute(): float
    {
        $pricing = $this->sorted_pricing;

        // Ưu tiên monthly
        if (!empty($pricing['monthly']['selling_price']) && $pricing['monthly']['selling_price'] > 0) {
            return (float) $pricing['monthly']['selling_price'];
        }

        // Fallback: selling_price field
        if ($this->selling_price > 0) {
            return (float) $this->selling_price;
        }

        // Fallback: chu kỳ có giá gần nhất (sort nhỏ nhất)
        foreach ($pricing as $cycle) {
            if (!empty($cycle['selling_price']) && $cycle['selling_price'] > 0) {
                return (float) $cycle['selling_price'];
            }
        }

        return 0;
    }

    /**
     * Nhãn chu kỳ của giá hiển thị
     */
    public function getDisplayPriceCycleAttribute(): string
    {
        $pricing = $this->sorted_pricing;

        if (!empty($pricing['monthly']['selling_price']) && $pricing['monthly']['selling_price'] > 0) {
            return '/tháng';
        }

        if ($this->selling_price > 0) {
            return '/tháng';
        }

        foreach ($pricing as $key => $cycle) {
            if (!empty($cycle['selling_price']) && $cycle['selling_price'] > 0) {
                return '/' . strtolower($cycle['label'] ?? $key);
            }
        }

        return '';
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->display_price, 0, ',', '.') . 'đ';
    }

    public function getRamGbAttribute()
    {
        return round($this->ram_mb / 1024, 1);
    }
}
