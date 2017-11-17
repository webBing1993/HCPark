<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/11/17
 * Time: 上午9:12
 */

namespace app\admin\controller;

use app\common\model\TrademarkInquire;
use app\common\model\TrademarkAdvisory;

//商标管理
class Trademark extends Admin
{
    //商标管理（回复）
    public function index()
    {
        $parkid = session('user_auth')['park_id'];
        $inquire = new TrademarkInquire();
        $map = ['status' => ['neq', -1],
            'park_id' => $parkid
        ];
        $list = $inquire->where($map)->order('status asc,create_time desc')->paginate(12, false, ['query' => request()->param()]);
        foreach ($list as $value) {
            $value['name'] = isset($value->user->name) ? $value->user->name : "";
            $value['mobile'] = isset($value->user->mobile) ? $value->user->mobile : "";
        }
        $this->assign('list', $list);
        return $this->fetch();

    }

    //商标咨询
    public function advisory()
    {
        if (IS_POST) {
            //咨询回复
            $id = input('id');
            $respond = input('respond');
            $ta = new TrademarkAdvisory();
            $res = $ta->where('id', $id)->update(['end_time' => time(), 'respond' => $respond, 'status' => 1]);
            if ($res) {
                return $this->success('回复成功');
            } else {
                return $this->error('失败', '', $ta->getError());
            }
        } else {
            $parkid = session('user_auth')['park_id'];
            $advisory = new TrademarkAdvisory();
            $map = ['status' => ['neq', -1],
                'park_id' => $parkid
            ];
            $list = $advisory->where($map)->order('status asc')->paginate(12, false, ['query' => request()->param()]);
            $this->assign('list', $list);
            return $this->fetch();
        }
    }

    //商标咨询删除
    public function advisoryDel()
    {
        $id = input('ids/a');
        $ta = new TrademarkAdvisory();
        $res = $ta->where(['id' => ['in', $id]])->Update(['status' => -1]);
        if ($res) {
            return $this->success("删除成功");

        } else {
            return $this->error("删除失败", '', $ta->getError());
        }
    }

    //商标查询删除
    public function inquireDel()
    {
        $id = input('ids/a');
        $ti = new TrademarkInquire();
        $res = $ti->where(['id' => ['in', $id]])->Update(['status' => -1]);
        if ($res) {
            return $this->success("删除成功");
        } else {
            return $this->error("删除失败", '', $ti->getError());
        }
    }

    //商标查询详情页
    public function show()
    {
        $id = input('id');
        $inquire = new TrademarkInquire();
        $info =$inquire->get($id);
        $info['name']=isset($info->user->name)?$info->user->name:"";
        $info['mobile']=isset($info->user->mobile)?$info->user->mobile:"";
        $info['submit_img']=json_decode($info['submit_img']);
        if($info['status']==1){
            $info['status_text']="已联系";
        }else{
            $info['status_text']="未联系";
        }
        $this->assign('info',$info);
        return $this->fetch();


    }
  //商标查询回复
     public function  reply(){
      $id = input('id');
      $back_img = input('back_img');
      $ti = new TrademarkInquire();
      $res = $ti->where('id',$id)->update(['back_img'=>$back_img,'status'=>1,'end_time'=>time()]);
      if($res){
          return $this->success('成功');
      }else{
          return $this->error('失败','',$res->getError());
      }

     }



}