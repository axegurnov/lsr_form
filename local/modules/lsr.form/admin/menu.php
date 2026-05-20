<?php

$aMenu = [
    'parent_menu' => 'global_menu_services',
    'section'     => 'lsr_form',
    'sort'        => 700,
    'text'        => 'LSR Форма',
    'title'       => 'Заявки с формы LSR',
    'icon'        => 'iblock_menu_icon_types',
    'page_icon'   => 'iblock_page_icon',
    'items_id'    => 'menu_lsr_form',
    'items'       => [
        [
            'text'     => 'Заявки',
            'url'      => 'lsr_form_request_list.php?lang=' . LANGUAGE_ID,
            'more_url' => ['lsr_form_request_edit.php'],
            'title'    => 'Список заявок',
        ],
    ],
];

return [$aMenu];