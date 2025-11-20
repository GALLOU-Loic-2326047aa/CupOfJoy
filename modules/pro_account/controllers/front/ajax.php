<?php

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

    protected function ajaxProcessValidateSiret()
    {
        $siret = Tools::getValue('siret');

        if (strlen($siret) !== 14 || !preg_match('/^[0-9]{14}$/', $siret)) {
            $this->ajaxResponse(false, 'Format invalide. Un SIRET doit contenir exactement 14 chiffres.');
            return;
        }

        $apiKey = $_ENV['API_KEY_SIRENE'] ?? null;

        if (!$apiKey) {
            $this->ajaxResponse(false, 'La clé API n\'est pas configurée.');
        }

        $apiUrl = "https://api.insee.fr/api-sirene/3.11/siret/" . urlencode($siret);

        $curl = curl_init($apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "X-INSEE-Api-Key-Integration: " . $apiKey,
            "Accept: application/json"
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            $this->ajaxResponse(false, 'Erreur de connexion à l\'API INSEE : ' . $error_msg);
        }

        curl_close($curl);

        $data = json_decode($response, true);

        if ($http_code == 200 && isset($data['etablissement'])) {
            $uniteLegale = $data['etablissement']['uniteLegale'];

            $companyName = $uniteLegale['denominationUniteLegale'] ?? null;

            if (!$companyName) {
                $nom = $uniteLegale['nomUniteLegale'] ?? '';
                $prenom = $uniteLegale['prenomUsuelUniteLegale'] ?? '';
                $companyName = trim($prenom . ' ' . $nom);
            }

            if (empty($companyName)) {
                $companyName = 'Nom non disponible';
            }

            $this->ajaxResponse(true, 'SIRET valide.', ['company_name' => $companyName]);

        } elseif ($http_code == 404) {
            $this->ajaxResponse(false, 'Ce numéro de SIRET n\'existe pas ou a été fermé.');
        } elseif ($http_code == 401 || $http_code == 403) {
            $this->ajaxResponse(false, 'Erreur d\'authentification API (Vérifiez votre clé).');
        } elseif ($http_code == 429) {
            $this->ajaxResponse(false, 'Quota d\'appels API dépassé. Réessayez plus tard.');
        } else {
            $errorMessage = $data['header']['message'] ?? 'Erreur inconnue lors de la vérification.';
            $this->ajaxResponse(false, $errorMessage . " (Code: $http_code)");
        }
    }

    protected function ajaxResponse($success, $message, $data = [], $http_code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($http_code);
        $response_data = array_merge(['success' => (bool)$success, 'message' => $message], $data);
        echo json_encode($response_data);
        die();
    }
}