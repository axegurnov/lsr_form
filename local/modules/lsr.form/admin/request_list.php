<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Lsr\Form\ApartmentStatus;
use Lsr\Form\RequestTable;

/** @var CMain $APPLICATION */
global $APPLICATION;

if (!$APPLICATION->GetGroupRight('lsr.form')) {
    $APPLICATION->AuthForm('Доступ запрещён');
}

Loader::includeModule('lsr.form');

$sTableID = 'tbl_lsr_form_requests';
$oSort = new CAdminSorting($sTableID, 'ID', 'desc');
$lAdmin = new CAdminList($sTableID, $oSort);

$request = Application::getInstance()->getContext()->getRequest();

$filterFields = ['find_email', 'find_phone', 'find_name'];
$lAdmin->InitFilter($filterFields);

$filter = [];
if ($findEmail = trim((string)$request->get('find_email'))) {
    $filter['%=EMAIL'] = '%' . $findEmail . '%';
}
if ($findPhone = trim((string)$request->get('find_phone'))) {
    $filter['%=PHONE'] = '%' . preg_replace('/\D+/', '', $findPhone) . '%';
}
if ($findName = trim((string)$request->get('find_name'))) {
    $filter['%=NAME'] = '%' . $findName . '%';
}

if ($actionId = $lAdmin->GroupAction()) {
    if ($actionId === 'delete') {
        $ids = $request->get('ID') ?: [];
        if ($request->get('action_target') === 'selected') {
            $rows = RequestTable::getList(['select' => ['ID'], 'filter' => $filter])->fetchAll();
            $ids = array_column($rows, 'ID');
        }
        foreach ((array)$ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                RequestTable::delete($id);
            }
        }
    }
}

$nav = new \Bitrix\Main\UI\PageNavigation('nav-lsr-form-requests');
$nav->allowAllRecords(true)
    ->setPageSize(50)
    ->initFromUri();

$total = RequestTable::getCount($filter);

$rows = RequestTable::getList([
    'select' => [
        'ID', 'NAME', 'EMAIL', 'PHONE', 'CREATED_AT',
        'APARTMENT_ID',
        'APARTMENT_NUMBER' => 'APARTMENT.NUMBER',
        'APARTMENT_STATUS' => 'APARTMENT.STATUS',
        'HOUSE_NAME'       => 'APARTMENT.HOUSE.NAME',
    ],
    'filter' => $filter,
    'order'  => [$oSort->getField() => $oSort->getOrder()],
    'limit'  => $nav->getLimit(),
    'offset' => $nav->getOffset(),
])->fetchAll();

$nav->setRecordCount($total);

$dbResult = new CDBResult();
$dbResult->InitFromArray($rows);
$rsData = new CAdminResult($dbResult, $sTableID);
$rsData->NavStart($nav->getPageSize(), false);
$rsData->NavRecordCount = $total;
$rsData->NavPageCount   = (int)ceil($total / max(1, $nav->getPageSize()));
$rsData->NavPageNomer   = $nav->getCurrentPage();

$lAdmin->NavText($rsData->GetNavPrint('Заявки'));

$lAdmin->AddHeaders([
    ['id' => 'ID',          'content' => 'ID',          'sort' => 'ID',         'default' => true],
    ['id' => 'CREATED_AT',  'content' => 'Дата',        'sort' => 'CREATED_AT', 'default' => true],
    ['id' => 'NAME',        'content' => 'Имя',         'sort' => 'NAME',       'default' => true],
    ['id' => 'EMAIL',       'content' => 'Почта',       'sort' => 'EMAIL',      'default' => true],
    ['id' => 'PHONE',       'content' => 'Телефон',     'sort' => 'PHONE',      'default' => true],
    ['id' => 'APARTMENT',   'content' => 'Квартира',    'sort' => '',           'default' => true],
    ['id' => 'STATUS',      'content' => 'Статус',      'sort' => '',           'default' => true],
]);

foreach ($rows as $row) {
    $editUrl = 'lsr_form_request_edit.php?ID=' . (int)$row['ID'] . '&lang=' . LANGUAGE_ID;
    $r = &$lAdmin->AddRow($row['ID'], $row, $editUrl, 'Открыть');
    $r->AddField('ID', $row['ID']);
    $r->AddField('CREATED_AT', $row['CREATED_AT'] instanceof \Bitrix\Main\Type\DateTime
        ? $row['CREATED_AT']->toString()
        : (string)$row['CREATED_AT']);
    $r->AddField('NAME', htmlspecialcharsbx($row['NAME']));
    $r->AddField('EMAIL', htmlspecialcharsbx($row['EMAIL']));
    $r->AddField('PHONE', htmlspecialcharsbx($row['PHONE']));
    $apt = trim(($row['HOUSE_NAME'] ?? '') . ' / кв. ' . ($row['APARTMENT_NUMBER'] ?? ''));
    $r->AddField('APARTMENT', htmlspecialcharsbx($apt));
    $r->AddField('STATUS', htmlspecialcharsbx(ApartmentStatus::getTitle((string)($row['APARTMENT_STATUS'] ?? ''))));

    $actions = [];
    $actions[] = [
        'ICON'    => 'edit',
        'TEXT'    => 'Открыть',
        'ACTION'  => $lAdmin->ActionRedirect($editUrl),
        'DEFAULT' => true,
    ];
    $actions[] = [
        'ICON'   => 'delete',
        'TEXT'   => 'Удалить',
        'ACTION' => "if(confirm('Удалить запись?')) " . $lAdmin->ActionDoGroup($row['ID'], 'delete'),
    ];
    $r->AddActions($actions);
}

$lAdmin->AddGroupActionTable([
    'delete' => 'Удалить',
]);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle('Заявки с формы LSR');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$oFilter = new CAdminFilter($sTableID . '_filter', [
    'find_name'  => 'Имя',
    'find_email' => 'Почта',
    'find_phone' => 'Телефон',
]);
?>

<form name="find_form" method="get" action="<?= $APPLICATION->GetCurPage() ?>">
    <?php $oFilter->Begin(); ?>
    <tr>
        <td>Имя:</td>
        <td><input type="text" name="find_name" size="40" value="<?= htmlspecialcharsbx($findName ?? '') ?>"></td>
    </tr>
    <tr>
        <td>Почта:</td>
        <td><input type="text" name="find_email" size="40" value="<?= htmlspecialcharsbx($findEmail ?? '') ?>"></td>
    </tr>
    <tr>
        <td>Телефон:</td>
        <td><input type="text" name="find_phone" size="40" value="<?= htmlspecialcharsbx($findPhone ?? '') ?>"></td>
    </tr>
    <?php
    $oFilter->Buttons([
        'table_id' => $sTableID,
        'url'      => $APPLICATION->GetCurPage(),
        'form'     => 'find_form',
    ]);
    $oFilter->End();
    ?>
</form>

<?php $lAdmin->DisplayList(); ?>