<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\ElasticSearch\Datasource\Connection;
use Cake\ElasticSearch\IndexRegistry;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    /**
     * @inheritDoc
     */
    public function bootstrap(): void
    {
        parent::bootstrap();
        $this->addPlugin('Cake/ElasticSearch');

        if (Configure::read('debug')) {
            // $this->addPlugin('DebugKit');
        }
        ConnectionManager::setDsnClassMap([
            'http' => Connection::class,
        ]);

        FactoryLocator::add('Elastic', [IndexRegistry::class, 'get']);
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                assert($request instanceof ServerRequest);

                if ($request->is('OPTIONS')) {
                    $response = new Response();
                } else {
                    $response = $handler->handle($request);
                }
                assert($response instanceof Response);

                return $response
                    ->cors($request)
                    ->allowOrigin(Configure::read('AccessControlAllowOrigin'))
                    ->allowMethods(['GET'])
                    ->allowHeaders(['X-CSRF-Token'])
                    ->exposeHeaders(['X-Reason'])
                    ->maxAge(300)
                    ->build();
            })

            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware())

            // Apply routing
            ->add(new RoutingMiddleware($this));

        return $middlewareQueue;
    }
}
