<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspacePresence extends Model
{

    protected $table = 'workspace_presence';

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'user_key',
        'user_name',
        'last_seen',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    protected $appends = ["device_type","browser"];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Определение устройства по User Agent
     */
    public function getDeviceTypeAttribute(): string
    {
        if (!$this->user_agent) return 'unknown';

        $ua = strtolower($this->user_agent);

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Определение браузера
     */
    public function getBrowserAttribute(): string
    {
        if (!$this->user_agent) return 'unknown';

        $ua = $this->user_agent;

        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'Edg')) return 'Edge';
        if (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) return 'Opera';

        return 'Other';
    }
}
