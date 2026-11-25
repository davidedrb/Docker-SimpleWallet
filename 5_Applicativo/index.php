<?php

use libs\Application;

// carico il file di configurazione
require_once 'application/config/config.php';

// autoload composer (libs/* e models/*)
require_once 'vendor/autoload.php';

// inizializzo Eloquent
require_once 'application/config/database.php';

// core framework (router/dispatcher)
// avvio applicazione



$app = new Application();