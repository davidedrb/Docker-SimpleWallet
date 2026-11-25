<?php

// Autoload di composer (controller\, libs\, models\ + dipendenze)
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$_SERVER['HTTP_HOST']     = 'localhost:8080';
$_SERVER['DOCUMENT_ROOT'] = str_replace('\\', '/', getcwd());

// Costanti dell'app: URL, HOST, DATABASE, USERNAME, PASSWORD, PORT (da .env)
require_once dirname(__DIR__) . '/config/config.php';


// Stub di header() per i namespace dell'app (solo test)
require_once __DIR__ . '/support/header_stub.php';
require_once __DIR__ . '/ControllerTestCase.php';
// BUG FIX: qui c'era anche require_once CategoryTest.php: i test li carica già PHPUnit, non vanno inclusi nel bootstrap

require_once dirname(__DIR__) . '/config/database.php';