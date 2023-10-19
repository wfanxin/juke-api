<?php

namespace App\Http\Middleware;

use Closure;

class TrackingToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $inputJSON = file_get_contents("php://input");
        $params = json_decode($inputJSON, true);
        $verifyInfo = $params['verify'];

        $request->data = $params['data'];

        /// 验证token
        if (empty($verifyInfo['timestamp']) && empty($verifyInfo['signature'])) {
            echo 404;
            exit;
        }
        $useremail = 'ab@ymify.com';
        $result = $this->verify($verifyInfo['timestamp'],$useremail,$verifyInfo['signature']);
        if ($result) {
            return $next($request);
        } else {
            echo 404;
            exit;
        }
    }

    function verify($timeStr,$useremail,$signature){
        $hash="sha256";
        $result=hash_hmac($hash,$timeStr,$useremail);
        return strcmp($result,$signature)==0?1:0;
    }
}
