<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \modules\session\middleware\SessionCheck;
use \modules\session\helper\SessionHelper;
use \modules\session\twig\SessionTwigExtension;

use \modules\core\helper\EtagHelper;

use \modules\user\User;
use \modules\user\UserManager;
use \modules\user\UserAuthManager;
use \modules\user\middleware\UserAuth;
use \modules\user\UserValidator as validator;
use \DavidePastore\Slim\Validation\Validation;

    // GET Edit Profile page
    $app->get('/edit-profile/{username}', function (Request $request, Response $response) {
        $user = new UserManager();
        $user->username = $request->getAttribute('username'); 
        $data = $user->read();
        $data['option_status'] = $user->optionStatus();
        $data['option_role'] = $user->optionRole();
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
        $this->view->addExtension(new SessionTwigExtension);
        return $this->view->render($response, "profile-edit.twig", $data);
    })->setName("/edit-profile")
        ->add($container->get('csrf'))
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // POST Edit Profile page
    $app->post('/edit-profile/{username}', function (Request $request, Response $response) {
        $datapost = $request->getParsedBody();
        if($request->getAttribute('has_errors')){
            $errors = $request->getAttribute('errors');
            $data = [
                'status' => 'error',
                'message' => 'Parameter is not valid! ',
                'problem' => json_encode($errors)
            ];
        } else {
            $user = new UserManager();
            $user->username = $datapost['username'];
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
            $user->status = $datapost['status'];
            $user->role = $datapost['role'];
            $sh = new SessionHelper();
            $user->updated_by = $sh->get('username');
            $data = $user->updateAsAdmin();
            $data['problem'] = '';
        }

        // Create flash message to next redirected url
        $this->flash->addMessage('update',['status' => $data['status'],'message' => $data['message'],'problem' => $data['problem']]);
        // Redirect to same page
        $url = $request->getUri()->withPath($this->router->pathFor('/edit-profile',['username' => $datapost['username']]));
        return $response->withRedirect($url);
    })->setName("/edit-profile")
        ->add(new Validation(validator::update()))
        ->add($container->get('csrf'))
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // Data user page
    $app->get('/data-user', function (Request $request, Response $response) {
        $response = $this->cache->withEtag($response, EtagHelper::updateByMinute());
        $this->view->addExtension(new SessionTwigExtension);
        return $this->view->render($response, "data-user.twig", []);
    })->setName("/data-user")
        ->add(new UserAuth)
        ->add(new SessionCheck($container));

    // User Auth page
    $app->get('/user-auth/{username}', function (Request $request, Response $response) {
        $uam = new UserAuthManager();
        $uam->username = $request->getAttribute('username');
        $data = $uam->readUserAuth();
        $data['option'] = $uam->optionAuthRoutes();
        $this->view->addExtension(new SessionTwigExtension);
        return $this->view->render($response, "user-auth.twig", $data);
    })->setName("/user-auth")
        ->add(new UserAuth)
        ->add(new SessionCheck($container));
