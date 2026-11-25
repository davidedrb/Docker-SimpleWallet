<?php


namespace controller;

use libs\Auth;
use libs\Flash;
use libs\Validator;
use models\Methods;

/**
 * Gestione metodi di pagamento (CRUD con soft delete).
 *
 * Rotte:
 *   method/index | method/create | method/store (POST)
 *   method/edit/<id> | method/update/<id> (POST) | method/delete/<id> (POST)
 *
 * - Come le categorie, i metodi sono collegati ai pagamenti (payments.payment_method_id), non agli utenti.
 * - "Elimina" = soft delete (is_active = 0): il metodo non è più proponibile ma i pagamenti esistenti lo mantengono.
 */
class Method
{
    private Validator $validator;

    public function __construct()
    {
        Auth::requireLogin();
        $this->validator = new Validator();
    }

    public function index(): void
    {
        $user_id = $_SESSION['user']->id;

        $methods = Methods::getUserPaymentMethods($user_id);

        $success = Flash::get('success', null);
        $errors  = Flash::get('errors', []);

        $pageTitle = 'Metodi di pagamento';
        require 'application/views/shared/header.php';
        require 'application/views/methods/index.php';
        require 'application/views/shared/footer.php';
    }

    public function create(): void
    {
        $errors = Flash::get('errors', []);
        $old    = Flash::get('old', []);

        $user = $_SESSION['user'];

        $typeSuggestions = Methods::getUserMethodTypes($user->id);

        $pageTitle = 'Nuovo metodo di pagamento';
        require 'application/views/shared/header.php';
        require 'application/views/methods/create.php';
        require 'application/views/shared/footer.php';
    }

    public function store(): void
    {

        $user_id = $_SESSION['user']->id;
        $methods = Methods::getUserPaymentMethods($user_id);

        $errors = [];
        // BUG FIX: $_POST['name'] / $_POST['type'] senza ?? davano Undefined array key se il campo mancava
        $old = [
            'user_id'   => $_SESSION['user']->id,
            'name'      => $this->validator->sanitizeInput($_POST['name'] ?? ''),
            'type'      => $this->validator->sanitizeInput($_POST['type'] ?? ''),
            'is_active' => 1,
        ];


        if(!$this->validator->checkInputText($old['name']) || !$this->validator->checkStringLength($old['name'])) {
            $errors['name'] = "nome non valido per il metodo di pagamento (massimo 100 caratteri)";
        }

        if(!$this->validator->checkInputText($old['type']) || !$this->validator->checkStringLength($old['type'])) {
            $errors['type'] = "tipo di pagamento non valido (massimo 100 caratteri)";
        }

        foreach ($methods as $method) {
            if($method->name == $old['name'] && $method->type == $old['type']) {
                $errors['generic'] = "metodo di pagamento esistente";
                break;
            }
        }

        if($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', $old);

            header('Location: ' . URL . 'method/create');
            exit();
        }

        try{
            Methods::create($old);
            Flash::set('success', 'Metodo di pagamento aggiunto');
        }catch (\Throwable $e){ //eccezione trowable diversa da una classica eccezone sollevata
            // BUG FIX: $errors qui è vuoto, l'utente non vedeva nessun messaggio
            Flash::set('errors', ['generic' => 'Impossibile salvare il metodo di pagamento']);
            Flash::set('old', $old);
            header('Location: ' . URL . 'method/create');
            exit();
        }

        header('Location: ' . URL . 'method/index');
        exit();
    }

    public function edit(): void
    {
        try {
            // BUG FIX: mancava il controllo del proprietario (come in update() e delete())
            $method = Methods::where('id', $_POST['PaymentMethodsId'] ?? 0)
                ->where('user_id', $_SESSION['user']->id)
                ->firstOrFail();

            $errors = Flash::get('errors', []);
            $old    = Flash::get('old', []);

            $typeSuggestions = Methods::getUserMethodTypes($_SESSION['user']->id);

            $pageTitle = 'Modifica metodo';

            require 'application/views/shared/header.php';
            require 'application/views/methods/edit.php';
            require 'application/views/shared/footer.php';
        }catch (\Throwable $e){
            error_log($e->getMessage());
            // BUG FIX: $method qui non esiste (findOrFail aveva appena fallito) e il messaggio dell'eccezione è tecnico
            Flash::set('errors', ['generic' => 'Metodo di pagamento non trovato']);
            Flash::set('old', $method);
            header('Location: ' . URL . 'method/index');
            exit();
        }
    }

    public function update(): void
    {
        $errors = [];

        try{
            $id = $_POST['PaymentMethodsId'];
            $user_id = $_SESSION['user']->id;

            $oldMethod = Methods::where('id', $id)
                ->where('user_id', $user_id)
                ->firstOrFail();
            $methods = Methods::getUserPaymentMethods($user_id);

            $new = [
                'user_id'   => $_SESSION['user']->id,
                'name'      => $this->validator->sanitizeInput($_POST['name'] ?? ''),   // BUG FIX: chiave mancante
                'type'      => $this->validator->sanitizeInput($_POST['type'] ?? ''),
                'is_active' => 1,
            ];
            if (!$this->validator->checkInputText($new['name']) || !$this->validator->checkStringLength($new['name'])) {
                $errors['name'] = "Nome non valido per il metodo di pagamento (massimo 100 caratteri)";
            }
            if (!$this->validator->checkInputText($new['type']) || !$this->validator->checkStringLength($new['type'])) {
                $errors['type'] = "Tipo di pagamento non valido (massimo 100 caratteri)";
            }

            foreach ($methods as $method) {
                if($method->id != $id && $method->name == $new['name'] && $method->type == $new['type']) {
                    $errors['generic'] = "Metodo di pagamento già esistente";
                    break;
                }
            }

            if(!empty($errors)) {
                Flash::set('errors', $errors);
                Flash::set('old', $new);
            } else {
                // BUG FIX: l'update veniva eseguito (e il successo segnalato) anche con errori di validazione
                $oldMethod->update($new);

                Flash::set('success', 'Metodo di pagamento aggiornato');
            }


        }catch (\Throwable $e){
            $errors['generic'] = $e->getMessage();
            Flash::set('errors', $errors);
            Flash::set('old', "");
            header('Location: ' . URL . 'method/edit');
            exit();
        }

        if(!empty($errors)) {
            header('Location: ' . URL . 'method/edit');
            exit();
        }

        header('Location: ' . URL . 'method/index');
        exit();
    }

    /** Soft delete: imposta is_active = 0 dopo aver controllato l'ID. */
    public function delete(): void
    {
        try{
            $id = $_POST['PaymentMethodsId'];

            $user_id = $_SESSION['user']->id;

            $method = Methods::where('id', $id)
                ->where('user_id', $user_id)
                ->firstOrFail();

            $method->is_active = false;
            $method->save();

        }catch (\Throwable $e){
            Flash::set('errors', $e->getMessage());
            header('Location: ' . URL . 'method/index');
            exit();
        }

        Flash::set('success', "Metodo di pagamento eliminato");
        header('Location: ' . URL . 'method/index');
        exit();
    }

    // ------------------------------------------------------------------ helper

    /** Legge e valida il form (name facoltativo, type obbligatorio). */
    private function readInput(): array
    {
        $errors = [];

        $name = $this->validator->sanitizeInput((string) ($_POST['name'] ?? ''));
        $type = $this->validator->sanitizeInput((string) ($_POST['type'] ?? ''));

        if ($name !== '' && (!$this->validator->checkInputText($name) || !$this->validator->checkStringLength($name))) {
            $errors['name'] = 'Il nome non è valido: usa lettere, numeri e punteggiatura semplice (massimo 100 caratteri)';
        }
        if (!$this->validator->checkInputText($type) || !$this->validator->checkStringLength($type)) {
            $errors['type'] = 'Il tipo non è valido: usa lettere, numeri e punteggiatura semplice (massimo 100 caratteri)';
        }

        $old  = ['name' => $name, 'type' => $type];
        $data = ['name' => $name !== '' ? $name : null, 'type' => $type];

        return [$errors, $old, $data];
    }

    private function findActive($id): ?Methods
    {
        if (!$this->validator->checkId($id)) {
            return null;
        }

        return Methods::where('is_active', 1)->find((int) $id);
    }

    private function findOrRedirect($id): Methods
    {
        $method = $this->findActive($id);

        if (!$method) {
            $this->redirect('method/index');
        }

        return $method;
    }

    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('method/index');
        }
    }

    private function redirect(string $route): void
    {
        header('Location: ' . URL . $route);
        exit;
    }
}