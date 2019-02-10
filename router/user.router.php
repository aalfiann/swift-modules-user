<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \modules\session\middleware\SessionCheck;
use \modules\session\helper\SessionHelper;
use \modules\session\twig\SessionTwigExtension;

use \modules\core\helper\EtagHelper;
use \modules\mailer\Mailer;

use \modules\user\User;
use \modules\user\UserManager;
use \modules\user\UserValidator as validator;
use \DavidePastore\Slim\Validation\Validation;

    // Get Login page
    $app->get('/login', function (Request $request, Response $response) {
        return $this->view->render($response, "login.twig", []);
    })->setName("/login")->add($container->get('csrf'));

    // Post Login page
    $app->post('/login', function (Request $request, Response $response) {
        $datapost = $request->getParsedBody();
        $user = new User();
        $user->username = $datapost['username'];
        $user->password = $datapost['password'];
        $data = $user->login();
        if($data['status'] == 'success') {
            $sh = new SessionHelper();
            $sh->set('username',$user->username);
            $sh->set('avatar',$data['avatar']);
            $url = $request->getUri()->withPath($this->router->pathFor('/dashboard'));
            return $response->withRedirect($url);
        }

        return $this->view->render($response, "login.twig", $data);
    })->add($container->get('csrf'));

    // Get Register page
    $app->get('/register', function (Request $request, Response $response) {
        $data = [
            'status' => '',
            'message' => ''
        ];
        return $this->view->render($response, "register.twig", $data);
    })->setName("/register")->add($container->get('csrf'));

    // Post Register page
    $app->post('/register', function (Request $request, Response $response) {
        $datapost = $request->getParsedBody();
        //Check the validation
        if($request->getAttribute('has_errors')){
            $errors = $request->getAttribute('errors');
            $data = [
                'status' => 'error',
                'message' => 'Parameter is not valid! ',
                'problem' => json_encode($errors)
            ];
        } else {
            $user = new User();
            $user->username = $datapost['username'];
            $user->password = $datapost['password'];
            $user->password2 = $datapost['password2'];
            $user->email = $datapost['email'];
            $data = $user->register();
        }

        return $this->view->render($response, "register.twig", $data);
    })->add(new Validation(validator::register()))->add($container->get('csrf'));

    // Dashboard page
    $app->get('/dashboard', function (Request $request, Response $response) {
        $response = $this->cache->withEtag($response, EtagHelper::updateByMinute());
        return $this->view->render($response, "dashboard.twig", []);
    })->setName("/dashboard")->add(new SessionCheck($container));

    // Logout
    $app->get('/logout', function (Request $request, Response $response) {
        $sh = new SessionHelper();
        $sh->destroy();
        $url = $request->getUri()->withPath($this->router->pathFor('/login'));
        return $response->withRedirect($url);
    })->setName("/logout");

    // Get Forgot page
    $app->get('/user/forgot', function (Request $request, Response $response) {
        $data = [
            'status' => '',
            'message' => ''
        ];
        return $this->view->render($response, "forgot.twig", $data);
    })->setName("/user/forgot")->add($container->get('csrf'));

    // Process forgot key
    $app->post('/user/forgot', function (Request $request, Response $response) {
        $datapost = $request->getParsedBody();
        $email = $datapost['email'];
        $user = new User();
        $user->email = $email;
        $data = $user->generateForgotKey();
        if($data['status'] == 'success'){
            $linkverify = $request->getUri()->withPath($this->router->pathFor('/user/forgot/verify',['key'=>$data['key']]));
            $mailer = new Mailer($this->settings);
            $mailer->addAddress = $email;
            $mailer->subject = 'Request reset password';
            $mailer->body = '<html><body><p>You have already requested to reset password.<br /><br />
            Here is the link to reset: <a href="'.$linkverify.'" target="_blank"><b>'.$linkverify.'</b></a>.<br /><br />
            
            Just ignore this email if You don\'t want to reset password. Link will be expired in 3 days.<br /><br /><br />
            Thank You<br />
            '.$this->settings['app']['name'].'</p></body></html>';
            $result = $mailer->send();
        } else {
            $result = $data;
        }
        return $this->view->render($response, "forgot.twig", $result);
    })->add($container->get('csrf'));

    // Get verify page
    $app->get('/user/forgot/verify/{key}', function (Request $request, Response $response) {
        $user = new User();
        $user->key = $request->getAttribute('key');
        if($user->isForgotKeyActive()){
            $username = $user->getUsernameByForgotKey();
            $data = [
                'expired' => false,
                'username' => $username,
                'message' => ''
            ];
        } else {
            $data = [
                'expired' => true,
                'username' => '',
                'message' => 'The key is wrong or has been expired!'
            ];
        }
        return $this->view->render($response, "reset-password.twig", $data);
    })->setName("/user/forgot/verify")->add($container->get('csrf'));

    // Process reset password
    $app->post('/user/forgot/verify/{key}', function (Request $request, Response $response) {
        $datapost = $request->getParsedBody();
        $user = new User();
        $user->username = $datapost['username'];
        $user->key = $request->getAttribute('key');
        $user->password = $datapost['password'];
        $user->password2 = $datapost['password2'];
        $data = $user->resetPassword();
        $data['expired'] = false;
        return $this->view->render($response, "reset-password.twig", $data);
    })->add($container->get('csrf'));

    // GET Profile page
    $app->get('/my-profile', function (Request $request, Response $response) {
        $sh = new SessionHelper();
        $user = new UserManager();
        $user->username = $sh->get('username'); 
        $data = $user->read();
        // Remove the data status from reading data
        if($data['status'] == 'success') {
            unset($data['status']);
            unset($data['message']);
        }
        // Get if there is any flash message before request
        if($this->flash->hasMessage('update')){
            $message = $this->flash->getMessage('update');
            // create new data status for response in twig
            $data['status'] = $message[0]['status'];
            $data['message'] = $message[0]['message'];
            $data['problem'] = $message[0]['problem'];
        }
        return $this->view->render($response, "profile.twig", $data);
    })->setName("/my-profile")
        ->add($container->get('csrf'))
        ->add(new SessionCheck($container));

    // POST Profile page
    $app->post('/my-profile', function (Request $request, Response $response) {
        if($request->getAttribute('has_errors')){
            $errors = $request->getAttribute('errors');
            $data = [
                'status' => 'error',
                'message' => 'Parameter is not valid! ',
                'problem' => json_encode($errors)
            ];
        } else {
            $datapost = $request->getParsedBody();
            $sh = new SessionHelper();
            $user = new UserManager();
            $user->username = $sh->get('username');
            $user->email = $datapost['email'];
            $user->firstname = $datapost['firstname'];
            $user->lastname = $datapost['lastname'];
            $user->address = $datapost['address'];
            $user->city = $datapost['city'];
            $user->country = $datapost['country'];
            $user->postal = $datapost['postal'];
            $user->avatar = $datapost['avatar'];
            $user->background_image = $datapost['background_image'];
            $user->about = $datapost['about'];
            $user->updated_by = $user->username;
            $data = $user->update();
            $data['problem'] = '';
            $sh->set('avatar',$user->avatar);
        }

        // Create flash message to next redirected url
        $this->flash->addMessage('update',['status' => $data['status'],'message' => $data['message'],'problem' => $data['problem']]);
        // Redirect to same page
        $url = $request->getUri()->withPath($this->router->pathFor('/my-profile'));
        return $response->withRedirect($url);
    })->setName("/my-profile")
        ->add(new Validation(validator::update()))
        ->add($container->get('csrf'))
        ->add(new SessionCheck($container));

    // GET Change Password page
    $app->get('/change-password', function (Request $request, Response $response) {
        $this->view->addExtension(new SessionTwigExtension);
        return $this->view->render($response, "change-password.twig", []);
    })->setName("/change-password")
        ->add($container->get('csrf'))
        ->add(new SessionCheck($container));

    // POST Change Password page
    $app->post('/change-password', function (Request $request, Response $response) {
        $datapost = $request->getParsedBody();
        if($request->getAttribute('has_errors')){
            $errors = $request->getAttribute('errors');
            $data = [
                'status' => 'error',
                'message' => 'Parameter is not valid! ',
                'problem' => json_encode($errors)
            ];
        } else {
            $sh = new SessionHelper();
            $user = new User();
            $user->username = $sh->get('username');
            $user->oldpassword = $datapost['oldpassword'];
            $user->password = $datapost['password'];
            $user->password2 = $datapost['password2'];
            $data = $user->changePassword();
        }
        return $this->view->render($response, "change-password.twig", $data);
    })->setName("/change-password")
        ->add($container->get('csrf'))
        ->add(new Validation(validator::changePassword()))
        ->add(new SessionCheck($container));

    