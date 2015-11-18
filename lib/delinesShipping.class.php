<?php

/**
 * @property string $app_key
 * @property array $arrival
 * @property string $cost_rounding_rule
 * @property bool $cost_round_up_only
 * @property string $derival_city
 * @property string $derival_city_code
 * @property bool $derival_door
 * @property string $handling_cost
 * @property string $handling_cost_rule
 * @property string $handling_days
 * @property string $limit_hour
 * @property string $parcel_volume
 * @property array $shipping_days
 */
class delinesShipping extends waShipping
{

    const LOG_FILE = 'delines.log';

    /**
     *
     * List of allowed address paterns
     * @return array
     */
    public function allowedAddress()
    {
        return array(array('country' => 'rus'));
    }

    /**
     *
     * @return string ISO3 currency code or array of ISO3 codes
     */
    public function allowedCurrency()
    {
        return 'RUB';
    }

    /**
     *
     * @return string Weight units or array of weight units
     */
    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function requestedAddressFields()
    {
        $fields = array(
            'country' => array('cost' => true, 'hidden' => true, 'value' => 'rus'),
            'region'  => array('cost' => true),
            'city'    => array('cost' => true),
            'zip'     => array('cost' => true)
        );

        if (isset($this->arrival['door']) && $this->arrival['door']) {
            $fields['street'] = array();
        }

        return $fields;
    }

    /**
     *
     * Returns shipment current tracking info
     * @param $tracking_id
     * @return string Tracking information (HTML)
     */
    public function tracking($tracking_id = null)
    {
        if (!$tracking_id) {
            return null;
        }
        $DelinesApi = new delinesApi($this->app_key);

        try {
            $result = $DelinesApi->track($tracking_id);
        } catch (waException $e) {
            waLog::log($e->getMessage(), self::LOG_FILE);

            return null;
        }

        return $result;
    }

    /**
     *
     */
    protected function calculate()
    {
        if ($this->getAddress('country') !== 'rus') {
            throw new waException('Расчет стоимости доставки возможен только для Российской Федерации');
        }
        /** @var string $region_code */
        $region_code = $this->getAddress('region');

        /** @var string $postal_index */
        $postal_index = $this->getAddress('zip');

        /** @var string $city_name */
        $city_name = $this->getAddress('city');

        if (!($region_code && $city_name) && !$postal_index) {
            return array(
                'rate'    => null,
                'comment' => 'Для расчета стоимости доставки укажите регион и город, либо почтовый индекс'
            );
        }

        $city_code = null;
        if ($region_code && $city_name) {
            $Cities = new delinesCities();
            $city_code = $Cities->getKladrCode($city_name, $region_code);
        }

        if (!$city_code) {
            if ($postal_index) {
                $city_code = $postal_index;
            } else {
                throw new waException('Доставка по указанному адресу невозможна');
            }
        }

        $delines_rates_door = array();
        $delines_rates_terminal = array();
        $rates_to_door = array();
        $rates_to_terminal = array();
        $DelinesApi = new delinesApi($this->app_key);
        $days_to_ship = $this->calcDaysToShip();

        // Считаем до двери
        if (isset($this->arrival['door']) && $this->arrival['door']) {
            try {
                $delines_rates_door = $DelinesApi->calc(
                    substr($this->derival_city_code, 1),
                    $city_code,
                    ($this->getTotalWeight() ? $this->getTotalWeight() : 1),
                    (floatval(str_replace(',', '.', $this->parcel_volume)) ? floatval(str_replace(',', '.', $this->parcel_volume)) : 0.1),
                    $this->getTotalPrice(),
                    false,
                    true
                );
            } catch (waException $e) {
                throw new waException('Доставка по указанному адресу невозможна');
            }

            $rates_to_door = array(
                'DOOR' => array(
                    'name'         => 'Доставка курьером до двери',
                    'rate'         => $this->calcTotalShippingCost($this->getTotalPrice(), $delines_rates_door['price']),
                    'currency'     => 'RUB',
                    'est_delivery' => waDateTime::format('humandate', strtotime(sprintf("+%d days", $delines_rates_door['days'] + $days_to_ship)))
                )
            );
        }

        // Считаем до теминала
        if (isset($this->arrival['terminal']) && $this->arrival['terminal']) {
            try {
                $delines_rates_terminal = $DelinesApi->calc(
                    substr($this->derival_city_code, 1),
                    $city_code,
                    ($this->getTotalWeight() ? $this->getTotalWeight() : 1),
                    (floatval(str_replace(',', '.', $this->parcel_volume)) ? floatval(str_replace(',', '.', $this->parcel_volume)) : 0.1),
                    $this->getTotalPrice(),
                    false,
                    false
                );
            } catch (waException $e) {
                throw new waException('Доставка по указанному адресу невозможна3');
            }

            if (isset($delines_rates_terminal['terminals']) && is_array($delines_rates_terminal['terminals'])) {
                $price = $this->calcTotalShippingCost($this->getTotalPrice(), $delines_rates_terminal['price']);
                foreach ($delines_rates_terminal['terminals'] as $terminal_variant) {
                    $rates_to_terminal["TERMINAL_" . md5($terminal_variant['name'])] = array(
                        'name'         => "Доставка до терминала " . $terminal_variant['name'],
                        'rate'         => $price,
                        'currency'     => 'RUB',
                        'comment'      => $terminal_variant['address'],
                        'est_delivery' => waDateTime::format('humandate', strtotime(sprintf("+%d days", $delines_rates_terminal['days'] + $days_to_ship)))
                    );
                }
            }
        }

        if (!$rates_to_door && !$rates_to_terminal) {
            throw new waException('Доставка по указанному адресу невозможна4');
        }

        return $rates_to_door + $rates_to_terminal;

    }

    /**
     * 1. Учесть перенос часа
     * 2. Учесть комлектацию
     * 3. Учесть день недели
     *
     * @return int
     * @throws waException
     */
    private function calcDaysToShip()
    {
        $days_to_add = intval($this->handling_days);
        $limit_hour = intval($this->limit_hour);
        $limit_hour = (($limit_hour > 0) && ($limit_hour < 24)) ? $limit_hour : 0;

        if ($limit_hour && date('H') >= $limit_hour) {
            $days_to_add++;
        }

        if ($this->shipping_days && count($this->shipping_days) < 7) {
            while (
                ($dow = date('N', strtotime("+$days_to_add days"))) &&
                (!isset($this->shipping_days[$dow]) || !$this->shipping_days[$dow]) &&
                $days_to_add < 30
            ) {
                $days_to_add++;
            }
        }

        if ($days_to_add >= 30) {
            throw new waException("Насчитался месяц до отправки. Вероятно какая-то ошибка. Расчет прерван.");
        }

        return $days_to_add;
    }

    /**
     *
     */
    protected function init()
    {
        $autoload = waAutoload::getInstance();
        foreach (array('delinesApi', 'delinesCities') as $class_name) {
            $autoload->add($class_name, "wa-plugins/shipping/delines/lib/classes/$class_name.class.php");
        }
        parent::init();
    }

    /**
     * Возвращает стоимость доставки включая все наценки
     *
     * @param float $order_cost Стоимость заказа (если нужно вычислять процент)
     * @param float $delivery_cost
     * @return float
     */
    private function calcTotalShippingCost($order_cost, $delivery_cost)
    {
        $percent_sign_pos = strpos($this->handling_cost, '%');
        $order_cost = floatval(str_replace(',', '.', $order_cost));
        $delivery_cost = floatval(str_replace(',', '.', $delivery_cost));

        $result = 0;

        // Фиксированная наценка
        if ($percent_sign_pos === false) {
            $result = $delivery_cost + floatval($this->handling_cost);
        } else {
            $cost = substr($this->handling_cost, 0, $percent_sign_pos + 1);
            if (strlen($cost) < 1) {
                $result = $delivery_cost;
            } else {
                switch ($this->handling_cost_rule) {
                    case 'delivery' :
                        $base = $delivery_cost;
                        break;
                    case 'total' :
                        $base = $order_cost + $delivery_cost;
                        break;
                    case 'order' :
                    default:
                        $base = $base = $order_cost;
                        break;
                }

                $result = $delivery_cost + $base * floatval($cost) / 100;
            }
        }

        return $this->costRound($result);
    }

    /**
     * @todo Wait for PHP 5.3 only, then update roundup
     * @param float $cost
     * @return float
     */
    private function costRound($cost)
    {
        $rule = floatval($this->cost_rounding_rule);

        switch ($rule) {
            case 0.1 :
                $rounded = round($cost, 1);
                break;
            case 1 :
                $rounded = round($cost);
                break;
            case 10 :
                $rounded = round($cost, -1);
                break;
            case 100:
                $rounded = round($cost, -2);
                break;
            case 0.01:
            default:
                $rounded = round($cost, 2);
                break;
        }

        if ($this->cost_round_up_only && ($cost > $rounded)) {
            $rounded += $rule;
        }

        return $rounded;
    }

    /**
     *
     * Инициализация значений настроек модуля доставки
     */
    public function saveSettings($settings = array())
    {
        if (!$settings['derival_city']) {
            throw new waException('Не указан город отправки');
        }

        $Cities = new delinesCities();
        $city_code = $Cities->getKladrCode($settings['derival_city']);
        if (!$city_code) {

            throw new waException('Неизвестный город отправки');
        }

        $settings['derival_city_code'] = "F$city_code";

        return parent::saveSettings($settings);
    }


}