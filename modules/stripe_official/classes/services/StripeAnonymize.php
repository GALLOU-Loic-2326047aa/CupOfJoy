<?php

/**
 * Copyright (c) since 2010 Stripe, Inc. (https://stripe.com)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Stripe <https://support.stripe.com/contact/email>
 * @copyright Since 2010 Stripe, Inc.
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class StripeAnonymize
{
    private $anonymizationRules = [
        'email' => 'fakeEmail',
        'customer_email' => 'fakeCustomerEmail',
        'name' => 'fakeName',
        'firstname' => 'fakeFirstName',
        'lastname' => 'fakeLastName',
        'phone' => 'fakePhone',
        'line1' => 'fakeLine1',
        'line2' => 'fakeLine2',
        'street' => 'fakeStreet',
        'postal_code' => 'fakePostalCode',
        'zipCode' => 'fakeZipCode',
        'city' => 'fakeCity',
        'state' => 'fakeState',
        'country' => 'fakeCountry',
        'countryIso' => 'fakeCountryIso',
    ];

    public function anonymize($data): array
    {
        // Handle JSON string
        if (is_string($data)) {
            $data = json_decode($data, true);
            if ($data === null) {
                return [];
            }
        }

        // Handle StripeObject or other objects with toArray()
        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray(true); // Stripe SDK objects
            } else {
                $data = json_decode(json_encode($data), true); // Generic object fallback
            }
        }

        if (!is_array($data)) {
            return [];
        }

        return $this->recursiveAnonymize($data);
    }

    private function recursiveAnonymize($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveAnonymize($value);
            } elseif (isset($this->anonymizationRules[$key])) {
                $data[$key] = $this->anonymizeValue($this->anonymizationRules[$key], $value);
            }
        }

        return $data;
    }

    private function anonymizeValue(string $strategy, $value)
    {
        switch ($strategy) {
            case 'fakeEmail':
            case 'fakeCustomerEmail':
                return 'anonymous_' . rand(1000, 9999) . '@prestashop.com';
            case 'fakeName':
                return $this->generateRandomFirstName() . ' ' . $this->generateRandomLastName();
            case 'fakeFirstName':
                return $this->generateRandomFirstName();
            case 'fakeLastName':
                return $this->generateRandomLastName();
            case 'fakePhone':
                return $this->generateRandomPhoneNumber();
            case 'fakeLine2':
            case 'fakeLine1':
            case 'fakeStreet':
                return $this->generateRandomLine();
            case 'fakePostalCode':
            case 'fakeZipCode':
                return rand(1, 99999);
            case 'fakeCity':
                return $this->generateRandomCity();
            case 'fakeCountry':
            case 'fakeCountryIso':
                return $this->generateRandomCountry();
            case 'fakeState':
                return $this->generateRandomState();
            case 'nullify':
                return null;
            default:
                return $value;
        }
    }

    public function generateRandomFirstName(): string
    {
        $firstNames = ['John', 'Alice', 'Maria', 'Liam', 'Sophie', 'David', 'Olivia', 'Noah', 'Lina', 'Marc'];

        return $firstNames[array_rand($firstNames)];
    }

    public function generateRandomLastName(): string
    {
        $lastNames = ['Smith', 'Johnson', 'Brown', 'Taylor', 'Anderson', 'Lee', 'Garcia', 'Martin', 'Crawford', 'Steven'];

        return $lastNames[array_rand($lastNames)];
    }

    public function generateRandomLine(): string
    {
        $streets = ['Main St', 'Elm St', 'Maple Ave', 'Broadway', 'Oak Lane', 'Sunset Blvd', 'Rue de l Eglise', 'Place de l Eglise', 'Grande Rue', 'Via Roma', 'Via Garibaldi', 'Via Marconi'];

        return $streets[array_rand($streets)] . ' ' . rand(1, 9999);
    }

    public function generateRandomCity(): string
    {
        $cities = ['Rome', 'Paris', 'Athena', 'Berlin', 'Lion', 'Budapest', 'Timisoara', 'Madrid', 'Barcelona', 'Milano'];

        return $cities[array_rand($cities)];
    }

    public function generateRandomCountry(): string
    {
        $countries = ['FR', 'IT', 'DE', 'RO', 'HU', 'AT', 'ES', 'GR', 'MT', 'UK'];

        return $countries[array_rand($countries)];
    }

    public function generateRandomState(): string
    {
        $state = ['Indiana', 'Florida', 'Nevada', 'New York', 'Virginia', 'Bavaria', 'Bremen', 'Hamburg'];

        return $state[array_rand($state)];
    }

    public function generateRandomPhoneNumber(): string
    {
        $areaCode = rand(200, 999);           // Avoid invalid codes like 000 or 123
        $prefix = rand(200, 999);             // Same here
        $lineNumber = rand(1000, 9999);       // Four-digit number

        return sprintf('(%03d) %03d-%04d', $areaCode, $prefix, $lineNumber);
    }
}
