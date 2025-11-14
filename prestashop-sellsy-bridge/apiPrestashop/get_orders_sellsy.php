<?php
/**
 * Script : get_orders_sellsy.php
 * Description :
 *   - Récupère toutes les commandes PrestaShop via l'API
 *   - Transforme les données pour Sellsy (clients, produits, totaux)
 *   - Sauvegarde dans output/orders_sellsy.json
 */


require_once __DIR__ . '/../config.php';  // Chemin vers config.php

$api_url     = PRESTA_API_URL . 'orders?output_format=JSON&display=full';
$api_key     = PRESTA_API_KEY;
$output_dir  = OUTPUT_DIR;
$output_file = $output_dir . '/orders_sellsy.json';


// --- Création du dossier output si nécessaire ---
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}

// --- 1️⃣ Appel API PrestaShop ---
echo "📦 Récupération des commandes depuis PrestaShop...\n";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "❌ Erreur cURL : " . curl_error($ch) . "\n";
    exit(1);
}

curl_close($ch);

// --- 2️⃣ Décodage JSON ---
$data = json_decode($response, true);
if (!isset($data['orders']) || empty($data['orders'])) {
    echo "⚠️ Aucune commande trouvée dans la réponse API.\n";
    file_put_contents($output_file, $response);
    exit(0);
}

$orders = $data['orders'];
echo "✅ " . count($orders) . " commande(s) récupérée(s).\n";

// --- 3️⃣ Transformation vers format Sellsy ---
$sellsy_orders = [];

foreach ($orders as $order) {
    // --- Statut simplifié ---
    $status = ((int)($order['current_state'] ?? 0) === 6) ? 'paid' : 'pending';

    // --- Lignes produits ---
    $lines = [];
    if (isset($order['associations']['order_rows'])) {
        $rows = $order['associations']['order_rows'];
        // Cas d'une seule ligne
        if (isset($rows['product_id'])) {
            $rows = [$rows];
        }
        foreach ($rows as $row) {
            $lines[] = [
                'reference' => $row['product_reference'] ?? '',
                'name' => $row['product_name'] ?? '',
                'quantity' => (float)($row['product_quantity'] ?? 0),
                'price_ht' => (float)($row['unit_price_tax_excl'] ?? 0),
                'price_ttc' => (float)($row['unit_price_tax_incl'] ?? 0)
            ];
        }
    }

    // --- Format final Sellsy ---
    $sellsy_orders[] = [
        'order_reference' => $order['reference'] ?? '',
        'date' => substr($order['date_add'] ?? '', 0, 10),
        'customer_id' => $order['id_customer'] ?? '',
        'total_ht' => (float)($order['total_paid_tax_excl'] ?? 0),
        'total_ttc' => (float)($order['total_paid_tax_incl'] ?? 0),
        'shipping_ht' => (float)($order['total_shipping_tax_excl'] ?? 0),
        'shipping_ttc' => (float)($order['total_shipping_tax_incl'] ?? 0),
        'currency' => 'EUR',
        'status' => $status,
        'payment_mode' => $order['payment'] ?? '',
        'comment' => $order['note'] ?? '',
        'products' => $lines
    ];
}

// --- 4️⃣ Sauvegarde JSON ---
file_put_contents($output_file, json_encode($sellsy_orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n✅ Données prêtes pour Sellsy dans : $output_file\n";
