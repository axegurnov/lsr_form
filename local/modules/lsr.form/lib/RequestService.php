<?php

namespace Lsr\Form;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;

final class RequestService
{
    public const OK                       = 'ok';
    public const ERR_VALIDATION           = 'validation';
    public const ERR_EMAIL_DUPLICATE      = 'email_duplicate';
    public const ERR_PHONE_DUPLICATE      = 'phone_duplicate';
    public const ERR_APARTMENT_NOT_FREE   = 'apartment_not_free';
    public const ERR_APARTMENT_NOT_FOUND  = 'apartment_not_found';
    public const ERR_INTERNAL             = 'internal';

    /**
     * @param array{name?:string,email?:string,phone?:string,apartment_id?:mixed} $input
     * @return array{status:string,message:string}
     */
    public function submit(array $input): array
    {
        $name        = trim((string)($input['name'] ?? ''));
        $email       = trim((string)($input['email'] ?? ''));
        $phoneRaw    = trim((string)($input['phone'] ?? ''));
        $apartmentId = (int)($input['apartment_id'] ?? 0);

        if ($name === '' || mb_strlen($name) > 255) {
            return self::fail(self::ERR_VALIDATION, 'Укажите корректное имя');
        }
        if (!Validator::isEmail($email)) {
            return self::fail(self::ERR_VALIDATION, 'Некорректная почта');
        }
        if (!Validator::isPhone($phoneRaw)) {
            return self::fail(self::ERR_VALIDATION, 'Некорректный телефон');
        }
        if ($apartmentId <= 0) {
            return self::fail(self::ERR_VALIDATION, 'Выберите объект недвижимости');
        }

        $phone = Validator::normalizePhone($phoneRaw);

        $connection = Application::getConnection();
        $connection->startTransaction();

        try {
            $apartment = ApartmentTable::getList([
                'select' => ['ID', 'STATUS'],
                'filter' => ['=ID' => $apartmentId],
                'limit'  => 1,
            ])->fetch();

            if (!$apartment) {
                $connection->rollbackTransaction();
                return self::fail(self::ERR_APARTMENT_NOT_FOUND, 'Выберите другой объект недвижимости');
            }

            if ($apartment['STATUS'] !== ApartmentStatus::FREE) {
                $connection->rollbackTransaction();
                return self::fail(self::ERR_APARTMENT_NOT_FREE, 'Выберите другой объект недвижимости');
            }

            if (RequestTable::getRow(['filter' => ['=EMAIL' => $email], 'select' => ['ID']])) {
                $connection->rollbackTransaction();
                return self::fail(self::ERR_EMAIL_DUPLICATE, 'Такая почта уже есть');
            }

            if (RequestTable::getRow(['filter' => ['=PHONE' => $phone], 'select' => ['ID']])) {
                $connection->rollbackTransaction();
                return self::fail(self::ERR_PHONE_DUPLICATE, 'Такой телефон уже есть');
            }

            $result = RequestTable::add([
                'NAME'         => $name,
                'EMAIL'        => $email,
                'PHONE'        => $phone,
                'APARTMENT_ID' => $apartmentId,
            ]);

            if (!$result->isSuccess()) {
                $connection->rollbackTransaction();
                $errors = $result->getErrorMessages();
                foreach ($errors as $msg) {
                    if (stripos($msg, 'EMAIL') !== false) {
                        return self::fail(self::ERR_EMAIL_DUPLICATE, 'Такая почта уже есть');
                    }
                    if (stripos($msg, 'PHONE') !== false) {
                        return self::fail(self::ERR_PHONE_DUPLICATE, 'Такой телефон уже есть');
                    }
                }
                return self::fail(self::ERR_VALIDATION, implode('; ', $errors));
            }

            $updated = ApartmentTable::getEntity()
                ->getDataClass()::update($apartmentId, ['STATUS' => ApartmentStatus::BOOKED]);

            if (!$updated->isSuccess()) {
                $connection->rollbackTransaction();
                return self::fail(self::ERR_INTERNAL, 'Не удалось обновить статус квартиры');
            }

            $connection->commitTransaction();

            return ['status' => self::OK, 'message' => 'ок'];
        } catch (SqlQueryException $e) {
            $connection->rollbackTransaction();
            $msg = $e->getMessage();
            if (stripos($msg, 'EMAIL') !== false) {
                return self::fail(self::ERR_EMAIL_DUPLICATE, 'Такая почта уже есть');
            }
            if (stripos($msg, 'PHONE') !== false) {
                return self::fail(self::ERR_PHONE_DUPLICATE, 'Такой телефон уже есть');
            }
            return self::fail(self::ERR_INTERNAL, 'Внутренняя ошибка');
        } catch (\Throwable $e) {
            $connection->rollbackTransaction();
            return self::fail(self::ERR_INTERNAL, 'Внутренняя ошибка');
        }
    }

    private static function fail(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }
}
