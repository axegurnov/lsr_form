<?php

use Bitrix\Main\Loader;


IncludeModuleLangFile(__FILE__);
const MODULE_ID = 'lsr.form';

Loader::registerAutoLoadClasses(MODULE_ID, [
    'Lsr\\Form\\HouseTable'      => 'lib/HouseTable.php',
    'Lsr\\Form\\ApartmentTable'  => 'lib/ApartmentTable.php',
    'Lsr\\Form\\RequestTable'    => 'lib/RequestTable.php',
    'Lsr\\Form\\ApartmentStatus' => 'lib/ApartmentStatus.php',
    'Lsr\\Form\\RequestService'  => 'lib/RequestService.php',
    'Lsr\\Form\\Validator'       => 'lib/Validator.php',
]);
