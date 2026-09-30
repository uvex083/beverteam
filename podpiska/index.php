<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
// «Кофе по подписке» заменён страницей «Кофе в офис»
LocalRedirect('/kofe-v-ofis/', false, '301 Moved permanently');
