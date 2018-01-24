<?php
namespace app\goods\controller;
use \think\Controller;
use \think\Request;
use \think\Db;
use \think\Cookie;
use \think\Session;
class Index extends controller
{

	function goods()
    {
        //$user=['nowuid'=>1,'nowaccount'=>'liliy','nowuname'=>'liliy','nowhead'=>'sdads'];
        //Session::set('user',$user);
		 if(Cookie::has('gid'))
        {
            $gid = Cookie::get('gid');
			Cookie::set('gid',$gid,3600);
        }
        if(input('?param.gid'))
        {
            $gid=input('param.gid');
			 Cookie::set('gid',$gid,3600);
			
        }
       // $gid=2;
        Cookie::set('gid',$gid,3600);
        $goods=Db::table('tbl_product')
            ->fetchSql(false)
            ->field('uid,image,ctid')
            ->where('pid',$gid)
            ->find();
			
        $uid=$goods['uid'];
		$ctid=$goods['ctid'];
		$city=Db::table('tbl_city')
				->where('ctid',$ctid)
				->find();
		$this->assign('city',$city['city']);
		$imagemain=$goods['image'];
		//echo $imagemain;exit;
		$imagemain=$this->assign('image',$imagemain);
		// $this->assign('image',$img[0]['imgurl']);
        $maiJ=Db::table('tbl_user')
            ->fetchSql(false)
            ->where('uid',$uid)
            ->find();
        $uname=$maiJ['uname'];
        $this->assign('uname',$uname);
        $head=$maiJ['head'];
        $this->assign('head',$head);
        $identification=$maiJ['identification'];
        if($identification)
        {
            $yanz='<span>实人验证通过</span>';
        }else{
            $yanz='<span>未实人验证</span>';
        }
        //实人验证
        $this->assign('yanz',$yanz);
        $sex=$maiJ['sex'];
        //卖家性别
        $this->assign('sex',$sex);
        //卖家id
        $chaturl=url('chat/login/dologin',['fuid'=>$uid]);//路径绚烂
        $this->assign('chaturl',$chaturl);
        $this->assign('uid',$uid);
        //卖家注册时间
        $resgister=$maiJ['registertime'];

        $time="to_days(now()) - to_days('{$resgister}')";
        $sql="select  (to_days(now()) - to_days('{$resgister}') );";
        $var =Db::query( $sql);
        $today=0;
        foreach($var[0] as $value)
        {
            $today=$value;
        }


        //卖家注册
        $this->assign('today',$today);
        $salecount=Db::table('tbl_product')
            ->alias('a')
            ->join('tbl_order b','a.pid=b.pid')
            ->where('a.uid',$uid)
            ->where('b.sid',5)
            ->whereor('b.sid',6)
            ->count();
        //卖家共卖出几件商品
        $this->assign('salecount',$salecount);


        /*  卖家其他闲置  */
        $othergoods=Db::table('tbl_product')
            ->fetchSql(false)
            ->alias('a')
            ->join('tbl_image b','a.pid=b.pid','left')
            ->where('a.uid',$uid)
            ->where('a.pid','<>',$gid)
            ->group('a.pid')
			->limit(10)
            ->select();
        $count=sizeof($othergoods);
		
        $this->assign('othergoods',$othergoods);
        //卖家共卖出几件商品
        $this->assign('count',$count);

        //商品图片
        $img = Db::table('tbl_image')
            ->fetchSql(false)
            ->alias('a')
            ->where('a.pid', $gid)
            ->select();
       // var_dump( $img[0]['imgurl']);exit;
      
        $this->assign('img', $img);
        // 留言

        return $this->fetch('goods');
	}
    /*       订单页面     */
    function pay()
    {
       // session_start();
       //isset($_SESSION['user'])
        if(Session::has('user')) {
			 //$user=$_SESSION['user'];
			 $user=Session::get('user');
			 // var_dump($user);exit;
             $uid=$user['nowuid'];
            if (input('?param.gid')) {
                $gid = input('param.gid');

            }
            if (Cookie::has('gid')) {
                $gid = Cookie::get('gid');
            }
            if (!isset($gid)) {
                $this->redirect('goods/index/goods');
            }

            $res = Db::table('tbl_product')
                ->fetchSql(false)
                ->alias('a')
                ->where('a.pid', $gid)
                ->find();
            //var_dump($res) ;exit;
            if($res['uid']==$uid)
            {
                $this->success('不能购买自己的商品', 'goods/index/goods');
            }
            if ($res['state'] != 0) {
                $this->success('商品以出售', 'goods/index/goods');
            } else
            {
                $this->assign('g_img',$res['image']);
                $this->assign('pname',$res['pname']);
                $this->assign('price',$res['price']);
                $this->assign('freight',$res['freight']);
                return $this->fetch('pay');
            }
            //var_dump($res);exit;

        }
        else{
            $this->error('还未登入请先登入');
        }

    }
//      /*       支付页面      */
    function paymoney(){
        //session_start();
        if(Session::has('user'))
        {
            if(input('?get.orderid'))
            {
                $orderid=input('get.orderid');
                $res=Db::table('tbl_order')
                    ->fetchSql(false)
                    ->field('a.oid,b.image,a.price,a.freight,b.pname,c.address,c.receive,c.cell,d.province,f.city,e.aname')
                    ->alias('a')
                    ->join('tbl_product b','a.pid=b.pid')
                    ->join('tbl_address c','a.adid=c.adid')
                    ->join('tbl_province d','c.prvid=d.prvid')
                    ->join('tbl_city f','c.ctid=f.ctid')
                    ->join(' tbl_area e','c.aid = e.aid')
                    ->where('a.oid',$orderid)
                    ->find();
               // var_dump($res) ;exit;
                $price=$res['price'];
                $freight=$res['freight'];
                $pname=$res['pname'];
                $image=$res['image'];
                $address=$res['address'];
                $receive=$res['receive'];
                $cell=$res['cell'];
                $province=$res['province'];
                $city=$res['city'];
                $aname=$res['aname'];
                //微信在线支付
                $urlWechatPay=url('goods/goods/wechatpay',['orderid'=>$orderid]);
                $this->assign('wechatpay',$urlWechatPay);
                //普通的支付方式
                $urlp=url('goods/goods/pay',['orderid'=>$orderid]);
                $this->assign('url',$urlp);
                $this->assign('price',$price);
                $this->assign('freight',$freight);
                $this->assign('pname',$pname);
                $this->assign('aname',$aname);
                $this->assign('image',$image);
                $this->assign('address',$address);
                $this->assign('receive',$receive);
                $this->assign('cell',$cell);
                $this->assign('province',$province);
                $this->assign('city',$city);
                $this->assign('allcount',$price+$freight);
                return $this->fetch('paymoney');
            }
            else{
                $this->error('登入信息错误');
            }
        }
        else{
            $this->error('登入信息错误');
        }

    }
}
