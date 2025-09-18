<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    La Poste
 * @copyright 2007-2025 PrestaShop SA / 2024-2025 La Poste
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
namespace LaPoste\LaPosteProExpeditionsPrestashop\Util;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Parcel point util class
 *
 * Handle parcel points storage and formating
 */
class ParcelPointUtil
{
    /**
     * Normalize a parcel point opening hours
     *
     * @param mixed|null $openingHours opening hours in standard or non standard format
     *
     * @return mixed|null opening hours in standard format
     */
    private static function normalizeOpeningHours($openingHours)
    {
        $result = null;

        if (null !== $openingHours && is_array($openingHours)) {
            $result = [];
            foreach ($openingHours as $openingHour) {
                $validOpeningHours = property_exists($openingHour, 'weekday')
                    && property_exists($openingHour, 'openingPeriods')
                    && is_array($openingHour->openingPeriods);

                if ($validOpeningHours) {
                    $dayOpeningHours = new \stdClass();
                    $dayOpeningHours->weekday = $openingHour->weekday;
                    $dayOpeningHours->openingPeriods = [];
                    foreach ($openingHour->openingPeriods as $period) {
                        $open = property_exists($period, 'openingTime')
                            ? $period->openingTime
                            : (property_exists($period, 'open') ? $period->open : null);
                        $close = property_exists($period, 'closingTime')
                            ? $period->closingTime
                            : (property_exists($period, 'close') ? $period->close : null);

                        $hours = new \stdClass();
                        $hours->open = $open;
                        $hours->close = $close;
                        $dayOpeningHours->openingPeriods[] = $hours;
                    }
                    $result[] = $dayOpeningHours;
                }
            }
        }

        return $result;
    }

    /**
     * Create a new parcel point object
     *
     * @param string|null $network
     * @param string|null $code
     * @param string|null $name
     * @param string $address
     * @param string $zipcode
     * @param string $city
     * @param string $country
     * @param array $openingHours
     * @param string|null $distance
     *
     * @return mixed point in standard format
     */
    public static function createParcelPoint(
        $network,
        $code,
        $name,
        $address,
        $zipcode,
        $city,
        $country,
        $openingHours,
        $distance
    ) {
        $point = null;
        if (null !== $network && null !== $code && null !== $name) {
            $point = new \stdClass();
            $point->network = $network;
            $point->code = $code;
            $point->name = $name;
            $point->address = $address;
            $point->zipcode = $zipcode;
            $point->city = $city;
            $point->country = $country;
            $point->openingHours = self::normalizeOpeningHours($openingHours);
            $point->distanceFromSearchLocation = $distance;
        }

        return $point;
    }

    /**
     * Normalize the point format for retrocompatibility reasons
     *
     * Default format   : format used globally in the module since 1.2.0
     * Old order format : format used in order storage before 1.2.0
     * Old cart format  : format used in cart storage before 1.2.0
     * Api format       : format returned by the plugin's api
     *
     * @param mixed $point in new or olf format
     *
     * @return mixed point in new format
     */
    public static function normalizePoint($point)
    {
        $result = null;

        if (null !== $point && false !== $point) {
            $hasNetwork = property_exists($point, 'network');
            $hasCode = property_exists($point, 'code');
            $hasName = property_exists($point, 'name');
            $hasAddress = property_exists($point, 'address');
            $hasZipcode = property_exists($point, 'zipcode');
            $hasCity = property_exists($point, 'city');
            $hasCountry = property_exists($point, 'country');
            $hasOpeningHours = property_exists($point, 'openingHours');
            $hasOpeningDays = property_exists($point, 'openingDays');
            $hasLocation = property_exists($point, 'location')
                && property_exists($point->location, 'street')
                && property_exists($point->location, 'zipCode')
                && property_exists($point->location, 'city')
                && property_exists($point->location, 'country');
            $hasParcelPoint = property_exists($point, 'parcelPoint')
                && property_exists($point->parcelPoint, 'network')
                && property_exists($point->parcelPoint, 'code')
                && property_exists($point->parcelPoint, 'name');

            $isDefaultFormat = $hasNetwork && $hasCode && $hasName && $hasAddress
                && $hasZipcode && $hasCity && $hasCountry && $hasOpeningHours && !$hasLocation;
            $isOldOrderFormat = $hasNetwork && $hasCode && $hasName && !$hasAddress
                && !$hasZipcode && !$hasCity && !$hasCountry && !$hasOpeningHours && !$hasLocation;
            $isOldCartFormat = $hasParcelPoint;
            $isApiFormat = $hasNetwork && $hasCode && $hasName && !$hasAddress
                && !$hasZipcode && !$hasCity && !$hasCountry && !$hasOpeningHours && $hasLocation && $hasOpeningDays;

            if ($isApiFormat) {
                $result = self::createParcelPoint(
                    $point->network,
                    $point->code,
                    $point->name,
                    $point->location->street,
                    $point->location->zipCode,
                    $point->location->city,
                    $point->location->country,
                    $point->openingDays,
                    $point->distanceFromSearchLocation
                );
            } elseif ($isDefaultFormat) {
                $result = $point;
            } elseif ($isOldOrderFormat) {
                $result = self::createParcelPoint(
                    $point->network,
                    $point->code,
                    $point->name,
                    '',
                    '',
                    '',
                    '',
                    [],
                    null
                );
            } elseif ($isOldCartFormat) {
                $sPoint = $point->parcelPoint;
                $result = self::createParcelPoint(
                    $sPoint->network,
                    $sPoint->code,
                    $sPoint->name,
                    '',
                    '',
                    '',
                    '',
                    [],
                    null
                );
            }
        }

        return $result;
    }

    /**
     * Set chosen parcel point.
     *
     * @param int $cartId cart id
     * @param int $carrierId shipping method id
     * @param mixed $point parcel point to save
     *
     * @return void
     */
    public static function setChosenPoint($cartId, $carrierId, $point)
    {
        $encodedPoint = null === $point ? null : EncodeUtil::encode($point);
        CartStorageUtil::set($cartId, 'lpChosenParcelPoint' . $carrierId, $encodedPoint);
    }

    /**
     * Get chosen parcel point.
     *
     * @param int $cartId cart id
     * @param int $id shipping method id
     *
     * @return mixed
     */
    public static function getChosenPoint($cartId, $id)
    {
        $point = EncodeUtil::decode(
            CartStorageUtil::get($cartId, 'lpChosenParcelPoint' . $id)
        );

        return self::normalizePoint($point);
    }

    /**
     * Set order parcel point.
     *
     * @param int $orderId order id
     *
     * @return mixed
     */
    public static function setOrderParcelPoint($orderId, $point)
    {
        $serializedPoint = null === $point ? null : EncodeUtil::encode($point);
        OrderStorageUtil::set($orderId, 'lpParcelPoint', $serializedPoint);
    }

    /**
     * Get order parcel point.
     *
     * @param int $orderId order id
     *
     * @return mixed
     */
    public static function getOrderParcelPoint($orderId)
    {
        $point = EncodeUtil::decode(OrderStorageUtil::get($orderId, 'lpParcelPoint'));

        // retrocompatibility check
        if (null === $point) {
            $code = OrderStorageUtil::get($orderId, 'lpParcelPointCode');
            $network = OrderStorageUtil::get($orderId, 'lpParcelPointNetwork');
            if (null !== $code && null !== $network) {
                $point = self::createParcelPoint($network, $code, '', '', '', '', '', [], null);
            }
        }

        return self::normalizePoint($point);
    }

    /**
     * Format parcelpoint opening hours into string format
     *
     * @param mixed $parcelpoint parcel point to format
     *
     * @return array of string
     */
    public static function formatParcelPointOpeningHours($parcelpoint)
    {
        $parsedDays = [];

        $closedLabel = \LaPosteProExpeditions::getInstance()->l('Closed     ');

        foreach ($parcelpoint->openingHours as $day) {
            $weekDay = \LaPosteProExpeditions::getInstance()->l(strtolower($day->weekday));
            $parsedDay = strtoupper(substr($weekDay, 0, 1)) . ' ';
            $openingPeriods = $day->openingPeriods;
            $parsedPeriods = [];

            foreach ($openingPeriods as $openingPeriod) {
                $open = $openingPeriod->open;
                $close = $openingPeriod->close;

                if ('' !== $open && '' !== $close) {
                    $parsedPeriods[] = $open . '-' . $close;
                } else {
                    $parsedPeriods[] = $closedLabel;
                }
            }

            $parsedDays[] = $parsedDay . implode(' ', $parsedPeriods);
        }

        return $parsedDays;
    }
}
