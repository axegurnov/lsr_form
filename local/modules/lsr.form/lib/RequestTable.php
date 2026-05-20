<?php

namespace Lsr\Form;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

class RequestTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_lsr_form_request';
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
                ->configureTitle('Имя'),

            (new StringField('EMAIL'))
                ->configureRequired()
                ->addValidator(new LengthValidator(1, 255))
                ->configureUnique()
                ->configureTitle('Почта'),

            (new StringField('PHONE'))
                ->configureRequired()
                ->addValidator(new LengthValidator(1, 32))
                ->configureUnique()
                ->configureTitle('Телефон'),

            (new IntegerField('APARTMENT_ID'))
                ->configureRequired()
                ->configureTitle('Квартира'),

            (new DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(static fn() => new DateTime())
                ->configureTitle('Дата заявки'),

            (new Reference(
                'APARTMENT',
                ApartmentTable::class,
                Join::on('this.APARTMENT_ID', 'ref.ID')
            ))->configureJoinType('left'),
        ];
    }
}