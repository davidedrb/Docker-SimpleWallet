-- ============================================================
-- 04_triggers.sql - Solo trigger
-- Eseguire dopo 02_functions.sql e 03_procedures.sql
-- e PRIMA di 06_test_data.sql (i dati di test si appoggiano ai trigger)
-- ============================================================
DELIMITER //

/* ---------- Nuovo utente ---------- */

/* Metodi di pagamento iniziali */
CREATE TRIGGER after_user_insert
    AFTER INSERT ON users
    FOR EACH ROW
BEGIN
    INSERT INTO payment_methods (user_id, name, type, is_active) VALUES
                                                                     (NEW.id, 'Contanti',          'Cash',           TRUE),
                                                                     (NEW.id, 'Carta di credito',  'Card',           TRUE),
                                                                     (NEW.id, 'Carta di debito',   'Card',           TRUE),
                                                                     (NEW.id, 'TWINT',             'Digital Wallet', TRUE),
                                                                     (NEW.id, 'Bonifico bancario', 'Bank Transfer',  TRUE),
                                                                     (NEW.id, 'PayPal',            'Digital Wallet', TRUE);
END //

/* Categorie iniziali */
CREATE TRIGGER after_user_insert_categories
    AFTER INSERT ON users
    FOR EACH ROW
    FOLLOWS after_user_insert
BEGIN
INSERT INTO categories (user_id, name, is_active) VALUES
                                                      (NEW.id, 'Stipendio',   TRUE),
                                                      (NEW.id, 'Alimentari',  TRUE),
                                                      (NEW.id, 'Ristoranti',  TRUE),
                                                      (NEW.id, 'Trasporti',   TRUE),
                                                      (NEW.id, 'Abbonamenti', TRUE),
                                                      (NEW.id, 'Casa',        TRUE),
                                                      (NEW.id, 'Shopping',    TRUE),
                                                      (NEW.id, 'Svago',       TRUE),
                                                      (NEW.id, 'Salute',      TRUE),
                                                      (NEW.id, 'Istruzione',  TRUE);
END //

/* ---------- Transazioni: spese mensili ---------- */

CREATE TRIGGER after_payment_insert
    AFTER INSERT ON payments
    FOR EACH ROW
BEGIN
    CALL recalc_monthly_expenses(NEW.user_id);
END //

CREATE TRIGGER after_payment_update
    AFTER UPDATE ON payments
    FOR EACH ROW
BEGIN
    CALL recalc_monthly_expenses(NEW.user_id);
    IF OLD.user_id <> NEW.user_id THEN
        CALL recalc_monthly_expenses(OLD.user_id);
END IF;
END //

CREATE TRIGGER after_payment_delete
    AFTER DELETE ON payments
    FOR EACH ROW
BEGIN
    CALL recalc_monthly_expenses(OLD.user_id);
END //

/* ---------- Transazioni: saldo ---------- */

CREATE TRIGGER after_payment_insert_balance
    AFTER INSERT ON payments
    FOR EACH ROW
    FOLLOWS after_payment_insert
BEGIN
CALL apply_balance_delta(NEW.user_id, payment_effect(NEW.type, NEW.amount, NEW.is_completed));
END //

CREATE TRIGGER after_payment_update_balance
    AFTER UPDATE ON payments
    FOR EACH ROW
    FOLLOWS after_payment_update
BEGIN
    /* annulla l'effetto della vecchia versione, poi applica quello nuovo */
CALL apply_balance_delta(OLD.user_id, -payment_effect(OLD.type, OLD.amount, OLD.is_completed));
CALL apply_balance_delta(NEW.user_id,  payment_effect(NEW.type, NEW.amount, NEW.is_completed));
END //

CREATE TRIGGER after_payment_delete_balance
    AFTER DELETE ON payments
    FOR EACH ROW
    FOLLOWS after_payment_delete
BEGIN
CALL apply_balance_delta(OLD.user_id, -payment_effect(OLD.type, OLD.amount, OLD.is_completed));
END //

/* ---------- Logs: utenti ---------- */

CREATE TRIGGER log_users_insert AFTER INSERT ON users FOR EACH ROW
    INSERT INTO logs (user_id, user_name, action)
    VALUES (COALESCE(@log_user_id, NEW.id),
            COALESCE(@log_user_name, CONCAT(NEW.first_name, ' ', NEW.last_name)),
            CONCAT('ha creato un nuovo utente (ID ', NEW.id, ')')) //

CREATE TRIGGER log_users_update AFTER UPDATE ON users FOR EACH ROW
BEGIN
    CALL log_field(NOT (OLD.email <=> NEW.email), 'email', 'un utente', NEW.id);
    CALL log_field(NOT (OLD.password <=> NEW.password), 'password', 'un utente', NEW.id);
    CALL log_field(NOT (OLD.first_name <=> NEW.first_name), 'first_name', 'un utente', NEW.id);
    CALL log_field(NOT (OLD.last_name <=> NEW.last_name), 'last_name', 'un utente', NEW.id);
    CALL log_field(NOT (OLD.spending_limit <=> NEW.spending_limit), 'spending_limit', 'un utente', NEW.id);
    CALL log_field(NOT (OLD.role <=> NEW.role), 'role', 'un utente', NEW.id);
    CALL log_flag(OLD.is_active, NEW.is_active, 'ha attivato', 'ha disattivato', 'un utente', NEW.id);
END //

CREATE TRIGGER log_users_delete AFTER DELETE ON users FOR EACH ROW
    CALL write_log(CONCAT('ha eliminato un utente (ID ', OLD.id, ')')) //

/* ---------- Logs: pagamenti ---------- */

CREATE TRIGGER log_payments_insert AFTER INSERT ON payments FOR EACH ROW
    CALL write_log(CONCAT('ha creato un nuovo pagamento (ID ', NEW.id, ')')) //

CREATE TRIGGER log_payments_update AFTER UPDATE ON payments FOR EACH ROW
BEGIN
    CALL log_field(NOT (OLD.payment_method_id <=> NEW.payment_method_id), 'payment_method_id', 'un pagamento', NEW.id);
    CALL log_field(NOT (OLD.category_id <=> NEW.category_id), 'category_id', 'un pagamento', NEW.id);
    CALL log_field(NOT (OLD.type <=> NEW.type), 'type', 'un pagamento', NEW.id);
    CALL log_field(NOT (OLD.amount <=> NEW.amount), 'amount', 'un pagamento', NEW.id);
    CALL log_field(NOT (OLD.description <=> NEW.description), 'description', 'un pagamento', NEW.id);
    CALL log_field(NOT (OLD.transaction_date <=> NEW.transaction_date), 'transaction_date', 'un pagamento', NEW.id);
    CALL log_field(NOT (OLD.is_completed <=> NEW.is_completed), 'is_completed', 'un pagamento', NEW.id);
    CALL log_flag(OLD.is_active, NEW.is_active, 'ha attivato', 'ha disattivato', 'un pagamento', NEW.id);
    IF OLD.is_active <=> NEW.is_active THEN
        CALL log_flag(OLD.is_trashed, NEW.is_trashed, 'ha spostato nel cestino', 'ha ripristinato dal cestino', 'un pagamento', NEW.id);
    END IF;
END //

/* ---------- Logs: categorie ---------- */

CREATE TRIGGER log_categories_insert AFTER INSERT ON categories FOR EACH ROW
    CALL write_log(CONCAT('ha creato una nuova categoria (ID ', NEW.id, ')')) //

CREATE TRIGGER log_categories_update AFTER UPDATE ON categories FOR EACH ROW
BEGIN
    CALL log_field(NOT (OLD.name <=> NEW.name), 'name', 'una categoria', NEW.id);
    CALL log_flag(OLD.is_active, NEW.is_active, 'ha attivato', 'ha disattivato', 'una categoria', NEW.id);
END //

/* ---------- Logs: metodi di pagamento ---------- */

CREATE TRIGGER log_payment_methods_insert AFTER INSERT ON payment_methods FOR EACH ROW
    CALL write_log(CONCAT('ha creato un nuovo metodo di pagamento (ID ', NEW.id, ')')) //

CREATE TRIGGER log_payment_methods_update AFTER UPDATE ON payment_methods FOR EACH ROW
BEGIN
    CALL log_field(NOT (OLD.name <=> NEW.name), 'name', 'un metodo di pagamento', NEW.id);
    CALL log_field(NOT (OLD.type <=> NEW.type), 'type', 'un metodo di pagamento', NEW.id);
    CALL log_flag(OLD.is_active, NEW.is_active, 'ha attivato', 'ha disattivato', 'un metodo di pagamento', NEW.id);
END //

/* ---------- Logs: abbonamenti ---------- */

CREATE TRIGGER log_subscriptions_insert AFTER INSERT ON subscriptions FOR EACH ROW
    CALL write_log(CONCAT('ha creato un nuovo abbonamento (ID ', NEW.id, ')')) //

CREATE TRIGGER log_subscriptions_update AFTER UPDATE ON subscriptions FOR EACH ROW
BEGIN
    CALL log_field(NOT (OLD.name <=> NEW.name), 'name', 'un abbonamento', NEW.id);
    CALL log_field(NOT (OLD.price <=> NEW.price), 'price', 'un abbonamento', NEW.id);
    CALL log_field(NOT (OLD.first_payment_date <=> NEW.first_payment_date), 'first_payment_date', 'un abbonamento', NEW.id);
    CALL log_field(NOT (OLD.frequency_months <=> NEW.frequency_months), 'frequency_months', 'un abbonamento', NEW.id);
    CALL log_flag(OLD.is_active, NEW.is_active, 'ha attivato', 'ha disattivato', 'un abbonamento', NEW.id);
END //

DELIMITER ;
