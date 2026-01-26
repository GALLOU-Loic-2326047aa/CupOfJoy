<?php

class AdminStripeController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'stripe_customer_link';
        $this->className = 'StripeCustomerLink';
        $this->identifier = 'id_customer_ps';

        // Récupération de l'instance et chargement des classes nécessaires
        $this->module = Module::getInstanceByName('ps_stripe_subscriptions');

        if (method_exists($this->module, 'loadModuleClasses')) {
            $this->module->loadModuleClasses();
        }

        parent::__construct();

        // Jointure pour récupérer les infos client PS et alias pour le calcul de date
        $this->_select = 'c.`firstname`, c.`lastname`, a.`id_customer_stripe` AS next_delivery';
        $this->_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer_ps`)';

        // Configuration des colonnes du tableau de bord
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
                'callback' => 'calculateNextDelivery', // Appel API Stripe dynamique
                'search' => false,
                'orderby' => false,
            ]
        ];

        $this->addRowAction('view');
    }

    /**
     * Récupère la date de fin de période via l'API Stripe
     */
    public function calculateNextDelivery($id_stripe, $row)
    {
        if (empty($id_stripe)) {
            return '--';
        }

        try {
            $this->module->initStripeApi();

            // Récupère le premier abonnement actif trouvé
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

            // Alerte visuelle si l'échéance est imminente (moins de 5 jours)
            if ($timestamp < strtotime('+5 days')) {
                return '<b style="color:red;">' . $date_formatted . ' (URGENT)</b>';
            }

            return '<span class="badge badge-success">' . $date_formatted . '</span>';

        } catch (Exception $e) {
            return '<i style="color:gray;">' . $this->module->l('Erreur API') . '</i>';
        }
    }

    /**
     * Logique de redirection au clic sur "Afficher"
     */
    public function renderView()
    {
        $id_customer = (int)Tools::getValue('id_customer_ps');

        if (!$id_customer) {
            $this->errors[] = $this->module->l('ID client introuvable.');
            return;
        }

        // Tente de rediriger vers la dernière commande passée
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
            // Fallback sur la fiche client classique
            $customerUrl = $this->context->link->getAdminLink('AdminCustomers', true, [
                'id_customer' => $id_customer,
                'viewcustomer' => 1
            ]);
            Tools::redirectAdmin($customerUrl);
        }
    }
}