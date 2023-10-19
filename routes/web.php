<?php
use \Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the 'web' middleware group. Now create something great!
|
*/
/************************ test *********************/

/*********************************************/
Route::get('/', function (\Illuminate\Http\Request $request) {
    echo '404';
    exit;
});
Route::get('api/', function (\Illuminate\Http\Request $request) {
    echo '404';
    exit;
});
Route::get('api/control_auth', function (\Illuminate\Http\Request $request) {
    // echo 'auth'; // 限制
    echo 'normal'; // 正常
    exit;
});
/************************ web *************************/
