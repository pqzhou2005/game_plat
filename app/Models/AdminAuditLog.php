<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $fillable = [
        'operator', 'action', 'target_type', 'target_id',
        'before_data', 'after_data', 'remark', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data' => 'array',
        ];
    }

    public static function record(
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $beforeData = null,
        ?array $afterData = null,
        ?string $remark = null,
    ): self {
        $operator = null;
        $ip = request()?->ip();

        if (auth('admin')->check()) {
            $operator = auth('admin')->user()->username;
        }

        return static::create([
            'operator' => $operator,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'remark' => $remark,
            'ip' => $ip,
        ]);
    }
}
