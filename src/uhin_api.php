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


}