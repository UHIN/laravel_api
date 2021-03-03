<?php

namespace uhin\laravel_api\Helpers;

use RuntimeException;

/**
 * Created by PhpStorm.
 * User: chejazi
 * Date: 11/29/18
 * Time: 8:56 AM
 */
trait FullTextSearchable
{

    /**
     * Replaces spaces with full text search wildcards
     *
     * @param string $term
     * @return string
     */
    protected function fullTextWildcards($term)
    {
        // removing symbols used by MySQL
        $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
        $term = str_replace($reservedSymbols, ' ', $term);

        $words = explode(' ', $term);

        foreach ($words as $key => $word) {
            /*
             * applying + operator (required word) only big words
             * because smaller ones are not indexed by mysql
             */
            if (strlen($word) >= 3) {
                $words[$key] = '+' . $word . '*';
            }
        }

        $searchTerm = implode(' ', $words);

        return $searchTerm;
    }

    /**
     * Scope a query that matches a full text search of term.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFullTextSearch($query, $term)
    {
        if (!property_exists(self::class, 'fullTextFields')) {
            throw new RuntimeException('To implement full text searching, you must add the "fullTextFields" property to your model.');
        }
        $columns = implode(',', $this->fullTextFields);

        $query->whereRaw("MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE)", $this->fullTextWildcards($term));

        return $query;
    }

}