<?php

class pro_accountmanualvalidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('confirmation', false);

        $this->setTemplate('module:pro_account/views/templates/front/manual_validation.tpl');
    }

    // Fonction qui gère le formulaire de demande de création d'un compte pro côté client
    public function postProcess()
    {
        if (Tools::isSubmit('submitManualSiret')) {
            $id_contact_webmaster = 1;
            $contact = new Contact($id_contact_webmaster);

            if (Validate::isLoadedObject($contact)) {
                $to_email = $contact->email;
                // On récupère le nom du contact dans la langue actuelle
                $to_name = $contact->name[$this->context->language->id] ?? 'Webmaster';
            } else {
                // Si jamais l'ID 1 a été supprimé, on utilise l'email de la boutique en secours
                $to_email = Configuration::get('PS_SHOP_EMAIL');
                $to_name = Configuration::get('PS_SHOP_NAME');
            }

            $firstname = Tools::getValue('firstname');
            $lastname = Tools::getValue('lastname');
            $email = Tools::getValue('email');
            $company = Tools::getValue('company_name');
            $siret = Tools::getValue('siret');

            if (empty($firstname) || empty($lastname) || !Validate::isEmail($email) || empty($company) || empty($siret)) {
                $this->errors[] = $this->l('Tous les champs sont obligatoires. Veuillez vérifier les informations saisies.');
                return;
            }

            $template_vars = [
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{email}' => $email,
                '{company_name}' => $company,
                '{siret}' => $siret,
            ];

            $sent = Mail::Send(
                (int)$this->context->language->id,
                'manual_siret_validation',
                $this->l('Demande de validation manuelle de SIRET'),
                $template_vars,
                $to_email,
                $to_name,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . '../mails/',
                false,
                null,
                null,
                $email,
            );

            if ($sent) {
                $this->success[] = $this->l('Votre demande a bien été envoyée. Nous reviendrons vers vous rapidement.');

                $loginUrl = $this->context->link->getPageLink('authentication', true);

                $this->redirectWithNotifications($loginUrl);
            } else {
                $this->errors[] = $this->l('Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.');
            }
        }
    }
}
