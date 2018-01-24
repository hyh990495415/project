<?php
/**
 * Created by PhpStorm.
 * User: 何
 * Date: 2018/1/3
 * Time: 9:29
 */

namespace app\index\controller;

use think\Controller;
use think\Session;

class Auto  extends Controller
{
    function _initialize()
    {
        if (!Session::has('user'))
        {
            $this->error('1002','index/index/index','',1);
        }
    }
}