<?php
// вход — окном на любой странице; старый адрес формы входа ведёт в кабинет, там окно откроется само
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
LocalRedirect('/personal/');
