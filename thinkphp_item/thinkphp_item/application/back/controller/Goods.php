<?php
/**
 * Created by PhpStorm.
 * User: Acid
 * Date: 2017/12/23
 * Time: 11:02
 */

namespace app\back\controller;


use think\Controller;
use think\Db;
use think\Request;
class Goods extends Controller
{
    //普通商品 拍卖商品
    public function common(){
       return $this->fetch();
    }

    public function auction(){
        return $this->fetch();
    }

    public function order(){
        return $this->fetch();
    }

    public function commonList(){
        $count = Db::name('product')
            ->where('sellway','普通')
            ->count();
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',10,'intval');
        $articles = Db::name('product')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_class t3','t1.clid = t3.clid')
            ->join('tbl_province t4','t1.prvid = t4.prvid')
            ->join('tbl_city t5','t1.ctid = t5.ctid')
            ->join('tbl_area t6','t1.aid = t6.aid')
            //->field('t1.pid,t1.state,t1.sellway,t1.pname,t1.publishtime,t1.original,t1.price,t2.uid,t2.uname,t3.clname,t4.province,t4.city,t4.aname')
            ->where('t1.sellway','普通')
            ->limit(($page-1)*$limit,$limit)
            ->select();
        foreach($articles as $key=>&$value){
            if(($value['state'])=='0'){
                $value['state'] = '上架';
            }else if(($value['state'])=='1'){
                $value['state'] = '下架';
            }else if(($value['state'])=='2'){
                $value['state'] = '审核';
            }
        }

        //0上架 1下架 2审核

        $arr = [
            "code" => 0,
            "msg"  => '',
            "count"=> $count,
            "data" => $articles
        ];
        return $arr;
    }

    public function auctionList(){
        $count = Db::name('product')
            ->where('sellway','拍卖')
            ->count();
        $page = $this->request->param('page',1,'intval');
        $limit = $this->request->param('limit',10,'intval');
        $articles = Db::name('product')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_class t3','t1.clid = t3.clid')
            ->join('tbl_province t4','t1.prvid = t4.prvid')
            ->join('tbl_city t5','t1.ctid = t5.ctid')
            ->join('tbl_area t6','t1.aid = t6.aid')
            ->join('tbl_auction t7','t7.pid = t1.pid')
            ->field(
                '
                t1.pid,
                t1.pname,
                t1.detail,
                t1.freight,
                t1.publishtime,
                t2.uid,
                t2.uname,
                t3.clname,
                t4.province,
                t5.city,
                t6.aname,
                t7.sprice,
                t7.margin,
                t7.raise,
                t7.stime,
                t7.etime,
                t7.state
                '
            )
            ->where('t1.sellway','拍卖')
            ->limit(($page-1)*$limit,$limit)
            ->select();
        foreach($articles as $key=>&$value){
            if(($value['state'])=='0'){
                $value['state'] = '上架';
            }else if(($value['state'])=='1'){
                $value['state'] = '下架';
            }else if(($value['state'])=='2'){
                $value['state'] = '审核';
            }
        }

        //0上架 1下架 2审核

        $arr = [
            "code" => 0,
            "msg"  => '',
            "count"=> $count,
            "data" => $articles
        ];
        return $arr;
    }

    public function commonDetails(){
        $gid = Request::instance()->param('id');

       /* $resArr = Db::name('product')
            ->where('pid',$gid)
            ->select();*/

        $resArr = Db::name('product')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_class t3','t1.clid = t3.clid')
            ->join('tbl_province t4','t1.prvid = t4.prvid')
            ->join('tbl_city t5','t1.ctid = t5.ctid')
            ->join('tbl_area t6','t1.aid = t6.aid')
            ->where('t1.pid',$gid)
            ->select();


        $this->assign('like',$resArr);

        return $this->fetch();
    }


    public function auctionDetails(){
        $gid = Request::instance()->param('id');

        /* $resArr = Db::name('product')
             ->where('pid',$gid)
             ->select();*/

        /*$resArr = Db::name('product')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_class t3','t1.clid = t3.clid')
            ->join('tbl_province t4','t1.prvid = t4.prvid')
            ->join('tbl_city t5','t1.ctid = t5.ctid')
            ->join('tbl_area t6','t1.aid = t6.aid')
            ->where('t1.pid',$gid)
            ->select();*/

        $resArr = Db::name('product')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_class t3','t1.clid = t3.clid')
            ->join('tbl_province t4','t1.prvid = t4.prvid')
            ->join('tbl_city t5','t1.ctid = t5.ctid')
            ->join('tbl_area t6','t1.aid = t6.aid')
            ->join('tbl_auction t7','t7.pid = t1.pid')
            ->field(
                '
                t1.pid,
                t1.pname,
                t1.detail,
                t1.freight,
                t1.publishtime,
                t2.uid,
                t2.uname,
                t3.clname,
                t4.province,
                t5.city,
                t6.aname,
                t7.*
                '
            )
            ->where('t1.pid',$gid)
            ->select();


        $this->assign('like',$resArr);
        //print_r($this);
        return $this->fetch();
    }

    public function auctionRecord(){
        $gid = Request::instance()->param('id');

        $resArr = Db::name('product')
            ->alias('t1')
            ->join('tbl_user t2','t1.uid = t2.uid')
            ->join('tbl_class t3','t1.clid = t3.clid')
            ->join('tbl_province t4','t1.prvid = t4.prvid')
            ->join('tbl_city t5','t1.ctid = t5.ctid')
            ->join('tbl_area t6','t1.aid = t6.aid')
            ->join('tbl_auction t7','t7.pid = t1.pid')
            ->field(
                '
                t1.pid,
                t1.pname,
                t1.detail,
                t1.freight,
                t1.publishtime,
                t2.uid,
                t2.uname,
                t3.clname,
                t4.province,
                t5.city,
                t6.aname,
                t7.*
                '
            )
            ->where('t1.pid',$gid)
            ->select();


        $this->assign('like',$resArr);
        //print_r($this);
        return $this->fetch();
    }
}