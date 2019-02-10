<?php
namespace modules\user;
use \aalfiann\Filebase;
/**
 * User Auth Manager class
 *
 * @package    swift-user
 * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
 * @copyright  Copyright (c) 2019 M ABD AZIZ ALFIAN
 * @license    https://github.com/aalfiann/swift-modules-user/blob/master/LICENSE.md  MIT License
 */

class UserAuthManager extends UserHelper {

    protected $key = 'route.auth';

    /**
     * Append data route for authorization
     * 
     * @param pattern is the pattern of routes. (not the route name)
     * 
     * @return bool
     */
    public function appendAuthRoute($pattern){
        if(!is_file($this->key)){
            return \Filebase\Filesystem::write($this->key,json_encode([$pattern],JSON_UNESCAPED_SLASHES));
        }
        $content = json_decode(\Filebase\Filesystem::read($this->key),true);
        if($content){
            if ((array_search($pattern, $content)) === false) {
                array_push($content,$pattern);
                return \Filebase\Filesystem::write($this->key,json_encode($content,JSON_UNESCAPED_SLASHES));
            } else {
                return false;
            }
        }
        return \Filebase\Filesystem::write($this->key,json_encode([$pattern],JSON_UNESCAPED_SLASHES));
    }

    /**
     * Delete data route for authorization
     * 
     * @param pattern is the pattern of routes. (not the route name)
     * 
     * @return bool
     */
    public function deleteAuthRoute($pattern) {
        $content = json_decode(\Filebase\Filesystem::read($this->key),true);
        if($content){
            $data = $content;
            if (($key = array_search($pattern, $data)) !== false) {
                unset($data[$key]);
            }
            $content = $data;
            return \Filebase\Filesystem::write($this->key,json_encode($content,JSON_UNESCAPED_SLASHES));
        }
        return false;
    }

    /**
     * Read current registered auth
     * 
     * @return array
     */
    public function readUserAuth(){
        $user = new \Filebase\Database([
            'dir' => $this->getDataSource()
        ]);

        if ($user->has($this->username)) {
            $item = $user->get($this->username);
            $data = [
                'result' => $item->auth,
                'attribute' => [
                    'username' => $this->username
                ],
                'status' => 'success',
                'message' => 'Data found!'
            ];
        } else {
            $data = [
                'status' => 'error',
                'message' => 'User not found!'
            ];
        }
        return $data;
    }

    /**
     * Update user auth
     * 
     * @return array
     */
    public function updateUserAuth(){
        $user = new \Filebase\Database([
            'dir' => $this->getDataSource()
        ]);
        if ($user->has($this->username)) {
            $item = $user->get($this->username);
            $item->auth = $this->auth;
            if($item->save()){
                $data = [
                    'status' => 'success',
                    'message' => 'Update successful!'
                ];
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Something went wrong!'
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'User not found!'
            ];
        }
        return $data;
    }

    /**
     * Option available routes for pattern in authorization
     * 
     * @return array
     */
    public function optionAuthRoutes(){
        $result = json_decode(\Filebase\Filesystem::read($this->key),true);
        if($result){
            return [
                'result' => $result,
                'status' => 'success',
                'message' => 'Data found!'
            ];
        }
        return [
            'result' => [],
            'status' => 'error',
            'message' => 'Data not found!'
        ];
    }

}