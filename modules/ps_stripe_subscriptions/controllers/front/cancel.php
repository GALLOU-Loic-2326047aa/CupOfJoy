<?php

class Ps_Stripe_SubscriptionsCancelModuleFrontController extends ModuleFrontController
{
    /**
     * Gère les actions après l'envoi du formulaire ou le clic sur le lien
     */
    public function postProcess()
    {
        // Vérifie si le client est bien connecté avant d'autoriser la résiliation
        if (!$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=authentication');
        }

        // Récupère l'ID de l'abonnement Stripe passé dans l'URL
        $subscriptionId = Tools::getValue('id_sub');

        // Charge les classes du module et initialise la connexion à Stripe
        $this->module->loadModuleClasses();
        $this->module->initStripeApi();

        try {
            // Cherche l'ID client Stripe associé au client PrestaShop actuel en base de données
            $stripeCustomerId = Db::getInstance()->getValue('
                SELECT id_customer_stripe 
                FROM ' . _DB_PREFIX_ . 'stripe_customer_link 
                WHERE id_customer_ps = ' . (int)$this->context->customer->id
            );

            // Récupère les données de l'abonnement directement auprès de Stripe
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);

            // Sécurité : vérifie que l'abonnement Stripe appartient bien au client connecté
            if ($subscription->customer !== $stripeCustomerId) {
                die("Accès non autorisé.");
            }

            // Demande à Stripe de ne pas renouveler l'abonnement à la fin de l'échéance payée
            $subscription->cancel_at_period_end = true;
            $subscription->save();

            // Prépare le message de succès et redirige vers la liste des abonnements
            $this->success[] = $this->module->l('Votre abonnement sera annulé à la fin de la période en cours.');
            $this->redirectWithNotifications($this->context->link->getModuleLink($this->module->name, 'subscriptions'));

        } catch (Exception $e) {
            // En cas d'erreur (problème API, ID invalide), affiche le message d'erreur et redirige
            $this->errors[] = $this->module->l('Erreur lors de la résiliation : ') . $e->getMessage();
            $this->redirectWithNotifications($this->context->link->getModuleLink($this->module->name, 'subscriptions'));
        }
    }
}