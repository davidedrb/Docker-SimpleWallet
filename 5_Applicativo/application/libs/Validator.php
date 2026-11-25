<?php

namespace libs;

use DateTime;
use models\Categories;
use models\Payments;

class Validator
{
    private const PAYMENT_TYPE  = ['Entrata', 'Uscita'];
    private const COMPLETED_STATUS = ['Yes', 'No'];

    // Pulizia dell'input dagli spazi inseriti prima e dopo
    public function sanitizeInput(string $input): string
    {
        return trim($input);
    }

    //Controlla input di testo, caratteri ammessi --> \s, include spazi, tabulazioni e nuove righe
    public function checkInputText(string $val, int $min = 1): bool
    {
        if (!preg_match('/^[\p{L}\p{M}\p{N}\s.,:;\'!?()\/-]+$/u',$val)) {
            return false;
        }
        // Controlla non sia un input vuoto
        return mb_strlen($val) >= $min;
    }

    function checkCredentials(string $valore, int $min = 2, int $max = 100): bool
    {
        // Normalizza l'apostrofo tipografico (’) in quello normale (')
        $valore = str_replace('’', "'", trim($valore));

        $lunghezza = mb_strlen($valore);
        if ($lunghezza < $min || $lunghezza > $max) {
            return false;
        }

        return preg_match('/^\p{L}+(?:[ \'\-]\p{L}+)*$/u', $valore) === 1;
    }
    public function checkStringLength(string $val, int $max = 100): bool
    {
        return mb_strlen($val) <= $max;
    }

    // Controlla che un ID (da URL o da form) sia un intero positivo: "12" sì;
    // "abc", "0", "-3", "1.5", "1e3", " 7", array o null no.
    public function checkId($id): bool
    {
        if (is_int($id)) {
            return $id > 0;
        }
        return is_string($id) && preg_match('/^[1-9]\d{0,18}$/', $id) === 1;
    }

    // Controlla input numerico (balance o spending limit)
    public function checkMoneyValues(string $val): bool
    {
        if (!preg_match('/^-?\d{1,8}(\.\d{1,2})?$/', $val )){
            return false;
        }
        return true;
    }

    // Controllo dell'email
    public function checkEmail(string $email): bool
    {
        // Verifica il formato dell'email senza reger ma con validatore nativo PHP
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    //Controllo della password
    public function checkPassword(string $password): bool
    {
        /**
         Validazione con criteri
         Min 8 chars
         Min 1 maiuscola
         Min 1 minuscola
         Min 1 number
         Min 1 special char
         Max 72 chars (limite di bcrypt: oltre il 72° byte la password viene ignorata)
         */
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,72}$/', $password) === 1;
    }

    public function isValidDate(string $value, string $format)
    {
        $date = DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }

    public function paymentsTypes($type)
    {
        if (in_array($type, self::PAYMENT_TYPE, true)){
            return $type;
        }
        return '';
    }

    public function paymentsStatus($state)
    {
        if (in_array($state, self::COMPLETED_STATUS, true)){
            return $state;
        }
        return '';
    }

    public function checkCategory($user_id, $category_id)
    {
        return Categories::where('id', $category_id)
            ->where('user_id', $user_id)
            ->where('is_active', 1)
            ->exists();
    }

    public function checkPaymentMethod($user_id, $payment_method_id){
        if(!Payments::where('payment_method_id', $payment_method_id)->where('user_id', $user_id)->get()){
            return false;
        }
        return true;
    }
}