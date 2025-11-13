<?php

class pro_accountajaxModuleFrontController extends ModuleFrontController
{
    /**
     * @var string Vos clés d'API (à stocker en BDD via un formulaire de configuration idéalement)
     */
    private $consumerKey;
    private $consumerSecret;

    public function init()
    {
        parent::init();

        $logFile = _PS_MODULE_DIR_ . 'pro_account/ajax_debug.log';
        file_put_contents($logFile, "--- " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
        file_put_contents($logFile, "Contrôleur AJAX atteint.\n", FILE_APPEND);

        $this->consumerKey = $_ENV['PUB_KEY_SIRENE'];
        $this->consumerSecret = $_ENV['PRIVATE_KEY_SIRENE'];

        if (!$this->isXmlHttpRequest()) {
            die('Accès non autorisé');
        }

        $action = Tools::getValue('action');
        if ($action == 'validateSiret') {
            $this->ajaxProcessValidateSiret();
        }

        die();
    }

    protected function ajaxProcessValidateSiret()
    {
        $siret = Tools::getValue('siret');

        if (!preg_match('/^[0-9]{14}$/', $siret)) {
            $this->ajaxDie(json_encode(['valid' => false, 'error' => 'Format invalide']));
        }

        // 1. Obtenir le jeton d'accès (token)
        $token = $this->getAccessToken();
        if (!$token) {
            $this->ajaxDie(json_encode(['valid' => false, 'error' => 'Erreur d\'authentification API']));
        }

        // 2. Appeler l'API Sirene pour vérifier le SIRET
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.insee.fr/entreprises/sirene/V3/siret/" . urlencode($siret),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Bearer " . $token
            ],
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200) {
            $data = json_decode($response, true);
            $companyName = $data['etablissement']['uniteLegale']['denominationUniteLegale'] ?? '';
            $this->ajaxDie(json_encode(['valid' => true, 'company_name' => $companyName]));
        } else {
            $this->ajaxDie(json_encode(['valid' => false, 'error' => 'SIRET non trouvé']));
        }
    }

    /**
     * S'authentifie auprès de l'API de l'INSEE pour obtenir un jeton d'accès.
     */
    private function getAccessToken()
    {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.insee.fr/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . $credentials,
                "Content-Type: application/x-www-form-urlencoded"
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        return $data['access_token'] ?? null;
    }
}