<?php
/**
 * Created by PhpStorm.
 * User: ztos
 * Date: 2017/8/31
 * Time: 上午9:22
 */

namespace app\index\controller;


use app\index\model\CommunicateGroup;
use app\index\model\CommunicateUser;
use app\index\model\WechatUser;
use app\index\model\CommunicatePosts;
use app\index\model\CommunicateComment;
use wechat\TPWechat;
use think\Loader;

class Communication extends Base
{
    /*首页*/
    public function index()
    {
        $user_id = session('userId');
        $cgroup = new CommunicateGroup();
        $cuser = new CommunicateUser();
        $user = new WechatUser();
        $map2 = [
            'status' => 1,
            'park_id' => session('park_id')
        ];
       if(session('park_id')==1){
           unset($map2['park_id']);
       }

        $groupList = $cgroup->where($map2)->select();
        $map = [
            'user_id' => $user_id,
            'status' => array('gt', 1),

        ];
        foreach ($groupList as $value) {
            $map['group_id'] = $value['id'];
            $is_join = $cuser->where($map)->find();
            //0 未加入  1 已经加入
            $value['is_join'] = $is_join ? 1 : 0;

        }
        $userinfo = $user->where('userid', $user_id)->find();
        if (empty($userinfo['header'])) {
            $header = $userinfo['avatar'];
        } else {
            $header = $userinfo['header'];

        }

        $this->assign('header', $header);
        $this->assign('list', $groupList);

        return $this->fetch();
    }

    /*个人*/
    public function personal()
    {
        $userid = session('userId');
        $cuser = new CommunicateUser();
        $map = [
            'user_id' => $userid,
            'status' => 3
        ];

        $count = $cuser->where($map)->count();
        //0 不是管理员 1 是管理员
        $is_manageer = $count > 0 ? 1 : 0;

        $this->assign('is_manage', $is_manageer);
        return $this->fetch();
    }

    /*加入*/
    public function join()
    {
        $cgroup = new CommunicateGroup();
        $wechat = new WechatUser();
        $cuser = new CommunicateUser();
        if (IS_POST) {
            $user_id = session('userId');
            $remark = input('remark');
            $map = [
                'group_id' => input('group_id'),
                'user_id' => $user_id,
                'status' => array('gt', 0)
            ];
            $is_join = $cuser->where($map)->find();
            if ($is_join) {
                return $this->error("已经加入或者正在审核中");
            } else {
                $map['status'] = 1;
                $map['remark'] = $remark;
                $reult = $cuser->save($map);
                if ($reult) {
                    //todo 推送给群审核人员（文字卡片推送）
                    $id = $cuser->getLastInsID();
                    $info = $cuser->where('id', $id)->find();
                    $map = array();
                    $map['group_id'] = $info['group_id'];
                    $map['status'] = 3;
                    $useridlist = "";
                    $list = $cuser->where($map)->select();
                    foreach ($list as $value) {
                        $useridlist .= '|' . $value['user_id'];
                    }
                    $username = isset($info->user->name) ? $info->user->name : "";
                    $mobile = isset($info->user->mobile) ? $info->user->mobile : "";
                    $department = isset($info->user->departmentName->name) ? $info->user->departmentName->name : "";
                    Loader::import('wechat\TPWechat', EXTEND_PATH);
                    $weObj = new TPWechat(config('Communication'));
                    $data = [
                        "touser" => $useridlist,
                        'safe' => 0,
                        'msgtype' => 'textcard',
                        'agentid' => 1000013,
                        'textcard' => [
                            'title' => "加群申请",
                            'description' => "您有一个加群申请需要审核\n姓名：" . $username . " 手机：" . $mobile . "   公司名称：" . $department . "   备注：" . $remark,
                            'url' => 'http://xk.0519ztnet.com/index/Communication/myCheck',
                        ]
                    ];
                    $result1 = $weObj->sendMessage($data);
                    //var_dump($result1);
                    //var_dump($weObj->errCode.'|'.$weObj->errMsg);
                    if ($result1['errcode'] == 0) {
                        return $this->success('提交成功');
                    } else {
                        return $this->error('推送失败');
                    }
                } else {
                    return $this->error("申请失败");
                }
            }
        } else {
            $group_id = input('group_id');
            $user_id = session('userId');
            $map = [
                'group_id' => $group_id,
                'user_id' => $user_id,
                'status' => array('gt', 0)
            ];
            $groupinfo = $cgroup->where('id', $group_id)->find();
            $status = $cuser->where($map)->field('status')->find();
            $groupinfo['is_join'] = $status['status'] ? $status['status'] : 0;
            $user = $wechat->where('userid', $user_id)->find();
            $userinfo = [
                'name' => $user['name'],
                'mobile' => $user['mobile'],
                'department' => isset($user->departmentName->name) ? $user->departmentName->name : ""
            ];
            $this->assign('group', $groupinfo);
            $this->assign('user', $userinfo);

            return $this->fetch();


        }
    }


    /*帖子列表*/
    public function postsList()
    {
        $cp = new CommunicatePosts();
        $cgroup = new CommunicateGroup();
        $map = [
            'group_id' => input('group_id'),
            'status' => 1
        ];
        $posts = $cp->where($map)->order('id desc')->select();
        $groupInfo = $cgroup->where('id', input('group_id'))->find();
        $postsList = array();
        foreach ($posts as $value) {
            $data = [
                'name' => isset($value->user->name) ? $value->user->name : "",
                'title' => $value['title'],
                'content' => $value['content'],
                'img' => !empty($value['img']) ? json_decode($value['img']) : "",
                'comments' => $value['comments'],
                'create_time' => $value['create_time'],
                'id' => $value['id']
            ];
            $avatar = isset($value->user->avatar) ? $value->user->avatar : "";
            $header = isset($value->user->header) ? $value->user->header : "";
            $data['header'] = empty($header) ? $avatar : $header;
            array_push($postsList, $data);
        }
        unset($groupInfo['status']);
        unset($groupInfo['content']);
//        dump($postsList);
        $this->assign('list', $postsList);
        $this->assign('group', $groupInfo);
        return $this->fetch();
    }

    /*帖子详情*/
    public function postDetails()
    {
        $weuser = new  WechatUser();
        $post = CommunicatePosts::get(input('id'));
        $post['img'] = !empty($post['img']) ? json_decode($post['img']) : "";
        $post['user_name'] = isset($post->user->name) ? $post->user->name : "";
        $header = isset($post->user->header) ? $post->user->header : "";
        $avatar = isset($post->user->avatar) ? $post->user->avatar : "";
        $post['header'] = empty($header) ? $avatar : $header;
        unset($post['user']);
        $this->assign('post', $post);
        // 评论列表
        $map = [
            'target_id' => input('id')
        ];
        $comments = CommunicateComment::where($map)->order('id desc')->limit(6)->select();
        foreach ($comments as $value) {
            $userinfo = WechatUser::where('userid', $value['user_id'])->field('header,avatar')->find();
            $head = isset($userinfo['header']) ? $userinfo['header'] : "";
            $ava = isset($userinfo['avatar']) ? $userinfo['avatar'] : "";
            $value['header'] = empty($head) ? $ava : $head;
            $value['create_time'] = date("Y-m-d H:m", $value['create_time']);
        }
        //$count = CommunicateComment::where($map)->count();
        //echo json_encode($post);
//        echo json_encode($comments);
        $this->assign('comments', json_encode($comments));

        return $this->fetch();
    }

    /*写帖子页面*/
    public function writePost()
    {
        $post = new CommunicatePosts();
        if (IS_POST) {
            //$data = input('data');
            $map = [
                'title' => input('title'),
                'content' => input('content'),
                'img' => input('img'),
                'user_id' => session('userId'),
                'group_id' => input('group_id')
            ];
            $result = $post->save($map);
            if ($result) {
                return $this->success("成功");
            } else {

                return $this->error("失败");
            }
        } else {
            $group_id = input('group_id');
            $this->assign('group_id', $group_id);
            return $this->fetch();
        }
    }

    /*评论*/
    public function comment()
    {
        $userinfo = WechatUser::where('userid', session('userId'))->field('name,header,avatar')->find();
        $data = [
            'target_id' => input('id'),
            'user_id' => session('userId'),
            'content' => input('content'),
        ];
        if (!empty($userinfo['header'])) {
            $data['header'] = $userinfo['header'];
        } else {
            $data['header'] = !empty($userinfo['avatar']) ? $userinfo['avatar'] : "";
        }
        $post = CommunicatePosts::get(input('id'));
        $data['user_name'] = $userinfo['name'];
        $result = CommunicateComment::create($data);
        if ($result) {
            $post['comments'] += 1;
            $result['create_time'] = date("Y-m-d H:m", $result['create_time']);
            $post->save();
            return $this->success('评论成功', '', $result);
        } else {
            return $this->error('评论失败');
        }
    }

    //评论分页
    public function moreComment()
    {
        $lastId = input('lastId', 0);
        $map = [
            'target_id' => input('id')
        ];
        if ($lastId != 0) {
            $map['id'] = ['<', $lastId];
        }
        $comments = CommunicateComment::where($map)->order('id desc')->limit(6)->select();
        foreach ($comments as $value) {
            $userinfo = WechatUser::where('userid', $value['user_id'])->field('header,avatar')->find();
            $head = isset($userinfo['header']) ? $userinfo['header'] : "";
            $ava = isset($userinfo['avatar']) ? $userinfo['avatar'] : "";
            $value['header'] = empty($head) ? $ava : $head;

            $value['create_time'] = date("Y-m-d H:m", $value['create_time']);
        }
        return json(['total' => count($comments), 'comments' => $comments]);
    }


    /*我的申请*/
    public function myApplication()
    {
        $userid = session('userId');
        $cuser = new CommunicateUser();
        $map = [
            'user_id' => $userid,
            'status' => array('lt', 4)
        ];
        $list = $cuser->where($map)->order('id desc')->select();
        foreach ($list as $value) {

            $value['group_name'] = isset($value->group->group_name) ? $value->group->group_name : "";
        }
        $this->assign('list', $list);

        return $this->fetch();
    }

    /*我的审核*/
    public function myCheck()
    {
        $userid = session('userId');
        $cuser = new CommunicateUser();
        if (IS_POST) {
            $id = input('id');
            $user = $cuser->get($id);
            //审核通过
            if (input('type') == 1) {
                $user['status'] = 2;
                $user->save();
                $msg = '通过';
            } //审核失败
            else {
                $user['status'] = -1;
                $user->save();
                $msg = '不通过';
            }

            Loader::import('wechat\TPWechat', EXTEND_PATH);
            $weObj = new TPWechat(config('Communication'));
            $groupname = isset($user->group->group_name) ? $user->group->group_name : "";
            $data = [
                "touser" => $user['user_id'],
                'safe' => 0,
                'msgtype' => 'text',
                'agentid' => 1000013,
                'text' => [
                    'title' => "加群申请",
                    'content' => '您申请加入“' . $groupname . '”群，审核结果：' . $msg . '',

                ]
            ];
            $result1 = $weObj->sendMessage($data);
            //var_dump($result1);
            //var_dump($weObj->errCode.'|'.$weObj->errMsg);
            if ($result1['errcode'] == 0) {
                return $this->success('提交成功');
            } else {
                return $this->error('推送失败');
            }
        } else {

            $map2 = [
                'user_id' => $userid,
                'status' => 3
            ];
            $group_id = array();
            $list = $cuser->where($map2)->order('create_time desc')->select();
            foreach ($list as $value) {
                array_push($group_id, $value['group_id']);
            }

            $map = [
                'status' => array('lt', 3),
                'group_id' => array('in', $group_id)
            ];
            $list = $cuser->where($map)->order('create_time desc')->select();
            foreach ($list as $value) {
                $value['name'] = isset($value->user->name) ? $value->user->name : "";
                $value['group_name'] = isset($value->group->group_name) ? $value->group->group_name : "";
                $value['company'] = isset($value->user->departmentName->name) ? $value->user->departmentName->name : "";
                $value['mobile'] = isset($value->user->mobile) ? $value->user->mobile : "";

                unset($value['user']);
                unset($value['group']);
            }

//            echo json_encode($list);
            $this->assign('list', $list);
            return $this->fetch();
        }
    }

    /*我的发布*/
    public function myRelease()
    {
        $userid = session('userId');
        $posts = new CommunicatePosts();
        $myPosts = $posts->where('user_id', $userid)->order('id desc')->select();
        $postsList = array();
        foreach ($myPosts as $value) {
            $data = [
                'name' => isset($value->user->name) ? $value->user->name : "",
                'title' => $value['title'],
                'content' => $value['content'],
                'img' => !empty($value['img']) ? json_decode($value['img']) : "",
                'comments' => $value['comments'],
                'create_time' => $value['create_time'],
                'id' => $value['id']
            ];
            $avatar = isset($value->user->avatar) ? $value->user->avatar : "";
            $header = isset($value->user->header) ? $value->user->header : "";
            $data['header'] = empty($header) ? $avatar : $header;
            array_push($postsList, $data);
        }
        //echo  json_encode($postsList);
        $this->assign('list', $postsList);
        return $this->fetch();
    }

    /*我的评论*/
    public function myComment()
    {
        $ccomment = new CommunicateComment();
        $cpost = new  CommunicatePosts();
        $userid = session('userId');
        $commments = $ccomment->where('user_id', $userid)->order('create_time desc')->select();
        $userinfo = WechatUser::where('userid', session('userId'))->field('header,avatar')->find();
        $head = isset($userinfo['header']) ? $userinfo['header'] : "";
        $ava = isset($userinfo['avatar']) ? $userinfo['avatar'] : "";
        $myComments = array();
        foreach ($commments as $value) {
            $value['header'] = empty($head) ? $ava : $head;
            $status = isset($value->CommunicatePost->status) ? $value->CommunicatePost->status : "";
            $value['post'] = isset($value->CommunicatePost) ? $value->CommunicatePost : "";
            if ($status == 1) {
                $value['post']['user_name'] = isset($value['CommunicatePost']->user->name) ? $value['CommunicatePost']->user->name : "";
                $header = isset($value['post']->user->header) ? $value['CommunicatePost']->user->header : "";
                $avatar = isset($value['post']->user->avatar) ? $value['CommunicatePost']->user->avatar : "";
                $value['post']['header'] = !empty($header) ? $header : $avatar;
                unset($value['post']['user']);
                unset($value['CommunicatePost']);
                array_push($myComments, $value);
            }
        }

        /*  foreach ($commments as $value) {
              array_push($group_ids, $value['target_id']);
          }
          $ids = array_values(array_unique($group_ids));
          $data['id'] = array('in', $ids);
          $list = $cpost->where($data)->select();
          foreach ($list as $value) {
              $comments = $value->comment;
              $myComments = array();
              foreach ($comments as $comment) {
                  if ($comment['user_id'] == $userid && $comment['status'] == 1) {
                      array_push($myComments, $comment);
                  }
              }
              $value['mycomments'] = $myComments;

              $value['user_name'] = isset($value->user->name) ? $value->user->name : "";
              $header = isset($value->user->header) ? $value->user->header : "";
              $avatar = isset($value->user->avatar) ? $value->user->avatar : "";
              $value['header'] = !empty($header) ? $header : $avatar;
          }

          $this->assign('list', $list);

         echo json_encode($list);
          */ // echo json_encode($myComments);
        $this->assign('list', $myComments);
        return $this->fetch();
    }

    /*发送给用户已回复推送*/
    public function send($data, $weObj)
    {
        Loader::import('wechat\TPWechat', EXTEND_PATH);
        $result = $weObj->sendMessage($data);
        return $result;
    }

}