<?php

namespace models;

use Illuminate\Database\Eloquent\Model;

class Subscriptions extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'name',
        'price',
        'first_payment_date',  // nome corretto: vale quello che esiste davvero nel DB
        'frequency_months',
        'is_active'
    ];

    public static function getSubscriptionByUserId($user_id)
    {
        return Subscriptions::where('user_id', $user_id)->get();
    }

    public static function subscriptionExists($user_id, $name)
    {
        $subscription = Subscriptions::where('user_id', $user_id)->where('name', $name)->first();
        if ($subscription) {
            return true;
        }
        return false;
    }

    /**
     * funzioni di collegamento tra tabelle
     *
     * Accesso unico alla data del primo pagamento: nelle view si usa sempre
     * $subscription->first_payment_date, qualunque dei due nomi abbia la colonna nel DB.
     */

    public function user() {
        return $this->belongsTo(Users::class);
    }

    /**
     * Soft delete: is_active = 1 record presente, is_active = 0 record "eliminato".
     * Uso:  Modello::active()->get()   |   $record->softDelete()
     */

    public function payments() {
        return $this->hasMany(Payments::class);
    }
}