<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/15
 * Time: 10:16
 */
namespace app\index\model;


use think\Model;

class WaterService extends Model
{
    protected $dateFormat ="Y-m-d";
    protected $insert = [
        'create_time' => NOW_TIME,
    ];
}