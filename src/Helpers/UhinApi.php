<?php

namespace uhin\laravel_api;

use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use \Illuminate\Database\Eloquent\Builder;

class UhinApi
{

    /**
     * Creates a new query builder that can be used with all of the
     * "parseXX" methods in this class.
     *
     * Example:
     *   $query = UhinApi::getQueryBuilder(TradingPartner::class);
     *
     * @param string $class
     * @return Builder
     */
    public static function getQueryBuilder(string $class) {
        return (new $class)->newQuery();
    }

    /**
     *
     *
     * @param Builder $query
     * @param Request $request
     * @param callable $filterOverrides
     * @return Builder
     */
    public static function parseAll(Builder $query, Request $request, $filterOverrides = null) {
        $query = static::parseFilters($query, $request, $filterOverrides);
        $query = static::parseFields($query, $request);
        $query = static::parseCursor($query, $request);
        $query = static::parseSorts($query, $request);
        return $query;
    }

    /**
     * Parses filters.
     *
     * Usage:
     *  example.com?filters[field1][operator1]=value1&filters[field2][operator2]=value
     *
     * @param Builder $query
     * @param Request $request
     * @param callable $filterOverrides
     * @return Builder
     */
    public static function parseFilters(Builder $query, Request $request, $filterOverrides = null) {
        if ($request->filled('filters')) {
            // build the filter object in the structure of:
            // $filters = {
            //   'field1': {
            //     'operator1': 'value',
            //     'operator2': 'value'
            //   },
            //   'field1': {
            //     'operator1': 'value',
            //     'operator2': 'value'
            //   },
            // }
            $filters = [];
            $raw_filters = $request->query('filters');
            foreach ($raw_filters as $column => $filter) {
                $filters[$column] = [];
                if (!is_array($filter)) {
                    $filter = ['in' => $filter];
                }
                foreach ($filter as $operator => $value) {
                    $filters[$column][$operator] = $value;
                }
            }
            // Execute the filter queries
            foreach ($filters as $column => $filter) {
                foreach ($filter as $operator => $value) {

                    // First check if this filter has an override handler
                    $overridden = $filterOverrides && ($filterOverrides($query, $column, $operator, $value) === true);

                    // If no filter override was provided, then proceed
                    if (!$overridden) {
                        switch ($operator) {
                            case 'in':
                                $query->whereIn($column, explode(',', $value));
                                break;
                            case 'not':
                                $query->whereNotIn($column, explode(',', $value));
                                break;
                            case 'prefix':
                                $query->where($column, 'like', "$value%");
                                break;
                            case 'postfix':
                                $query->where($column, 'like', "%$value");
                                break;
                            case 'infix':
                                $query->where($column, 'like', "%$value%");
                                break;
                            case 'before':
                                $query->where($column, '<=', self::formatDateSearch($value));
                                break;
                            case 'after':
                                $query->where($column, '>=', self::formatDateSearch($value));
                                break;
                            case 'null':
                                $query->whereNull($column);
                                break;
                            case 'notnull':
                                $query->whereNotNull($column);
                                break;
                        }
                    }
                }
            }
        }
        return $query;
    }

    /**
     * Parses fields.
     *
     * Usage:
     *  example.com?fields=field1,field2
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public static function parseFields(Builder $query, Request $request) {
        if($request->filled('fields')) {
            $array = $request->query('fields');
            $fields = explode(",", $array[key($array)]);
            $query->select($fields);
        }
        return $query;
    }

    /**
     * Parses offset and limit.
     *
     * Usage:
     *  example.com?cursor=100&limit=10
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public static function parseCursor(Builder $query, Request $request) {
        if($request->filled('cursor')) {
            $cursor = $request->query('cursor');
        }
        else {
            $cursor = 0;
        }
        if($request->filled('limit')) {
            $limit = $request->query('limit');
        }
        else {
            $limit = 20;
        }

        $query->skip($cursor)->take($limit);
        return $query;
    }

    /**
     * Parses sorts.
     *
     * Usage:
     *  example.com?sort=field1,-field2
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public static function parseSorts(Builder $query, Request $request) {
        if($request->filled('sort')){
            $sorts = explode(",", $request->query('sort'));

            foreach ($sorts as $sort) {
                if (starts_with($sort, '-')) {
                    $field = substr($sort, 1);
                    $direction = 'desc';
                } else {
                    $field = $sort;
                    $direction ='asc';
                }

                $query->orderBy($field, $direction);

            }

        }
        return $query;
    }


    /**
     * Converts a javascript UTC timestamp to a sql date string (ie: 2016-09-21 16:17:54)
     *
     * @param $value
     * @return mixed
     */
    public static function formatDateSearch ($value) {
        $timestamp = intval($value) / 1000.0;
        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Fills a model with all data from the request except for the 'id' and 'type' attributes.
     * NOTE: You should only use this function if you are on Laravel 5.6 or lower. Otherwise, use
     * the fillModelFromValidator function.
     *
     * @param Model $model
     * @param Request $request
     * @return Model
     */
    public static function fillModel(Model $model, Request $request)
    {
        foreach( $request->input('data') as $key => $value) {
            if( $key === 'id' || $key ==='type')
                continue;
            $model->$key = $value;
        }

        return $model;
    }

    /**
     * Fills a model with the given validated data. This will only strip the 'type'
     * attribute from the validated data.
     *
     * @param Model $model
     * @param array $validatedData
     * @param null|string $dataPrefix
     * @return Model
     */
    public static function fillModelFromValidator(Model $model, array $validatedData, ?string $dataPrefix = null)
    {
        if ($dataPrefix !== null) {
            $validatedData = $validatedData[$dataPrefix];
        }
        if (array_key_exists('type', $validatedData)) {
            unset($validatedData['type']);
        }
        $model->fill($validatedData);
        return $model;
    }

}