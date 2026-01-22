<?php

class SireneService
{
    private $apiKey;
    private $apiUrl = "https://api.insee.fr/api-sirene/3.11/siret/";

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    //  Vérifie un SIRET auprès de l'INSEE
    public function checkSiret($siret)
    {
        // Validation format
        if (strlen($siret) !== 14 || !preg_match('/^[0-9]{14}$/', $siret)) {
            return ['success' => false, 'message' => 'Format invalide (14 chiffres requis).'];
        }

        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'Clé API non configurée.'];
        }

        // Appel API
        $curl = curl_init($this->apiUrl . urlencode($siret));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "X-INSEE-Api-Key-Integration: " . $this->apiKey,
            "Accept: application/json"
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            return ['success' => false, 'message' => 'Erreur cURL : ' . $error];
        }
        curl_close($curl);

        // Traitement réponse
        $data = json_decode($response, true);

        if ($http_code == 200 && isset($data['etablissement'])) {
            $uniteLegale = $data['etablissement']['uniteLegale'];
            $companyName = $uniteLegale['denominationUniteLegale'] ?? null;

            if (!$companyName) {
                $nom = $uniteLegale['nomUniteLegale'] ?? '';
                $prenom = $uniteLegale['prenomUsuelUniteLegale'] ?? '';
                $companyName = trim($prenom . ' ' . $nom);
            }

            return [
                'success' => true,
                'message' => 'SIRET valide.',
                'company_name' => $companyName ?: 'Nom non disponible'
            ];
        } elseif ($http_code == 404) {
            return ['success' => false, 'message' => 'SIRET introuvable ou fermé.'];
        } elseif ($http_code == 401 || $http_code == 403) {
            return ['success' => false, 'message' => 'Erreur API (Clé invalide).'];
        } else {
            return ['success' => false, 'message' => 'Erreur API Code: ' . $http_code];
        }
    }
}