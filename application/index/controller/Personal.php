<?php
/**
 * Created by PhpStorm.
 * User: zyf
 * QQ: 2205446834@qq.com
 * Date: 2017/6/2
 * Time: 13:35
 */

namespace app\index\controller;


use app\index\model\CarparkService;
use app\index\model\Collect as CollectModel;
use app\index\model\WechatUser;
use app\index\model\WechatDepartment;
use app\index\model\PersonalMessage;
use app\index\model\CompanyService;
use app\index\model\CompanyApplication;
use think\Db;
use app\index\model\FeePayment;
use app\index\model\PropertyServer;
use app\index\model\WaterService;
use app\index\model\CarparkRecord;
use app\index\model\ElectricityService;
use app\index\model\AdvertisingService;
use app\index\model\AdvertisingRecord;
use app\index\model\FunctionRoomRecord;
use app\index\model\LedRecord;
use app\index\model\BroadbandPhone;


class Personal extends Base
{
    public function index()
    {
        $user_id = session("userId");
        $user = new  WechatUser();
        $partyinfo = $user->where(['userid' => $user_id])->find();
        $userinfo = $user->checkUserExist($user_id);
        $this->assign("party_branch", $partyinfo['party_branch']);
        $this->assign("userinfo", $userinfo);

        return $this->fetch();
    }

    /*个人信息*/
    public function personalinfo()
    {


        $user_id = session('userId');
        $wu = new  WechatUser();
        $info = $wu->where('userid', $user_id)->find();
        $data = [
            'name' => $info['name'],
            'avatar' => $info['avatar'],
            'sex' => $info['gender'] == 1 ? "男" : "女",
            'mobile' => $info['mobile'],
            'department' => isset($info->departmentName->name) ? $info->departmentName->name : "",
            'header' => $info['header']
        ];

        $this->assign('info', $data);
        return $this->fetch();
    }

    /*个人收藏*/
    public function collection()
    {
        $user_id = session("userId");
        $list1 = CollectModel::where(['user_id' => $user_id, 'type' => 1])
            ->order('create_time desc')
            ->limit(6)->select();
        foreach ($list1 as $k => $v) {
            $list1[$k]['title'] = $v->News->title;
            $list1[$k]['views'] = $v->News->views;
            $list1[$k]['image'] = $v->News->front_cover;
        }
        $list2 = CollectModel::where(['user_id' => $user_id, 'type' => 2])
            ->order('create_time desc')
            ->limit(6)->select();
        foreach ($list2 as $k => $v) {
            $list2[$k]['title'] = $v->News->title;
            $list2[$k]['views'] = $v->News->views;
            $list2[$k]['source'] = $v->News->source;
        }

        $list3 = CollectModel::where(['user_id' => $user_id, 'type' => 3])
            ->order('create_time desc')
            ->limit(6)->select();
        foreach ($list3 as $k => $v) {
            $list3[$k]['title'] = $v->News->title;
            $list3[$k]['views'] = $v->News->views;
            $list3[$k]['source'] = $v->News->source;
        }
        $list4 = CollectModel::where(['user_id' => $user_id, 'type' => ['in', [4, 5]]])
            ->order('create_time desc')
            ->limit(6)->select();
        foreach ($list4 as $k => $v) {
            $list4[$k]['title'] = $v->News->title;
            $list4[$k]['views'] = $v->News->views;
            $list4[$k]['source'] = $v->News->source;
        }

        $this->assign('list1', $list1);
        $this->assign('list2', $list2);
        $this->assign('list3', $list3);
        $this->assign('list4', $list4);

        return $this->fetch();

    }

    /**党员信息*/
    public function party()
    {
        $user_id = session("userId");
        $party_user = new  WechatUser();
        if (IS_POST) {
            $data = input('');
            $data['education'] = $data['education'] - 1;
            $result = $party_user->save($data, ['userid' => $user_id]);
            if ($result !== false) {

                return $this->success('成功');
            } else {

                return $this->error('失败');
            }
        } else {
            $userInfo = $party_user->where(['userid' => $user_id])->find();
            if ($userInfo) {
                unset($userInfo['mobile']);
                unset($userInfo['userid']);
                $userInfo['education'] = $userInfo['education'] + 1;
                $result['data'] = $userInfo;
            }
            $this->assign("data", json_encode($result));
            return $this->fetch();
        }

    }

    //党员编辑
    public function editParty()
    {
        $user_id = session("userId");
        $party_user = new  WechatUser();
        $userInfo = $party_user->where(['userid' => $user_id])->find();
        $userInfo['education'] = $userInfo['education'] + 1;
        unset($userInfo['mobile']);
        unset($userInfo['userid']);
        $this->assign("data", $userInfo);

        return $this->fetch();
    }

    /**
     * 设置头像
     */
    public function setHeader()
    {
        $userId = session('userId');
        $header = input('header');
        $map = array(
            'header' => $header,
        );
        $wu = new  WechatUser();
        $info = $wu->where('userid', $userId)->update($map);
        if ($info) {
            return $this->success("修改成功");
        } else {
            return $this->error("修改失败");
        }
    }

    /*我的消息*/
    public function message()
    {
        $personMessage = new PersonalMessage();
        $user_id = session('userId');
        $park_id = session('park_id');
        $user = WechatUser::where('userid', $user_id)->find();
        //运营人员
        if ($user['department'] == 76) {
            $map = [
                'park_id' => $park_id,
                'type' => array('neq', 3)
            ];
            $list = $personMessage->where($map)->limit(0,6)->select();

        } //物业人员
        elseif ($user['tagid'] == 2) {
            $map = [
                'park_id' => $park_id,
                'type' => array('eq', 2)
            ];
            $list = $personMessage->where($map)->limit(0,6)->select();
        } //用户自己
        else {

            $map = [
                'park_id' => $park_id,
                'type' => array('eq', 3),
                'userid' => $user_id
            ];
            $list = $personMessage->where($map)->limit(0,6)->select();
        }
        $this->assign('list', json_encode($list));
        return $this->fetch();
    }

    /*我的消息上拉加载*/
    public function messageMore(){
        $personMessage = new PersonalMessage();
        $user_id = session('userId');
        $park_id = session('park_id');
        $user = WechatUser::where('userid', $user_id)->find();
        $num =input('num');
        //运营人员
        if ($user['department'] == 76) {
            $map = [
                'park_id' => $park_id,
                'type' => array('neq', 3)
            ];
            $list = $personMessage->where($map)->limit($num,6)->select();

        } //物业人员
        elseif ($user['tagid'] == 2) {
            $map = [
                'park_id' => $park_id,
                'type' => array('eq', 2)
            ];
            $list = $personMessage->where($map)->limit($num,6)->select();
        } //用户自己
        else {

            $map = [
                'park_id' => $park_id,
                'type' => array('eq', 3),
                'userid' => $user_id
            ];
            $list = $personMessage->where($map)->limit($num,6)->select();
        }
         if($list){

            return $this->success('成功','',json_encode($list));
         }else{

             return $this->error('失败');
         }



    }


    /*我的服务*/
    public function service()
    {
        $userid = session('userId');
        //费用缴纳
        $userinfo = WechatUser::where(['userid' => $userid])->find();
        $departmentId = $userinfo['department'];
        $map = ['company_id' => $departmentId, 'status' => ['neq', -1]];
        $list1 = FeePayment::where($map)->order('create_time desc')->field('type as service_name,status,create_time')->select();
        $types = [1 => '费用缴纳（水电费)', 2 => "费用缴纳（物业费)", 3 => "费用缴纳（房租费)", 4 => "费用缴纳（公耗费)"];
        foreach ($list1 as $k => $v) {
            $v['service_name'] = $types[$v['service_name']];
            if ($v['status'] == 1) {
                $v['status'] = 0;
            }
            if ($v['status'] == 2) {
                $v['status'] = 1;
            }
        };
        //物业报修
        $types = [1 => '物业报修（空调报修）', 2 => "物业报修（电梯报修）", 3 => "物业报修（其他报修）"];
        $list2 = PropertyServer::where(['type' => ['<', 4], 'user_id' => $userid, 'status' => ['>=', 0]])->order('create_time desc')->field('type as service_name,status,create_time')->select();
        foreach ($list2 as $k => $v) {
            $v['service_name'] = $types[$v['service_name']];
        }

        //饮水服务
        $map = [
            'status' => array('neq', -1),
            'userid' => $userid,
        ];
        $list3 = WaterService::where($map)->order('create_time desc')->field('status,create_time')->select();
        foreach ($list3 as $k => $v) {
            $v['service_name'] = "饮水服务";
        }
        //室内保洁

        $list4 = PropertyServer::where(['type' => ['=', 4], 'user_id' => $userid, 'status' => ['>=', 0]])->order('create_time desc')->field('type as service_name,status,create_time')->select();
        foreach ($list4 as $k => $v) {
            $v['service_name'] = "保洁服务";
        }

        //车卡服务
        $list5 = CarparkService::where(['user_id' => $userid, 'status' => array('neq', -1)])->select();
        foreach ($list5 as $k => $v) {
            $list5[$k]['service_name'] = $v['type'] == 1 ? "车卡服务（新卡办理）" : "车卡服务（旧卡续费）";
            $list5[$k]['create_time'] = date("Y-m-d", $v['create_time']);
            if ($list5[$k]['status'] == 0) {
                $list5[$k]['status_text'] = '进行中';
            } elseif ($list5[$k]['status'] == 1) {
                $list5[$k]['status_text'] = '已完成';
            } else {
                $list5[$k]['status_text'] = '审核失败';
            }
        }

        //充电柱办公
        $service = new ElectricityService;
        $user_id = session('userId');
        $map = [
            'user_id' => $user_id,
            'status' => array('neq', -1)
        ];
        $list6 = $service->where($map)->order('create_time desc')->select();

        int_to_string($list6, array('type' => array(1 => '充电柱办公(新柱办理)', 2 => '充电柱办公(旧柱续费)'), 'status' => array(0 => '进行中', 1 => '已完成', 2 => '审核失败')));
        foreach ($list6 as $value) {
            $value['service_name'] = $value['type_text'];

        }

        //公共服务


        //大厅广告记录
        $service = new AdvertisingService();
        $ad = new AdvertisingRecord();
        $fs = new FunctionRoomRecord();
        $led = new LedRecord();

        $data1 = array();
        $time = array();
        $create_time = array();
        $serviceInfo = $service->where('id', 1)->find();
        $list = $ad->where('create_user', $user_id)->order('create_time desc')->select();
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

            ];

            foreach ($map as $k => $value) {
                $re['service_name'] = "公共区服务（大厅广告位预约）";
                $re['create_time'] = $map[$k]['create_time'];
            }
            if ($map[0]['status'] == 0) {

                $re['status'] = 2;
            } else if ($map[0]['status'] == 1) {
                $re['status'] = 0;

            } else {
                $re['status'] = 1;
            }

            array_push($data1, $re);

        }

        //二楼多功能厅
        $data2 = array();
        $time = array();
        $create_time = array();
        $serviceInfo = $service->where('id', 2)->find();
        $list = $fs->where('create_user', $user_id)->order('create_time desc')->select();
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
            ];

            //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
            $map_time = array();
            foreach ($map as $m) {
                array_push($map_time, $m['order_time']);
            }
            $mtime_list = array_values(array_unique($map_time));

            foreach ($mtime_list as $k => $value) {
                $re['service_name'] = "公共区服务（多功能厅预约）";
                $re['create_time'] = $map[$k]['create_time'];
            }
            if ($map[0]['status'] == 0) {

                $re['status'] = 2;
            } else if ($map[0]['status'] == 1) {
                $re['status'] = 0;

            } else {
                $re['status'] = 1;
            }

            array_push($data2, $re);
        }

        //大堂led灯
        $data3 = array();
        $time = array();
        $create_time = array();
        $serviceInfo = $service->where('id', 3)->find();
        $list = $led->where('create_user', $user_id)->order('create_time desc')->select();
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
            ];

            //这个map为这一条记录的所有用户选中预约天数（因为要考虑上下午，还要按天分）
            $map_time = array();
            foreach ($map as $m) {
                array_push($map_time, $m['order_time']);
            }
            $mtime_list = array_values(array_unique($map_time));

            foreach ($mtime_list as $k => $value) {
                $re['service_name'] = "公共区服务（大堂led屏预约）";
                $re['create_time'] = $map[$k]['create_time'];
            }
            if ($map[0]['status'] == 0) {

                $re['status'] = 2;
            } else if ($map[0]['status'] == 1) {
                $re['status'] = 0;

            } else {
                $re['status'] = 1;
            }

            array_push($data3, $re);

        }

        //电话宽带
        $userid = session('userId');
        //      echo  $userid;
        $map = [
            'status' => array('neq', -1),
            'user_id' => $userid,
        ];
        $list7 = BroadbandPhone::where($map)->order('create_time desc')->select();

        foreach ($list7 as $k => $v) {
            $list7[$k] = [
                'create_time' => $v['create_time'],
                'status' => $v['status'],
                'service_name' => "电话宽带",
            ];
        }

        //合并所有物业服务的数组
        $list = array_merge($list1, $list2, $list3, $list4, $list5, $list6, $data1, $data2, $data3, $list7);

        //把数组按时间排序
        usort($list, function ($a, $b) {
            $al = strtotime($a['create_time']);
            $bl = strtotime($b['create_time']);
            if ($al == $bl)
                return 0;
            return ($al > $bl) ? -1 : 1;
        });

        foreach ($list as $k => $value) {
            if ($value['status'] == 0) {
                $list[$k]['status_text'] = '进行中';
            } elseif ($value['status'] == 1) {
                $list[$k]['status_text'] = '已完成';
            } else {
                $list[$k]['status_text'] = '审核失败';
            }
        }

        $this->assign('property', $list);
        //企业服务

        $list = Db::table('tb_company_service')
            ->alias('s')
            ->join('__COMPANY_APPLICATION__ a', 's.app_id=a.app_id')
            ->field('a.name as service_name,s.status,s.create_time')
            ->where('s.user_id', 'eq', $userid)
            ->where('s.status', 'neq', -1)
            ->order('create_time desc')
            ->select();

        foreach ($list as $value) {
            $value['create_time'] = date("Y-m-d", $value['create_time']);

            if ($value['status'] == 0) {
                $value['status_text'] = '进行中';
            } else {
                $value['status_text'] = '已完成';
            }
        }
        $this->assign('company', $list);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /*我的收藏下拉刷新*/
    public function listmore()
    {
        $len = input("length");
        $type = input("type");
        $user_id = session("userId");
        if ($type == 1) {
            $list1 = CollectModel::where(['user_id' => $user_id, 'type' => 1])
                ->order('create_time desc')
                ->limit($len, 6)->select();
            if ($list1) {
                foreach ($list1 as $k => $v) {
                    $list1[$k]['title'] = $v->News->title;
                    $list1[$k]['views'] = $v->News->views;
                    $list1[$k]['image'] = $v->News->front_cover;
                    unset($list1[$k]['News']);
                }

                return json(['code' => 1, 'data' => $list1]);
            } else {

                return json(['code' => 0, 'msg' => "没有更多内容了"]);
            }

        } else if ($type == 2) {
            $list2 = CollectModel::where(['user_id' => $user_id, 'type' => 2])
                ->order('create_time desc')
                ->limit($len, 6)->select();
            if ($list2) {
                foreach ($list2 as $k => $v) {
                    $list2[$k]['title'] = $v->News->title;
                    $list2[$k]['views'] = $v->News->views;
                    unset($list2[$k]['News']);
                }
                return json(['code' => 1, 'data' => $list2]);
            } else {

                return json(['code' => 0, 'msg' => "没有更多内容了"]);
            }
        } else if ($type == 3) {
            $list3 = CollectModel::where(['user_id' => $user_id, 'type' => 3])
                ->order('create_time desc')
                ->limit($len, 6)->select();
            if ($list3) {
                foreach ($list3 as $k => $v) {
                    $list3[$k]['title'] = $v->News->title;
                    $list3[$k]['views'] = $v->News->views;
                    unset($list3[$k]['News']);
                }

                return json(['code' => 1, 'data' => $list3]);
            } else {
                return json(['code' => 0, 'msg' => "没有更多内容了"]);
            }
        } else {
            $list4 = CollectModel::where(['user_id' => $user_id, 'type' => ['>', 3]])
                ->order('create_time desc')
                ->limit($len, 6)->select();
            if ($list4) {
                foreach ($list4 as $k => $v) {
                    $list4[$k]['title'] = $v->News->title;
                    $list4[$k]['views'] = $v->News->views;
                    unset($list4[$k]['News']);
                }

                return json(['code' => 1, 'data' => $list4]);
            } else {

                return json(['code' => 0, 'msg' => "没有更多内容了"]);
            }
        }
    }
}