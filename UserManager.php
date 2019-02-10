<?php
namespace modules\user;
use \aalfiann\Filebase;
/**
 * User Manager class
 *
 * @package    swift-user
 * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
 * @copyright  Copyright (c) 2019 M ABD AZIZ ALFIAN
 * @license    https://github.com/aalfiann/swift-modules-user/blob/master/LICENSE.md  MIT License
 */

class UserManager extends UserHelper {

    /**
     * @var limitData is to prevent the data load
     */
    protected $limitData=1000;

    /**
     * @var cache if set to true then will query will use data from cache 
     */
    protected $cache=true;

    /**
     * @var cache_expires is time for cache will expired
     */
    protected $cache_expires=60;

    /**
     * Determine is limit already allowed
     */
    private function isLimitAllowed($number){
        return (($this->limitData>=$number)?true:false);
    }

    /**
     * Add User
     * 
     * @return array
     */
    public function add() {

        if($this->password != $this->password2) {
            return [
                'status' => 'error',
                'message' => 'Password is not match!'
            ];
        }

        $user = new \Filebase\Database([
            'dir' => $this->getDataSource()
        ]);

        if (!$user->has($this->username)) {
            if(!$this->isEmailRegistered()) {
                $item = $user->get($this->username);
                $item->created_at = date('Y-m-d H:i:s');
                $item->username = $this->username;
                $item->email = $this->email;
                $item->hash = $this->hashPassword($this->username,$this->password);
                $item->status = 'active';
                $item->role = 'user';
                if($item->save()){
                    $data = [
                        'status' => 'success',
                        'message' => 'Register user successful!'
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Process saving failed, please try again!'
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Email address already taken!'
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Username is already taken!'
            ];
        }
        return $data;
    }

    /**
     * Update User
     * 
     * @return array
     */
    public function update() {
        $user = new \Filebase\Database([
            'dir' => $this->getDataSource()
        ]);

        if ($user->has($this->username)) {
            if(!$this->isEmailRegistered($this->username)) {
                $item = $user->get($this->username);
                $item->firstname = $this->firstname;
                $item->lastname = $this->lastname;
                $item->address = $this->address;
                $item->city = $this->city;
                $item->country = $this->country;
                $item->postal = $this->postal;
                $item->avatar = $this->avatar;
                $item->background_image = $this->background_image;
                $item->about = $this->about;
                $item->updated_at = date('Y-m-d H:i:s');
                $item->updated_by = $this->updated_by;
                if($item->save()){
                    $data = [
                        'status' => 'success',
                        'message' => 'Update User successful!'
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Process failed to update, Please try again later!'
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Email is already used by other user!'
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Can\'t update, User is not found!'
            ];
        }
        return $data;
    }

    /**
     * Update User As Admin means have full options, except Auth and Password
     * 
     * @return array
     */
    public function updateAsAdmin() {
        $user = new \Filebase\Database([
            'dir' => $this->getDataSource()
        ]);

        if ($user->has($this->username)) {
            if(!$this->isEmailRegistered($this->username)) {
                $item = $user->get($this->username);
                $item->firstname = $this->firstname;
                $item->lastname = $this->lastname;
                $item->address = $this->address;
                $item->city = $this->city;
                $item->country = $this->country;
                $item->postal = $this->postal;
                $item->avatar = $this->avatar;
                $item->background_image = $this->background_image;
                $item->about = $this->about;
                $item->updated_at = date('Y-m-d H:i:s');
                $item->updated_by = $this->updated_by;
                $item->role = $this->role;
                $item->status = $this->status;
                if($item->save()){
                    $data = [
                        'status' => 'success',
                        'message' => 'Update User successful!'
                    ];
                } else {
                    $data = [
                        'status' => 'error',
                        'message' => 'Process failed to update, Please try again later!'
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Email is already used by other user!'
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Can\'t update, User is not found!'
            ];
        }
        return $data;
    }

    /**
     * Delete User
     * 
     * @return array
     */
    public function delete() {
        $user = new \Filebase\Database([
            'dir' => $this->getDataSource()
        ]);

        if ($user->has($this->username)) {
            $item = $user->get($this->username);
            if($item->delete()){
                $data = [
                    'status' => 'success',
                    'message' => 'Delete User successful!'
                ];
            } else {
                $data = [
                    'status' => 'error',
                    'message' => 'Process failed to delete, Please try again later!'
                ];
            }
        } else {
            $data = [
                'status' => 'error',
                'message' => 'Can\'t delete, User is not found!'
            ];
        }
        return $data;
    }

    /**
     * Index Data User
     * 
     * @return array
     */
    public function index() {
        $search = $this->search;
        $page = $this->page;
        $itemperpage = $this->itemperpage;
        $offset = (((($page-1)*$itemperpage) <= 0)?0:(($page-1)*$itemperpage));

        //Check is limit allowed
        if(!$this->isLimitAllowed($itemperpage)) {
            return [
                'status' => 'error',
                'message' => 'Too many data to display!'
            ];
        }

        $user = new \Filebase\Database([
            'dir' => $this->getDataSource(),
            'cache' => $this->cache,
            'cache_expires' => $this->cache_expires
        ]);

        $columns = ['username','email','role','status','created_at','updated_at','avatar'];

        // total records
        $total_records = $user->query()->select($columns)
            ->where('username','LIKE',$search)
            ->orWhere('email','LIKE',$search)
            ->orWhere('status','LIKE',$search)
            ->count();

        // total pages
        $total_pages = ceil($total_records/$itemperpage);
        
        // List for pagination
        $list = $user->query()->select($columns)
            ->where('username','LIKE',$search)
            ->orWhere('email','LIKE',$search)
            ->orWhere('status','LIKE',$search)
            ->limit($itemperpage,$offset)
            ->orderBy('created_at','DESC');

        // total items
        $total_items = $list->count();

        if(!empty($list->results())){
            return [
                'result' => $list->results(),
                'status' => 'success',
                'message' => 'Data found!',
                'metadata' => [
                    'record_total' => $total_records,
                    'record_count' => $total_items,
                    'number_item_first' => (int)((($page-1)*$itemperpage)+1),
                    'number_item_last' => (int)((($page-1)*$itemperpage)+$total_items),
                    'itemperpage' => (int)$itemperpage,
                    'page_now' => (int)$page,
                    'page_total' => $total_pages
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Data not found!'
            ];
        }
    }

    /**
     * Index for jQuery DataTables serverside only
     * 
     * @return array
     */
    public function indexDatatables() {
        $search = $this->search;
        $length = $this->length;
        $offset = $this->start;
        $draw = $this->draw;
        $column = $this->column;
        $sort = $this->sort;

        $user = new \Filebase\Database([
            'dir' => $this->getDataSource(),
            'cache' => $this->cache,
            'cache_expires' => $this->cache_expires
        ]);

        $columns = ['username','email','role','status','created_at','updated_at','updated_by','avatar'];

        // total records
        $total_records = $user->query()->select($columns)
            ->where('username','LIKE',$search)
            ->orWhere('email','LIKE',$search)
            ->orWhere('status','LIKE',$search)
            ->count();

        // total pages
        $total_pages = ceil($total_records/$length);

        // List for pagination
        $list = $user->query()->select($columns)
            ->where('username','LIKE',$search)
            ->orWhere('email','LIKE',$search)
            ->orWhere('status','LIKE',$search)
            ->limit($length,$offset)
            ->orderBy($columns[$column],strtoupper($sort));

        // total items
        $total_items = $list->count();

        if(!empty($list->results())){
            return [
                'draw' => (int)$this->draw,
                'recordsTotal' => $total_records,
                'recordsFiltered' => $total_records,
                'data' => $list->results(),
                'status' => 'success',
                'message' => 'Data found!',
                'metadata' => [
                    'start' => (int)$offset+1,
                    'number_item_first' => (int)((($offset)*$length)+1),
                    'number_item_last' => (int)((($offset)*$length)+$total_items),
                    'length' => (int)$length,
                    'page_total' => $total_pages
                ]
            ];
        } else {
            return [
                'draw' => (int)$this->draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'status' => 'error',
                'message' => 'Data not found!'
            ];
        }
    }

    /**
     * Read Single Data User
     * 
     * @return array
     */
    public function read() {
        $user = new \Filebase\Database([
            'dir' => $this->getDataSource()
        ]);

        if ($user->has($this->username)) {
            $item = $user->get($this->username);
            $data = $item->toArray();
            unset($data['hash']); // remove hashed password
            $data['created_at'] = $item->createdAt();
            $data['updated_at'] = $item->updatedAt(); 
            return [
                'result' => $data,
                'status' => 'success',
                'message' => 'Data found!'
            ];
        }
        return [
            'status' => 'error',
            'message' => 'Data not found!'
        ];
    }

    /**
     * Show data option for Manage User
     * 
     * @return array
     */
    public function optionStatus(){
        return [
            'active',
            'suspended'
        ];
    }

    /**
     * Show data option for User Role
     */
    public function optionRole(){
        return [
            'admin',
            'user'
        ];
    }
}