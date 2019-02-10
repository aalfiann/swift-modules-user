<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \modules\session\middleware\SessionCheck;
use \modules\core\helper\EtagHelper;

use \modules\user\User;
use \modules\user\UserManager;
use \modules\user\UserAuthManager;
use \modules\user\middleware\UserAuth;
use \modules\user\UserValidator as validator;
use \DavidePastore\Slim\Validation\Validation;

    // API Add New User
    $app->post('/user/data/new', function (Request $request, Response $response) {
        $body = $response->getBody();
        $datapost = $request->getParsedBody();
        $user = new UserManager();
        $user->username = $datapost['username'];
        $user->email = $datapost['email'];
        $user->password = $datapost['password'];
        $user->password2 = $datapost['password2'];
        $body->write(json_encode($user->add()));
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user/data/new'")
        ->add(new Validation(validator::register()))
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // API Verify User
    $app->post('/user/verify', function (Request $request, Response $response) {
        $body = $response->getBody();
        $datapost = $request->getParsedBody();
        $user = new User();
        $user->username = $datapost['username'];
        $body->write(json_encode($user->verify()));
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user/verify");

    // API Verify email
    $app->post('/user/verify/email', function (Request $request, Response $response) {
        $body = $response->getBody();
        $datapost = $request->getParsedBody();
        $user = new User();
        $user->email = $datapost['email'];
        $body->write(json_encode($user->verifyEmail()));
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user/verify/email");

    // API Get User Data by Username
    $app->get('/user/info/api/json/{username}', function (Request $request, Response $response) {
        $body = $response->getBody();
        $response = $this->cache->withEtag($response, EtagHelper::updateByMinute());
        if($request->getAttribute('has_errors')){
            $errors = $request->getAttribute('errors');
            $data = [
                'status' => 'error',
                'message' => 'Parameter is not valid! ',
                'problem' => $errors
            ];
            $body->write(json_encode($data));
        } else {
            $user = new UserManager();
            $user->username = $request->getAttribute('username');
            $body->write(json_encode($user->read()));
        }
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user/info/api/json")
        ->add(new Validation(validator::userinfo()))
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // API Data User for global use 
    $app->get('/user/data/api/json/{page}/{itemperpage}', function (Request $request, Response $response) {
        $body = $response->getBody();
        $response = $this->cache->withEtag($response, EtagHelper::updateByMinute());
        if($request->getAttribute('has_errors')){
            $errors = $request->getAttribute('errors');
            $data = [
                'status' => 'error',
                'message' => 'Parameter is not valid! ',
                'problem' => $errors
            ];
            $body->write(json_encode($data));
        } else {
            $user = new UserManager();
            $user->search = (!empty($_GET['search'])?$_GET['search']:'');
            $user->page = $request->getAttribute('page');
            $user->itemperpage = $request->getAttribute('itemperpage');
            $body->write(json_encode($user->index()));
        }
        
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user/data/api/json")
        ->add(new Validation(validator::index()))
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // API Data User for DataTables ServerSide use
    $app->post('/user/data/api/json/datatables', function (Request $request, Response $response) {
        $body = $response->getBody();
        $user = new UserManager();
        $user->draw = (!empty($_POST['draw'])?$_POST['draw']:'1');
        $user->search = (!empty($_POST['search']['value'])?$_POST['search']['value']:'');
        $user->start = (!empty($_POST['start'])?$_POST['start']:'0');
        $user->length = (!empty($_POST['length'])?$_POST['length']:'10');
        $user->column = (!empty($_POST['order'][0]['column'])?$_POST['order'][0]['column']:0);
        $user->sort = (!empty($_POST['order'][0]['dir'])?$_POST['order'][0]['dir']:'asc');
        $body->write(json_encode($user->indexDatatables()));
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user/data/api/json/datatables")
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // API Option data for available auth routes
    $app->get('/user-auth/routes/api/json', function (Request $request, Response $response) use($container) {
        $body = $response->getBody();
        $user = new UserAuthManager();
        $body->write(json_encode($user->optionAuthRoutes()));
        
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user-auth/read/api/json")
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // API to append new auth routes
    $app->map(['GET','POST'],'/user-auth/routes/append/api/json',function(Request $request,Response $response){
        $body = $response->getBody();
        $datapost = $request->getParsedBody();
        $pattern = (!empty($datapost['pattern'])?$datapost['pattern']:'');
        $uam = new UserAuthManager();
        if($uam->appendAuthRoute($pattern)){
            $data = [
                'status' => 'success',
                'message' => 'Process append data route is successful!'
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Process append data route is failed!'
            ];
        }
        $body->write(json_encode($data));
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user-auth/routes/append/api/json")
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // API to delete auth routes
    $app->map(['GET','POST'],'/user-auth/routes/delete/api/json',function(Request $request,Response $response){
        $body = $response->getBody();
        $datapost = $request->getParsedBody();
        $pattern = (!empty($datapost['pattern'])?$datapost['pattern']:'');
        $uam = new UserAuthManager();
        if($uam->deleteAuthRoute($pattern)){
            $data = [
                'status' => 'success',
                'message' => 'Process delete data route is successful!'
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Process delete data route is failed!'
            ];
        }
        $body->write(json_encode($data));
        return $response->withStatus(200)
        ->withHeader('Content-Type','application/json; charset=utf-8')
        ->withBody($body);
    })->setName("/user-auth/routes/delete/api/json")
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // API Update User Auth Route
    $app->map(['GET','POST'],'/user-auth/acl/api/json/update', function (Request $request, Response $response) {
        $body = $response->getBody();
        $datapost = $request->getParsedBody();
        $uam = new UserAuthManager();
        $uam->username = $datapost['username'];
        $uam->auth = $datapost['auth'];
        $body->write(json_encode($uam->updateUserAuth()));
        return $response->withStatus(200)
            ->withHeader('Content-Type','application/json; charset=utf-8')
            ->withBody($body);
    })->setName("/user-auth/acl/api/json/update")
        ->add(new UserAuth)
        ->add(new SessionCheck($container));