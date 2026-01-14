<?php

class Ps_Stripe_SubscriptionsSubscriptionsModuleFrontController extends ModuleFrontController
{
    /**
     * Initialise et affiche le contenu de la page "Mes abonnements"
     */
    public function initContent()
    {
        parent::initContent();

        //On vérifie si le client est connecté, sinon redirection vers la page de connexion
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        //Chargement des classes nécessaires et initialisation de l'API Stripe
        $this->module->loadModuleClasses();
        $this->module->initStripeApi();

        $subscriptions = [];

        try {
            //Récupération de l'ID client Stripe associé au compte PrestaShop actuel
            $stripeCustomerId = Db::getInstance()->getValue('
                SELECT id_customer_stripe 
                FROM ' . _DB_PREFIX_ . 'stripe_customer_link 
                WHERE id_customer_ps = ' . (int)$this->context->customer->id
            );

            if ($stripeCustomerId) {
                //Récupération de la liste des abonnements directement chez Stripe
                //L'option 'expand' permet de récupérer aussi les détails du produit (nom, etc.)
                $stripeSubs = \Stripe\Subscription::all([
                    'customer' => $stripeCustomerId,
                    'expand' => ['data.plan.product']
                ]);
                $subscriptions = $stripeSubs->data;
            }

        } catch (Exception $e) {
            //Si une erreur API survient, affiche une erreur
            $this->errors[] = $this->module->l('Erreur technique : ') . $e->getMessage();
        }

        // Transmission des données pour l'affichage dans le template
        $this->context->smarty->assign([
            'subscriptions' => $subscriptions,
            'my_account_url' => $this->context->link->getPageLink('my-account'),
        ]);

        // Définition du fichier de template à utiliser pour le rendu visuel
        $this->setTemplate('module:ps_stripe_subscriptions/views/templates/front/subscriptions.tpl');
    }
}