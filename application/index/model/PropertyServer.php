<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/8/18
 * Time: 上午9:29
 */
namespace app\index\model;

use think\Model;

class PropertyServer extends Model
{

    protected $insert = [
        'clear_time' => NOW_TIME,
    ];


}