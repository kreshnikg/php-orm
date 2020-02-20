<?php

/**
 * @param string $message
 * @param int $code
 */
function response($message,$code = 200)
{
    echo $message;
    die(http_response_code($code));
}

/**
 * Example $data = ["string", 2,"another string" ,2.02], => $types = "sisd"
 * String = 's';
 * Integer = 'i';
 * Double = 'd';
 * @param array $data
 * @return string
 */
function dataTypesToString($data)
{
    $types = '';
    foreach ($data as $dt) {
        $varType = gettype($dt);
        switch ($varType) {
            case 'integer':
                $types .= 'i';
                break;
            case 'string':
                $types .= 's';
                break;
            case 'double':
                $types .= 'd';
                break;
            default:
                $types .= 's';
        }
    }
    return $types;
}

/**
 * Remove items from array
 * @param $array
 * @param array $toRemove
 * @return array
 */
function filterVars($array, $toRemove = [])
{
    if (count($toRemove) === 0)
        $toRemove = [
            'connection',
            'table',
            'primaryKey',
            'query',
            'values',
            'timestamps',
            'with',
            'CREATED_AT',
            'UPDATED_AT'
        ];
    return array_filter(
        $array,
        function ($key) use ($toRemove) {
            foreach ($toRemove as $item) {
                if ($item == $key) {
                    return false;
                }
            }
            return true;
        }, ARRAY_FILTER_USE_KEY
    );
}
