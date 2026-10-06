<?php
// Расчёт доставки СДЭК по весу заказа через калькулятор API. Только расчёт — заказы в СДЭК не создаём.
// Ключи — опции bt/cdek_account, bt/cdek_secure (не в гите). Тариф, город отправителя — опции bt/cdek_* с дефолтами.

namespace Bt\Cdek;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Sale;

class Api
{
    private const BASE = 'https://api.cdek.ru/v2';

    private static function opt(string $n, string $def = ''): string
    {
        return (string)Option::get('bt', $n, $def);
    }

    // OAuth-токен живёт час; держим в опции с запасом
    public static function token(): string
    {
        $tok = self::opt('cdek_token');
        $exp = (int)self::opt('cdek_token_exp', '0');
        if ($tok !== '' && $exp > time() + 60) {
            return $tok;
        }
        $acc = self::opt('cdek_account');
        $sec = self::opt('cdek_secure');
        if ($acc === '' || $sec === '') {
            return '';
        }
        $h = new HttpClient(['socketTimeout' => 10, 'streamTimeout' => 15]);
        $h->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        $res = $h->post(self::BASE . '/oauth/token', [
            'grant_type' => 'client_credentials', 'client_id' => $acc, 'client_secret' => $sec,
        ]);
        $d = json_decode((string)$res, true);
        if (empty($d['access_token'])) {
            return '';
        }
        Option::set('bt', 'cdek_token', $d['access_token']);
        Option::set('bt', 'cdek_token_exp', (string)(time() + (int)($d['expires_in'] ?? 3000)));
        return $d['access_token'];
    }

    private static function req(string $method, string $path, array $body = []): ?array
    {
        $tok = self::token();
        if ($tok === '') {
            return null;
        }
        $h = new HttpClient(['socketTimeout' => 10, 'streamTimeout' => 15]);
        $h->setHeader('Authorization', 'Bearer ' . $tok);
        $h->setHeader('Content-Type', 'application/json');
        $h->setHeader('Accept', 'application/json');
        $url = self::BASE . $path;
        $raw = $method === 'GET'
            ? $h->get($url . ($body ? '?' . http_build_query($body) : ''))
            : $h->post($url, Json::encode($body));
        $d = json_decode((string)$raw, true);
        return is_array($d) ? $d : null;
    }

    // Код города СДЭК по коду локации Битрикса; кэш в опции bt/cdek_city_map
    public static function cityCode(string $locCode): ?int
    {
        if ($locCode === '') {
            return null;
        }
        $map = json_decode(self::opt('cdek_city_map', '{}'), true) ?: [];
        if (isset($map[$locCode])) {
            return $map[$locCode] ?: null;
        }
        $loc = Sale\Location\LocationTable::getList([
            'filter' => ['=CODE' => $locCode, '=NAME.LANGUAGE_ID' => 'ru'],
            'select' => ['N' => 'NAME.NAME', 'R' => 'PARENT.NAME.NAME'],
        ])->fetch();
        if (!$loc) {
            return null;
        }
        $q = ['city' => $loc['N'], 'size' => 1];
        if (!empty($loc['R'])) {
            $q['region'] = $loc['R'];
        }
        $d = self::req('GET', '/location/cities', $q);
        $code = isset($d[0]['code']) ? (int)$d[0]['code'] : 0;
        $map[$locCode] = $code;
        Option::set('bt', 'cdek_city_map', Json::encode($map));
        return $code ?: null;
    }

    // Расчёт тарифа: [цена, срок_дней] или null
    public static function tariff(int $toCity, int $weightGrams): ?array
    {
        $from = (int)self::opt('cdek_from', '270');          // Екатеринбург
        $code = (int)self::opt('cdek_tariff', '137');        // посылка склад-дверь
        $type = (int)self::opt('cdek_type', '1');            // 1 — интернет-магазин
        $min = (int)self::opt('cdek_min_weight', '500');
        $w = max($weightGrams, $min);
        $d = self::req('POST', '/calculator/tariff', [
            'type' => $type,
            'tariff_code' => $code,
            'from_location' => ['code' => $from],
            'to_location' => ['code' => $toCity],
            'packages' => [['weight' => $w]],
        ]);
        if (!isset($d['total_sum'])) {
            // без договора ИМ тариф может не считаться — пробуем обычный тип
            if ($type === 1) {
                $d = self::req('POST', '/calculator/tariff', [
                    'type' => 2, 'tariff_code' => $code,
                    'from_location' => ['code' => $from], 'to_location' => ['code' => $toCity],
                    'packages' => [['weight' => $w]],
                ]);
            }
            if (!isset($d['total_sum'])) {
                return null;
            }
        }
        $days = (int)($d['period_max'] ?? $d['calendar_max'] ?? $d['period_min'] ?? 0);
        return [(float)$d['total_sum'], $days];
    }
}

class Handler extends Sale\Delivery\Services\Base
{
    protected static $isCalculatePriceImmediately = true;
    protected static $whetherAdminExtraServicesShow = true;

    public static function getClassTitle()
    {
        return 'СДЭК (расчёт по весу)';
    }

    public static function getClassDescription()
    {
        return 'Расчёт стоимости доставки СДЭК по весу заказа через калькулятор. Заказы в СДЭК не создаются.';
    }

    protected function calculateConcrete(Sale\Shipment $shipment): Sale\Delivery\CalculationResult
    {
        $r = new Sale\Delivery\CalculationResult();

        $locCode = '';
        $order = $shipment->getCollection()->getOrder();
        if ($order && ($p = $order->getPropertyCollection()->getDeliveryLocation())) {
            $locCode = (string)$p->getValue();
        }
        if ($locCode === '') {
            $r->addError(new \Bitrix\Main\Error('Выберите город для расчёта СДЭК'));
            return $r;
        }
        $city = Api::cityCode($locCode);
        if (!$city) {
            $r->addError(new \Bitrix\Main\Error('СДЭК не доставляет в этот город'));
            return $r;
        }
        $tar = Api::tariff($city, (int)$shipment->getWeight());
        if (!$tar) {
            $r->addError(new \Bitrix\Main\Error('Не удалось рассчитать доставку СДЭК'));
            return $r;
        }
        $r->setDeliveryPrice($tar[0]);
        if ($tar[1] > 0) {
            $r->setPeriodDescription($tar[1] . ' дн.');
        }
        return $r;
    }
}
