<?php

namespace controller;

use libs\Auth;
use libs\Validator;
use libs\Flash;
use models\Categories;

class Category
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
            exit;
        }

        $pageTitle = 'Categorie';
        $categories = Categories::getCategoriesByUserId($userId);

        require 'application/views/shared/header.php';
        require 'application/views/categories/index.php';
        require 'application/views/shared/footer.php';
    }

    public function create()
    {
        $userId = $_SESSION['user']->id;

        $errors  = Flash::get('errors', []);
        $old     = Flash::get('old', []);
        $success = Flash::get('success', null);

        $pageTitle = 'Crea Categoria';

        require 'application/views/shared/header.php';
        require 'application/views/categories/create.php';
        require 'application/views/shared/footer.php';
    }

    public function store()
    {
        $errors = [];

        $name = $this->validator->sanitizeInput($_POST["name"] ?? '');
        $isActive = 1;
        $userId = $_SESSION['user']->id;
        $enabled = false;

        if (!$this->validator->checkInputText($name) || !$this->validator->checkStringLength($name)) {
            $errors['name'] = ' Il nome della categoria non è valido, inseriscine uno con sole lettere e/o numeri (massimo 100 caratteri)';
        }

        /**Se esiste già una categoria con lo stesso nome ed è attiva viene generato
         * un errore e viene avvisato l'utente, se la categoria esiste ma è disabilitata
         * viene semplicemente riattivata
         */
        // BUG FIX: getCategoriesByUserId() restituisce solo le categorie ATTIVE, quindi "if(!$category->is_active)"
        // non scattava mai e una categoria disabilitata non veniva riattivata: leggo tutte quelle dell'utente
        $categories = Categories::where('user_id', $userId)->get();
        foreach ($categories as $category) {
            if ($category->name === $name) {
                if(!$category->is_active){
                    $category->update(['is_active' => 1]);
                    $enabled = true;
                }
                else{
                    $errors['duplicated_name'] = 'Categoria con nome ' . $category->name . ' è gia esistente';
                    break;
                }
            }
        }

        if ($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', [
                'name' => $name
            ]);

            header('Location: ' . URL . 'category/create');
            exit;
        }

        if(!$enabled){
            Categories::create([
                'user_id' => $userId,
                'name' => $name,
                'is_active' => $isActive
            ]);
        }

        Flash::set('success', 'Categoria creata con successo');
        header('Location: ' . URL . 'category/index');
        exit;
    }

    public function edit()
    {
        $userId = $_SESSION['user']->id;

        $id = $_POST['id_category'] ?? '';

        // BUG FIX: findOrFail() lancia l'eccezione e "if (!$category)" non scattava mai; in più non si verificava il proprietario
        $category = Categories::find($id);

        if (!$category || $category->user_id != $userId) {
            header('Location: ' . URL . 'category/index');
            exit;
        }

        $errors  = Flash::get('errors', []);
        $old     = Flash::get('old', []);
        $success = Flash::get('success', null);

        $pageTitle = 'Modifica categoria';

        require 'application/views/shared/header.php';
        require 'application/views/categories/edit.php';
        require 'application/views/shared/footer.php';
    }

    public function update()
    {
        $errors = [];

        $id = $_POST['id_category'];
        $category = Categories::find($id);

        // BUG FIX: non si controllava il proprietario (si poteva rinominare la categoria di un altro utente)
        if (!$category || $category->user_id != $_SESSION['user']->id) {
            header('Location: ' . URL . 'category/index');
            exit;
        }

        $name = $this->validator->sanitizeInput($_POST["name"] ?? '');
        $is_active = 1;

        // BUG FIX: in modifica non si controllava il nome duplicato (in store() sì)
        foreach (Categories::getCategoriesByUserId($category->user_id) as $other) {
            if ($other->id != $category->id && $other->name === $name) {
                $errors['duplicated_name'] = 'Categoria con nome ' . $other->name . ' è gia esistente';
                break;
            }
        }

        if (!$this->validator->checkInputText($name) || !$this->validator->checkStringLength($name)) {
            $errors['name'] = ' Il nome della categoria non è valido, inseriscine uno con sole lettere e/o numeri (massimo 100 caratteri)';
        }

        if ($errors) {
            Flash::set('errors', $errors);
            Flash::set('old', [
                'name' => $name
            ]);
            header('Location: ' . URL . 'category/edit/' . $id);
            exit;
        }

        $category->name = $name;
        $category->is_active = $is_active;
        $category->save();

        Flash::set('success', 'Categoria modificata con successo');
        header('Location: ' . URL . 'category/index');
        exit;
    }

    // Disabilita la categoria per le nuove transazioni ma mantiene i valori in quelle esistenti
    public function delete()
    {
        $id = $_POST['id_category'] ?? '';

        // BUG FIX: findOrFail() lancia l'eccezione e "if (!$category)" non scattava mai; in più non si verificava il proprietario
        $category = Categories::find($id);

        if (!$category || $category->user_id != $_SESSION['user']->id) {
            header('Location: ' . URL . 'category/index');
            exit;
        }

        $category->is_active = false;
        $category->save();

        Flash::set('success', 'Categoria eliminata con successo');
        header('Location: ' . URL . 'category/index');
        exit;
    }
}