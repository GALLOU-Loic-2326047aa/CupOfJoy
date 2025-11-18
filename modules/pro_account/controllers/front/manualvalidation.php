<?php

class pro_accountmanualvalidationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('confirmation', false);

        $this->setTemplate('module:pro_account/views/templates/front/manual_validation.tpl');
    }

    public function postProcess()
    {
        // On vérifie si le formulaire a été soumis
        if (Tools::isSubmit('submitManualSiret')) {
            // Récupération des données
            $firstname = Tools::getValue('firstname');
            $lastname = Tools::getValue('lastname');
            $email = Tools::getValue('email');
            $company = Tools::getValue('company_name');
            $siret = Tools::getValue('siret');
            $to_email = 'killian.gurrea@etu.univ-amu.fr'; // @TODO modif avec l'addresse final
            $to_name = 'Administrateur Boutique';

            if (empty($firstname) || empty($lastname) || !Validate::isEmail($email) || empty($company) || empty($siret)) {
                $this->errors[] = $this->l('Tous les champs sont obligatoires. Veuillez vérifier les informations saisies.');
                return;
            }

            // Préparation des variables pour le template d'email
            $template_vars = [
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{email}' => $email,
                '{company_name}' => $company,
                '{siret}' => $siret,
            ];

            // Envoi de l'email via la fonction native de PrestaShop
            $sent = Mail::Send(
                (int)$this->context->language->id, // id_lang
                'manual_siret_validation', // nom du template d'email
                $this->l('Demande de validation manuelle de SIRET'), // Sujet
                $template_vars, // variables
                $to_email, // destinataire
                $to_name, // nom du destinataire
                $email, // email de l'expéditeur (From)
                $firstname . ' ' . $lastname, // nom de l'expéditeur
                null,
                null,
                _PS_MODULE_DIR_ . '../mails/' // Chemin vers les templates
            );

            if ($sent) {
                $this->context->smarty->assign('confirmation', $this->l('Votre demande a bien été envoyée. Nous reviendrons vers vous rapidement.'));
            } else {
                $this->errors[] = $this->l('Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.');
            }
        }
    }
}