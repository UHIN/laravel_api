<?php
/**
 * Created by PhpStorm.
 * User: rmclelland
 * Date: 2/20/18
 * Time: 12:57 PM
 */

namespace uhin\laravel_api;



use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class uhin_api
{

    public static function parseAPICall(Model $model, Request $request) {
        $query = ($model)->newQuery();

        //get filters
        if ($request->has('filters')) {
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

        //handle fields
        if($request->has('fields')) {
            $fields = explode(",", $request->query('fields'));
            $query->select($fields);
        }

        //get cursors
        if($request->has('cursor')) {
            $cursor = $request->query('cursor');
        }
        else {
            $cursor = 0;
        }
        if($request->has('limit')) {
            $limit = $request->query('limit');
        }
        else {
            $limit = 20;
        }

        $query->skip($cursor)->take($limit);

        //get sorting
        if($request->has('sort')){
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

        return $query->get();

    }

    public static function parseFields(Model $model, Request $request, $id) {

        $query = ($model)->newQuery();


        if($request->has('fields')) {

            $fields = explode(",", $request->query('fields'));

            return $query->select($fields)->find($id);;

        } else {

            return  $query->find($id);;

        }

    }


    /**
     * Converts a javascript UTC timestamp to a sql date string (ie: 2016-09-21 16:17:54)
     *
     * @param $value
     * @return mixed
     */
    private static function formatDateSearch ($value) {
        $timestamp = intval($value) / 1000.0;
        return gmdate('Y-m-d H:i:s', $timestamp);
    }

}