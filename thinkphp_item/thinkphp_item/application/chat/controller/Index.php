<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/30
 * Time: 16:30
 */
namespace app\chat\controller;
use \think\Controller;
use \think\Db;
use \think\Session;
class index extends controller
{
    public function _initialize()
    {
        if( empty(cookie('uid')) ){
            $this->redirect( url('login/index'), 302 );
        }
    }
    //个人信息的显示
    function index()
    {
        
        $user=Session::get('user');
        $mine=Db::table('tbl_user')->fetchSql(false)->where('uid',cookie('uid'))->find();
        $friend=Db::table('tbl_chatman')->where('account',$user['nowaccount'])->select();
        $this->assign(['friend'=>$friend]);
//        $mine=['id'=>'lili','username'=>'hf1111','avatar'=>'this','head'=>'online'];
        $this->assign(['uinfo'=>$mine]);
        return $this->fetch('chat');
    }
    function getfriend()
    {

        $friend=['status'=>1,
                "msg"=>'OK',
                "data"=>[
                    "name"=>'在线好友',
                    'nums'=>2,
                    'id'=>1,
                    'item'=>[['id'=>'12222','name'=>'liliy','face'=>'http://tp1.sinaimg.cn/1571889140/180/40030060651/1']]
                 ]
                ];
        return json($friend);
    }

}