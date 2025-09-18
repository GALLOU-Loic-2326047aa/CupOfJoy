<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Tests\PrestaShop\CircuitBreaker\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PrestaShop\CircuitBreaker\Client\GuzzleClient;
use PrestaShop\CircuitBreaker\Exception\UnavailableServiceException;

/**
 * @group common
 */
class GuzzleClientTest extends TestCase
{
    public function testRequestWorksAsExpected()
    {
        $client = new GuzzleClient();

        $this->assertNotNull($client->request('https://www.google.com', [
            'method' => 'GET',
        ]));
    }

    public function testWrongRequestThrowsAnException()
    {
        $this->expectException(UnavailableServiceException::class);

        $client = new GuzzleClient();
        $client->request('http://not-even-a-valid-domain.xxx', []);
    }

    public function testTheClientAcceptsHttpMethodOverride()
    {
        $client = new GuzzleClient([
            'method' => 'HEAD',
        ]);

        $this->assertEmpty($client->request('https://www.google.fr', []));
    }

    /**
     * @dataProvider getSupportedMethods
     */
    public function testSupportedMethod(string $method, bool $throwException)
    {
        if ($throwException) {
            $this->expectException(UnavailableServiceException::class);
            $this->expectExceptionMessage(sprintf('Unsupported method: "%s"', $method));
        } else {
            $this->expectNotToPerformAssertions();
        }

        $mock = new MockHandler([
            new Response(200),
        ]);

        $client = new GuzzleClient([
            'method' => $method,
            'handler' => $mock,
        ]);
        $client->request('https://www.google.fr', []);
    }

    public function getSupportedMethods()
    {
        return [
            ['HEAD', false],
            ['GET', false],
            ['POST', false],
            ['PUT', false],
            ['DELETE', false],
            ['OPTIONS', false],
            ['UNKNOWN_METHOD', true],
        ];
    }
}
