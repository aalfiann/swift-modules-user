<?php 
namespace modules\user\middleware;
use \modules\session\helper\SessionHelper;
use \aalfiann\Filebase;
    /**
     * A middleware class for user authorization
     *
     * @package    swift-user
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2019 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/swift-modules-user/blob/master/license.md  MIT License
     */
    class UserAuth {
        /**
         * UserAuth middleware invokable class
         * 
         * @param \Psr\Http\Message\ServerRequestInterface  $request    PSR7 request
         * @param \Psr\Http\Message\ResponseInterface       $response   PSR7 response
         * @param callable                                  $next       Next middleware
         * 
         * @return \Psr\Http\Message\ResponseInterface
         */
        public function __invoke($request, $response, $next){
            $rules = [];
            $sh = new SessionHelper();
            if($sh->exists('username')){
                $username = $sh->get('username');

                $user = new \Filebase\Database([
                    'dir' => 'storage/user'
                ]);

                if ($user->has($username)) {
                    $item = $user->get($username);
                    $rules = $item->auth;
                }
            }

            if(!empty($rules)){
                $routes = $request->getAttribute('route');
                $pattern = false;
                $method = false;
                $datamethod = [];
                foreach($rules as $rule){
                    if(in_array($routes->getPattern(),$rule)){
                        $pattern = true;
                        if (in_array($request->getMethod(),$rule['methods'])){
                            $method = true;
                        } else {
                            $datamethod = $rule['methods'];
                        }
                    }
                }
                if($pattern && $method) {
                    $response = $next($request, $response);    
                    return $response;
                }
                if(!empty($datamethod)){
                    throw new \Slim\Exception\MethodNotAllowedException($request, $response, $datamethod);
                }
            }
            throw new \Slim\Exception\NotFoundException($request, $response);
        }
    }