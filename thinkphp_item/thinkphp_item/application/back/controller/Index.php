<?php
/**
 * Created by PhpStorm.
 * User: Acid
 * Date: 2017/12/22
 * Time: 14:26
 */

namespace app\back\controller;


use think\Controller;
use think\Db;
use think\Request;
class Index extends Controller
{
    public function index(){
        return $this->fetch();
        /*if(captcha_check())*/
    }

    public function menu(){
        $resArr = Db::name('menu')->select();
        //var_dump($resArr);
        foreach($resArr as $key=>&$value){
            if(!empty($value['url'])){
                $value['url'] = url($value['url']);
            }
        }
        //print_r($resArr);
        return  $resArr;
    }

    public function getComSelect(){
        $areaType = Request::instance()->param('area');
        $classTyle = Request::instance()->param('ify');
        if($classTyle=='g1' && $areaType == 'gp'){

            $areaResArr = Db::name('province')
                ->select();

            $classResArr = Db::name('class')
                ->where('fid',0)
                ->select();
            $resArr=[
                  'area' => $areaResArr,
                  'class'=> $classResArr
            ];
            return $resArr;

        }else if($classTyle=='g2'){
            $fId = Request::instance()->param('y');
            $resArr = Db::name('class')
                ->where('fid',$fId)
                ->select();
            return $resArr;
        }else if ($areaType == 'gc') {
            $provinceId = Request::instance()->param('p');
            $resArr = Db::name('city')
                ->where('prvid',$provinceId)
                ->select();
            return $resArr;
        } else if($areaType == 'ga'){
            $cityId = Request::instance()->param('c');
            $resArr = Db::name('area')
                ->where('ctid',$cityId)
                ->select();
            return $resArr;
        }
    }
}