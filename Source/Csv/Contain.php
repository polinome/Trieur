<?php

namespace Polinome\Trieur\Source\Csv;

/**
 * Csv filter class for Contain filter.
 *
 * @author  polinome <contact@polinome.com>
 * @license MIT http://mit-license.org/
 */
class Contain extends Filter
{
    /**
     * Filter.
     *
     * @return bool
     */
    public function filter()
    {
        if (is_array($this->terms)) {
            $term = implode(' ', $this->terms);
        } else {
            $term = $this->terms;
        }

        $words = preg_split('`\s+`', $term);
        foreach ($words as $word) {
            foreach ($this->columns as $column) {
                if (stripos($this->row[$column], $word) !== false
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
