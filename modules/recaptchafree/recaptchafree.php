<?php

if (!defined('_PS_VERSION_')) {
    exit;
}
class RecaptchaFree extends Module
{
    public function __construct()
    {
        $this->name = 'recaptchafree';
        $this->tab = 'security features';
        $this->version = '1.0.0';
        $this->author = 'Maxime Allasio';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Version de reCaptcha gratuite');
        $this->description = $this->l('Ajoute Google reCAPTCHA aux formulaires.');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayCustomerAccountForm') &&
            $this->registerHook('actionSubmitAccountBefore') &&
            $this->registerHook('displayCustomerLoginFormAfter') &&
            $this->registerHook('actionAuthentication');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayHeader()
    {
        // Appel de l'api google
        $this->context->controller->registerJavascript(
            'google-recaptcha-api',
            'https://www.google.com/recaptcha/api.js',
            ['server' => 'remote', 'position' => 'bottom', 'priority' => 150]
        );

        $this->context->controller->registerStylesheet(
            'recaptchafree-front-css',
            $this->_path.'views/css/front.css',
            ['media' => 'all', 'priority' => 150]
        );
    }

    public function hookDisplayCustomerAccountForm()
    {
        // Gère l'envoie de la clé publique à google
        $this->context->smarty->assign(
            'recaptcha_site_key',
            '6LfLOwUsAAAAAFymTYIjxqP6Cky9gjqaDDzz-_SG' // Clé publique de google
        );

        return $this->display(__FILE__, 'views/templates/hook/recaptcha.tpl');
    }

    public function hookDisplayCustomerLoginFormAfter()
    {
        return $this->hookDisplayCustomerAccountForm();
    }

    public function hookActionSubmitAccountBefore($params)
    {

        $recaptchaResponse = Tools::getValue('g-recaptcha-response'); // Recupère la réponse du captcha donnée par google

        $secretKey = '6LfLOwUsAAAAACBNM0mfbup56hapuUFRyCVBUYss'; // La clé secrète privée de google

        $validationResult = $this->isRecaptchaValid($recaptchaResponse, $secretKey);

        // La partie où la validation échoue
        if (isset($validationResult['success']) && $validationResult['success'] === false) {
            $this->context->controller->errors[] = $this->l('La vérification reCAPTCHA a échoué.');
            return;
        }
        return true;
    }


    public function hookActionAuthentication($params)
    {

        $recaptchaResponse = Tools::getValue('g-recaptcha-response'); // Recupère la réponse du captcha donnée par google


        $secretKey = '6LfLOwUsAAAAACBNM0mfbup56hapuUFRyCVBUYss'; // La clé secrète privée de google

        $validationResult = $this->isRecaptchaValid($recaptchaResponse, $secretKey);

        // La partie où la validation échoue
        if (isset($validationResult['success']) && $validationResult['success'] === false) {
            $this->context->controller->errors[] = $this->l('La vérification reCAPTCHA a échoué.');
            $this->context->customer->logout();
        }
    }


    private function isRecaptchaValid($response, $secretKey)
    {
        if (empty($response)) {
            return ['success' => false, 'error-codes' => ['missing-input-response']];
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => $secretKey,
            'response' => $response,
            'remoteip' => Tools::getRemoteAddr(),
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ];

        $context  = stream_context_create($options);
        $result = Tools::file_get_contents($url, false, $context);

        // Renvoie la réponse de google
        return json_decode($result, true);
    }
}