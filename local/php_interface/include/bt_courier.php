<?php
// Курьер BEVERTEAM: цена по расстоянию от склада — км по прямой × коэффициент дорог × цена за км, не меньше минимума.
// Координаты адресов — подсказки DaData (опция bt/dadata_key), кеш 30 дней. Адрес не нашёлся — минимальная цена.

namespace Bt\Courier;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale;

class Handler extends Sale\Delivery\Services\Base
{
    protected static $isCalculatePriceImmediately = true;

    public static function getClassTitle()
    {
        return 'Курьер по расстоянию от склада';
    }

    public static function getClassDescription()
    {
        return 'Цена = км по прямой от склада × коэффициент дорог × цена за км, но не меньше минимальной. Склад — адрес из контактов сайта.';
    }

    protected function getConfigStructure()
    {
        return ['MAIN' => ['TITLE' => 'Тариф', 'DESCRIPTION' => 'Цена = км по прямой × коэффициент дорог × цена за км, не меньше минимальной', 'ITEMS' => [
            'MIN' => ['TYPE' => 'NUMBER', 'NAME' => 'Минимальная цена, ₽', 'DEFAULT' => 350, 'MIN' => 0],
            'PER_KM' => ['TYPE' => 'NUMBER', 'NAME' => 'Цена за км, ₽', 'DEFAULT' => 50, 'MIN' => 0],
            'K' => ['TYPE' => 'NUMBER', 'NAME' => 'Коэффициент дорог (км по дороге / км по прямой)', 'DEFAULT' => 1.3, 'MIN' => 1, 'STEP' => 0.05],
        ]]];
    }

    // координаты адреса в городе: [широта, долгота] или null, если DaData не знает хотя бы улицу
    public static function geo(string $city, string $street): ?array
    {
        $street = trim($street);
        $key = (string)\COption::GetOptionString('bt', 'dadata_key');
        if ($street === '' || $key === '') {
            return null;
        }
        // квартира и подъезд для координат не нужны: берём адрес до номера дома включительно
        preg_match('/^(.*?,\s*(?:д|дом)\.?\s*[\w\/-]+(?:\s*(?:к|корп|стр)\.?\s*\w+)?)/u', $street, $m)
            and $street = $m[1];
        $street = preg_replace('/,\s*кв\.\/офис.*$/u', '', $street);
        $q = ($city !== '' && mb_stripos($street, $city) === false ? $city . ', ' : '') . $street;
        $cache = Cache::createInstance();
        if ($cache->initCache(86400 * 30, 'geo|' . md5($q), '/bt/geo')) {
            return $cache->getVars() ?: null;
        }
        $http = new HttpClient(['socketTimeout' => 3, 'streamTimeout' => 3]);
        $http->setHeader('Content-Type', 'application/json');
        $http->setHeader('Accept', 'application/json');
        $http->setHeader('Authorization', 'Token ' . $key);
        $res = json_decode((string)$http->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address',
            json_encode(['query' => $q, 'count' => 1], JSON_UNESCAPED_UNICODE)), true);
        if (!is_array($res)) {
            return null; // сбой связи не кешируем
        }
        $d = $res['suggestions'][0]['data'] ?? [];
        // qc_geo: 0 — дом, 1 — ближайший дом, 2 — улица; город и хуже — расстояние не считаем
        $geo = ($d['geo_lat'] ?? '') !== '' && (int)($d['qc_geo'] ?? 5) <= 2 ? [(float)$d['geo_lat'], (float)$d['geo_lon']] : [];
        $cache->startDataCache();
        $cache->endDataCache($geo);
        return $geo ?: null;
    }

    // км по прямой от склада до адреса; null — адрес не нашёлся
    public static function km(string $city, string $street): ?float
    {
        $co = bt_contacts();
        $a = self::geo((string)($co['city'] ?? ''), (string)($co['street'] ?? ''));
        $b = $a ? self::geo($city, $street) : null;
        if (!$b) {
            return null;
        }
        [$la1, $lo1, $la2, $lo2] = array_map('deg2rad', [$a[0], $a[1], $b[0], $b[1]]);
        $h = sin(($la2 - $la1) / 2) ** 2 + cos($la1) * cos($la2) * sin(($lo2 - $lo1) / 2) ** 2;
        return 6371 * 2 * asin(min(1, sqrt($h)));
    }

    public function price(?float $km): float
    {
        $c = $this->config['MAIN'] ?? [];
        $min = (float)($c['MIN'] ?? 350);
        return $km === null ? $min : max($min, ceil($km * max(1, (float)($c['K'] ?? 1.3))) * (float)($c['PER_KM'] ?? 50));
    }

    protected function calculateConcrete(Sale\Shipment $shipment): Sale\Delivery\CalculationResult
    {
        $r = new Sale\Delivery\CalculationResult();
        $order = $shipment->getCollection()->getOrder();
        $props = $order ? $order->getPropertyCollection() : null;
        $loc = $props && ($p = $props->getDeliveryLocation()) ? (string)$p->getValue() : '';
        $addr = $props && ($p = $props->getItemByOrderPropertyCode('ADDRESS')) ? (string)$p->getValue() : '';
        $r->setDeliveryPrice($this->price(self::km(bt_loc($loc)['n'], $addr)));
        return $r;
    }
}
