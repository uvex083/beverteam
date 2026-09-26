<?php
$arUrlRewrite=array (
  0 => 
  array (
    'CONDITION' => '#^\\/?\\/mobileapp/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => NULL,
    'PATH' => '/bitrix/services/mobileapp/jn.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  5 => 
  array (
    'CONDITION' => '#^/personal/orders/([0-9]+)/(\?.*)?$#',
    'RULE' => 'ID=$1',
    'ID' => '',
    'PATH' => '/personal/orders/detail.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^/magazin/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/magazin/index.php',
    'SORT' => 100,
  ),
  1 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
  3 => 
  array (
    'CONDITION' => '#^/news/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/news/index.php',
    'SORT' => 100,
  ),
  7 => 
  array (
    'CONDITION' => '#^/servis/remont-kofemashin/([a-z0-9-]+)/(\?.*)?$#',
    'RULE' => 'BRAND=$1',
    'ID' => '',
    'PATH' => '/servis/remont-kofemashin/index.php',
    'SORT' => 100,
  ),
);
