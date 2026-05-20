<?php

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Lsr\Form\ApartmentStatus;
use Lsr\Form\ApartmentTable;
use Lsr\Form\HouseTable;
use Lsr\Form\RequestService;

header('Content-Type: application/json; charset=utf-8');

if (!Loader::includeModule('lsr.form')) {
    echo json_encode(['status' => 'error', 'message' => 'Модуль не установлен'], JSON_UNESCAPED_UNICODE);
    exit;
}

$request = Application::getInstance()->getContext()->getRequest();

if (!$request->isPost()) {
    $action = (string)$request->get('action');

    if ($action === 'apartments') {
        $houseId = (int)$request->get('house_id');
        $q = trim((string)$request->get('q'));
        if ($houseId <= 0) {
            echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $filter = ['=HOUSE_ID' => $houseId, '=STATUS' => ApartmentStatus::FREE];
        if ($q !== '') {
            $filter['%=NUMBER'] = $q . '%';
        }
        $rows = ApartmentTable::getList([
            'select' => ['ID', 'NUMBER'],
            'filter' => $filter,
            'order'  => ['NUMBER' => 'ASC'],
            'limit'  => 50,
        ])->fetchAll();
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'houses_search') {
        $q = trim((string)$request->get('q'));
        $runtime = [
            new \Bitrix\Main\Entity\ExpressionField(
                'FREE_CNT',
                '(SELECT COUNT(*) FROM ' . ApartmentTable::getTableName() .
                ' a WHERE a.HOUSE_ID = %s AND a.STATUS = \'' . ApartmentStatus::FREE . '\')',
                ['ID']
            ),
        ];
        $filter = ['>FREE_CNT' => 0];
        if ($q !== '') {
            $filter['%=NAME'] = '%' . $q . '%';
        }
        $rows = HouseTable::getList([
            'select'  => ['ID', 'NAME'],
            'filter'  => $filter,
            'runtime' => $runtime,
            'order'   => ['NAME' => 'ASC'],
            'limit'   => 50,
        ])->fetchAll();
        echo json_encode(['items' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!check_bitrix_sessid()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Сессия истекла, перезагрузите страницу'], JSON_UNESCAPED_UNICODE);
    exit;
}

$service = new RequestService();
$result = $service->submit([
    'name'         => (string)$request->getPost('name'),
    'email'        => (string)$request->getPost('email'),
    'phone'        => (string)$request->getPost('phone'),
    'apartment_id' => $request->getPost('apartment_id'),
]);

if ($result['status'] === RequestService::OK) {
    echo json_encode(['status' => 'ok', 'message' => 'ок'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';