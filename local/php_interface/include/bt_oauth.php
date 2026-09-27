<?php
// Вход через сервисы на модуле «Социальные сервисы»: Яндекс и VK — штатные классы модуля, банки — классы ниже.
// Ключи всех пяти — в настройках модуля (Настройки → Настройки продукта → Настройки модулей → Социальные сервисы).

use Bitrix\Main\Security\Random;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Socialservices\OAuth\StateService;

class BtOAuth
{
    // ID сервиса в модуле => [класс, css-класс кнопки, название, монограмма]
    public const IDP = [
        'YandexOAuth' => ['CSocServYandexAuth', 'i-ya', 'Яндекс ID', 'Я'],
        'VKontakte' => ['CSocServVKontakte', 'i-vk', 'VK ID', 'VK'],
        'BtTbank' => ['BtOAuthTbank', 'i-tb', 'T-Bank ID', 'Т'],
        'BtSber' => ['BtOAuthSber', 'i-sber', 'Сбер ID', 'С'],
        'BtAlfa' => ['BtOAuthAlfa', 'i-alfa', 'Альфа ID', 'А'],
    ];
    private const LINK_TTL = 1200;

    // OnAuthServicesBuildList: банки в списке сервисов модуля — там же их ключи
    public static function services(): array
    {
        $r = [];
        foreach (self::IDP as $id => [$class, , $name]) {
            if (is_subclass_of($class, 'BtOAuthBank')) {
                $r[] = ['ID' => $id, 'CLASS' => $class, 'NAME' => $name, 'ICON' => 'openid'];
            }
        }
        return $r;
    }

    public static function buttons(): array
    {
        $m = new CSocServAuthManager();
        $r = [];
        foreach (self::IDP as $id => [, $cls, $name, $mono]) {
            if ($m->isActiveAuthService($id)) {
                $r[] = ['id' => $id, 'cls' => $cls, 't' => $name, 'm' => $mono];
            }
        }
        return $r;
    }

    // OnFindSocialservicesUser: привязки к сервису ещё нет — ищем покупателя по e-mail или создаём.
    // Существующий аккаунт без подтверждения e-mail сервисом не отдаём: сначала код на почту, потом привязка (BtOAuth::link)
    public static function findUser(&$f): int
    {
        $id = (string)($f['EXTERNAL_AUTH_ID'] ?? '');
        if (!isset(self::IDP[$id])) {
            return 0;
        }
        $name = self::IDP[$id][2];
        if ($id === 'VKontakte' && empty($f['EMAIL'])) {
            $f['EMAIL'] = self::vkEmail((string)($f['OATOKEN'] ?? ''));
        }
        $email = mb_strtolower(trim((string)($f['EMAIL'] ?? '')));
        if (!check_email($email, true)) {
            return self::pending($f, '', $name . ' не передал e-mail. Укажите почту и подтвердите кодом — дальше будете входить через ' . $name . ' без кода.');
        }
        $u = UserTable::getList(['filter' => ['=EMAIL' => $email], 'select' => ['ID', 'ACTIVE'], 'order' => ['ID' => 'ASC'], 'limit' => 1])->fetch();
        if (!$u) {
            $new = bt_user_create($email, trim(($f['NAME'] ?? '') . ' ' . ($f['LAST_NAME'] ?? '')), (string)($f['PERSONAL_PHONE'] ?? ''));
            return is_int($new) ? $new : self::fail('Не получилось создать аккаунт. Получите код на почту или позвоните нам.');
        }
        if ($u['ACTIVE'] !== 'Y' || in_array(1, array_map('intval', CUser::GetUserGroup((int)$u['ID'])), true)) {
            return self::fail('Для этого аккаунта вход через сервисы недоступен. Позвоните нам, поможем.');
        }
        if (($f['BT_EMAIL_VERIFIED'] ?? false) !== true) {
            return self::pending($f, $email, 'Аккаунт с этим e-mail уже есть. Подтвердите почту кодом — ' . $name . ' привяжется к нему, и дальше код не понадобится.');
        }
        return (int)$u['ID'];
    }

    // Вход по коду подтвердил почту — привязываем сервис, с которым покупатель пришёл в этой сессии
    public static function link(int $userId): void
    {
        $l = $_SESSION['BT_OAUTH_LINK'] ?? null;
        unset($_SESSION['BT_OAUTH_LINK']);
        if (!$l || $l['EXP'] < time() || !CModule::IncludeModule('socialservices')) {
            return;
        }
        $t = \Bitrix\Socialservices\UserTable::class;
        if (!$t::getList(['filter' => ['=EXTERNAL_AUTH_ID' => $l['EXTERNAL_AUTH_ID'], '=XML_ID' => $l['XML_ID']], 'select' => ['ID']])->fetch()) {
            $t::add(['USER_ID' => $userId, 'CAN_DELETE' => 'Y', 'SITE_ID' => SITE_ID] + array_intersect_key($l, array_flip(['EXTERNAL_AUTH_ID', 'XML_ID', 'LOGIN', 'NAME', 'LAST_NAME', 'EMAIL'])));
        }
    }

    private static function fail(string $msg): int
    {
        $_SESSION['BT_OAUTH_ERR'] = ['msg' => $msg];
        return 0;
    }

    private static function pending(array $f, string $email, string $msg): int
    {
        $_SESSION['BT_OAUTH_LINK'] = ['EXP' => time() + self::LINK_TTL] + array_intersect_key($f, array_flip(['EXTERNAL_AUTH_ID', 'XML_ID', 'LOGIN', 'NAME', 'LAST_NAME', 'EMAIL']));
        $_SESSION['BT_OAUTH_ERR'] = ['msg' => $msg, 'email' => $email];
        return 0;
    }

    // VK ID отдаёт e-mail не в ответе на токен, а в user_info
    private static function vkEmail(string $token): string
    {
        if ($token === '') {
            return '';
        }
        $h = new HttpClient(['socketTimeout' => 10, 'streamTimeout' => 15]);
        try {
            $r = Json::decode((string)$h->post('https://id.vk.ru/oauth2/user_info', ['client_id' => trim(CSocServAuth::GetOption('vkontakte_appid')), 'access_token' => $token]));
        } catch (\Throwable) {
            return '';
        }
        return (string)($r['user']['email'] ?? '');
    }
}

// OpenID Connect, код авторизации. Возврат — на /local/ajax/oauth.php, сервис — из state
abstract class BtOAuthBank extends CSocServAuth
{
    public const ID = '';
    protected const OPT = '';
    protected const AUTH_URL = '';
    protected const TOKEN_URL = '';
    protected const INFO_URL = '';
    protected const SCOPE = '';
    // запросы к банку с клиентским сертификатом (mTLS)
    protected const CERT = false;

    public static function redirectUri(): string
    {
        return (string)(new Uri('/local/ajax/oauth.php'))->toAbsolute();
    }

    protected function opt(string $key): string
    {
        return trim((string)static::GetOption(static::OPT . '_' . $key));
    }

    public function GetSettings()
    {
        $s = [
            [static::OPT . '_id', 'Client ID (идентификатор приложения)', '', ['text', 40]],
            [static::OPT . '_secret', 'Client secret (секрет приложения)', '', ['text', 40]],
        ];
        if (static::CERT) {
            $s[] = [static::OPT . '_cert', 'Путь к сертификату банка на сервере (PEM: сертификат и ключ)', '', ['text', 60]];
            $s[] = [static::OPT . '_ca', 'Путь к корневому сертификату сервера банка (PEM)', '', ['text', 60]];
        }
        $s[] = ['note' => 'Адрес возврата (redirect URI) для заявки в банк: <b>' . static::redirectUri() . '</b><br>'
            . 'Кнопка на сайте появится, когда заполнены все поля и сервис отмечен в списке на вкладке «Настройки». Подробно — в инструкции «Вход через сервисы»'];
        return $s;
    }

    public function getUrl($arParams = [])
    {
        $state = StateService::getInstance()->createState(['site_id' => SITE_ID, 'check_key' => CSocServAuthManager::getUniqueKey(),
            'redirect_url' => $this->getRedirectUrl((array)$arParams), 'service' => static::ID]);
        $nonce = Random::getString(32, true);
        $_SESSION['BT_OAUTH_NONCE'][$state] = $nonce;
        return static::AUTH_URL . '?' . http_build_query(array_filter([
            'response_type' => 'code',
            'client_id' => $this->opt('id'),
            'redirect_uri' => static::redirectUri(),
            'scope' => static::SCOPE,
            'state' => $state,
            'nonce' => $nonce,
        ] + $this->authParams()), '', '&', PHP_QUERY_RFC3986);
    }

    protected function authParams(): array
    {
        return [];
    }

    public function Authorize()
    {
        $state = (string)($_REQUEST['state'] ?? '');
        $nonce = (string)($_SESSION['BT_OAUTH_NONCE'][$state] ?? '');
        unset($_SESSION['BT_OAUTH_NONCE'][$state]);
        $code = (string)($_REQUEST['code'] ?? '');
        $res = SOCSERV_AUTHORISATION_ERROR;
        if ($code === '') {
            $this->logger->error('oauth.request.invalid_code', ['error' => (string)($_REQUEST['error'] ?? '')]);
            if (($_REQUEST['error'] ?? '') === 'access_denied') {
                $_SESSION['BT_OAUTH_ERR'] = ['msg' => 'Вход отменён. Можно войти ещё раз или получить код на почту.'];
            }
        } elseif ($nonce === '' || !CSocServAuthManager::CheckUniqueKey()) {
            $_SESSION['BT_OAUTH_ERR'] = ['msg' => 'Сессия входа устарела. Начните вход заново.'];
        } elseif (($token = $this->token($code)) && $this->idTokenOk($token, $nonce) && ($f = $this->user($token))) {
            $res = $this->AuthorizeUser($f);
        }
        LocalRedirect($this->getRedirectUriAfterAuthorize($res, static::ID));
    }

    protected function http(): HttpClient
    {
        $h = new HttpClient(['socketTimeout' => 10, 'streamTimeout' => 15]);
        $h->setHeader('Accept', 'application/json');
        if (static::CERT) {
            $h->setContextOptions(['ssl' => ['local_cert' => $this->opt('cert'), 'cafile' => $this->opt('ca')]]);
        }
        return $h;
    }

    protected function json(HttpClient $h, $body, string $what): ?array
    {
        try {
            $r = Json::decode((string)$body);
        } catch (\Throwable) {
            $r = null;
        }
        if (!is_array($r) || $h->getStatus() !== 200) {
            $this->logger->error('oauth.' . $what . '.failed', ['status' => $h->getStatus(), 'error' => is_array($r) ? (string)($r['error'] ?? '') : 'not json']);
            return null;
        }
        return $r;
    }

    // client_secret_basic; банки с другой схемой переопределяют
    protected function token(string $code): ?array
    {
        $h = $this->http();
        $h->setAuthorization($this->opt('id'), $this->opt('secret'));
        $r = $this->json($h, $h->post(static::TOKEN_URL, ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => static::redirectUri()]), 'token');
        return !empty($r['access_token']) ? $r : null;
    }

    // id_token получен напрямую от банка по TLS — подпись не проверяем, сверяем получателя и nonce
    protected function idTokenOk(array $token, string $nonce): bool
    {
        if (empty($token['id_token'])) {
            return true;
        }
        $p = json_decode((string)base64_decode(strtr(explode('.', $token['id_token'] . '..')[1], '-_', '+/')), true);
        $aud = (array)($p['aud'] ?? []);
        $ok = is_array($p) && in_array($this->opt('id'), $aud, true) && (!isset($p['nonce']) || hash_equals($nonce, (string)$p['nonce']));
        if (!$ok) {
            $this->logger->error('oauth.id_token.invalid');
        }
        return $ok;
    }

    protected function info(array $token): ?array
    {
        $h = $this->http();
        $h->setHeader('Authorization', 'Bearer ' . $token['access_token']);
        return $this->json($h, $h->get(static::INFO_URL), 'userinfo');
    }

    // Поля для модуля: XML_ID — постоянный id у сервиса; BT_EMAIL_VERIFIED читает BtOAuth::findUser
    protected function user(array $token): ?array
    {
        $u = $this->info($token);
        if (empty($u['sub'])) {
            return null;
        }
        return ['EXTERNAL_AUTH_ID' => static::ID, 'XML_ID' => (string)$u['sub'], 'LOGIN' => static::ID . '_' . $u['sub'],
            'EMAIL' => (string)($u['email'] ?? ''), 'BT_EMAIL_VERIFIED' => in_array($u['email_verified'] ?? false, [true, 'true'], true),
            'NAME' => (string)($u['given_name'] ?? ''), 'LAST_NAME' => (string)($u['family_name'] ?? ''),
            'PERSONAL_PHONE' => (string)($u['phone_number'] ?? ''), 'OATOKEN' => $token['access_token'],
            'OATOKEN_EXPIRES' => time() + (int)($token['expires_in'] ?? 60)];
    }
}

// T-ID: developer.tbank.ru/docs/products/TID/web — набор данных (scope) задаётся договором, e-mail в текущей документации нет
class BtOAuthTbank extends BtOAuthBank
{
    public const ID = 'BtTbank';
    protected const OPT = 'bt_tbank';
    protected const AUTH_URL = 'https://id.tbank.ru/auth/authorize';
    protected const TOKEN_URL = 'https://id.tbank.ru/auth/token';
    protected const INFO_URL = 'https://id.tbank.ru/userinfo/userinfo';

    protected function info(array $token): ?array
    {
        $h = $this->http();
        $h->setHeader('Authorization', 'Bearer ' . $token['access_token']);
        return $this->json($h, $h->post(static::INFO_URL, ['client_id' => $this->opt('id'), 'client_secret' => $this->opt('secret')]), 'userinfo');
    }
}

// Сбер ID: developers.sber.ru/docs/ru/sberid — токен и данные только с сертификатом приложения, заголовки RqUID
class BtOAuthSber extends BtOAuthBank
{
    public const ID = 'BtSber';
    protected const OPT = 'bt_sber';
    // по http намеренно: страница Сбера помогает браузерам без сертификатов Минцифры, параметры те же, что у authorize.do
    protected const AUTH_URL = 'http://id.sber.ru/landing/certificates.html';
    protected const TOKEN_URL = 'https://oauth.sber.ru/ru/prod/tokens/v2/oidc';
    protected const INFO_URL = 'https://oauth.sber.ru/ru/prod/sberbankid/v2.1/userinfo';
    protected const SCOPE = 'openid name email mobile';
    protected const CERT = true;

    protected function authParams(): array
    {
        return ['client_type' => 'PRIVATE'];
    }

    protected function token(string $code): ?array
    {
        $h = $this->http();
        $h->setHeader('RqUID', bin2hex(random_bytes(16)));
        $r = $this->json($h, $h->post(static::TOKEN_URL, ['grant_type' => 'authorization_code', 'code' => $code, 'client_id' => $this->opt('id'),
            'client_secret' => $this->opt('secret'), 'redirect_uri' => static::redirectUri()]), 'token');
        return !empty($r['access_token']) ? $r : null;
    }

    protected function info(array $token): ?array
    {
        $h = $this->http();
        $h->setHeader('Authorization', 'Bearer ' . $token['access_token']);
        $h->setHeader('x-introspect-rquid', bin2hex(random_bytes(16)));
        $u = $this->json($h, $h->get(static::INFO_URL), 'userinfo');
        if ($u) {
            // обязательное по регламенту Сбера уведомление об успешном входе
            $c = $this->http();
            $c->setHeader('Authorization', 'Bearer ' . $token['access_token']);
            $c->setHeader('rquid', bin2hex(random_bytes(16)));
            $c->get('https://oauth.sber.ru/api/v2/auth/completed');
        }
        return $u;
    }
}

// Альфа ID: developers.alfabank.ru/products/alfa-id — адреса из открытой части документации, схему входа сверить при подключении
class BtOAuthAlfa extends BtOAuthBank
{
    public const ID = 'BtAlfa';
    protected const OPT = 'bt_alfa';
    protected const AUTH_URL = 'https://id.alfabank.ru/oidc/authorize';
    protected const TOKEN_URL = 'https://baas.alfabank.ru/oidc/token';
    protected const INFO_URL = 'https://baas.alfabank.ru/oidc/userinfo';
    protected const SCOPE = 'openid';
    protected const CERT = true;
}
