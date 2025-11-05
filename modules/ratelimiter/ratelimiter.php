<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class RateLimiter extends Module
{
    public function __construct()
    {
        $this->name = 'ratelimiter';
        $this->tab = 'Security features';
        $this->version = '1.0.0';
        $this->author = 'Maxime Allasio';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Rate Limiter - Limiteur de requêtes');
        $this->description = $this->l('Affiche une page de blocage si un visiteur effectue trop de requêtes.');
    }

    public function install()
    {
        return parent::install() && $this->registerHook('actionFrontControllerInitBefore');
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->unregisterHook('actionFrontControllerInitBefore');
    }

    public function hookActionFrontControllerInitBefore($params)
    {

        // Protection pour éviter que le code se refresh trop rapidement et fasse une boucle infini de chargement
        if (Tools::getValue('module') == $this->name && Tools::getValue('controller') == 'banned') {
            return;
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $limitAttempts = 20;
        $timeFrameSeconds = 60;

        // Créer ou réinitialise la variable de session si elle n'existe pas ou a expiré
        if (!isset($_SESSION['rate_limiter']) || (time() - $_SESSION['rate_limiter']['timestamp'] > $timeFrameSeconds)) {
            $_SESSION['rate_limiter'] = [
                'count' => 1,
                'timestamp' => time(),
            ];
        } else {
            // Augmente le compteur si la variable de session existe déjà
            $_SESSION['rate_limiter']['count']++;
        }

        // Vérfie si le compteur depasse la limite d'attempts et lance le ban
        if ($_SESSION['rate_limiter']['count'] > $limitAttempts) {
            Tools::redirect($this->context->link->getModuleLink($this->name, 'banned', [], true));
            exit;
        }
    }
}