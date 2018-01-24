<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/12/25
 * Time: 10:16
 */
namespace app\goods\controller;
use \think\Controller;
use \think\Request;
use \think\Db;
use \think\Cookie;
use \think\Cache;
use \think\Session;
class goods extends controller
{

    function goodsmess()
    {
        if(input('?param.gid'))
        {
            $gid=input('param.gid');
            $res=Db::table('tbl_product')
                ->fetchSql(false)
                ->alias('a')
                ->join('tbl_image b','a.pid=b.pid','LEFT')
                ->where('a.pid',$gid)
                ->find();
            echo  json_encode($res);
        }
    }
    //商品留言显示
    function review()
    {
        if(input('?get.gid'))
        {
            $gid=input('get.gid');
         $goodsreview=Cache::get('goodsid'.$gid,'');
          if(!$goodsreview)
          {
              $goodsreview=Db::table('tbl_comments')
            ->field("a.cmid,a.content,a.uid,a.pid,a.comments_to,b.uname,a.to_cmid")
            ->fetchSql(false)
            ->alias('a')
            ->join('tbl_user b','a.uid=b.uid')
            ->where('a.pid',$gid)
            ->select();
           Cache::set('goodsid'.$gid,$goodsreview,30);
         }
            echo json_encode($goodsreview);
        }
    }
    //收藏
    function collect()
    {
        if(input('?get.gid'))
        {
            //session_start();
            if(Session::has('user'))
            {
                $gid=input('get.gid');
                $user=Session::get('user');
                $uid=$user['nowuid'];
                $gid=input('get.gid');
                $isExist=Db::table('tbl_collection')
                    ->where('uid',$uid)
                    ->where('pid',$gid)
                    ->find();
                //                判断是否收藏过
                if(empty($isExist))
                {
                    /*   N   */
                    $collect=Db::table('tbl_product')
                        ->field('collect')
                        ->where('pid',$gid)
                        ->where('uid',$uid)
                        ->find();
                    $addzan=$collect['collect']+1;
                    $insertdata=['uid'=>$uid,'pid'=>$gid];
                    Db::startTrans();
                    try{
                        Db::table('tbl_collection')->insert($insertdata);
                        Db::table('tbl_product')->where('pid',$gid)->update(['collect'=>$addzan]);
                        Db::commit();  // 提交事
                        $res=['code'=>'10006','errorM'=>'收藏成功~'];
                    }
                    catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        $res=['code'=>'10004','errorM'=>'收藏失败~'];
                    }


                }else{
                    /*    Y  */
                    $res=['code'=>'10003','errorM'=>'已经收藏过了~'];
                }

            }else{
                $res=['code'=>'10001','errorM'=>'还未登入，请先登入'];
            }
            //$res=['code'=>'10001','errorM'=>'还未登入，请先登入'];
           echo json_encode($res);
        }
    }

    //留言的留言
    function  reviewto(){
        // session_start();

        if(Session::has('user'))
        {
            $user=Session::get('user');
            $uid=$user['nowuid'];
            $uname=$user['nowuname'];
            $cimid=input('param.cmid');
            $text=input('param.text');
            $pig=input('param.pid');
            $data=['cmid'=>'','content'=>$text,'uid'=>$uid,'pid'=>$pig,'comments_to'=>0,'to_cmid'=>$cimid];
            $insert_res=Db::table('tbl_comments')
                ->insertGetId($data);
            if($insert_res)
            {
                $datares=['cmid'=>$insert_res,'content'=>$text,'uid'=>$uid,'pid'=>$pig,'comments_to'=>0,'to_cmid'=>$cimid,'uname'=>$uname];
                $res=['code'=>'10002','errorM'=>'留言成功！！！','datar'=>$datares];


            }
            else{
                $res=['code'=>'10002','errorM'=>'留言失败！！！'];
            }
        }
        else
        {
            $res=['code'=>'10001','errorM'=>'还未登入，请先登入'];
        }
        echo json_encode($res);
    }
    //赞
    function zan()
    {
        if(input('?get.gid'))
        {
            //session_start();

            if(Session::has('user'))
            {
                $user=Session::get('user');
                $uid=$user['nowuid'];
                $gid=input('get.gid');
                $isExist=Db::table('tbl_praise')
                    ->where('uid',$uid)
                    ->where('pid',$gid)
                    ->find();
//                判断是否点赞过
                if(empty($isExist))
                {
                    /*   N   */
                    $zan=Db::table('tbl_product')
                        ->field('num')
                        ->where('pid',$gid)
                        ->find();
                    $addzan=$zan['num']+1;
                    $insertdata=['psid'=>'','uid'=>$uid,'pid'=>$gid];
                    Db::startTrans();
                    try{
                        Db::table('tbl_praise')->insert($insertdata);
                        Db::table('tbl_product')->where('pid',$gid)->update(['num'=>$addzan]);
                        Db::commit();  // 提交事
                        $res=['code'=>'10006','errorM'=>'点赞成功！！！'];
                    }
                    catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                        $res=['code'=>'10003','errorM'=>'点赞失败！！！'];
                        }

                }else{
                    /*    Y  */
                    $res=['code'=>'10003','errorM'=>'已经赞过了~'];
                }


            }else{
                $res=['code'=>'10001','errorM'=>'还未登入，请先登入'];
            }
            //$res=['code'=>'10001','errorM'=>'还未登入，请先登入'];
            echo json_encode($res);
        }

    }

    function ready(){
        //session_start();

        if(Session::has('user'))
        {
            if(input('?get.gid'))
            {
                $user=Session::get('user');
                $uid=$user['nowuid'];
                $gid=input('get.gid');
                $resTate= Db::table('tbl_product')  //商品信息
                ->field('state')
                    ->where('pid',$gid)
                    ->find();
                if($resTate['state']!=0)
                {
                    $res=['code'=>1003,'mess'=>'商品以下架'];

                }
                else{
                    $res=['code'=>1000,'mess'=>'成功'];

                }
            }else{
                $res=['code'=>1002,'mess'=>'系统错误'];
                echo -2;//系统错误
            }
        }
        else{
            $res=['code'=>1001,'mess'=>'请先登入'];

        }
        echo json_encode($res);
    }

    //给商品留言
    function liuy()
    {
       // session_start();
        if(Session::has('user'))
        {
            $user=Session::get('user');
            $uname=$user['nowuname'];
            $uid=$user['nowuid'];
            $gid=input('get.gid');
            $text=input('param.text');

            $data=['cmid'=>'','content'=>$text,'uid'=>$uid,'pid'=>$gid,'comments_to'=>0,'to_cmid'=>0];
            $insert_res=Db::table('tbl_comments')
                ->insertGetId($data);

            if($insert_res)
            {
                $datares=['cmid'=>$insert_res,'content'=>$text,'uid'=>$uid,'pid'=>$gid,'comments_to'=>0,'to_cmid'=>0,'uname'=>$uname];
                $res=['code'=>'10002','errorM'=>'留言成功！！！','datar'=>$datares];

            }
            else{
                $res=['code'=>'10002','errorM'=>'留言失败！！！'];
            }
        }
        else{
            $res=['code'=>'10001','errorM'=>'还未登入,请先登入'];
        }
        echo  json_encode($res);
    }
    /*     收货地址   */
    function personaddr(){
       // session_start();
        if(Session::has('user'))
        {
            $user=Session::get('user');
            $uid=$user['nowuid'];
           // var_dump($uid);exit;
            $gid=input('get.gid');
            $resadd=Db::table('tbl_address')
                ->fetchSql(false)
                ->alias('a')
                ->join('tbl_province b','a.prvid = b.prvid')
                ->join('tbl_city c','a.ctid=c.ctid')
                ->join('tbl_area d','a.aid=d.aid')
                ->where('a.uid',$uid)
                ->select();
            $res=Db::table('tbl_product')
                ->fetchSql(false)
                ->alias('a')
                ->join('tbl_image b','a.pid=b.pid')
                ->where('a.pid',$gid)
                ->find();
            echo json_encode([$resadd,$res]);
          // var_dump($resadd);exit;
        }
        else{
            $this->error('error');
        }
    }

    /*     生成订单号   */
    function createOr()
    {
        //session_start();
        if(Session::has('user'))
        {
            /*   订单生成   UID, pid, adid, price , freight,sid=待支付 */
            $user=Session::get('user');
            $uid=$user['nowuid']; //用户id
            $pid=input('param.pid');//商品id
            $adid=input('param.adid');//收货地址id
            $price=input('param.price'); //
            $freight=input('param.freight');
           // $iss=input('?param.price');
            $resTate= Db::table('tbl_product')  //商品信息
            ->field('state')
                ->where('pid',$pid)
                ->find();
           // var_dump($iss);exit;
            if($resTate['state']!=0)
            {
                $this->success('商品已出售', 'goods/index/goods');
            }else{
                $ds=date('Y-m-d H:i:s',time());




                $data=['oid'=>'','uid'=>$uid,'pid'=>$pid,'adid'=>$adid,'price'=>$price,'freight'=>$freight,'sid'=>1,'pyid'=>1,'order_number'=>['exp','order_seq()'],'ordertime'=>$ds];
                $resoid=Db::table('tbl_order')->fetchSql(false)->insertGetId($data);
                if($resoid)
                {
                    $res=['code'=>'10003','oid'=>$resoid];
                }else{
                    $res=['code'=>'10004','errorM'=>'订单生成失败！！！'];
                }
            }

        }else{
          $res=['code'=>'10002','errorM'=>'登入信息错误！！！'];

        }
        echo json_encode($res);
    }

    /*     去支付             */
    function pay()
    {
        //session_start();
        if(Session::has('user'))
        {
            if(input('?param.orderid'))
            {
                $user=Session::get('user');

                $account=$user['nowaccount'];
                $orderid=input('param.orderid');
                $psw=input('param.paypwd');
                $saltmm=Db::table('tbl_user')
                        ->field('csalt')
                        ->where('account',$account)
                        ->find();
                $salt=$saltmm['csalt'];
                $passwordMd5=md5($psw);
                $passwordMd5Salt=crypt($passwordMd5,$salt);
              //  echo $passwordMd5Salt;exit;
                $res=Db::table('tbl_user')  //用户信息查找
                    ->fetchSql(false)
                    ->where('account',$account)
                    ->where('payPwd',md5($psw))
                    ->find();
               // var_dump($res) ;exit;
                $zhanghu=$res['money'];

                //支付密码验证
                if(empty($res))
                {
                    $this->error('密码错误,请重新输入！');
                }
                else{
                    $res2=Db::table('tbl_order') //订单信息查看
                        ->where('oid',$orderid)
                        ->find();
                    $orderstate=$res2['sid'];

                    //订单状态验证
                    if($orderstate!=1)
                    {
                        $this->success('交易已结束', 'goods/index/goods');
                    }
                    else
                    {
                        $pid=$res2['pid'];
                        $price=$res2['price'];
                        $freight=$res2['freight'];
                        $allpay=$price+$freight;//总花费
                        $resTate= Db::table('tbl_product')  //商品信息
                            ->field('state')
                            ->where('pid',$pid)
                            ->find();

                        /*  商品是否出售了*/
                        if($resTate['state']!=0)
                        {
                            $res6=Db::table('tbl_order')    //订单
                                ->where('oid',$orderid)
                                ->update(['sid'=>7]);
                            $this->success('商品以出售', 'goods/index/goods');
                        }
                        else{
                            /*   支付 */

                            /*     账户余额  */
                            if($res['money']>=$allpay)
                            {

                                Db::startTrans();
                                try{
                                    Db::table('tbl_product') //商品
                                        ->fetchSql(false)
                                        ->where('pid',$pid)
                                        ->update(['state'=>1]);

                                    Db::table('tbl_user')
                                        ->where('account',$account)
                                        ->update(['money'=>($zhanghu-$allpay)]);  //用户

                                    Db::table('tbl_order')    //订单
                                        ->where('oid',$orderid)
                                        ->update(['sid'=>2]);
                                    Db::commit();
                                }catch (\Exception $e){
                                    Db::rollback();
                                    $this->error('支付失败！！！');
                                }
                               // echo $res4,$res5,$res6;echo '成功';exit;
                                $this->success('支付成功', 'goods/index/goods');
                            }
                            else{
                                $this->error('账户余额不足！');
                            }
                        }
                    }
                }
            }else{
                $this->success('出错了', 'goods/index/goods');

            }
        }else{
            $this->success('登入信息错误', 'goods/index/goods');
        }
    }

    function wechatpay(){
        if(Session::has('user'))
        {
            echo 1;
        }
        else{
            $this->error('登入信息错误','index/index/index','',1);
        }
    }
}