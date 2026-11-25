<?php

namespace controller;

use libs\Auth;
use libs\Flash;
use models\Payments;
use models\Users;
class Home
{
    private $payments;

    public function __construct()
    {
        Auth::requireLogin();
        $this->payments = new Payments();
    }

    public function index()
    {
        $user = Users::find($_SESSION['user']->id);
        // BUG FIX: se l'utente non esiste più $user è null e $user->id dava errore, il controllo seguente non serviva a niente
        $userId = $user->id ?? null;
        if (!$userId) {
            header('Location: ' . URL . 'authenticator/login');
            exit;
        }

        if ($user->role === 'admin') {
            header('Location: ' . URL . 'administrator/stats');
            exit;
        }

        $payments = Payments::getUserHomePayments($userId);

        $success = Flash::get('success', null);
        $errors  = Flash::get('errors', []);

        $pageTitle = 'Home';

        require 'application/views/shared/header.php';
        require 'application/views/dashboard/index.php';
        require 'application/views/shared/footer.php';
    }
}