<?php

class Ps_Stripe_SubscriptionsSubscriptionsModuleFrontController extends ModuleFrontController
{
    /**
     * Initialise et affiche la page "Mes abonnements" du compte client
     */
    public function initContent()
    {
        parent::initContent();

        // 1. On vérifie si le client est connecté, sinon on le renvoie vers la page de login
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        // Chargement des outils du module et connexion à l'API Stripe
        $this->module->loadModuleClasses();
        $this->module->initStripeApi();

        // 2. Initialisation de la liste des abonnements
        $subscriptions = [];
        try {
            // On cherche l'ID client Stripe associé à l'ID PrestaShop actuel en base de données
            $stripeCustomerId = Db::getInstance()->getValue('
                SELECT stripe_customer_id FROM ' . _DB_PREFIX_ . 'stripe_customer_link 
                WHERE id_customer = ' . (int)$this->context->customer->id
            );

            // Si un ID Stripe existe pour ce client, on récupère ses abonnements via l'API
            if ($stripeCustomerId) {
                $stripeSubs = \Stripe\Subscription::all(['customer' => $stripeCustomerId]);
                $subscriptions = $stripeSubs->data;
            }
        } catch (Exception $e) {
            //En cas d'erreur de l'API Stripe, on affiche un message d'alerte sur la page
            $this->errors[] = $this->module->l('Impossible de charger vos abonnements.');
        }

        // On envoie les données pour qu'elles soient utilisables dans le fichier .tpl
        $this->context->smarty->assign([
            'subscriptions' => $subscriptions,
            'my_account_url' => $this->context->link->getPageLink('my-account'),
        ]);

        // Définition du template visuel à charger
        $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/subscriptions.tpl');
    }
}