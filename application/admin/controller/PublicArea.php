<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/8/25
 * Time: 下午2:14
 */

namespace app\admin\controller;
use  app\common\model\AdvertisingService;
use  app\common\model\AdvertisingRecord;
use  app\common\model\FunctionRoomRecord;
use  app\common\model\LedRecord;
class PublicArea extends Admin
{
    public  function  index(){
        $AdvertisingService=new AdvertisingService();
        $park_id=session('user_auth')['park_id'];

        $search=input('search');
        if(!empty($search)){
            $map['park_id']=$park_id;
            $map['status']=1;
            $map=[
                'abstract'=>array('like','%'.$search.'%'),
            ];
            $data = $AdvertisingService->where($map)->order('id asc')->paginate();
            foreach ($data as $value){
                $value['park_id']=isset($value->findPark->name)?$value->findPark->name:"";

            }
            int_to_string($data, array(
                'status' => array(0=>'禁用',1=>'启用'),
                'type'=> array(1=> '大厅广告位', 2=>'多功能厅'  ,3=> 'LED灯' )
            ));

        }else{
           $map['park_id']=$park_id;
            $map['status']=1;
            $data= $AdvertisingService->where($map)->order('id asc')->paginate();
            foreach ($data as $value){
                $value['park_id']=isset($value->findPark->name)?$value->findPark->name:"";

            }
            int_to_string($data, array(
                'status' => array(0=>'禁用',1=>'启用'),
                'type' => array(1=> '大厅广告位', 2=>'多功能厅'  ,3=> 'LED灯' )
            ));



        }

        $this->assign('list',$data);
        return $this->fetch();
    }

    public  function  show(){
        $AdvertisingRecord=new AdvertisingRecord();
        $AdvertisingService=new AdvertisingService();
        $Fa =new FunctionRoomRecord();
       $led = new LedRecord();
        $id=input('id');
        $type=input('type');
        switch ($type){
            case 1:
                $create_time=array();
                $data=array();
                $time=array();
                $map=[
                    'service_id'=>$id,
                    'status'=>array('neq',0)
                ];
                $serviceInfo =$AdvertisingService->where('id',1)->find();
                $list=$AdvertisingRecord->where($map)->select();
                //所有的创建时间
                foreach ($list as $l){
                    array_push($create_time,$l['create_time']);
                }
                //数组去重
                $time = array_values(array_unique ($create_time));
                foreach ($time as $onetime){
                    $map =array();
                    foreach ($list as  $info){
                        if($info['create_time']==$onetime){
                            array_push($map,$info);
                        }
                    }
                    $re=[
                        'time'=> strtotime($onetime),
                        'user'=>isset($info->findUser->name)?$info->findUser->name:"未找到",
                        'price'=>count($map)*$serviceInfo['price']
                        ];

                    $re['day']="";

                    foreach ($map as  $value){
                        $re['day'].=date('Y-m-d',$value['order_time'])." ";
                    }
                    if($map[0]['status']==0){

                        $re['status']="被取消";
                    }else if($map[0]['status']==1){
                        $re['status']="还未上传凭证";
                    }else{
                        $re['status']="预约成功";
                    }
                    $re['type']=1;
                    array_push($data,$re);
                }
                break;
            case 2:
                $data=array();
                $time=array();
                $create_time=array();
                $map=[
                    'service_id'=>$id,
                    'status'=>array('neq',0)
                ];
                $serviceInfo =$AdvertisingService->where('id',2)->find();
                $list= $Fa->where($map)->order('create_time desc')->select();
                //所有的创建时间
                foreach ($list as $l){
                    array_push($create_time,$l['create_time']);
                }
                //数组去重
                $time = array_values(array_unique ($create_time));

                foreach ($time as $onetime){
                    $map =array();
                    foreach ($list as  $info){
                        if($info['create_time']==$onetime){
                            array_push($map,$info);
                        }
                    }
                    $re=[
                        'time'=>strtotime($onetime),
                        'user'=>isset($info->findUser->name)?$info->findUser->name:"未找到",
                        'price'=>count($map)*$serviceInfo['price']
                    ];
                    $re['day']="";
                    //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
                    $map_time=array();
                    foreach ($map as $m){
                        array_push($map_time,$m['order_time']);
                    }
                    $mtime_list = array_values(array_unique ($map_time));

                    foreach ($mtime_list as $value){
                        $re['day'].=date('Y-m-d',$value);
                        foreach($map as $value2){
                            if($value ==$value2['order_time']){

                                if($value2['date_type']==1){
                                    $re['day'].="上午 ";
                                }
                                elseif($value2['date_type']==2){
                                    $re['day'].="下午 " ;
                                }
                            }
                        }

                    }
                    if($map[0]['status']==0){

                        $re['status']="被取消";
                    }else if($map[0]['status']==1){
                        $re['status']="还未上传凭证";

                    }else{
                        $re['status']="预约成功";
                    }
                    $re['type']=2;
                    array_push($data,$re);

                }
                break;




            case 3:
                $data=array();
                $time=array();
                $create_time=array();
                $serviceInfo =$AdvertisingService->where('id',3)->find();
                $map=[
                    'service_id'=>$id,
                    'status'=>array('neq',0)
                ];
                $list= $led->where($map)->order('create_time desc')->select();
                //所有的创建时间
                foreach ($list as $l){
                    array_push($create_time,$l['create_time']);
                }
                //数组去重
                $time = array_values(array_unique ($create_time));

                foreach ($time as $onetime){
                    $map =array();
                    foreach ($list as  $info){
                        if($info['create_time']==$onetime){
                            array_push($map,$info);
                        }
                    }
                    $re=[
                        'time'=>strtotime($onetime),
                        'user'=>isset($info->findUser->name)?$info->findUser->name:"未找到",
                        'price'=>count($map)*$serviceInfo['price']
                    ];
                    $re['day']="";
                    //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
                    $map_time=array();
                    foreach ($map as $m){
                        array_push($map_time,$m['order_time']);
                    }
                    $mtime_list = array_values(array_unique ($map_time));

                    foreach ($mtime_list as $value){
                        $re['day'].=date('Y-m-d',$value)."| ";
                        foreach($map as $value2){
                            if($value ==$value2['order_time']){
                                switch($value2['date_type']){
                                    case 1:$re['day'].="9:00-10:00 ";break;
                                    case 2:$re['day'].="10:00-11:00 ";break;
                                    case 3:$re['day'].="11:00-12:00 ";break;
                                    case 4:$re['day'].="12:00-13:00 ";break;
                                    case 5:$re['day'].="13:00-14:00 ";break;
                                    case 6:$re['day'].="14:00-15:00 ";break;
                                    case 7:$re['day'].="15:00-16:00 ";break;
                                    case 8:$re['day'].="16:00-17:00 ";break;
                                    case 9:$re['day'].="17:00-18:00 ";break;
                                }

                            }
                        }

                    }
                    if($map[0]['status']==0){

                        $re['status']="被取消";
                    }else if($map[0]['status']==1){
                        $re['status']="还未上传凭证";

                    }else{
                        $re['status']="预约成功";
                    }
                    $re['type']=3;
                    array_push($data,$re);

                }



                break;


        }

        $this->assign('data',$data);
        return $this->fetch();
    }
    public function cancel() {
        $create_time=input('create_time');
        $type=input('type');
       $adr = new AdvertisingRecord();switch ($type){
      case 1:
      $list =$adr->where('create_time',$create_time)->select();

      foreach ($list as $value){
      $value['status']=0;
      $value->save();
      }
      break;

      case 2:break;
      case 3:break;
  }

        if(count($list)>0) {
            $this->sucess('取消成功');
        } else {
            $this->error('取消失败');
        }
    }





}
