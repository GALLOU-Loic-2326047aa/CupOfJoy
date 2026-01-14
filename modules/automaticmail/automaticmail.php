<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AutomaticMail extends Module
{
    public function __construct()
    {
        $this->name = 'automaticmail';
        $this->tab = 'emailing';
        $this->version = '1.1.0';
        $this->author = 'Maxime Allasio';
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Relance Panier - Cup of Joy');
    }

    public function install()
    {
        // Création de la table lors de l'installation du module
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'automaticmail_log` (
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `date_sent` DATETIME NOT NULL,
            PRIMARY KEY (`id_cart`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return parent::install()
            && Db::getInstance()->execute($sql)
            && $this->registerHook('displayFooter');
    }

    public function uninstall()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'automaticmail_log`');
        return parent::uninstall();
    }

    public function hookDisplayFooter($params)
    {
        // Intéroge la base de donnée toute les 30 sec pour ne pas la surchargé
        $lastExec = (int)Configuration::get('AUTO_MAIL_LAST_EXEC');
        if ((time() - $lastExec) < 30) {
            return;
        }
        Configuration::updateValue('AUTO_MAIL_LAST_EXEC', time());

        $this->runRelance();
    }

    public function runRelance()
    {
        $phpNow = pSQL(date('Y-m-d H:i:s'));

        // Le script detecte les paniers abandonées et met en place une petite protection contre les spams d'envoie de mail
        $sql = 'SELECT c.id_cart, cu.email, cu.firstname, cu.lastname, c.id_lang
                FROM ' . _DB_PREFIX_ . 'cart c
                INNER JOIN ' . _DB_PREFIX_ . 'customer cu ON (c.id_customer = cu.id_customer)
                LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.id_cart = c.id_cart)
                LEFT JOIN ' . _DB_PREFIX_ . 'automaticmail_log log ON (log.id_cart = c.id_cart)
                WHERE o.id_order IS NULL
                AND log.id_cart IS NULL
                AND EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'cart_product cp WHERE cp.id_cart = c.id_cart)
                AND TIMESTAMPDIFF(MINUTE, c.date_upd, "' . $phpNow . '") >= 120
                AND TIMESTAMPDIFF(HOUR, c.date_upd, "' . $phpNow . '") < 24
                GROUP BY c.id_cart';

        $results = Db::getInstance()->executeS($sql);

        if ($results) {
            foreach ($results as $row) {
                $sent = Mail::Send(
                    (int)$row['id_lang'],
                    'panierabandonne',
                    $this->l('Votre dégustation Cup of Joy vous attend'),
                    [
                        '{firstname}' => $row['firstname'],
                        '{lastname}' => $row['lastname'],
                        '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                        '{shop_url}' => Context::getContext()->link->getPageLink('cart', null, null, ['action' => 'show']),
                    ],
                    $row['email'],
                    $row['firstname'] . ' ' . $row['lastname'],
                    null, null, null, null,
                    $this->getLocalPath() . 'mails/'
                );

                // Une fois le mail envoyé, l'id du client est enregistré pour ne plus lui renvoyé de mail
                if ($sent) {
                    Db::getInstance()->execute('
                        INSERT INTO `' . _DB_PREFIX_ . 'automaticmail_log` (`id_cart`, `date_sent`) 
                        VALUES (' . (int)$row['id_cart'] . ', NOW())
                    ');
                }
            }
        }
    }
}