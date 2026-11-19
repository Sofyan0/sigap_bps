<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardSetting extends Model
{
    protected $fillable = [
        'admin_name',
        'email',
        'institution',
        'employee_id',
        'email_ews_enabled',
        'weekly_iph_enabled',
    ];

    protected function casts(): array
    {
        return [
            'email_ews_enabled' => 'boolean',
            'weekly_iph_enabled' => 'boolean',
        ];
    }
}
