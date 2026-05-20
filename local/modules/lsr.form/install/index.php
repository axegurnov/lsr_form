<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!class_exists('lsr_form')) {

    class lsr_form extends CModule
    {
        public $MODULE_ID = 'lsr.form';
        public $MODULE_VERSION;
        public $MODULE_VERSION_DATE;
        public $MODULE_NAME = 'LSR Form';
        public $MODULE_DESCRIPTION = 'Форма заявки на квартиру';
        public $PARTNER_NAME = 'LSR';
        public $PARTNER_URI = '';

        public function __construct()
        {
            $arModuleVersion = [];
            include __DIR__ . '/version.php';
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        public function DoInstall(): void
        {
            global $APPLICATION;

            ModuleManager::registerModule($this->MODULE_ID);

            try {
                Loader::includeModule($this->MODULE_ID);
                $this->InstallDB();
                $this->InstallFiles();
                $this->InstallEvents();
            } catch (\Throwable $e) {
                $APPLICATION->ThrowException($e->getMessage());
            }

            $APPLICATION->IncludeAdminFile(
                'Установка модуля ' . $this->MODULE_NAME,
                __DIR__ . '/step_install.php'
            );
        }

        public function DoUninstall(): void
        {
            global $APPLICATION;

            $request = Application::getInstance()->getContext()->getRequest();
            $savedata = $request->get('savedata') === 'Y';

            try {
                Loader::includeModule($this->MODULE_ID);
                $this->UninstallEvents();
                $this->UninstallFiles();
                if (!$savedata) {
                    $this->UninstallDB();
                }
            } catch (\Throwable $e) {
                $APPLICATION->ThrowException($e->getMessage());
            }

            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(
                'Удаление модуля ' . $this->MODULE_NAME,
                __DIR__ . '/step_uninstall.php'
            );
        }

        public function InstallDB(): void
        {
            $conn = Application::getConnection();

            if (!$conn->isTableExists(\Lsr\Form\HouseTable::getTableName())) {
                \Lsr\Form\HouseTable::getEntity()->createDbTable();
            }
            if (!$conn->isTableExists(\Lsr\Form\ApartmentTable::getTableName())) {
                \Lsr\Form\ApartmentTable::getEntity()->createDbTable();
                $conn->queryExecute(
                    'CREATE UNIQUE INDEX ux_lsr_form_apt_house_num ON '
                    . \Lsr\Form\ApartmentTable::getTableName()
                    . ' (HOUSE_ID, NUMBER)'
                );
            }
            if (!$conn->isTableExists(\Lsr\Form\RequestTable::getTableName())) {
                \Lsr\Form\RequestTable::getEntity()->createDbTable();
            }
        }

        public function UninstallDB(): void
        {
            $conn = Application::getConnection();
            foreach ([
                \Lsr\Form\RequestTable::getTableName(),
                \Lsr\Form\ApartmentTable::getTableName(),
                \Lsr\Form\HouseTable::getTableName(),
            ] as $table) {
                if ($conn->isTableExists($table)) {
                    $conn->dropTable($table);
                }
            }
        }

        public function InstallFiles(): void
        {
            $docRoot = Application::getDocumentRoot();

            CopyDirFiles(
                __DIR__ . '/admin',
                $docRoot . '/bitrix/admin',
                true,
                true
            );

            CopyDirFiles(
                __DIR__ . '/components',
                $docRoot . '/local/components',
                true,
                true
            );
        }

        public function UninstallFiles(): void
        {
            $docRoot = Application::getDocumentRoot();

            foreach (glob(__DIR__ . '/admin/*.php') ?: [] as $file) {
                @unlink($docRoot . '/bitrix/admin/' . basename($file));
            }

            DeleteDirFilesEx('/local/components/lsr/form');
        }

        public function InstallEvents(): void
        {
            \CModule::AddAutoloadClasses($this->MODULE_ID, [
                'Lsr\\Form\\HouseTable'     => 'lib/HouseTable.php',
                'Lsr\\Form\\ApartmentTable' => 'lib/ApartmentTable.php',
                'Lsr\\Form\\RequestTable'   => 'lib/RequestTable.php',
            ]);
        }

        public function UninstallEvents(): void
        {
        }
    }
}