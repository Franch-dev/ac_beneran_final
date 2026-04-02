# Illuminate\Database\QueryException - Internal Server Error

SQLSTATE[HY000] [1049] Unknown database 'main_platform' (Connection: main, Host: 127.0.0.1, Port: 3306, Database: main_platform, SQL: select * from `sessions` where `id` = gExNtLfphaIEdakie9qJvu28ZJWZM10CKkoiAayW limit 1)

PHP 8.3.26
Laravel 12.52.0
127.0.0.1:8000

## Stack Trace

0 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:838
1 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:794
2 - vendor\laravel\framework\src\Illuminate\Database\Connection.php:411
3 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3438
4 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3423
5 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:4013
6 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3422
7 - vendor\laravel\framework\src\Illuminate\Database\Concerns\BuildsQueries.php:366
8 - vendor\laravel\framework\src\Illuminate\Database\Query\Builder.php:3345
9 - vendor\laravel\framework\src\Illuminate\Session\DatabaseSessionHandler.php:96
10 - vendor\laravel\framework\src\Illuminate\Session\Store.php:128
11 - vendor\laravel\framework\src\Illuminate\Session\Store.php:116
12 - vendor\laravel\framework\src\Illuminate\Session\Store.php:100
13 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:146
14 - vendor\laravel\framework\src\Illuminate\Support\helpers.php:388
15 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:143
16 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:115
17 - vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php:63
18 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
19 - vendor\laravel\framework\src\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse.php:36
20 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
21 - vendor\laravel\framework\src\Illuminate\Cookie\Middleware\EncryptCookies.php:74
22 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
23 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:137
24 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:821
25 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:800
26 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:764
27 - vendor\laravel\framework\src\Illuminate\Routing\Router.php:753
28 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:200
29 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:180
30 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php:21
31 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull.php:31
32 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
33 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php:21
34 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TrimStrings.php:51
35 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
36 - vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePostSize.php:27
37 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
38 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance.php:109
39 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
40 - vendor\laravel\framework\src\Illuminate\Http\Middleware\HandleCors.php:61
41 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
42 - vendor\laravel\framework\src\Illuminate\Http\Middleware\TrustProxies.php:58
43 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
44 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks.php:22
45 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
46 - vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePathEncoding.php:26
47 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:219
48 - vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php:137
49 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:175
50 - vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php:144
51 - vendor\laravel\framework\src\Illuminate\Foundation\Application.php:1220
52 - public\index.php:20
53 - vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php:23

## Request

GET /

## Headers

* **host**: 127.0.0.1:8000
* **connection**: keep-alive
* **sec-ch-ua**: "Chromium";v="146", "Not-A.Brand";v="24", "Google Chrome";v="146"
* **sec-ch-ua-mobile**: ?0
* **sec-ch-ua-platform**: "Windows"
* **upgrade-insecure-requests**: 1
* **user-agent**: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36
* **accept**: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
* **sec-fetch-site**: none
* **sec-fetch-mode**: navigate
* **sec-fetch-user**: ?1
* **sec-fetch-dest**: document
* **accept-encoding**: gzip, deflate, br, zstd
* **accept-language**: en-US,en;q=0.9,id;q=0.8

## Route Context

controller: App\Http\Controllers\HomeController@index
route name: home
middleware: web

## Route Parameters

No route parameter data available.

## Database Queries

No database queries detected.
