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
        if (Tools::isSubmit('submitMfaCode')) {
            $cookie = new Cookie('ps-mfa');
            $submittedCode = Tools::getValue('mfa_mail');

            $storedCode = $cookie->mfa_code;
            $expirationTime = $cookie->mfa_time;
            $customerId = (int)$cookie->mfa_id_customer;

            if ($storedCode && $submittedCode == $storedCode && time() < $expirationTime) {

                // On marque le client comme vérifié
                Db::getInstance()->execute('
                    REPLACE INTO `' . _DB_PREFIX_ . 'mfa` (`id_customer`, `is_verified`) 
                    VALUES (' . $customerId . ', 1)
                ');

                unset($cookie->mfa_code, $cookie->mfa_time, $cookie->mfa_id_customer);
                $cookie->write();

                $customer = new Customer($customerId);
                $this->context->updateCustomer($customer);
                Tools::redirect('index.php?controller=my-account');
            } else {
                $this->errors[] = $this->trans('Le code de vérification est invalide ou a expiré.', [], 'Modules.Multifacteurauthentification.Shop');
            }
        }
    }
}