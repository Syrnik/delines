<?php

/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version
 * @copyright Serge Rodovnichenko, 2015
 * @license
 */
class delinesApi
{
    protected $app_key;

    function __construct($app_key)
    {
        $this->app_key = $app_key;
    }

    public function calc($from, $to, $weight, $volume, $cost = 0, $from_door = false, $to_door = false)
    {
        $data = array(
            'appKey'       => $this->app_key,
            'derivalPoint' => $from,
            'derivalDoor'  => $from_door,
            'arrivalPoint' => $to,
            'arrivalDoor'  => $to_door,
            'sizedVolume'  => $volume,
            'sizedWeight'  => $weight,
        );

        if ($cost) {
            $data['statedValue'] = $cost;
        }

        try {
            $result = $this->request('https://api.dellin.ru/v1/public/calculator.json', $data);
        } catch (waException $e) {
            waLog::log($e->getMessage(), delinesShipping::LOG_FILE);

            return array();
        }

        if (isset($result['errors'])) {
            if (is_array($result['errorses'])) {
                foreach ($result['errorses'] as $k => $v) {
                    waLog::log($v['message'], delinesShipping::LOG_FILE);
                }
            }

            return array();
        }

        if (!isset($result['price'])) {
            return array();
        }

        $rates = array('price' => floatval(str_replace(',', '.', $result['price'])));

        if (!$to_door) {
            if (!isset($result['arrival']['terminals']) || !is_array($result['arrival']['terminals'])) {
                return array();
            }

            foreach ($result['arrival']['terminals'] as $terminal) {
                $rates['terminals'][] = array(
                    'name'    => $terminal['name'],
                    'address' => $terminal['address']
                );
            }
        }

        if (isset($result['time']) && is_array($result['time']) && isset($result['time']['value'])) {
            $rates['days'] = intval($result['time']['value']);
        }

        return $rates;

    }

    public function track($docId)
    {
        if (!$docId) {
            return null;
        }

        $data = array(
            'appKey' => $this->app_key,
            'docId'  => $docId
        );

        $result = $this->request('https://api.dellin.ru/v1/public/tracker.json', $data);

        if (isset($result['errors'])) {
            throw new waException($result['errors']);
        }
        $return = "<p>Состояние: <b>{$result['state']}</b>";
        if (isset($result['giveout']) && is_array($result['giveout']) && isset($result['giveout']['requiredDocuments'])) {
            $return = $return . "<br><i>{$result['giveout']['requiredDocuments']}</i>";
        }
        $return = $return . '</p>';

        return $return;
    }

    protected function request($url, $data = null, $method = 'POST')
    {
        if (!($ch = curl_init())) {
            throw new waException('Ошибка инициализации cURL');
        }

        $curl_options = array(
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => array("Content-type: application/json"),
            CURLOPT_RETURNTRANSFER => true
        );

        if ($method == 'POST') {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $curl_options);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new waException('Ошибка cURL: ' . curl_error($ch));
        }

        if (empty($response)) {
            throw new waException('Пустой ответ сервера');
        }

        $result = json_decode($response, true);

        if (is_array($result)) {
            return $result;
        }

        return $response;
    }

}