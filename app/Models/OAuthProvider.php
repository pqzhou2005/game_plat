<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthProvider extends Model
{
    protected $fillable = ['user_id', 'provider', 'provider_id', 'provider_data'];

    protected function casts(): array
    {
        return ['provider_data' => 'array'];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
