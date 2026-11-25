
-- =========================================================
-- UTENTI
-- Nota: L'inserimento genera automaticamente i metodi di pagamento per ciascun utente tramite Trigger.
-- Utente 1 (Marco) -> Metodi ID 1..6
-- Utente 2 (Luca)  -> Metodi ID 7..12
-- Utente 3 (Anna)  -> Metodi ID 13..18
-- =========================================================
INSERT INTO users
(email, password, first_name, last_name, spending_limit, balance)
VALUES
('marco.rossi@email.com', '$2y$10$QXc/6hQfDkTtOZPc0LdiUuuBr.OQr6GHU6p0T2Ybf4PMMohmergKO', 'Marco', 'Rossi', 2500.00, 0),
('luca.bianchi@email.com', '$2y$10$QXc/6hQfDkTtOZPc0LdiUuuBr.OQr6GHU6p0T2Ybf4PMMohmergKO', 'Luca', 'Bianchi', 1800.00, 0),
('anna.verdi@email.com', '$2y$10$QXc/6hQfDkTtOZPc0LdiUuuBr.OQr6GHU6p0T2Ybf4PMMohmergKO', 'Anna', 'Verdi', 2200.00, 0);


INSERT INTO users (email, password, first_name, last_name, role)
VALUES
('dalcin.admin@email.com', '$2y$10$QXc/6hQfDkTtOZPc0LdiUuuBr.OQr6GHU6p0T2Ybf4PMMohmergKO', 'Dal Cin', 'Admin', 'admin'),
('bernasconi.admin@email.com', '$2y$10$QXc/6hQfDkTtOZPc0LdiUuuBr.OQr6GHU6p0T2Ybf4PMMohmergKO', 'Bernasconi', 'Admin', 'admin'),
('vlajic.admin@email.com', '$2y$10$QXc/6hQfDkTtOZPc0LdiUuuBr.OQr6GHU6p0T2Ybf4PMMohmergKO', 'Vlajic', 'Admin', 'admin');

-- =========================================================
-- SOTTOSCRIZIONI
-- =========================================================

INSERT INTO subscriptions
(user_id, name, price, first_payment_date, frequency_months, is_active)
VALUES
(1, 'Netflix', 17.90, '2026-01-15', 1, TRUE),
(1, 'Spotify', 10.95, '2026-01-10', 1, TRUE),
(1, 'Amazon Prime', 4.99, '2026-01-05', 1, TRUE),

(2, 'Netflix', 17.90, '2026-01-12', 1, TRUE),
(2, 'Spotify', 10.95, '2026-01-08', 1, TRUE),
(2, 'PlayStation Plus', 8.99, '2026-01-20', 1, TRUE),

(3, 'Netflix', 17.90, '2026-01-14', 1, TRUE),
(3, 'Spotify', 10.95, '2026-01-09', 1, TRUE),
(3, 'Amazon Prime', 4.99, '2026-01-06', 1, TRUE);

-- =========================================================
-- TRANSAZIONI UTENTE 1 - MARCO
-- Metodi di pagamento mappati su ID 1..6
-- Categorie mappate su ID 1..10
-- =========================================================


INSERT INTO payments
(user_id, payment_method_id, category_id, type, amount, description, transaction_date, is_completed)
VALUES

-- =====================================
-- ANNO 2023 (17 TRANSAZIONI)
-- =====================================

(1, 5, 1, 'Entrata', 2850.00, 'Stipendio mensile', '2023-01-03 09:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 76.40, 'Spesa supermercato', '2023-01-07 17:30:00', 'Yes'),
(1, 2, 6, 'Uscita', 620.00, 'Affitto appartamento', '2023-01-10 08:00:00', 'Yes'),
(1, 4, 4, 'Uscita', 42.00, 'Abbonamento trasporti', '2023-01-15 09:00:00', 'Yes'),
(1, 2, 3, 'Uscita', 28.50, 'Pranzo al ristorante', '2023-02-04 13:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 2850.00, 'Stipendio mensile', '2023-02-03 09:00:00', 'Yes'),
(1, 3, 7, 'Uscita', 95.00, 'Acquisto pantaloni', '2023-02-12 15:30:00', 'Yes'),
(1, 2, 5, 'Uscita', 15.90, 'Abbonamento streaming', '2023-02-15 10:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 2850.00, 'Stipendio mensile', '2023-03-03 09:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 89.70, 'Spesa settimanale', '2023-03-06 18:00:00', 'Yes'),
(1, 2, 8, 'Uscita', 35.00, 'Serata al bowling', '2023-03-11 21:00:00', 'Yes'),
(1, 4, 9, 'Uscita', 55.00, 'Visita medica', '2023-04-08 14:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 2850.00, 'Stipendio mensile', '2023-04-03 09:00:00', 'Yes'),
(1, 3, 7, 'Uscita', 130.00, 'Acquisto giacca', '2023-05-13 16:00:00', 'Yes'),
(1, 2, 10, 'Uscita', 75.00, 'Corso di informatica', '2023-06-10 10:00:00', 'No'),
(1, 5, 1, 'Entrata', 300.00, 'Vendita vecchio computer', '2023-07-18 11:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 112.80, 'Spesa alimentare', '2023-08-05 17:00:00', 'Yes'),

-- =====================================
-- ANNO 2024 (17 TRANSAZIONI)
-- =====================================

(1, 5, 1, 'Entrata', 2950.00, 'Stipendio mensile', '2024-01-03 09:00:00', 'Yes'),
(1, 2, 6, 'Uscita', 620.00, 'Affitto appartamento', '2024-01-05 08:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 95.30, 'Spesa supermercato', '2024-01-09 18:00:00', 'Yes'),
(1, 4, 4, 'Uscita', 45.00, 'Abbonamento trasporti', '2024-01-15 08:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 2950.00, 'Stipendio mensile', '2024-02-03 09:00:00', 'Yes'),
(1, 2, 3, 'Uscita', 52.00, 'Cena con amici', '2024-02-10 20:00:00', 'Yes'),
(1, 2, 5, 'Uscita', 17.90, 'Abbonamento streaming', '2024-02-15 10:00:00', 'Yes'),
(1, 3, 7, 'Uscita', 180.00, 'Acquisto scarpe', '2024-03-12 16:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 2950.00, 'Stipendio mensile', '2024-03-03 09:00:00', 'Yes'),
(1, 2, 2, 'Uscita', 83.60, 'Spesa alimentare', '2024-03-08 17:30:00', 'Yes'),
(1, 4, 8, 'Uscita', 24.00, 'Biglietti cinema', '2024-04-06 20:30:00', 'Yes'),
(1, 5, 1, 'Entrata', 2950.00, 'Stipendio mensile', '2024-04-03 09:00:00', 'Yes'),
(1, 3, 9, 'Uscita', 65.00, 'Controllo dentistico', '2024-05-14 11:00:00', 'Yes'),
(1, 2, 6, 'Uscita', 620.00, 'Affitto appartamento', '2024-06-05 08:00:00', 'Yes'),
(1, 5, 10, 'Entrata', 450.00, 'Lavoro occasionale', '2024-07-20 14:00:00', 'Yes'),
(1, 3, 7, 'Uscita', 220.00, 'Acquisto vestiti estivi', '2024-08-11 15:00:00', 'Yes'),
(1, 2, 8, 'Uscita', 48.00, 'Concerto musicale', '2024-09-21 21:00:00', 'No'),

-- =====================================
-- ANNO 2025 (17 TRANSAZIONI)
-- =====================================

(1, 5, 1, 'Entrata', 3100.00, 'Stipendio mensile', '2025-01-03 09:00:00', 'Yes'),
(1, 2, 6, 'Uscita', 650.00, 'Affitto appartamento', '2025-01-05 08:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 104.50, 'Spesa supermercato', '2025-01-11 17:00:00', 'Yes'),
(1, 4, 4, 'Uscita', 45.00, 'Abbonamento trasporti', '2025-01-15 08:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 3100.00, 'Stipendio mensile', '2025-02-03 09:00:00', 'Yes'),
(1, 2, 3, 'Uscita', 62.00, 'Cena al ristorante', '2025-02-08 20:00:00', 'Yes'),
(1, 3, 7, 'Uscita', 145.00, 'Acquisto giubbotto', '2025-02-16 15:00:00', 'Yes'),
(1, 2, 5, 'Uscita', 19.90, 'Abbonamento streaming', '2025-03-15 10:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 3100.00, 'Stipendio mensile', '2025-03-03 09:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 92.30, 'Spesa supermercato', '2025-03-09 18:00:00', 'Yes'),
(1, 4, 8, 'Uscita', 38.00, 'Serata al cinema', '2025-04-12 21:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 3100.00, 'Stipendio mensile', '2025-04-03 09:00:00', 'Yes'),
(1, 2, 9, 'Uscita', 85.00, 'Visita specialistica', '2025-05-17 10:30:00', 'Yes'),
(1, 3, 7, 'Uscita', 260.00, 'Acquisto smartwatch', '2025-06-14 16:00:00', 'Yes'),
(1, 5, 10, 'Entrata', 500.00, 'Progetto freelance', '2025-07-22 12:00:00', 'Yes'),
(1, 2, 2, 'Uscita', 118.40, 'Spesa settimanale', '2025-08-09 17:00:00', 'Yes'),
(1, 4, 8, 'Uscita', 90.00, 'Festival musicale', '2025-09-13 20:00:00', 'No'),

-- =====================================
-- ANNO 2026 (19 TRANSAZIONI)
-- =====================================

(1, 5, 1, 'Entrata', 3300.00, 'Stipendio mensile', '2026-04-02 09:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 97.80, 'Spesa supermercato', '2026-04-05 17:30:00', 'Yes'),
(1, 2, 6, 'Uscita', 650.00, 'Affitto appartamento', '2026-04-01 08:00:00', 'Yes'),
(1, 4, 4, 'Uscita', 45.00, 'Abbonamento trasporti', '2026-04-08 08:30:00', 'Yes'),
(1, 2, 3, 'Uscita', 54.00, 'Cena al ristorante', '2026-04-12 20:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 3300.00, 'Stipendio mensile', '2026-05-02 09:00:00', 'Yes'),
(1, 3, 7, 'Uscita', 175.00, 'Acquisto abbigliamento', '2026-05-10 15:00:00', 'Yes'),
(1, 2, 5, 'Uscita', 19.90, 'Abbonamento streaming', '2026-05-15 10:00:00', 'Yes'),
(1, 4, 8, 'Uscita', 32.00, 'Biglietti cinema', '2026-05-21 21:00:00', 'Yes'),
(1, 5, 1, 'Entrata', 3300.00, 'Stipendio mensile', '2026-06-02 09:00:00', 'Yes'),
(1, 3, 2, 'Uscita', 108.60, 'Spesa supermercato', '2026-06-06 17:00:00', 'Yes'),
(1, 2, 9, 'Uscita', 70.00, 'Controllo medico', '2026-06-13 11:00:00', 'Yes'),
(1, 5, 10, 'Entrata', 650.00, 'Lavoro freelance', '2026-07-10 12:00:00', 'Yes'),
(1, 3, 7, 'Uscita', 210.00, 'Acquisto accessori', '2026-07-18 16:00:00', 'Yes'),
(1, 2, 3, 'Uscita', 47.50, 'Pranzo fuori casa', '2026-08-02 13:00:00', 'Yes'),
(1, 4, 4, 'Uscita', 45.00, 'Abbonamento trasporti', '2026-08-08 08:30:00', 'Yes'),
(1, 5, 1, 'Entrata', 3300.00, 'Stipendio mensile', '2026-09-02 09:00:00', 'Yes'),
(1, 2, 8, 'Uscita', 125.00, 'Evento e serata con amici', '2026-10-17 21:00:00', 'No'),
(1, 3, 2, 'Uscita', 86.70, 'Spesa supermercato', '2026-11-05 17:30:00', 'Yes');


-- =========================================================
-- TRANSAZIONI UTENTE 2 - LUCA
-- Metodi di pagamento mappati su ID 7..12
-- Categorie mappate su ID 11..20
-- =========================================================

INSERT INTO payments
(user_id, payment_method_id, category_id, type, amount, description, transaction_date, is_completed)
VALUES
(2, 11, 11, 'Entrata', 2800.00, 'Stipendio mensile', '2026-01-03 09:00:00', 'Yes'),
(2, 9,  12, 'Uscita', 75.20, 'Spesa supermercato', '2026-01-05 18:00:00', 'Yes'),
(2, 8,  13, 'Uscita', 28.00, 'Pranzo al ristorante', '2026-01-07 13:00:00', 'Yes'),
(2, 10, 14, 'Uscita', 50.00, 'Trasporti pubblici', '2026-01-09 08:00:00', 'Yes'),
(2, 8,  15, 'Uscita', 17.90, 'Netflix', '2026-01-12 10:00:00', 'Yes'),
(2, 8,  16, 'Uscita', 700.00, 'Affitto', '2026-01-02 08:00:00', 'Yes'),
(2, 9,  17, 'Uscita', 95.00, 'Acquisto elettronica', '2026-01-15 15:30:00', 'Yes'),
(2, 7,  18, 'Uscita', 20.00, 'Cinema', '2026-01-18 20:00:00', 'Yes'),
(2, 8,  12, 'Uscita', 64.90, 'Spesa supermercato', '2026-01-21 17:00:00', 'Yes'),

(2, 11, 11, 'Entrata', 2800.00, 'Stipendio mensile', '2026-02-03 09:00:00', 'Yes'),
(2, 9,  12, 'Uscita', 82.40, 'Spesa supermercato', '2026-02-05 18:00:00', 'Yes'),
(2, 8,  13, 'Uscita', 41.00, 'Cena', '2026-02-08 20:30:00', 'Yes'),
(2, 10, 14, 'Uscita', 50.00, 'Trasporti pubblici', '2026-02-09 08:00:00', 'Yes'),
(2, 8,  15, 'Uscita', 17.90, 'Netflix', '2026-02-12 10:00:00', 'Yes'),
(2, 8,  16, 'Uscita', 700.00, 'Affitto', '2026-02-02 08:00:00', 'Yes'),
(2, 9,  17, 'Uscita', 130.00, 'Nuovo paio di jeans', '2026-02-16 15:00:00', 'Yes'),
(2, 7,  18, 'Uscita', 30.00, 'Concerto', '2026-02-19 21:00:00', 'Yes'),
(2, 8,  12, 'Uscita', 70.50, 'Spesa supermercato', '2026-02-23 11:30:00', 'Yes'),

(2, 11, 11, 'Entrata', 2800.00, 'Stipendio mensile', '2026-03-03 09:00:00', 'Yes'),
(2, 9,  12, 'Uscita', 90.00, 'Spesa supermercato', '2026-03-05 18:00:00', 'Yes'),
(2, 8,  13, 'Uscita', 36.50, 'Cena', '2026-03-07 20:00:00', 'Yes'),
(2, 10, 14, 'Uscita', 50.00, 'Trasporti pubblici', '2026-03-09 08:30:00', 'Yes'),
(2, 8,  15, 'Uscita', 17.90, 'Netflix', '2026-03-12 10:00:00', 'Yes'),
(2, 8,  16, 'Uscita', 700.00, 'Affitto', '2026-03-02 08:00:00', 'Yes'),
(2, 9,  17, 'Uscita', 80.00, 'Acquisto vestiti', '2026-03-15 14:30:00', 'Yes'),
(2, 7,  18, 'Uscita', 25.00, 'Serata bowling', '2026-03-20 21:00:00', 'Yes'),
(2, 8,  12, 'Uscita', 68.70, 'Spesa supermercato', '2026-03-23 12:00:00', 'Yes'),
(2, 9,  19, 'Uscita', 35.00, 'Farmacia', '2026-03-25 16:00:00', 'Yes'),
(2, 11, 20, 'Uscita', 45.00, 'Libro scolastico', '2026-03-27 10:00:00', 'No');


-- =========================================================
-- TRANSAZIONI UTENTE 3 - ANNA
-- Metodi di pagamento mappati su ID 13..18
-- Categorie mappate su ID 21..30
-- =========================================================

INSERT INTO payments
(user_id, payment_method_id, category_id, type, amount, description, transaction_date, is_completed)
VALUES
(3, 17, 21, 'Entrata', 3000.00, 'Stipendio mensile', '2026-01-04 09:00:00', 'Yes'),
(3, 15, 22, 'Uscita', 92.30, 'Spesa supermercato', '2026-01-06 17:30:00', 'Yes'),
(3, 14, 23, 'Uscita', 35.00, 'Cena al ristorante', '2026-01-08 20:00:00', 'Yes'),
(3, 16, 24, 'Uscita', 45.00, 'Abbonamento trasporti', '2026-01-10 08:00:00', 'Yes'),
(3, 14, 25, 'Uscita', 17.90, 'Netflix', '2026-01-14 10:00:00', 'Yes'),
(3, 14, 26, 'Uscita', 800.00, 'Affitto appartamento', '2026-01-03 08:00:00', 'Yes'),
(3, 15, 27, 'Uscita', 110.00, 'Shopping', '2026-01-17 15:00:00', 'Yes'),
(3, 13, 28, 'Uscita', 22.00, 'Cinema', '2026-01-20 20:30:00', 'Yes'),
(3, 14, 22, 'Uscita', 73.50, 'Spesa supermercato', '2026-01-23 11:00:00', 'Yes'),

(3, 17, 21, 'Entrata', 3000.00, 'Stipendio mensile', '2026-02-04 09:00:00', 'Yes'),
(3, 15, 22, 'Uscita', 86.40, 'Spesa supermercato', '2026-02-06 17:30:00', 'Yes'),
(3, 14, 23, 'Uscita', 48.00, 'Cena con amici', '2026-02-08 20:00:00', 'Yes'),
(3, 16, 24, 'Uscita', 45.00, 'Abbonamento trasporti', '2026-02-10 08:00:00', 'Yes'),
(3, 14, 25, 'Uscita', 17.90, 'Netflix', '2026-02-14 10:00:00', 'Yes'),
(3, 14, 26, 'Uscita', 800.00, 'Affitto appartamento', '2026-02-03 08:00:00', 'Yes'),
(3, 15, 27, 'Uscita', 140.00, 'Acquisto giacca', '2026-02-16 14:00:00', 'Yes'),
(3, 13, 28, 'Uscita', 28.00, 'Teatro', '2026-02-19 21:00:00', 'Yes'),
(3, 14, 22, 'Uscita', 81.20, 'Spesa supermercato', '2026-02-22 11:30:00', 'Yes'),

(3, 17, 21, 'Entrata', 3000.00, 'Stipendio mensile', '2026-03-04 09:00:00', 'Yes'),
(3, 15, 22, 'Uscita', 94.70, 'Spesa supermercato', '2026-03-06 18:00:00', 'Yes'),
(3, 14, 23, 'Uscita', 42.00, 'Pranzo al ristorante', '2026-03-08 13:00:00', 'Yes'),
(3, 16, 24, 'Uscita', 45.00, 'Abbonamento trasporti', '2026-03-10 08:00:00', 'Yes'),
(3, 14, 25, 'Uscita', 17.90, 'Netflix', '2026-03-14 10:00:00', 'Yes'),
(3, 14, 26, 'Uscita', 800.00, 'Affitto appartamento', '2026-03-03 08:00:00', 'Yes'),
(3, 15, 27, 'Uscita', 95.00, 'Acquisto scarpe', '2026-03-16 15:00:00', 'Yes'),
(3, 13, 28, 'Uscita', 35.00, 'Serata fuori', '2026-03-20 21:30:00', 'Yes'),
(3, 14, 22, 'Uscita', 76.80, 'Spesa supermercato', '2026-03-23 12:00:00', 'Yes'),
(3, 15, 29, 'Uscita', 50.00, 'Farmacia', '2026-03-25 16:00:00', 'Yes'),
(3, 17, 30, 'Uscita', 75.00, 'Corso di formazione', '2026-03-27 10:00:00', 'No');