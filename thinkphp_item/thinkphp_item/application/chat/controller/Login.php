<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/30
 * Time: 16:25
 */
namespace app\chat\controller;
use \think\Controller;
use \think\Session;
class Login extends controller
{
    //页面跳转登入
    function dologin()
    {
        
        if(Session::has('user'))
        {
            $userinfo=Session::get('user');
            cookie( 'uid', $userinfo['nowuid'] );
            cookie( 'username', $userinfo['nowuname'] );
            cookie( 'account', $userinfo['nowaccount'] );
            //cookie( 'head', $userinfo['nowhead'] );
            $this->redirect(url('index/index'));
        }else{
            $this->error('请先登入');
        }
    }
}