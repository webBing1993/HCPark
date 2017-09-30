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
use  app\common\behavior\Service;

class PublicArea extends Admin
{
    public function index()
    {
        $AdvertisingService = new AdvertisingService();
        $park_id = session('user_auth')['park_id'];

        $search = input('search');
        if (!empty($search)) {
            $map['park_id'] = $park_id;
            $map = [
                'abstract' => array('like', '%' . $search . '%'),
            ];
            $data = $AdvertisingService->where($map)->order('id asc')->paginate();
            foreach ($data as $value) {
                $value['park_id'] = isset($value->findPark->name) ? $value->findPark->name :"";

            }
            int_to_string($data, array(
                'status' => array(0 => '禁用', 1 => '启用'),
                'type' => array(1 => '大厅广告位', 2 => '多功能厅', 3 => '大堂LED屏')
            ));

        } else {
            $map['park_id'] = $park_id;
            $data = $AdvertisingService->where($map)->order('id asc')->paginate();
            foreach ($data as $value) {
                $value['park_id'] = isset($value->findPark->name) ? $value->findPark->name :"";

            }
            int_to_string($data, array(
                'status' => array(0 => '禁用', 1 => '启用'),
                'type' => array(1 => '大厅广告位', 2 => '多功能厅', 3 => '大堂LED屏')
            ));
        }

        $this->assign('list', $data);
        return $this->fetch();
    }

    //公共场所编辑
    public  function  edit(){
     $data =input('');
     $id =$data['id'];
     unset($data['id']);
     $AdvertisingService = new AdvertisingService();
     $reult = $AdvertisingService->save($data,['id' => $id]);
     if($reult){

         return $this->success("更新成功");
     }else{
         return $this->success("更新失败");
     }


    }


    //显示预约情况
    public function show()
    {
        $AdvertisingRecord = new AdvertisingRecord();
        $AdvertisingService = new AdvertisingService();
        $Fa = new FunctionRoomRecord();
        $led = new LedRecord();
        $id = input('id');
        $type = input('type');
        switch ($type) {
            case 1:
                $create_time = array();
                $data = array();
                $time = array();
                $map = [
                    'service_id' => $id,
                    'status' => array('neq', 1)
                ];
                $serviceInfo = $AdvertisingService->where('id', 1)->find();
                $list = $AdvertisingRecord->where($map)->select();
                //所有的创建时间
                foreach ($list as $l) {
                    array_push($create_time, $l['create_time']);
                }
                //数组去重
                $time = array_values(array_unique($create_time));
                foreach ($time as $onetime) {
                    $map = array();
                    foreach ($list as $info) {
                        if ($info['create_time'] == $onetime) {
                            array_push($map, $info);
                        }
                    }
                    $re = [
                        'time' => strtotime($onetime),
                        'user' => isset($map[0]->findUser->name) ? $map[0]->findUser->name : "未找到",
                        'mobile' => isset($info->findUser->mobile) ? $info->findUser->mobile : "未找到",
                        'price' => count($map) * $serviceInfo['price'],
                        'payment_voucher' => $map[0]['payment_voucher'],
                        'userid' => $info['create_user']
                    ];

                    $re['day'] = "";

                    foreach ($map as $value) {
                        $re['day'] .= date('Y-m-d', $value['order_time'])."   " ;
                    }
                    if ($map[0]['status'] == 0) {

                        $re['status'] = "被取消";
                    } else if ($map[0]['status'] == 1) {
                        $re['status'] = "等待上传凭证";
                    } else {
                        $re['status'] = "预约成功";
                    }
                    $re['type'] = 1;
                    $re['state'] = $map[0]['status'];
                    array_push($data, $re);
                }
                break;
            case 2:
                $data = array();
                $time = array();
                $create_time = array();
                $map = [
                    'service_id' => $id,
                    'status' => array('neq', 1)
                ];
                $serviceInfo = $AdvertisingService->where('id', 2)->find();
                $list = $Fa->where($map)->order('create_time desc')->select();
                //所有的创建时间
                foreach ($list as $l) {
                    array_push($create_time, $l['create_time']);
                }
                //数组去重
                $time = array_values(array_unique($create_time));

                foreach ($time as $onetime) {
                    $map = array();
                    foreach ($list as $info) {
                        if ($info['create_time'] == $onetime) {
                            array_push($map, $info);
                        }
                    }
                    $re = [
                        'time' => strtotime($onetime),
                        'user' => isset($info->findUser->name) ? $info->findUser->name : "未找到",
                        'mobile' => isset($info->findUser->mobile) ? $info->findUser->mobile : "未找到",
                        'price' => count($map) * $serviceInfo['price'],
                        'payment_voucher' => $map[0]['payment_voucher'],
                        'userid' => $info['create_user']
                    ];
                    $re['day'] = "";
                    //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
                    $map_time = array();
                    foreach ($map as $m) {
                        array_push($map_time, $m['order_time']);
                    }
                    $mtime_list = array_values(array_unique($map_time));

                    foreach ($mtime_list as $value) {
                        $re['day'] .= date('Y-m-d', $value);
                        $length =0;
                        foreach ($map as $value2) {
                            $length+=1;
                            if ($value == $value2['order_time']) {
                                if ($value2['date_type'] == 1) {
                                    $re['day'] .= "上午 ";
                                } elseif ($value2['date_type'] == 2) {
                                    $re['day'] .= "下午 ";
                                }
                            }
                            if($length==count($map)){
                                $re['day'].="<br>";
                            }
                        }

                    }
                    if ($map[0]['status'] == 0) {

                        $re['status'] = "被取消";
                    } else if ($map[0]['status'] == 1) {
                        $re['status'] = "还未上传凭证";

                    } else {
                        $re['status'] = "预约成功";
                    }
                    $re['type'] = 2;
                    $re['state'] = $map[0]['status'];
                    array_push($data, $re);

                }
                break;
            case 3:
                $data = array();
                $time = array();
                $create_time = array();
                $serviceInfo = $AdvertisingService->where('id', 3)->find();
                $map = [
                    'service_id' => $id,
                    'status' => array('neq', 1)
                ];
                $list = $led->where($map)->order('create_time desc')->select();
                //所有的创建时间
                foreach ($list as $l) {
                    array_push($create_time, $l['create_time']);
                }
                //数组去重
                $time = array_values(array_unique($create_time));

                foreach ($time as $onetime) {
                    $map = array();
                    foreach ($list as $info) {
                        if ($info['create_time'] == $onetime) {
                            array_push($map, $info);
                        }
                    }
                    $re = [
                        'time' => strtotime($onetime),
                        'user' => isset($info->findUser->name) ? $info->findUser->name : "未找到",
                        'price' => count($map) * $serviceInfo['price'],
                        'payment_voucher' => $map[0]['payment_voucher'],
                        'userid' => $info['create_user'],
                        'mobile' => isset($info->findUser->mobile) ? $info->findUser->mobile : "未找到",
                    ];
                    $re['day'] = "";
                    //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
                    $map_time = array();
                    foreach ($map as $m) {
                        array_push($map_time, $m['order_time']);
                    }
                    $mtime_list = array_values(array_unique($map_time));

                    foreach ($mtime_list as $value) {
                        $re['day'] .= date('Y-m-d', $value) .":";
                        $length=0;
                        foreach ($map as $value2) {
                            $length +=1;
                            if ($value == $value2['order_time']) {
                                switch ($value2['date_type']) {
                                    case 1:
                                        $re['day'] .= "9:00-10:00 ";
                                        break;
                                    case 2:
                                        $re['day'] .= "10:00-11:00 ";
                                        break;
                                    case 3:
                                        $re['day'] .= "11:00-12:00 ";
                                        break;
                                    case 4:
                                        $re['day'] .= "12:00-13:00 ";
                                        break;
                                    case 5:
                                        $re['day'] .= "13:00-14:00 ";
                                        break;
                                    case 6:
                                        $re['day'] .= "14:00-15:00 ";
                                        break;
                                    case 7:
                                        $re['day'] .= "15:00-16:00 ";
                                        break;
                                    case 8:
                                        $re['day'] .= "16:00-17:00 ";
                                        break;
                                    case 9:
                                        $re['day'] .= "17:00-18:00 ";
                                        break;
                                }

                            }
                            if($length==count($map)){
                             $re['day'] .= "<br>";

                            }
                        }

                    }
                    if ($map[0]['status'] == 0) {

                        $re['status'] = "被取消";
                    } else if ($map[0]['status'] == 1) {
                        $re['status'] = "还未上传凭证";

                    } else {
                        $re['status'] = "预约成功";
                    }
                    $re['type'] = 3;
                    $re['state'] = $map[0]['status'];
                    array_push($data, $re);
                }
                break;
        }
       // echo json_encode($data);
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function cancel()
    {
        $create_time = input('create_time');
        $type = input('type');
        $advertisingRecord = new AdvertisingRecord();
        $led =new LedRecord();
        $FunctionRoomRecord =new FunctionRoomRecord();
        switch ($type) {
            case 1:
                $list = $advertisingRecord->where('create_time', $create_time)->select();
                foreach ($list as $value) {
                    $value['status'] = 0;
                    $value->save();
                }
                $title ="大厅广告位";
                break;
            case 2:
                $list = $FunctionRoomRecord->where('create_time', $create_time)->select();
                foreach ($list as $value) {
                    $value['status'] = 0;
                    $value->save();
                }
                $title ="二楼多功能厅";
                break;
            case 3:
                $list = $led->where('create_time', $create_time)->select();
                foreach ($list as $value) {
                    $value['status'] = 0;
                    $value->save();
                    $title ="大堂LED屏";
                }
                break;
        }
        $send =new Service();

        $message = "设备服务提示\n您有".$title."\n金额为".input('price')."元\n预约时间为:".input('day')."\n的预约已被取消了。";
        $reult = $send->sendPersonalText($message,input('user'));



          return  $this->success("取消成功");

    }


}
