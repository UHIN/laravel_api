<?php

namespace uhin\laravel_api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;

class StandardHealthCheckController extends BaseController
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function shc(Request $request)
    {
        /* Get a list of external services that are depedencies. */
        $endpoints = config('uhin.endpoints');

        $db_connection = $this->checkDatabaseConnection();

        $version_info = $this->getVersionControlInfo();

        if ($db_connection) {
            $status_code = 200;
        } else {
            $status_code = 500;
        }

        return response()->json([
            'success' => true,
            'database_connection' => $db_connection,
            'version' => $version_info,
            'service_dependencies' => $endpoints
        ])->setStatusCode($status_code);
    }

    /**
     * @return bool
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $t) {
            return false;
        }
    }

    /**
     * @return string
     */
    private function getVersionControlInfo()
    {
        try {
            $commitHash = trim(exec('git log --pretty="%h" -n1 HEAD'));

            $commitDate = new \DateTime(trim(exec('git log -n1 --pretty=%ci HEAD')));
            $commitDate->setTimezone(new \DateTimeZone('UTC'));

            return "" . $commitHash . " " . $commitDate->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return "";
        }
    }
}
