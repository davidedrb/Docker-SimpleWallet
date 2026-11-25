-- ============================================================
-- 02_functions.sql - Funzioni
-- Eseguire dopo 01_schema.sql
-- ============================================================
DELIMITER //

/* Effetto di una transazione sul saldo (+ entrata, - uscita, 0 se non completata) */
CREATE FUNCTION payment_effect(p_type VARCHAR(10), p_amount DECIMAL(10,2), p_completed VARCHAR(3))
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    IF p_completed <> 'Yes' THEN
        RETURN 0;
    END IF;
    RETURN IF(p_type = 'Entrata', p_amount, -p_amount);
END //

DELIMITER ;