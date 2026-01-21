<?php

class Machine_SupportTicketModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // chargement de la classe
        require_once _PS_MODULE_DIR_ . 'machine_support/classes/MachineSupportType.php';

        // récupèration des types
        $types = MachineSupportType::getTypes($this->context->language->id);

        $this->context->smarty->assign([
            'support_types' => $types
        ]);

        $this->setTemplate('module:machine_support/views/templates/front/ticket.tpl');
    }

    // Fonction par défaut de prestashop que gère l'entièreté du module
    // Gère l'envoie de mail en validant les champs de texte demander
    // Ajoute le nouveau ticket support et envoie le mail de validation au client et la notification de l'admin
    public function postProcess()
    {
        if (Tools::isSubmit('submitMachineSupport')) {
            $from = Tools::getValue('from'); // Email
            $phone = Tools::getValue('phone');
            $requestType = Tools::getValue('request_type'); // Récupération du type
            $message = Tools::getValue('message');
            $id_type = (int)Tools::getValue('request_type');

            require_once _PS_MODULE_DIR_ . 'machine_support/classes/MachineSupportType.php';

            $typeObj = new MachineSupportType($id_type, $this->context->language->id);
            $requestType = $typeObj->name;


            // Validation des différentes informations
            if (!Validate::isEmail($from)) {
                $this->errors[] = $this->l('Adresse email invalide.');
            } elseif (empty($message)) {
                $this->errors[] = $this->l('Veuillez décrire le problème.');
            } elseif (empty($phone)) {
                $this->errors[] = $this->l('Le numéro de téléphone est requis.');
            } elseif (empty($requestType) || !Validate::isLoadedObject($typeObj)) {
                $this->errors[] = $this->l('Veuillez choisir un type de demande.');
            }

            if (count($this->errors)) {
                return;
            }

            $id_contact = 2; // ID 2 = Service Client , ID 1 = Webmaster
            $id_customer = 0;

            if ($this->context->customer->isLogged()) {
                $id_customer = (int)$this->context->customer->id;
                // force l'email du compte connecté
                $from = $this->context->customer->email;
            }

            $ct = new CustomerThread();
            $ct->id_contact = $id_contact;
            $ct->id_customer = $id_customer;
            $ct->id_shop = (int)$this->context->shop->id;
            $ct->id_order = 0; // Lié à aucune commande
            $ct->id_lang = (int)$this->context->language->id;
            $ct->email = $from;
            $ct->status = 'open';
            $ct->token = Tools::passwdGen(12);
            $ct->add();

            // Création du message
            if ($ct->id) {

                Db::getInstance()->update(
                    'customer_thread',
                    ['request_type' => pSQL($requestType)],
                    'id_customer_thread = ' . (int)$ct->id
                );

                $cm = new CustomerMessage();
                $cm->id_customer_thread = $ct->id;

                // Format pour l'admin
                $fullMessage = "TYPE DE DEMANDE : " . $requestType . "\n";
                $fullMessage .= "TÉLÉPHONE : " . $phone . "\n\n";
                $fullMessage .= "DESCRIPTION :\n" . $message;

                $cm->message = $fullMessage;
                $cm->save();

                // Envoi des emails
                $this->sendEmails($ct, $fullMessage, $from, $requestType);

                $this->success[] = $this->l('Votre demande de SAV a bien été envoyée. Numéro de ticket : #') . $ct->id;
                $this->redirectWithNotifications($this->context->link->getPageLink('my-account'));
            } else {
                $this->errors[] = $this->l('Une erreur est survenue lors de la création du ticket.');
            }
        }
    }

    // Fonction qui gère l'envoie de mail
    protected function sendEmails($ct, $messageBody, $clientEmail, $subjectType)
    {
        $shopName = Configuration::get('PS_SHOP_NAME');
        $id_lang = (int)$this->context->language->id;
        $templateFile = _PS_MODULE_DIR_ . '../mails/';

        // Email de confirmation au client
        Mail::Send(
            $id_lang,
            'ticket_confirmation_client', // Nom du template
            'Votre demande de SAV #' . $ct->id . ' - ' . $shopName,
            [
                '{ticket_id}' => $ct->id,
                '{request_type}' => $subjectType,
                '{message}' => nl2br($messageBody)
            ],
            $clientEmail, // Client
            null,
            null, null, null, null,
            $templateFile
        );

        // Email à l'admin
        $contact = new Contact(2, $id_lang);
        $adminEmail = Validate::isLoadedObject($contact) ? $contact->email : Configuration::get('PS_SHOP_EMAIL');

        Mail::Send(
            $id_lang,
            'ticket_notification_admin', // Nom du template
            'Nouveau Ticket SAV #' . $ct->id . ' (' . $subjectType . ')',
            [
                '{ticket_id}' => $ct->id,
                '{email}' => $clientEmail,
                '{request_type}' => $subjectType,
                '{message}' => nl2br($messageBody)
            ],
            $adminEmail, // Service Client
            null,
            null, null, null, null,
            $templateFile
        );
    }
}
