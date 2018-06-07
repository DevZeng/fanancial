<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Whoops\Exception\ErrorException;

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
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
//        dd($exception);
        $code = 422;
        if ($exception instanceof \Illuminate\Validation\ValidationException){
            $validator = $exception->validator;
            $messages = $validator->getMessageBag()->getMessages();
            $msg = [];
            foreach ($messages as $message){
                array_push($msg,$message[0]);
            }
            $message = $msg[0];
        }elseif ($exception instanceof FatalThrowableError){
            $message = '资源不存在！';
            $code = 404;
        }elseif($exception instanceof ModelNotFoundException){
            $message = '该实例不存在！';
            $code = 404;
        }elseif ($exception instanceof AuthenticationException){
            $message = '请先登录';
            $code = 401;
        }elseif($exception instanceof TokenMismatchException){
            $message = '请先登录';
            $code = 401;
        }
        else{
            $message = $exception->getMessage();
            $code = 500;
        }
        dd($exception);
        return response()->json($exception);
        return response()->json([
            'msg'=>$message
        ],$code);
//        return parent::render($request, $exception);
    }
}
