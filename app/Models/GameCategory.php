<?php
namespace App\Models;

use App\Enums\CommonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'sort', 'status'];

    protected function casts(): array
    {
        return ['status' => 'integer', 'sort' => 'integer'];
    }

    public function games(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Game::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', CommonStatus::ACTIVE);
    }
}
