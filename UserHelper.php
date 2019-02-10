<?php 
namespace modules\user;
use \aalfiann\Filebase;
/**
 * User Helper class
 *
 * @package    swift-user
 * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
 * @copyright  Copyright (c) 2019 M ABD AZIZ ALFIAN
 * @license    https://github.com/aalfiann/swift-modules-user/blob/master/LICENSE.md  MIT License
 */
class UserHelper {

    /**
     * Data Source User
     * 
     * @return string
     */
    public function getDataSource(){
        return 'storage/user';
    }

    /**
     * Data Source User Forgot
     * 
     * @return string
     */
    public function getDataSourceForgot(){
        return 'storage/user_forgot';
    }
    
    /** 
     * HashPassword is to secure your login and password
     *
     * @param $username : input username
     * @param $password : input password
     * @return string Hashed Password
     */
    public function hashPassword($username,$password) {
        $options = [
            'cost' => 8
        ];
        return password_hash($username.$password, PASSWORD_BCRYPT, $options);
    }

    /** 
     * Verify Password is to verify your login and password is match or not
     *
     * @param $username : input username
     * @param $password : input password
     * @param $hash : your password hash saved in database
     * @return boolean true / false
     */
    public function verifyPassword($username,$password,$hash) {
        return password_verify($username.$password, $hash);
    }

    /**
     * Determine registered email
     * 
     * @param username this is to skip email check to same username, to allow update same email on same username.
     * 
     * @return bool
     */
    public function isEmailRegistered($username=null){
        $email = $this->email;
        
        $user = new \Filebase\Database([
            'dir' => 'storage/user'
        ]);

        if(!empty($username)){
            $list = $user->query()->where('email','=',$email)->andWhere('username','!=',$username)->limit(1)->results();
        } else {
            $list = $user->query()->where('email','=',$email)->limit(1)->results();
        }
        if(!empty($list)){
            if($list[0]['email'] == $email) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine Forgot Key is active or not
     * 
     * @return bool
     */
    public function isForgotKeyActive(){
        $key = $this->key;
        
        $user = new \Filebase\Database([
            'dir' => 'storage/user_forgot'
        ]);

        if ($user->has($key)) {
            $item = $user->get($key);
            if(strtotime(date('Y-m-d H:i:s')) <= $item->expired){
                return true;
            } else {
                $item->delete();
                return false;
            }
        }
        return false;
    }

    /**
     * Determine is first register user action
     * 
     * @return bool
     */
    public function isFirstRegisterUser(){
        $user = new \Filebase\Database([
            'dir' => 'storage/user'
        ]);

        $list = $user->query()->limit(1)->results();
        if(!empty($list)){
            return false;
        }
        return true;
    }

}