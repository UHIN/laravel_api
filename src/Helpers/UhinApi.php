<?php

namespace uhin\laravel_api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public static function parseAll($query, Request $request, $filterOverrides = null, $classKeyOverride = null, $compatibilityFlag = true) {
        $query = static::parseFilters($query, $request, $filterOverrides, $classKeyOverride);
        $query = static::parseFields($query, $request, $classKeyOverride, $compatibilityFlag);
        $query = static::parseCursor($query, $request, $classKeyOverride);
        $query = static::parseSorts($query, $request, $classKeyOverride);
        $query = static::parseRelationships($query, $request, $classKeyOverride);
        return $query;
    }

    /**
     * Parses relationships.
     *
     * Usage:
     *   example.com?relationships=value1
     *   example.com?relationships[class-key-name]=value1
     */
    public static function parseRelationships($query, Request $request, $classKeyOverride = null) {
        $classKey = $classKeyOverride ?? self::getClassKeyName($query->getModel());

        if ($request->filled('relationships')) {

            $relationships = $request->query('relationships');
            
            if (!is_array($relationships)) {
                // No specified class
                $relationships = explode(",", $relationships);
            } else if (key_exists($classKey, $relationships)) {
                // Reassign the sorts to use the ones specific to that class.
                $relationships = explode(",", $relationships[$classKey]);
            } else {
                // No values to sort by
                $relationships = [];
            }

            foreach($relationships as $relationship) {
                $relationshipCamel = Str::camel($relationship);

                $query->with([$relationshipCamel => function($relationshipQuery) use($request) {
                    self::parseAll($relationshipQuery, $request, null, null, false);
                }]);
            }
        }

        return $query;
    }

    /**
     * Parses filters.
     *
     * Usage:
     *  example.com?filters[field1][operator1]=value1&filters[field2][operator2]=value
     *  example.com?filters[class-key-name][operator1]=value1&filters[class2-table-name][operator2]=value
     *
     * @param Builder $query
     * @param Request $request
     * @param callable $filterOverrides
     * @return Builder
     */
    public static function parseFilters($query, Request $request, $filterOverrides = null, $classKeyOverride = null) {
        $classKey = $classKeyOverride ?? self::getClassKeyName($query->getModel());

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

            if (key_exists($classKey, $raw_filters)) {
                // Reassign the filters to use the ones specific to that class.
                $raw_filters = $raw_filters[$classKey];
            }

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
                        if ($column === 'fulltext_search') {
                            $query->fullTextSearch($value);
                        } else {
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
                                case 'less':
                                    $query->where($column, '<',$value);
                                    break;
                                case 'lessequal':
                                    $query->where($column, '<=',$value);
                                    break;
                                case 'greater':
                                    $query->where($column, '>',$value);
                                    break;
                                case 'greaterequal':
                                    $query->where($column, '>=',$value);
                                    break;
                            }
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
     *  example.com?[class-key-name]=field1,field2&[class2-table-name]=field3,field4
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public static function parseFields($query, Request $request, $classKeyOverride = null, $compatibilityFlag = true) {
        $classKey = $classKeyOverride ?? self::getClassKeyName($query->getModel());

        if($request->filled('fields')) {
            $fields = $request->query('fields');

            if (!is_array($fields)) {
                // No specified class
                $fields = explode(",", $fields);
            } else if (key_exists($classKey, $fields)) {
                // Reassign the fields to use the ones specific to that class.
                $fields = explode(",", $fields[$classKey]);
            } else if (!is_null(key($fields)) && $compatibilityFlag) {
                // Assign for backwards compatibility
                $key = key($fields);
                $fields = explode(",", $fields[$key]);
                Log::notice("Mismatched Key: Expected Class Key - {$classKey},  Actual Key - {$key}, ");
            } else {
                // Show all fields
                $fields = ['*'];
            }

            $query->select($fields);
        }
        return $query;
    }

    /**
     * Parses offset and limit.
     *
     * Usage:
     *  example.com?cursor=100&limit=10
     *  example.com?[class-key-name]cursor=10&limit[class-key-name]limit=10
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public static function parseCursor($query, Request $request, $classKeyOverride = null) {
        $classKey = $classKeyOverride ?? self::getClassKeyName($query->getModel());

        if($request->filled('cursor')) {
            $cursor = $request->query('cursor');

            if (is_array($cursor)) {
                if (key_exists($classKey, $cursor)) {
                    // Reassign the cursor to use the ones specific to that class.
                    $cursor = $cursor[$classKey];
                } else {
                    // Use default cursor.
                    $cursor = 0;
                }
            }
        }
        else {
            $cursor = 0;
        }
        if($request->filled('limit')) {
            $limit = $request->query('limit');

            if (is_array($limit)) {
                if (key_exists($classKey, $limit)) {
                    // Reassign the limit to use the ones specific to that class.
                    $limit = $limit[$classKey];
                } else {
                    // Use default limit.
                    $limit = 20;
                }
            }
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
     *  example.com?[class-key-name]sort=field1,-field2&[class2-table-name]=-field3
     *
     * @param Builder $query
     * @param Request $request
     * @return Builder
     */
    public static function parseSorts($query, Request $request, $classKeyOverride = null) {
        $classKey = $classKeyOverride ?? self::getClassKeyName($query->getModel());
        
        if($request->filled('sort')){
            $sorts = $request->query('sort');

            if (!is_array($sorts)) {
                // No specified class
                $sorts = explode(",", $sorts);
            } else if (key_exists($classKey, $sorts)) {
                // Reassign the sorts to use the ones specific to that class.
                $sorts = explode(",", $sorts[$classKey]);
            } else {
                // No values to sort by
                $sorts = [];
            }

            foreach ($sorts as $sort) {
                if (Str::startsWith($sort, '-')) {
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

    /**
     * Gets the dash-cased type from the class of the model associated with the query.
     * 
     * @param object $instance
     * @return string
     */
    public static function getClassKeyName($instance) {
        return Str::kebab(class_basename($instance));
    }
}