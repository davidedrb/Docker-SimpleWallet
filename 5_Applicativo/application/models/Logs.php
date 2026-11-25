<?php

namespace models;

use Illuminate\Database\Eloquent\Model;

// Le righe le scrivono i trigger del DB (vedi 04_triggers.sql): qui si leggono soltanto
class Logs extends Model
{
    protected $table = 'logs';
    public $timestamps = false;     // created_at lo imposta il DB (DEFAULT CURRENT_TIMESTAMP)

    // Log dal più recente, filtrati per nome utente (anche parziale) e intervallo di date (estremi inclusi)
    public static function getFilteredLogs($filters)
    {
        $data = Logs::orderBy('id', 'desc');

        if (!empty($filters['user_name'])) {
            $data->where('user_name', 'like', '%' . $filters['user_name'] . '%');
        }
        if (!empty($filters['date_min'])) {
            $data->whereDate('created_at', '>=', $filters['date_min']);
        }
        if (!empty($filters['date_max'])) {
            $data->whereDate('created_at', '<=', $filters['date_max']);
        }

        return $data;
    }
}