<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/SireneService.php';

class SireneServiceTest extends TestCase
{
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = $_ENV['API_KEY_SIRENE'] ?? getenv('API_KEY_SIRENE');
    }

    /**
     * Test 1 : Vérifier un SIRET valide (Google France)
     */
    public function testValidSiretGoogle()
    {
        $service = new SireneService($this->apiKey);

        // SIRET de Google France
        $siretGoogle = '44306184100047';

        $result = $service->checkSiret($siretGoogle);

        // Assertions (Vérifications)
        $this->assertTrue($result['success'], 'Le SIRET Google devrait être valide');
        $this->assertEquals('GOOGLE FRANCE', $result['company_name'], 'Le nom de l\'entreprise devrait être GOOGLE FRANCE');
    }

    /**
     * Test 2 : Vérifier un SIRET invalide (Faux numéro)
     */
    public function testInvalidSiret()
    {
        $service = new SireneService($this->apiKey);

        // Un SIRET qui n'existe pas
        $siretFaux = '11111111111111';

        $result = $service->checkSiret($siretFaux);

        $this->assertFalse($result['success'], 'Ce SIRET ne devrait pas être valide');
        // On vérifie que le message contient bien une erreur 404 ou introuvable
        $this->assertStringContainsString('introuvable', $result['message']);
    }

    /**
     * Test 3 : Vérifier le format (Moins de 14 chiffres)
     */
    public function testBadFormatSiret()
    {
        $service = new SireneService($this->apiKey);

        $siretCourt = '12345';

        $result = $service->checkSiret($siretCourt);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Format invalide', $result['message']);
    }

    /**
     * Test 4 : Vérifier la connexion (Mauvaise clé API)
     */
    public function testBadApiKey()
    {
        // On instancie avec une fausse clé
        $service = new SireneService('MAUVAISE_CLE_TOTO');

        $siretGoogle = '44306184100047';
        $result = $service->checkSiret($siretGoogle);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Erreur API', $result['message']);
    }
}