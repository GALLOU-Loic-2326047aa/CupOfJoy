<?php

class AdminStripeController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'stripe_customer_link';
        $this->className = 'StripeCustomerLink';
        $this->identifier = 'id_customer_ps';

        // 1. Récupération de l'instance du module
        $this->module = Module::getInstanceByName('ps_stripe_subscriptions');

        // 2. Chargement des classes (StripeCustomerLink, etc.) pour éviter l'erreur "Class not found"
        if (method_exists($this->module, 'loadModuleClasses')) {
            $this->module->loadModuleClasses();
        }

        parent::__construct();

        // 3. Configuration de la requête SQL (Jointure pour le nom et alias pour la date)
        $this->_select = 'c.`firstname`, c.`lastname`, a.`id_customer_stripe` AS next_delivery';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer_ps`)';

        // 4. Définition des colonnes de la liste
        $this->fields_list = [
            'id_customer_ps' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'width' => 25
            ],
            'firstname' => [
                'title' => $this->module->l('Prénom'),
                'filter_key' => 'c!firstname'
            ],
            'lastname' => [
                'title' => $this->module->l('Nom'),
                'filter_key' => 'c!lastname'
            ],
            'id_customer_stripe' => [
                'title' => $this->module->l('ID Stripe'),
                'align' => 'left'
            ],
            'next_delivery' => [
                'title' => $this->module->l('Prochaine Expédition'),
                'callback' => 'calculateNextDelivery',
                'search' => false,
                'orderby' => false,
            ]
        ];

        // 5. Activation du bouton "Afficher" (l'icône de l'œil)
        $this->addRowAction('view');
    }

    /**
     * Calcule et affiche la date de prochaine expédition via Stripe
     */
    public function calculateNextDelivery($id_stripe, $row)
    {
        if (empty($id_stripe)) {
            return '--';
        }

        try {
            $this->module->initStripeApi();

            $subs = \Stripe\Subscription::all([
                'customer' => $id_stripe,
                'status' => 'active',
                'limit' => 1
            ]);

            if (empty($subs->data)) {
                return '<span class="label label-warning">' . $this->module->l('Aucun abonnement actif') . '</span>';
            }

            $timestamp = $subs->data[0]->current_period_end;
            $date_formatted = date('d/m/Y', $timestamp);

            if ($timestamp < strtotime('+5 days')) {
                return '<b style="color:red;">' . $date_formatted . ' (URGENT)</b>';
            }

            return '<span class="badge badge-success">' . $date_formatted . '</span>';

        } catch (Exception $e) {
            return '<i style="color:gray;">' . $this->module->l('Erreur API') . '</i>';
        }
    }

    /**
     * Gère l'action du bouton "Afficher" : Redirige vers la dernière commande
     */
    public function renderView()
    {
        // 1. Récupération de l'ID du client depuis la ligne
        $id_customer = (int)Tools::getValue('id_customer_ps');

        if (!$id_customer) {
            $this->errors[] = $this->module->l('ID client introuvable.');
            return;
        }

        // 2. Recherche de la commande la plus récente
        $last_order_id = (int)Db::getInstance()->getValue('
            SELECT id_order 
            FROM ' . _DB_PREFIX_ . 'orders 
            WHERE id_customer = ' . $id_customer . ' 
            ORDER BY date_add DESC'
        );

        if ($last_order_id > 0) {
            $orderUrl = $this->context->link->getAdminLink('AdminOrders', true, [
                'id_order' => $last_order_id,
                'vieworder' => 1
            ]);

            Tools::redirectAdmin($orderUrl);
        } else {
            // Si vraiment aucune commande n'existe, on va sur la fiche client
            $customerUrl = $this->context->link->getAdminLink('AdminCustomers', true, [
                'id_customer' => $id_customer,
                'viewcustomer' => 1
            ]);

            Tools::redirectAdmin($customerUrl);
        }
    }
}