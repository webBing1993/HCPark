<?php
/**
 * Created by PhpStorm.
 * User: Butaier
 * Date: 2017/8/14
 * Time: 17:55
 */

namespace app\index\controller;

use app\common\model\FeePayment;
use app\common\model\PeopleRent;
use  app\index\model\CompanyService;
use app\index\model\CarparkService;
use app\index\model\TrademarkAdvisory;
use app\index\model\TrademarkInquire;
use app\index\model\WaterService;
use app\index\model\WechatUser;
use app\index\model\CompanyApplication;
use app\index\model\Park;
use  app\index\model\PropertyServer;
use app\index\model\WaterService as WaterModel;
use app\index\model\BroadbandPhone as BroadbandModel;
use app\index\model\EnterpriseRecruitment as EnterpriseModel;
use  app\index\model\ElectricityService;
use  app\index\model\AdvertisingRecord;
use  app\index\model\AdvertisingService;
use  app\index\model\FunctionRoomRecord;
use  app\index\model\News as NewsModel;
use  app\index\model\LedRecord;
use  app\common\behavior\Service as commonService;
use  app\common\model\ParkRoom;
use  app\common\model\ParkRent;
/*use app\index\model\PersonalMessage;*/
use app\index\model\ServiceInformation as ServiceModel;
use app\common\model\OperationalAuthority;
use app\common\model\WaterType;
use wechat\TPWechat;
use app\index\model\Patent;
use app\index\model\CopyrightArt;
use app\index\model\CopyrightSoft;
use app\index\model\CopyrightSoftwrite;
use app\index\model\ExchangePoint;
use app\common\model\ParkFloor;

//企业服务
class Service extends Base
{


    //服务列表
    public function index()
    {
        $user_id = session('userId');
        $park_id = session('park_id');
        $user = new WechatUser();
        $app = new  CompanyApplication();
        $info = $user->where('userid', $user_id)->find();
        $map = ['type ' => 0];
        $parkName = [];
        $is = 'yes';
        if ($info['fee_status'] == 0) {
            $is = "no";
        }
        $water = 'yes';
        if ($info['water_status'] == 0) {
            $water = "no";
        }
        if ($park_id == 3) {
            $parkName = [['name' => '希垦科技园']];
        }
        if ($park_id == 80) {
            $parkName = [['name' => '人工智能产业园区']];
        }
        /*if($info['tagid']==1){
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
        }else{*/
        //物业服务
        $serviceApps = $app->where($map)->order('id asc')->select();
        $PropertyServices = array();
        foreach ($serviceApps as $value) {

            $parkid = json_decode($value['park_id']);

            foreach ($parkid as $value2) {
                if ($value2 == $park_id) {
                    $value['park_id'] = $park_id;
                    array_push($PropertyServices, $value);
                }
            }
        }
        //企业服务
        $companyApps = $app->where('type', 1)->order('id asc')->select();
        $CompanyService = array();
        foreach ($companyApps as $value) {

            $parkid = json_decode($value['park_id']);

            foreach ($parkid as $value2) {
                if ($value2 == $park_id) {
                    $value['park_id'] = $park_id;
                    array_push($CompanyService, $value);
                }
            }
        }
        //人才服务
        $talentService = $app->where('type', 2)->order('id asc')->select();
        $Talent = array();
        foreach ($talentService as $value) {

            $parkid = json_decode($value['park_id']);

            foreach ($parkid as $value2) {
                if ($value2 == $park_id) {
                    $value['park_id'] = $park_id;
                    array_push($Talent, $value);
                }
            }
        }
        //置顶服务
        $TopServices = array();
        $num = 0;
        foreach ($PropertyServices as $value) {
            if ($value['is_top'] == 1 && $num < 4) {
                $num++;
                array_push($TopServices, $value);
            }
        }
        $is_boss = "no";
        /*}*/
        //政策法规轮播
        $policy = NewsModel::where(['park_id' => $park_id, 'type' => ['in', [4, 5]]])->field('id,title')->order('id  desc')->limit(4)->select();
        $this->assign('policy', json_encode($policy));
        $this->assign('is_boss', $is_boss);
        $this->assign('propert', json_encode($PropertyServices));
        $this->assign('company', json_encode($CompanyService));
        $this->assign('top_service', json_encode($TopServices));
        $this->assign('talent', json_encode($Talent));
        $this->assign('is_fee', $is);
        $this->assign('is_water', $water);
        $this->assign('parkName', json_encode($parkName));
        return $this->fetch();
    }

    //选择服务
    public function onCheck()
    {
        $path = input('path');
        $app_id = input('id');
        $userid = session('userId');
        $parkid = session('park_id');
        $UserModel = new  WechatUser();
        $Park = new Park();
        $CardparkService = new CarparkService();
        $AdService = new AdvertisingService();
        $info = [];
        $user = $UserModel->where('userid', $userid)->find();
        $parkInfo = Park::where('id', $parkid)->find();
        $parkroom = ParkRoom::where('company_id', $user['department'])->find();
        $build_block = !empty($parkroom) ? $parkroom['build_block'] : "";
        //echo json_encode($user);
        if (empty($user['company_address'])) {
            if (empty($parkroom)) {
                $office = "";
            } else {
                $office = $parkroom['room'];
            }
        } else {
            //$array=explode("幢",$user['company_address']);
            //$build_block=$array[0]."幢";
            $office = $user['company_address'];
        }
        switch ($app_id) {
            case 1:
                break;
            //物业报修
            case 2:
                $info = [
                    'name' => $user['name'],
                    'mobile' => $user['mobile'],
                    'propretyMobile' => $parkInfo['property_phone'],
                    'office' => $office,
                    'build_block' => $build_block,
                ];
                $floorList = $this->commonFloor();
                $this->assign('floorlist', json_encode($floorList));
                $this->assign('propretyMobile', $parkInfo['property_phone']);
                break;
            //饮水
            case 3:
                $watertype = WaterType::where(['id' => array('gt', 1), 'status' => 1])->select();
                $this->assign('watertype', json_encode($watertype));
                $info['name'] = $user['name'];
                $info['mobile'] = $user['mobile'];
                $info['office'] = $office;
                $info['build_block'] = $build_block;
                $info['company'] = isset($user->departmentName->name) ? $user->departmentName->name : "";
                $floorList = $this->commonFloor();
                $this->assign('floorlist', json_encode($floorList));
                $cp = new CompanyApplication();
                $Park = new Park();
                $park = $Park->where('id', $parkid)->find();
                $CA = $cp->where('app_id', $app_id)->find();
                //支付宝用户
                $info['ailpay_user'] = $CA['has_alipay'] == 1 ? $park['ailpay_user'] : "";
                //缴费支付宝账号
                $info['payment_alipay'] = $CA['has_alipay'] == 1 ? $park['payment_alipay'] : "";
                if ($parkid == 3) {
                    $imgs = "/index/images/service/payment-code-xiken.png";
                } elseif ($parkid == 80) {
                    $imgs = "/index/images/service/payment-code-binjiang.png";
                }
                $info['code'] = $imgs;
                //$info['app_id'] = $app_id;
                break;
            //室内保洁
            case 4:
                $info = [
                    'name' => $user['name'],
                    'mobile' => $user['mobile'],
                    'office' => $office,
                    'build_block' => $build_block,
                    'propretyMobile' => $parkInfo['property_phone']
                ];
                $floorList = $this->commonFloor();
                $this->assign('floorlist', json_encode($floorList));
                break;
            //车卡
            case 6:
                $map = [
                    'user_id' => $userid,
                ];
                $map['park_card'] = array('exp', 'is not null');
                $map['status'] = array('in', [0, 1]);
                $usercard = $CardparkService->where($map)->find();

                if ($usercard) {

                    if ($usercard['status'] == 1) {
                        $info['type'] = "old";
                    } else {
                        $info['type'] = "check";
                    }

                } else {

                    $info['type'] = "new";
                }

                break;
            //充电柱
            case 7:
                $es = new ElectricityService();
                $map = [
                    'user_id' => $userid,
                    'electricity_id' => array('exp', 'is not null'),
                    'status' => array('in', [0, 1])
                ];
                $is_new = $es->where($map)->find();
                if ($is_new) {
                    if ($is_new['status'] == 1) {
                        $info['type'] = "old";
                    } else {
                        $info['type'] = "check";
                    }

                } else {
                    $info['type'] = "new";
                }
                break;
            //公共场所
            case 8:
                $re = $AdService->where('park_id', $parkid)->select();
                $info['adlist'] = $re;
                break;
            //服务信息(人才服务)
            case 19:

                $map = array(
                    'park_id' => session('park_id'),
                    'status' => 1,
                );

                $list = ServiceModel::where($map)->order('create_time  desc')->limit(6)->select();
                $info['list'] = $list;
                break;
            //企业招聘(人才服务)
            case 20:
                $map = array(
                    'park_id' => $parkid,
                    'status' => 1,
                );
                $list = EnterpriseModel::where($map)->order('create_time  desc')->field('id,position,company,education,experience,number,wages')->select();
                $info['list'] = $list;
                break;
            default:
                $user = $UserModel->where('userid', $userid)->find();
                $info['name'] = $user['name'];
                $info['mobile'] = $user['mobile'];
                $info['company'] = isset($user->departmentName->name) ? $user->departmentName->name : "";
                $info['app_id'] = $app_id;
                $floorList = $this->commonFloor();
                $this->assign('floorlist', json_encode($floorList));
                break;
        }
        //echo json_encode($info);
        $info['app_id'] = $app_id;
        $info['park_id'] = $parkid;
        $this->assign('info', json_encode($info));
        return $this->fetch($path);
    }


    /*企业招聘详情页*/
    public function talentdetail()
    {
        $id = input('id');
        $info = EnterpriseModel::where('id', $id)->find();
        $this->assign('info', json_encode($info));
        return $this->fetch();
    }

    /*服务信息列表下拉刷新*/
    public function serviceList()
    {
        $len = input("length");
        $parkid = session('park_id');
        $map = array(
            'park_id' => $parkid,
            'status' => 1,
        );

        $list = ServiceModel::where($map)
            ->order("create_time desc")
            ->limit($len, 6)
            ->select();

        if ($list) {

            return json(['code' => 1, 'data' => $list]);
        } else {

            return json(['code' => 0, 'msg' => "没有更多内容了"]);
        }

    }

    /*服务信息详情页*/
    public function infoDetail()
    {
        $id = input('id');
        $info = ServiceModel::get($id);
        $info['views'] = $info['views'] + 1;
        $info->save();
        $parkid = session('park_id');
        //发布园区
        $park = Park::where('id', 'eq', $parkid)->field('name')->find();

        $this->assign('park', $park['name']);
        $this->assign('news', $info);
        return $this->fetch();
    }

//    暂无权限
    public function noPermissions()
    {
        $parkid = session('park_id');
        $park_info = Park::where('id', $parkid)->find();
        $property_phone = $park_info['property_phone'] ? $park_info['property_phone'] : '';
        $this->assign('parkid', $parkid);
        $this->assign('property_phone', $property_phone);

        return $this->fetch();
    }

    public function officeSupplies()
    {
        return $this->fetch();
    }

    //预约
    public function order()
    {
        //$personalMessage = new PersonalMessage();
        $compantService = new CompanyService();
        $companyapplication = new CompanyApplication();

        $data = input('');
        $re = $this->_checkData($data);
        if (!$re) {
            return $this->error('参数缺失');
        }
        $map = [
            'company' => $data['company'],
            'people' => $data['name'],
            'mobile' => $data['mobile'],
            'app_id' => $data['app_id'],
            'remark' => $data['remark'],
            'user_id' => session('userId'),
            'create_time' => time(),
            'status' => 0
        ];
        $result = $compantService->save($map);
        if ($result) {
            //todo： 推送点击到详情页面代码
            $ca = $companyapplication->where('app_id', $data['app_id'])->find();
            $message = [
                "title" => "企业服务提示",
                "description" => $ca['name'] . "服务申请\n公司名称：" . $data['company'] . "\n联系人员：" . $data['name'] . "\n联系方式：" . $data['mobile'] . "\n备注信息：" . $data['remark'],
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/' . $data['app_id'] . '/can_check/yes/id/' . $compantService->id
            ];

            /* $new = [
                 "title" => $message['title'],
                 "message" => $message['description'],
                 'type' => 1,
                 'park_id' => session('park_id'),
                 'app_id' => $data['app_id'],
                 'sid' => $compantService->id,
                 'create_time' => time(),
                 'status' => 0
             ];
             $savemessage = $personalMessage->save($new);*/


            //推送给运营
            $reult = $this->commonSend(1, $message, '', $data['app_id']);
            if ($reult) {
                return $this->success("预约成功");
            } else {
                return $this->error("推送失败");
            }


            return $this->success('提交成功');


        } else {
            return $this->error('提交失败');
        }
    }


    //新柱办理
    public function newPillar()
    {
        $data['app_id'] = input('app_id');
        $park_id = session('park_id');
        $Park = new Park();
        $park = $Park->where('id', $park_id)->find();
        //充电柱单价
        $data['charging_price'] = (int)$park['charging_price'];
        //充电柱押金
        $data['charging_deposit'] = (int)$park['charging_deposit'];
        $this->assign('data', json_encode($data));
        return $this->fetch();
    }


    //新柱提交
    public function addNewPillar()
    {

        $PillarService = new ElectricityService();
        $park_id = session('park_id');
        //$personalMessage = new PersonalMessage();
        $id = session('userId');
        $data = input('');
        $service = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'user_id' => $id,
            'status' => 0,
            'create_time' => time(),
            'type' => 1,
            'aging' => $data['aging'],
            'payment_voucher' => json_encode($data['payment_voucher']),
            'money' => ((int)$data['charging_price'] * (int)$data['aging']) + (int)$data['charging_deposit'],
            'company' => $data['company'],
            'park_id' => $park_id,
        ];

        $re = $PillarService->save($service);


        if ($re) {
            //todo： 推送点击到详情页面代码（要改）
            $message = [
                "title" => "充电柱服务提示",
                "description" => "您有新充电柱缴费需要审核，请点击查看",
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/7/can_check/yes/id/' . $PillarService->getLastInsID()
            ];
            /* $new = [
                 "title" => $message['title'],
                 "message" => $message['description'],
                 'type' => 1,
                 'park_id' => session('park_id'),
                 'app_id' => $data['app_id'],
                 'sid' => $PillarService->id,
                 'create_time' => time(),
                 'status' => 0
             ];
             $savemessage = $personalMessage->save($new);*/
            //推送给运营
            $reult = $this->commonSend(1, $message, '', 7);
            if ($reult) {
                $msg = "您的缴费信息正在核对中;核对完成后,将在个人中心中予以反馈;请耐心等待,确认成功后;请您在2小时内到希垦科技园A座201领取钥匙";
                $this->success('成功' .$PillarService->id,'',$msg);
            } else {
                return $this->error("推送失败");
            }


        } else {
            $this->error("失败");
        }

    }


    //旧柱办理
    public function oldPillar()
    {
        $data['app_id'] = input('app_id');
        $user_id = session('userId');
        $park_id = session('park_id');
        $Park = new Park();
        $pillar = new ElectricityService();
        $park = $Park->where('id', $park_id)->find();
        $map['user_id'] = $user_id;
        $map['status'] = 1;
        $pillarinfo = $pillar->where($map)->order("create_time desc")->select();
        $pillar_id = array();
        foreach ($pillarinfo as $value) {
            array_push($pillar_id, $value['electricity_id']);
        }
        $pillar_ids = array_values(array_unique($pillar_id));
        $list = array();
        foreach ($pillar_ids as $value) {
            $is = 0;
            foreach ($pillarinfo as $v) {
                if ($v['electricity_id'] == $value && $is == 0) {
                    $is = 1;
                    array_push($list, $v);
                }
            }
        }

        //充电柱单价
        $data['charging_price'] = $park['charging_price'];
        //充电柱押金
        $data['charging_deposit'] = $park['charging_deposit'];
        //用户停车卡信息
        $data['cardlist'] = $list;
        $this->assign('data', json_encode($data));
        return $this->fetch();
    }


    //旧柱提交
    public function addOldPillar()
    {
        $er = new ElectricityService();
        $parkId = session('park_id');
        //$personalMessage = new PersonalMessage();
        $id = session('userId');
        $data = input('');
        $record = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'user_id' => $id,
            'type' => 2,
            'electricity_id' => $data['id'],
            'aging' => $data['aging'],
            'payment_voucher' => json_encode($data['payment_voucher']),
            'create_time' => time(),
            'status' => 0,
            'money' => ((int)$data['charging_price'] * (int)$data['aging']),
            'company' => $data['company'],
            'park_id' => $parkId,
        ];
        $re2 = $er->save($record);
        if ($re2) {
            //todo： 推送点击到详情页面代码（要改）
            $message = [
                "title" => "充电柱服务提示",
                "description" => "您有旧充电柱缴费需要审核，请点击查看",
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/7/can_check/yes/id/' . $er->getLastInsID()
            ];


            /*$new = [
                "title" => $message['title'],
                "message" => $message['description'],
                'type' => 1,
                'park_id' => session('park_id'),
                'app_id' => $data['app_id'],
                'sid' => $er->id,
                'create_time' => time(),
                'status' => 0
            ];
            $savemessage = $personalMessage->save($new);*/
            //推送给运营
            $reult = $this->commonSend(1, $message, '', 7);
            if ($reult) {

                $msg ="提交成功，您的缴费信息正在核对中，核对完成后，将在个人中心中予以反馈";
                $this->success('成功','',$msg);
            } else {
                return $this->error("推送失败");
            }


        } else {
            $this->error("失败");
        }
    }


    //充电柱记录
    public function pillarRecord()
    {
        $service = new ElectricityService;
        $user_id = session('userId');
        $park_id = session('park_id');
        $map = [
            'user_id' => $user_id,
            'status' => array('neq', -1),
            'park_id' => $park_id
        ];
        $list = $service->where($map)->order('create_time desc')->select();

        int_to_string($list, array('type' => array(1 => '新柱办理', 2 => '旧柱办理'), 'status' => array(0 => '审核中', 1 => '审核通过', 2 => '审核失败')));
        $res = array();
        foreach ($list as $k => $val) {
            $res[$k]['name'] = $val['type'] == 1 ? '新柱办理' : "旧柱续费";
            $res[$k]['pay'] = $val['money'];
            $res[$k]['time'] = $val['create_time'];
            $res[$k]['status'] = $val['status'];
            $res[$k]['id'] = $val['id'];
            $res[$k]['company'] = $val['company'];
            $res[$k]['url'] = '/index/service/historyDetail/appid/7/can_check/yes/id/' . $val['id'];
        }
        return $res;
    }


    //新车卡主页
    public function newCard()
    {
        $data['app_id'] = input('app_id');
        $park_id = session('park_id');
        $Park = new Park();
        $park = $Park->where('id', $park_id)->find();
        //停车卡单价
        $data['carpark_price'] = $park['carpark_price'];
        //车卡押金
        $data['carpark_deposit'] = $park['carpark_deposit'];
        $this->assign('info', json_encode($data));

        return $this->fetch();
    }

    //新车卡，下一步
    public function nextNewCard()
    {
        $data = input('');
        $this->assign('data', json_encode($data));
        return $this->fetch('payment');
    }

    //凭证提交公共方法
    public function payment()
    {
        $user_id = session('userId');
        $userinfo = WechatUser::where('userid', $user_id)->find();
        $data = input('');
        $park_id = session('park_id');
        $cp = new CompanyApplication();
        $Park = new Park();
        $park = $Park->where('id', $park_id)->find();
        $CA = $cp->where('app_id', $data['app_id'])->find();
        //支付宝用户
        $data['ailpay_user'] = $CA['has_alipay'] == 1 ? $park['ailpay_user'] : "";
        //银行用户
        $data['bank_user'] = $CA['has_bank'] == 1 ? $park['bank_user'] : "";
        //缴费支付宝账号
        $data['payment_alipay'] = $CA['has_alipay'] == 1 ? $park['payment_alipay'] : "";
        //缴费支付宝账号
        $data['payment_bank'] = $CA['has_bank'] == 1 ? $park['payment_bank'] : "";
        $data['invoice'] = array();
        //用户联系方式
        $data['invoice']['mobile'] = $userinfo['mobile'];
        //用户公司名称
        $data['invoice']['department'] = isset($userinfo->departmentName->name) ? $userinfo->departmentName->name : "";
        //发票其他信息
        $data['invoice']['taxpayer_number'] = "";
        $data['invoice']['contact_address'] = "";
        $data['invoice']['bank'] = "";
        $data['invoice']['account_opening'] = "";

        //开不开票
        $data['invoicing'] = $park_id == 80 ? "yes" : "no";
        if ($park_id == 3) {

            $imgs = "/index/images/service/payment-code-xiken.png";

        } elseif ($park_id == 80) {

            $info = FeePayment::where(['status' => 1, 'company_id' => $userinfo['department']])->order('create_time desc')->find();
            if (count($info) > 0) {
                $data['invoice']['department'] = $info['department'];
                $data['invoice']['mobile'] = $info['mobile'];
                $data['invoice']['taxpayer_number'] = $info['taxpayer_number'];
                $data['invoice']['contact_address'] = $info['contact_address'];
                $data['invoice']['bank'] = $info['bank'];
                $data['invoice']['account_opening'] = $info['account_opening'];
            }
            $imgs = "/index/images/service/payment-code-binjiang.png";
        }
        $data['code'] = $imgs;


        $this->assign('data', json_encode($data));

        return $this->fetch();
    }

    //提交新卡
    public function addNewCard()
    {
        $CardparkService = new CarparkService();
        //$personalMessage = new PersonalMessage();
        $id = session('userId');
        $parkId = session('park_id');
        $data = input('');
        $p_v = array();
        $file = new File();
        foreach ($data['payment_voucher'] as $value) {
            $info = $file->uploadPicture();
            $a['picture'] = $value;
            array_push($p_v, $info);
        }
        $service = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'people_card' => $data['people_card'],
            'user_id' => $id,
            'status' => 0,
            'create_time' => time(),
            'car_card' => $data['car_card'],
            'type' => 1,
            'aging' => $data['aging'],
            'payment_voucher' => json_encode($data['payment_voucher']),
            'money' => ((int)$data['carpark_price'] * (int)$data['aging']) + (int)$data['carpark_deposit'],
            'company' => $data['company'],
            'park_id' => $parkId,
        ];
        $re = $CardparkService->save($service);


        if ($re) {
            //todo： 推送点击到详情页面代码（要改）
            $message = [
                "title" => "车卡服务提示",
                "description" => "您有新卡缴费需要审核，请点击查看",
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/6/can_check/yes/id/' . $CardparkService->getLastInsID()
            ];
            /*$new = [
                "title" => $message['title'],
                "message" => $message['description'],
                'type' => 1,
                'park_id' => session('park_id'),
                'app_id' => $data['app_id'],
                'sid' => $CardparkService->id,
                'create_time' => time(),
                'status' => 0
            ];
            $savemessage = $personalMessage->save($new);*/
            //推送给运营
            $reult = $this->commonSend(1, $message, '', 6);
            if ($reult) {
                $msg = "您的缴费信息正在核对中;核对完成后,将在个人中心中予以反馈;请耐心等待,确认成功后;请您在2小时内到希垦科技园A座201领取车卡";
                $this->success('成功', "", $msg);
            } else {
                return $this->error("推送失败");
            }
        } else {
            $this->error("失败");
        }
    }


    //旧卡首页
    public function oldCard()
    {
        $user_id = session('userId');
        $park_id = session('park_id');
        $data['app_id'] = input('app_id');
        $Park = new Park();
        $carCard = new CarparkService();
        $park = $Park->where('id', $park_id)->find();
        $map['user_id'] = $user_id;
        $map['status'] = 1;
        //已通过审核的卡
        $cardinfo = $carCard->where($map)->order('create_time desc')->select();
        //该用户按卡号分
        $park_card = array();
        foreach ($cardinfo as $value) {
            array_push($park_card, $value['park_card']);
        }
        $park_cards = array_values(array_unique($park_card));
        $list = array();
        foreach ($park_cards as $value) {
            $is = 0;
            foreach ($cardinfo as $v) {
                if ($v['park_card'] == $value && $is == 0) {
                    $is = 1;
                    array_push($list, $v);
                }
            }
        }
        //停车卡单价
        $data['carpark_price'] = $park['carpark_price'];
        //车卡押金
        $data['carpark_deposit'] = $park['carpark_deposit'];
        //用户停车卡信息
        $data['cardlist'] = $list;
        $this->assign('data', json_encode($data));
        return $this->fetch();
    }

    //旧卡续费（上传凭证）
    public function keepOldCard()
    {
        $CardparkService = new CarparkService();
        //$personalMessage = new PersonalMessage();
        $id = session('userId');
        $parkId = session('park_id');
        $data = input('');
        $service = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'people_card' => $data['people_card'],
            'car_card' => $data['car_card'],
            'user_id' => $id,
            'park_card' => $data['park_card'],
            'type' => 2,
            'aging' => $data['aging'],
            'payment_voucher' => json_encode($data['payment_voucher']),
            'create_time' => time(),
            'status' => 0,
            'money' => ((int)$data['carpark_price'] * (int)$data['aging']),
            'company' => $data['company'],
            'park_id' => $parkId,
        ];
        $re2 = $CardparkService->save($service);
        if ($re2) {

            //todo： 推送点击到详情页面代码（要改）
            $message = [
                "title" => "车卡服务提示",
                "description" => "您有旧卡缴费需要审核，请点击查看",
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/6/can_check/yes/id/' . $CardparkService->getLastInsID()
            ];
            /*$new = [
                "title" => $message['title'],
                "message" => $message['description'],
                'type' => 1,
                'park_id' => session('park_id'),
                'app_id' => $data['app_id'],
                'sid' => $CardparkService->id,
                'create_time' => time(),
                'status' => 0
            ];
            $savemessage = $personalMessage->save($new);*/
            //推送给运营
            $reult = $this->commonSend(1, $message, '', 6);
            if ($reult) {
                $msg = "您的缴费信息正在核对中;核对完成后,将在个人中心中予以反馈";
                $this->success('成功', "", $msg);
            } else {
                return $this->error("推送失败");
            }
        } else {
            $this->error("失败");
        }
    }

    //车卡记录
    public function carRecord()
    {
        $user = session('userId');
        $park_id = session('park_id');
        $service = new CarparkService();
        $map = [
            'status' => array('neq', -1),
            'user_id' => $user,
            'park_id' => $park_id
        ];
        $list = $service->where($map)->field('id,type,money,status,create_time')->order('create_time desc')->select();
        foreach ($list as $v) {
            $v['name'] = $v['type'] == 1 ? '新卡办理' : "旧卡续费";
            $v['pay'] = $v['money'];
            $v['time'] = date('Y-m-d', $v['create_time']);
            $v['url'] = '/index/service/historyDetail/appid/6/can_check/yes/id/' . $v['id'];
        }
        return $list;
    }


    //大厅广告位预约
    public function advertise()
    {
        $user_id = session('userId');
        $adService = new AdvertisingService();
        $adRecord = new AdvertisingRecord();
        //取消超时没有上传凭证的预约信息
        $nowtime = time() - 600;
        $map = [
            'status' => 1,
            'create_time' => array('lt', $nowtime),
        ];

        $out_date = $adRecord->where($map)->select();

        foreach ($out_date as $value) {
            $value['status'] = 0;
            $value->save();
        }
        /* **************************************/

        //今天结束时间
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        //本月结束时间
        $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        unset($map['create_time']);
        $map['order_time'] = array('between', array($endToday, $endThismonth));
        $map['service_id'] = 1;
        $map['status'] = array('neq', 0);
        //所有被选中的和预约成功的
        $list = $adRecord->where($map)->select();
        //该用户自己选中的（当月）
        $map['status'] = array('eq', 1);
        $map['create_user'] = $user_id;
        $user_select = $adRecord->where($map)->select();
        //该用户自己选中的（全部）
        unset($map['order_time']);
        $user_allselect = $adRecord->where($map)->select();
        $user_allcheck = array();
        foreach ($user_allselect as $value) {
            array_push($user_allcheck, $value['order_time'] * 1000);
        }
        //两者差值
        $reult = array_diff($list, $user_select);

        $service = $adService->where('id', 1)->find();
        $this->assign('price', $service['price']);
        $data = array();
        foreach ($reult as $value) {
            array_push($data, $value['order_time'] * 1000);
        }

        $this->assign('record', json_encode($data));
        $this->assign('user_check', json_encode($user_allcheck));
        $this->assign('app_id', input('app_id'));
        return $this->fetch();
    }


    //大厅广告位（下一步）
    public function nextAdvertise()
    {

        $data = input('');
        $user_id = session('userId');
        $ad = new AdvertisingRecord();
        $record = array();
        $creat_time = time();
        $is_select = array();
        foreach ($data['order_times'] as $value) {
            $map = ['order_time' => $value / 1000,
                'status' => array('neq', 0),
                'create_user' => array('neq', $user_id)
            ];

            $is = $ad->where($map)->find();

            if ($is) {
                array_push($is_select, $is['order_time'] * 1000);
            } else {
                $info = [
                    'create_user' => $user_id,
                    'service_id' => 1,
                    'order_time' => $value / 1000,
                    'create_time' => $creat_time,
                    'status' => 1
                ];
                array_push($record, $info);
            }
        }
        if (count($is_select) > 0) {
            $data['no_save'] = json_encode($is_select);
            return json_encode($data);
        }
        $map2 = [
            'create_user' => $user_id,
            'status' => 1
        ];
        $de = $ad->where($map2)->delete();
        $re = $ad->saveAll($record);
        $data['no_save'] = json_encode($is_select);
        return json_encode($data);
    }


    //大厅广告位（提交）
    public function submitAdvertise()
    {
        $ad = new AdvertisingRecord();
        //$personalMessage = new PersonalMessage();
        $user_id = session('userId');
        $data = input('');

        $map = [
            'create_user' => $user_id,
            'status' => 1
        ];
        $record = $ad->where($map)->select();
        if (count($record) > 0) {
            foreach ($record as $value) {
                $value['payment_voucher'] = json_encode($data['payment_voucher']);
                $value['status'] = 2;
                $value->save();
            }
            $message = [
                "title" => "设备服务提示",
                "description" => "您有新的大厅广告位预约，请点击查看",
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/8/can_check/yes/type/1/create_time/' . strtotime($value->create_time)
            ];
            //推送给运营
            $reult = $this->commonSend(1, $message, '', 8);
            if ($reult) {
                $msg = "您的缴费信息正在核对中;核对完成后，将在个人中心中予以反馈;请耐心等待";
                return $this->success('成功', "", $msg);
            } else {
                return $this->error("推送失败");
            }
        } else {
            return $this->error('超时');
        }
    }


    //大厅广告位月份切换
    public function changeMonth()
    {
        $adRecord = new AdvertisingRecord();
        //数字（几月）
        $month = input('month');
        $beginThismonth = mktime(0, 0, 0, $month, 1, date('Y'));

        $endThismonth = mktime(23, 59, 59, $month, date('t'), date('Y'));
        $map['order_time'] = array('between', array($beginThismonth, $endThismonth));
        $map['service_id'] = 1;
        $map['status'] = array('eq', 2);
        $list = $adRecord->where($map)->select();
        $reult = array();
        foreach ($list as $value) {

            array_push($reult, $value['order_time'] * 1000);

        }

        return json_encode($reult);
    }


    //多功能厅
    public function multifunction()
    {
        $adService = new  AdvertisingService();
        $FunctionRoomRecord = new FunctionRoomRecord();
        $user_id = session('userId');
        //取消超时没有上传凭证的预约信息
        $nowtime = time() - 600;
        $map = [
            'status' => 1,
            'create_time' => array('lt', $nowtime)
        ];

        $out_date = $FunctionRoomRecord->where($map)->select();

        foreach ($out_date as $value) {
            $value['status'] = 0;
            $value->save();
        }
        /***************************************/
        //从今天到第七天结束的时间戳数组
        $weeks = array();
        for ($i = 1; $i < 8; $i++) {
            $days = array();
            $time = mktime(0, 0, 0, date('m'), date('d') + $i, date('Y')) - 1;
            $map = ['order_time' => $time, 'status' => array('in', [1, 2])];
            $re = $FunctionRoomRecord->where($map)->select();
            if ($re) {
                foreach ($re as $value) {
                    $days['day'] = $time * 1000;
                    //是当前用户
                    if ($value['create_user'] == $user_id) {
                        //选中未付款
                        if ($value['status'] == 1) {
                            //上午
                            if ($value['date_type'] == 1) {
                                $days['amCheck'] = "yes";
                            } //下午
                            else {
                                $days['pmCheck'] = "yes";
                            }
                        } //已付款
                        else {
                            //上午
                            if ($value['date_type'] == 1) {
                                $days['amBespeak'] = "yes";
                            } //下午
                            else {
                                $days['pmBespeak'] = "yes";
                            }
                        }
                    } //不是当前用户
                    else {
                        //上午
                        if ($value['date_type'] == 1) {
                            $days['amBespeak'] = "yes";
                        } //下午
                        else {
                            $days['pmBespeak'] = "yes";
                        }
                    }
                }

                $days['amBespeak'] = isset($days['amBespeak']) ? $days['amBespeak'] : "no";
                $days['pmBespeak'] = isset($days['pmBespeak']) ? $days['pmBespeak'] : "no";
                $days['amCheck'] = isset($days['amCheck']) ? $days['amCheck'] : "no";
                $days['pmCheck'] = isset($days['pmCheck']) ? $days['pmCheck'] : "no";


            } else {
                $days['day'] = $time * 1000;
                $days['amBespeak'] = "no";
                $days['pmBespeak'] = "no";
                $days['amCheck'] = "no";
                $days['pmCheck'] = "no";
            }
            array_push($weeks, $days);
        }
        $this->assign('data', json_encode($weeks));
        $this->assign('app_id', input('app_id'));
        return $this->fetch();
    }

    //多功能厅预定先生成数据
    public function nextFunctionRoom()
    {
        $data = input('');
        $user_id = session('userId');
        $frr = new FunctionRoomRecord();
        $record = array();
        $create_time = time();
        $map = [
            'create_user' => $user_id,
            'status' => 1
        ];
        $is_select = array();
        foreach ($data as $value) {
            if ($value['amCheck'] == "yes") {
                $map = ['order_time' => $value['day'] / 1000,
                    'status' => array('neq', 0),
                    'create_user' => array('neq', $user_id),
                    'date_type' => 1
                ];
                $is1 = $frr->where($map)->find();
                if ($is1) {
                    $map = [
                        'day' => $is1['order_time'] * 1000,
                        'type' => 'am'
                    ];
                    array_push($is_select, $map);
                } else {

                    $info = [
                        'create_user' => $user_id,
                        'service_id' => 2,
                        'order_time' => $value['day'] / 1000,
                        'create_time' => $create_time,
                        'status' => 1,
                        'date_type' => 1

                    ];
                    array_push($record, $info);

                }

            }
            if ($value['pmCheck'] == "yes") {
                $map = ['order_time' => $value['day'] / 1000,
                    'status' => array('neq', 0),
                    'create_user' => array('neq', $user_id),
                    'date_type' => 2
                ];
                $is2 = $frr->where($map)->find();
                if ($is2) {
                    $map = [
                        'day' => $is1['order_time'] * 1000,
                        'type' => 'pm'
                    ];
                    array_push($is_select, $map);

                } else {
                    $info = [
                        'create_user' => $user_id,
                        'service_id' => 2,
                        'order_time' => $value['day'] / 1000,
                        'create_time' => $create_time,
                        'status' => 1,
                        'date_type' => 2
                    ];
                    array_push($record, $info);

                }
            }

        }
        if (count($is_select) > 0) {
            $data['no_save'] = json_encode($is_select);
            return json_encode($data);
        }
        $map2 = [
            'status' => array('eq', 1),
            'create_user' => $user_id
        ];

        $de = $frr->where($map2)->delete();
        $re = $frr->saveAll($record);
        $data['no_save'] = json_encode($is_select);
        return json_encode($data);
    }

    //二楼多功能厅（提交）
    public function submitFunctionRoom()
    {
        $ad = new FunctionRoomRecord();
        //$personalMessage = new PersonalMessage();
        $user_id = session('userId');
        $data = input('');
        $map = [
            'create_user' => $user_id,
            'status' => 1
        ];
        $record = $ad->where($map)->select();
        if (count($record) > 0) {
            foreach ($record as $value) {
                $value['payment_voucher'] = json_encode($data['payment_voucher']);
                $value['status'] = 2;
                $value->save();
            }
            $msg = "您的缴费信息正在核对中;核对完成后，将在个人中心中予以反馈;请耐心等待";
            $message = [
                "title" => "设备服务提示",
                "description" => "您有新的二楼多功能厅预约申请，请点击查看",
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/8/can_check/yes/type/2/create_time/' . strtotime($value->create_time)
            ];
            //推送给运营
            $reult = $this->commonSend(1, $message, '', 8);
            if ($reult) {
                //成功预约一次设备服务，获得1个积分；
                WechatUser::where('userid', session('userId'))->setInc('score', 3);
                $message = "积分服务提示\n成功预约一次设备服务，积分+3！";

                //并记录
                $point = new  ExchangePoint();
                $map = [
                    'userid' => session('userId'),
                    'content' => '多功能厅租赁',
                    'score' => 3,
                    'create_time' => time(),
                    'park_id' => session('park_id'),
                    'status' => 0,
                    'type' => 2

                ];
                $point->save($map);


                //推送给自己
                $this->sendMessageToFeature($message, session('userId'));
                return $this->success('成功', "", $msg);
            } else {
                return $this->error("推送失败");
            }

        } else {
            return $this->error('超时');
        }
    }

    //led 屏服务
    public function led()
    {
        $user_id = session('userId');
        $led = new LedRecord();
        //取消超时没有上传凭证的预约信息
        $nowtime = time() - 600;
        $map = [
            'status' => 1,
            'create_time' => array('lt', $nowtime)
        ];

        $out_date = $led->where($map)->select();

        foreach ($out_date as $value) {
            $value['status'] = 0;
            $value->save();
        }
        /* **************************************/
        //明天的时间
        $Today = mktime(8, 0, 0, date('m'), date('d') + 1, date('Y'));
        $map = [
            'create_user' => $user_id,
            'status' => 1,
        ];
        //用户约成功的
        $all_check2 = array();
        $map2 = [
            'create_user' => $user_id,
            'status' => 2,
        ];
        $usersuccess = $led->where($map2)->select();
        foreach ($usersuccess as $value) {
            $data = [

                'interval' => $value['date_type'],
                'day' => $value['order_time'] * 1000
            ];
            array_push($all_check2, $data);
        }

        //用户所有选中的
        $user_check = $led->where($map)->select();
        $user_check2 = array();
        foreach ($user_check as $value) {
            $info = [
                'day' => $value['order_time'] * 1000,

                'interval' => $value['date_type']
            ];
            array_push($user_check2, $info);
        }
        $map['create_user'] = array('neq', $user_id);
        $map['status'] = array('in', [1, 2]);
        $map['order_time'] = $Today;
        //今天已选的
        $all_check = $led->where($map)->select();
        foreach ($all_check as $value) {
            $data = [

                'interval' => $value['date_type'],
                'day' => $Today * 1000
            ];
            array_push($all_check2, $data);
        }
        $this->assign('app_id', input('app_id'));
        $this->assign('other', json_encode($all_check2));
        $this->assign('user', json_encode($user_check2));
        return $this->fetch();
    }

    //led灯下一步
    public function nextLed()
    {
        $data = input('');
        $user_id = session('userId');

        $ad = new LedRecord();
        $record = array();
        $creat_time = time();
        $is_select = array();
        foreach ($data as $value) {
            $map = ['order_time' => $value['day'] / 1000,
                'status' => array('neq', 0),
                'create_user' => array('neq', $user_id),
                'date_type' => $value['interval']
            ];
            $is = $ad->where($map)->find();

            if ($is) {
                array_push($is_select, $is['order_time']);
            } else {
                $info = [
                    'create_user' => $user_id,
                    'service_id' => 3,
                    'order_time' => $value['day'] / 1000,
                    'create_time' => $creat_time,
                    'status' => 1,
                    'date_type' => $value['interval']
                ];
                array_push($record, $info);
            }
        }
        if (count($is_select) > 0) {
            $data['no_save'] = json_encode($is_select);
            return json_encode($data);
        }
        $map2 = [
            'create_user' => $user_id,
            'status' => 1
        ];
        $de = $ad->where($map2)->delete();
        $re = $ad->saveAll($record);
        $data['no_save'] = json_encode($is_select);

        return json_encode($data);
    }

// led灯的日期切换
    public function changeLed()
    {
        $data2 = input('day');

        $user_id = session('userId');
        $led = new LedRecord();
        $map['create_user'] = array('neq', $user_id);
        $map['status'] = array('neq', 0);
        $map['order_time'] = $data2 / 1000;


        //用户约成功的
        $all_check2 = array();
        $map2 = [
            'create_user' => $user_id,
            'status' => 2,
        ];
        $usersuccess = $led->where($map2)->select();
        foreach ($usersuccess as $value) {
            $data = [
                'interval' => $value['date_type'],
                'day' => $value['order_time'] * 1000
            ];
            array_push($all_check2, $data);
        }
        //今天已选的
        $all_check = $led->where($map)->select();
        foreach ($all_check as $value) {
            $data = [
                'interval' => $value['date_type'],
                'day' => $data2
            ];
            array_push($all_check2, $data);
        }


        return json_encode($all_check2);


    }

    //Led灯提交
    public function submitLed()
    {
        $ad = new LedRecord();
        //$personalMessage = new PersonalMessage();
        $user_id = session('userId');
        $data = input('');
        $map = [
            'create_user' => $user_id,
            'status' => 1
        ];
        $record = $ad->where($map)->select();
        if (count($record) > 0) {
            foreach ($record as $value) {
                $value['payment_voucher'] = json_encode($data['payment_voucher']);
                $value['status'] = 2;
                $value->save();
            }
            $msg = "您的缴费信息正在核对中;核对完成后，将在个人中心中予以反馈;请耐心等待";
            $message = [
                "title" => "设备服务提示",
                "description" => "您有新的大堂LED屏预约申请，请点击查看",
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/8/can_check/yes/type/3/create_time/' . strtotime($value->create_time)
            ];
            //推送给运营和物业
            $reult = $this->commonSend(1, $message, '', 8);
            if ($reult) {
                //成功预约一次设备服务，获得1个积分；
                WechatUser::where('userid', session('userId'))->setInc('score', 1);
                $message = "积分服务提示\n成功预约一次设备服务，积分+1！";

                //并记录
                $point = new  ExchangePoint();
                $map = [
                    'userid' => session('userId'),
                    'content' => 'LED屏租赁',
                    'score' => 1,
                    'create_time' => time(),
                    'park_id' => session('park_id'),
                    'status' => 0,
                    'type' => 2

                ];
                $point->save($map);

                //推送给自己
                $reult = $this->sendMessageToFeature( $message, session('userId'));
                return $this->success('成功', "", $msg);
            } else {
                return $this->error("推送失败");
            }
        } else {
            return $this->error('超时');
        }
    }


    //公共场所全部广告记录分类
    public function record()
    {
        $type = input('t');
        $user_id = session('userId');
        $service = new AdvertisingService();
        $ad = new AdvertisingRecord();
        $fs = new FunctionRoomRecord();
        $led = new LedRecord();
        $data = array();
        switch ($type) {
            //大厅广告记录
            case 1:
                $data = array();
                $time = array();
                $create_time = array();
                $serviceInfo = $service->where('id', 1)->find();
                $list = $ad->where(['create_user' => $user_id, 'status' => ['neq', -1]])->order('create_time desc')->select();
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
                        'create_time' => strtotime($onetime) * 1000,
                        'price' => count($map) * $serviceInfo['price'],
                        'url' => '/index/service/historyDetail/appid/8/can_check/no/type/1/create_time/' . strtotime($onetime)
                    ];
                    $re['day'] = "";

                    foreach ($map as $value) {
                        $re['day'] .= date('Y-m-d', $value['order_time']) . " ";
                    }
                    if ($map[0]['status'] == 0) {

                        $re['status'] = "被取消";
                    } else if ($map[0]['status'] == 1) {
                        $re['status'] = "还未上传凭证";

                    } else {
                        $re['status'] = "预约成功";
                    }

                    array_push($data, $re);

                }
                break;

            //二楼多功能厅
            case 2:
                $data = array();
                $time = array();
                $create_time = array();
                $serviceInfo = $service->where('id', 2)->find();
                $list = $fs->where(['create_user' => $user_id, 'status' => ['neq', -1]])->order('create_time desc')->select();
                //所有的创建时间
                foreach ($list as $l) {
                    array_push($create_time, $l['create_time']);
                }

                //数组去重
                $time = array_values(array_unique($create_time));
                foreach ($time as $onetime) {
                    $map = array();
                    $times = array();
                    foreach ($list as $info) {
                        if ($info['create_time'] == $onetime) {
                            array_push($map, $info);
                            array_push($times, $info['order_time']);
                        }
                    }
                    $price = 0;
                    $timelist = array_count_values($times);

                    foreach ($timelist as $key => $value) {
                        if ($value == 1) {
                            $price = $price + 500;
                        } else if ($value == 2) {
                            $price = $price + 800;
                        }

                    }
                    $re = [
                        'create_time' => strtotime($onetime) * 1000,
                        'price' => $price,
                        'url' => '/index/service/historyDetail/appid/8/can_check/no/type/2/create_time/' . strtotime($onetime)
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
                        foreach ($map as $value2) {
                            if ($value == $value2['order_time']) {

                                if ($value2['date_type'] == 1) {
                                    $re['day'] .= "上午 ";
                                } elseif ($value2['date_type'] == 2) {
                                    $re['day'] .= "下午 ";
                                }
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

                    array_push($data, $re);

                }

                break;
            //大堂led灯
            case 3:
                $data = array();
                $time = array();
                $create_time = array();
                $serviceInfo = $service->where('id', 3)->find();
                $list = $led->where(['create_user' => $user_id, 'status' => ['neq', -1]])->order('create_time desc')->select();
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
                        'create_time' => strtotime($onetime) * 1000,
                        'price' => count($map) * $serviceInfo['price'],
                        'url' => '/index/service/historyDetail/appid/8/can_check/no/type/3/create_time/' . strtotime($onetime)
                    ];
                    $re['day'] = "";
                    //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
                    $map_time = array();
                    foreach ($map as $m) {
                        array_push($map_time, $m['order_time']);
                    }
                    $mtime_list = array_values(array_unique($map_time));

                    foreach ($mtime_list as $value) {
                        $re['day'] .= date('Y-m-d', $value) . "| ";
                        foreach ($map as $value2) {
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
                        }
                    }
                    if ($map[0]['status'] == 0) {

                        $re['status'] = "被取消";
                    } else if ($map[0]['status'] == 1) {
                        $re['status'] = "还未上传凭证";

                    } else {
                        $re['status'] = "预约成功";
                    }
                    array_push($data, $re);
                }
                break;
        }
        $this->assign('data', json_encode($data));
        return $this->fetch();
    }

    /*物业报修*/
    public function repair()
    {
        $userid = session("userId");
        $parkid = session('park_id');
        $property = new PropertyServer();
        //$personalMessage = new PersonalMessage();
        $data = input('post.');
        $data['create_time'] = time();
        $data['user_id'] = $userid;
        $data['park_id'] = $parkid;
        $data["image"] = json_encode($data["payment_voucher"]);
        $res = $property->allowField(true)->save($data);
        if ($res) {
            //todo： 推送点击到详情页面代码
            //服务类型 1为空调，2为电梯，3为其他
            switch ($data['type']) {
                case 1:
                    $data['type_text'] = "空调维修";
                    break;
                case 2:
                    $data['type_text'] = "电梯维修";
                    break;
                case 3:
                    $data['type_text'] = "其他维修";
                    break;
            }
            $message = [
                "title" => "物业报修提示",
                "description" => "服务类型：" . $data['type_text'] . "\n服务地点：" . $data['address'] . "\n联系人员：" . $data['name'] . "\n联系电话：" . $data['mobile'],
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/2/can_check/yes/id/' . $property->getLastInsID()
            ];

            /*$new = [
                "title" => $message['title'],
                "message" => $message['description'],
                'type' => 2,
                'park_id' => session('park_id'),
                'app_id' => $data['appid'],
                'sid' => $property->id,
                'create_time' => time(),
                'status' => 0
            ];
            $savemessage = $personalMessage->save($new);*/

            //推送给物业
            $reult = $this->commonSend(3, $message, '', 2);

            if ($reult) {
                return $this->success("报修成功");
            } else {
                return $this->error("推送失败");
            }
        } else {

            return $this->error("报修失败");
        }
    }

    /*保洁服务*/
    public function clear()
    {
        $userid = session("userId");
        $parkid = session('park_id');
        $data = input('post.');
        $time = date("w", strtotime(input("dateStr")));
        if ($time == 6 || $time == 0) {
            return $this->error("请在工作日预约");
        }
        $data['clear_time'] = strtotime(input("dateStr"));
        $data['user_id'] = $userid;
        $data['park_id'] = $parkid;
        $data["image"] = json_encode($data["payment_voucher"]);

        $property = new PropertyServer();
        //$personalMessage = new PersonalMessage();
        $res = $property->allowField(true)->save($data);
        if ($res) {
            //todo： 推送点击到详情页面代码
            $message = [
                "title" => "保洁服务提示",
                "description" => "服务地点：" . $data['address'] . "\n服务时间：" . date('m月d日', $data['clear_time']) . "\n联系人员：" . $data['name'] . "\n联系电话：" . $data['mobile'],
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/4/can_check/yes/id/' . $property->getLastInsID()
            ];
            //推送物业
            $reult = $this->commonSend(3, $message, '', 4);

            /*$new = [
                "title" => $message['title'],
                "message" => $message['description'],
                'type' => 2,
                'park_id' => session('park_id'),
                'app_id' => $data['app_id'],
                'sid' => $property->id,
                'create_time' => time(),
                'status' => 0
            ];
            $savemessage = $personalMessage->save($new);*/
            if ($reult) {
                return $this->success("预约成功");
            } else {
                return $this->error("推送失败", '', json_encode($reult));
            }
        } else {

            return $this->error("报修失败");
        }
    }

    /*物业报修记录*/
    public function repairRecord()
    {
        $info = [];
        $userId = session("userId");
        $park_id = session('park_id');
        $types = [1 => '空调报修', 2 => "电梯报修", 3 => "其他报修"];
        $list = PropertyServer::where(['type' => ['<', 4], 'status' => ['>=', 0], 'user_id' => $userId, 'park_id' => $park_id])->order('id desc')->paginate();
        foreach ($list as $k => $v) {
            $info[$k] = [
                'id' => $v['id'],
                'name' => $types[$v['type']],
                'time' => date('Y-m-d', $v['create_time']),
                'status' => $v['status'],
                "url" => '/index/service/historyDetail/appid/2/can_check/no/id/' . $v['id']

            ];
        }
        return $info;
    }

    /*保洁服务记录*/
    public function clearRecord()
    {
        $info = [];
        $userId = session("userId");
        $park_id = session('park_id');
        $list = PropertyServer::where(['type' => 4, 'status' => ['>=', 0], 'user_id' => $userId, 'park_id' => $park_id])->order('clear_time desc')->paginate();
        foreach ($list as $k => $v) {
            $info[$k] = [
                'id' => $v['id'],
                'type' => "保洁服务",
                'time' => date("Y-m-d", $v['clear_time']),
                'name' => $v['address'],
                'status' => $v['status'],
                "url" => '/index/service/historyDetail/appid/4/can_check/yes/id/' . $v['id']
            ];
        }
        return $info;
    }

    /*物业保洁下拉刷新*/
    public function listmore()
    {
        $type = input('type');
        $len = input('length');
        if ($type == 1) {

            $list = PropertyServer::where(['type' => ['<', 4], 'status' => ['in', [0, 1, 2]]])->order('create_time desc')->limit($len, 6)->paginate();
            int_to_string($list, ['type' => [1 => '空调报修', 2 => "电梯报修", 3 => "其他报修"]]);
        } else {

            $list = PropertyServer::where(['type' => 4, 'status' => ['in', [0, 1, 2]]])->order('create_time desc')->limit($len, 6)->paginate();
        }

        return $list;

    }


    //饮水服务
    public function waterService()
    {
        $data = input('post.');
        $waterModel = new WaterModel;
        //$personalMessage = new PersonalMessage();
        $data['userid'] = session('userId');
        $data['park_id'] = session('park_id');
        $watertype = WaterType::get($data['water_id']);
        $data['price'] = $watertype['price'] * $data['number'];
        $result = $waterModel->allowField(true)->validate(true)->save($data);
        if ($result) {
            //todo： 推送点击到详情页面代码
            $message = [
                "title" => "饮水服务提示",
                "description" => "送水地点：" . $data['address'] . "\n送水桶数：" . $data['number'] . "\n联系人员：" . $data['name'] . "\n联系电话：" . $data['mobile'],
                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/3/can_check/yes/id/' . $waterModel->getLastInsID()
            ];
            //推送给物业
            $reult = $this->commonSend(3, $message, '', 3);
            /*$new = [
                "title" => $message['title'],
                "message" => $message['description'],
                'type' => 2,
                'park_id' => session('park_id'),
                'app_id' => $data['appid'],
                'sid' => $waterModel->id,
                'create_time' => time(),
                'status' => 0
            ];
            $savemessage = $personalMessage->save($new);*/
            if ($reult) {
                return $this->success("预约成功");
            } else {
                return $this->error("推送失败");
            }
        } else {
            return $this->error($waterModel->getError());
        }

    }

//饮水服务列表页
    public function waterList()
    {
        $info = [];
        $userid = session('userId');
        $park_id = session('park_id');
        $map = [
            'status' => array('neq', -1),
            'userid' => $userid,
            'park_id' => $park_id
        ];
        $list = WaterModel::where($map)->order('id desc')->paginate();

        foreach ($list as $k => $v) {
            $info[$k] = [
                'id' => $v['id'],
                'name' => $v['name'],
                'time' => date('Y-m-d', $v['create_time']),
                'num' => $v['number'],
                'status' => $v['status'],
                "url" => '/index/service/historyDetail/appid/3/can_check/no/id/' . $v['id']
            ];
        }
        return $info;
    }

    //商标咨询记录
    public function advisoryHistory()
    {
        $info = [];
        $userid = session('userId');
        $park_id = session('park_id');
        $map = [
            'status' => array('neq', -1),
            'userid' => $userid,
            '$park_id' => $park_id

        ];
        $list = TrademarkAdvisory::where($map)->order('create_time desc,status asc')->select();

        foreach ($list as $k => $v) {
            $info[$k] = [
                'id' => $v['id'],
                'name' => $v->user->name,
                'time' => date('Y-m-d', $v['create_time']),
                'status' => $v['status'],

            ];
        }

        return $info;

    }

    //商标查询记录
    public function inquireHistory()
    {
        $info = [];
        $userid = session('userId');
        $park_id = session('park_id');
        $map = [
            'status' => array('neq', -1),
            'userid' => $userid,
            'park_id' => $park_id
        ];
        $list = TrademarkInquire::where($map)->order('id desc')->select();

        foreach ($list as $k => $v) {
            $info[$k] = [
                'id' => $v['id'],
                'type' => $v['type'],
                'time' => date('Y-m-d', $v['create_time']),
                'status' => $v['status'],
            ];
        }

        return $info;
    }

    //专利申请首页 app_id=21
    public function patent()
    {
        $this->assign('park_id', session('park_id'));
        return $this->fetch();
    }

    public function technicalDocument()
    {
        return $this->fetch('technical_document');
    }

    //专利申请 app_id=21
    public function patentInfo()
    {
        if (IS_POST) {
            $data = input('');
            $patent = new Patent();
            $result = $patent->_checkData(input('type'), $data);
            if ($result == false) {
                return $this->error('接口调用失败，参数缺失');
            }
            if (count($data['id_card']) != 2) {
                return $this->error('接口调用失败，身份证图片未传齐');
            }
            $data['id_card'] = json_encode($data['id_card']);
            if (isset($data['product_img'])) {
                if (count($data['product_img']) != 7 && $data['type'] == 3) {
                    return $this->error('接口调用失败，设计图片未上传齐');
                }
                $data['product_img'] = json_encode($data['product_img']);
            }
            $data['create_user'] = session('userId');
            $data['park_id'] = session('park_id');

            if (isset($data['id'])) {
                $data['status'] = 0;
                $data['reply'] = "";
                $res = $patent->where('id', $data['id'])->Update($data);

            } else {
                $res = $patent->save($data);
            }
            if ($res) {
                //专利申请提示11月28日您有一条新的专利申请服务待处理，点击查看详情
                $message = [
                    "title" => "专利申请提示",
                    "description" => "您有一条新的专利申请服务待处理，点击查看详情",
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetailCompany/appid/21/can_check/no/id/' . $patent->getLastInsID()
                ];
                //推送给运营
                $reult = $this->commonSend(1, $message, '', 21);
                return $this->success('提交成分');
            } else {
                return $this->error('提交失败');
            }
        } else {
            $data = input('');
            if (isset($data['id'])) {
                $data2 = Patent::get($data['id']);
                $data2['id_card'] = json_decode($data2['id_card']);
                $data2['product_img'] = json_decode($data2['product_img']);

                $this->assign('info', json_encode($data2));
            } else {
                $this->assign('info', json_encode($data));
            }

            return $this->fetch('patent_info');
        }
    }
    //商标类别
    public function marktype()
    {

        return $this->fetch();
    }

    //版权申请 app_id=22
    public function copyRightInfo()
    {
        $data = input('');
        $a = $data;
        if (IS_POST) {
            if ($data['type'] == 1) {
                //艺术作品
                $copy = new CopyrightArt();
                $data['product_img'] = json_encode($data['product_img']);

            } elseif ($data['type'] == 2) {
                //软件著作
                $copy = new CopyrightSoft();

            } elseif ($data['type'] == 3) {
                //软件撰写
                $copy = new CopyrightSoftwrite();
            }
            $result = $copy->_checkData($data);
            if ($result == false) {
                return $this->error('接口调用失败，参数缺失');
            }
            $data['userid'] = session('userId');
            $data['park_id'] = session('park_id');
            if (isset($data['id'])) {
                $data['status'] = 0;
                $data['reply'] = "";
                $res = $copy->allowField(true)->save($data,['id',$data['id']]);
            } else {
                $res = $copy->allowField(true)->save($data);
            }

            if ($res) {
                //专利申请提示11月28日您有一条新的专利申请服务待处理，点击查看详情
                $message = [
                    "title" => "版权申请提示",
                    "description" => "您有一条新的版权申请服务待处理，点击查看详情",
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetailCompany/appid/22/can_check/yes/type/' . $data
                        ['type'] . '/id/' . $copy->getLastInsID()
                ];
                //推送给运营
                $reult = $this->commonSend(1, $message, '', 21);
                return $this->success('提交成分');
            } else {
                return $this->error('提交失败');
            }
        } else {
            if ($data['type'] == 1) {
                //艺术作品
                $copy = new CopyrightArt();
            } elseif ($data['type'] == 2) {
                //软件著作
                $copy = new CopyrightSoft();

            } elseif ($data['type'] == 3) {
                //软件撰写
                $copy = new CopyrightSoftwrite();
            }
            if (isset($data['id'])) {
                $data2 = $copy->where('id', $data['id'])->find();
                $data2['type']=$data['type'];
                if ($data['type'] == 1) {
                    //艺术作品
                    $data2['product_img'] = json_decode($data2['product_img']);

                }
                //return json_encode($data2);
                $this->assign('info', json_encode($data2));
            } else {
                //return json_encode($data);
                $this->assign('info', json_encode($data));
            }
            return $this->fetch('copy_right_info');
        }
    }
//饮水服务详情页()
    public function waterDetail()
    {
        $id = input('id');
        $result = WaterModel::where('id', 'eq', $id)->find();
        $this->assign('res', $result);
        //       echo json_encode($result);exit;
        return $this->fetch();
    }
    /*//电话宽带
    public function broadbandPhone()
    {
        $data = input('');
        //echo json_encode($data);exit;
        $broadbandModel = new BroadbandModel;
        $data['user_id'] = session('userId');

        $result = $broadbandModel->allowField(true)->validate(true)->save($data);
        if ($result) {
            return $this->success("预约成功");
        } else {
            return $this->error($broadbandModel->getError());
        }

    }*/
    //费用缴纳
    public function feedetail()
    {
        $type = input('t');
        $appid = input('id');
        $userid = session("userId");
        $property = [];
        $userinfo = WechatUser::where(['userid' => $userid])->find();
        $departmentId = $userinfo['department'];
        $map = ['company_id' => $departmentId, 'type' => $type];
        if ($type == 2) {
            $info = FeePayment::where($map)->order('id desc')->find();
            if ($info) {
                FeePayment::where('id', $info['id'])->update(['onclick' => 1, 'onclick_time' => time()]);
                array_push($property, "http://" . $_SERVER['HTTP_HOST'] . $info['images']);
                $member = $info['number'];
                $memberArr = explode('|', $member);
                if (!in_array($userid, $memberArr)) {
                    $member = $member . $userid . "|";
                    FeePayment::where('id', $info['id'])->update(['number' => $member]);
                }
            }
            $info['payment_voucher'] = isset($info['payment_voucher']) ? unserialize($info["payment_voucher"]) : "";
            $info['appid'] = $appid;
            $info['title'] = '物业费';
            $this->assign('image', json_encode($property));
            $this->assign('info', json_encode($info));
        } else {
            $info = FeePayment::where($map)->order('id desc')->find();
            $image = [];
            if ($info) {
                FeePayment::where('id', $info['id'])->update(['onclick' => 1, 'onclick_time' => time()]);
                array_push($image, "http://" . $_SERVER['HTTP_HOST'] . $info['images']);
                $member = $info['number'];
                $memberArr = explode('|', $member);
                if (!in_array($userid, $memberArr)) {
                    $member = $member . $userid . "|";
                    FeePayment::where('id', $info['id'])->update(['number' => $member]);
                }
            }
            if ($type == 1) {
                $info['title'] = '水电费';
            } else if ($type == 3) {
                $info['title'] = '房租费';
            } else if ($type == 4) {
                $info['title'] = '功耗费';
            }
            $info['appid'] = $appid;
            $info['payment_voucher'] = isset($info['payment_voucher']) ? unserialize($info["payment_voucher"]) : "";

            $this->assign('image', json_encode($image));
            $this->assign('info', json_encode($info));
        }
        return $this->fetch();
    }

    /*记录*/
    public function history()
    {
        $path = 'history';
        $info = [];
        $appid = input('id');
        $type = input('type');
        $companyapp = new CompanyApplication();
        $company_type = $companyapp->getCompanyType($appid);
        if ($appid == 1) {
            $userid = session("userId");
            $userinfo = WechatUser::where(['userid' => $userid])->find();
            $departmentId = $userinfo['department'];
            $map = ['company_id' => $departmentId, 'type' => $type];
            $list = FeePayment::where($map)->order('id desc')->select();
            foreach ($list as $k => $v) {
                $info[$k] = [
                    'id' => $v['id'],
                    'name' => $v['name'],
                    'status' => $v['status'],
                    'time' => $v['expiration_time'],
                    'pay' => $v['fee'],
                    'url' => '/index/service/historyDetail/appid/' . $appid . '/can_check/no/id/' . $v['id']
                ];
            }
        } elseif ($appid == 2) {
            $info = $this->repairRecord();

        } elseif ($appid == 3) {

            $info = $this->waterList();

        } elseif ($appid == 4) {

            $info = $this->clearRecord();
        } elseif ($appid == 6) {
            $info = $this->carRecord();
        } elseif ($appid == 7) {

            $info = $this->pillarRecord();
        } elseif ($appid == 12) {
            $path = 'history_company';

            //type =1 商标查询   2 商标咨询
            if ($type == 1) {
                $info = $this->inquireHistory();
            } elseif ($type == 2) {
                $info = $this->advisoryHistory();
            }
        } elseif ($appid == 21) {
            $path = 'history_company';
            //专利申请
            $patent = new Patent();
            $info = $patent->patentHistory();
        } elseif ($appid == 22) {
            $path = 'history_company';
            //美术作品
            $CopyrightArt = new CopyrightArt();
            $info1 = $CopyrightArt->copyHistory();
            //软著登记
            $CopyrightSoft = new CopyrightSoft();
            $info2 = $CopyrightSoft->copyHistory();
            //软著撰写
            $CopyrightSoftwrite = new CopyrightSoftwrite();
            $info3 = $CopyrightSoftwrite->copyHistory();
            $info = array_merge($info1, $info2, $info3);
        }
        //物业服务（记录详情页跳转地址为historyDetail）
        if ($company_type == 0) {
            foreach ($info as $k => $value) {
                if (empty($type)) {
                    $info[$k]['url'] = '/index/service/historyDetail/appid/' . $appid . '/can_check/no/id/' . $info[$k]['id'];
                } else {
                    $info[$k]['url'] = '/index/service/historyDetail/appid/' . $appid . '/can_check/no/type/' . $type . '/id/' . $info[$k]['id'];
                }
            }

        } //企业服务（记录详情页跳转地址为historyDetailCompany）
        elseif ($company_type == 1) {
            foreach ($info as $k => $value) {
                //给版权的特殊url 中type
                if ($appid == 22) {
                    $type = $value['type'];
                }


                if (empty($type)) {
                    $info[$k]['url'] = '/index/service/historyDetailCompany/appid/' . $appid . '/can_check/no/id/' . $info[$k]['id'];
                } else {
                    $info[$k]['url'] = '/index/service/historyDetailCompany/appid/' . $appid . '/can_check/no/type/' . $type . '/id/' . $info[$k]['id'];
                }
            }
        }
        $this->assign('info', json_encode($info));
        $this->assign('appId', $appid);
        return $this->fetch($path);
    }

    /**企业服务记录列表**/
    public function historyCompany()
    {
        return $this->fetch('history_company');
    }

    /*付款*/
    public function feeinfo()
    {
        $parkid = session('park_id');
        if (IS_POST) {
            $feePayment = new FeePayment();
            //$personalMessage = new PersonalMessage();
            $id = input('id');
            $appid = input('app_id');
            $data = input('post.');
            $datas["payment_voucher"] = serialize($data["payment_voucher"]);
            $datas['invoice_type'] = (int)$data['invoice']['invoice_type'];
            $datas['taxpayer_number'] = $data['invoice']['taxpayer_number'];
            $datas['contact_address'] = $data['invoice']['contact_address'];
            $datas['bank'] = $data['invoice']['bank'];
            $datas['account_opening'] = $data['invoice']['account_opening'];
            $datas['mobile'] = $data['invoice']['mobile'];
            $datas['department'] = $data['invoice']['department'];
            $datas['status'] = 1;
            $res2 = $feePayment->where('id', $id)->find();
            $res = $feePayment->where('id', $id)->update($datas);
            //费用类型：1为水电费，2为物业费，3位为房租费，4位公耗费
            switch ($res2['type']) {
                case 1:
                    $res2['type_text'] = "水电费";
                    break;
                case 2:
                    $res2['type_text'] = "物业费";
                    break;
                case 3:
                    $res2['type_text'] = "房租费";
                    break;
                case 4:
                    $res2['type_text'] = "公耗费";
                    break;
            }
            if ($res2) {
                $message = [
                    "title" => $res2['type_text'] . "缴纳确认提示",
                    "description" => $res2['name'] . "企业\n" . $res2['type_text'] . "\n到期时间：" . $res2['expiration_time'] . "\n应缴费用：" . $res2['fee'] . "元",
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/1/can_check/yes/id/' . $id
                ];
                //推送给运营
                $reult = $this->commonSend(1, $message, "", $appid);
                /*$new = [
                    "title" => $message['title'],
                    "message" => $message['description'],
                    'type' => 2,
                    'park_id' => session('park_id'),
                    'app_id' => $data['app_id'],
                    'sid' => $id,
                    'create_time' => time(),
                    'status' => 0
                ];
                $savemessage = $personalMessage->save($new);*/
            }

            $msg = "您的缴费信息正在核对中;核对完成后，将在个人中心中予以反馈;请耐心等待，确认成功后;发票将由园区工作人员在15个工作日之内送达企业";
            return $this->success('成功', "", $msg);
        } else {
            $parkInfo = Park::where('id', $parkid)->find();
            $this->assign('parkInfo', json_encode($parkInfo));
            return $this->fetch();
        }
    }

    /**
     *
     * 商标查询
     */
    public function trademark()
    {
        if (IS_POST) {
            $userid = session('userId');
            $park_id = session('park_id');
            $ti = new TrademarkInquire();
            $info = input('');
            $info['status'] = 0;
            $info['create_time'] = time();
            $info['userid'] = $userid;
            $info['park_id'] = $park_id;
            $info['submit_img'] = json_encode($info['submit_img']);
            $re = $ti->save($info);
            if ($re) {
                //用户提交查询申请后，推送给园区服务人员，推送：商标查询服务提示/时间/您有一条新的商标查询服务待处理，请登录后台查看；
                $message = [
                    "title" => "商标查询服务提示",
                    "description" => "您有一条新的商标查询服务待处理，点击查看详情",
                    "url" => 'https://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetailCompany/appid/12/can_check/yes/type/1/id/' . $ti->getLastInsID(),
                ];
                //推送给运营
                $reult = $this->commonSend(1, $message, "", 12);

                return $this->success('成功');

            } else {
                return $this->error('失败', '', $ti->getError());
            }
        } else {
            return $this->fetch();
        }
    }

    /**
     *
     * 商标咨询
     */
    public function trademarkAdvisory()
    {
        if (IS_POST) {
            $userid = session('userId');
            $park_id = session('park_id');
            $ta = new TrademarkAdvisory();
            $info = input('');
            $info['status'] = 0;
            $info['create_time'] = time();
            $info['userid'] = $userid;
            $info['park_id'] = $park_id;
            $re = $ta->save($info);
            if ($re) {
                //咨询提交后，推送给园区服务人员，推送：商标查询服务咨询提示/时间/您有一条商标查询服务咨询待处理，请登录后台查看；
                $message = [
                    "title" => "商标查询服务咨询提示",
                    "description" => "您有一条商标查询服务咨询待处理，点击查看详情",
                    "url" => 'https://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetailCompany/appid/12/can_check/yes/type/2/id/' . $ta->getLastInsID(),
                ];

                //推送给运营
                $reult = $this->commonSend(1, $message, "", 12);
                return $this->success('成功');

            } else {
                return $this->error('失败', '', $ta->getError());
            }
        } else {
            return $this->fetch();
        }
    }


    /* 企业服务的历史记录详情页*/
    public function historyDetailCompany()
    {
        $id = input('id');
        $appid = input('appid');
        $can_check = empty(input('can_check')) ? "no" : input('can_check');
        /*商标查询(type=1)/商标咨询(type=2)*/
        if ($appid == 12) {
            $type = input('type');
            if ($type == 1) {
                $ti = new TrademarkInquire();
                $info = $ti->InquireHistoryDetail($id, $appid);
            } elseif ($type == 2) {
                $ta = new TrademarkAdvisory();
                $info = $ta->AdvisoryHistoryDetail($id, $appid);
            }
        } //专利查询
        elseif ($appid == 21) {
            $patent = new Patent();
            $info = $patent->patentHistoryDetail($id, $appid);
        } //版权申请($type (1 艺术作品 ，2 软著登记 ，3软著撰写))
        elseif ($appid == 22) {
            $type = input('type');
            switch ($type) {
                case 1:
                    $copy = new CopyrightArt();
                    break;
                case 2:
                    $copy = new CopyrightSoft();
                    break;
                case 3:
                    $copy = new CopyrightSoftwrite();
                    break;
            }
            $info = $copy->copyHistoryDetail($id, $appid);
        } //企业服务
        else if (9 < $appid && $appid < 19 && $appid != 12) {
            $companyservice = new CompanyService();
            $info = $companyservice->historyDetail($id, $appid);
        }
        //echo json_encode($info);
        $this->assign('can_check', $can_check);
        $this->assign('type', json_encode($appid));
        $this->assign('info', json_encode($info));
        $this->assign('park_id', session('park_id'));

        return $this->fetch("history_detail_company");
    }

    /* 记录详情 物业服务*/
    public function historyDetail()
    {
        $id = input('id');
        $appid = input('appid');
        $can_check = empty(input('can_check')) ? "no" : input('can_check');
        $service = new AdvertisingService();
        //费用缴纳
        if ($appid == 1) {

            $infos = FeePayment::get($id);
            // echo json_encode($infos);
            $info = [
                'name' => $infos['name'],
                'expiration_time' => $infos['expiration_time'],
                'img' => isset($infos['payment_voucher']) ? unserialize($infos['payment_voucher']) : "",
                'id' => $infos['id'],
                'status' => $infos['status'],
                'fee' => $infos['fee'],
                'type' => $infos['type'],
                'invoice_type' => $infos['invoice_type'],
                'taxpayer_number' => $infos['taxpayer_number'],
                'contact_address' => $infos['contact_address'],
                'bank' => $infos['bank'],
                'account_opening' => $infos['account_opening'],
                'mobile' => $infos['mobile'],
                'department' => $infos['department']
            ];
        } //物业维护 $types = [1 => '空调报修', 2 => "电梯报修", 3 => "其他报修"];
        else if ($appid == 2) {
            $info = PropertyServer::get($id);
            $info['image'] = !empty($info['image']) ? json_decode($info['image']) : "";
            $info['proof'] = !empty($info['proof']) ? json_decode($info['proof']) : "";

        } //饮水
        elseif ($appid == 3) {
            $info = WaterService::get($id);
            $info['water_name'] = isset($info->watertype->water_name) ? $info->watertype->water_name : "";
            $info['format'] = isset($info->watertype->format) ? $info->watertype->format : "";
            unset($info['watertype']);
            $info['create_time'] = !empty($info['create_time']) ? date('m月d日 H:i', $info['create_time']) : "";
            $info['check_time'] = !empty($info['check_time']) ? date('m月d日 H:i', $info['check_time']) : "";
            $info['end_time'] = !empty($info['end_time']) ? date('m月d日 H:i', $info['end_time']) : "";
        } elseif ($appid == 4) {
            $info = PropertyServer::get($id);
            $info['image'] = json_decode($info['image']);
        } //车卡
        elseif ($appid == 6) {
            $info = CarparkService::where('id', $id)->find();
            //图片
            $info['img'] = json_decode($info['payment_voucher'], true);
            //费用总计
            $info['all_money'] = $info['money'];
            unset($info['payment_voucher']);
            $park_id = session('park_id');
            $park = Park::where('id', $park_id)->field('carpark_deposit,carpark_price')->find();
            //押金
            $info['carpark_deposit'] = $park['carpark_deposit'];

            if ($info['type'] == 1) {
                $info['money'] = $info['money'] - $info['carpark_deposit'];
            }
        } //充电柱
        elseif ($appid == 7) {
            $service = ElectricityService::get($id);
            $info['electricity_id'] = $service['electricity_id'];//充电柱编号
            $info['name'] = $service['name'];
            $info['mobile'] = $service['mobile'];
            $park_id = session('park_id');
            $park = Park::where('id', $park_id)->field('charging_deposit,charging_price')->find();
            $info['aging'] = $service['aging'];
            $info['carpark_deposit'] = $park['charging_deposit'];//押金
            $info['all_money'] = $service['money'];//总费用
            $info['img'] = json_decode($service['payment_voucher'], true);//图片
            $info['type'] = $service['type'];
            $info['money'] = $service['money'];
            $info['status'] = $service['status'];
            $info['id'] = $service['id'];
            $info['company'] = $service['company'];
            $info['charge_voucher'] = $service['charge_voucher'];//收费凭证
            if ($info['type'] == 1) {
                $info['money'] = $service['money'] - $park['charging_deposit'];
            }
        } elseif ($appid == 8) {
            $type = input('type');
            $create_time = input('create_time');
            $time = "";
            switch ($type) {
                //大堂广告位
                case 1:
                    $list = AdvertisingRecord::where(['create_time' => $create_time, 'status' => array('neq', -1)])->select();
                    $serviceInfo = $service->where('id', 1)->find();
                    foreach ($list as $value) {
                        $time .= date('Y-m-d', $value['order_time']) . "<br>";
                        $value['payment_voucher'] = json_decode($value['payment_voucher']);
                    }
                    $info = [
                        'type_text' => "大堂广告位",
                        'payment_voucher' => $list[0]['payment_voucher'],
                        'order_time' => $time,
                        'create_user' => isset($list[0]->user->name) ? $list[0]->user->name : "",
                        'type' => 1,
                        'status' => $list[0]['status'],
                        'create_time' => $create_time,
                        'price' => count($list) * $serviceInfo['price']
                    ];
                    break;
                //二楼多功能厅
                case 2:
                    $list = FunctionRoomRecord::where(['create_time' => $create_time, 'status' => array('neq', -1)])->select();
                    $serviceInfo = $service->where('id', 2)->find();
                    //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
                    $map_time = array();

                    foreach ($list as $value) {
                        array_push($map_time, $value['order_time']);
                        $value['payment_voucher'] = json_decode($value['payment_voucher']);
                    }
                    $price = 0;
                    $timelist = array_count_values($map_time);
                    foreach ($timelist as $key => $value) {
                        if ($value == 1) {
                            $price = $price + 500;
                        } else if ($value == 2) {
                            $price = $price + 800;
                        }

                    }
                    $mtime_list = array_values(array_unique($map_time));

                    foreach ($mtime_list as $value) {

                        $time .= date('Y-m-d', $value);
                        foreach ($list as $value2) {
                            if ($value == $value2['order_time']) {
                                if ($value2['date_type'] == 1) {
                                    $time .= "上午 ";
                                } elseif ($value2['date_type'] == 2) {
                                    $time .= "下午 ";
                                }
                            }
                        }
                        $time .= '<br>';
                    }
                    $info = [
                        'type_text' => "二楼多功能厅",
                        'payment_voucher' => $list[0]['payment_voucher'],
                        'order_time' => $time,
                        'create_user' => isset($list[0]->user->name) ? $list[0]->user->name : "",
                        'type' => 2,
                        'status' => $list[0]['status'],
                        'create_time' => $create_time,
                        'price' => $price
                    ];
                    break;
                //led屏
                case 3:
                    $list = LedRecord::where(['create_time' => $create_time, 'status' => array('neq', -1)])->select();
                    $serviceInfo = $service->where('id', 3)->find();
                    $map_time = array();
                    foreach ($list as $value) {
                        array_push($map_time, $value['order_time']);
                        $value['payment_voucher'] = json_decode($value['payment_voucher']);
                    }
                    //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
                    $mtime_list = array_values(array_unique($map_time));
                    foreach ($mtime_list as $value) {
                        $time .= date('Y-m-d', $value) . "| ";
                        foreach ($list as $value2) {
                            if ($value == $value2['order_time']) {
                                switch ($value2['date_type']) {
                                    case 1:
                                        $time .= "9:00-10:00 ";
                                        break;
                                    case 2:
                                        $time .= "10:00-11:00 ";
                                        break;
                                    case 3:
                                        $time .= "11:00-12:00 ";
                                        break;
                                    case 4:
                                        $time .= "12:00-13:00 ";
                                        break;
                                    case 5:
                                        $time .= "13:00-14:00 ";
                                        break;
                                    case 6:
                                        $time .= "14:00-15:00 ";
                                        break;
                                    case 7:
                                        $time .= "15:00-16:00 ";
                                        break;
                                    case 8:
                                        $time .= "16:00-17:00 ";
                                        break;
                                    case 9:
                                        $time .= "17:00-18:00 ";
                                        break;
                                }
                            }
                        }
                        $time .= '<br>';
                    }
                    $info = [
                        'type_text' => "LED屏",
                        'payment_voucher' => $list[0]['payment_voucher'],
                        'order_time' => $time,
                        'create_user' => isset($list[0]->user->name) ? $list[0]->user->name : "",
                        'type' => 3,
                        'status' => $list[0]['status'],
                        'create_time' => $create_time,
                        'price' => count($list) * $serviceInfo['price']
                    ];
                    break;
            }
        } /*商标查询(type=1)/商标咨询(type=2)*/
        else if ($appid == 12) {
            $type = input('type');
            if ($type == 1) {
                $info = TrademarkInquire::get($id);
                $app = CompanyApplication::Where('app_id', $appid)->find();
                $info['name'] = $app['name'];
                $info['submit_img'] = !empty($info['submit_img']) ? json_decode($info['submit_img']) : array();
                $info['back_img'] = !empty($info['back_img']) ? json_decode($info['back_img']) : array();
                $info['trademark_type'] = 1;

            } elseif ($type == 2) {
                $info = TrademarkAdvisory::get($id);
                $app = CompanyApplication::Where('app_id', $appid)->find();
                $info['user_name'] = $info['name'];
                $info['name'] = $app['name'];
                $info['trademark_type'] = 2;
            }
        } //企业服务
        else if (9 < $appid && $appid < 19 && $appid != 12) {
            $info = CompanyService::get($id);
            $app = CompanyApplication::Where('app_id', $appid)->find();
            $info['name'] = $app['name'];

        }
        //echo json_encode($info);
        $this->assign('can_check', $can_check);
        $this->assign('type', json_encode($appid));
        $this->assign('info', json_encode($info));
        $this->assign('park_id', session('park_id'));
        return $this->fetch();

    }


    public function _checkData($data)
    {
        if (empty($data)) {
            return false;
        }
        if (!isset($data['company']) ||
            !isset($data['name']) ||
            !isset($data['mobile']) ||
            !isset($data['app_id'])
        ) {

            return false;
        }
        if (empty($data['company']) ||
            empty($data['name']) ||
            empty($data['mobile']) ||
            empty($data['app_id'])
        ) {
            return false;
        }
        return true;
    }

    public function check()
    {
        $userid = session('userId');
        $appid = input('appid');
        $type = input('type');
        $id = input('id');
        $data = input('');
        $CardparkService = new CarparkService();
        $ElectricityService = new ElectricityService();
        $feepayment = new FeePayment();
        $companyService = new CompanyService();
        $companyapplication = new CompanyApplication();
        $advertisingRecord = new AdvertisingRecord();
        $led = new LedRecord();
        $FunctionRoomRecord = new FunctionRoomRecord();
        $wechatUser = new WechatUser();
        $ti = new TrademarkInquire();
        $ta = new TrademarkAdvisory();
        $userinfo = $wechatUser->where('userid', $userid)->find();
        //$personalMessage = new PersonalMessage();
        switch ($appid) {
            //费用缴纳
            case  1:
                //费用类型：1为水电费，2为物业费，3位为房租费，4位公耗费
                $res = $feepayment->where('id', $id)->find();
                $userlist = WechatUser::where(['department' => $res['company_id'], 'fee_status' => 1])->select();
                $users = "";
                foreach ($userlist as $value) {
                    $users .= "|" . $value['userid'];
                }
                switch ($res['type']) {
                    case 1:
                        $res['type_text'] = "水电费";
                        break;
                    case 2:
                        $res['type_text'] = "物业费";
                        break;
                    case 3:
                        $res['type_text'] = "房租费";
                        break;
                    case 4:
                        $res['type_text'] = "公耗费";
                        break;
                }
                $message = [
                    "title" => $res['type_text'] . "缴纳确认提示",
                    "description" => $res['name'] . "企业\n" . $res['type_text'] . "\n到期时间：" . $res['expiration_time'] . "\n应缴费用：" . $res['fee'] . "元",
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/Service/historyDetail/appid/1/can_check/no/id/' . $id
                ];

                if ($type == 1) {
                    $res['status'] = 2;
                    $message['description'] .= "\n确认成功";
                } else {
                    $res['status'] = 3;
                    $message['description'] .= "\n缴费失败";
                }
                unset($res['type_text']);
                $res->save();
                //todo： 推送点击到详情页面代码
                //推送给用户
                $reult = $this->commonSend(4, $message, $users);
                if ($reult) {
                    /* $new = [
                         "title" => $message['title'],
                         "message" => $message['description'],
                         'type' => 3,
                         'park_id' => session('park_id'),
                         'app_id' => $data['appid'],
                         'sid' => $id,
                         'create_time' => time(),
                         'status' => 0,
                         'userid' => $users
                     ];
                     $savemessage = $personalMessage->save($new);*/
                    return $this->success("预约成功");
                } else {
                    return $this->error("推送失败");
                }

                break;
            //物业报修
            case  2:
                $proof = input('proof/a');

                if ($type == 3) {
                    $res = PropertyServer::get($id);
                    $res['proof'] = json_encode($proof);
                    $res['status'] = 3;
                    $res->save();
                    if ($res) {
                        /* //使用一次报修服务，流程走完后获得1个积分；
                         WechatUser::where('userid', $res['user_id'])->setInc('score', 1);
                         $message = "积分服务提示\n您完成一次物业服务，积分+1！";
                         //推送给直接
                         $reult = $this->commonSendText(4, $message, $res['user_id'], 2);*/
                        return $this->success("上传凭证成功");
                    } else {
                        return $this->error("上传凭证失败");
                    }

                } else {
                    $message = [
                        "title" => "物业报修提示",
                        "description" => "您的报修园区已确认，维修人员将稍后进行维修，请您耐心等待",
                        "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/2/can_check/no/id/' . $id
                    ];
                    $user = PropertyServer::get($id);
                    if ($type == 1) {
                        $res = PropertyServer::where('id', $id)->update(['status' => 1, 'check_remark' => $data['check_remark']]);
                        if ($userinfo['department'] == 76) {

                            //服务类型 1为空调，2为电梯，3为其他
                            switch ($user['type']) {
                                case 1:
                                    $user['type_text'] = "空调维修";
                                    break;
                                case 2:
                                    $user['type_text'] = "电梯维修";
                                    break;
                                case 3:
                                    $user['type_text'] = "其他维修";
                                    break;
                            }
                            $message2 = [
                                "title" => "物业报修提示",
                                "description" => "服务类型：" . $user['type_text'] . "\n服务地点：" . $user['address'] . "\n联系人员：" . $user['name'] . "\n联系电话：" . $user['mobile'] . "\n该服务已由园区运营完成审核，请尽快处理",
                                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/2/can_check/yes/id/' . $id
                            ];
                            //推送给物业
                            $this->commonSend(2, $message2);
                        }
                    } else {
                        $res = PropertyServer::where('id', $id)->update(['status' => 2, 'check_remark' => $data['check_remark']]);
                        $message['description'] = "报修服务暂时无法提供";
                    }
                    if (!empty($data['check_remark'])) {
                        $message['description'] .= "\n备注：" . $data['check_remark'];
                    }
                    //推送给用户
                    $reult = $this->commonSend(4, $message, $user['user_id']);

                    if ($reult) {
                        /* $new = [
                             "title" => $message['title'],
                             "message" => $message['description'],
                             'type' => 3,
                             'park_id' => session('park_id'),
                             'app_id' => $data['appid'],
                             'sid' => $id,
                             'create_time' => time(),
                             'status' => 0,
                             'userid' => $user['user_id']
                         ];
                         $savemessage = $personalMessage->save($new);*/

                        return $this->success("报修成功");
                    } else {
                        return $this->error("推送失败");
                    }
                }
                break;
            //饮水
            case  3:
                $success = input('success');
                $user = WaterModel::get($id);
                if ($success == 1) {
                    $result = WaterModel::where('id', 'in', $id)->update(['status' => 3, 'end_time' => time()]);
                    if ($result) {
                        //使用一次饮水服务，流程走完后获得1个积分；
                        $re = WechatUser::where('userid', session('userId'))->setInc('score', 1);
                        $message = "积分服务提示\n您完成一次饮水服务，积分+1！";
                        //并记录
                        $point = new  ExchangePoint();
                        $map = [
                            'userid' => session('userId'),
                            'content' => '饮水服务',
                            'score' => 1,
                            'create_time' => time(),
                            'park_id' => session('park_id'),
                            'status' => 0,
                            'type' => 2

                        ];
                        $point->save($map);

                        //推送给自己
                        $reult = $this->sendMessageToFeature($message, $user['userid']);
                        //推送物业，运营 已送达
                        $message = [
                            "title" => "饮水服务提示",
                            "description" => "送水地点：" . $user['address'] . "\n送水桶数：" . $user['number'] . "\n联系人员：" . $user['name'] . "\n联系电话：" . $user['mobile'] . "\n您的饮水服务订单用户已确认送达。",
                            "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/3/can_check/no/id/' . $id
                        ];
                        $this->commonSend(3, $message);

                        return $this->success("送达成功");
                    } else {
                        return $this->error("送达失败");
                    }
                } else {
                    $message = [
                        "title" => "饮水服务提示",
                        "description" => "您的饮水服务园区已确认，稍后将有服务人员送水，请您耐心等待",
                        "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/3/can_check/no/id/' . $id
                    ];

                    if ($type == 1) {
                        $check_type = 1;
                        if ($userinfo['department'] == 76) {
                            $message2 = [
                                "title" => "饮水服务提示",
                                "description" => "送水地点：" . $user['address'] . "\n送水桶数：" . $user['number'] . "\n联系人员：" . $user['name'] . "\n联系电话：" . $user['mobile'] . "\n该服务已由园区运营完成审核，请尽快处理",
                                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/3/can_check/yes/id/' . $id
                            ];
                            //推送给物业
                            $this->commonSend(2, $message2);
                            $check_type = 2;
                        }
                        $result = WaterModel::where('id', 'in', $id)->update(['status' => 1, 'check_remark' => $data['check_remark'], 'check_time' => time(), 'check_type' => $check_type]);
                    } else {
                        $result = WaterModel::where('id', 'in', $id)->update(['status' => 2, 'check_remark' => $data['check_remark'], 'check_time' => time()]);
                        $message['description'] = "饮水服务暂时无法提供";
                    }
                    if (!empty($data['check_remark'])) {
                        $message['description'] .= "\n备注：" . $data['check_remark'];
                    }

                    //推送给用户
                    $reult = $this->commonSend(4, $message, $user['userid']);

                    if ($reult) {
                        /*$new = [
                            "title" => $message['title'],
                            "message" => $message['description'],
                            'type' => 3,
                            'park_id' => session('park_id'),
                            'app_id' => $data['appid'],
                            'sid' => $id,
                            'create_time' => time(),
                            'status' => 0,
                            'userid' => $user['user_id']
                        ];
                        $savemessage = $personalMessage->save($new);*/
                        return $this->success("报修成功");
                    } else {
                        return $this->error("推送失败");
                    }
                }

                break;
            //保洁
            case  4:
                $message = [
                    "title" => "保洁服务提示",
                    "description" => "您的保洁服务园区已确认，稍后将有服务人员联系您，请您耐心等待",
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/4/can_check/no/id/' . $id
                ];
                $user = PropertyServer::get($id);
                if ($type == 1) {
                    $res = PropertyServer::where('id', $id)->update(['status' => 1, 'check_remark' => $data['check_remark']]);
                    if ($userinfo['department'] == 76) {

                        $message2 = [
                            "title" => "室内保洁服务提示",
                            "description" => "服务类型：" . $user['type_text'] . "\n服务地点：" . $user['address'] . "\n联系人员：" . $user['name'] . "\n联系电话：" . $user['mobile'] . "\n该服务已由园区运营完成审核，请尽快处理",
                            "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/4/can_check/yes/id/' . $id
                        ];
                        //推送给物业
                        $this->commonSend(2, $message2);
                    }

                } else {
                    $res = PropertyServer::where('id', $id)->update(['status' => 2, 'check_remark' => $data['check_remark']]);
                    $message['description'] = "保洁服务暂时无法提供";
                }
                if (!empty($data['check_remark'])) {
                    $message['description'] .= "\n备注：" . $data['check_remark'];
                }

                //推送给用户
                $reult = $this->commonSend(4, $message, $user['user_id']);

                if ($reult) {
                    /*$new = [
                        "title" => $message['title'],
                        "message" => $message['description'],
                        'type' => 3,
                        'park_id' => session('park_id'),
                        'app_id' => $data['appid'],
                        'sid' => $id,
                        'create_time' => time(),
                        'status' => 0,
                        'userid' => $user['user_id']
                    ];
                    $savemessage = $personalMessage->save($new);*/
                    return $this->success("报修成功");
                } else {
                    return $this->error("推送失败");
                }
                break;
            //车卡
            case  6:
                //审核通过
                if (empty($data['park_card'])) {
                    return $this->error("请填写 停车卡号");
                }
                $message = [
                    "title" => "车卡服务提示",
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/6/can_check/no/id/' . $id
                ];
                $record = $CardparkService->where('id', $id)->find();
                $record['park_card'] = $data['park_card'];
                if ($type == 1) {
                    $record['status'] = 1;
                    $record['check_remark'] = $data['check_remark'];
                    //新卡
                    if ($record['type'] == 1) {
                        $message ['description'] = "您的新卡缴费已经完成，请2小时后前往领取";
                    } // 旧卡
                    else {
                        $message ['description'] = "您的旧卡续费已经完成";
                    }
                    if ($record['aging'] == 6) {
                        $score = 1;
                    } else {
                        $score = 2;
                    }

                    //使用一次车卡，流程走完后获得1个积分；
                    WechatUser::where('userid', session('userId'))->setInc('score', $score);
                    $message = "积分服务提示\n您完成一次充电柱服务，积分+" . $score . "！";
                    //并记录
                    $point = new  ExchangePoint();
                    $map = [
                        'userid' => session('userId'),
                        'content' => '车卡服务(' . $record['aging'] . ')',
                        'score' => $score,
                        'create_time' => time(),
                        'park_id' => session('park_id'),
                        'status' => 0,
                        'type' => 2

                    ];
                    $point->save($map);
                    //推送给自己
                    $this->sendMessageToFeature($message, session('userId'));
                } //审核不过
                else {
                    $record['status'] = 2;
                    $record['check_remark'] = $data['check_remark'];
                    //新卡
                    if ($record['type'] == 1) {
                        $message ['description'] = "您的新卡缴费无法通过审核";
                    } // 旧卡
                    else {
                        $message ['description'] = "您的旧卡续费无法通过审核";
                    }
                }
                if (!empty($data['check_remark'])) {
                    $message['description'] .= "\n备注：" . $data['check_remark'];
                }
                $record->save();
                //推送给用户
                $reult = $this->commonSend(4, $message, $record['user_id']);

                if ($reult) {
                    /*$new = [
                        "title" => $message['title'],
                        "message" => $message['description'],
                        'type' => 3,
                        'park_id' => session('park_id'),
                        'app_id' => $data['appid'],
                        'sid' => $id,
                        'create_time' => time(),
                        'status' => 0,
                        'userid' => $record['user_id']
                    ];
                    $savemessage = $personalMessage->save($new);*/
                    return $this->success("报修成功");
                } else {
                    return $this->error("推送失败");
                }
                break;
            //充电柱
            case  7:
                $message = [
                    "title" => "充电柱服务提示",
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/7/can_check/no/id/' . $id
                ];
                $record = $ElectricityService->where('id', $id)->find();
                if ($type == 1) {
                    if (empty($data['electricity_id'])) {
                        return $this->error("请填写 充电柱编号");
                    }
                    $message ['description'] = "您的旧柱续费已经完成";
                    //新柱申请
                    if ($record['type'] == 1) {
                        $message ['description'] = "您的新柱缴费已经完成，请2小时后前往领取";
                        $map['electricity_id'] = $data['electricity_id'];
                        $map['status'] = 1;
                        $is_has = $ElectricityService->where($map)->find();
                        if ($is_has) {
                            return $this->error('此柱已有使用者');
                        }
                    }
                    $record['check_remark'] = $data['check_remark'];
                    $record['status'] = 1;
                    $record['electricity_id'] = $data['electricity_id'];
                    if ($record['aging'] == 6) {
                        $score = 1;
                    } else {
                        $score = 2;
                    }

                    //使用一次充电柱服务，流程走完后获得1个积分；
                    WechatUser::where('userid', session('userId'))->setInc('score', $score);
                    $message = "积分服务提示\n您完成一次充电柱服务，积分+" . $score . "！";
                    //并记录
                    $point = new  ExchangePoint();
                    $map = [
                        'userid' => session('userId'),
                        'content' => '充电柱服务(' . $record['aging'] . ')',
                        'score' => $score,
                        'create_time' => time(),
                        'park_id' => session('park_id'),
                        'status' => 0,
                        'type' => 2

                    ];
                    $point->save($map);


                    //推送给自己
                    $reult = $this->sendMessageToFeature($message, $record['user_id']);

                } else {
                    if ($record['type'] == 1) {
                        $message ['description'] = "您的新柱缴费无法通过审核";
                    } else {
                        $message ['description'] = "您的旧柱缴费无法通过审核";
                    }

                    $record['check_remark'] = $data['check_remark'];
                    $record['status'] = 2;
                }
                if (!empty($data['check_remark'])) {
                    $message['description'] .= "\n备注：" . $data['check_remark'];
                }
                $record->save();
                //推送给用户
                $reult = $this->commonSend(4, $message, $record['user_id']);

                if ($reult) {
                    /*$new = [
                        "title" => $message['title'],
                        "message" => $message['description'],
                        'type' => 3,
                        'park_id' => session('park_id'),
                        'app_id' => $data['appid'],
                        'sid' => $id,
                        'create_time' => time(),
                        'status' => 0,
                        'userid' => $record['user_id']
                    ];
                    $savemessage = $personalMessage->save($new);*/
                    return $this->success("报修成功");
                } else {
                    return $this->error("推送失败");
                }
                break;
            //设备服务（这里的type 不是上面的（1／审核通过，2／审核不通过） ，而是（1／大厅广告位，2／二楼多功能厅，3／led屏））
            case 8:
                switch ($type) {
                    case 1:
                        $list = $advertisingRecord->where('create_time', $data['create_time'])->select();
                        foreach ($list as $value) {
                            $value['status'] = 0;
                            $value['check_remark'] = $data['check_remark'];
                            $value->save();
                        }
                        $message = [
                            "title" => "设备服务提示",
                            "description" => "您的大厅广告位预约申请被取消，请点击查看",
                            "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/8/can_check/no/type/1/create_time/' . $data['create_time']
                        ];

                        break;
                    case 2:
                        $list = $FunctionRoomRecord->where('create_time', $data['create_time'])->select();
                        foreach ($list as $value) {
                            $value['status'] = 0;
                            $value['check_remark'] = $data['check_remark'];
                            $value->save();
                        }
                        $message = [
                            "title" => "设备服务提示",
                            "description" => "您的二楼多功能厅预约申请被取消，请点击查看",
                            "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/8/can_check/no/type/2/create_time/' . $data['create_time']
                        ];

                        break;
                    case 3:
                        $list = $led->where('create_time', $data['create_time'])->select();
                        foreach ($list as $value) {
                            $value['status'] = 0;
                            $value['check_remark'] = $data['check_remark'];
                            $value->save();
                            $message = [
                                "title" => "设备服务提示",
                                "description" => "您的大堂LED屏预约申请被取消，请点击查看",
                                "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/8/can_check/no/type/3/create_time/' . $data['create_time']
                            ];
                        }
                        break;
                }
                if (!empty($data['check_remark'])) {
                    $message['description'] .= "\n备注：" . $data['check_remark'];
                }
                //推送给用户
                $reult = $this->commonSend(4, $message, $list[0]['create_user']);
                if ($reult) {
                    /*  $new = [
                          "title" => $message['title'],
                          "message" => $message['description'],
                          'type' => 3,
                          'park_id' => session('park_id'),
                          'app_id' => $data['appid'],
                          'sid' => $id,
                          'create_time' => time(),
                          'status' => 0,
                          'userid' => $record['user_id']
                      ];
                      $savemessage = $personalMessage->save($new);*/
                    //只有2／二楼多功能厅，3／led屏 预约有积分增加 所以取消预约后要减去
                    if ($type == 2 && $type == 3) {
                        if ($type == 2) {
                            $score = 3;

                        } else {
                            $score = 1;
                        }

                        //取消预约一次设备服务，减得1个积分；
                        WechatUser::where('userid', session('userId'))->setDec('score', $score);
                        $message = "积分服务提示\n您取消一次设备服务，积分-1！";
                        //并记录
                        $point = new  ExchangePoint();
                        $map = [
                            'userid' => session('userId'),
                            'content' => '设备服务（取消）',
                            'score' => $score,
                            'create_time' => time(),
                            'park_id' => session('park_id'),
                            'status' => 0,
                            'type' => 1

                        ];
                        $point->save($map);
                        //推送给自己
                        $reult = $this->sendMessageToFeature( $message, session('userId'));
                    }

                    return $this->success("取消成功");
                } else {
                    return $this->error("推送失败");
                }
                break;
            //商标查询（$type =1 查询  $type =2 咨询）
            case 12:
                //商标查询
                if ($type == 1) {
                    $trademarkin = $ti->get($id);
                    $trademarkin['back_img'] = json_encode($data['back_img']);
                    $trademarkin['reply'] = $data['reply'];
                    $trademarkin['end_time'] = time();
                    $trademarkin['status'] = 1;
                    $trademarkin->save();
                    // 商标查询服务回复提示/时间/您的商标查询园区已回复，点击查看详情；
                    $message = [
                        "title" => "商标查询服务回复提示",
                        'description' => "您的商标查询园区已回复，点击查看详情",
                        "url" => 'https://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetailCompany/appid/12/can_check/no/type/1/id/' . $id
                    ];
                    commonService::sendPersonalMessage($message, $trademarkin['userid']);
                    return $this->success("回复成功");
                } //商标咨询
                elseif ($type == 2) {
                    $trademarka = $ta->get($id);
                    $trademarka['respond'] = $data['respond'];
                    $trademarka['end_time'] = time();
                    $trademarka['status'] = 1;
                    $trademarka->save();

                    // 商标查询服务回复提示/时间/您的商标查询园区已回复，点击查看详情；
                    $message = [
                        "title" => "商标咨询服务回复提示",
                        'description' => "您的商标咨询园区已回复，点击查看详情",
                        "url" => 'https://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetailCompany/appid/12/can_check/no/type/2/id/' . $id
                    ];
                    commonService::sendPersonalMessage($message, $trademarka['userid']);
                    return $this->success("回复成功");
                }
                break;
            //专利申请
            case 21:
                $patent = new  Patent();
                $res = $patent->check($type, $id, $data);
                if ($res) {
                    if ($type == 3) {
                        return $this->success("修改成功");
                    }
                    return $this->success("审核成功");
                } else {
                    return $this->error("操作失败");
                }
                break;
            //版权申请(type_check:(1 艺术作品 2软著登记 3 软著撰写)  type (1. 通过 ， 2 不通过)   )
            case 22:
                $type_check = $data['type_check'];
                if ($type_check == 1) {
                    $copy = new CopyrightArt();
                } elseif ($type_check == 2) {
                    $copy = new CopyrightSoft();
                } elseif ($type_check == 3) {
                    $copy = new CopyrightSoftwrite();
                }
                $result = $copy->check($type, $id, $data);
                if ($result) {
                    return $this->success('审核成功');
                } else {
                    return $this->error("操作失败");
                }
                break;
            //企业服务
            default:
                $ca = $companyapplication->where('app_id', $data['appid'])->find();
                $record = $companyService->where('id', $id)->find();
                $message = [
                    "title" => "企业服务提示",
                    'description' => $ca['name'] . "服务申请\n公司名称：" . $record['company'] . "\n联系人员：" . $record['people'] . "\n联系方式：" . $record['mobile'] . "\n备注信息：" . $record['remark'],
                    "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/' . $data['appid'] . '/can_check/no/id/' . $id
                ];
                $record['check_remark'] = $data['check_remark'];
                if ($type == 1) {

                    $record['status'] = 1;
                    $record['check_remark'] = $data['check_remark'];
                    $message ['description'] .= "\n您的企业服务园区已确认，稍后将有工作人员联系您，请您耐心等待。";
                } else {
                    $record['status'] = 2;
                    $record['check_remark'] = $data['check_remark'];
                    $message ['description'] .= "\n您的申请的企业服务暂时无法提供。";
                }
                if (!empty($data['check_remark'])) {
                    $message['description'] .= "\n备注：" . $data['check_remark'];
                }
                $record->save();
                //推送给用户
                $reult = $this->commonSend(4, $message, $record['user_id']);
                if ($reult) {
                    /*  $new = [
                          "title" => $message['title'],
                          "message" => $message['description'],
                          'type' => 3,
                          'park_id' => session('park_id'),
                          'app_id' => $data['appid'],
                          'sid' => $id,
                          'create_time' => time(),
                          'status' => 0,
                          'userid' => $record['user_id']
                      ];
                      $savemessage = $personalMessage->save($new);*/
                    return $this->success("报修成功");
                } else {
                    return $this->error("推送失败");
                }

                break;
        }
    }


    /**推个人中心，推送人员选择公共方法
     *$type =1  该园区运营人员
     *$type =2  该园区物业管理
     *$type =3  该园区运营人员+物业管理
     *$type =4  该用户
     *
     * $message=[
     *   "title" => "物业保修提示",
     *   "description" => date('m月d', $data['create_time']) . "\n服务类型：" . $data['type_text'] . "\n服务地点：" . $data['address'] . "\n联系人员：" . $data['name'] . "\n联系电话：" . $data['mobile'],
     *   "url" => '']
     *
     *retur  true/false
     */
    public function commonSend($type, $message, $userid = "", $appid = 0)
    {
        $wechatUser = new WechatUser();
        $useridlist = "";
        $park_id = session('park_id');
        //该园区运营的department——id
        switch ($park_id) {
            case  3 :
                $department_id = 76;
                $propertyDepartment = 86;
                break;
            case  80 :
                $department_id = 88;
                $propertyDepartment = 90;
                break;
            default:
                $department_id = 76;
                $propertyDepartment = 86;
                break;
        }
        switch ($type) {
            //运营
            case 1 :
                $user = $wechatUser->where(['department' => $department_id, 'status' => 1])->select();
                foreach ($user as $value) {
                    if (isset($value->operational->appids)) {
                        $appids = empty($value->operational->appids) ? array() : json_decode($value->operational->appids);
                        if (in_array($appid, $appids)) {
                            $useridlist .= '|' . $value['userid'];
                        }
                    }
                }
                break;
            //物业
            case 2 :
                $user = $wechatUser->where(['department' => $propertyDepartment, 'park_id' => $park_id])->select();
                foreach ($user as $value2) {
                    $useridlist .= '|' . $value2['userid'];
                }
                break;
            case 3:
                //该园区运营团队
                $user1 = $wechatUser->where(['department' => $department_id, 'status' => 1])->select();
                foreach ($user1 as $value) {
                    if (isset($value->operational->appids)) {
                        $appids = empty($value->operational->appids) ? array() : json_decode($value->operational->appids);
                        if (in_array($appid, $appids)) {
                            $useridlist .= '|' . $value['userid'];
                        }
                    }
                }
                //该园区物业管理
                $user2 = $wechatUser->where(['department' => $propertyDepartment, 'park_id' => $park_id, 'status' => 1])->select();
                foreach ($user2 as $value2) {
                    $useridlist .= '|' . $value2['userid'];
                }
                break;

            case 4:
                $useridlist = $userid;
                break;
        }
        if (!empty($useridlist)) {
            $res = commonService::sendPersonalMessage($message, $useridlist);
        } else {
            return true;
        }
        if ($res['errcode'] == 0) {
            return true;
        } else {

            return false;
        }
    }


    /**推个人中心，推送人员选择公共方法
     *$type =1  该园区运营人员
     *$type =2  该园区物业管理
     *$type =3  该园区运营人员+物业管理
     *$type =4  该用户
     *
     * $message=[
     *   "title" => "物业保修提示",
     *   "description" => date('m月d', $data['create_time']) . "\n服务类型：" . $data['type_text'] . "\n服务地点：" . $data['address'] . "\n联系人员：" . $data['name'] . "\n联系电话：" . $data['mobile'],
     *   "url" => '']
     *
     *retur  true/false
     */
    public function commonSendText($type, $message, $userid = "", $appid = 0)
    {
        $wechatUser = new WechatUser();
        $useridlist = "";
        $park_id = session('park_id');
        //该园区运营的department——id
        switch ($park_id) {
            case  3 :
                $department_id = 76;
                $propertyDepartment = 86;
                break;
            case  80 :
                $department_id = 88;
                $propertyDepartment = 90;
                break;
            default:
                $department_id = 76;
                $propertyDepartment = 86;
                break;
        }
        switch ($type) {
            //运营
            case 1 :
                $user = $wechatUser->where(['department' => $department_id, 'status' => 1])->select();
                foreach ($user as $value) {
                    if (isset($value->operational->appids)) {
                        $appids = empty($value->operational->appids) ? array() : json_decode($value->operational->appids);
                        if (in_array($appid, $appids)) {
                            $useridlist .= '|' . $value['userid'];
                        }
                    }
                }

                break;
            //物业
            case 2 :
                $user = $wechatUser->where(['department' => $propertyDepartment, 'park_id' => $park_id])->select();
                foreach ($user as $value2) {
                    $useridlist .= '|' . $value2['userid'];
                }
                break;
            case 3:
                //该园区运营团队
                $user1 = $wechatUser->where(['department' => $department_id, 'status' => 1])->select();
                foreach ($user1 as $value) {
                    if (isset($value->operational->appids)) {
                        $appids = empty($value->operational->appids) ? array() : json_decode($value->operational->appids);
                        if (in_array($appid, $appids)) {
                            $useridlist .= '|' . $value['userid'];
                        }
                    }
                }
                //该园区物业管理
                $user2 = $wechatUser->where(['department' => $propertyDepartment, 'park_id' => $park_id, 'status' => 1])->select();
                foreach ($user2 as $value2) {
                    $useridlist .= '|' . $value2['userid'];
                }
                break;

            case 4:
                $useridlist = $userid;
                break;

        }
        if (!empty($useridlist)) {

            $weObj = new TPWechat(Config('personal'));
            $data = [
                'touser' => $useridlist,
                'agentid' => 1000008,
                'msgtype' => 'text',
                'text' => [
                    'content' => $message
                ]
            ];

            $res = $weObj->sendMessage($data);


        } else {
            return true;
        }

        if ($res['errcode'] == 0) {
            return true;
        } else {

            return false;
        }

    }


    /*推个人中心，推送人员选择公共方法
     *$type =1  该园区运营人员
     *$type =2  该园区物业管理
     *$type =3  该园区运营人员+物业管理
     *$type =4  该用户
     *
     * $message=[
     *   "title" => "物业保修提示",
     *   "description" => date('m月d', $data['create_time']) . "\n服务类型：" . $data['type_text'] . "\n服务地点：" . $data['address'] . "\n联系人员：" . $data['name'] . "\n联系电话：" . $data['mobile'],
     *   "url" => '']
     *
     *retur  true/false
    */
    /* public
     function commonSend($type, $message, $userid = "")
     {
         $wechatUser = new WechatUser();
         $useridlist = "";
         $park_id = session('park_id');
         //该园区运营团队
         $operation = $this->getUserbyTagid(1, $park_id);
         //该园区物业管理
         $property = $this->getUserbyTagid(2, $park_id);

         switch ($type) {
             //该园区运营团队
             case 1 :
                 foreach ($operation as $value) {
                     $useridlist .= '|' . $value['userid'];
                 }
                 break;
             //该园区物业管理
             case 2 :
                 foreach ($property as $value) {
                     $useridlist .= '|' . $value['userid'];
                 }
                 break;
             //物业加运营
             case 3:
                 foreach ($operation as $value) {
                     $useridlist .= '|' . $value['userid'];
                 }
                 foreach ($property as $value) {
                     $useridlist .= '|' . $value['userid'];
                 }
                 break;
             //用户
             case 4:
                 $useridlist = $userid;
                 break;
         }
         $res = commonService::sendPersonalMessage($message, $useridlist);
         if ($res['errcode'] == 0) {
             return true;
         } else {

             return false;
         }

     }*/

    /*推个人中心，推送人员选择公共方法（设备服务专用）
         *$type =1  该园区运营人员
         *$type =2  该园区物业管理
         *$type =3  该园区运营人员+物业管理
         *$type =4  该用户
         *
         * $message=[
         *   "title" => "物业保修提示",
         *   "description" => date('m月d', $data['create_time']) . "\n服务类型：" . $data['type_text'] . "\n服务地点：" . $data['address'] . "\n联系人员：" . $data['name'] . "\n联系电话：" . $data['mobile'],
         *   "url" => '']
         *
         *retur  true/false
        */
    /*public
    function publicSend($type, $message, $userid = "")
    {
        $wechatUser = new WechatUser();
        $useridlist = "";
        $park_id = session('park_id');
        //该园区运营的department——id
        switch ($park_id) {
            case  3 :
                $department_id = 76;
                break;
            default:
                $department_id = 76;
                break;
        }
        switch ($type) {
            case 1 :
                $user = $wechatUser->where('department', $department_id)->select();
                foreach ($user as $value) {
                    $useridlist .= '|' . $value['userid'];
                }
                break;
            case 2 :
                $user = $wechatUser->where(['tagid' => 2, 'park_id' => $park_id])->select();
                foreach ($user as $value2) {
                    $useridlist .= '|' . $value2['userid'];
                }
                break;
            case 3:
                //该园区运营团队
                $user1 = $wechatUser->where('department', $department_id)->select();
                foreach ($user1 as $value) {
                    $useridlist .= '|' . $value['userid'];
                }
                //该园区物业管理
                $user2 = $wechatUser->where(['tagid' => 2, 'park_id' => $park_id])->select();
                foreach ($user2 as $value2) {
                    $useridlist .= '|' . $value2['userid'];
                }
                break;

            case 4:
                $useridlist = $userid;
                break;

        }
        $res = commonService::sendPersonalText($message, $useridlist);
        if ($res['errcode'] == 0) {
            return true;
        } else {

            return false;
        }

    }*/

    /*楼盘信息公共方法*/
    public function commonFloor()
    {
        $floor = [];
        $floor1 = [];
        $newArr = [];
        $newArr1 = [];
        $roomArray = [];
        $parkFloor = new ParkFloor();
        $parkId = session('park_id');
        $parkInfo = Park::where('id', $parkId)->find();
        $parkRoom = new ParkRoom();
        $map = [
            'park_id' => $parkId,
            'build' => "A",
        ];
        $list = $parkFloor->where($map)->distinct(true)->field('fid')->order('fid asc')->select();
        foreach ($list as $k => $v) {
            $floor[$k] = $v['fid'];
        }
        foreach ($floor as $k => $v) {
            $roomList = $parkRoom->where(['floor' => $v, 'build_block' => "A", 'del' => 0, 'park_id' => $parkId])->order("room asc")->select();
            if (count($roomList)){
                foreach ($roomList as $k1 => $v1) {
                    $roomArray[$k][$k1] = $v1['room'];
                }
            }else{
                $roomArray[$k] = '' ;
            }
        }
        foreach ($floor as $k => $v) {
            $newArr[$k]['name'] = $v . "楼";
            $newArr[$k][$v . "楼"] = $roomArray[$k];
            //$newArr[$k]['rooms'] = $roomArray[$k];
        }
        $map1 = [
            'park_id' => $parkId,
            'build' => "B",
        ];
        $list1 = $parkFloor->where($map1)->distinct(true)->field('fid')->order('fid asc')->select();
        foreach ($list1 as $k => $v) {
            $floor1[$k] = $v['fid'];
        }
        foreach ($floor1 as $k => $v) {
            $roomList1 = $parkRoom->where(['floor' => $v, 'build_block' => "B", 'del' => 0, 'park_id' => $parkId])->order("room asc")->select();
            if (count($roomList1)){
                foreach ($roomList1 as $k1 => $v1) {
                    $roomArray1[$k][$k1] = $v1['room'];
                }
            }else{
                $roomArray1[$k] = '';
            }

        }
        foreach ($floor1 as $k => $v) {
            $newArr1[$k]['name'] = $v . "楼";
            $newArr1[$k][$v . "楼"] = $roomArray1[$k];
        }
        $list = [
            "A幢" => $newArr,
            "B幢" => $newArr1,
        ];
        if ($parkId == 80) {
            $map2 = [
                'park_id' => $parkId,
                'build' => "C",
            ];
            $list2 = $parkFloor->where($map2)->distinct(true)->field('fid')->order('fid asc')->select();
            $floor2 =array();
            foreach ($list2 as $k => $v) {
                $floor2[$k] = $v['fid'];
            }

            $roomArray2=array();
            foreach ($floor2 as $k => $v) {
                $roomList2 = $parkRoom->where(['floor' => $v, 'build_block' => "C", 'del' => 0, 'park_id' => $parkId])->order("room asc")->select();
                if (count($roomList2)){
                    foreach ($roomList2 as $k1 => $v1) {
                        $roomArray2[$k][$k1] = $v1['room'];
                    }
                }else{
                    $roomArray2[$k] = '';
                }

            }
            $newArr2=array();
            foreach ($floor2 as $k => $v) {
                $newArr2[$k]['name'] = $v . "楼";
                $newArr2[$k][$v . "楼"] = $roomArray2[$k];
                //$newArr1[$k]['rooms'] = $roomArray1[$k];
            }

            $map3 = [
                'park_id' => $parkId,
                'build' => "D",
            ];
            $list3 = $parkFloor->where($map3)->distinct(true)->field('fid')->order('fid asc')->select();


            foreach ($list3 as $k => $v) {
                $floor3[$k] = $v['fid'];
            }
            $floor3 =array();
            foreach ($floor3 as $k => $v) {
                $roomList3 = $parkRoom->where(['floor' => $v, 'build_block' => "D", 'del' => 0, 'park_id' => $parkId])->order("room asc")->select();
                if (count($roomList3)){
                    foreach ($roomList3 as $k1 => $v1) {
                        $roomArray3[$k][$k1] = $v1['room'];
                    }
                }else{
                    $roomArray3[$k] = '';
                }
            }

            $newArr3 =array();
            foreach ($floor3 as $k => $v) {
                $newArr3[$k]['name'] = $v . "楼";
                $newArr3[$k][$v . "楼"] = $roomArray3[$k];
                //$newArr1[$k]['rooms'] = $roomArray1[$k];
            }
            $list["C幢"] = $newArr2;
            $list["D幢"] = $newArr3;
        }
        return $list;
        //echo json_encode($list);

    }
//    预约喝茶
    public function bespeakActivity()
    {
        $data = input('');
        $this->assign('info',json_encode($data));
        return $this->fetch('bespeak_activity');
    }

    /**
     * 多彩园区推送
     * @param $message
     * @param $useridlist
     */
    public function sendMessageToFeature($message,$useridlist){
        if (empty($useridlist)){

            return false;
        }
        $weObj = new TPWechat(Config('feature'));
        $data = [
            'touser' => $useridlist,
            'agentid' => 1000024,
            'msgtype' => 'text',
            'text' => [
                'content' => $message
            ]
        ];

        $res = $weObj->sendMessage($data);
    }
    /*public function testFeature(){
        $this->sendMessageToFeature("测试数据",'18867514826');
    }*/
}


