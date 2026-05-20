<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponentTemplate $this */

?>
<form class="lsr-form" id="lsr-form" novalidate>
    <input type="hidden" name="sessid" value="<?= htmlspecialcharsbx($arResult['SESSID']) ?>">

    <div class="lsr-form__row">
        <label class="lsr-form__label" for="lsr-name">Имя</label>
        <input class="lsr-form__input" type="text" id="lsr-name" name="name" maxlength="255" required>
        <div class="lsr-form__error" data-field="name"></div>
    </div>

    <div class="lsr-form__row">
        <label class="lsr-form__label" for="lsr-email">Почта</label>
        <label for="email"></label>
        <input class="lsr-form__input" type="email" id="email" name="email" maxlength="255" required>
        <div class="lsr-form__error" data-field="email"></div>
    </div>

    <div class="lsr-form__row">
        <label class="lsr-form__label" for="lsr-phone">Телефон</label>
        <input class="lsr-form__input" type="tel" id="lsr-phone" name="phone" maxlength="32" required>
        <div class="lsr-form__error" data-field="phone"></div>
    </div>

    <div class="lsr-form__row lsr-form__ac">
        <label class="lsr-form__label" for="lsr-house-input">Дом</label>
        <input class="lsr-form__input" type="text" id="lsr-house-input"
               autocomplete="off" placeholder="Начните вводить название дома">
        <input type="hidden" name="house_id" id="lsr-house" value="">
        <ul class="lsr-form__suggest" id="lsr-house-suggest" hidden></ul>
        <div class="lsr-form__error" data-field="house_id"></div>
    </div>

    <div class="lsr-form__row lsr-form__ac">
        <label class="lsr-form__label" for="lsr-apartment-input">Квартира</label>
        <input class="lsr-form__input" type="text" id="lsr-apartment-input"
               autocomplete="off" placeholder="Сначала выберите дом" disabled>
        <input type="hidden" name="apartment_id" id="lsr-apartment" value="">
        <ul class="lsr-form__suggest" id="lsr-apartment-suggest" hidden></ul>
        <div class="lsr-form__error" data-field="apartment_id"></div>
    </div>

    <div class="lsr-form__row">
        <button type="submit" class="lsr-form__submit">Отправить</button>
    </div>

    <div class="lsr-form__message" id="lsr-form-message"></div>
</form>

<script>
    window.LSR_FORM_CONFIG = <?= \Bitrix\Main\Web\Json::encode([
        'ajaxUrl' => $arResult['AJAX_URL'],
        'sessid'  => $arResult['SESSID'],
    ]) ?>;
</script>
