<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Lsr\Form\ApartmentStatus;
use Lsr\Form\HouseTable;

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */

if (!Loader::includeModule('lsr.form')) {
    ShowError('Модуль lsr.form не установлен');
    return;
}

$arResult['HOUSES'] = HouseTable::getList([
    'select' => ['ID', 'NAME'],
    'order'  => ['NAME' => 'ASC'],
    'runtime' => [
        new \Bitrix\Main\Entity\ExpressionField(
            'FREE_CNT',
            '(SELECT COUNT(*) FROM ' . \Lsr\Form\ApartmentTable::getTableName() .
            ' a WHERE a.HOUSE_ID = %s AND a.STATUS = \'' . ApartmentStatus::FREE . '\')',
            ['ID']
        ),
    ],
    'filter' => ['>FREE_CNT' => 0],
])->fetchAll();

$arResult['AJAX_URL'] = $this->getPath() . '/ajax.php';
$arResult['SESSID'] = bitrix_sessid();

$this->IncludeComponentTemplate();