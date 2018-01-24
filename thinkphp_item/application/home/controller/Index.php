<?php
namespace app\home\controller;
use think\Controller;
use think\Db;
use think\Paginator;
use think\Cache;
use think\Cookie;
use think\Session;

class Index extends Controller
{
    //首页入口
    public function main()
    {
        if(Session::has('user')){
            $userInfo=Session::get('user');
            $user=$userInfo['nowuname'];
            $head=$userInfo['nowhead'];
        }
        else{
            $user='';
            $head='';
        }
//        var_dump($userInfo);exit;
        $this->assign('user',$user);
        $this->assign('head',$head);
        $location=$this->location();
        $city=$location['content']['address_detail']['city'];
        $this->assign('city',$location['content']['address_detail']['city']);
        $this->assign('province',$location['content']['address_detail']['province']);
        return $this->fetch();
    }
    //首页猜你喜欢
    public function guessLike(){
        $data=Db::name('product')->fetchSql(false)->where(['sellway'=>'普通','del'=>0,'state'=>0])->order('RAND()')->limit(4)->select();
        foreach($data as &$val){
            $val['url']=url('goods/Index/goods',['gid'=>$val['pid']]);
        }
        return $data;
    }
    //首页同城商品
    public function getLocalProduct(){
        $location=$this->location();
        $city=$location['content']['address_detail']['city'];
        $localData=Db::name('product')
            ->alias('t1')
            ->join('city t2','t1.ctid=t2.ctid')
            ->where(['t2.city'=>$city,'t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0])
            ->fetchSql(false)
            ->order('RAND()')
            ->limit(4)
            ->select();
        //var_dump($localData);exit;
        foreach($localData as &$val){
            $val['url']=url('goods/Index/goods',['gid'=>$val['pid']]);
        }
       return $localData;
    }
    //首页banner
    public function getBanner(){
        $data=Db::name('banner')->select();
        return $data;
    }
    //搜索页入口
    public function search(){
        $mainUrl=url('home/Index/main');
        $this->assign('main',$mainUrl);
        if(input('?param.clid')){
            $clid=input('param.clid');
        }
        else{
            $clid=0;
        }
        if(input('?param.fid')){
            $fid=input('param.fid');
        }
        else{
            $fid=0;
        }
        Cookie::set('clid',$clid);
        Cookie::set('fid',$fid);
        $clidArr=[];
        if(input('?param.prvid')){
            $prvid=input('param.prvid');
        }
        else{
            $prvid=0;//省份ID 默认获取全国商品
        }
       if(input('?param.ctid')){
            $ctid=input('param.ctid');
        }
        else{
            $ctid=0;//城市ID  默认获取全国商品
        }
        //获取全国商品
        if($prvid==0&&$ctid==0){
            $nowSearch='全国';
            //获取全国全部分类商品
            if($clid==0){
                $class=Db::name('class')->field('clname,clid,fid')->select();

                //全国全部分类商品中：关键词搜索
                if(input('?get.keyWord')){
                    $keyWord=input('get.keyWord');
                    $product=Db::name('product')
                        ->alias('t1')
                        ->join('user t2','t1.uid=t2.uid')
                        ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0])
                        ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                        ->field('t1.*,t2.head,t2.account,t2.uname')
                        ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                    //print_r($product);exit;
                }
                //获取全国全部分类商品
                else{
                    $product=Db::name('product')
                        ->alias('t1')
                        ->join('user t2','t1.uid=t2.uid')
                        ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0])
                        ->field('t1.*,t2.head,t2.account,t2.uname')
                        ->paginate(24);
                    //print_r($product);exit;
                }
            }
            //获取全国具体分类下的商品
            else{
                //关键词搜索
                if(input('?get.keyWord')){
                    $keyWord=input('get.keyWord');
                    if($fid==0){//一级分类关键词搜索
                        //一级分类对应的二级分类
                        $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                        foreach($class as $val){
                            $clidArr[]=$val['clid'];
                        }
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where('clid','in',$clidArr)
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//二级分类关键词搜索
                        $class=[];
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                }
                else{//非关键词搜索商品获取
                    if($fid==0){
                        //一级分类对应的二级分类
                        $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                        foreach($class as $val){
                            $clidArr[]=$val['clid'];
                        }
                        $product=Db::name('product')
                            ->alias('t1')
                            ->fetchSql(false)
                            ->join('user t2','t1.uid=t2.uid')
                            ->where('t1.clid','in',$clidArr)
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                    else{
                        $class=[];
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
            }
        }
        //获取地区商品
        else{
            //获取城市商品：prvid=0 ctid!=0
            if($prvid==0){
                $res=Db::name('city')->where(['ctid'=>$ctid])->field('city')->find();
                $nowSearch=$res['city'];
                //获取城市全部分类商品
                if($clid==0){
                    $class=Db::name('class')->field('clname,clid,fid')->select();
                    //城市全部分类商品中：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//城市全部分类商品
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
                //获取城市具体分类商品
                else{//城市具体分类下关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        if($fid==0){//一级分类关键词搜索
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                        else{//二级分类关键词搜索
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                    }
                    else{//城市具体分类：非关键词搜索商品获取
                        if($fid==0){
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                        else{
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                    }
                }
            }
            //获取省份商品
            else{
                $res=Db::name('province')->where(['prvid'=>$prvid])->field('province')->find();
                $nowSearch=$res['province'];
                //获取省份全部分类商品
                if($clid==0){
                    $class=Db::name('class')->field('clname,clid,fid')->select();

                    //省份全部分类商品中：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//获取省份全部分类商品：非关键词搜索
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
                //获取省份具体分类商品
                else{//省份具体分类商品：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        if($fid==0){//一级分类关键词搜索
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                        else{//二级分类关键词搜索
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                    }
                    else{//省份具体分类商品：非关键词搜索
                        if($fid==0){
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                        else{
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                    }
                }
            }
        }
        //当前商品的地区
        $this->assign('location',$nowSearch);
        //print_r($province);exit;
        foreach($class as &$value){
            $value['url']=url('home/Index/search',['clid'=>$value['clid'],'fid'=>$value['fid']]);
            if($value['fid']==0){
                foreach($class as &$val){
                    if($val['fid']==$value['clid']){
                        $val['url']=url('home/Index/search',['clid'=>$val['clid'],'fid'=>$val['fid']]);
                        $value['child'][]=$val;
                    }
                }
            }
        }

        //商品分类信息
        $this->assign('class',$class);

        //分页页码信息
        $this->assign('page',$product->render()) ;
        $list=$product->toArray();
        //print_r($list['data']);exit;
        if(!empty($list['data'])){
            foreach ($list['data'] as &$val){//注意是$list['data']，又包了一层
                $val['url']=url('goods/Index/goods',['gid'=>$val['pid']]);//增加字段
            }
            unset($value);
        }
        //print_r($list['data']);exit;
        //商品信息
        $this->assign('product', $list['data']);
        $data=[];
        $data[]=$fid;
        $data[]=$clid;
        $clname=Db::name('class')->fetchSql(false)->where('clid','in',$data)->field('clname,clid,fid')->select();
        foreach($clname as &$value){
            $value['url']=url('home/Index/search',['clid'=>$value['clid'],'fid'=>$value['fid']]);
        }
        //print_r($data);exit;
        //导航栏信息
        $this->assign('clid',$clname);
        return $this->fetch();
    }
    public function auction(){
        $mainUrl=url('home/Index/main');
        $this->assign('main',$mainUrl);
        if(input('?param.clid')){
            $clid=input('param.clid');
        }
        else{
            $clid=0;
        }
        if(input('?param.fid')){
            $fid=input('param.fid');
        }
        else{
            $fid=0;
        }
        Cookie::set('clid',$clid);
        Cookie::set('fid',$fid);
        $clidArr=[];
        if(input('?param.prvid')){
            $prvid=input('param.prvid');
        }
        else{
            $prvid=0;//省份ID 默认获取全国商品
        }
        if(input('?param.ctid')){
            $ctid=input('param.ctid');
        }
        else{
            $ctid=0;//城市ID  默认获取全国商品
        }
        //获取全国拍卖商品
        if($prvid==0&&$ctid==0){
            $nowSearch='全国';
            //获取全国全部分类拍卖商品
            if($clid==0){
                $class=Db::name('class')->field('clname,clid,fid')->select();
                //全国全部分类商品中：关键词搜索
                if(input('?get.keyWord')){
                    $keyWord=input('get.keyWord');
                    $product=Db::name('product')
                        ->alias('t1')
                        ->join('user t2','t1.uid=t2.uid')
                        ->join('auction t3','t1.pid=t3.pid')
                        ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0])
                        ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                        ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                        ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                    //print_r($product);exit;
                }
                //获取全国全部分类拍卖商品
                else{
                    $product=Db::name('product')
                        ->alias('t1')
                        ->join('user t2','t1.uid=t2.uid')
                        ->join('auction t3','t1.pid=t3.pid')
                        ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0])
                        ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                        ->paginate(24);
                    //print_r($product);exit;
                }
            }
            //获取全国具体分类下的商品
            else{
                //关键词搜索
                if(input('?get.keyWord')){
                    $keyWord=input('get.keyWord');
                    if($fid==0){//一级分类关键词搜索
                        //一级分类对应的二级分类
                        $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                        foreach($class as $val){
                            $clidArr[]=$val['clid'];
                        }
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where('t1.clid','in',$clidArr)
                            ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//二级分类关键词搜索
                        $class=[];
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where(['t1.sellway'=>'拍卖','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                }
                else{//非关键词搜索商品获取
                    if($fid==0){
                        //一级分类对应的二级分类
                        $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                        foreach($class as $val){
                            $clidArr[]=$val['clid'];
                        }
                        $product=Db::name('product')
                            ->alias('t1')
                            ->fetchSql(false)
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0])
                            ->where('t1.clid','in',$clidArr)
                            ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                    else{
                        $class=[];
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where(['t1.sellway'=>'拍卖','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0])
                            ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
            }
        }
        //获取地区商品
        else{
            //获取城市商品：prvid=0 ctid!=0
            if($prvid==0){
                $res=Db::name('city')->where(['ctid'=>$ctid])->field('city')->find();
                $nowSearch=$res['city'];
                //获取城市全部分类商品
                if($clid==0){
                    $class=Db::name('class')->field('clname,clid,fid')->select();
                    //城市全部分类商品中：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//城市全部分类商品
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                            ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
                //获取城市具体分类商品
                else{//城市具体分类下关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        if($fid==0){//一级分类关键词搜索
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                        else{//二级分类关键词搜索
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where(['t1.sellway'=>'拍卖','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                    }
                    else{//城市具体分类：非关键词搜索商品获取
                        if($fid==0){
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                        else{
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where(['t1.sellway'=>'拍卖','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                    }
                }
            }
            //获取省份商品
            else{
                $res=Db::name('province')->where(['prvid'=>$prvid])->field('province')->find();
                $nowSearch=$res['province'];
                //获取省份全部分类商品
                if($clid==0){
                    $class=Db::name('class')->field('clname,clid,fid')->select();

                    //省份全部分类商品中：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//获取省份全部分类商品：非关键词搜索
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->join('auction t3','t1.pid=t3.pid')
                            ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                            ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
                //获取省份具体分类商品
                else{//省份具体分类商品：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        if($fid==0){//一级分类关键词搜索
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                        else{//二级分类关键词搜索
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where(['t1.sellway'=>'拍卖','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                    }
                    else{//省份具体分类商品：非关键词搜索
                        if($fid==0){
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->fetchSql(false)
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where('t1.clid','in',$clidArr)
                                ->where(['t1.sellway'=>'拍卖','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                        //省份二级分类对应的拍卖商品
                        else{
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->join('auction t3','t1.pid=t3.pid')
                                ->where(['t1.sellway'=>'拍卖','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->field('t1.*,t2.head,t2.account,t2.uname,t3.*')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                    }
                }
            }
        }
        //当前商品的地区
        $this->assign('location',$nowSearch);
        //print_r($province);exit;
        foreach($class as &$value){
            $value['url']=url('home/Index/auction',['clid'=>$value['clid'],'fid'=>$value['fid']]);
            if($value['fid']==0){
                foreach($class as &$val){
                    if($val['fid']==$value['clid']){
                        $val['url']=url('home/Index/auction',['clid'=>$val['clid'],'fid'=>$val['fid']]);
                        $value['child'][]=$val;
                    }
                }
            }
        }

        //商品分类信息
        $this->assign('class',$class);

        //分页页码信息
        $this->assign('page',$product->render()) ;
        $list=$product->toArray();
        //print_r($list['data']);exit;
        if(!empty($list['data'])){
            foreach ($list['data'] as &$val){//注意是$list['data']，又包了一层
                $val['url']=url('goods/Index/goods',['gid'=>$val['pid']]);//增加字段
            }
            unset($value);
        }
        //print_r($list['data']);exit;
        //商品信息
        $this->assign('product', $list['data']);
        $data=[];
        $data[]=$fid;
        $data[]=$clid;
        $clname=Db::name('class')->fetchSql(false)->where('clid','in',$data)->field('clname,clid,fid')->select();
        foreach($clname as &$value){
            $value['url']=url('home/Index/auction',['clid'=>$value['clid'],'fid'=>$value['fid']]);
        }
        //print_r($data);exit;
        //导航栏信息
        $this->assign('clid',$clname);
        return $this->fetch();
    }
    //获取搜索页的导航栏
    public function getNavigation(){
        $clid=Cookie::get('clid');
        $fid=Cookie::get('fid');
        $data[]=$fid;
        $data[]=$clid;
        //获取一级分类下的二级分类
        if($fid==0){
            //一级分类对应的二级分类
            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
        }
        else{
            $class=[];
        }
        $clname=Db::name('class')->fetchSql(false)->where('clid','in',$data)->field('clname,clid,fid')->select();
        foreach($clname as &$value){
            $value['url']=url('home/Index/search',['clid'=>$value['clid'],'fid'=>$value['fid']]);
        }
        return ['navigation'=>$clname,'class'=>$class];
    }
    //获取进入搜索页的分类商品
    public function getProductByClass(){
        if(input('?param.clid')){
            $clid=input('param.clid');
        }
        else{
            $clid=Cookie::get('clid');
        }
        if(input('?param.fid')){
            $fid=input('param.fid');
        }
        else{
            $fid=Cookie::get('fid');
        }
        $clidArr=[];
        if(input('?param.prvid')){
            $prvid=input('param.prvid');
        }
        else{
            $prvid=0;//省份ID 默认获取全国商品
        }
        if(input('?param.ctid')){
            $ctid=input('param.ctid');
        }
        else{
            $ctid=0;//城市ID  默认获取全国商品
        }
        //获取全国商品
        if($prvid==0&&$ctid==0){
            $nowSearch='全国';
            //获取全国全部分类商品
            if($clid==0){
                $class=Db::name('class')->field('clname,clid,fid')->select();

                //全国全部分类商品中：关键词搜索
                if(input('?get.keyWord')){
                    $keyWord=input('get.keyWord');
                    $product=Db::name('product')
                        ->alias('t1')
                        ->join('user t2','t1.uid=t2.uid')
                        ->where(['sellway'=>'普通','del'=>0,'state'=>0])
                        ->where('pname|detail','like','%'.$keyWord.'%')
                        ->field('t1.*,t2.head,t2.account,t2.uname')
                        ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                    //print_r($product);exit;
                }
                //获取全国全部分类商品
                else{
                    $product=Db::name('product')
                        ->alias('t1')
                        ->join('user t2','t1.uid=t2.uid')
                        ->where(['sellway'=>'普通','del'=>0,'state'=>0])
                        ->field('t1.*,t2.head,t2.account,t2.uname')
                        ->paginate(24);
                    //print_r($product);exit;
                }
            }
            //获取全国具体分类下的商品
            else{
                //关键词搜索
                if(input('?get.keyWord')){
                    $keyWord=input('get.keyWord');
                    if($fid==0){//一级分类关键词搜索
                        //一级分类对应的二级分类
                        $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                        foreach($class as $val){
                            $clidArr[]=$val['clid'];
                        }
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where('clid','in',$clidArr)
                            ->where(['sellway'=>'普通','del'=>0,'state'=>0])
                            ->where('pname|detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//二级分类关键词搜索
                        $class=[];
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['sellway'=>'普通','clid'=>$clid,'del'=>0,'state'=>0])
                            ->where('pname|detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                }
                else{//非关键词搜索商品获取
                    if($fid==0){
                        //一级分类对应的二级分类
                        $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                        foreach($class as $val){
                            $clidArr[]=$val['clid'];
                        }
                        $product=Db::name('product')
                            ->alias('t1')
                            ->fetchSql(false)
                            ->join('user t2','t1.uid=t2.uid')
                            ->where('t1.clid','in',$clidArr)
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                    else{
                        $class=[];
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['sellway'=>'普通','clid'=>$clid,'del'=>0,'state'=>0])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
            }
        }
        //获取地区商品
        else{
            //获取城市商品：prvid=0 ctid!=0
            if($prvid==0){
                $res=Db::name('city')->where(['ctid'=>$ctid])->field('city')->find();
                $nowSearch=$res['city'];
                //获取城市全部分类商品
                if($clid==0){
                    $class=Db::name('class')->field('clname,clid,fid')->select();
                    //城市全部分类商品中：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//城市全部分类商品
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.ctid'=>$ctid])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
                //获取城市具体分类商品
                else{//城市具体分类下关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        if($fid==0){//一级分类关键词搜索
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('clid','in',$clidArr)
                                ->where(['sellway'=>'普通','del'=>0,'state'=>0,'ctid'=>$ctid])
                                ->where('pname|detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                        else{//二级分类关键词搜索
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['sellway'=>'普通','clid'=>$clid,'del'=>0,'state'=>0,'ctid'=>$ctid])
                                ->where('pname|detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                    }
                    else{//城市具体分类：非关键词搜索商品获取
                        if($fid==0){
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('clid','in',$clidArr)
                                ->where(['sellway'=>'普通','del'=>0,'state'=>0,'ctid'=>$ctid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                        else{
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['sellway'=>'普通','clid'=>$clid,'del'=>0,'state'=>0,'ctid'=>$ctid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                    }
                }
            }
            //获取省份商品
            else{
                $res=Db::name('province')->where(['prvid'=>$prvid])->field('province')->find();
                $nowSearch=$res['province'];
                //获取省份全部分类商品
                if($clid==0){
                    $class=Db::name('class')->field('clname,clid,fid')->select();

                    //省份全部分类商品中：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                            ->where('t1.pname|t1.detail','like','%'.$keyWord.'%')
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                        //print_r($product);exit;
                    }
                    else{//获取省份全部分类商品：非关键词搜索
                        $product=Db::name('product')
                            ->alias('t1')
                            ->join('user t2','t1.uid=t2.uid')
                            ->where(['sellway'=>'普通','del'=>0,'state'=>0,'t1.prvid'=>$prvid])
                            ->field('t1.*,t2.head,t2.account,t2.uname')
                            ->paginate(24);
                        //print_r($product);exit;
                    }
                }
                //获取省份具体分类商品
                else{//省份具体分类商品：关键词搜索
                    if(input('?get.keyWord')){
                        $keyWord=input('get.keyWord');
                        if($fid==0){//一级分类关键词搜索
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('clid','in',$clidArr)
                                ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->where('pname|detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                        else{//二级分类关键词搜索
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->where('pname|detail','like','%'.$keyWord.'%')
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24,false,['query'=>['keyWord'=>$keyWord]]);
                            //print_r($product);exit;
                        }
                    }
                    else{//省份具体分类商品：非关键词搜索
                        if($fid==0){
                            //一级分类对应的二级分类
                            $class=Db::name('class')->where(['fid'=>$clid])->field('clid,clname,fid')->select();
                            foreach($class as $val){
                                $clidArr[]=$val['clid'];
                            }
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where('clid','in',$clidArr)
                                ->where(['t1.sellway'=>'普通','t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                        else{
                            $class=[];
                            $product=Db::name('product')
                                ->alias('t1')
                                ->join('user t2','t1.uid=t2.uid')
                                ->where(['t1.sellway'=>'普通','t1.clid'=>$clid,'t1.del'=>0,'t1.state'=>0,'t1.prvid'=>$prvid])
                                ->field('t1.*,t2.head,t2.account,t2.uname')
                                ->paginate(24);
                            //print_r($product);exit;
                        }
                    }
                }
            }
        }
        //当前商品的地区
        //$this->assign('location',$nowSearch);
        //print_r($province);exit;
        foreach($class as &$value){
            $value['url']=url('home/Index/search',['clid'=>$value['clid'],'fid'=>$value['fid']]);
            if($value['fid']==0){
                foreach($class as &$val){
                    if($val['fid']==$value['clid']){
                        $val['url']=url('home/Index/search',['clid'=>$val['clid'],'fid'=>$val['fid']]);
                        $value['child'][]=$val;
                    }
                }
            }
        }

        //商品分类信息
        //$this->assign('class',$class);

        //分页页码信息
        //$this->assign('page',$product->render()) ;
        $list=$product->toArray();
        //print_r($list['data']);exit;
        if(!empty($list['data'])){
            foreach ($list['data'] as &$val){//注意是$list['data']，又包了一层
                $val['url']=url('goods/Index/goods',['gid'=>$val['pid']]);//增加字段
            }
            unset($value);
        }
        //print_r($list['data']);exit;
        //商品信息
        //$this->assign('product', $list['data']);
        $data=[];
        $data[]=$fid;
        $data[]=$clid;
        //导航栏信息
        $clname=Db::name('class')->fetchSql(false)->where('clid','in',$data)->field('clname,clid,fid')->select();
        foreach($clname as &$value){
            $value['url']=url('home/Index/search',['clid'=>$value['clid'],'fid'=>$value['fid']]);
        }
        //print_r($data);exit;
        return ['product'=>$list['data'],'class'=>$class,'navigation'=>$clname];
    }
    //获取全国省市信息
    public function getLocation(){
        $province=Db::name('province')->select();
        foreach($province as &$val){
            $val['url']=url('home/Index/search',['prvid'=>$val['prvid']]);
            $city=Db::name('city')->where(['prvid'=>$val['prvid']])->select();
            foreach($city as &$v){
                $v['url']=url('home/Index/search',['ctid'=>$v['ctid']]);
            }
            $val['city']=$city;
        }
        $province[]=['prvid'=>0,'province'=>'全国','city'=>[],'url'=>url('home/Index/search',['prvid'=>0])];
        return $province;

    }
    //获取商品分类 以及小类对应的商品
    public function getClass(){
        $class=Db::name('class')->fetchSql(false)->select();
        foreach($class as $key=>&$value){
            $clid=[];
            if($value['fid']==0){
                foreach($class as $k=>$v){
                    if($value['clid']==$v['fid']){
                        $clid[]=$v['clid'];
                    }
                }
                $product=Db::name('product')
                    ->where('clid','IN',$clid)
                    ->fetchSql(false)
                    ->where(['sellway'=>'普通'])
                    ->order('RAND()')
                    ->limit(6)
                    ->select();
                foreach($product as $k=>&$val){
                   /* var_dump($val);exit;*/
                    $val['url']=url('goods/Index/goods',['gid'=>$val['pid']]);
                }
                $value['product']=$product;
            }
            $value['url']=url('home/Index/search',['clid'=>$value['clid'],'fid'=>$value['fid']]);
        }
        echo json_encode($class);
    }
    public function publish(){
        return $this->fetch();
    }
    public function text(){
        return $this->fetch();
    }
    public function getUserLike(){
        $data=Db::name('product')->fetchSql(false)->order('RAND()')->limit(4)->select();
        echo json_encode($data);
    }
    //远程传输
    public function curlHttp($url,$method,$data){
        $ch=curl_init();//初始化curl对象
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,$method);//ajax type
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        $outPut=json_decode(curl_exec($ch),true);
        return $outPut;
    }
    //百度地图web接口
    public function location(){
        $url='http://api.map.baidu.com/location/ip';
        $data=['ip'=>'','ak'=>'MZbQ3UkIg87ni4XN7aOP1STgjCGuuc5l','sn'=>'','coor'=>'bd09ll'];
        $msg=$this->curlHttp($url,'POST',$data);
        return $msg;
    }
    public function base(){
        return $this->fetch();
    }
    //身份认证
    public function identification($img1,$img2){
        $url='http://netocr.com/api/faceliu.do';
        $method='POST';
        $data=[
            'img1'=>$img1,
            'img2'=>$img2,
            'key'=>'RvNwKuVZfqt1pE5sto3K6r',
            'secret'=>'deadbd0471994b66856bc7a4dd8f8dae',
            'typeId'=>'21',
            'format'=>'json'
        ];
        $res=$this->curlHttp($url,$method,$data);
        return $res;
    }
    function getAccessToken(){
        if(!Cookie::has('accessToken')){
            $url = 'https://aip.baidubce.com/oauth/2.0/token';
            $data=[
                'grant_type'=>'client_credentials',
                'client_id'=>'YGGFkUg4GnjSNODS8h6jiHGb',
                'client_secret'=>'fHxdC0ILdU0EgDo25qmgAf3dsn3NKmc8'
            ];
            $res=$this->curlHttp($url,'POST',$data);
            //var_dump($res);
            // 设置Cookie 有效期为 3600秒
            $accessToken=$res['access_token'];
            Cookie::set('accessToken',$res['access_token'],$res['expires_in']);
        }
        else{
            $accessToken=Cookie::get('accessToken');
        }
        return $accessToken;
    }
    //百度远程传输
    function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        // 初始化curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // post提交方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        // 运行curl
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }
    public function changeBase64(){
        $base64_img1 = input('post.image1');
        $base64_img2 = input('post.image2');
        $base64_img[]=urldecode($base64_img1);
        $base64_img[]=urldecode($base64_img2);
        $code=0;
        $img=[];
        $flag=false;
        $up_dir = './upload/';//存放在当前目录的upload文件夹下
        if(!file_exists($up_dir)){
            mkdir($up_dir,0777);//规定要创建的目录的名称 0777，意味着最大可能的访问权
        }
        foreach($base64_img as $k=> $value){
            //preg_match(正则表达式,需要匹配检索的对象,存储匹配结果的数组)
            if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $value, $result)){
                //var_dump($result);exit;
                $type = $result[2];//上传图片的类型
                //判断类型是否符合
                if(in_array($type,array('pjpeg','jpeg','jpg','gif','bmp','png'))){
                    //图片重命名
                    $new_file = $up_dir.date('YmdHis_').$k.'.'.$type;
                    //把$base64_img中的'data:image/gif;base64,'替换为'' 后的数据流解码后写入$new_file;
                    if(file_put_contents($new_file, base64_decode(str_replace($result[1], '', $value)))){
                        // echo $new_file;exit;
                        $img[]=str_replace($result[1], '', $value);
                        $img_path = str_replace('../../..', '', $new_file);
                        $code=5001;
                    }else{
                        $code=5002;

                    }
                }else{
                    //文件类型错误
                    $code=5003;
                }

            }else{
                //文件错误
                $code=5004;
            }
        }
        if($code==5001){
            $info=$this->idInfo($img[1]);
           /* //百度人脸对比接口
            $data = [
                'images' => implode(',',$img)
            ];
            $token=$this->getAccessToken();
            $url='https://aip.baidubce.com/rest/2.0/face/v2/match?access_token=' . $token;

            $res=$this->request_post($url,$data);
            $res=json_decode($res,true);
            return $res;*/
           if($info['message']['status']==2){
                //return $info;
               //百度人脸对比接口
                $data = [
                    'images' => implode(',',$img)
                ];
                $token=$this->getAccessToken();
                $url='https://aip.baidubce.com/rest/2.0/face/v2/match?access_token=' . $token;
                $res=$this->request_post($url,$data);
                $res=json_decode($res,true);


                //登陆的用户信息
                $user = Session::get('user');
                if($res['result'][0]['score']>=80){

                    return ['code'=>5001,'msg'=>'身份认证成功','id'=>$info];
//                $message= Db::name('user')
//                     ->where('uid',$user['nowuid'])
//                     ->update(['identification'=>0,'idCard'=>$info['id']['cardsinfo']['0']['items']['6']['content'],'idName'=>$info['id']['cardsinfo']['0']['items']['1']['content']]);
//
//                echo $message;exit;
//                    if ($message ==1)
//                    {
//                        return ['code'=>5001,'msg'=>'身份认证成功','id'=>$info];
//                    }else
//                    {
//                        return ['code'=>5007,'msg'=>'身份认证失败','id'=>$info];
//                    }

                }
                else{
                    return ['code'=>5005,'msg'=>'身份验证失败','id'=>$res];
                }
            }
            else{
                return ['code'=>5006,'msg'=>'身份证识别失败'];
            }
            //百度人脸对比接口
            /*$data = [
                'images' => implode(',',$img)
            ];
            $token=$this->getAccessToken();
            $url='https://aip.baidubce.com/rest/2.0/face/v2/match?access_token=' . $token;
              $res=$this->request_post($url,$data);
            $res=json_decode($res,true);
            if($res['result'][0]['score']>=80){
                return ['code'=>5001,'msg'=>'身份认证成功','id'=>$info];
            }
            else{
                return ['code'=>5006,'msg'=>'身份验证失败'];
            }*/
            /* $ocr=$this->identification($img[0],$img[1]);//翔云人证合一接口
            *if ($ocr ['message'] ['status'] == 0){
                 $item=$ocr['cardsinfo'][0]['items'];
                 if($item[1]['content']=='是'){
                     $info=$this->idInfo($img[1]);
                     if(!empty($info['cardsinfo'])){
                         $idCard=$info['cardsinfo'][0]['items'];
                         return ['code'=>5001,'msg'=>'身份认证成功','id'=>$idCard];
                     }
                     else{
                         return ['code'=>5005,'msg'=>'身份证照片无法识别，请重新验证'];
                     }
                 }
                 else{
                     return ['code'=>5006,'msg'=>'身份验证失败','id'=>$ocr];
                 }
             }
             else{
                 return ['code'=>5007,'msg'=>'身份验证失败'];
             }*/

        }elseif($code==5002){
            return ['code'=>5002,'msg'=>'图片上传失败'];
        }elseif($code==5003){
            return ['code'=>5003,'msg'=>'图片上传类型错误'];
        }else{
            return ['code'=>5004,'msg'=>'文件错误'];
        }
        /* $file=$_FILES['myFile'];
         //var_dump($file);
         $upTypes=['image/png','image/jpg','image/gif'];//上传文件类型
         $max_file_size=3145728; //上传文件大小限制, 单位BYTE
         $up_dir = './upload/';//存放在当前目录的upload文件夹下
         if(!file_exists($up_dir)){
             mkdir($up_dir,0777);//规定要创建的目录的名称 0777，意味着最大可能的访问权
         }
         if(isset($_FILES['myFile']))//文件是否存在
         {
             for($i=0;$i<count($file['name']);$i++){
                 if(is_uploaded_file($file['tmp_name'][$i]))
                 {
                     $flag=0;
                     for($j=0;$j<count($upTypes);$j++){
                         if($file['type'][$i]==$upTypes[$j]){
                             $flag=1;
                         }
                     }
                     if($file['error'][$i]==0&&$file['size'][$i]<=$max_file_size&&$flag){
                         move_uploaded_file($file['tmp_name'][$i],$up_dir.$file['name'][$i]);//把图片存在服务器中的image文件夹中
                     }
                     else{
                         echo "文件上传失败";
                     }
                 }
             }
         }*/
    }
    //获取身份证信息
    public function idInfo($img){
        $url='http://netocr.com/api/recogliu.do';
        $data=[
            'img'=>$img,
            'key'=>'RvNwKuVZfqt1pE5sto3K6r',
            'secret'=>'deadbd0471994b66856bc7a4dd8f8dae',
            'typeId'=>'2',
            'format'=>'json'
        ];
        $method='POST';
        $info=$this->curlHttp($url,$method,$data);
        return $info;
    }
    public function upload(){
    // 获取表单上传文件
    $files = request()->file('image');
    foreach($files as $file){
        // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        //var_dump($info);
        if($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            $saveName=$info->getSaveName();
            echo $saveName;
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();
            $img[]=base64_encode($saveName);
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }
    //var_dump($img);
    $data = [
        'images' => implode(',',$img)
    ];
    $token=$this->getAccessToken();
    $url='https://aip.baidubce.com/rest/2.0/face/v2/match?access_token=' . $token;
    $res=$this->request_post($url,$data);
    //var_dump($res);
    $res=$this->identification($img[0],$img[1]);
    //var_dump($res);

}

}
