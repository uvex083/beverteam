<?php
// Курьер: км по прямой × коэффициент дорог × цена за км, не меньше минимума; адрес не нашёлся — минимум

namespace Bt\Courier;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale;

class Handler extends Sale\Delivery\Services\Base
{
    private const MAX_KM = 50;
    private static array $mem = [];

    public static function getClassTitle()
    {
        return 'Курьер по расстоянию от склада';
    }

    public static function getClassDescription()
    {
        return 'Цена = км по прямой от склада × коэффициент дорог × цена за км, но не меньше минимальной. Склад — адрес из контактов сайта.';
    }

    public function isCalculatePriceImmediately()
    {
        return true;
    }

    protected function getConfigStructure()
    {
        return ['MAIN' => ['TITLE' => 'Тариф', 'DESCRIPTION' => 'Цена = км по прямой × коэффициент дорог × цена за км, не меньше минимальной', 'ITEMS' => [
            'MIN' => ['TYPE' => 'NUMBER', 'NAME' => 'Минимальная цена, ₽', 'DEFAULT' => 350, 'MIN' => 0, 'REQUIRED' => 'Y'],
            'PER_KM' => ['TYPE' => 'NUMBER', 'NAME' => 'Цена за км, ₽', 'DEFAULT' => 50, 'MIN' => 0, 'REQUIRED' => 'Y'],
            'K' => ['TYPE' => 'NUMBER', 'NAME' => 'Коэффициент дорог (км по дороге / км по прямой)', 'DEFAULT' => 1.3, 'MIN' => 1, 'STEP' => 0.05, 'REQUIRED' => 'Y'],
        ]]];
    }

    // для координат — адрес до дома с корпусом: квартира, подъезд и код пункта СДЭК не нужны
    public static function street(string $s): string
    {
        $s = trim(preg_replace(['/^Пункт выдачи СДЭК:\s*/u', '/\s*#S\S+$/u', '/,\s*кв\.\/офис.*$/u'], '', $s));
        return preg_match('/^(.*?,\s*(?:д|дом)(?:\.\s*|\s+)[\w\/-]+(?:\s*(?:корп|к|стр|лит(?:ера)?)\.?\s*\w+)?)/u', $s, $m) ? $m[1] : $s;
    }

    // координаты адреса в городе: [широта, долгота] или null
    public static function geo(string $city, string $street): ?array
    {
        $street = self::street($street);
        $key = (string)\COption::GetOptionString('bt', 'dadata_key');
        if ($street === '' || $key === '') {
            return null;
        }
        $q = ($city !== '' && mb_stripos($street, $city) === false ? $city . ', ' : '') . $street;
        if (array_key_exists($q, self::$mem)) {
            return self::$mem[$q];
        }
        $cache = Cache::createInstance();
        if ($cache->initCache(86400 * 30, 'geo|' . md5($q), '/bt/geo')) {
            return self::$mem[$q] = $cache->getVars() ?: null;
        }
        // DaData недавно не ответила — минуту не ждём таймаутов на каждом расчёте
        if (time() - (int)Option::get('bt', 'dadata_down', '0') < 60) {
            return self::$mem[$q] = null;
        }
        $http = new HttpClient(['socketTimeout' => 3, 'streamTimeout' => 3]);
        $http->setHeader('Content-Type', 'application/json');
        $http->setHeader('Accept', 'application/json');
        $http->setHeader('Authorization', 'Token ' . $key);
        $body = ['query' => $q, 'count' => 1] + ($city !== '' ? ['locations' => [['city' => $city], ['settlement' => $city]]] : []);
        $res = json_decode((string)$http->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address', json_encode($body, JSON_UNESCAPED_UNICODE)), true);
        if ((int)$http->getStatus() !== 200 || !isset($res['suggestions'])) {
            Option::set('bt', 'dadata_down', (string)time());
            return self::$mem[$q] = null; // сбой и ошибки ключа/лимита не кешируем
        }
        $d = $res['suggestions'][0]['data'] ?? [];
        // qc_geo: 0 — дом, 1 — ближайший дом, 2 — улица; город и хуже — расстояние не считаем
        $geo = ($d['geo_lat'] ?? '') !== '' && (int)($d['qc_geo'] ?? 5) <= 2 ? [(float)$d['geo_lat'], (float)$d['geo_lon']] : [];
        // «не найден» помним сутки: справочник DaData пополняется
        $cache->initCache($geo ? 86400 * 30 : 86400, 'geo|' . md5($q), '/bt/geo');
        $cache->startDataCache();
        $cache->endDataCache($geo);
        return self::$mem[$q] = $geo ?: null;
    }

    // км по прямой от склада до адреса; null — адрес не нашёлся или слишком далеко для курьера
    public static function km(string $city, string $street): ?float
    {
        if (self::street($street) === '') {
            return null;
        }
        $co = bt_contacts();
        $a = self::geo((string)($co['city'] ?? ''), (string)($co['street'] ?? ''));
        $b = $a ? self::geo($city, $street) : null;
        if (!$b) {
            return null;
        }
        [$la1, $lo1, $la2, $lo2] = array_map('deg2rad', [$a[0], $a[1], $b[0], $b[1]]);
        $h = sin(($la2 - $la1) / 2) ** 2 + cos($la1) * cos($la2) * sin(($lo2 - $lo1) / 2) ** 2;
        $km = 6371 * 2 * asin(min(1, sqrt($h)));
        return $km <= self::MAX_KM ? $km : null;
    }

    public function price(?float $km): float
    {
        $c = $this->config['MAIN'] ?? [];
        $num = fn(string $k, float $def) => is_numeric($c[$k] ?? null) ? (float)$c[$k] : $def;
        $min = $num('MIN', 350);
        return $km === null ? $min : max($min, ceil($km * max(1, $num('K', 1.3))) * $num('PER_KM', 50));
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
