<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 11:16
 */
namespace vendor\acl;

use yii\caching\Cache;
use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;

class Acl
{
    public $userTable = '{{%admin_user}}';

    public $roleTable = '{{%role}}';

    public $permissionTable = '{{%permission}}';

    public $roleUserTable = '{{%user_role}}';

    public $cache;

    public $db = 'db';

    public function __construct()
    {
        $this->db = Instance::ensure($this->db,Connection::className());
        if($this->cache !== null){
            $this->cache = Instance::ensure($this->cache,Cache::className());
        }
    }

    /**
     * 判断用户有没有访问权限
     * @param $userId int
     * @param $permission string
     * @param $control string
     * @return boolean true Or false
     */
    public function isAllow($userId,$control,$permission)
    {
        $query = new Query();
        $query ->from(['a'=>$this->permissionTable]);
        $query ->leftJoin(['b' =>$this->roleUserTable],'{{a}}.[[role_id]]={{b}}.[[role_id]]');
        $query ->where('a.control=:control AND a.permission=:permission AND b.user_id=:user_id',[':control'=>$control,':permission'=>$permission,':user_id'=>$userId]);

        $data = [];
        foreach($query ->all() as $row){
            $data[] = $row;
        }

        if($data){
            return true;
        }

        return false;
    }

    /**
     * 给角色赋予权限
     * @param $permission array
     * @param $roleId int
     * @return boolean
     */
    public function createPermission($permission,$roleId)
    {
       return $this->db->createCommand()->insert($this->permissionTable, [
                'control' => $permission['control'],
                'permission' => $permission['permission'],
                'role_id' => $roleId,
            ])->execute();
    }

    /**
     * 删除某个角色的权限
     * @param $control
     * @param $permission
     * @param $roleId
     * @return boolean
     */
    public function delPermission($control,$permission,$roleId)
    {
         return $this->db->createCommand()->delete($this->permissionTable,['control'=>$control,'permission'=>$permission,'role_id'=>$roleId])->execute();
    }

    /**
     * 删除一个角色的所有权限
     * @param $roleId int
     * @return boolean
     */
    public function delPermissionByRole($roleId)
    {
        return $this->db->createCommand()->delete($this->permissionTable,['role_id'=>$roleId])->execute();
    }

    /**
     * 添加一个角色
     * @param $roleName string
     * @param $aliasName string
     *@return boolean
     */
    public function addRole($roleName,$aliasName)
    {
         return $this->db->createCommand()->insert($this->roleTable, [
                'role_name' => $roleName,
                'role_alias' => $aliasName,
            ])->execute();

    }

    /**
     * 获取一个用户拥有的角色
     * @param $userId int
     * @return  array
     */
    public function getRolesByUser($userId)
    {
        $query  = new Query();
        $query->from($this->roleUserTable);
        $query->where(['user_id'=>':user_id'],[':user_id'=>$userId]);
        $query->all();

        $data = [];
        foreach($query as $val){
            $data[] = $val['role_id'];
        }

        return $data;
    }

    /**
     * 删除一个角色
     * @param $roleId int
     * @return boolean
     */
    public function removeRole($roleId)
    {
        $this->db->createCommand()->delete($this->roleTable,['id'=>$roleId])->execute();
        $this->db->createCommand()->delete($this->roleUserTable,['role_id'=>$roleId])->execute();
        return $this->db->createCommand()->delete($this->permissionTable,['role_id'=>$roleId])->execute();

    }

    /**
     * 添加一个管理员
     * @param $userName string
     * @param $password string
     * @return boolean
     */
    public function addUser($userName,$password)
    {
         return $this->db->createCommand()->insert($this->userTable,['username'=>$userName,'password'=>$password,'add_time'=>time()])->execute();
    }

    /**
     * 删除一个管理员
     * @param $userId int
     * @return boolean
     */
    public function removeUser($userId)
    {
        $this->db->createCommand()->delete($this->userTable,['id'=>$userId])->execute();

        return $this->db->createCommand()->delete($this->roleUserTable,['user_id'=>$userId])->execute();
    }

    /**
     * 为用户添加一个角色
     * @param $userId int
     * @param $roleId int
     * @return boolean
     */
    public function addUserForRole($userId,$roleId)
    {
         return $this->db->createCommand()->insert($this->roleUserTable, [
                'user_id' => $userId,
                'role_id' => $roleId,
            ])->execute();
    }

    /**
     * 删除一个用户的角色
     * @param $userId int
     * @param $roleId int
     * @return  boolean
     */
    public function removeUserForRole($userId,$roleId)
    {
        return $this->db->createCommand()->delete($this->roleUserTable,['user_id'=>$userId,'role_id'=>$roleId])->execute();
    }

}
