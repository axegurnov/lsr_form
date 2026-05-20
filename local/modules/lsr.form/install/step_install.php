<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var CMain $APPLICATION */
if ($ex = $APPLICATION->GetException()) {
    CAdminMessage::ShowMessage([
        'TYPE'    => 'ERROR',
        'MESSAGE' => 'Ошибка при установке модуля',
        'DETAILS' => $ex->GetString(),
        'HTML'    => true,
    ]);
} else {
    CAdminMessage::ShowNote('Модуль LSR Form успешно установлен');
}
?>
<form action="<?= htmlspecialchars($APPLICATION->GetCurPage()) ?>">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="submit" name="back" value="Назад">
</form>