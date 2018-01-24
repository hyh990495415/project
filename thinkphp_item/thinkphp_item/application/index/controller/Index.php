<?php
namespace app\index\controller;


use function PHPSTORM_META\type;
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



class Index extends  controller
{
//    登录界面
    public function index()
    {
        return $this->fetch();
    }

//    忘记密码界面
    public function forgot_show()
    {
        return $this->fetch('forgot');
    }

//    注册界面
    public function sign_show()
    {
        //查询所有省份
       $province= Db::name('province')
           ->select();

       $this->assign('province',$province);
        return $this->fetch('sign');
    }

//    新密码地址
    public function new_password_show()
    {
        return $this->fetch('new_password');
    }

//    短信登录页
    public function sms_login_show()
    {
        return $this->fetch('sms_login');
    }

//  登陆验证
    public function login()
    {
        //读取个人配置文件
        $reLogInfo = config('reLoginInfo');

        //判断用户、密码、验证码 是否输入完毕

        if (input('?param.username') && input('?param.upwd') && input('?param.code'))
        {
            //用户输入的验证码
            $code= input('param.code');

            //验证码验证过程
            $captcha=new Captcha();
            $result = $captcha->check($code);
            $a= json_encode($result);

            if($a=='false'){
                return json(['code'=>10003,'message'=>$reLogInfo['noCode'],'data'=>[]]);
            }else
            {

                //用户输入的用户名、密码
                $uname= input('param.username');
                $pwd= input('param.upwd');

                //用户数据查询
               $ulist=Db::name('user')
                ->where('account',$uname)
                ->find();
//               var_dump($ulist);exit;
                if(empty($ulist))
                {
                    return json(['code'=>10001,'message'=>$reLogInfo['noUser'],'data'=>[]]);
                }else
                {


                    // MD5 加密 后 加盐
                    $passowrdMd5 =  Md5(input('param.upwd'));
                    $passwordMd5Stalt=  crypt($passowrdMd5,$ulist['csalt']);

//                    echo $ulist['upwd'];
//                    echo $passwordMd5Stalt;
                    //须满足 加盐后 的值与数据库中的值相等，且不能为锁定后用户和删除后用户
                    if ($ulist['upwd'] == $passwordMd5Stalt && $ulist['ulock']==1 && $ulist['udel']==1)
                    {
                                // 用户对应省份信息
                                $province=Db::name('province')
                                    ->where('prvid',$ulist['prvid'])
                                    ->find();

                                //对应市区信息
                                $city=Db::name('city')
                                    ->where('ctid',$ulist['ctid'])
                                    ->find();

                                //对应辖区信息
                                $area=Db::name('area')
                                    ->where('aid',$ulist['aid'])
                                    ->find();
                        //用户基本数据
                        $data = [
							'nowuid'    =>$ulist['uid'],
                            'nowaccount' => $ulist['account'],   //用户名
                            'nowuname'   => $ulist['uname'],    //用户昵称
                            'nowmoney'   => $ulist['money'],    //用户金额
                            'nowcell'    => $ulist['cell'],      //用户手机
                            'nowpervince'=> $province,          //归属省
                            'nowcity'    => $city,              //归属市
                            'nowarea'    => $area               //归属区
                        ];
                           Session::set('user',$data);
                           Cookie::set('user',$ulist['account']);
                            return json(['code'=>10000,'message'=>$reLogInfo['login_success'],'data'=>[]]);
                        }else
                            {
                                return json(['code'=>10001,'message'=>$reLogInfo['noUser'],'data'=>[]]);
                    }

                }

            }

        }else
        {
            echo json_encode(['code'=>10002,'message'=>$reLogInfo['noData'],'data'=>[]]);
        }
    }

//  发送验证码 (忘记密码)
    public function forgot_code()
    {
        //用户输入的手机号
        $phone= input('param.phone');
        $data =  Db::name('user')
            ->where('cell',$phone)
            ->find();
       if ($data == null)
       {
           echo json_encode(['code'=>'0001','msg'=>'用户不存在']);
       }else
       {
           //new 类名
           $captChaNew = new CaptchaNew();
           //生成验证码
           $captChaNew->createCode();
           //获取验证码
           $num=$captChaNew->getCaptcha();

           //验证手机号存入session
           Session::set('forgot_phone',$phone);
           //验证码存入session （忘记密码）
           Session::set('forgot_code',$num);

           //接口部分

           //填写在开发者控制台首页上的Account Sid
           $options['accountsid']='e2cb0d22d60890cda7886bf35fc54f75';
           //填写在开发者控制台首页上的Auth Token
           $options['token']='692c9e79e7d19805990c77cad75e6221';
           //初始化 $options必填
           $ucpass = new Ucpaas($options);

           $appid = "401fdc3135734e50bd70e8c55d0f6ede";	//应用的ID，可在开发者控制台内的短信产品下查看
           $templateid = "253659";    //可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
           $param = "".$num; //多个参数使用英文逗号隔开（如：param=“a,b,c”），如为参数则留空
           $mobile ="".$phone;
           $uid = "";

           //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。

           $ucpass->SendSms($appid,$templateid,$param,$mobile,$uid);
       }


    }

//  忘记密码验证、修改
    public function forgot()
    {
        //用户输入的手机号
        $phone= input('param.phone');
        //用户输入的验证码
        $code= input('param.code');

        //判断找回的手机号和验证码是否一致
        if ($code == Session::get('forgot_code') && $phone == Session::get('forgot_phone'))
        {
            //设置有有效时间，进行修改密码
            Cookie::set('session_id','1',600);
           echo '0';

        }else
        {
           echo '1';
        }

    }

//  修改密码
    public function new_password()
    {
        //用户输入的新密码
        $upwd = input('param.upwd');

        //生成随机数来进行加盐
        $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $stalt = '';
        for ($i = 0; $i < 6; $i++) {
            $stalt.= $str{rand(0, strlen($str) - 1)};
        }

        // MD5 加密 后 加盐
        $passowrdMd5 =  Md5($upwd);
        $passwordMd5Stalt=  crypt($passowrdMd5,$stalt);

        Db::name('user')
            ->where('cell',Session::get('forgot_phone'))
            ->update(['upwd'=>$passwordMd5Stalt,'csalt'=>$stalt]);

        //删除cookie 防止跳转到密码修改页
        Cookie::delete('session_id');

        echo '0';
    }

//  发送验证码（注册）
    public function sign_up_code()
    {
        //用户输入的手机号
        $phone= input('param.phone');

        //new 类名
        $captChaNew = new CaptchaNew();
        //生成验证码
        $captChaNew->createCode();
        //获取验证码
        $num=$captChaNew->getCaptcha();

        //手机号存入session （注册）
        Session::set('sign_up_phone',$phone);
        //验证存入session （注册）
        Session::set('sign_up_code',$num);

        //接口部分

        //填写在开发者控制台首页上的Account Sid
        $options['accountsid']='e2cb0d22d60890cda7886bf35fc54f75';
        //填写在开发者控制台首页上的Auth Token
        $options['token']='692c9e79e7d19805990c77cad75e6221';
        //初始化 $options必填
        $ucpass = new Ucpaas($options);

        $appid = "401fdc3135734e50bd70e8c55d0f6ede";	//应用的ID，可在开发者控制台内的短信产品下查看
        $templateid = "253465";    //可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
        $param = "".$num; //多个参数使用英文逗号隔开（如：param=“a,b,c”），如为参数则留空
        $mobile ="".$phone;
        $uid = "";

        //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。

        $ucpass->SendSms($appid,$templateid,$param,$mobile,$uid);
    }

    //修改省份
    public function province()
    {
        //省id
        $prvid = input('param.prvid');

        //显示对应市区
        $city  = Db::name('city')
            ->where('prvid',$prvid)
            ->select();

       echo  json_encode($city);

    }
    //修改市区
    public function city()
    {
        //市id
        $city = input('param.city');

        //显示对应的辖区
        $area   = Db::name('area')
            ->where('ctid',$city)
            ->select();

        echo  json_encode($area);
    }

    //注册用户信息
    public function register()
    {

//     判断用户名是否存在、密码是否存在、省id是否存在、市id是否在、区id是否存在、手机号是否存在、验证码是否存在
        if(input('?param.username') && input('?param.upwd') && input('param.province')!=0 && input('param.city')
            !=0 && input('param.area')!=0 && input('?post.phone') && input('?post.code'))
        {

//            判断手机验证码是否一致
//            echo '4567';
            if ( Session::get('sign_up_phone') == input('post.phone') &&  Session::get('sign_up_code')== input('post.code'))
            {
//                echo '123';
                $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
                $stalt = '';
                for ($i = 0; $i < 6; $i++) {
                    $stalt.= $str{rand(0, strlen($str) - 1)};
                }

                // MD5 加密 后 加盐
                $passowrdMd5 =  Md5(input('param.upwd'));
                $passwordMd5Stalt=  crypt($passowrdMd5,$stalt);

                        $data=[
                            'uid'           =>'',
                            'account'       =>input('param.username'),
                            'uname'         =>'闲趣',
                            'upwd'          =>$passwordMd5Stalt,
                            'money'         =>0,
                            'sex'           =>'男',
                            'payPwd'        =>md5('123456'),
                            'head'          =>'/image/head.png',
                            'cell'          =>input('post.phone'),
                            'prvid'         =>input('param.province'),
                            'ctid'          =>input('param.city'),
                            'aid'           =>input('param.area'),
                            'wechat'        =>'',
                            'alipay'        =>'',
                            'ulock'         =>1,
                            'registertime'  =>date("Y-m-d H:i:s"),
                            'udel'          =>1,
                            'identification'=>1,
                            'csalt'         =>$stalt,
                            'psalt'         =>''
                        ];


             $data= Db::name('user')->insert($data);
              if ($data==1)
              {
                  echo '0';
              }else
              {
                  echo '1';
              }


            }else
            {
                echo '手机号或验证码不一致';
            }

        }else
        {
                echo '内容未填写完毕';
        }
    }

//   查找用户是否重名
    public function testUser()
    {
       $data= Db::name('user')
            ->where('account',input('param.user'))
            ->find();

       if (!empty($data))
       {
           echo '1';
       }else
       {
           echo '0';
       }
    }

//    短信登录验证码
    public function smsCode()
    {
        //用户输入的手机号
        $phone= input('param.phone');

        //new 类名
        $captChaNew = new CaptchaNew();
        //生成验证码
        $captChaNew->createCode();
        //获取验证码
        $num=$captChaNew->getCaptcha();

        //手机号存入session （短信登录）
        Session::set('smsLogin_phone',$phone);
        //验证存入session （短信登录）
        Session::set('smsLogin_code',$num);

        //接口部分

        //填写在开发者控制台首页上的Account Sid
        $options['accountsid']='e2cb0d22d60890cda7886bf35fc54f75';
        //填写在开发者控制台首页上的Auth Token
        $options['token']='692c9e79e7d19805990c77cad75e6221';
        //初始化 $options必填
        $ucpass = new Ucpaas($options);

        $appid = "401fdc3135734e50bd70e8c55d0f6ede";	//应用的ID，可在开发者控制台内的短信产品下查看
        $templateid = "255372";    //可在后台短信产品→选择接入的应用→短信模板-模板ID，查看该模板ID
        $param = "".$num; //多个参数使用英文逗号隔开（如：param=“a,b,c”），如为参数则留空
        $mobile ="".$phone;
        $uid = "";

        //70字内（含70字）计一条，超过70字，按67字/条计费，超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。

        $ucpass->SendSms($appid,$templateid,$param,$mobile,$uid);
    }

//    短信登录
    public function smsLogin()
    {
        //用户输入的手机号
        $phone = input('param.phone');
        //用户输入的验证码
         $code = input('param.code');
        if ( Session::get('smsLogin_phone') == $phone &&  Session::get('smsLogin_code')== $code)
        {

            //用户数据查询
            $ulist=Db::name('user')
                ->where('cell',$phone)
                ->find();

            // 用户对应省份信息
            $province=Db::name('province')
                ->where('prvid',$ulist['prvid'])
                ->find();

            //对应市区信息
            $city=Db::name('city')
                ->where('ctid',$ulist['ctid'])
                ->find();

            //对应辖区信息
            $area=Db::name('area')
                ->where('aid',$ulist['aid'])
                ->find();
            //用户基本数据
            $data = [
                'nowaccount' => $ulist['account'],   //用户名
                'nowuname'   => $ulist['uname'],    //用户昵称
                'nowmoney'   => $ulist['money'],    //用户金额
                'nowcell'    => $ulist['cell'],      //用户手机
                'nowpervince'=> $province,          //归属省
                'nowcity'    => $city,              //归属市
                'nowarea'    => $area               //归属区
            ];
            Session::set('user',$data);
            Cookie::set('user',$ulist['account']);
            echo '0';
        }else
        {
            echo '1';
        }

    }
}
