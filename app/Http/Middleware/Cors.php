<?php

namespace App\Http\Middleware;

use App\SiteSettings;
use Closure;

class Cors
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
        //$domains = ['https://smarty-api.top', 'https://www.smarty-api.top'];
        //$domains = ['https://smartyvpn.com'];
        $site_settings = SiteSettings::find(1);
        $domains = explode(',',$site_settings->settings['domain']);
        // $domains = ['http://localhost:8080', 'http://localhost'];

        if(isset($request->server()['HTTP_ORIGIN'])) {
            $origin = $request->server()['HTTP_ORIGIN'];
            if(in_array($origin, $domains)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization');
            }
        }

        return $next($request);
    }
}
