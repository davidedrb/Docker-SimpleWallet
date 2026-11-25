<?php

namespace controller;

use libs\Auth;
use libs\Flash;
use libs\Validator;
use models\Categories;
use models\Methods;
use models\Payments;
use models\Users;
class Trash
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

        // Un minimo più grande del massimo non ha senso: avviso l'utente e non applico quell'intervallo
        // ($filters resta com'è, così le caselle mantengono i valori scritti e si possono correggere)
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

        // Solo i pagamenti cestinati: il filtro va messo prima del conteggio, altrimenti le pagine
        // vengono calcolate su tutti i pagamenti dell'utente
        $query = Payments::getFilteredPayments($user_id, $queryFilters)
            ->where('is_active', '=', TRUE)
            ->where('is_trashed', '=', TRUE);

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

        $pageTitle = 'Cestino';

        require 'application/views/shared/header.php';
        require 'application/views/bin/index.php';
        require 'application/views/shared/footer.php';
    }

    public function show()
    {
        $id = $_POST['payment_id'] ?? '';
        // BUG FIX: findOrFail() lancia l'eccezione e "if (!$payment)" non scattava mai: uso find()
        $payment = Payments::find($id);

        if (!$payment) {
            Flash::set('error', 'Pagamento non trovato');
            header('Location: ' . URL . 'trash/index');
            exit;
        }

        if ($payment->user_id != $_SESSION['user']->id) {
            Flash::set('error', 'Non hai accesso a questa pagina');
            header('Location: ' . URL . 'trash/index');
            exit;
        }

        $pageTitle = 'Dettaglio pagamento';

        require 'application/views/shared/header.php';
        require 'application/views/bin/show.php';
        require 'application/views/shared/footer.php';
    }

    // Restore dei pagamenti cestinati
    public function restore()
    {
        $id = $_POST['payment_id'] ?? '';

        // BUG FIX: findOrFail() lancia l'eccezione e "if (!$payment)" non scattava mai: uso find()
        $payment = Payments::find($id);

        if (!$payment) {
            Flash::set('error', 'Pagamento non trovato');
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        $user_id = $_SESSION['user']->id;
        $user = Users::findOrFail($user_id);

        // BUG FIX: !== confronta anche il tipo: se user_id arriva dal DB come stringa l'utente veniva rifiutato anche se proprietario
        if ($user->id != $payment->user_id) {
            Flash::set('error', 'Non hai accesso a questa pagina');
            header('Location: ' . URL . 'payment/index');
            exit;
        }

        $payment->is_trashed = FALSE;
        $payment->save();

        Flash::set('success', 'Pagamento ripristinato');
        header('Location: ' . URL . 'trash/index');
        exit;
    }

    /** Eliminazione (i pagamenti hanno soft delete): controlla ID e proprietario. */
    public function delete()
    {
        $id = $_POST['payment_id'];

        try{
            $payment = Payments::findOrFail($id);

            $user_id = $_SESSION['user']->id;
            $user = Users::findOrFail($user_id);

            // BUG FIX: !== -> != (vedi restore())
            if ($user->role !== 'admin' && $user->id != $payment->user_id) {
                header('Location: ' . URL . 'trash/index');
                exit;
            }

            require 'application/views/shared/header.php';
            require 'application/views/bin/confirm-delete.php';
            require 'application/views/shared/footer.php';

        }catch (\Throwable $e){
            Flash::set('errors', $e->getMessage());
            header('Location: ' . URL . 'trash/index');
            exit();
        }

    }

    public function finalDelete(){
        $id = $_POST['payment_id'];
        $restore = $_POST['restore'] ?? '';   // BUG FIX: chiave mancante -> Undefined array key

        try{
            $payment = Payments::findOrFail($id);

            $user_id    = $_SESSION['user']->id;
            $user = Users::findOrFail($user_id);

            // BUG FIX: !== -> != (vedi restore())
            if($user->role !== 'admin' && $user->id != $payment->user_id){
                Flash::set('error', 'Non hai accesso a questa pagina');
                header('Location: ' . URL . 'trash/index');
                exit;
            }

            /**
             * Riaccredita i soldi sul conto totale
             * Rimuove il pagamento dalle spese mensili solo se appartiene al mese corrente e all'anno corrente
             */
            if($restore === 'TRUE' && $payment->is_completed == 'Yes'){
                // BUG FIX: l'importo nel DB è sempre positivo. Ripristinando una Uscita si riaccredita il saldo,
                // ma un'Entrata va tolta (prima veniva sommata comunque). Verificare che sia coerente con i trigger.
                $isExpense = $payment->type === 'Uscita';
                $user->balance = $isExpense ? $user->balance + $payment->amount : $user->balance - $payment->amount;
                if ($isExpense && $payment->transaction_date && date('Y-m', strtotime($payment->transaction_date)) == date('Y-m')) {
                    // Visto che sarà negativo lo riaggiungo
                    $user->monthly_expenses = $user->monthly_expenses + $payment->amount;
                    $payment->is_counted = FALSE;
                }
                $user->save();
                $payment->save();
                $_SESSION['user'] = $user;
            }

            $payment->is_active = FALSE;
            $payment->is_trashed = FALSE;
            $payment->save();

            Flash::set('success', 'Pagamento eliminato');
            header('Location: ' . URL . 'trash/index');
            exit;

        }catch (\Throwable $e){
            Flash::set('errors', 'Utente o pagamento non trovato');
            header('Location: ' . URL . 'trash/index');
            exit();
        }
    }

    private function param(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }
}