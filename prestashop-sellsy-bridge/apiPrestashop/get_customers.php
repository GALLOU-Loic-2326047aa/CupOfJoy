<?php
/**
 * Script : get_customers.php
 * Description : Récupère la liste des clients depuis l'API PrestaShop
 *               et enregistre les données dans un fichier JSON local.
 */

require_once __DIR__ . '/../config.php';  // adapte le chemin selon l'emplacement du script

$api_url = PRESTA_API_URL . 'customers';
$api_key = PRESTA_API_KEY;
$output_dir = OUTPUT_DIR;
$output_file = OUTPUT_DIR . '/customers.json';

// --- Création du dossier output si nécessaire ---
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}

// --- Préparation de la requête cURL ---
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':'); // Authentification basique
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

// --- Exécution de la requête ---
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Erreur cURL : " . curl_error($ch) . "\n";
    exit(1);
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo "Erreur HTTP ($http_code) lors de la requête API.\n";
    file_put_contents($output_file, $response); // on garde la réponse brute
    exit(1);
}

// --- Traitement du JSON ---
$data = json_decode($response, true);
if ($data === null) {
    // Tentative de lecture XML si JSON invalide
    try {
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
        $data = json_decode(json_encode($xml), true);
    } catch (Exception $e) {
        echo "Erreur de parsing : " . $e->getMessage() . "\n";
        file_put_contents($output_file, $response);
        exit(1);
    }
}

// --- Sauvegarde du fichier JSON ---
file_put_contents($output_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Données clients enregistrées dans : $output_file\n";
