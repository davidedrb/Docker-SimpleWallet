<?php

namespace models;

use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    protected $table = 'payments';
    protected $fillable = [
        'user_id',
        'payment_method_id',
        'category_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'is_completed',
        'is_active',
        'is_trashed',
        'is_counted',
    ];

    //funzioni di collegamento tra tabelle
    public static function getAllUserPayments($user_id){
        return $payments = Payments::where('user_id', '=', $user_id)
            ->orderBy('transaction_date', 'desc');
    }

    public static function getUserHomePayments($user_id){
        return $payments = Payments::where('user_id', '=', $user_id)
            ->where('is_active', TRUE)
            ->where('is_trashed', FALSE)
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();
    }

    public static function getFilteredPayments($user_id, $filters){
        $data = Payments::getAllUserPayments($user_id);

        if (!empty($filters['category_id'])) {
            $data->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['payment_method_id'])) {
            $data->where('payment_method_id', $filters['payment_method_id']);
        }
        if (!empty($filters['type'])) {
            $data->where('type', $filters['type']);
        }
        // Intervallo di date, estremi inclusi: se c'è solo il minimo prende da quel giorno in poi,
        // se c'è solo il massimo prende tutto fino a quel giorno
        if (!empty($filters['date_min'])) {
            $data->whereDate('transaction_date', '>=', $filters['date_min']);
        }
        if (!empty($filters['date_max'])) {
            $data->whereDate('transaction_date', '<=', $filters['date_max']);
        }
        // Intervallo di importi, estremi inclusi, sul valore con segno che vede l'utente:
        // nel DB l'importo è sempre positivo, ma le Uscite si mostrano (e quindi si filtrano) come negative.
        // isset e non empty: "0" è un valore valido
        $signedAmount = "(CASE WHEN type = 'Uscita' THEN -amount ELSE amount END)";
        if (isset($filters['amount_min'])) {
            $data->whereRaw("$signedAmount >= CAST(? AS DECIMAL(10,2))", [$filters['amount_min']]);
        }
        if (isset($filters['amount_max'])) {
            $data->whereRaw("$signedAmount <= CAST(? AS DECIMAL(10,2))", [$filters['amount_max']]);
        }
        if (!empty($filters['is_completed'])) {
            // Nel DB is_completed vale 'Yes' oppure 'No'
            $data->where('is_completed', $filters['is_completed']);
        }
        return $data;
    }

    // Totale speso (solo Uscite) per tipo di metodo di pagamento: [tipo => totale], per il grafico a torta
    public static function getPaymentMethodUsage($user_id)
    {
        return Payments::join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->where('payments.user_id', '=', $user_id)
            ->where('payments.is_active', '=', 1)
            ->where('payments.is_trashed', '=', 0)
            ->where('payments.type', '=', 'Uscita')
            ->where('payment_methods.user_id', '=', $user_id)
            ->where('payment_methods.is_active', '=', 1)
            ->groupBy('payment_methods.type')
            ->selectRaw('payment_methods.type AS method_type, SUM(payments.amount) AS total')
            ->pluck('total', 'method_type');
    }

    // Totale speso (solo Uscite) in ogni giorno del mese corrente: [giorno => totale], per il grafico lineare
    public static function getMonthlyExpensesByDay($user_id)
    {
        return Payments::where('user_id', '=', $user_id)
            ->where('is_active', '=', 1)
            ->where('is_completed', '=', 1)
            ->where('is_trashed', '=', 0)
            ->where('type', '=', 'Uscita')
            ->whereYear('transaction_date', date('Y'))
            ->whereMonth('transaction_date', date('m'))
            ->groupBy('day_of_month')
            ->selectRaw('DAY(transaction_date) AS day_of_month, SUM(amount) AS total')
            ->pluck('total', 'day_of_month');
    }

    public function user(){
        return $this->belongsTo(Users::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(Methods::class);
    }

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function subscriptions() {
        return $this->belongsTo(Subscriptions::class);
    }
}