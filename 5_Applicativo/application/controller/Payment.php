<?php

namespace controller;

use DateTime;
use libs\Auth;
use libs\Flash;
use libs\Validator;
use models\Categories;
use models\Methods;
use models\Payments;
use models\Users;

/**
 * Storico e gestione dei pagamenti (transazioni) dell'utente loggato.
 *
 * Rotte:
 *   payment/index              storico con filtri e paginazione (15 per pagina)
 *   payment/show/<id>   (POST) dettaglio
 *   payment/create             form di creazione
 *   payment/store       (POST) salva il nuovo pagamento
 *   payment/edit/<id>   (POST) form di modifica
 *   payment/update/<id> (POST) salva le modifiche
 *   payment/delete/<id> (POST) elimina il pagamento (hard delete, come da documentazione)
 *
 * Ogni pagamento è visibile/modificabile solo dal suo proprietario (payments.user_id).
 */
class Payment
{
    private const PER_PAGE = 15;
    private Validator $validator;

    public function __construct()
    {
        Auth::requireLogin();
        $this->validator = new Validator();
    }

    public function index()
    {
        $user = Users::find($_SESSION['user']->id);

        $user_id = $user->id;

        if ($user->role === 'admin') {
            header('Location: ' . URL . 'administrator/stats');
            exit;
        }

        // Intervalli (date e importi): estremi inclusi, ogni estremo è facoltativo
        $dateMin   = $this->param('date_min');
        $dateMax   = $this->param('date_max');
        $amountMin = str_replace(',', '.', $this->param('amount_min'));
        $amountMax = str_replace(',', '.', $this->param('amount_max'));

        $filters = [
            // BUG FIX: ctype_digit(null) è deprecato da PHP 8.1 (e con ?category_id[]=x riceverebbe un array):
            // param() restituisce sempre una stringa
            'category_id'       => ctype_digit($this->param('category_id')) ? $this->param('category_id') : null,
            'payment_method_id' => ctype_digit($this->param('payment_method_id')) ? $this->param('payment_method_id') : null,
            'type'              => $this->validator->paymentsTypes($_GET['type'] ?? ''),
            'date_min'          => $this->validator->isValidDate($dateMin, 'Y-m-d') ? $dateMin : null,
            'date_max'          => $this->validator->isValidDate($dateMax, 'Y-m-d') ? $dateMax : null,
            'amount_min'        => $this->validator->checkMoneyValues($amountMin) ? $amountMin : null,
            'amount_max'        => $this->validator->checkMoneyValues($amountMax) ? $amountMax : null,
            'is_completed'      => $this->validator->paymentsStatus($_GET['is_completed'] ?? ''),
        ];

        $filterErrors = [];
        $queryFilters = $filters;

        if ($filters['date_min'] !== null && $filters['date_max'] !== null && $filters['date_min'] > $filters['date_max']) {
            $filterErrors['date'] = 'Data transazione: la data minima non può essere successiva alla data massima. Intervallo non applicato.';
            $queryFilters['date_min'] = $queryFilters['date_max'] = null;
        }
        if ($filters['amount_min'] !== null && $filters['amount_max'] !== null && (float) $filters['amount_min'] > (float) $filters['amount_max']) {
            $filterErrors['amount'] = "Importo: l'importo minimo non può essere maggiore dell'importo massimo. Intervallo non applicato.";
            $queryFilters['amount_min'] = $queryFilters['amount_max'] = null;
        }

        // Solo i pagamenti non cestinati: il filtro va messo prima del conteggio, altrimenti le pagine
        // includono anche quelli finiti nel cestino
        $query = Payments::getFilteredPayments($user_id, $queryFilters)
            ->where('is_active', TRUE)
            ->where('is_trashed', FALSE);

        $total       = (clone $query)->count();
        $totalPages  = max(1, (int) ceil($total / self::PER_PAGE));
        $currentPage = min(max(1, (int) $this->param('page', '1')), $totalPages);

        $payments = $query
            ->with(['category', 'payment_method'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->forPage($currentPage, self::PER_PAGE)
            ->get()
            ->all();

        $usedCategoryIds = Payments::where('user_id', $user_id)->distinct()->pluck('category_id')->all();
        $categories      = Categories::whereIn('id', $usedCategoryIds)->orderBy('name')->get()->all();

        $usedMethodIds = Payments::where('user_id', $user_id)->distinct()->pluck('payment_method_id')->all();
        $methods       = Methods::whereIn('id', $usedMethodIds)->orderBy('type')->orderBy('name')->get()->all();

        $success = Flash::get('success', null);
        $errors  = Flash::get('errors', []);

        $pageTitle = 'Storico';

        require 'application/views/shared/header.php';
        require 'application/views/payments/index.php';
        require 'application/views/shared/footer.php';
    }

    public function show()
    {
        $id = $_POST['payment_id'] ?? '';
        // BUG FIX: findOrFail() lancia un'eccezione se l'id non esiste, quindi "if (!$payment)" non scattava mai: uso find()
        $payment = Payments::find($id);

        if (!$payment) {
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        if ($payment->user_id != $_SESSION['user']->id) {
            Flash::set('error', 'Non hai accesso a questa pagina');
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        $pageTitle = 'Dettaglio pagamento';

        require 'application/views/shared/header.php';
        require 'application/views/payments/show.php';
        require 'application/views/shared/footer.php';
    }

    public function create()
    {
        $user_id = $_SESSION['user']->id;

        $categories = Categories::getCategoriesByUserId($user_id);
        $methods = Methods::getUserPaymentMethods($user_id);

        $errors  = Flash::get('errors', []);
        $old     = Flash::get('old', []);
        $success = Flash::get('success', null);

        $pageTitle = 'Nuovo pagamento';

        require 'application/views/shared/header.php';
        require 'application/views/payments/create.php';
        require 'application/views/shared/footer.php';
    }

    public function store($current = null)
    {
        $errors = [];

        $user_id     = $_SESSION['user']->id;
        $type        = $this->validator->sanitizeInput($_POST['type'] ?? '');
        $amount      = str_replace(',', '.', $this->validator->sanitizeInput($_POST['amount'] ?? ''));
        $rawDate     = $this->validator->sanitizeInput($_POST['transaction_date'] ?? '');
        $category_id  = $this->validator->sanitizeInput($_POST['category_id'] ?? '');
        $payment_method_id    = $this->validator->sanitizeInput($_POST['payment_method_id'] ?? '');
        $is_completed = $this->validator->sanitizeInput($_POST['is_completed'] ?? '');
        $description = $this->validator->sanitizeInput($_POST['description'] ?? '');

        if (!$this->validator->paymentsTypes($type)) {
            $errors['type'] = 'Seleziona il tipo di movimento';
        }
        if (!$this->validator->checkMoneyValues($amount) || (float) $amount <= 0) {
            $errors['amount'] = 'Importo non valido: inserisci un numero maggiore di zero con al massimo 2 decimali (es. 12.50)';
        }
        // Data: se vuota uso quella attuale, altrimenti accetta yyyy-MM-dd HH:mm oppure solo yyyy-MM-dd
        // (con la sola data l'ora resta quella del momento in cui si registra il pagamento)
        $transactionDate = date('Y-m-d H:i:s');

        if ($rawDate !== '') {
            $date = null;
            foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
                if ($this->validator->isValidDate($rawDate, $format)) {
                    $date = DateTime::createFromFormat($format, $rawDate);
                    break;
                }
            }
            if (!$date) {
                $errors['transaction_date'] = 'Data non valida: seleziona una data e un\'ora valide';
            } else {
                $transactionDate = $date->format('Y-m-d H:i:s');
            }
        }
        $category = ctype_digit($category_id) ? Categories::find($category_id) : null;
        $keepsCategory = $current && (int) $current->category_id === (int) $category_id;

        // BUG FIX: non si controllava che la categoria fosse dell'utente loggato
        if (!$category || (int) $category->user_id !== (int) $user_id || (!$category->is_active && !$keepsCategory)) {
            $errors['category_id'] = 'Seleziona una categoria valida';
        }
        $method = ctype_digit($payment_method_id) ? Methods::find($payment_method_id) : null;
        $keepsMethod = $current && (int) $current->payment_method_id === (int) $payment_method_id;

        // BUG FIX: non si controllava che il metodo fosse dell'utente loggato
        if (!$method || (int) $method->user_id !== (int) $user_id || (!$method->is_active && !$keepsMethod)) {
            $errors['payment_method_id'] = 'Seleziona un metodo di pagamento valido';
        }
        if (!$this->validator->paymentsStatus($is_completed)) {
            $errors['is_completed'] = 'Stato del pagamento non valido';
        }
        if ($description == '' || (!$this->validator->checkInputText($description) || !$this->validator->checkStringLength($description, 1000))) {
            $errors['description'] = 'La descrizione contiene caratteri non ammessi o è troppo lunga (massimo 1000 caratteri)';
        }

        // BUG FIX: il metodo di pagamento era sbagliato nel riferimento: method_id --> payment_method_id
        if ($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', [
                'user_id' => $user_id,
                'payment_method_id' => $payment_method_id,
                'category_id' => $category_id,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'transaction_date' => $rawDate !== '' ? $rawDate : date('Y-m-d\TH:i', strtotime($transactionDate)),
                'is_completed' => $is_completed
            ]);

            header('Location: ' . URL . 'payment/create');
            exit;
        }

        Payments::create([
            'user_id' => $user_id,
            'payment_method_id' => $payment_method_id,
            'category_id' => $category_id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'transaction_date' => $transactionDate,
            'is_completed' => $is_completed,
        ]);

        Flash::set('success', 'Pagamento aggiunto');

        header('Location: ' . URL . 'payment/index');
        exit;
    }

    public function edit()
    {
        $id = $_POST['payment_id'] ?? '';
        $user_id = $_SESSION['user']->id;

        $payment = Payments::find($id);

        // BUG FIX: mancava il controllo del proprietario: si poteva aprire la modifica del pagamento di un altro utente
        if(!$payment || $payment->user_id != $user_id) {
            Flash::set('error', 'Pagamento non trovato');
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        $categories = Categories::getCategoriesByUserId($user_id);
        $methods = Methods::getUserPaymentMethods($user_id);

        $errors = Flash::get('errors', []);
        $old    = Flash::get('old', []);
        $success = Flash::get('success', null);

        $pageTitle = 'Modifica pagamento';

        require 'application/views/shared/header.php';
        require 'application/views/payments/edit.php';
        require 'application/views/shared/footer.php';
    }

    public function update()
    {
        $id = $_POST['payment_id'] ?? '';
        $user_id = $_SESSION['user']->id;

        $pageTitle = 'Modifica pagamento';

        $errors = [];

        $old = [
            'user_id'      => $_SESSION['user']->id,
            'type'         => $this->validator->sanitizeInput($_POST['type'] ?? ''),
            'amount'       => str_replace(',', '.', $this->validator->sanitizeInput($_POST['amount'] ?? '')),
            'rawDate'      => $this->validator->sanitizeInput($_POST['transaction_date'] ?? ''),
            'category_id'  => $this->validator->sanitizeInput($_POST['category_id'] ?? ''),
            'method_id'    => $this->validator->sanitizeInput($_POST['payment_method_id'] ?? ''),
            'is_completed' => $this->validator->sanitizeInput($_POST['is_completed'] ?? ''),
            'description'  => $this->validator->sanitizeInput($_POST['description'] ?? '')
        ];

        if(!isset($old['type']) || $this->validator->paymentsTypes($old['type']) == '') {
            $errors['type'] = 'Seleziona il tipo di movimento';
        }
        // BUG FIX: come in store(), l'importo deve essere maggiore di zero
        if(!isset($old['amount']) || !$this->validator->checkMoneyValues($old['amount']) || (float) $old['amount'] <= 0) {
            $errors['amount'] = 'Importo non valido';
        }
        if(!isset($old['category_id']) || !$this->validator->checkCategory($old['user_id'], $old['category_id'])) {
            $errors['category_id'] = 'Categoria non valida';
        }
        // BUG FIX: la chiave era 'payment_method_id' ma in $old si chiama 'method_id': dava sempre errore
        if(!isset($old['method_id']) || !$this->validator->checkPaymentMethod($old['user_id'], $old['method_id'])) {
            $errors['payment_method_id'] = 'Metodo di pagamento non valido';
        }
        if(!isset($old['is_completed']) || !$this->validator->paymentsStatus($old['is_completed'])){
            $errors['is_completed'] = 'Stato non valido';
        }
        // BUG FIX: descrizione e data non venivano validate/aggiornate (come in store())
        if($old['description'] == '' || (!$this->validator->checkInputText($old['description']) || !$this->validator->checkStringLength($old['description'], 1000))) {
            $errors['description'] = 'La descrizione contiene caratteri non ammessi o è troppo lunga (massimo 1000 caratteri)';
        }

        // Data: se vuota resta quella già salvata, altrimenti accetta yyyy-MM-dd HH:mm oppure solo yyyy-MM-dd
        $transactionDate = null;

        if($old['rawDate'] !== '') {
            $date = null;
            foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d'] as $format) {
                if ($this->validator->isValidDate($old['rawDate'], $format)) {
                    $date = DateTime::createFromFormat($format, $old['rawDate']);
                    break;
                }
            }
            if(!$date) {
                $errors['transaction_date'] = 'Data non valida: seleziona una data e un\'ora valide';
            } else {
                $transactionDate = $date->format('Y-m-d H:i:s');
            }
        }

        // BUG FIX: findOrFail(...)->where(...) restituiva un query builder (sempre "vero") e il proprietario non veniva verificato
        $payment = Payments::where('id', $id)->where('user_id', $user_id)->first();

        if(!$payment) {
            Flash::set('error', 'Pagamento non trovato');
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        // BUG FIX: con errori di validazione il pagamento veniva salvato lo stesso.
        // La modifica si apre solo via POST (payment/edit), quindi si torna all'elenco che mostra gli errori
        if($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', $old);

            header('Location: ' . URL . 'payment/index');
            exit;
        }

        // BUG FIX: update($old) usava le chiavi 'method_id' e 'rawDate', che non sono colonne:
        // metodo di pagamento e data non venivano mai aggiornati
        $data = [
            'type'              => $old['type'],
            'amount'            => $old['amount'],
            'category_id'       => $old['category_id'],
            'payment_method_id' => $old['method_id'],
            'is_completed'      => $old['is_completed'],
            'description'       => $old['description'],
        ];
        if($transactionDate !== null) {
            $data['transaction_date'] = $transactionDate;
        }

        // Il try/catch resta solo sul salvataggio: gli header() sono fuori, così non vengono "catturati"
        try{
            $payment->update($data);
        }catch (\Throwable $th) {
            Flash::set('errors', ['generic' => 'errore server']);
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        Flash::set('success', 'Pagamento modificato');

        header('Location: ' . URL . 'payment/index');
        exit;
    }

    /** Il pagamento va nel cestino (soft delete, is_trashed = TRUE): controlla ID e proprietario. */
    public function delete()
    {
        $id = $_POST['payment_id'] ?? '';

        // BUG FIX: findOrFail() lanciava l'eccezione prima del controllo; in più non si verificava il proprietario
        $payment = Payments::find($id);

        if (!$payment || $payment->user_id != $_SESSION['user']->id) {
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        $payment->is_trashed = TRUE;
        $payment->save();

        Flash::set('success', 'Pagamento cestinato');
        header('Location: ' . URL . 'payment/index');
        exit;
    }

    /** Parametro GET come stringa (ignora eventuali array tipo ?type[]=x). */
    private function param(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }
}