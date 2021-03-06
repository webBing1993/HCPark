<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/12/13
 * Time: 上午9:41
 */

namespace app\index\controller;

use app\index\model\Activity as ActivityModel;
use app\index\model\ActivityComment;
use app\index\model\WechatUser;
use MongoDB\Driver\WriteError;

class Activity extends Base
{
    //活动报名主页
    public function index()
    {
        $activity = new ActivityModel();
        $list = $activity->getListbyParkid();
        $this->assign('list', json_encode($list));
        return $this->fetch();
    }

    //活动报名详情页
    public function detail()
    {
        $data = input('');
        $info =ActivityModel::where('id',$data['id'])->find();
        $comment = ActivityComment::where(['activity_id'=>$data['id'],'userid'=>session('userId'),'status'=>['in',[0,1]]])->select();
        $is_sgin=count($comment)>0?"yes":"no";
        $info['is_sign']=$is_sgin;

        $count = ActivityComment::where(['activity_id'=>$data['id'],'status'=>['in',[1]]])->count();

        $userid =  session('userId');
        $user = WechatUser::where('userid',$userid)->find();
        $userInfo = ActivityModel::where('id',$data['id'])->find();
        $map=[
            'name'=>$user['name'],
            'department'=>isset($user->departmentName->name)?$user->departmentName->name:"",
            'mobile'=>$user['mobile'],
        ];
        $this->assign('user',json_encode($map));
        $this->assign('count',$count);
        $this->assign('info',json_encode($info));
        return $this->fetch();
    }

    //报名
    public function signUp()
    {   $data = input('');
        if(IS_POST){
            $activity = new ActivityModel();
            $Comment  =  new ActivityComment();
            $activityInfo = $activity->where('id',$data['activity_id'])->find();
            //活动不处于 开始报名 状态
            if($activityInfo['status']!=2){
                return $this->error('当前活动不可报名中');
            }
            //当前时间已超过活动开始时间
            if(time()>$activityInfo['start_time']){
                return $this->error('当前活动不可报名中');
            }
            $park_id = session('park_id');
            $user_id =session('userId');
            $data['userid']=$user_id;
            unset($data['id']);
            $data['park_id']=$park_id;
            $data = $Comment->save($data);
            if($data){
                return $this->success('报名成功');

            }else{
                return $this->error("保存失败");
            }
        }else{
         $userid =  session('userId');
         $user = WechatUser::where('userid',$userid)->find();
            $info = ActivityModel::where('id',$data['id'])->find();
         $map=[
             'name'=>$user['name'],
             'department'=>isset($user->departmentName->name)?$user->departmentName->name:"",
             'mobile'=>$user['mobile'],
             'activity_id'=>$data['id'],
             'title'=>$info['name'],
             'front_cover'=>$info['front_cover'],
             'registration_required'=>$info['registration_required'],
             'status'=>$info['status']
         ];
         $this->assign('user',json_encode($map));
        }

        return $this->fetch('sign_up');
    }
    public function needKnow()
    {   $data = input('');
        $activity = new ActivityModel();
        $Comment  =  new ActivityComment();
        $activityInfo = $activity->where('id',$data['id'])->find();
        $this->assign('info',json_encode($activityInfo['registration_required']));
        return $this->fetch('need_know');
    }


}