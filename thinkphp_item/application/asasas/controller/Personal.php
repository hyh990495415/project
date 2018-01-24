<?php
/**
 * Created by PhpStorm.
 * User: 何
 * Date: 2017/12/23
 * Time: 14:20
 */

namespace app\index\controller;
use think\Controller;
use think\Session;

class Personal extends Controller
{
    //默认跳转
    public function index()
    {
        session_start();

//        var_dump(Session::get('user.item')) ;
//         var_dump(\session('user.itme'));
//        if (Session::has('user'))
//        {
////            return $this->fetch('{:url("index/Index/index")}');
//            var_dump('123456');
//        }
//        var_dump($_SESSION['user']);

        $menu=[
           0=>['muntName'=>'个人信息','muntUrl'=>url('index/Personal/message')],
           1=>['muntName'=>'我发布的','muntUrl'=>url('index/Personal/release')],
           2=>['muntName'=>'我卖出的','muntUrl'=>url('index/Personal/sell')],
           3=>['muntName'=>'我买到的','muntUrl'=>url('index/Personal/purchase')],
           4=>['muntName'=>'我收藏的','muntUrl'=>url('index/Personal/collection')],
           5=>['muntName'=>'我拍买的','muntUrl'=>url('index/Personal/auction')]
            ];

        $this->assign('menu',$menu);
        return $this->fetch();

    }
//  个人信息
    public function message()
    {
        return $this->fetch('personal_message');
    }

//   我发布的
    public function release()
    {
        return $this->fetch('personal_release');
    }

//  我卖出的
    public function sell()
    {
        return $this->fetch('personal_sell');
    }

//  我买入的
    public function purchase()
    {
        return $this->fetch('personal_purchase');
    }

//  我收藏的
    public function collection()
    {
        return $this->fetch('personal_collection');
    }

//  我拍卖的
    public function auction()
    {
        return $this->fetch('personal_auction');
    }
}