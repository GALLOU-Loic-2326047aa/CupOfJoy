<?php

/*if (!class_exists('PsBannerItem')) {
    throw new Exception('La classe PsBannerItem n\'est pas chargée');
}*/

class AdminPsBannerController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'ps_banner_item';
        $this->className = 'PsBannerItem';
        $this->_orderBy = 'id_ps_banner_item';
        $this->_orderWay = 'ASC';
        $this->lang = false;
        $this->bootstrap = true;

        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function init()
    {
        parent::init();

        $this->identifier = 'id_ps_banner_item';

        $this->fields_list = [
            'id_ps_banner_item' => ['title' => $this->trans('ID', [], 'Modules.ps_banner.Admin'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'image'             => ['title' => $this->trans('Image', [], 'Modules.ps_banner.Admin')],
            'link'              => ['title' => $this->trans('Lien', [], 'Modules.ps_banner.Admin')],
            'description'       => ['title' => $this->trans('Description', [], 'Modules.ps_banner.Admin')],
        ];
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Banner', [], 'Modules.ps_banner.Admin'),
            ],
            'input'  => [
                [
                    'type'     => 'file',
                    'label'    => $this->trans('Image', [], 'Modules.ps_banner.Admin'),
                    'name'     => 'image',
                    'required' => true,
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->trans('Lien', [], 'Modules.ps_banner.Admin'),
                    'name'     => 'link',
                    'size'     => 100,
                    'required' => false,
                ],
                [
                    'type'          => 'textarea',
                    'label'         => $this->trans('Description', [], 'Modules.ps_banner.Admin'),
                    'name'          => 'description',
                    'cols'          => 40,
                    'rows'          => 10,
                    'autoload_rte'  => true,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Modules.ps_banner.Admin'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        if (Tools::isSubmit('updateps_banner_item') || Tools::getValue('id_ps_banner_item')) {
            $obj = $this->loadObject(true);
            if($obj && Validate::isLoadedObject($obj)){
                $this->fields_value['image'] = $obj->image;
                $this->fields_value['link'] = $obj->link;
                $this->fields_value['description'] = $obj->description;
            }
        }

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $id    = Tools::getValue('id_ps_banner_item');
            $image = $_FILES['image'] ?? null;

            if ($image && isset($image['tmp_name']) && !empty($image['tmp_name'])) {
                $ext         = pathinfo($image['name'], PATHINFO_EXTENSION);
                $fileName    = md5(uniqid()) . '.' . $ext;
                $destination = dirname(__FILE__) . '/../../img/' . $fileName;

                if (!move_uploaded_file($image['tmp_name'], $destination)) {
                    $this->errors[] = $this->trans('Error uploading image.', [], 'Modules.ps_banner.Admin');
                    return;
                }
            }

            if ($id) {
                Db::getInstance()->update('ps_banner_item', [
                    'image'       => isset($fileName) ? pSQL($fileName) : pSQL(Tools::getValue('image_old')),
                    'link'        => pSQL(Tools::getValue('link')),
                    'description' => pSQL(Tools::getValue('description')),
                ], 'id_ps_banner_item = ' . (int)$id);
            } else {
                Db::getInstance()->insert('ps_banner_item', [
                    'image'       => pSQL($fileName ?? ''),
                    'link'        => pSQL(Tools::getValue('link')),
                    'description' => pSQL(Tools::getValue('description')),
                ]);
            }

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminPsBanner'));
        }

        if (Tools::isSubmit('deleteps_banner_item')) {
            $id = (int) Tools::getValue('id_ps_banner_item');
            if (!$id) {
                $this->errors[] = $this->trans('ID manquant ou invalide', [], 'Modules.ps_banner.Admin');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminPsBanner'));
            }
            $obj = new PsBannerItem($id);
            if (!Validate::isLoadedObject($obj)) {
                $this->errors[] = $this->trans('Objet non chargé', [], 'Modules.ps_banner.Admin');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminPsBanner'));
            }
            if (!$obj->delete()) {
                $this->errors[] = $this->trans('Erreur pendant la suppression', [], 'Modules.ps_banner.Admin');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminPsBanner'));
            }
            $this->success[] = $this->trans('Suppression réussie', [], 'Modules.ps_banner.Admin');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminPsBanner'));
        }

        parent::postProcess();
    }
}
