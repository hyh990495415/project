<?php
/**
 * Created by PhpStorm.
 * User: Acid
 * Date: 2018/1/2
 * Time: 2:56
 */

namespace app\back\controller;
use think\Db;
use think\Request;
use think\Controller;

class Order extends Controller
{
    public function common(){
        return $this->fetch();
    }

    public function commonList(){
        $count = Db::name('order')
            ->alias('t1')
            ->join('tbl_product t2','t1.pid = t2.pid')
            ->where('t2.sellway','普通')
            ->count();
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',10,'intval');
        $articles = Db::name('order')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_product t3','t1.pid = t3.pid')
            ->join('tbl_address t4','t1.adid = t4.adid')
            ->join('tbl_state t5','t1.sid = t5.sid')
            ->join('tbl_payway t6','t1.pyid = t6.pyid')
            ->field('t3.pid,t3.pname,t2.uid,t2.uname,t4.address,t5.state,t6.pyname')
            //->field('t1.pid,t1.state,t1.sellway,t1.pname,t1.publishtime,t1.original,t1.price,t2.uid,t2.uname,t3.clname,t4.province,t4.city,t4.aname')
            ->where('t3.sellway','普通')
            ->limit(($page-1)*$limit,$limit)
            ->select();
       /* foreach($articles as $key=>&$value){
            if(($value['ulock'])=='0'){
                $value['ulock'] = '锁定';
            }else if(($value['ulock'])=='1'){
                $value['ulock'] = '正常';
            }
        }
        foreach($articles as $key=>&$value){
            if(($value['identification'])=='0'){
                $value['identification'] = '未完成';
            }else if(($value['identification'])=='1'){
                $value['identification'] = '已完成';
            }
        }*/
        $arr = [
            "code" => 0,
            "msg"  => '',
            "count"=> $count,
            "data" => $articles
        ];
        return $arr;
    }

    public function auctionList(){
        $count = Db::name('order')
            ->alias('t1')
            ->join('tbl_product t2','t1.pid = t2.pid')
            ->where('t2.sellway','拍卖')
            ->count();
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',10,'intval');
        $articles = Db::name('order')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_product t3','t1.pid = t3.pid')
            ->join('tbl_address t4','t1.adid = t4.adid')
            ->join('tbl_state t5','t1.sid = t5.sid')
            ->join('tbl_payway t6','t1.pyid = t6.pyid')
            ->field('t3.pid,t3.pname,t2.uid,t2.uname,t4.address,t5.state,t6.pyname')
            //->field('t1.pid,t1.state,t1.sellway,t1.pname,t1.publishtime,t1.original,t1.price,t2.uid,t2.uname,t3.clname,t4.province,t4.city,t4.aname')
            ->where('t3.sellway','拍卖')
            ->limit(($page-1)*$limit,$limit)
            ->select();
         foreach($articles as $key=>&$value){
             if(($value['state'])=='0'){
                 $value['state'] = '正常';
             }else if(($value['ulock'])=='1'){
                 $value['state'] = '失败';
             }
         }
        /*
         foreach($articles as $key=>&$value){
             if(($value['identification'])=='0'){
                 $value['identification'] = '未完成';
             }else if(($value['identification'])=='1'){
                 $value['identification'] = '已完成';
             }
         }*/
        $arr = [
            "code" => 0,
            "msg"  => '',
            "count"=> $count,
            "data" => $articles
        ];
        return $arr;
    }
}