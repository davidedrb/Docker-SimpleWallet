-- ============================================================
-- 03_procedures.sql - Stored procedure
-- Eseguire dopo 01_schema.sql
-- ============================================================

DELIMITER //

/* Ricalcola le spese del mese corrente.
   p_user_id = id utente  -> ricalcola solo quell'utente
   p_user_id = NULL       -> ricalcola tutti gli utenti (usato dall'evento mensile) */
CREATE PROCEDURE recalc_monthly_expenses(IN p_user_id BIGINT)
BEGIN
    UPDATE users u
    SET u.monthly_expenses = (
        SELECT COALESCE(SUM(p.amount), 0)
        FROM payments p
        WHERE p.user_id = u.id
          AND p.type = 'Uscita'
          AND p.is_completed = 'Yes'
          AND p.is_counted = TRUE
          AND p.transaction_date >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY)
          AND p.transaction_date <  DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)
    )
    WHERE p_user_id IS NULL OR u.id = p_user_id;
END //

/* Applica una variazione al saldo dell'utente */
CREATE PROCEDURE apply_balance_delta(IN p_user_id BIGINT, IN p_delta DECIMAL(10,2))
BEGIN
    IF p_delta <> 0 THEN
        UPDATE users
        SET balance = balance + p_delta
        WHERE id = p_user_id;
    END IF;
END //

/* Scrive una riga di log a nome di chi sta agendo */
CREATE PROCEDURE write_log(IN p_action VARCHAR(255))
BEGIN
    IF @log_user_id IS NOT NULL THEN
        INSERT INTO logs (user_id, user_name, action)
        VALUES (@log_user_id, @log_user_name, p_action);
    END IF;
END //

/* "ha modificato il campo X di ..." se il valore è cambiato (i valori non finiscono mai nel log) */
CREATE PROCEDURE log_field(IN p_changed BOOLEAN, IN p_field VARCHAR(50), IN p_entity VARCHAR(50), IN p_id INT)
BEGIN
    IF p_changed THEN
        CALL write_log(CONCAT('ha modificato il campo ', p_field, ' di ', p_entity, ' (ID ', p_id, ')'));
    END IF;
END //

/* Campi sì/no (is_active, is_trashed): "ha attivato / ha disattivato ..." se il valore è cambiato */
CREATE PROCEDURE log_flag(IN p_old TINYINT, IN p_new TINYINT, IN p_true VARCHAR(50), IN p_false VARCHAR(50),
                          IN p_entity VARCHAR(50), IN p_id INT)
BEGIN
    IF NOT (p_old <=> p_new) THEN
        CALL write_log(CONCAT(IF(p_new, p_true, p_false), ' ', p_entity, ' (ID ', p_id, ')'));
    END IF;
END //

DELIMITER ;
