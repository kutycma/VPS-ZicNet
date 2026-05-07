<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'department',
        'status',
        'priority',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }
    
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'open': return 'warning';
            case 'answered': return 'primary';
            case 'client-reply': return 'info';
            case 'resolved': return 'success';
            case 'closed': return 'secondary';
            default: return 'primary';
        }
    }
    
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'open': return 'Chờ xử lý';
            case 'answered': return 'Đã trả lời';
            case 'client-reply': return 'Khách phản hồi';
            case 'resolved': return 'Hoàn thành';
            case 'closed': return 'Đã đóng';
            default: return 'Không xác định';
        }
    }
}
