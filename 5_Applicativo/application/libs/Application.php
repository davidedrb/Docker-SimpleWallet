<?php

namespace libs;

class Application
{
    private $url_controller = null;
    private $url_action = null;
    private $url_parameter_1 = null;
    private $url_parameter_2 = null;
    private $url_parameter_3 = null;

    public function __construct()
    {
        // Sessione avviata una sola volta, qui, prima di ogni routing/controller.
        // Così tutti i controller e le view possono usare $_SESSION senza doverlo
        // ricontrollare/riavviare ognuno per conto proprio.
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        $this->splitUrl();

        $file  = './application/controller/' . $this->url_controller . '.php';
        $class = 'controller\\' . $this->url_controller;

        if ($this->url_controller && file_exists($file) && class_exists($class)) {
            $controller = new $class();

            if (method_exists($controller, $this->url_action)) {
                $params = array_filter(
                    [$this->url_parameter_1, $this->url_parameter_2, $this->url_parameter_3],
                    fn($p) => $p !== null
                );
                $controller->{$this->url_action}(...$params);
            } else {
                $controller->index();
            }
        } else {
            $home = new \controller\Home();
            $home->index();
        }
    }

    /**
     * Splitto l'url URL
     */
    private function splitUrl()
    {
        if (isset($_GET['url'])) {

            // tolgo il carattere / dalla fine della stringa
            $url = rtrim($_GET['url'], '/');
            //rimuove tutti i caratteri illegali dall'URL
            $url = filter_var($url, FILTER_SANITIZE_URL);
            //divido in un array in base al carattere /
            $url = explode('/', $url);

            // divido le parti dell'utl in base a controller, azione e 3 parametri
            // Prima lettera maiuscola: i file dei controller si chiamano Home.php, Payment.php, ...
            // (sui server Linux il filesystem distingue maiuscole e minuscole)
            $this->url_controller = (isset($url[0]) ? ucfirst($url[0]) : null);
            $this->url_action = (isset($url[1]) ? $url[1] : '');
            $this->url_parameter_1 = (isset($url[2]) ? $url[2] : null);
            $this->url_parameter_2 = (isset($url[3]) ? $url[3] : null);
            $this->url_parameter_3 = (isset($url[4]) ? $url[4] : null);

            // Per debug
            // echo 'Controller: ' . $this->url_controller . '<br />';
            // echo 'Action: ' . $this->url_action . '<br />';
            // echo 'Parameter 1: ' . $this->url_parameter_1 . '<br />';
            // echo 'Parameter 2: ' . $this->url_parameter_2 . '<br />';
            // echo 'Parameter 3: ' . $this->url_parameter_3 . '<br />';
        }
    }
}