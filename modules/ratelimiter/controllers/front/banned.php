<?php
class RateLimiterBannedModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->setTemplate('module:ratelimiter/views/templates/front/banned.tpl');
    }
}