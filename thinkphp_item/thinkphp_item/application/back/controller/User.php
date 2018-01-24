<?php
/**
 * Created by PhpStorm.
 * User: Acid
 * Date: 2017/12/27
 * Time: 16:09
 */

namespace app\back\controller;
use think\Controller;
use think\Db;
use think\Request;

class User extends Controller
{
    public function user(){
       return $this->fetch();
    }

    public function userList(){
        $count = Db::name('user')
            //->where('sellway','普通')
            ->count();
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',10,'intval');
        $articles = Db::name('user')
            ->alias('t1')
            //->join('tbl_user t2','t1.uid = t2.uid')
            //->join('tbl_class t3','t1.clid = t3.clid')
            ->join('tbl_province t4','t1.prvid = t4.prvid')
            ->join('tbl_city t5','t1.ctid = t5.ctid')
            ->join('tbl_area t6','t1.aid = t6.aid')
            //->field('t1.pid,t1.state,t1.sellway,t1.pname,t1.publishtime,t1.original,t1.price,t2.uid,t2.uname,t3.clname,t4.province,t4.city,t4.aname')
            //->where('t1.sellway','普通')
            ->limit(($page-1)*$limit,$limit)
            ->select();
        foreach($articles as $key=>&$value){
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
        }
        $arr = [
            "code" => 0,
            "msg"  => '',
            "count"=> $count,
            "data" => $articles
        ];
        return $arr;
    }
}