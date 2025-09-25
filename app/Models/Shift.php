<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'service',
        'customer_id',
        'employee_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        // start_time/end_time are TIME columns; keep as string
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? substr($value, 0, 5) : $value,
        );
    }
    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? substr($value, 0, 5) : $value,
        );
    }
}
