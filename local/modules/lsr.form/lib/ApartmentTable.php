<?php

namespace Lsr\Form;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;

class ApartmentTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_lsr_form_apartment';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),

            (new IntegerField('HOUSE_ID'))
                ->configureRequired()
                ->configureTitle('Дом'),

            (new StringField('NUMBER'))
                ->configureRequired()
                ->addValidator(new LengthValidator(1, 32))
                ->configureTitle('Номер квартиры'),

            (new EnumField('STATUS'))
                ->configureRequired()
                ->configureValues(array_keys(ApartmentStatus::getList()))
                ->configureDefaultValue(ApartmentStatus::FREE)
                ->configureTitle('Статус'),

            (new Reference(
                'HOUSE',
                HouseTable::class,
                Join::on('this.HOUSE_ID', 'ref.ID')
            ))->configureJoinType('inner'),
        ];
    }
}