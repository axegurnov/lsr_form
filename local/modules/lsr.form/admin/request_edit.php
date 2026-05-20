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

$request = Application::getInstance()->getContext()->getRequest();
$id = (int)$request->get('ID');

$row = null;
if ($id > 0) {
    $row = RequestTable::getList([
        'select' => [
            'ID', 'NAME', 'EMAIL', 'PHONE', 'CREATED_AT',
            'APARTMENT_NUMBER' => 'APARTMENT.NUMBER',
            'APARTMENT_STATUS' => 'APARTMENT.STATUS',
            'HOUSE_NAME'       => 'APARTMENT.HOUSE.NAME',
        ],
        'filter' => ['=ID' => $id],
        'limit'  => 1,
    ])->fetch();
}

$APPLICATION->SetTitle($row ? 'Заявка #' . $id : 'Заявка не найдена');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if (!$row) {
    CAdminMessage::ShowMessage([
        'TYPE'    => 'ERROR',
        'MESSAGE' => 'Заявка не найдена',
    ]);
    return;
}

$tabControl = new CAdminTabControl('tabControl', [
    ['DIV' => 'edit1', 'TAB' => 'Заявка', 'ICON' => '', 'TITLE' => 'Данные заявки'],
]);

$tabControl->Begin();
$tabControl->BeginNextTab();
?>
<tr>
    <td width="40%">ID:</td>
    <td><?= (int)$row['ID'] ?></td>
</tr>
<tr>
    <td>Дата:</td>
    <td><?= $row['CREATED_AT'] instanceof \Bitrix\Main\Type\DateTime
            ? $row['CREATED_AT']->toString()
            : htmlspecialcharsbx((string)$row['CREATED_AT']) ?></td>
</tr>
<tr>
    <td>Имя:</td>
    <td><?= htmlspecialcharsbx($row['NAME']) ?></td>
</tr>
<tr>
    <td>Почта:</td>
    <td><?= htmlspecialcharsbx($row['EMAIL']) ?></td>
</tr>
<tr>
    <td>Телефон:</td>
    <td><?= htmlspecialcharsbx($row['PHONE']) ?></td>
</tr>
<tr>
    <td>Объект:</td>
    <td><?= htmlspecialcharsbx(($row['HOUSE_NAME'] ?? '') . ' / кв. ' . ($row['APARTMENT_NUMBER'] ?? '')) ?></td>
</tr>
<tr>
    <td>Статус квартиры:</td>
    <td><?= htmlspecialcharsbx(ApartmentStatus::getTitle((string)($row['APARTMENT_STATUS'] ?? ''))) ?></td>
</tr>
<?php
$tabControl->Buttons(['back_url' => 'lsr_form_request_list.php?lang=' . LANGUAGE_ID]);
$tabControl->End();