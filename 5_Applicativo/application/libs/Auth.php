<?php

namespace libs;

use Illuminate\Database\Capsule\Manager as Capsule;
use models\Users;

class Auth
{
    // Ritorna se lo user ha effettuato l'accesso
    // NB: $_SESSION['user'] è un model Eloquent (Users), non un array:
    // il vecchio controllo is_array() era sempre false e faceva
    // fallire il login-check anche per utenti già autenticati.
    public static function isLogged(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }

    // Ritorna un booleano (si attacca a isLogged() sopra)
    // Non è loggato? --> torna all'authenticazione
    public static function requireLogin(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }

        // Recupero l'utente aggiornato dal database
        $user = Users::find($_SESSION['user']->id);

        // Verifico che esista e sia attivo
        if (!$user || (int) $user->is_active !== 1) {
            self::logout();
            Flash::set('errors', 'Il tuo account è stato disattivato.');
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }

        // Aggiorno i dati della sessione
        $_SESSION['user'] = $user;

        // Dico al DB chi sta agendo: i trigger che scrivono la tabella logs usano queste variabili
        Capsule::connection()->statement(
            'SET @log_user_id = ?, @log_user_name = ?',
            [$user->id, $user->first_name . ' ' . $user->last_name]
        );
    }

    // Slogga l'utente pulendo la sessione (svuota SESSION)
    public static function logout(): void
    {
        // Svuoto i dati di sessione lato PHP
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            // Elimino anche il cookie di sessione lato browser, altrimenti
            // il vecchio session id resta valido per una nuova sessione
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
        }
    }
}