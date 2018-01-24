<?php
/**
 * Created by PhpStorm.
 * User: 何
 * Date: 2017/12/23
 * Time: 14:20
 */

namespace app\index\controller;

use think\captcha\Captcha;
use think\Controller;
use think\Db;
use think\helper\Hash;
use think\helper\hash\Md5;
use think\Request;
use think\Session;
use think\Cookie;

//扩展验证码
use other\CaptchaNew;
use think\Model;

//载入ucpass类
//require_once('../extend/sms_v2_demo_php/lib/Ucpaas.php');

use lib\Ucpaas;


class Personal extends Controller
{
    //默认跳转
    public function index()
    {


        $data= Cookie::get('user');
//        var_dump($data);

       $uname=   Db::name('user')
            ->field('uname')
            ->where('account',$data)
            ->find();
//        var_dump($uname);
        Cookie::set('myusername',$uname['uname']);


//        $menu=[
//           0=>['muntName'=>'个人信息','muntUrl'=>url('index/Personal/message')],
//           1=>['muntName'=>'我发布的','muntUrl'=>url('index/Personal/release')],
//           2=>['muntName'=>'我卖出的','muntUrl'=>url('index/Personal/sell')],
//           3=>['muntName'=>'我买到的','muntUrl'=>url('index/Personal/purchase')],
//           4=>['muntName'=>'我收藏的','muntUrl'=>url('index/Personal/collection')],
//           5=>['muntName'=>'我拍买的','muntUrl'=>url('index/Personal/auction')]
//            ];
//
        $this->assign('menu',"");
        return $this->fetch();

    }
//  个人信息
    public function message()
    {

        $this->assign('menu',"");
        $this->assign('add',"");
//        var_dump($data);

        //查询所有省份
//        $province= Db::name('province')
//            ->select();

//        var_dump($province);
        $this->assign('province','');

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

//   绑定新号码
    public function new_number()
    {
        return $this->fetch('new_number');
    }

//   个人中心数据获取
    public function getMessage()
    {
//        获取数据库中对应的用户信息
        $uname = input('param.user');

           $data=Db::name('user')
            ->where('account',$uname)
            ->find();

        // 用户对应省份信息
        $province=Db::name('province')
            ->where('prvid',$data['prvid'])
            ->find();

        //对应市区信息
        $city=Db::name('city')
            ->where('ctid',$data['ctid'])
            ->find();

        //对应辖区信息
        $area=Db::name('area')
            ->where('aid',$data['aid'])
            ->find();

        //手机号码进行隐藏部分
        $phone = substr_replace($data['cell'],'****',3,4);
//           筛选需要的数据返回
           $message = [
                'nowuname'          =>$data['uname'],       //用户昵称
                'nowsex'            =>$data['sex'],         //性别
                'nowhead'           =>'/tp5053/public/static/image/head.png',       //头像
                'nowcell'           =>$phone,        //电话
                'nowpervince'       => $province,          //归属省
                'nowcity'           => $city,              //归属市
                'nowarea'           => $area,               //归属区
                'nowidcard'         =>$data['idCard']       //身份证
           ];

            echo json_encode($message);

    }

//    修改昵称
    public function nickname()
    {
      $user =   Cookie::get('user');

        if (input('?param.name'))
        {
            $nickname = input('param.name');

          $ti= Db::name('user')
                ->where('account',$user)
                ->update(['uname'=>$nickname]);

            if ($ti==1)
            {
                 return ['code'=>1001,'msg'=>'修改成功'];
            }else
            {
                return['code'=>1002,'msg'=>'修改失败'];
            }

        }

    }
    //  获取解除绑定短信验证码
    public function required_code()
    {

        $data= Db::name('user')
            ->where('account',Cookie::get('user'))
            ->field('cell')
            ->find();


            //new 类名
            $captChaNew = new CaptchaNew();
            //生成验证码
            $captChaNew->createCode();
            //获取验证码
            $num=$captChaNew->getCaptcha();

            //需解绑手机号存入session
            Session::set('Unbundling_phone',$data['cell']);
            //验证码存入session （解绑手机号验证码）
            Session::set('Unbundling_code',$num);

            //接口部分

            //填写在开发者控制台首页上的Account Sid
            $options['accountsid']='e2cb0d22d60890cda7886bf35fc54f75';
            //填写在开发者控制台首页上的Auth Token
            $options['token']='692c9e79e7d19805990c77cad75e6221';
            //初始化 $options必填
            $ucpass = new Ucpaas($options);

            $appid = "401fdc3135734e50bd70e8c55d0f6ede";	//应用的ID，可在开发者控制台内的短信产品下查看
            $templateid = "259243";    //可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
            $param = "".$num; //多个参数使用英文逗号隔开（如：param=“a,b,c”），如为参数则留空
            $mobile ="".$data['cell'];
            $uid = "";

            //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。

            $ucpass->SendSms($appid,$templateid,$param,$mobile,$uid);


    }

//  验证解绑的验证码
    public function unbundling()
    {
        $code = input('param.code');

        if (Session::get('Unbundling_code')== $code)
        {
            Session::delete('Unbundling_code');
            return ['code'=>'100011','msg'=>'验证通过'];
        }else
        {
            return ['code'=>'100012','msg'=>'验证码错误'];
        }
    }

//    发送用户绑定验证码
    public function binding_code()
    {
        //用户需绑定手机号
        $phone = input('param.phone');

        //判断该手机是否已注册
        $data= Db::name('user')
            ->where('cell',$phone)
            ->find();

        if (empty($data))
        {
            //new 类名
            $captChaNew = new CaptchaNew();
            //生成验证码
            $captChaNew->createCode();
            //获取验证码
            $num=$captChaNew->getCaptcha();

            //需绑定机号存入session
            Session::set('binding_phone',$phone);
            //验证码存入session （绑定手机号验证码）
            Session::set('binding_code',$num);

            //接口部分

            //填写在开发者控制台首页上的Account Sid
            $options['accountsid']='e2cb0d22d60890cda7886bf35fc54f75';
            //填写在开发者控制台首页上的Auth Token
            $options['token']='692c9e79e7d19805990c77cad75e6221';
            //初始化 $options必填
            $ucpass = new Ucpaas($options);

            $appid = "401fdc3135734e50bd70e8c55d0f6ede";	//应用的ID，可在开发者控制台内的短信产品下查看
            $templateid = "258418";    //可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
            $param = "".$num; //多个参数使用英文逗号隔开（如：param=“a,b,c”），如为参数则留空
            $mobile ="".$phone;
            $uid = "";

            //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。

            $ucpass->SendSms($appid,$templateid,$param,$mobile,$uid);
        }else
        {
            return ['code'=>100101,'msg'=>'该号码已经注册'];
        }
    }

//    绑定新号码
    public function binding()
    {
        //用户输入的手机号和验证码
        $phone = input('param.phone');
        $code  = input('param.code');

        if (Session::get('binding_phone')==$phone && Session::get('binding_code')==$code)
        {

          $data =   Db::name('user')
                ->where('cell',Session::get('Unbundling_phone'))
                ->fetchSql(false)
                ->update(['cell'=>$phone]);

            if ($data == 1)
            {
                Session::delete('binding_code');
                return ['code'=>1001,'msg'=>'绑定成功'];
            }else
            {
                return ['code'=>1002,'msg'=>'验证码或手机号有误，请重新输入'];
            }

        }else
        {
            return ['code'=>1002,'msg'=>'验证码或手机号有误，请重新输入'];
        }
    }

//   发送用户更改密码验证码
    public function password_code()
    {

        $data= Db::name('user')
            ->where('account',Cookie::get('user'))
            ->find();

        //new 类名
        $captChaNew = new CaptchaNew();
        //生成验证码
        $captChaNew->createCode();
        //获取验证码
        $num=$captChaNew->getCaptcha();

        //更改密码的手机号存入session
        Session::set('chang_phone',$data['cell']);
        //验证码存入session （更改密码手机号验证码）
        Session::set('chang_code',$num);

        //接口部分

        //填写在开发者控制台首页上的Account Sid
        $options['accountsid']='e2cb0d22d60890cda7886bf35fc54f75';
        //填写在开发者控制台首页上的Auth Token
        $options['token']='692c9e79e7d19805990c77cad75e6221';
        //初始化 $options必填
        $ucpass = new Ucpaas($options);

        $appid = "401fdc3135734e50bd70e8c55d0f6ede";	//应用的ID，可在开发者控制台内的短信产品下查看
        $templateid = "259462";    //可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
        $param = "".$num; //多个参数使用英文逗号隔开（如：param=“a,b,c”），如为参数则留空
        $mobile ="".$data['cell'];
        $uid = "";

        //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。

        $ucpass->SendSms($appid,$templateid,$param,$mobile,$uid);
    }

//   验证更改密码验证码是否一致
    public function judge_code()
    {
        $code = input('param.code');

        if (Session::get('chang_code')== $code)
        {
            Session::delete('chang_code');
            return ['code'=>1001,'msg'=>'验证通过'];
        }else
        {
            return ['code'=>1002,'msg'=>'验证码错误'];
        }
    }

//   更改密码
    public function new_password()
    {
        //用户输入的新密码
        $upwd = input('param.password');

        //生成随机数来进行加盐
        $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $stalt = '';
        for ($i = 0; $i < 6; $i++) {
            $stalt.= $str{rand(0, strlen($str) - 1)};
        }

        // MD5 加密 后 加盐
        $passowrdMd5 =  Md5($upwd);
        $passwordMd5Stalt=  crypt($passowrdMd5,$stalt);

       $data=Db::name('user')
            ->where('account',Cookie::get('user'))
            ->update(['upwd'=>$passwordMd5Stalt,'csalt'=>$stalt]);

        if ($data == 1)
        {
            return ['code'=>1001,'msg'=>'更改成功'];
        }else
        {
            return ['code'=>1002,'msg'=>'更改失败'];
        }
    }

//   收获地址
    public  function address_show()
    {
        return $this->fetch('personal_address');
    }

//   获取对应用户的地址信息
    public function address()
    {
        $amount = input('param.amount');//每页显示的量
        $nowpage  = ceil(input('param.nowpage'));//当前页
        //查找用户的uid
        $user = Db::name('user')
            ->where('account',Cookie::get('user'))
            ->field('uid')
            ->find();

        //查询总条数
        $number = Db::name('address')
            ->where('uid',$user['uid'])
            ->count();


          $totalPage=ceil($number/$amount); //总页数
//          echo $totalPage;exit;
//        setcookie('toalopage',$totalPage);//总页数
//        //使用该用户uid 查找对应的所有收货地址(分页)
       $num = ($nowpage-1)*$amount;

        $data = Db::name('address')
           ->where('uid',$user['uid'])
           ->limit($num,$amount)
           ->select();

        //查询所有省份
        $province= Db::name('province')
            ->select();

        return ['tatalpages'=>$totalPage,'nowpage'=>$nowpage,'message'=>$data,];

    }

//   删除对应用户的收货地址
    public function delete_add()
    {
//        echo '123';exit;
        $adid = input('param.adid');
       $res = Db::name('address')
            ->where('adid',$adid)
            ->delete();

//       echo $res;
       if ($res == 1)
       {
           return ['code'=>1001,'msg'=>'删除成功'];

       }else
       {
           return['code'=>1002,'mag'=>'删除失败'];
       }
    }

//   默认的省
    public function init_province()
    {
           $province= Db::name('province')
            ->select();

           echo json_encode($province);
    }

    //默认市区、辖区
    public function init()
    {
        $mycity = ['ctid'=>'','city'=>""];

        $myarea = ['aid'=>"",'aname'=>""];

        return ['mycity'=>$mycity,'myarea'=>$myarea];
    }

    //添加地址
    public function add_address()
    {
        //查找用户的uid
        $user = Db::name('user')
            ->where('account',Cookie::get('user'))
            ->field('uid')
            ->find();

        $data = [
            'adid'      =>'',     //自增主键
            'uid'       =>$user['uid'], //用户id
            'prvid'     =>input('param.province'), //省id
            'ctid'      =>input('param.city'),      //市id
            'aid'       =>input('area'),            //区id
            'address'   =>input('param.detailed'),   //详情地址
            'receive'   =>input('param.consognee'),   //收件人
            'cell'      =>input('param.phone'),     //收件电话
            'aflag'     =>input('param.aflag')      //默认地址
        ];

        $data= Db::name('address')->insert($data);

        if ($data ==1)
        {
            return ['code'=>1001,'msg'=>'添加成功'];
        }else
        {
            return ['code'=>1002,'msg'=>'添加失败'];
        }
    }

    //修改显示的数据
    public function modify_add()
    {
        //对应的收货地址数据
       $data= Db::name('address')
            ->where('adid',input('param.adid'))
            ->find();

       //把修改的地址id 存入session
       Session::set('adid',input('param.adid'));
       //省
        $province= Db::name('province')
            ->where('prvid',$data['prvid'])
            ->find();

        //市
        $city  = Db::name('city')
            ->where('ctid',$data['ctid'])
            ->find();

        //区
        $area  = Db::name('area')
            ->where('aid',$data['aid'])
            ->find();
//        echo json_encode($area);exit;
       return['data'=>$data,'province'=>$province,'city'=>$city,'area'=>$area];
    }

    //修改地址
    public function submit_address()
    {
        //查找用户的uid
        $user = Db::name('user')
            ->where('account',Cookie::get('user'))
            ->field('uid')
            ->find();

        //需要改变的数据
        $data = [
            'uid'       =>$user['uid'], //用户id
            'prvid'     =>input('param.province'), //省id
            'ctid'      =>input('param.city'),      //市id
            'aid'       =>input('area'),            //区id
            'address'   =>input('param.detailed'),   //详情地址
            'receive'   =>input('param.consognee'),   //收件人
            'cell'      =>input('param.phone'),     //收件电话
            'aflag'     =>input('param.aflag')      //默认地址
        ];

            //修改不是默认地址
        if (input('param.aflag')==0)
        {
          $data =  Db::name('address')
                ->where('adid',Session::get('adid'))
                ->update($data);

            if ($data == 1)
            {
                return ['code'=>1001,'msg'=>'修改成功'];
            }else
            {
                return ['code'=>1002,'msg'=>'修改失败'];
            }
        }else
        {
            Db::name('address')
                ->where('uid',$user['uid'])
                ->update(['aflag'=>0]);

          $data = Db::name('address')
                ->where('adid',Session::get('adid'))
                ->update($data);

            if ($data == 1)
            {
                return ['code'=>1001,'msg'=>'修改成功'];
            }else
            {
                return ['code'=>1002,'msg'=>'修改失败'];
            }
        }

    }

    //注销用户
    public function cancel_page()
    {
        //清除cookie存放的用户名
        Cookie::delete('user');
//     return  $this->index();
        $this->assign('menu',"");
        $this->assign('add',"");
        return $this->fetch('index');

    }
}