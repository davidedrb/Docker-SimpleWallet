<?php

namespace models;

use Illuminate\Database\Eloquent\Model;

class Methods extends Model
{
    protected $table = 'payment_methods';
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'is_active'
    ];

    static function getUserPaymentMethods($id)
    {
        return Methods::where('user_id', $id)
            ->where('is_active', '=', 1)->get();
    }

    // Tipi di pagamento già presenti nei metodi dell'utente (es. Cash, Card, Digital Wallet, Bank Transfer):
    // suggerimenti per il campo "tipo"
    static function getUserMethodTypes($id)
    {
        return Methods::where('user_id', $id)
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all();
    }

    static function disablePaymentMethod($id)
    {
        return Methods::findOrFail($id)->update(['is_active' => 0]);
    }

    public function payments() {
        return $this->hasMany(Payments::class, 'payment_method_id');
    }
}