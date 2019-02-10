<?php
namespace modules\user;
use \Respect\Validation\Validator as v;
/**
 * UserValidator class
 *
 * @package    swift-user
 * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
 * @copyright  Copyright (c) 2019 M ABD AZIZ ALFIAN
 * @license    https://github.com/aalfiann/swift-modules-user/blob/master/LICENSE.md  MIT License
 */
class UserValidator {
    public static function register(){
        return [
            'username' => v::alnum()->noWhitespace()->length(3, 20),
            'email' => v::email(),
            'password' => v::length(3, 20)
        ];
    }

    public static function index(){
        return [
            'page' => v::intVal(),
            'itemperpage' => v::intVal(),
            'search' => v::length(0, 20)
        ];
    }

    public static function userinfo(){
        return [
            'username' => v::alnum()->noWhitespace()->length(3, 20)
        ];
    }

    public static function update(){
        return [
            'firstname' => v::length(0,20),
            'lastname' => v::length(0,20),
            'email' => v::email(),
            'address' => v::length(0,150),
            'city' => v::length(0,50),
            'country' => v::length(0,50),
            'postal' => v::optional(v::intVal()->length(0,6)),
            'about' => v::length(0,150),
            'avatar' => v::optional(v::url()->length(0,250)),
            'background_image' => v::optional(v::url()->length(0,250))
        ];
    }

    public static function changePassword(){
        return [
            'oldpassword' => v::length(3, 20),
            'password' => v::length(3, 20)
        ];
    }
}