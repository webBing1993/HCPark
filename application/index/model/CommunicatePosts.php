<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/8/31
 * Time: 上午10:58
 */

namespace app\index\model;


use think\Model;

class CommunicatePosts extends  Model
{


    protected $insert = [
        'create_time' => NOW_TIME,
        'comments' => 0,
        'status'=>1
    ];

    protected  $type=[

        'create_time'=>'strtotime'
    ];

    public  function  user(){

        return $this->hasOne('WechatUser','userid','user_id');

    }

    public  function  comment(){




        return $this->hasMany('CommunicateComment','target_id','id');

    }


}