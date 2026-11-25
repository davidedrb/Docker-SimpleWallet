<?php

use models\Categories;
use models\Methods;
use models\Payments;
use models\Subscriptions;
use models\Users;

/**
 * Helper per creare i dati di prova nel DB e ripulirli a fine test.
 *
 * Ogni test lavora su utenti "usa e getta" (email unittest.<random>@example.com):
 * così non si toccano i dati reali (es. utente id 2) e il saldo/le spese mensili
 * modificati dai trigger non sporcano altri test.
 *
 * Uso: `use CreatesTestData;` nella classe di test e chiamare $this->cleanTestData() nel tearDown().
 */
trait CreatesTestData
{
    /** @var int[] */
    protected array $testUserIds = [];

    protected function createUser(array $attrs = []): Users
    {
        $user = Users::create(array_merge([
            'email'          => 'unittest.' . bin2hex(random_bytes(6)) . '@example.com',
            'password'       => password_hash('Password123!', PASSWORD_BCRYPT),
            'first_name'     => 'Unit',
            'last_name'      => 'Test',
            'spending_limit' => 500,
            'balance'        => 1000,
            'is_active'      => 1,
        ], $attrs));

        $this->testUserIds[] = $user->id;

        // fresh(): rilegge dal DB, così ci sono anche i default (role, monthly_expenses, ...)
        return $user->fresh();
    }

    protected function createCategory(int $userId, bool $active = true, string $name = 'UnitTest categoria'): Categories
    {
        return Categories::create(['user_id' => $userId, 'name' => $name, 'is_active' => $active ? 1 : 0]);
    }

    protected function createMethod(int $userId, bool $active = true): Methods
    {
        return Methods::create([
            'user_id'   => $userId,
            'name'      => 'UnitTest metodo',
            'type'      => 'Card',
            'is_active' => $active ? 1 : 0,
        ]);
    }

    /**
     * is_active e is_trashed non sono fillable in Payments: vengono impostati a parte con forceFill().
     */
    protected function createPayment(Users $user, Categories $category, Methods $method, array $attrs = []): Payments
    {
        $extra = array_intersect_key($attrs, array_flip(['is_active', 'is_trashed']));
        $attrs = array_diff_key($attrs, $extra);

        $payment = Payments::create(array_merge([
            'user_id'           => $user->id,
            'payment_method_id' => $method->id,
            'category_id'       => $category->id,
            'type'              => 'Uscita',
            'amount'            => 10.00,
            'description'       => 'UnitTest pagamento',
            'transaction_date'  => date('Y-m-d H:i:s'),
            'is_completed'      => 'Yes',
        ], $attrs));

        if ($extra) {
            $payment->forceFill($extra)->save();
        }

        return $payment->fresh();
    }

    protected function createSubscription(Users $user, array $attrs = []): Subscriptions
    {
        return Subscriptions::create(array_merge([
            'user_id'            => $user->id,
            'name'               => 'UnitTest abbonamento',
            'price'              => 9.90,
            'first_payment_date' => '2026-01-05',
            'frequency_months'   => 1,
            'is_active'          => 1,
        ], $attrs))->fresh();
    }

    /**
     * Elimina gli utenti di prova e tutti i loro dati (compresi quelli creati dai controller,
     * purché l'email dell'utente inizi con "unittest.").
     */
    protected function cleanTestData(): void
    {
        $ids = array_unique(array_merge(
            $this->testUserIds,
            Users::where('email', 'like', 'unittest.%@example.com')->pluck('id')->all()
        ));

        if (!$ids) {
            return;
        }

        // Prima i figli, poi l'utente (foreign key)
        Payments::whereIn('user_id', $ids)->delete();
        Subscriptions::whereIn('user_id', $ids)->delete();
        Categories::whereIn('user_id', $ids)->delete();
        Methods::whereIn('user_id', $ids)->delete();
        Users::whereIn('id', $ids)->delete();

        $this->testUserIds = [];
    }
}
