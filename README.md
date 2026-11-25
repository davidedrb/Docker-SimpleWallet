# 💰 SimpleWallet

**SimpleWallet** è un gestionale web per le **spese personali**: permette di registrare entrate e uscite, tenere sotto controllo il saldo e un limite di spesa mensile, gestire gli abbonamenti ricorrenti e consultare storico, grafici ed estratto conto.

Il progetto è stato realizzato come lavoro pratico individuale (LPI) per la qualifica **Informatica/o Aziendale AFC** presso il **CPT Trevano**, nel periodo 31.08.2026 – 22.01.2027.

| | |
|---|---|
| **Autori** | Martino Dal Cin · Djordje Vlajic · Davide Bernasconi |
| **Superiore professionale** | Maurizio Di Florio |
| **Stack** | PHP (MVC custom) · Eloquent ORM · MySQL · Apache · Docker |

---

## ✨ Funzionalità

### Utente
- **Registrazione e login** con password cifrata (bcrypt) e sessioni PHP.
- **Home / dashboard**: ultimi pagamenti, saldo e limite di spesa mensile (evidenziato quando superato).
- **Pagamenti**: inserimento, modifica ed eliminazione di entrate/uscite, con categoria, metodo di pagamento, data e ora, descrizione e importo. Sono ammessi anche pagamenti futuri.
- **Storico** paginato con andamento del saldo.
- **Estratto conto** esportabile in **PDF** (Dompdf).
- **Grafici** per analizzare l'andamento delle spese.
- **Categorie** e **metodi di pagamento** personalizzabili (alla registrazione ne vengono creati alcuni di default tramite trigger).
- **Abbonamenti**: i pagamenti ricorrenti (mensili, trimestrali, semestrali, annuali) vengono generati automaticamente alla scadenza.
- **Cestino**: i pagamenti eliminati possono essere ripristinati o rimossi definitivamente.
- **Account**: modifica dei dati personali ed eliminazione del profilo.

### Amministratore
- **Statistiche** generali sull'utilizzo.
- **Archivio** degli utenti.
- **Registro attività** (log), alimentato automaticamente dai trigger del database.

---

## 🧱 Architettura

Applicazione **MVC** scritta da zero, con front controller e URL del tipo `/<controller>/<azione>/<parametri>`.

```
URL → .htaccess → index.php → libs\Application (router) → controller → models (Eloquent) → views
```

La logica di business più delicata (saldo, spese mensili, log, abbonamenti) vive **nel database**, tramite funzioni, procedure, trigger ed eventi MySQL.

### Struttura del repository

```
├── 1_QdC/                 Quaderno dei compiti
├── 2_Abstract/            Abstract
├── 3_Documentazione/      Documentazione di progetto
├── 4_Diario/              Diario di lavoro
├── 5_Applicativo/         ⭐ Codice dell'applicazione
│   ├── application/
│   │   ├── config/        config.php (costanti, .env) e database.php (Eloquent)
│   │   ├── controller/    Authenticator, Payment, Category, Method, Subscription,
│   │   │                  Statement, Chart, Trash, User, Administrator, Home
│   │   ├── libs/          Application (router), Auth, Validator, Flash
│   │   ├── models/        Users, Payments, Categories, Methods, Subscriptions, Logs
│   │   ├── views/         Template PHP organizzati per sezione
│   │   └── tests/         Test PHPUnit
│   ├── Docker/            docker-compose e Dockerfile (app + db)
│   ├── public/            CSS, JavaScript, immagini
│   ├── composer.json
│   ├── index.php          Front controller
│   └── .htaccess          Rewrite e protezione dei file sensibili
├── 6_Database/
│   ├── DatabaseSQL/       Script SQL numerati (da eseguire in ordine)
│   └── StrutturaER/       Schema ER (Workbench, PNG, SVG)
├── 7_Allegati/            UML, use case, swimlane, mockup interfacce, Gantt, user stories
└── 8_Manuali/
```

### Database

Sei tabelle: `users`, `categories`, `payment_methods`, `subscriptions`, `payments`, `logs`.

| Script | Contenuto |
|---|---|
| `01_schema.sql` | Creazione delle tabelle |
| `02_functions.sql` | Funzioni (`payment_effect`) |
| `03_procedures.sql` | Procedure (ricalcolo spese mensili, aggiornamento saldo, log) |
| `04_triggers.sql` | Trigger per saldo, categorie/metodi di default e registro attività |
| `05_events.sql` | Eventi pianificati: generazione abbonamenti (giornaliero) e ricalcolo spese mensili (ogni mese) |
| `06_test_data.sql` | Dati di esempio (3 utenti, 1 amministratore, abbonamenti e transazioni) |

---

## 🛠️ Requisiti

**Con Docker (consigliato):**
- Docker e Docker Compose v2

**Senza Docker:**
- PHP **8.2+** con estensioni `pdo_mysql` e `mbstring`
- Apache con `mod_rewrite` e `AllowOverride All` (es. XAMPP)
- MySQL 8.x (o MariaDB) con **event scheduler attivo**
- [Composer](https://getcomposer.org/) 2

---

## 🚀 Installazione

### 1. Clonare il repository

```bash
git clone <URL-DEL-REPOSITORY>
cd martino-davide-djordje-simplewallet/5_Applicativo
```

### 2. Creare il file `.env`

Il file va creato nella cartella **`5_Applicativo/`** (non è versionato). Esempio:

```env
MYSQL_ROOT_PASSWORD=cambiami_root
MYSQL_DATABASE=simplewallet
MYSQL_USER=simplewallet
MYSQL_PASSWORD=cambiami_user
DB_HOST=db
DB_PORT=3306
WEB_PORT=8080
```

> 💡 Con Docker `DB_HOST` viene sovrascritto automaticamente con `db`. Con un'installazione manuale usa `127.0.0.1` (o l'host del tuo MySQL) e la porta reale (di solito `3306`). `WEB_PORT` serve solo a Docker.

---

### Opzione A — Docker 🐳

```bash
cd Docker
docker compose --env-file ../.env up --build -d
```

Al primo avvio vengono costruite le immagini, creato il database ed eseguiti automaticamente **tutti gli script** di `6_Database/DatabaseSQL/` (compresi i dati di test).

L'applicazione è raggiungibile su **http://localhost:8080** (o sulla porta impostata in `WEB_PORT`).

Comandi utili:

```bash
docker compose --env-file ../.env logs -f      # log
docker compose --env-file ../.env down         # ferma i container
docker compose --env-file ../.env down -v      # ferma e CANCELLA il database
docker compose --env-file ../.env up --build -V   # dopo modifiche a composer.json
```

**Note:**
- Gli script SQL vengono eseguiti **solo alla prima creazione del volume**. Per ripartire da zero usa `down -v`.
- Il file `docker-compose.override.yaml` viene caricato automaticamente e abilita la modalità **sviluppo** (errori PHP visibili, codice montato in live reload, MySQL esposto solo su `127.0.0.1`). Per la **produzione** usa:
  ```bash
  docker compose --env-file ../.env -f docker-compose.yaml up -d --build
  ```
- Su Linux/macOS i nomi dei file sono case-sensitive: il compose cerca `Docker/app/Dockerfile`, mentre nel repository il file si chiama `dockerfile`. Se la build fallisce, rinominalo:
  ```bash
  mv Docker/app/dockerfile Docker/app/Dockerfile
  ```

---

### Opzione B — Installazione manuale (XAMPP / Apache + MySQL)

1. **Installare le dipendenze PHP** (da `5_Applicativo/`):
   ```bash
   composer install --no-dev
   ```

2. **Creare il database** ed eseguire gli script SQL **in ordine** dalla cartella `6_Database/DatabaseSQL/`:
   ```bash
   mysql -u root -p -e "CREATE DATABASE simplewallet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   for f in 01_schema 02_functions 03_procedures 04_triggers 05_events 06_test_data; do
     mysql -u root -p simplewallet < 6_Database/DatabaseSQL/$f.sql
   done
   ```
   (`06_test_data.sql` è opzionale: contiene i dati di esempio.)

   Se usi MySQL Workbench o phpMyAdmin puoi eseguire i file nello stesso ordine: gli script usano `DELIMITER`, quindi servono client compatibili.

3. **Attivare l'event scheduler** (necessario per abbonamenti e ricalcolo mensile):
   ```sql
   SET GLOBAL event_scheduler = ON;
   ```
   Per renderlo permanente aggiungi `event_scheduler=ON` sotto `[mysqld]` in `my.ini` / `my.cnf`.

4. **Configurare `.env`** in `5_Applicativo/` con `DB_HOST=127.0.0.1` e le credenziali del tuo database (vedi sopra).

5. **Pubblicare l'applicazione**: copia (o collega) la cartella `5_Applicativo` in `htdocs/` e apri, ad esempio, `http://localhost/5_Applicativo/`.
   Assicurati che `mod_rewrite` sia attivo e che `AllowOverride All` sia impostato per la cartella: il file `.htaccess` instrada tutte le richieste a `index.php` e blocca l'accesso a `application/`, `vendor/`, `Docker/` e ai file di configurazione.

---

## 👤 Primo accesso

- **Nuovo utente:** dalla pagina di login scegli *Registrati* e crea il tuo profilo.
- **Dati di test** (se hai eseguito `06_test_data.sql`):

  | Ruolo | Email |
  |---|---|
  | Utente | `marco.rossi@email.com` |
  | Utente | `luca.bianchi@email.com` |
  | Utente | `anna.verdi@email.com` |
  | Amministratore | `dalcin.admin@email.com` |

  Le password sono salvate come hash bcrypt in `06_test_data.sql`: se non le conosci, registra un nuovo utente oppure sostituisci l'hash con uno generato da `password_hash()`.

---

## 🧪 Test

I test automatici usano **PHPUnit 11** (richiede `composer install` con le dipendenze di sviluppo e un database raggiungibile secondo il `.env`):

```bash
cd 5_Applicativo
vendor/bin/phpunit
```

Suite attuale: `AutenticatorTest`, `CategoryTest`, `MethodTest`.

---

## 📚 Documentazione

Tutto il materiale di progetto è nel repository:

- **Quaderno dei compiti** → `1_QdC/`
- **Documentazione completa** → `3_Documentazione/Documentazione.docx`
- **Diario di lavoro** → `4_Diario/Diario.docx`
- **Schema ER** → `6_Database/StrutturaER/`
- **UML, Use Case, SwimLane, mockup, user stories, Gantt** → `7_Allegati/`

---

## 📏 Regole di sviluppo

1. Prima di scrivere codice, controllare che qualcosa di simile non esista già altrove.
2. Commentare il codice se non è di facile comprensione.
3. Non mettere il codice in posti casuali, ma dove ha effettivamente senso.
4. Accusare prima se stessi e poi gli altri se qualcosa non funziona.
5. In difficoltà: PRIMA chiedere ai compagni, DOPO alle AI.

---

## 🛡️ Note di sicurezza

- Non committare mai il file `.env`: è già in `.gitignore`.
- Cambia tutte le password di esempio prima di qualsiasi deploy reale.
- In produzione disattiva la visualizzazione degli errori (`display_errors`) in `application/config/config.php` e usa il compose senza override.
