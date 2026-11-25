-- ============================================================
-- 05_events.sql - Eventi pianificati
-- Eseguire dopo 03_procedures.sql
--
-- Serve lo scheduler attivo (su XAMPP/MariaDB di solito e' spento).
-- Per renderlo permanente: event_scheduler=ON in my.ini sotto [mysqld].
-- ============================================================

DELIMITER //

/* Genera il pagamento di ogni abbonamento attivo alla scadenza:
   first_payment_date + (n * frequency_months) mesi
   (1 = mensile, 3 = trimestrale, 6 = semestrale, 12 = annuale).
   Viene generata solo l'ultima scadenza raggiunta (non recupera quelle saltate).
   La descrizione deve essere 'Abbonamento: <nome>' per evitare i doppioni. */
CREATE EVENT generate_subscription_payments
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY)
DO
BEGIN
    INSERT INTO payments
        (user_id, payment_method_id, category_id, type, amount, description,
         transaction_date, is_completed, created_at, updated_at)
    SELECT d.user_id, d.method_id, d.category_id, 'Uscita', d.price,
           CONCAT('Abbonamento: ', d.name), d.due_date, 'Yes', NOW(), NOW()
    FROM (
        SELECT s.user_id, s.name, s.price,

               -- ultima scadenza raggiunta (oggi o precedente)
               DATE_ADD(s.first_payment_date,
                        INTERVAL FLOOR(TIMESTAMPDIFF(MONTH, s.first_payment_date, CURRENT_DATE)
                                       / s.frequency_months) * s.frequency_months MONTH) AS due_date,

               -- primo metodo di pagamento attivo dell'utente
               (SELECT pm.id FROM payment_methods pm
                 WHERE pm.user_id = s.user_id AND pm.is_active = 1
                 ORDER BY pm.id LIMIT 1) AS method_id,

               -- categoria "Abbonamenti" DELL'UTENTE se esiste, altrimenti la sua prima attiva
               (SELECT c.id FROM categories c
                 WHERE c.user_id = s.user_id AND c.is_active = 1
                 ORDER BY (c.name LIKE 'Abbonament%') DESC, c.id LIMIT 1) AS category_id
        FROM subscriptions s
        WHERE s.is_active = 1
          AND s.frequency_months > 0
          AND s.first_payment_date <= CURRENT_DATE
    ) d
    WHERE d.due_date <= CURRENT_DATE
      AND d.method_id IS NOT NULL
      AND d.category_id IS NOT NULL

      -- evita i doppioni se l'evento gira piu' volte o se il pagamento esiste gia'
      AND NOT EXISTS (
          SELECT 1 FROM payments p
          WHERE p.user_id = d.user_id
            AND p.description = CONCAT('Abbonamento: ', d.name)
            AND DATE(p.transaction_date) = d.due_date
      );
END //

/* Il primo di ogni mese ricalcola le spese mensili di tutti gli utenti.
   (Prima le azzerava soltanto: se l'evento degli abbonamenti girava alle 00:00
   del giorno 1, la spesa appena inserita veniva cancellata.) */
CREATE EVENT reset_monthly_expenses
ON SCHEDULE EVERY 1 MONTH
STARTS TIMESTAMP(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01'))
DO
    CALL recalc_monthly_expenses(NULL);
//

DELIMITER ;
