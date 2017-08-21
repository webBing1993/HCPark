<?php
/**
 * Created by PhpStorm.
 * User: Butaier
 * Date: 2017/8/14
 * Time: 17:55
 */
namespace app\index\controller;
use app\common\model\CarparkRecord;
use  app\index\model\CompanyService;
use app\common\model\CarparkService;
use app\index\model\WechatUser;
use app\index\model\CompanyApplication;
use app\index\model\Park;
use  app\index\model\WechatTag;
use  app\index\model\PropertyServer;
use app\index\model\WaterService as WaterModel;
use app\index\model\BroadbandPhone as BroadbandModel;

use  app\index\model\AdvertisingRecord;
use  app\index\model\AdvertisingService;
//企业服务
class Service extends Base{


  //服务列表
    public function index() {
        $user_id =session('userId');
        $park_id=session('park_id');
         $user =new WechatUser();
        $app= new  CompanyApplication();
         $info=$user->where('userid',$user_id)->find();
         if($info['tagid']==1){

             $map=[
                 'park_id'=>3,
                 'type'=>0
             ];
             //物业服务
             $PropertyServices=$app->where($map)->order('id asc')->select();
             $map['type']=1;
             //企业服务
             $CompanyService=$app->where($map)->order('id asc')->select();
             $is_boss="yes";


         }else{
             //物业服务
             $serviceApps= $app->where('type',0)->order('id asc')->select();
             $PropertyServices=array();
             foreach ($serviceApps as $value){

                 $parkid= json_decode($value['park_id']);

                 foreach($parkid as $value2){
                     if($value2==$park_id ){
                         $value['park_id']=$park_id;
                         array_push($PropertyServices,$value);

                     }
                 }

             }
             //企业服务
             $companyApps=$app->where('type',1)->order('id asc')->select();
             $CompanyService=array();
             foreach ($companyApps as $value){

                 $parkid= json_decode($value['park_id']);

                 foreach($parkid as $value2){
                     if($value2==$park_id ){
                         $value['park_id']=$park_id;
                         array_push($CompanyService,$value);
                     }
                 }

             }
             $is_boss="no";
         }
        $this->assign('is_boss',$is_boss);
        $this->assign('propert',json_encode($PropertyServices));
        $this->assign('company',json_encode($CompanyService));
        return $this->fetch();

    }

    //选择服务
    public function  onCheck(){
        $path=input('path');
        $app_id=input('id');
        $user_id = session('userId');
        $park_id = session('park_id');
        $UserModel = new  WechatUser();
        $Park = new Park();
        $CardparkService= new CarparkService();
        $AdService = new AdvertisingService();
        $info=[];
        switch ($app_id){
            case 1:



            case 2:
                $userid =session("userId");
                $parkid=session('park_id');
                $parkInfo=Park::where('id',$parkid)->find();
                $userinfo=WechatUser::where(['userid'=>$userid])->find();
                $info =[
                    'name'=>$userinfo['name'],
                    'mobile'=>$userinfo['mobile'],
                    'propretyMobile'=>$parkInfo['property_phone']
                ];
            case 4:
                $userid =session("userId");
                $parkid=session('park_id');
                $parkInfo=Park::where('id',$parkid)->find();
                $userinfo=WechatUser::where(['userid'=>$userid])->find();
                $info =[
                    'name'=>$userinfo['name'],
                    'mobile'=>$userinfo['mobile'],
                    'propretyMobile'=>$parkInfo['property_phone']
                ];
            //车卡
            case 6:
                $map=[
                    'user_id'=>$user_id,
                ];
                $map['park_card']=array('exp','is not null');
                $usercard =$CardparkService->where($map)->select();

                if(count($usercard)>0){

                    $info['type']="old";

                }else{

                    $info['type']="new";
                }


                break;


            //充电柱
            case 7:  break;
           //公共场所
            case 8:
                $re = $AdService->where('park_id',$park_id)->select();
                $info['adlist']=json_encode($re);
                break;
            default:
                $user=$UserModel->where('userid',$user_id)->find();
                $info['name']=$user['name'];
                $info['mobile']=$user['mobile'];
                $info['company']=$user->departmentName->name;
                $info['app_id']=$app_id;
                break;


        }
        $this->assign('info',json_encode($info));
        return $this->fetch($path);



    }


     //预约
     public  function  order(){
      $compantService = new CompanyService();
           $data=input('');
           $re =$this->_checkData($data);
           if(!$re){
             return $this->error('参数缺失');
            }
           $map=[
          'company'=>$data['company'],
          'people'=>$data['name'],
          'mobile'=>$data['mobile'],
          'app_id'=>$data['app_id'],
          'remark'=>$data['remark'],
          'user_id'=>session('userId'),
          'create_time'=>time(),
          'status'=>0
      ];
     $result = $compantService->save($map);
     if($result){
         return $this->success('提交成功');
     }else{

         return $this->error('提交失败');

     }
     }


    public  function  _checkData($data){
         if(empty($data)){
             return false;
         }
         if(!isset($data['company'])||
             !isset($data['name'])||
             !isset($data['mobile'])||
             !isset($data['app_id'])
         ){

             return false;

         }

    if(empty($data['company'])||
        empty($data['name'])||
        empty($data['mobile'])||
        empty($data['app_id'])
    ){

        return false;

    }

      return true;



}




  //新柱办理
    public function  newPillar(){


        return $this->fetch();
    }
 //旧柱办理
    public function  oldPillar(){


        return $this->fetch();
    }



    //新卡主页
    public  function  newCard(){
        $park_id =session('park_id');
        $Park = new Park();
        $park= $Park->where('id',$park_id)->find();
        //支付宝用户
        $data['ailpay_user']=$park['ailpay_user'];
        //银行用户
        $data['bank_user']=$park['bank_user'];
        //缴费支付宝账号
        $data['payment_alipay']=$park['payment_alipay'];
        //缴费支付宝账号
        $data['payment_bank']=$park['payment_bank'];
        //停车卡单价
        $data['carpark_price']=$park['carpark_price'];
        //车卡押金
        $data['carpark_deposit']=$park['carpark_deposit'];

        $this->assign('info',json_encode($data));

        return $this->fetch();
    }

   //新卡，下一步
    public  function  nextNewCard(){

        $data=input('');
        $this->assign('data',json_encode($data));
        return $this->fetch('payment');

    }
    //凭证提交公共方法
    public function  payment(){
        $data = input('');
        $park_id =session('park_id');
        $Park = new Park();
        $park= $Park->where('id',$park_id)->find();
        //支付宝用户
        $data['ailpay_user']=$park['ailpay_user'];
        //银行用户
        $data['bank_user']=$park['bank_user'];
        //缴费支付宝账号
        $data['payment_alipay']=$park['payment_alipay'];
        //缴费支付宝账号
        $data['payment_bank']=$park['payment_bank'];
        $this->assign('info',json_encode($data));

        return $this->fetch();


    }
    //提交新卡
    public  function  addNewCard(){

        $CarparkRecord = new CarparkRecord();
        $CardparkService= new CarparkService();

            $id = session('userId');
            $data = input('');
            $service = [
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'people_card' => $data['people_card'],
                'car_card' => $data['car_card'],
                'user_id' => $id,
                'status' => 0,
                'create_time'=>time()
            ];

            $re = $CardparkService->save($service);

            $record=[
                'type'=>1,
                'aging'=>$data['aging'],
                'payment_voucher'=>$data['payment_voucher'],
                'create_time'=>time(),
                'carpark_id'=>$CardparkService->id,
                'status'=>0,
                'money'=>((int)$data['carpark_price']*(int)$data['aging'])+100,
            ];
            $re2=$CarparkRecord->save($record);
            if($re2){
                $this->success('成功'.json_encode($CardparkService->id));

            }else{
                $this->error("失败");
            }
    }
    //旧卡首页
    public  function  oldCard(){
        $user_id =session('userId');
        $park_id =session('park_id');
        $Park = new Park();
        $carCard =new CarparkService();
        $park= $Park->where('id',$park_id)->find();
        $map['user_id']=$user_id;
        $map['park_card']=array('exp','is not null');
        $cardinfo =$carCard->where($map)->select();
        //支付宝用户
        $data['ailpay_user']=$park['ailpay_user'];
        //银行用户
        $data['bank_user']=$park['bank_user'];
        //缴费支付宝账号
        $data['payment_alipay']=$park['payment_alipay'];
        //缴费支付宝账号
        $data['payment_bank']=$park['payment_bank'];
        //停车卡单价
        $data['carpark_price']=$park['carpark_price'];
        //车卡押金
        $data['carpark_deposit']=$park['carpark_deposit'];
        //用户停车卡信息
        $data['cardlist']=$cardinfo;
        $this->assign('data',json_encode($data));
        return $this->fetch();
    }

    //旧卡下一步
    public  function  nextOldCard(){

        $data=input('');
        $this->assign('data',$data);
        return $this->fetch();

    }
    //旧卡续费（上传凭证）
    public  function  keepOldCard(){

        $CarparkRecord = new CarparkRecord();
        $CardparkService= new CarparkService();
            $id = session('userId');
            $data = input('');
            $record=[
                'type'=>2,
                'aging'=>$data['aging'],
                'payment_voucher'=>$data['payment_voucher'],
                'create_time'=>time(),
                'carpark_id'=>$data['carpark_id'],
                'status'=>0,
                'money'=>$data['money'],
            ];
            $re2=$CarparkRecord->save($record);
            if($re2){
                $this->success('成功');

            }else{
                $this->error("失败");
            }
    }
    //车卡记录
    public  function  carRecord(){
        $service =new CarparkService();
        $user_id= session('userId');
        $map=[
            'user_id'=>$user_id,
            'status'=>array('neq',-1)
        ];
        $list=$service->where($map)->select();
        int_to_string($list,array('status'=>array(0=>'审核中',1=>'审核通过',2=>'审核失败')));
        $this->assign('list',json_encode($list));
        return $this->fetch();
    }

/*        //大厅广告位预约
        public function advertise(){
            $adService=new AdvertisingService();
            $adRecord = new AdvertisingRecord();
            //今天结束时间
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            //本月结束时间
            $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
            $map['order_time']=array('between',array($endToday,$endThismonth));
            $map['service_id']=1;
            $map['status']=array('eq',1);
            $list =$adRecord->where($map)->select();
            $service =$adService->where('id',1)->find();
            $this->assign('price',$service['price']);
            $data=array();
            foreach ($list as $value){
                array_push($data,$value['order_time']);
            }
            $this->assign('record',json_encode($data));
            return $this->fetch();
        }



      //大厅广告位（下一步）
      public  function  nextAdvertise(){
            $park_id=session('$park_id');
            $Park=new Park();
            $data=input('');
            $info = $Park->where("id",$park_id)->find();
            $data['ailpay_user']=$info['ailpay_user'];
            $data['payment_alipay']=$info['payment_alipay'];
            $this->assign('data',$data);
            return $this->fetch();
      }

    //大厅广告位（提交）
    public  function  submitAdvertise(){
        $ad =new AdvertisingRecord();
       $user_id =session('userId');
        $data=input('');
        $num = count($data['order_times']);
        $record=array();
        $creat_time=time();
        foreach ($data['order_times'] as $value){
            $info=[
              'create_user'=>$user_id,
                'service_id'=>1,
                'payment_voucher'=>$data['payment_voucher'],
                'order_time'=>$value,
                 'create_time'=>$creat_time,
                  'statute'=>1
            ];
            array_push($record,$info);
        }
         $re = $ad ->save($record);
        if($re){
            return $this->success('成功');
        }
         else{
             return $this->error('成功');
         }
    }




        //大厅广告位月份切换
       public  function   changeMonth(){
           $adRecord = new AdvertisingRecord();
        //数字（几月）
        $month=input('month');


           $beginThismonth=mktime(0,0,0,$month,1,date('Y'));

           $endThismonth=mktime(23,59,59,$month,date('t'),date('Y'));
           $map['order_time']=array('between',array($beginThismonth,$endThismonth));
           $map['service_id']=1;
           $map['status']=array('eq',1);
           $list =$adRecord->where($map)->select();

           return json_encode($list);
       }*/













    //大厅广告位预约
    public function advertise(){
        $user_id = session('userId');
        $adService=new AdvertisingService();
        $adRecord = new AdvertisingRecord();
        //今天结束时间
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        //本月结束时间
        $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
        $map['order_time']=array('between',array($endToday,$endThismonth));
        $map['service_id']=1;
        $map['status']=array('neq',0);
        //所有被选中的和预约成功的
        $list =$adRecord->where($map)->select();
        //该用户自己选中的（当月）
        $map['status']=array('eq',1);
        $map['create_user']=$user_id;
        $user_select = $adRecord->where($map)->select();
        //该用户自己选中的（全部）
        unset($map['order_time']);
        $user_allselect = $adRecord->where($map)->select();
        $user_allcheck=array();
        foreach ($user_allselect as $value){
            array_push($user_allcheck,$value['order_time']);
        }
        //两者差值
        $reult = array_diff($list,$user_select);
        $service =$adService->where('id',1)->find();
        $this->assign('price',$service['price']);
        $data=array();
        foreach ($reult as $value){
            array_push($data,$value['order_time']);
        }

        $this->assign('record',json_encode($data));
        $this->assign('user_check',json_encode($user_allcheck));

        return $this->fetch();
    }



    //大厅广告位（下一步）
    public  function  nextAdvertise(){

           $data = input('');
           $user_id = session('userId');
           $park_id = session('park_id');
           $Park = new Park();
           $ad = new AdvertisingRecord();
           $record = array();
           $creat_time = time();
           $map = [
               'create_user' => $user_id,
               'status' => 1
           ];

           $is_select = array();

           foreach ($data['order_times'] as $value) {
              $map=['order_time'=> $value,
               'status' => array('neq', 0),
                  'create_user'=>array('neq',$user_id)
                  ];

               $is = $ad->where($map)->find();

               if ($is) {
                   array_push($is_select, $is['order_time']);
               } else {
                   $info = [
                       'create_user' => $user_id,
                       'service_id' => 1,
                       'order_time' => $value,
                       'create_time' => $creat_time,
                       'status' => 1
                   ];
                   array_push($record, $info);
               }
           }
           if(count($is_select)>0){
               $data['no_save'] = json_encode($is_select);
               return   json_encode($data);
           }
           $de = $ad->where($map)->delete();
           $re = $ad->saveAll($record);
           $info = $Park->where("id",$park_id)->find();
           $data['ailpay_user'] = $info['ailpay_user'];
           $data['payment_alipay'] = $info['payment_alipay'];
           $data['no_save'] = json_encode($is_select);


        return   json_encode($data);
    }

    //大厅广告位（提交）
    public  function  submitAdvertise(){
        $ad =new AdvertisingRecord();
        $user_id =session('userId');
        $data=input('');
        $map=[
            'create_user'=>$user_id,
             'status'=>1
        ];
        $record =$ad->where($map)->select();
        foreach ($record as $value){
            $value['payment_voucher']=$data['payment_voucher'];
            $value['status']=2;
        }

        $re = $ad->saveAll($record);
        if($re){
            return $this->success('成功');
        }
        else{
            return $this->error('失败'.$re);
        }
    }




    //大厅广告位月份切换
    public  function   changeMonth(){
        $adRecord = new AdvertisingRecord();
        //数字（几月）
        $month=input('month');


        $beginThismonth=mktime(0,0,0,$month,1,date('Y'));

        $endThismonth=mktime(23,59,59,$month,date('t'),date('Y'));
        $map['order_time']=array('between',array($beginThismonth,$endThismonth));
        $map['service_id']=1;
        $map['status']=array('eq',2);
        $list =$adRecord->where($map)->select();
        $reult =array();
        foreach ($list as $value){

            array_push($list,$value['order_time']);

        }

        return json_encode($reult);
    }






        //公共区服务
        public function publicservice(){



            return $this->fetch();
        }

        //多功能厅
        public function multifunction(){
            return $this->fetch();
        }

    /*物业报修*/
    public function repair(){
        $userid =session("userId");
        $parkid=session('park_id');
        $property =new PropertyServer();
        $data = input('post.');
        $data['user_id']=$userid;
        $data['park_id']=$parkid;

        $res=$property->allowField(true)->save($data);

        if ($res){

            return $this->success("报修成功");
        }else{

            return $this->error("报修失败");
        }


    }
    /*保洁服务*/
    public function clear(){
        $userid =session("userId");
        $parkid=session('park_id');
        $data = input('post.');
        $time = date("w",strtotime(input("dateStr")));
        if ($time ==6 ||$time ==0){
            return $this->error("请在工作日预约");
        }
        $data['clear_time']=strtotime(input("dateStr"));
        $data['image'] =input('imgStr');
        $data['user_id']=$userid;
        $data['park_id']=$parkid;
        $property =new PropertyServer();

        $res=$property->allowField(true)->save($data);
        if ($res){

            return $this->success("报修成功");
        }else{

            return $this->error("报修失败");
        }


    }
    /*物业报修记录*/
    public function repairRecord(){

        $list = PropertyServer::where(['type'=>['<',4],'status'=>['in',[0,1]]])->order('create_time desc')->paginate();
        int_to_string($list,['type'=>[1=>'空调报修',2=>"电梯报修",3=>"其他报修"],
                             'status'=>[0=>"进行中",1=>"已完成"]
                    ]);

        $this->assign('list',$list);

        return $this->fetch();
    }

    /*保洁服务记录*/

    public function clearRecord(){
        $list = PropertyServer::where(['type'=>['<',4],'status'=>['in',[0,1]]])->order('create_time desc')->paginate();
        int_to_string($list,['type'=>[4=>'保洁记录'], 'status'=>[0=>"进行中",1=>"已完成"]]);

        $this->assign('list',$list);

        return $this->fetch();

    }
    /*物业保洁下拉刷新*/
    public function listmore(){
        $type=input('type');
        $len = input('length');
        if ($type==1){

            $list = PropertyServer::where(['type'=>['<',4],'status'=>['in',[0,1]]])->order('create_time desc')->limit($len,6)->paginate();
            int_to_string($list,['type'=>[1=>'空调报修',2=>"电梯报修",3=>"其他报修"]]);
        }else{

            $list = PropertyServer::where(['type'=>4,'status'=>['in',[0,1]]])->order('create_time desc')->limit($len,6)->paginate();
        }

        return $list;

    }

    //饮水服务
    public function waterService()
    {
        $data=input('post.');
        $waterModel = new WaterModel;
        $data['userid']=session('userId');
        $result = $waterModel->allowField(true)->validate(true)->save($data);
        if ($result) {
            //预约成功
            return $this->success("预约成功");
        } else {
            return $this->error($waterModel->getError());
        }


          /*  $waterModel = new WaterModel;
            $_POST['userid']=session('userId');
            $result = $waterModel->allowField(true)->validate(true)->save($_POST);
            if ($result) {
                //预约成功
                return $this->success("预约成功");
            } else {
                return $this->error($waterModel->getError());
            }*/


    }

    //饮水服务列表页
    public function waterList(){
        //分页total
        $total=input('total');
        $userid = session('userId');
        $map = [
            'status'=> "1",
            'userid'=>$userid,
        ];
        $list = WaterModel::where($map)->order('id desc')->paginate($total);
        $this->assign('list',$list);
        return $this->fetch();
    }

    //饮水服务详情页
    public function waterDetail(){
        $id=input('id');
        $result=WaterModel::where('id','eq',$id)->find();
        $this->assign('res',$result);
        echo json_encode($result);exit;
        return $this->fetch();
    }

    //电话宽带
    public function broadbandPhone()
    {
        $data=input('');
//        echo json_encode($data);exit;
            $broadbandModel = new BroadbandModel;
            $data['user_id']=session('userId');

            $result = $broadbandModel->allowField(true)->validate(true)->save($data);
            if ($result) {
                return $this->success("预约成功");
            } else {
                return $this->error($broadbandModel->getError());
            }

    }

//费用缴纳
    public function feedetail(){

        return $this->fetch();
    }

    public function history()
    {
        return $this->fetch();
    }

}