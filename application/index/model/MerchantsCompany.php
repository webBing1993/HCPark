<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/9/1
 * Time: 下午4:30
 */

namespace app\index\model;


use think\Model;

class MerchantsCompany extends  Model
{
public  function  user(){

    return $this->hasOne('WechatUser','userid','user_id');

}
}