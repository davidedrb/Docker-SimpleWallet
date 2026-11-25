<?php

namespace controller;

use libs\Validator;
use libs\Flash;
use libs\Auth;
use models\Logs;
use models\Payments;
use models\Users;

class Administrator
{
    private Validator $validator;

    private const PER_PAGE = 15;

    public function __construct()
    {
        Auth::requireLogin();
        $this->validator = new Validator();
    }

    public function stats()
    {
        $user = Users::find($_SESSION['user']->id);

        if ($user->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $users = Users::all();
        $payments = Payments::all();

        $activeUsers = $users
            ->where('is_active', true)
            ->count();
        $activePayments = $payments
            ->where('is_active', true)
            ->where('is_trashed', false)
            ->count();


        $inactiveUsers = $users
            ->where('is_active', false)
            ->count();
        $inactivePayments = $payments
            ->where('is_active', false)
            ->where('is_trashed', false)
            ->count();

        $trashedPayments = $payments
            ->where('is_active', true)
            ->where('is_trashed', true)
            ->count();
        $completedPayments = $payments
            ->where('is_completed', 'Yes')
            ->count();
        $uncompletedPayments = $payments
            ->where('is_completed', 'No')
            ->count();

        $totalUsers = $users->count();
        $totalPayments = $payments->count();


        $success = Flash::get('success', null);
        $errors = Flash::get('errors', []);

        $pageTitle = 'Amministrazione';

        require 'application/views/shared/header.php';
        require 'application/views/amministration/stats.php';
        require 'application/views/shared/footer.php';
    }

    public function archive()
    {
        $user = Users::find($_SESSION['user']->id);

        if ($user->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $role = $_GET['role'] ?? '';
        $isActive = $_GET['is_active'] ?? '';
        $allowedRoles = ['admin', 'user'];

        if (!in_array($role, $allowedRoles, true)) {
            $role = '';
        }
        if (!in_array($isActive, ['1', '0'], true)) {
            $isActive = '';
        }
        $query = Users::query();

        // Filtro ruolo
        if ($role !== '') {
            $query->where('role', $role);
        }

        // Filtro stato
        if ($isActive !== '') {
            $query->where('is_active', (int) $isActive);
        }

        // Conteggio dopo i filtri
        $total = (clone $query)->count();

        // Paginazione
        $totalPages = max(
            1,
            (int) ceil($total / self::PER_PAGE)
        );

        $currentPage = min(
            max(1, (int) ($_GET['page'] ?? 1)),
            $totalPages
        );;

        // Recupero utenti della pagina corrente
        $users = $query
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->forPage($currentPage, self::PER_PAGE)
            ->get()
            ->all();

        // Parametri da mantenere nella paginazione
        $queryParams = [
            'role' => $role,
            'is_active' => $isActive
        ];

        $queryParams = array_filter(
            $queryParams,
            fn($value) => $value !== ''
        );

        $success = Flash::get('success', null);
        $errors  = Flash::get('errors', []);

        $pageTitle = 'Archivio Utenti';

        require 'application/views/shared/header.php';
        require 'application/views/amministration/archive.php';
        require 'application/views/shared/footer.php';
    }

    public function logs()
    {
        $user = Users::find($_SESSION['user']->id);

        if ($user->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $success = Flash::get('success', null);
        $errors = Flash::get('errors', []);
        $dateMin  = $this->param('date_min');
        $dateMax  = $this->param('date_max');
        $userName = mb_substr($this->param('user_name'), 0, 100);

        $filters = [
            'user_name' => $userName,
            'date_min'  => $this->validator->isValidDate($dateMin, 'Y-m-d') ? $dateMin : null,
            'date_max'  => $this->validator->isValidDate($dateMax, 'Y-m-d') ? $dateMax : null,
        ];

        $filterErrors = [];
        $queryFilters = $filters;

        if ($filters['date_min'] !== null && $filters['date_max'] !== null && $filters['date_min'] > $filters['date_max']) {
            $filterErrors['date'] = 'Data: la data minima non può essere successiva alla data massima. Intervallo non applicato.';
            $queryFilters['date_min'] = $queryFilters['date_max'] = null;
        }

        $query = Logs::getFilteredLogs($queryFilters);

        $total = (clone $query)->count();

        // Paginazione
        $totalPages = max(
            1,
            (int) ceil($total / self::PER_PAGE)
        );

        $currentPage = min(
            max(1, (int) ($_GET['page'] ?? 1)),
            $totalPages
        );

        $pageTitle = 'Registro attività';
        $logs = $query
            ->forPage($currentPage, self::PER_PAGE)
            ->get()
            ->all();

        $pageTitle = 'Log';

        require 'application/views/shared/header.php';
        require 'application/views/amministration/logs.php';
        require 'application/views/shared/footer.php';
    }

    public function show()
    {
        $id = $_POST['user_id'] ?? '';

        if ($_SESSION['user']->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $user = Users::find($id);

        if (!$user) {
            Flash::set('errors', 'Utente non trovato');
            header('Location: ' . URL . 'administrator/archive');
            exit;
        }

        $pageTitle = 'Dettagli Utente';

        require 'application/views/shared/header.php';
        require 'application/views/amministration/show.php';
        require 'application/views/shared/footer.php';
    }

    public function create()
    {
        if ($_SESSION['user']->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $errors = Flash::get('errors', []);
        $old    = Flash::get('old', []);

        $pageTitle = 'Crea utente';

        require 'application/views/shared/header.php';
        require 'application/views/amministration/create-user.php';
        require 'application/views/shared/footer.php';
    }

    public function store()
    {
        if ($_SESSION['user']->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $errors = [];

        $first_name = $this->validator->sanitizeInput($_POST['first_name'] ?? '');
        $last_name  = $this->validator->sanitizeInput($_POST['last_name'] ?? '');
        $email      = mb_strtolower($this->validator->sanitizeInput($_POST['email'] ?? ''));
        $password   = $_POST['password'] ?? '';
        $conf_pwd   = $_POST['confirm_password'] ?? '';
        $role       = $_POST['role'] ?? 'user';

        // Validazione ruolo
        if (!in_array($role, ['user', 'admin'], true)) {
            $errors['role'] = 'Il ruolo selezionato non è valido';
        }

        if (!$this->validator->checkCredentials($first_name) || !$this->validator->checkStringLength($first_name)) {
            $errors['first_name'] = 'Il nome inserito ha un formato non valido';
        }

        if (!$this->validator->checkCredentials($last_name) || !$this->validator->checkStringLength($last_name)) {
            $errors['last_name'] = 'Il cognome inserito ha un formato non valido';
        }

        if (!$this->validator->checkEmail($email) || !$this->validator->checkStringLength($email)) {
            $errors['email'] = 'La mail inserita non è valida (massimo 100 caratteri), inserisci una email nel seguente formato: nome.cognome@example.com';
        }

        if (!$this->validator->checkPassword($password)) {
            $errors['password'] = 'La password non è valida o non rispetta i criteri di sicurezza. Inserisci una password di almeno 8 e al massimo 72 caratteri composta da 1 maiuscola, 1 minuscola, 1 numero, 1 carattere speciale.';
        }

        if ($conf_pwd !== $password) {
            $errors['conf_pwd'] = 'Le password non corrispondono';
        }

        if (!isset($errors['email']) && Users::userExists($email)) {
            $errors['email'] = 'Questa mail è gia stata utilizzata';
        }

        if ($errors) {
            Flash::set('errors', $errors);

            Flash::set('old', [
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'email'      => $email,
                'role'       => $role,
            ]);

            header('Location: ' . URL . 'administrator/create-user');
            exit;
        }

        Users::storeUser([
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'password'   => password_hash($password, PASSWORD_BCRYPT),
            'role'       => $role,
        ]);

        Flash::set('success', 'Utente creato con successo');

        header('Location: ' . URL . 'administrator/archive');
        exit;
    }

    public function edit()
    {
        $id = $_POST['user_id'] ?? $_SESSION['user']->id;

        if ($_SESSION['user']->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $user = Users::find($id);

        if (!$user) {
            Flash::set('errors', 'Utente non trovato');
            header('Location: ' . URL . 'administrator/archive');
            exit;
        }

        $pageTitle = 'Aggiorna Utente';

        require 'application/views/shared/header.php';
        require 'application/views/amministration/edit.php';
        require 'application/views/shared/footer.php';

    }

        public function update()
        {
            $id = $_POST['user_id'] ?? $_SESSION['user']->id;

            $errors = [];

            if ($_SESSION['user']->role !== 'admin') {
                Flash::set('errors', 'Non hai accesso a questa pagina');
                header('Location: ' . URL);
                exit;
            }

            $user = Users::find($id);

            if (!$user) {
                Flash::set('errors', 'Utente non trovato');
                header('Location: ' . URL . 'administrator/archive');
                exit;
            }

            $first_name     = $this->validator->sanitizeInput($_POST['first_name'] ?? '');
            $last_name      = $this->validator->sanitizeInput($_POST['last_name'] ?? '');
            $email          = $this->validator->sanitizeInput($_POST['email'] ?? '');
            $password       = $_POST['password'] ?? '';
            $conf_pwd       = $_POST['confirm_password'] ?? '';

            if ($user->role === 'admin') {
                $spending_limit = $user->spending_limit;
                $balance = $user->balance;
            } else {
                $spending_limit = $this->validator->sanitizeInput($_POST['spending_limit'] ?? '');
                $balance = $this->validator->sanitizeInput($_POST['balance'] ?? '');

                if(!$this->validator->checkMoneyValues($balance)) {
                    $errors['balance'] = 'Saldo non valido';
                }
                if (!$this->validator->checkMoneyValues($spending_limit)) {
                    $errors['spending_limit'] = 'Limite non valido';
                }
            }

            if ($password === '') {
                $password = $user->password;
                $needToHash = false;
            } else {
                $needToHash = true;
                if ($conf_pwd !== $password) {
                    $errors['conf_pwd'] = 'Le password non corrispondono';
                }
            }

            if (!$this->validator->checkCredentials($first_name) || !$this->validator->checkStringLength($first_name)) {
                $errors['first_name'] = 'Il nome inserito ha un formato non valido';
            }
            if (!$this->validator->checkCredentials($last_name) || !$this->validator->checkStringLength($last_name)) {
                $errors['last_name'] = 'Il cognome inserito ha un formato non valido';
            }
            if (!$this->validator->checkEmail($email) || !$this->validator->checkStringLength($email)) {
                $errors['email'] = 'La mail inserita non è valida (massimo 100 caratteri), inserisci una email nel seguente formato: nome.cognome@example.com';
            }
            if (
                $this->validator->checkEmail($email) &&
                $this->validator->checkStringLength($email)
            ) {
                $emailExists = Users::where('email', $email)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($emailExists) {
                    $errors['email'] = 'Questa email è già utilizzata da un altro utente.';
                }
            }

            if (!$this->validator->checkPassword($password)) {
                $errors['password'] = 'La password non è valida o non rispetta i criteri di sicurezza. Inserisci una password di almeno 8 e al massimo 72 caratteri composta da 1 maiuscola, 1 minuscola, 1 numero, 1 carattere speciale.';
            }

            if ($spending_limit < 0) {
                $errors['spending_limit'] = 'Inserire un limite positivo';
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
                header('Location: ' . URL . 'administrator/edit/' . $id);
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
            header('Location: ' . URL . 'administrator/archive');
            exit;
        }

    public function deactivate()
    {
        $id = $_POST['user_id'] ?? '';

        if ($_SESSION['user']->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $user = Users::find($id);

        if (!$user) {
            Flash::set('errors', 'Utente non trovato');
            header('Location: ' . URL . 'administrator/archive');
            exit;
        }

        if ($user->id == $_SESSION['user']->id) {
            Flash::set('errors', 'Non puoi disattivare il tuo account');
            header('Location: ' . URL . 'administrator/archive');
            exit;
        }

        $user->is_active = false;
        $user->save();

        Flash::set('success', 'Utente disattivato');
        header('Location: ' . URL . 'administrator/archive');
        exit;
    }

    public function activate()
    {
        $errors = [];

        $id = $_POST['user_id'] ?? '';

        if ($_SESSION['user']->role !== 'admin') {
            Flash::set('errors', 'Non hai accesso a questa pagina');
            header('Location: ' . URL);
            exit;
        }

        $user = Users::find($id);

        if(!$user) {
            Flash::set('errors', 'Utente non trovato');
            header('Location: ' . URL . 'administrator/archive');
            exit;
        }

        $user->is_active = true;
        $user->save();

        Flash::set('success', 'Utente riattivato');
        header('Location: ' . URL . 'administrator/archive');
        exit;
    }

    private function param(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }
}