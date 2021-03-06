<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/8/28
 * Time: 下午3:24
 */
namespace app\index\controller;
use  app\common\model\PartyBuilding as PartyBuildingModel;
use app\common\model\PartyComment;
use app\index\model\WechatDepartment;
use app\index\model\WechatUser;

class Partybuild extends Base{

    public function index(){
        $list1=PartyBuildingModel::where(['type'=>1,'status'=>1])->order('id desc')->find();
        $list2=PartyBuildingModel::where(['type'=>2,'status'=>1])->order('id desc')->find();
        $list3=PartyBuildingModel::where(['type'=>3,'status'=>1])->order('id desc')->find();
        $this->assign('list1',$list1);
        $this->assign('list2',$list2);
        $this->assign('list3',$list3);

        return $this->fetch();
    }
        /*党建新闻列表*/
    public function showList(){
        $type= input('type');
        $list=[];
        switch ($type){
            case 1:
                $listMap = [
                    'type' => 1,
                    'status' => 1,
                ];
                $list = PartyBuildingModel::where($listMap)->order('create_time desc')->limit(6)->select();

                break;
            case 2:
                $listMap = [
                    'type' => 2,
                    'status' => 1,
                ];
                $list = PartyBuildingModel::where($listMap)->order('create_time desc')->limit(6)->select();

                break;
            case 3:
                $listMap = [
                    'type' => 3,
                    'status' => 1,
                ];
                $list = PartyBuildingModel::where($listMap)->order('create_time desc')->limit(6)->select();

                break;
            default :

                break;
        }


        $this->assign("list",json_encode($list));
        $this->assign('type',$type);

        return $this->fetch();

    }
        /*新闻详细页面*/
    public function detail(){
        // 添加阅读量
        PartyBuildingModel::where('id', input('id'))->setInc('views');
        $id= input('id');
        $news = PartyBuildingModel::get(input('id'));
        $this->assign('news', $news);
        // 评论列表
        $map = [
            'target_id' => $id
        ];
        $comments = PartyComment::where($map)->order("create_time desc ")->limit(6)->select();
        $count = PartyComment::where($map)->count();
        foreach( $comments as $v){
            $v['header']=isset($v->wechatuser->header)?$v->wechatuser->header:"";
            $v['avatar']=isset($v->wechatuser->avatar)?$v->wechatuser->avatar:"";
            if (empty($v['header'])){
                $v['header'] = $v['avatar'];
                unset($v['avatar']);
            }
        }
        $this->assign('count',$count);
        $this->assign('comments', json_encode($comments));
        $this->assign('id',$id);


        return $this->fetch();
    }
        /*获取更多评论*/
    public function getMore(){
        $len = input("length");
        $newsId = input("newsId");
        $comments = PartyComment::where(['target_id' => $newsId])
            ->order("create_time desc")
            ->limit($len, 6)
            ->select();
        if ($comments) {
            foreach( $comments as $v){
                $v['header']=isset($v->wechatuser->header)?$v->wechatuser->header:"";
                $v['avatar']=isset($v->wechatuser->avatar)?$v->wechatuser->avatar:"";
                if (empty($v['header'])){
                    $v['header'] = $v['avatar'];
                    unset($v['avatar']);
                }
            }

            return json(['code' => 1, 'data' => $comments]);
        } else {

            return json(['code' => 0, 'msg' => "没有更多内容了"]);
        }


    }

         /*获取更多新闻*/
    public function getMoreList (){
        $type = input("type");
        $len =input("length");
        if ($type ==1 ){
            $listMap = [
                'type' => 1,
                'status' => 1,
            ];
            $list = PartyBuildingModel::where($listMap)->order('create_time desc')->limit($len,6)->select();
            if ($list){

                return json(['code'=>1,'data'=>$list]);
            }else{

                return json(['code'=>0,'data'=>"没有更多了"]);
            }
        }else if($type ==2){
            $listFileMap = [
                'type' => 2,
                'status' => 1
            ];
            $list = PartyBuildingModel::where($listFileMap)->order('create_time desc')->limit($len,6)->select();
            if ($list){

                return json(['code'=>2,'data'=>$list]);
            }else{

                return json(['code'=>0,'data'=>"没有更多了"]);
            }
        }else{
            $articleMap= [
                'type' => 3,
                'status' => 1
            ];
            $list =  PartyBuildingModel::where($articleMap)->order('create_time desc')->limit($len,6)->select();
            if ($list){

                return json(['code'=>3,'data'=>$list]);
            }else{

                return json(['code'=>0,'data'=>"没有更多了"]);
            }
        }

    }
//总数60人，男42人，女18人，年龄20-30岁16人，30-40岁20人，40-50岁10人，50岁以上4人，学历本科以下3人，本科36人，硕士6人，博士5人，党支部数量就写1个
        /*党员统计*/
    public function countParty(){
        /*人数统计*/
        $parkId = session("park_id");
//        $data[0]= WechatUser::where(['age'=>['<',20]])->count();
//        $data[1]= WechatUser::where(['age'=>['between',[20,30]]])->count();
//        $data[2]= WechatUser::where(['age'=>['between',[30,40]]])->count();
//        $data[3]= WechatUser::where(['age'=>['between',[40,50]]])->count();
//        $data[4]= WechatUser::where(['age'=>['>',50]])->count();
        $data[0]= 0;
        $data[1]= 16;
        $data[2]= 20;
        $data[3]= 10;
        $data[4]= 4;
        /*男女比例统计*/
//        $man = WechatUser::where(['age'=>['>',0],'gender'=>1])->count();
//        $women= WechatUser::where(['age'=>['>',0],'gender'=>2])->count();
        $man = 42;
        $women= 18;
        $sex[0] = 100*$man/($man+$women);
        $sex[1] = 100*$women/($man+$women);
        /*教育统计*/
//        $education[0] = WechatUser::where(['age'=>['>',0],'education'=>0])->count();
//        $education[1] = WechatUser::where(['age'=>['>',0],'education'=>1])->count();
//        $education[2] = WechatUser::where(['age'=>['>',0],'education'=>2])->count();
//        $education[3] = WechatUser::where(['age'=>['>',0],'education'=>3])->count();
        $education[0] = 3;
        $education[1] = 36;
        $education[2] = 6;
        $education[3] = 5;
        /*党支部统计*/
//        $count = WechatUser::where(['age'=>['>',0]])->group('party_branch')->count();
        $count = 1;
        $this->assign("countParty",$count);
        $this->assign('data',json_encode($data));
        $this->assign('sex',json_encode($sex));
        $this->assign('education',json_encode($education));

        return $this->fetch();

    }








}