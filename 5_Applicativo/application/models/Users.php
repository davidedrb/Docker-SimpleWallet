<?php

namespace models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model{
    protected $table = 'users';
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'spending_limit',
        'balance',
        'is_active',
        'role'
    ];

    public static function login($email, $password){
        $user = Users::where('email', $email)->first();
        if($user){
            if(password_verify($password, $user->password)){
                return $user;
            }
        } else {
            // Email inesistente: faccio comunque un'operazione di hash dal costo equivalente a password_verify,
            // così il tempo di risposta non rivela se l'email è registrata o no.
            password_hash((string) $password, PASSWORD_BCRYPT);
        }
        return null;
    }

    public static function userInactive($email){
        $user = Users::where('email', $email)->first();
        if ($user){
            if (!$user->is_active){
                return FALSE;
            }
        }
        return $user;
    }

    public static function userExists($email)
    {
        $user = Users::where('email', $email)->first();
        if($user){
            return true;
        }
        return false;
    }

    public static function storeUser($data)
    {
        $user = Users::create($data);
        return $user->exists ? $user : null;
    }

    public function payments() {
        return $this->hasMany(Payments::class);
    }

    public function subscriptions() {
        return $this->hasMany(Subscriptions::class);
    }
}