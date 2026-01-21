<?php
require_once _PS_MODULE_DIR_ . 'machine_support/classes/MachineSupportType.php';

class AdminMachineSupportTypesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'support_client_type';
        $this->className = 'MachineSupportType';
        $this->identifier = 'id_support_client_type';
        $this->lang = true; // Multilangue

        parent::__construct();

        $this->meta_title = $this->module->l('Types de demande SAV');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->fields_list = [
            'id_support_client_type' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->module->l('Nom'),
            ],
            'active' => [
                'title' => $this->module->l('Actif'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
            ]
        ];
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Type de demande'),
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->l('Nom'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Activé'),
                    'name' => 'active',
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->module->l('Oui')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->module->l('Non')],
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->module->l('Enregistrer'),
            ]
        ];

        return parent::renderForm();
    }
}