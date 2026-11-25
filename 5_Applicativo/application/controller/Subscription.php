<?php

namespace controller;

use DateTime;
use libs\Auth;
use libs\Flash;
use libs\Validator;
use models\Subscriptions;

/**
 * Gestione abbonamenti dell'utente loggato (CRUD con soft delete).
 *
 * Rotte:
 *   subscription/index | subscription/create | subscription/store (POST)
 *   subscription/edit/<id> | subscription/update/<id> (POST) | subscription/delete/<id> (POST)
 *
 * - Ogni abbonamento è visibile/modificabile/eliminabile solo dal proprietario (subscriptions.user_id).
 * - "Elimina" = soft delete (is_active = 0).
 */
class Subscription
{
    private Validator $validator;

    public function __construct()
    {
        Auth::requireLogin();
        $this->validator = new Validator();
    }

    public function index()
    {
        $userId = $_SESSION['user']->id;

        if (!$userId) {
            header('Location: ' . URL);
            exit();
        }

        $subscriptions = Subscriptions::getSubscriptionByUserId($userId);

        $pageTitle = 'Abbonamenti';

        require 'application/views/shared/header.php';
        require 'application/views/subscription/index.php';
        require 'application/views/shared/footer.php';
    }

    public function create()
    {
        $userId = $_SESSION['user']->id;

        $errors  = Flash::get('errors', []);
        $old     = Flash::get('old',    []);
        $success = Flash::get('success', null);

        $pageTitle = 'Crea Abbonamento';

        require 'application/views/shared/header.php';
        require 'application/views/subscription/create.php';
        require 'application/views/shared/footer.php';
    }

    public function store()
    {
        $errors = [];

        $userId = $_SESSION['user']->id;

        $name      = $this->validator->sanitizeInput($_POST['name'] ?? '');
        $price     = str_replace(',', '.', $this->validator->sanitizeInput((string) ($_POST['price'] ?? '')));
        $isActive  = 1;
        // BUG FIX: "(int)$x ?? ''" non protegge da chiave mancante (il cast viene prima di ??): Undefined array key
        $frequency = (int) ($_POST['frequency_months'] ?? 0); // Forzato con CAST
        $firstDate = trim((string) ($_POST['first_payment_date'] ?? ''));

        if (!$this->validator->checkInputText($name) || !$this->validator->checkStringLength($name)) {
            $errors['name'] = 'Il nome non è valido: usa lettere, numeri e punteggiatura semplice (massimo 100 caratteri)';
        }

        if (!$this->validator->checkMoneyValues($price) || (float) $price <= 0) {
            $errors['price'] = 'Prezzo non valido: inserisci un numero maggiore di zero con al massimo 2 decimali (es. 9.90)';
        }

        if (!$frequency) {
            $errors['frequency_months'] = 'Seleziona una frequenza valida';
        }

        // La data deve essere nel formato yyyy-MM-dd (es. 2026-01-05)
        $normalizedDate = $this->normalizeDate($firstDate);
        if ($normalizedDate === null) {
            $errors['first_payment_date'] = 'Inserisci la data nel formato yyyy-MM-dd, es: 2026-01-31';
        }

        if (Subscriptions::subscriptionExists($userId, $name)) {
            $errors['name'] = 'Il nome è già stato usato';
        }

        if ($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', [
                'name' => $name,
                'price' => $price,
                'first_payment_date' => $firstDate,
                'frequency_months' => $frequency,
                'is_active' => $isActive
            ]);

            header('Location: ' . URL . 'subscription/create');
            exit;
        }

        Subscriptions::create([
            'user_id' => $userId,
            'name' => $name,
            'price' => $price,
            'first_payment_date' => $normalizedDate,
            'frequency_months' => $frequency,
            'is_active' => $isActive
        ]);

        Flash::set('success', 'Abbonamento creato con successo');

        header('Location: ' . URL . 'subscription/index');
        exit();
    }

    public function edit()
    {
        $userId = $_SESSION['user']->id;

        $id = $_POST['id_subscription'] ?? '';


        $subscription = Subscriptions::find($id);

        // BUG FIX: mancava exit dopo il redirect (la pagina continuava a caricarsi con $subscription nullo)
        // e non si controllava il proprietario
        if (!$subscription || $subscription->user_id != $userId) {
            header('Location: ' . URL . 'subscription/index');
            exit;
        }

        $errors = Flash::get('errors', []);
        $old    = Flash::get('old', []);
        $success = Flash::get('success', null);

        $pageTitle = 'Modifica abbonamento';

        require 'application/views/shared/header.php';
        require 'application/views/subscription/edit.php';
        require 'application/views/shared/footer.php';
    }

    public function update()
    {
        $errors = [];

        $id = $_POST['id_subscription'] ?? '';
        // BUG FIX: findOrFail() lancia l'eccezione e "if (!$subscription)" non scattava mai; in più non si verificava il proprietario
        $subscription = Subscriptions::find($id);

        if (!$subscription || $subscription->user_id != $_SESSION['user']->id) {
            Flash::set('errors', 'Abbonamento non trovato');
            var_dump(Subscriptions::find($id));
            die;
            header('Location: ' . URL . 'subscription/index');
            exit;
        }

        $name      = $this->validator->sanitizeInput($_POST['name'] ?? '');
        $price     = str_replace(',', '.', $this->validator->sanitizeInput((string) ($_POST['price'] ?? '')));
        $frequency = (int) ($_POST['frequency_months'] ?? 0);   // BUG FIX: vedi store()
        $firstDate = trim((string) ($_POST['first_payment_date'] ?? ''));

        if (!$this->validator->checkInputText($name) || !$this->validator->checkStringLength($name)) {
            $errors['name'] = 'Il nome non è valido: usa lettere, numeri e punteggiatura semplice (massimo 100 caratteri)';
        }

        if (!$this->validator->checkMoneyValues($price) || (float) $price <= 0) {
            $errors['price'] = 'Prezzo non valido: inserisci un numero maggiore di zero con al massimo 2 decimali (es. 9.90)';
        }

        if (!$frequency) {
            $errors['frequency_months'] = 'Seleziona una frequenza valida';
        }

        // La data deve essere nel formato yyyy-MM-dd (es. 2026-01-05)
        $normalizedDate = $this->normalizeDate($firstDate);
        if ($normalizedDate === null) {
            $errors['first_payment_date'] = 'Inserisci la data nel formato yyyy-MM-dd, es: 2026-01-31';
        }
        if (Subscriptions::subscriptionExists($subscription->user_id, $name)) {
            $errors['name'] = 'Il nome è già stato usato';
        }
        if ($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', [
                'name' => $name,
                'price' => $price,
                'frequency_months' => $frequency,   // BUG FIX: la chiave era 'frequency', ma il form usa 'frequency_months'
                'first_payment_date' => $firstDate
            ]);

            header('Location: ' . URL . 'subscription/edit/' . $id);
            exit();
        }

        $subscription->name = $name;
        $subscription->price = $price;
        $subscription->first_payment_date = $normalizedDate;
        $subscription->frequency_months = $frequency;
        $subscription->save();

        Flash::set('success', 'Abbonamento modificato con successo');
        header('Location: ' . URL . 'subscription/index');
        exit();
    }

    public function delete()
    {
        $id = $_POST['id_subscription'] ?? '';

        // BUG FIX: findOrFail() lancia l'eccezione e "if (!$subscription)" non scattava mai; in più non si verificava il proprietario
        $subscription = Subscriptions::find($id);

        if (!$subscription || $subscription->user_id != $_SESSION['user']->id) {
            header('Location: ' . URL . 'subscription/index');
            exit;
        }

        $subscription->is_active = false;
        $subscription->save();

        Flash::set('success', 'Abbonamento eliminato con successo');

        header('Location: ' . URL . 'subscription/index');
        exit();
    }

    /** Accetta solo il formato yyyy-MM-dd (es. 2026-01-31); null se non valida. */
    private function normalizeDate(string $value): ?string
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }
}