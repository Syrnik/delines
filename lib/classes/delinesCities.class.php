<?php

/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version
 * @copyright Serge Rodovnichenko, 2015
 * @license
 */
class delinesCities
{
    protected $data;

    function __construct()
    {
        $this->data = simplexml_load_file(waConfig::get('wa_path_plugins') . '/shipping/delines/lib/config/data/cities.xml');
    }

    public function getKladrCode($city, $region = null)
    {
        $conditions[] = "@name_lowercased='" . mb_strtolower($city) . "'";
        if($region) {
            $conditions[] = "@region_code='$region'";
        }

        $cities = $this->data->xpath("/cities/city[" . implode(' and ', $conditions) . ']');

        if(!$cities) {
            return null;
        }

        $city = array_shift($cities);

        return (string)$city['kladr'];
    }

}