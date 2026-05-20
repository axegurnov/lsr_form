<?php

namespace Lsr\Form;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

class HouseTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_lsr_form_house';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('NAME'))
                ->configureRequired()
                ->addValidator(new LengthValidator(1, 255))
                ->configureTitle('Название дома'),
        ];
    }
}