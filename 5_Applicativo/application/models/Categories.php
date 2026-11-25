<?php

namespace models;

use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    protected $table = 'categories';
    protected $fillable = [
        'user_id',
        'name',
        'is_active',
    ];

    public static function getCategoriesByUserId($userId)
    {
        return Categories::where('user_id', '=', $userId)
            ->where('is_active', '=', 1)
            ->get();
    }

    /**
     * Soft delete: is_active = 1 record presente, is_active = 0 record "eliminato".
     * Uso:  Modello::active()->get()   |   $record->softDelete()
     */

    public function payments() {
        return $this->hasMany(Payments::class, 'category_id');
    }
}