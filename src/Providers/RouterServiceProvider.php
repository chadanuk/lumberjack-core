<?php

namespace Rareloop\Lumberjack\Providers;

use Psr\Http\Message\RequestInterface;
use Rareloop\Lumberjack\Application;
use Rareloop\Router\Router;
use Zend\Diactoros\ServerRequestFactory;

class RouterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $router = new Router($this->app);
        $router->setBasePath($this->getBasePathFromWPConfig());

        $this->app->bind('router', $router);
        $this->app->bind(Router::class, $router);

        add_action('wp_loaded', function () {
            $request = ServerRequestFactory::fromGlobals(
                $_SERVER,
                $_GET,
                $_POST,
                $_COOKIE,
                $_FILES
            );

            $this->processRequest($request);
        });
    }

    private function getBasePathFromWPConfig()
    {
        // Infer the base path from the site's URL
        $siteUrl = get_bloginfo('url');
        $siteUrlParts = explode('/', rtrim($siteUrl, ' //'));
        $siteUrlParts = array_slice($siteUrlParts, 3);
        $basePath = implode('/', $siteUrlParts);

        if (!$basePath) {
            $basePath = '/';
        } else {
            $basePath = '/' . $basePath . '/';
        }

        return $basePath;
    }

    public function processRequest(RequestInterface $request)
    {
        $this->app->bind('request', $request);

        $response = $this->app->get('router')->match($request);

        if ($response->getStatusCode() === 404) {
            return;
        }

        $this->app->shutdown($response);
    }
}
