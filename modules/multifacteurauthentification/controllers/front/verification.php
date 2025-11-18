<?php

class MultifacteurAuthentificationVerificationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $formActionUrl = $this->context->link->getModuleLink(
            $this->module->name,
            'verification'
        );

        $this->context->smarty->assign('form_action_url', $formActionUrl);

        $this->setTemplate('module:multifacteurauthentification/views/templates/front/verification.tpl');
    }

    public function postProcess()
    {
        // On vérifie si le formulaire a été soumis
        if (Tools::isSubmit('submitMfaCode')) {
            $submittedCode = Tools::getValue('mfa_mail');
            $storedCode = $this->context->cookie->mfa_mail;
            $expirationTime = $this->context->cookie->mfa_mail;
            $customerId = $this->context->cookie->mfa_id_customer;

            // Vérification : le code soumis est correct ET il n'a pas expiré
            if ($submittedCode == $storedCode && time() < $expirationTime) {

                unset($this->context->cookie->mfa_mail, $this->context->cookie->mfa_time, $this->context->cookie->mfa_id_customer);

                // On récupère l'objet Customer et on met à jour le contexte pour le connecter
                $customer = new Customer((int)$customerId);
                $this->context->updateCustomer($customer);

                Tools::redirect('index.php?controller=my-account');
            } else {
                $this->errors[] = $this->trans('Le code de vérification est invalide ou a expiré.', [], 'Modules.Multifacteurauthentification.Shop');
            }
        }
    }
}