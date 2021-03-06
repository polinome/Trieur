<?php
namespace Polinome\Trieur\Example;

/**
 * Format class tool example
 *
 * @author  polinome <contact@polinome.com>
 * @license MIT http://mit-license.org/
 */
class Format
{
    /**
     * A serialize like function, using only for test
     *
     * @param array  $row   An array
     * @param string $value A value
     *
     * @return string
     */
    public static function serialize($row, $value)
    {
        return serialize($row) . '|' . $value;
    }

    /**
     * Format an sql datetime string
     *
     * @param string $dateSql The sql date string
     * @param string $format  The ouput format
     *
     * @return string
     * @see \date()
     */
    public static function sqlTo($dateSql, $format = 'd/m/Y')
    {
        $date = new \DateTime($dateSql);
        return $date->format($format);
    }
}
