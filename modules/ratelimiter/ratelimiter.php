<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class RateLimiter extends Module
{
    const BANNED_IP_TABLE = 'ratelimiter_banned_ips';
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
        return parent::install() && $this->registerHook('actionFrontControllerInitBefore') && $this->installDatabase();

    }

    public function uninstall()
    {
        return parent::uninstall() && $this->unregisterHook('actionFrontControllerInitBefore') && $this->uninstallDatabase();
    }

    private function installDatabase()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . self::BANNED_IP_TABLE . "` (
            `id_banned_ip` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip_address` VARCHAR(45) NOT NULL,
            `ban_expires_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_banned_ip`),
            INDEX `ip_address` (`ip_address`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        // On exécute la requête et on retourne son résultat (true si succès, false si échec)
        return Db::getInstance()->execute($sql);
    }

    private function uninstallDatabase()
    {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . self::BANNED_IP_TABLE . "`;";
        return Db::getInstance()->execute($sql);
    }

    public function hookActionFrontControllerInitBefore($params)
    {
        if ($this->context->controller->controller_type != 'front') {
            return;
        }

        // Protection contre les boucles infini
        if (Tools::getValue('module') == $this->name && Tools::getValue('controller') == 'banned') {
            return;
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $userIp = Tools::getRemoteAddr();

        $sql = new DbQuery();
        $sql->select('ban_expires_at');
        $sql->from(self::BANNED_IP_TABLE);
        $sql->where('ip_address = \'' . pSQL($userIp) . '\' AND ban_expires_at > UTC_TIMESTAMP()');

        if (Db::getInstance()->getValue($sql)) {
            Tools::redirect($this->context->link->getModuleLink($this->name, 'banned', [], true));
            exit;
        }

        $limitAttempts = 50;
        $timeFrameSeconds = 60;
        $banDurationMinutes = 15;

        // Créer la variable session si elle n'existe pas ou est expiré.
        if (!isset($_SESSION['rate_limiter']) || !is_array($_SESSION['rate_limiter']) || (time() - $_SESSION['rate_limiter']['timestamp'] > $timeFrameSeconds)) {
            $_SESSION['rate_limiter'] = [
                'count' => 1,
                'timestamp' => time(),
            ];
        } else {
            $_SESSION['rate_limiter']['count']++;
        }

        if ($_SESSION['rate_limiter']['count'] > $limitAttempts) {
            $banExpiresAt = gmdate('Y-m-d H:i:s', strtotime("+$banDurationMinutes minutes"));

            Db::getInstance()->insert(self::BANNED_IP_TABLE, [
                'ip_address' => pSQL($userIp),
                'ban_expires_at' => $banExpiresAt,
            ]);

            unset($_SESSION['rate_limiter']);

            Tools::redirect($this->context->link->getModuleLink($this->name, 'banned', [], true));
            exit;
        }
    }
}