<?php

namespace controller;

use Illuminate\Database\QueryException;
use libs\Validator;
use libs\Flash;
use libs\Auth;
use models\Users;
use function PHPUnit\Framework\exactly;

class User
{
    private Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    public function store()
    {
        $errors = [];
        // Prendo tutti i dati inseriti dall'utente e li sanifico eliminando eventuali spazi iniziali o finali

        // Tutti tranne per la password perché possono essere inseriti caratteri speciali e anche spazi
        $first_name = $this->validator->sanitizeInput($_POST['first_name'] ?? '');
        $last_name  = $this->validator->sanitizeInput($_POST['last_name'] ?? '');
        $email      = mb_strtolower($this->validator->sanitizeInput($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $conf_pwd = $_POST['confirm_password'] ?? '';

        // Validazione di tutti gli input
        if (!$this->validator->checkCredentials($first_name) || !$this->validator->checkStringLength($first_name)) {
            $errors['first_name'] = 'Il nome può contenere solo lettere';
        }
        if (!$this->validator->checkCredentials($last_name) || !$this->validator->checkStringLength($last_name)) {
            $errors['last_name'] = 'Il cognome può contenere solo lettere';
        }
        if (!$this->validator->checkEmail($email)) {
            $errors['email'] = 'La mail inserita non è valida, inserisci una email nel seguente formato: nome.cognome@example.com';
        }
        if (!$this->validator->checkPassword($password)) {
            $errors['password'] = 'La password non è valida o non rispetta i criteri di sicurezza. Inserisci una password di almeno 8 caratteri composta da 1 maiuscola, 1 minuscola, 1 numero, 1 carattere speciale.';
        }
        if ($conf_pwd !== $password) {
            $errors['conf_pwd'] = 'Le password non corrispondono';
        }

        // Salvataggio degli errori della sessione per inserirli nel form una volta eseguito il redirect
        if ($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email
            ]);

            header('Location: ' . URL . 'authenticator/register');
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        if(Users::userExists($email)) {
            $errors['email'] = 'Questa mail è gia stata utilizzata';
            Flash::set('errors', $errors);
            Flash::set('old', [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $password
            ]);

            header('Location: ' . URL . 'authenticator/register');
            exit;
        }

        $user = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $passwordHash,
        ];
        $userCreated = Users::storeUser($user);

        if($userCreated) {
            Flash::set('success', 'Utente creato con successo');
            Flash::set('old', [
                'email' => $email,
                'password' => $password
            ]);
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }

        // BUG FIX: qui $errors era vuoto, l'utente tornava al form senza nessun messaggio
        $errors['generic'] = 'Registrazione non riuscita, riprova';
        Flash::set('errors', $errors);
        Flash::set('old', [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $password,
        ]);
        header('Location: ' . URL . 'authenticator/register');
        exit;
    }

    public function edit()
    {
        Auth::requireLogin();

        $id = $_SESSION['user']->id;

        // Si può modificare solo il proprio account: con un ID non valido o di un altro utente
        // si viene riportati alla pagina del proprio account

        $errors  = Flash::get('errors', []);
        $old     = Flash::get('old', []);
        $success = Flash::get('success', null);

        $user = Users::find((int) $id);

        if (!$user) {
            header('Location: ' . URL);
            exit;
        }

        $pageTitle = 'Impostazioni profilo';
        require 'application/views/shared/header.php';
        require 'application/views/user/edit.php';
        require 'application/views/shared/footer.php';
    }

    public function update()
    {
        Auth::requireLogin();

        $id = $_SESSION['user']->id;
        $errors = [];

        $user = Users::find((int) $id);   // BUG FIX: findOrFail() lanciava l'eccezione e il controllo seguente era inutile
        if (!$user) {
            header('Location: ' . URL);
            exit;
        }

        $first_name     = $this->validator->sanitizeInput($_POST['first_name'] ?? '');
        $last_name      = $this->validator->sanitizeInput($_POST['last_name'] ?? '');
        $email          = mb_strtolower($this->validator->sanitizeInput($_POST['email'] ?? ''));   // BUG FIX: come in store(), email sempre minuscola
        $password       = $_POST['password'] ?? '';
        $conf_pwd       = $_POST['confirm_password'] ?? '';
        $spending_limit = $this->validator->sanitizeInput($_POST['spending_limit'] ?? '');
        $balance        = $this->validator->sanitizeInput($_POST['balance'] ?? '');

        if ($password === '') {
            $password = $user->password;
            $needToHash = false;
        } else {
            $needToHash = true;
            if ($conf_pwd !== $password) {
                $errors['conf_pwd'] = 'Le password non corrispondono';
            }
        }

        // BUG FIX: il controllo del limite era dentro l'else: veniva fatto solo se si cambiava la password
        if (!$this->validator->checkMoneyValues($spending_limit)) {
            $errors['spending_limit'] = 'Limite non valido';
        }

        if (!$this->validator->checkCredentials($first_name) || !$this->validator->checkStringLength($first_name)) {
            $errors['first_name'] = 'Il nome può contenere solo lettere';
        }
        if (!$this->validator->checkCredentials($last_name) || !$this->validator->checkStringLength($last_name)) {
            $errors['last_name'] = 'Il cognome può contenere solo lettere';
        }
        if (!$this->validator->checkEmail($email) || !$this->validator->checkStringLength($email)) {
            $errors['email'] = 'La mail inserita non è valida (massimo 100 caratteri), inserisci una email nel seguente formato: nome.cognome@example.com';
        }
        // BUG FIX: senza nuova password $password è l'hash già salvato: non va validato come se fosse una password in chiaro
        if ($needToHash && !$this->validator->checkPassword($password)) {
            $errors['password'] = 'La password non è valida o non rispetta i criteri di sicurezza. Inserisci una password di almeno 8 caratteri composta da 1 maiuscola, 1 minuscola, 1 numero, 1 carattere speciale.';
        }

        // BUG FIX: non si controllava che la nuova email non fosse già di un altro utente (errore del DB sull'UNIQUE)
        if (!isset($errors['email']) && Users::where('email', $email)->where('id', '!=', $id)->exists()) {
            $errors['email'] = 'Questa mail è gia stata utilizzata';
        }

        if ($spending_limit < 0) {
            $errors['spending_limit'] = 'Inserire un limite positivo';
        }
        if(!$this->validator->checkMoneyValues($balance)) {
            $errors['balance'] = 'Saldo non valido';
        }
        if (!$this->validator->checkMoneyValues($spending_limit)) {
            $errors['spending_limit'] = 'Limite non valido';
        }

        if ($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $password,
                'conf_pwd' => $conf_pwd,
                'spending_limit' => $spending_limit,
                'balance' => $balance
            ]);
            header('Location: ' . URL . 'user/edit/' . $id);
            exit;
        }

        if($needToHash){
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        } else {
            $passwordHash = $user->password;
        }

        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->email = $email;
        $user->password = $passwordHash;
        $user->spending_limit = $spending_limit;
        $user->balance = $balance;
        $user->save();

        Flash::set('success', 'Utente modificato con successo');
        $_SESSION['user'] = $user;

        header('Location: ' . URL);
        exit;
    }

    public function delete()
    {
        Auth::requireLogin();
        $id = $_SESSION['user']->id;

        $user = Users::find($id);

        if (!$user) {
            $_SESSION['error'] = 'Utente non trovato';
            header('Location: ' . URL);
            exit;
        }

        $errors  = Flash::get('errors', []);
        $old     = Flash::get('old', []);

        $pageTitle = 'Elimina Profilo';
        // Rimando l'utente alla verifica della password per eliminare l'account
        require 'application/views/shared/header.php';
        require 'application/views/user/confirm-delete.php';
        require 'application/views/shared/footer.php';
    }

    public function finalDelete()
    {
        Auth::requireLogin();

        $errors = [];

        $user = $_SESSION['user'];
        $id = $user->id;
        $password = $this->validator->sanitizeInput($_POST['password'] ?? '');

        // Ritorna [TRUE] se la password inserita e quella nel DB corrispondono
        if (!$this->verifyPasswordBeforeDelete($password) )
        {
            $errors['password'] = 'Password errata';
            Flash::set('errors', $errors);
            Flash::set('old', [
                'password' => $password
            ]);
            header('Location: ' . URL . 'user/delete');
            exit;
        }
        $user->is_active = FALSE;
        $user->save();

        // Chiudo la sessione dell'account appena eliminato
        // (la sessione viene distrutta: un messaggio Flash andrebbe perso)
        Auth::logout();

        //$_SESSION['success'] = "Utente eliminato con successo";
        header('Location: ' . URL . 'authenticator/login');
        exit;
    }

    public function verifyPasswordBeforeDelete($password): bool
    {
        Auth::requireLogin();
        /**
         * Per sicurezza andiamo a prendere la password direttamente
         * dal DB per evitare errori di sessione o manomissioni
         */
        $user = Users::where('id', '=', $_SESSION['user']->id)->first();
        if (password_verify($password, $user->password)) {
            return true;

        }
        return false;
    }

    /** True se $id è un ID valido ed è quello dell'utente loggato. */
    private function isOwnId($id): bool
    {
        return $this->validator->checkId($id)
            && (int) $id === (int) ($_SESSION['user']->id ?? 0);
    }
}