<?php

namespace Verba\Mod;

class Geo extends \Verba\Mod
{
    use \Verba\ModInstance;
    protected $cities_table = 'geo_cities';
    protected $countries_table = 'geo_countries';
    protected $aURLPos = 0;

    function makeAction(&$BParams)
    {
        switch ($BParams['action']) {
            case 'load_cities'    :
                $handler = 'LoadCities';
                break;
        }
        return $handler;
    }

    function detectLocation($location, $table = false, $col = false)
    {
        global $S;

        switch (true) {
            case (is_numeric($location) && $location > 0):
                $getted_location = $this->checkLocation($location, $table);
                break;
            case ($location == 'null' || $location == null):
                $getted_location = $this->getProfileLocation();
                break;
        }

        if (!is_numeric($getted_location)) {
            $getted_location = $this->getGeoLocation('weather');
        }

        return is_numeric($getted_location) ? $getted_location : $this->gC('weather', 'location');
    }

    function checkLocation($location, $table = false, $col = false)
    {
        $location = $this->DB()->escape_string($location);
        $query = <<< SQL_END
SELECT geo_cities.pred_id as location
FROM smartclick.geo_cities
SQL_END;
        if ($table) {
            $table = $table . '_cities';
            $query .= <<< SQL_END
 LEFT JOIN smartclick.$table as cities ON
cities.pred_id = geo_cities.pred_id
SQL_END;
        }
        $query .= <<< SQL_END
 WHERE geo_cities.pred_id = $location AND geo_cities.pred_id > 0
SQL_END;

        $oRes = $this->DB()->query($query);

        if ($oRes->getNumRows() > 0) {
            $row = $oRes->fetchRow();
        }
        return is_numeric($row['location']) ? $row['location'] : false;
    }

    function get_geoIP()
    {
        return array('country_code' => '', 'location' => 'Sevastopil');
    }

    function getGeoLocation($table = false, $col = false)
    {

        $geo_data = $this->get_geoIP();
        if (!empty($geo_data['location'])) {
            $geo_location = strtolower($geo_data['location']);
            if ($table == 'weather') {
                $query = <<< SQL_END
SELECT weather_cities.pred_id as location
FROM smartclick.weather_cities
LEFT JOIN smartclick.weather_countries as countries ON
countries.id = weather_cities.country_id
LEFT JOIN smartclick.geo_cities ON
geo_cities.pred_id = weather_cities.pred_id
WHERE weather_cities.en = '$geo_location' AND weather_cities.id > 0
SQL_END;
            } else {
                $query = <<< SQL_END
SQL_END;
            }
        } elseif (!empty($geo_data['country_code'])) {
            $geo_country = strtolower($geo_data['country_code']);
            if ($table == 'weather') {
                $query = <<< SQL_END
SELECT weather_cities.id as location
FROM smartclick.weather_cities
LEFT JOIN smartclick.weather_countries  as countries ON
countries.id = weather_cities.country_id
WHERE countries.iso2 = '$geo_country' AND weather_cities.capital = 1
SQL_END;
            } else {
                $query = <<< SQL_END
SQL_END;
            }
        } else return false;

        $oRes = $this->DB()->query($query);
        if ($oRes->getNumRows() > 0) {
            $row = $oRes->fetchRow();
        }

        return is_numeric($row['location']) ? $row['location'] : false;
    }

    function searchLocation($query_string, $table = false, $col = false)
    {
        $lang = ord($query_string) < 127 ? 'en' : 'ru';
        $query_string = $this->DB()->escape_string($query_string);
        if ($table == 'weather') {
            $query = <<< S_END
select cities.pred_id as id, cities.ru, weather_countries.id as country_id, weather_countries.ru as country
from smartclick.geo_cities
left join smartclick.weather_cities as cities on
cities.pred_id = geo_cities.pred_id
left join smartclick.geo_countries on
geo_countries.pred_id = cities.country_id
left join smartclick.weather_countries on
weather_countries.id = cities.country_id
where geo_cities.ru like '$query_string%' AND cities.pred_id > 0
order by cities.ru
S_END;
        } else {
            $query = <<< S_END
select geo_cities.pred_id, geo_cities.ru
from smartclick.geo_cities
where geo_cities.ru like '$query_string%'
order by geo_cities.ru
S_END;
        }

        return $this->DB()->query($query);
    }

    function searchCountryCities($country_id, $table = false, $col = false)
    {
        if ($table == 'weather') {
            $query = <<< S_END
select weather_cities.id, weather_cities.ru, weather_countries.id as country_id, weather_countries.ru as country
from smartclick.weather_cities
left join smartclick.weather_countries on
weather_countries.id = weather_cities.country_id
where weather_cities.country_id = $country_id
order by weather_cities.ru
S_END;
        } else {
            $query = <<< S_END
S_END;
        }

        return $this->DB()->query($query);
    }

    function getAllCountries($table = false, $format = 'array')
    {
        if ($table == 'weather') {
            $query = <<< S_END
select weather_countries.id as country_id, weather_countries.ru as country
from smartclick.weather_countries
order by country
S_END;
        } else {
            $query = <<< S_END
S_END;
        }
        $oRes = $this->DB()->query($query);

        if ($format == 'array') {
            $result = array();
            if ($oRes->getNumRows() > 0) {
                while ($row = $oRes->fetchRow()) {
                    $result['country_' . $row['country_id']] = $row;
                }
            }
        } elseif ($format = 'object') {
            $result = new stdClass;
            if ($oRes->getNumRows() > 0) {
                while ($row = $oRes->fetchRow()) {
                    $country = 'country_' . $row['country_id'];
                    $result->$country = new stdClass;
                    foreach ($row as $field => $value) {
                        $result->$country->$field = $value;
                    }
                }
            }
        }

        return $result;
    }

    function getLocatonData($location, $table = false, $col = false)
    {
        if ($table == 'weather') {
            $query = <<< S_END
SELECT * FROM smartclick.weather_cities
LEFT JOIN smartclick.geo_cities ON
geo_cities.pred_id = weather_cities.pred_id
WHERE weather_cities.pred_id = $location AND weather_cities.id > 0
S_END;
        } else {
            $query = <<< S_END
SELECT pred_id as id, ru, en FROM smartclick.geo_cities WHERE pred_id = $location
S_END;
        }

        $oRes = $this->DB()->query($query);

        if ($oRes->getNumRows() > 0) {
            $data = $oRes->fetchRow();
        }

        $result = new stdClass;
        foreach ($data as $field => $value) {
            $result->$field = $value;
        }

        return $result;
    }

    /**
     * Получает список городов для выбранной страны
     *
     * @return string HTML-набор опций(<options>) для тега <select>
     */
    function LoadCities()
    {
        global $S;

        $pred_ot_id = $S->otCodeToId('predefined');
        $ot_id = $_REQUEST['ot_id'];
        $attr_id = $_REQUEST['attr_id'];
        $country = $_REQUEST['iid'];
        $branch = \Verba\Branch::get_branch(array($pred_ot_id => array('iids' => $country, 'aot' => array($pred_ot_id))), 'down', 1, false, false);

        $iids = &$branch['handled'][$pred_ot_id];
        $data = array();
        if (is_array($iids) && !empty($iids)) {
            $oh = \Verba\_oh($ot_id);
            $attrs = $oh->A($attr_id)->filterValues(array(
                'id' => $iids,
            ));
            if (is_array($attrs) && count($attrs) > 0) {
                foreach ($attrs as $pred_id => $value) {
                    $data[] = array(
                        'pred_id' => $pred_id,
                        'ru' => $value,
                    );
                }
            }
        }

        return \Verba\Response\Json::wrap((bool)count($data), $data);
    }
}
