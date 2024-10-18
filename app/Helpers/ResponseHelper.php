<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Response;

class ResponseHelper {
    public static function responseJson($status, $message, $data = [], $redirect = null){
        return Response::json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'redirect' => $redirect
        ], $status);
    }

}
