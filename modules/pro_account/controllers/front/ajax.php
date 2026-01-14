<?php

require_once _PS_MODULE_DIR_ . 'pro_account/classes/SireneService.php';

class pro_accountajaxModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        if (!$this->isXmlHttpRequest()) {
            $this->ajaxResponse(false, 'Accès non autorisé', [], 403);
        }

        $action = Tools::getValue('action');
        if ($action === 'validateSiret') {
            $this->ajaxProcessValidateSiret();
        }

        $this->ajaxResponse(false, 'Action non reconnue', [], 400);
    }

    // Fonction qui vérfie si le numéro de Siret rentré par le client
    protected function ajaxProcessValidateSiret()
    {
        $siret = Tools::getValue('siret');
        $apiKey = $_ENV['API_KEY_SIRENE'] ?? null;

        // On utilise notre nouvelle classe
        $service = new SireneService($apiKey);
        $result = $service->checkSiret($siret);

        // On renvoie la réponse
        if ($result['success']) {
            $this->ajaxResponse(true, $result['message'], ['company_name' => $result['company_name']]);
        } else {
            $this->ajaxResponse(false, $result['message']);
        }
    }

    // Fonction qui gère la réponse de l'API
    protected function ajaxResponse($success, $message, $data = [], $http_code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($http_code);
        $response_data = array_merge(['success' => (bool)$success, 'message' => $message], $data);
        echo json_encode($response_data);
        die();
    }
}