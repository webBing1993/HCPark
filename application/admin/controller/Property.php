<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/8/16
 * Time: 下午2:08
 */
namespace app\admin\controller;

use app\index\model\PropertyServer;
use app\common\behavior\Service as ServiceModel;

class Property extends Admin
{
    /*报修服务列表*/
    public function index(){

        $parkid =session("user_auth")['park_id'];
        $status_change = input('status_type');
        $map=[
            'park_id'=> $parkid,
            'type'=>['<',4],
            'status'=>['in',[0,1,2]]
        ];
        if($status_change!==null&&$status_change!=-1){
            $map['status']=$status_change;

        }
        $list=PropertyServer::where($map)->order('create_time desc')->paginate(12,false,['query' => request()->param()]);
        $list2=PropertyServer::where(['park_id'=> $parkid,'type'=>['<',4],'status'=>0])->order('create_time desc')->select();
        int_to_string($list,['type'=>[1=>'空调报修',2=>"电梯报修",3=>"其他报修"]]);
        int_to_string($list,['status'=>[0=>"进行中",1=>"审核成功",2=>"审核失败",3=>'服务完成']]);
        $this->assign("list",$list);
        $this->assign("count",count($list2));
        $this->assign('checkType',$status_change);

        return $this->fetch();
    }
    /*保洁服务列表*/
    public function clear(){
        $parkid =session("user_auth")['park_id'];
        $status_change = input('status_type');
        $map=[
            'park_id'=> $parkid,
            'type'=>4,
            'status'=>['in',[0,1,2]]
        ];
        if($status_change!==null&&$status_change!=-1){
            $map['status']=$status_change;

        }


        $list=PropertyServer::where($map)->order('id desc')->paginate(12,false,['query' => request()->param()]);
        $list2=PropertyServer::where(['park_id'=> $parkid,'type'=>4,'status'=>0])->order('id desc')->select();
        int_to_string($list,['type'=>[4=>'室内保洁']]);
        int_to_string($list,['status'=>[0=>"进行中",1=>"已完成",2=>"审核失败"]]);
        $this->assign("list",$list);
        $this->assign('count',count($list2));
        $this->assign('checkType',$status_change);
        return $this->fetch();



    }
    /*修改报修服务和保洁服务的状态值*/
    public  function changeStatus(){
        $id=input('id');
        $uid = input('del');
        $remark = input('check_remark');
        $res=PropertyServer::where('id',$id)->update(['status'=>$uid,'check_remark'=>$remark]);
        $userInfo = PropertyServer::where('id',$id)->find();
        $userType = $userInfo['type'];
        $userId = $userInfo['user_id'];
        if ($uid == -1){
            $msg = "删除成功";
            $msgs = "删除失败";
        }else{
            $msg = "审核成功";
            $msgs = "审核失败";
        }
        if ($res){

            if ($uid == 1){
                if ($userType == 4){
                    $message = [
                        "title" => "保洁服务提示",
                        "description" => "您的保洁服务园区已确认，稍后将有服务人员联系您，请您耐心等待",
                        "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/4/can_check/no/id/' . $id
                    ];
                }else{
                    $message = [
                        "title" => "物业报修提示",
                        "description" => "您的报修园区已确认，维修人员将稍后进行维修，请您耐心等待",
                        "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/2/can_check/no/id/' . $id
                    ];
                }
                ServiceModel::sendPersonalMessage( $message,$userId);

            }elseif ($uid == 2){
                if ($userType == 4){
                    $message = [
                        "title" => "保洁服务提示",
                        "description" => "报修服务暂时无法提供\n备注：".$userInfo['check_remark'],
                        "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/4/can_check/no/id/' . $id
                    ];
                }else{
                    $message = [
                        "title" => "物业报修提示",
                        "description" => "报修服务暂时无法提供\n备注：".$userInfo['check_remark'],
                        "url" => 'http://' . $_SERVER['HTTP_HOST'] . '/index/service/historyDetail/appid/2/can_check/no/id/' . $id
                    ];
                    ServiceModel::sendPersonalMessage( $message,$userId);
                }

            }else{

            }

            $this->success( $msg);
        }else{

            $this->error( $msgs);
        }

    }

    /*显示凭证*/
    public function showImage(){
        $id = input("id");
        $html="";
        $feepayment = PropertyServer::get($id);
        if ($feepayment['image']){
            $image = json_decode($feepayment['image']);

            foreach($image as $value){
                $html .= "<div class='col-md-4'><img class='front_cover_img' src='$value' style='width: 150px;height: 200px;'></div>";

            }
        }

        return $html;

    }
    /*显示物业上传维修凭证*/
    public function showProof(){
        $id = input("id");
        $html="";
        $feepayment = PropertyServer::get($id);
        if ($feepayment['proof']){
            $image = json_decode($feepayment['proof']);

            foreach($image as $value){
                $html .= "<div class='col-md-4'><img class='front_cover_img' src='$value' style='width: 150px;height: 200px;'></div>";

            }
        }

        return $html;

    }

    //逻辑删除
    public function moveToTrash() {
        $ids = input('ids/a');
        $result = PropertyServer::where('id', 'in', $ids)->update(['status' => -1]);
        if($result) {
            return $this->success('删除成功');
        } elseif(!$result) {
            return $this->error('删除失败');
        }
    }




}