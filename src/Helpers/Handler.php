<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable $exception
     * @return void
     * @throws Throwable
     */
    public function report(\Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return Response
     */
    public function render($request, \Throwable $exception)
    {

        $errors = [];
        $meta = [];
        $http_status = null;


        if ($exception instanceof ValidationException && $exception->errors()) {
            foreach ($exception->errors() as $key => $field) {
                foreach ($field as $error) {
                    $error = preg_replace_callback('/data.([A-z\_\.]+)/', function ($matches) {
                        return str_replace('.', ' ', $matches[1]);
                    }, $error);
                    $errors[] = [
                        'title' => 'Invalid Attribute',
                        'detail' => $error,
                        'status' => $exception->status,
                        'source' => ['pointer' => '/' . str_replace('.', '/', $key) ]
                    ];
                }
            }
        } else if ($exception instanceof ModelNotFoundException) {
            $errors[] = [
                'title' => "Resource Not Found",
                'status' => (string) Response::HTTP_NOT_FOUND,
                'detail' => $exception->getMessage()
            ];
        } else if ($exception instanceof HttpException) {
            $errors[] = [
                'title' => Response::$statusTexts[$exception->getStatusCode()],
                'status' => (string) $exception->getStatusCode(),
                'detail' => $exception->getMessage()
            ];
        } else {
            $errors[] = [
                'title' => "An internal server error has occurred. If you continue to experience issues, please contact customer service. This error has been logged.",
                'status' => (string) Response::HTTP_INTERNAL_SERVER_ERROR,
                'detail' => $exception->getMessage()
            ];
        }

        if (count($errors) == 0) {
            $errors[] = [
                'title' => "An internal server error has occurred. If you continue to experience issues, please contact customer service. This error has been logged.",
                'status' => (string) Response::HTTP_INTERNAL_SERVER_ERROR,
                'detail' => $exception->getMessage()
            ];
        }
        if ($http_status == null) {
            $http_status = isset($errors[0]['status'])?$errors[0]['status']:500;
        }

        $json = ['errors' => $errors];

        if (!empty($meta)) {
            $json['meta'] = $meta;
        }

        return response()->json($json, $http_status);

    }
}
