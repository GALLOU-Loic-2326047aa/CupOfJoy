<?php

class AdminProAccountBusinessController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'customer';
        $this->className = 'Customer';
        $this->lang = false;

        parent::__construct();

        $this->meta_title = $this->module->l('Création de Compte Business');
    }

    public function initContent()
    {
        parent::initContent();
        $this->content .= $this->renderForm();
        $this->context->smarty->assign('content', $this->content);
    }

    // Affiche le formulaire pour créer un compte pro côté admin
    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Créer un nouveau compte PRO'),
                'icon' => 'icon-briefcase'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->l('Prénom'),
                    'name' => 'firstname',
                    'required' => true,
                    'col' => 4
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Nom'),
                    'name' => 'lastname',
                    'required' => true,
                    'col' => 4
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Email'),
                    'name' => 'email',
                    'required' => true,
                    'col' => 4
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Nom de l\'entreprise'),
                    'name' => 'company_name',
                    'required' => true,
                    'col' => 4
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Numéro de SIRET'),
                    'name' => 'siret',
                    'required' => true,
                    'col' => 4
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Créer le compte et envoyer l\'email'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        return parent::renderForm();
    }

    // Fonction qui gère la création d'un compte pro côté admin, créer un mot de passe totalement aléatoire
    // Et envoie un mail au client avec les informations du compte avec le mot de passe généré
    public function postProcess()
    {
        if (Tools::isSubmit('submitAddcustomer')) {
            $firstname = Tools::getValue('firstname');
            $lastname = Tools::getValue('lastname');
            $email = Tools::getValue('email');
            $company_name = Tools::getValue('company_name');
            $siret = Tools::getValue('siret');

            if (empty($firstname) || empty($lastname) || empty($email) || empty($company_name) || empty($siret)) {
                $this->errors[] = $this->module->l('Tous les champs sont obligatoires.');
                return;
            }

            if (!Validate::isEmail($email)) {
                $this->errors[] = $this->module->l('Format d\'email invalide.');
                return;
            }

            if (Customer::customerExists($email)) {
                $this->errors[] = $this->module->l('Cet email est déjà utilisé par un autre client.');
                return;
            }

            // Mot de pass généré aléatoirement
            $rawPassword = Tools::passwdGen(8);
            $hashedPassword = Tools::hash($rawPassword);

            $template_vars = [
                '{firstname}' => $firstname,
                '{lastname}' => $lastname,
                '{email}' => $email,
                '{password}' => $rawPassword, // On envoie le mot de passe en clair
                '{company_name}' => $company_name,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
                '{shop_url}' => Context::getContext()->link->getPageLink('index', true, Context::getContext()->language->id)
            ];

            $emailSent = Mail::Send(
                (int)$this->context->language->id,
                'account_creation_pro',
                $this->module->l('Vos identifiants de connexion PRO'),
                $template_vars,
                $email,
                $firstname . ' ' . $lastname,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . '../mails/en/'
            );

            if ($emailSent) {
                $customer = new Customer();
                $customer->firstname = $firstname;
                $customer->lastname = $lastname;
                $customer->email = $email;
                $customer->passwd = $hashedPassword;
                $customer->active = 1;
                $customer->id_shop = $this->context->shop->id;

                if ($customer->add()) {
                    Db::getInstance()->insert('customer_pro_data', [
                        'id_customer' => (int)$customer->id,
                        'company_name' => pSQL($company_name),
                        'siret' => pSQL($siret),
                    ]);

                    Tools::redirectAdmin(self::$currentIndex.'&conf=3&token='.$this->token);
                } else {
                    $this->errors[] = $this->module->l('L\'email a été envoyé, mais une erreur est survenue lors de la création du client en base de données.');
                }
            } else {
                $this->errors[] = $this->module->l('Erreur lors de l\'envoi de l\'email. Le compte n\'a PAS été créé. Vérifiez votre configuration mail et les templates.');
            }
        }
    }
}
