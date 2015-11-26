<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version
 * @copyright Serge Rodovnichenko, 2015
 * @license
 */

return array(
    'app_key'            => array(
        'title'        => 'Ключ приложения',
        'description'  => 'Ключ для доступа к публичному API, полученный при регистрации приложения',
        'control_type' => waHtmlControl::INPUT
    ),
    'derival_city'       => array(
        'title'        => 'Город отправки',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
        'value'        => 'Москва'
    ),
    'derival_city_code'  => array(
        'control_type' => waHtmlControl::HIDDEN,
        'value'        => ''
    ),
    'derival_door'       => array(
        'title'        => 'Забор отправлений от дверей',
        'description'  =>
            'Выберите этот пункт, если Деловые Линии забирают отправления с вашего склада. ' .
            'Уберите отметку, если вы сами привозите грузы на склад Деловых Линий',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 0
    ),
    'parcel_volume'      => array(
        'value'        => '0.01',
        'title'        => 'Средний объем отправления (м3)',
        'description'  =>
            'Средний объем отправления в кубометрах (m<sup>3</sup>). К сожалению, в текущей версии нет возможности ' .
            'рассчитывать объем исходя из данных заказа, поэтому все вычисления будут производиться для ' .
            'объема указанного здесь',
        'control_type' => waHtmlControl::INPUT
    ),
    'arrival'            => array(
        'title'        => 'Виды доставки',
        'control_type' => waHtmlControl::GROUPBOX,
        'options'      => array(
            array('title' => 'До двери', 'value' => 'door'),
            array('title' => 'До терминала', 'value' => 'terminal')
        ),
        'value'        => array('door', 'terminal')
    ),
    'arrival_order'      => array(
        'value'        => 'courier-first',
        'title'        => 'Очередность вариантов доставки',
        'description'  =>
            'Очередность показа вариантов доставки курьерская или до терминала. Если для города доступны и курьерская ' .
            'доставка, и терминалы, то здесь можно указать какой вариант будет первым',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array('courier-first' => 'Сначала курьер', 'store-first' => 'Сначала терминалы'),
    ),
    'limit_hour'         => array(
        'value'        => 18,
        'title'        => 'Час переноса отгрузки',
        'description'  => 'Час, после которого к сроку доставки прибавляется 1 день. Укажите 0, чтобы выключить эту функцию',
        'control_type' => waHtmlControl::INPUT
    ),
    'handling_days'      => array(
        'value'        => 0,
        'title'        => 'Срок комплектации',
        'description'  => 'Дополнительное количество дней на комплектацию заказа. Срок будет добавлен к расчетному сроку доставки',
        'control_type' => waHtmlControl::INPUT
    ),
    'shipping_days'      => array(
        'title'        => 'Дни недели для отгрузки',
        'description'  => 'Дни недели, в которые осуществляется передача заказов на отправку. Сначала считается дата, когда заказ будет скомплектован в соответствии с предыдущими разделами настроек, после вычисляется первый подходящий день отправки',
        'control_type' => waHtmlControl::GROUPBOX,
        'options'      => array(
            array('value' => 1, 'title' => 'Понедельник'),
            array('value' => 2, 'title' => 'Вторник'),
            array('value' => 3, 'title' => 'Среда'),
            array('value' => 4, 'title' => 'Четверг'),
            array('value' => 5, 'title' => 'Пятница'),
            array('value' => 6, 'title' => 'Суббота'),
            array('value' => 7, 'title' => 'Воскресенье'),
        ),
        'value'        => array(1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1)
    ),
    'handling_cost'      => array(
        'title'        => 'Стоимость комплектации',
        'description'  => 'Дополнительная сумма, которая должна быть добавлена к результату расчета. Фиксированная сумма или проценты. Например "100" - 100 рублей, "10%" - 10 процентов',
        'value'        => 0,
        'control_type' => waHtmlControl::INPUT
    ),
    'handling_cost_rule' => array(
        'title'        => 'Начисления стоимости комплектации',
        'description'  => 'Правило начисления стоимости комплектации. Имеет смысл, если стоимость указана в процентах',
        'value'        => 'order',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            array('value' => 'order', 'title' => 'Стоимость заказа', 'description' => '% наценки будет вычисляться из стоимости заказа'),
            array('value' => 'delivery', 'title' => 'Стоимость доставки', 'description' => '% наценки будет вычисляться из стоимости доставки'),
            array('value' => 'total', 'title' => 'Сумма стоимости заказа и доставки', 'description' => '% наценки будет вычисляться из общей суммы стоимости доставки и стоимости заказа')
        )
    ),
    'cost_rounding_rule' => array(
        'title'        => 'Правило округления',
        'description'  => 'Выбор типа округления стоимости тарифа',
        'value'        => '0.01',
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            array('value' => '100', 'title' => 'Округлять до 100'),
            array('value' => '10', 'title' => 'Округлять до 10'),
            array('value' => '1', 'title' => 'Округлять до 1'),
            array('value' => '0.1', 'title' => 'Округлять до 0.1'),
            array('value' => '0.01', 'title' => 'Округлять до 0.01'),
        )
    ),
    'cost_round_up_only' => array(
        'title'        => 'Округлять только вверх',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1
    ),
);