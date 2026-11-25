<?php

namespace controller;

use models\Users;
use libs\Flash;
use libs\Validator;
use libs\Auth;
class Authenticator
{
    private Validator $validator;
    private Users $users;

    // Azioni:
    // Vedo form di login
    // Recupero errori e dati old
    public function __construct()
    {
        // La sessione è già avviata centralmente in libs\Application, non serve rifarlo qui.

        $this->validator = new Validator();
        $this->users = new Users();

    }

    //  login utente
    public function login(): void
    {
        if(Auth::isLogged()){
            header('Location: ' . URL . 'home/index');
            exit();
        }

        // Prendo errori e vecchi valori di sessione
        $errors = Flash::get('errors',  []);
        $old    = Flash::get('old',     []);

        // True / False se è loggato o no
        $isLogged = Auth::isLogged();

        $pageTitle = 'Accedi';
        require 'application/views/shared/header.php';
        require 'application/views/auth/login.php';
        require 'application/views/shared/footer.php';
    }

    /**
     * Elabora il form di login.
     *
     * Flusso:
     * 1) legge i dati dal POST
     * 2) valida email e password
     * 3) se errore -> salva in Flash + redirect (PRG)
     * 4) se ok -> verifica credenziali nel DB
     * 5) se login valido -> salva utente in sessione
     */

    // registrazione utente
    public function register(): void
    {
        if(Auth::isLogged()){
            header('Location: ' . URL . 'home/index');
            exit();
        }

        $errors = Flash::get('errors',  []);
        $old    = Flash::get('old',     []);

        $pageTitle = 'Registrati';

        require 'application/views/shared/header.php';
        require 'application/views/auth/signin.php';
        require 'application/views/shared/footer.php';
    }

    // autenticazione utente
    public function authenticate(): void
    {
        $errors = [];

        // Leggo i dati dell'utente dai campi del form
        $email = $this->validator->sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Valido email
        if (!$this->validator->checkEmail($email)) {
            $errors['email'] = 'Email non valida';
        }

        // Verifico errori di validazione
        if ($errors) {
            Flash::set('errors',            $errors);
            Flash::set('old', ['email' =>   $email]);

            // Torna al form login
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }

        // Controllo le credenziali nel DB
        // Email è UNIQUE, pertanto prendo il primo risultato
        $user = $this->users->login($email, $password);
        if(!$user){
            Flash::set('errors',    ['login' => 'Credenziali errate']);
            Flash::set('old',       ['email' => $email]);

            // Redirect al login
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }
        $user = $this->users->userInactive($email);
        if (!$user) {
            Flash::set('errors',    ['login' => 'Account inesistente']);
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }

        // Rigenero l'id di sessione prima di scriverci l'utente loggato,
        // per evitare session fixation.
        session_regenerate_id(true);
        $_SESSION['user'] = $user;

        // Redirect alla home
        header('Location: ' . URL . 'home/index');
        exit;
    }

    // logout utente
    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . URL . 'authenticator/login');
        exit;
    }
}