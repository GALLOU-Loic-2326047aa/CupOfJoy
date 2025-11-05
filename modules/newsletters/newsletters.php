<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NewsLetters extends Module
{
    private $html = '';

    public function __construct()
    {
        $this->name = 'newsletters';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'LoicGallou';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('News Letter', [], 'Modules.NewsLetters.Admin');
        $this->description = $this->trans('This is a modules who sends emails (newsletter) to customer and to recommend a lot of product.', [], 'Modules.NewsLetters.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];
    }





}