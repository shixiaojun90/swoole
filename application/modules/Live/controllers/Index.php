<?php
/*
|---------------------------------------------------------------
|  Copyright (c) 2016
|---------------------------------------------------------------
| 文件名称：控制器用户操作类
| 功能 :用户信息操作
| 作者：qieangel2013
| 联系：qieangel2013@gmail.com
| 版本：V1.0
| 日期：2016/2/25 15:42 星期四
|---------------------------------------------------------------
*/
class IndexController extends Yaf_Controller_Abstract {
  
    /**
     * 名称:  初始化一些配置文件
     */
 public function init() {
	$this->_req = $this->getRequest();
    error_reporting(E_ALL | E_STRICT);
	}

 public function logoutAction() {
     Z('zys_user')->where(array('uid' =>$_SESSION['userinfo']['uid']))->save(array('status' =>0));
     //添加异步任务
      $taskdata=array(
      'uid' =>$_SESSION['userinfo']['uid'],
      'type'=>'rsynctask',
      'state'=>'offline',
      'username' =>$_SESSION['userinfo']['username'],
      'avatar' =>$_SESSION['userinfo']['avatar']
      );
      sendtask(json_encode($taskdata,true));
      unset($_SESSION['userinfo']);
     header('Location: /live/index/login');
     exit;
	}

  public function loginAction() {
     if($this->getRequest()->isPost()){
      $registerinfo=$this->getRequest()->getPost();
      $uinfo['username']=!empty($registerinfo['account'])?trim($registerinfo['account']):'游客';
      $uinfo['passwd']=!empty($registerinfo['password'])?md5(trim($registerinfo['password']).'zys'):md5('123456zys');
      $userinfo=Z()->query('select *,count(uid) as count from zys_user where username="'.$uinfo['username'].'" and passwd="'.$uinfo['passwd'].'"');
      if($userinfo[0]['count']<1){
        echo json_encode(array('code' =>1,'msg'=>'用户名或者密码错误！','data'=>'fail'));
        die();
      }
      unset($userinfo[0]['count']);
      $_SESSION['userinfo']=$userinfo[0];
      $uinfo=array(
      'uid' =>$_SESSION['userinfo']['uid'],
      'username' =>$_SESSION['userinfo']['username'],
      'avatar' =>$_SESSION['userinfo']['avatar']
      );
      echo json_encode(array('code' =>0,'msg'=>'登录成功！','data'=>$uinfo));
      Z('zys_user')->where(array('uid' =>$_SESSION['userinfo']['uid']))->save(array('status' =>1));
      //添加异步任务
      $taskdata=array(
      'uid' =>$_SESSION['userinfo']['uid'],
      'type'=>'rsynctask',
      'state'=>'online',
      'username' =>$_SESSION['userinfo']['username'],
      'avatar' =>$_SESSION['userinfo']['avatar']
      );
      sendtask(json_encode($taskdata,true));
      die();      
     }
	}
  public function registerAction() {
     if($this->getRequest()->isPost()){
     	$registerinfo=$this->getRequest()->getPost();
      $uinfo['username']=!empty($registerinfo['email'])?trim($registerinfo['email']):'游客';
      $userinfo=Z()->query('select count(uid) as count from zys_user where username="'.$uinfo['username'].'"');
      if($userinfo[0]['count']>=1){echo json_encode(array('code' =>1,'msg'=>'用户已存在！','data'=>'fail'));die();}
      $uinfo['passwd']=!empty($registerinfo['password'])?md5(trim($registerinfo['password']).'zys'):md5('123456zys');
      $uinfo['avatar']='/public/images/'.rand(0,12).'.jpg';
      $uinfo['create_at']=date('Y-m-d H:i:s',time());
      $insertid=Z('zys_user')->add($uinfo);
      if($insertid){
        echo json_encode(array('code' =>0,'msg'=>'用户注册成功！','data'=>'success'));
        die();
      }else{
        echo json_encode(array('code' =>1,'msg'=>'用户注册失败！','data'=>'fail'));
        die();
      }
     }
	}
  public function indexAction() {
    if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    if($this->isMobile()){
          header('Location: /Live/index/mobile');
          exit;
     }
     $uinfo=array(
     	'uid' =>$_SESSION['userinfo']['uid'],
     	'username' =>$_SESSION['userinfo']['username'],
     	'avatar' =>$_SESSION['userinfo']['avatar']
     	);
     $this->getView()->assign("uinfo",json_encode($uinfo));
     $this->getView()->assign("userinfo",$uinfo);
  } 

  public function homeAction() {
    if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
  } 

  public function groupAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT a.* FROM  zys_group a,zys_relationship b WHERE a.groupid=b.groupid AND b.uid="'.$_SESSION['userinfo']['uid'].'"');
    $this->getView()->assign("frienddata",$userfriend);
  }


   public function useraplAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT a.*,b.type FROM  zys_user a,zys_friend b WHERE a.uid = b.originid AND b.type=1 AND b.targetid="'.$_SESSION['userinfo']['uid'].'"');
    $this->getView()->assign("frienddata",$userfriend);
  }

public function addgroupAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT a.* FROM  zys_user a,zys_friend b WHERE a.uid=b.targetid AND b.is_ori=1 AND b.type=2 AND b.originid="'.$_SESSION['userinfo']['uid'].'"');
    $this->getView()->assign("frienddata",$userfriend);
  }

public function addgroupcAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT a.* FROM  zys_user a,zys_friend b WHERE a.uid=b.targetid AND b.is_ori=1 AND b.type=2 AND b.originid="'.$_SESSION['userinfo']['uid'].'"');
    $this->getView()->assign("frienddata",$userfriend);
  }

public function msgboxAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
}

public function findAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
}

public function getmsgcountAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT count(id) as count FROM  zys_message WHERE type=2 AND lread=0 AND action IN(2,3,5,6) AND fromid='.$_SESSION['userinfo']['uid']);
    if($userfriend[0]['count']){
    	echo json_encode(array('code' =>0,'msg'=>'获取消息成功！','data'=>$userfriend[0]['count']));
        die();
    }else{
    	echo json_encode(array('code' =>1,'msg'=>'获取消息失败！','data'=>0));
    	die();
    }
}


public function getmsgAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT a.*,b.mesage,b.action FROM zys_user a, zys_message b WHERE a.uid=b.fromid AND b.type=2 AND b.action IN(2,3,5,6) AND b.fromid="'.$_SESSION['userinfo']['uid'].'" ORDER BY b.create_at ASC');
    $userdata=array(
    	'id'=>$_SESSION['userinfo']['uid'],
    	'uid'=>$_SESSION['userinfo']['uid'],
    	'from_group'=>0,
    	'type'=>1,
    	'remark'=>'有问题要问',
    	'href'=>null,
    	'lread'=>1,
    	'time'=>'刚刚'
    );
    $result=array(
    	'code' =>0,
    	'pages'=>1,
    	'data'=>[]
    	 );
    foreach ($userfriend as &$v) {
    	switch ($v['action']) {
			case 1:
			    $userdata['content']=$v['mesage'];
			    $userdata['from']=$v['uid'];
              $userdata['user']=array(
                'id' =>$v['uid'],
                'avatar'=>$v['avatar'],
                'username'=>$v['username'],
                'sign'=>$v['sign']
             );
			    break;
			case 2:
			    $userdata['content']=$v['mesage'];
			    $userdata['from']=null;
			    $userdata['user']=array(
			    	'id' =>null
			    	 );
			    break;
			case 3:
			    $userdata['content']=$v['mesage'];
			    $userdata['from']=null;
			    $userdata['user']=array(
			    	'id' =>null
			    	 );
			    break;
     default:
          case 3:
          $userdata['content']=$v['mesage'];
          $userdata['from']=null;
          $userdata['user']=array(
            'id' =>null
             );
          break;
		}
		
		$result['data'][]=$userdata;
		Z('zys_message')->where(array('toid' =>$v['uid'],'type'=>2))->save(array('lread' =>1));
    }
    if(empty($result['data'])){
    	echo json_encode(array('code' =>1,'msg'=>'未获取到消息！','data'=>[]));
        die();
    }else{
    	echo json_encode($result);
    	die();
    }
    
}

   public function chatlogmAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }

  }

  public function chatlogmjAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $id = $this->getRequest()->getQuery("id");
    $type =$this->getRequest()->getQuery("type");

    switch ($type) {
      case 'group':
         $resultdata=Z()->query('SELECT mesage FROM  zys_message WHERE type=1 AND groupid ='.$id);
        break;
      case 'friend':
        $resultdata=Z()->query('SELECT mesage FROM  zys_message WHERE type=0 AND (fromid='.$id.' AND toid='.$_SESSION['userinfo']['uid'].') OR (fromid='.$_SESSION['userinfo']['uid'].' AND toid='.$id.') ORDER BY create_at ASC');
        break;
      default:
        break;
    }
    foreach ($resultdata as &$v) {
      $tmpjson=json_decode($v['mesage'],true);
      $tmparr=array();
      switch ($tmpjson['type']) {
        case 'group':
          $tmparr['username']=$tmpjson['Username'];
          $tmparr['id']=$tmpjson['uid'];
          $tmparr['avatar']=$tmpjson['Img'];
          $tmparr['timestamp']=$tmpjson['Timestamp'];
          $tmparr['content']=$tmpjson['Data'];
          break;
        case 'friend':
          $tmparr['username']=$tmpjson['Username'];
          $tmparr['id']=$tmpjson['uid'];
          $tmparr['avatar']=$tmpjson['Img'];
          $tmparr['timestamp']=$tmpjson['Timestamp'];
          $tmparr['content']=$tmpjson['Data'];
          break;
        default:
          break;
      }
      $v=$tmparr;
    }
    $result= array(
      "code"=>"0",
      "msg"=>"liveim",
      "data"=>$resultdata
      );   
    echo json_encode($result);
    exit;
  }
  
  public function chatlogAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $id = $this->getRequest()->getQuery("id");
    $type =$this->getRequest()->getQuery("type");

    switch ($type) {
      case 'group':
         $resultdata=Z()->query('SELECT mesage FROM  zys_message WHERE type=1 AND groupid ='.$id);
        break;
      case 'friend':
        $resultdata=Z()->query('SELECT mesage FROM  zys_message WHERE type=0 AND (fromid='.$id.' AND toid='.$_SESSION['userinfo']['uid'].') OR (fromid='.$_SESSION['userinfo']['uid'].' AND toid='.$id.') ORDER BY create_at ASC');
        break;
      default:
        break;
    }
    foreach ($resultdata as &$v) {
      $tmpjson=json_decode($v['mesage'],true);
      $tmparr=array();
      switch ($tmpjson['type']) {
        case 'group':
          $tmparr['username']=$tmpjson['Username'];
          $tmparr['id']=$tmpjson['uid'];
          $tmparr['avatar']=$tmpjson['Img'];
          $tmparr['timestamp']=$tmpjson['Timestamp'];
          $tmparr['content']=$tmpjson['Data'];
          break;
        case 'friend':
          $tmparr['username']=$tmpjson['Username'];
          $tmparr['id']=$tmpjson['uid'];
          $tmparr['avatar']=$tmpjson['Img'];
          $tmparr['timestamp']=$tmpjson['Timestamp'];
          $tmparr['content']=$tmpjson['Data'];
          break;
        default:
          break;
      }
      $v=$tmparr;
    }
    $result= array(
      "code"=>"0",
      "msg"=>"liveim",
      "data"=>$resultdata
      );   
    $this->getView()->assign("resultdata",json_encode($result));
  }

  public function friendAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT a.* FROM  zys_user a,zys_friend b WHERE a.uid=b.targetid AND b.is_ori=1 AND b.type=2 AND b.originid="'.$_SESSION['userinfo']['uid'].'"');
    foreach ($userfriend as &$v) {
    	$v['id']=$v['uid'];
    	switch ($v['status']) {
    		case 0:
    			$v['status']='offline';
    			break;
    		case 1:
    			$v['status']='online';
    			break;
    	}
    	unset($v['passwd']);
    }
    $this->getView()->assign("frienddata",$userfriend);
  }

  public function addfriendmAction() {
    if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT * FROM  zys_user WHERE uid!="'.$_SESSION['userinfo']['uid'].'"');
    $userf=Z()->query('SELECT * FROM  zys_friend WHERE originid="'.$_SESSION['userinfo']['uid'].'"');
    $mtmp=0;
    foreach ($userfriend as &$value) {
    	  if($userf){
			      foreach ($userf as &$v) {

			        if($value['uid']==$v['targetid']){
			          $mtmp=1;
			          $value['state']=$v['type'];
			          switch ($v['type']) {
			            case 1:
			              $value['statem']='已申请';
			              break;
			            case 2:
			              $value['statem']='好友';
			              break;
			            case 3:
			              $value['statem']='已拒绝';
			              break;
			            case 4:
			              $value['statem']='黑名单';
			              break;
			            case 5:
			              $value['statem']='已删除';
			              break;
			            default:
			               $value['statem']='申请好友';
			              break;
			          }
			        }else{
			          if($mtmp==0){
			            $value['state']=0;
			            $value['statem']='申请好友';
			          }
			          
			        }
			      }
    	  }else{
    	  	if($mtmp==0){
			     $value['state']=0;
			     $value['statem']='申请好友';
			  }
    	  }
      
      $mtmp=0;
    }
    $this->getView()->assign("frienddata",$userfriend);
  }

public function addfriendpAction() {
    if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT * FROM  zys_user WHERE uid!="'.$_SESSION['userinfo']['uid'].'"');
    $userf=Z()->query('SELECT * FROM  zys_friend WHERE originid="'.$_SESSION['userinfo']['uid'].'"');
    $mtmp=0;
    foreach ($userfriend as &$value) {
        if($userf){
            foreach ($userf as &$v) {

              if($value['uid']==$v['targetid']){
                $mtmp=1;
                $value['state']=$v['type'];
                switch ($v['type']) {
                  case 1:
                    $value['statem']='已申请';
                    break;
                  case 2:
                    $value['statem']='好友';
                    break;
                  case 3:
                    $value['statem']='已拒绝';
                    break;
                  case 4:
                    $value['statem']='黑名单';
                    break;
                  case 5:
                    $value['statem']='已删除';
                    break;
                  default:
                     $value['statem']='申请好友';
                    break;
                }
              }else{
                if($mtmp==0){
                  $value['state']=0;
                  $value['statem']='申请好友';
                }
                
              }
            }
        }else{
          if($mtmp==0){
           $value['state']=0;
           $value['statem']='申请好友';
        }
        }
      
      $mtmp=0;
    }
    $this->getView()->assign("frienddata",$userfriend);
  }


   public function addfriendAction() {
    if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $userfriend=Z()->query('SELECT * FROM  zys_user WHERE uid!="'.$_SESSION['userinfo']['uid'].'"');
    $userf=Z()->query('SELECT * FROM  zys_friend WHERE originid="'.$_SESSION['userinfo']['uid'].'"');
    //获取群组信息
    $groupdata=Z()->query('SELECT * FROM  zys_group');
    $grouparr=Z()->query('SELECT * FROM  zys_relationship WHERE uid="'.$_SESSION['userinfo']['uid'].'"');
    $mtmp=0;
    foreach ($userfriend as &$value) {
    	  if($userf){
			      foreach ($userf as &$v) {

			        if($value['uid']==$v['targetid']){
			          $mtmp=1;
			          $value['state']=$v['type'];
			          switch ($v['type']) {
			            case 1:
			              $value['statem']='已申请';
			              break;
			            case 2:
			              $value['statem']='好友';
			              break;
			            case 3:
			              $value['statem']='已拒绝';
			              break;
			            case 4:
			              $value['statem']='黑名单';
			              break;
			            case 5:
			              $value['statem']='已删除';
			              break;
			            default:
			               $value['statem']='申请好友';
			              break;
			          }
			        }else{
			          if($mtmp==0){
			            $value['state']=0;
			            $value['statem']='申请好友';
			          }
			          
			        }
			      }
    	  }else{
    	  	if($mtmp==0){
			     $value['state']=0;
			     $value['statem']='申请好友';
			  }
    	  }
      
      $mtmp=0;
    }
     //群组处理
    foreach ($groupdata as &$vv) {
      $vv['statem']='申请入群';
      $vv['state']=0;
      foreach ($grouparr as &$vvv) {
        if($vv['groupid']==$vvv['groupid']){
          $vv['statem']='已申请';
          $vv['state']=1;
        }
      }
    }
    $uinfo=array(
      'uid' =>$_SESSION['userinfo']['uid'],
      'username' =>$_SESSION['userinfo']['username'],
      'avatar' =>$_SESSION['userinfo']['avatar']
      );
     $this->getView()->assign("uinfo",json_encode($uinfo));
    $this->getView()->assign("groupdata",$groupdata);
    $this->getView()->assign("frienddata",$userfriend);
  }
     
	public function liveimAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
	}

  public function addgcAction() {
    if(!$_POST['uid']){
      echo json_encode(array('code' =>1,'msg'=>'请求失败！','data'=>'fail'));
        die();
    }else{
      $data=array(
        'groupname' => $_POST['name'], 
        'groupmaster' => $_SESSION['userinfo']['uid'],
        'avatar'=>'/public/images/logo.jpg',
        'create_at'=>date('Y-m-d H:i:s',time())
        );
       $insertid=Z('zys_group')->add($data);
      if($insertid){
        $datag= array(
            'uid' =>$_SESSION['userinfo']['uid'],
            'groupid'=>$insertid
             );
          $insertid_g=Z('zys_relationship')->add($datag);
        foreach ($_POST['uid'] as &$v) {
          $datag= array(
            'uid' =>$v,
            'groupid'=>$insertid
             );
          $insertid_g=Z('zys_relationship')->add($datag);
        }
        $groupdata=array(
            'groupname' => $_POST['name'], 
            'groupid'=>$insertid,
            'avatar'=>'/public/images/logo.jpg'
             );
        echo json_encode(array('code' =>0,'msg'=>'创建群组成功！','data'=>$groupdata));
        die();
      }else{
        echo json_encode(array('code' =>1,'msg'=>'创建群组失败！','data'=>'fail'));
        die();
      }
    }
  }

   public function addgAction() {
    if(!$_POST['uid']){
      echo json_encode(array('code' =>1,'msg'=>'请求失败！','data'=>'fail'));
        die();
    }else{
      $data=array(
        'groupname' => $_POST['name'], 
        'groupmaster' => $_SESSION['userinfo']['uid'],
        'avatar'=>'/public/images/logo.jpg',
        'create_at'=>date('Y-m-d H:i:s',time())
        );
       $insertid=Z('zys_group')->add($data);
      if($insertid){
      	$datag= array(
            'uid' =>$_SESSION['userinfo']['uid'],
            'groupid'=>$insertid
             );
          $insertid_g=Z('zys_relationship')->add($datag);
        foreach ($_POST['uid'] as &$v) {
          $datag= array(
            'uid' =>$v,
            'groupid'=>$insertid
             );
          $insertid_g=Z('zys_relationship')->add($datag);
        }
        echo json_encode(array('code' =>0,'msg'=>'创建群组成功！','data'=>'success'));
        die();
      }else{
        echo json_encode(array('code' =>1,'msg'=>'创建群组失败！','data'=>'fail'));
        die();
      }
    }
  }

  public function addgrpAction() {
    if(!$_POST['uid']){
      echo json_encode(array('code' =>1,'msg'=>'请求失败！','data'=>'fail'));
        die();
    }else{
      $data=array(
        'uid' => $_POST['uid'], 
        'groupid' => $_POST['groupid']
        );
       $insertid=Z('zys_relationship')->add($data);
      if($insertid){
        echo json_encode(array('code' =>0,'msg'=>'添加群组成员成功！','data'=>'success'));
        //添加异步任务
      $taskdata=array(
      'uid' =>$_SESSION['userinfo']['uid'],
      'groupid'=>$_POST['groupid'],
      'type'=>'rsynctask',
      'state'=>'addgroup',
      'username' =>$_SESSION['userinfo']['username'],
      'avatar' =>'http://www.weivq.com:88'.$_SESSION['userinfo']['avatar']
      );
      sendtask(json_encode($taskdata,true));
        die();
      }else{
        echo json_encode(array('code' =>1,'msg'=>'添加群组成员失败！','data'=>'fail'));
        die();
      }
    }
  }


  public function setstaAction() {
    if(!$_POST['uid']){
      echo json_encode(array('code' =>1,'msg'=>'请求失败！','data'=>'fail'));
        die();
    }else{
      $data=array(
        'originid' => $_SESSION['userinfo']['uid'], 
        'uid' => $_SESSION['userinfo']['uid'],
        'targetid'=>$_POST['uid'],
        'is_ori'=>1,
        'type'=>$_POST['type']
        );
       $insertid=Z('zys_friend')->add($data);
       $savedata=array(
       	'type' =>$_POST['type']
       	 );
       $option=array(
       	'originid' =>$_POST['uid'],
       	'type'=>1
       	 );
       Z('zys_friend')->where($option)->save($savedata);
      if($insertid){
        echo json_encode(array('code' =>0,'msg'=>'操作成功！','data'=>'success'));
        switch ($_POST['type']) {
        	case '2':
			      $taskdata=array(
			      'uid' =>$_SESSION['userinfo']['uid'],
			      'type'=>'rsynctask',
			      'state'=>'agreefriend',
			      'targetid' =>$_POST['uid'],
			      'friendtype' =>$_POST['type'],
			      'username'=>$_SESSION['userinfo']['username'],
			      'avatar'=>'http://www.weivq.com:88'.$_SESSION['userinfo']['avatar'],
			      'sign'=>$_SESSION['userinfo']['sign']
			      );
        		break;
        	case '3':
			      $taskdata=array(
			      'uid' =>$_SESSION['userinfo']['uid'],
			      'type'=>'rsynctask',
			      'state'=>'refusefriend',
			      'targetid' =>$_POST['uid'],
			      'friendtype' =>$_POST['type'],
			      'username'=>$_SESSION['userinfo']['username'],
			      'avatar'=>'http://www.weivq.com:88'.$_SESSION['userinfo']['avatar'],
			      'sign'=>$_SESSION['userinfo']['sign']
			      );
        		break;
        }
	      sendtask(json_encode($taskdata,true));
        die();
      }else{
        echo json_encode(array('code' =>1,'msg'=>'操作失败！','data'=>'fail'));
        die();
      }
    }
  }

  public function getfield($return,$table,$key,$value){
      $result=Z()->query('SELECT '.$return.' FROM  '.$table.' WHERE '.$key.'="'.$value.'"');
      return $result[0][$return];
  }

   public function setgrpAction() {
    if(!$_POST['uid']){
      echo json_encode(array('code' =>1,'msg'=>'请求失败！','data'=>'fail'));
        die();
    }else{
       $savedata=array(
        'action' =>1
         );
       $option=array(
        'groupid'=>$_POST['groupid']
         );
      $insertid=Z('zys_relationship')->where($option)->save($savedata);
      if($insertid){
        echo json_encode(array('code' =>0,'msg'=>'操作成功！','data'=>'success'));
        $taskdata=array(
            'uid' =>$_SESSION['userinfo']['uid'],
            'type'=>'rsynctask',
            'state'=>'agreegroup',
            'targetid' =>$_POST['uid'],
            'groupid' =>$_POST['groupid'],
            'groupname'=>$_POST['groupname'],
            'avatar' =>'http://www.weivq.com:88'.$this->getfield('avatar','zys_group','groupid',$_POST['groupid']),
            'username'=>$_SESSION['userinfo']['username']
            );
        sendtask(json_encode($taskdata,true));
        die();
      }else{
        echo json_encode(array('code' =>1,'msg'=>'操作失败！','data'=>'fail'));
        die();
      }
    }
  }


  public function addfAction() {
    if(!$_POST['uid']){
      echo json_encode(array('code' =>1,'msg'=>'请求失败！','data'=>'fail'));
        die();
    }else{
      $data=array(
        'originid' => $_SESSION['userinfo']['uid'], 
        'uid' => $_SESSION['userinfo']['uid'],
        'targetid'=>$_POST['uid'],
        'is_ori'=>1
        );
       $insertid=Z('zys_friend')->add($data);
      if($insertid){
        echo json_encode(array('code' =>0,'msg'=>'添加好友成功！','data'=>'success'));
        //添加异步任务
	      $taskdata=array(
	      'uid' =>$_SESSION['userinfo']['uid'],
	      'type'=>'rsynctask',
	      'state'=>'addfriend',
	      'targetid' =>$_POST['uid'],
	      'friendtype' =>1,
	      'username'=>$_SESSION['userinfo']['username'],
	      'avatar'=>'http://www.weivq.com:88'.$_SESSION['userinfo']['avatar'],
	      'sign'=>$_SESSION['userinfo']['sign']
	      );
	      sendtask(json_encode($taskdata,true));
        die();
      }else{
        echo json_encode(array('code' =>1,'msg'=>'添加好友失败！','data'=>'fail'));
        die();
      }
    }
  }
    
public function mobileAction() {
    if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
      exit;
    }
    $uinfo=array(
     	'uid' =>$_SESSION['userinfo']['uid'],
     	'username' =>$_SESSION['userinfo']['username'],
     	'avatar' =>$_SESSION['userinfo']['avatar']
     	);
     $this->getView()->assign("uinfo",json_encode($uinfo));
  }
  public function cameraimAction() {
      if(!$_SESSION['userinfo']){
      header('Location: /live/index/login');
    }
  }
  public function uploadfileAction() {
  	Yaf_Dispatcher::getInstance()->autoRender(FALSE);
    if($this->getRequest()->isPost()){
    $config = Yaf_Application::app()->getConfig()->upload->config->toArray();
    $upload = new Upload($config); 
     $info = $upload->upload();
     if (!$info) {// 上传错误提示错误信息
                echo $upload->getError();
      } else {// 上传成功
        if (!empty($info["file"]))
                $file_p = $info["file"]['savepath'] . $info["file"]['savename'];
        $fileinfo = array(
          "code"=>"0",
          "msg"=>"0",
          "data"=>[
              "src"=>'http://www.weivq.com:88'.$file_p,
              ]
      ); 
      exit(json_encode($fileinfo,true));
      } 
    }else{
       header('Location: /Live/index/index');
       exit;
    }
  }
  public function uploadimgAction() {
  	Yaf_Dispatcher::getInstance()->autoRender(FALSE);
    if($this->getRequest()->isPost()){
    $config = Yaf_Application::app()->getConfig()->upload->config->toArray();
    $upload = new Upload($config); 
     $info = $upload->upload();
     if (!$info) {// 上传错误提示错误信息
                echo $upload->getError();
      } else {// 上传成功
        if (!empty($info["file"]))
                $file_p = $info["file"]['savepath'] . $info["file"]['savename'];
        $fileinfo = array(
          "code"=>"0",
          "msg"=>"0",
          "data"=>[
              "src"=>'http://www.weivq.com:88'.$file_p,
              ]
      ); 
         exit(json_encode($fileinfo,true));
      } 
    }else{
       header('Location: /Live/index/index');
       exit;
    }
  }

   public function getkfgroupAction() {
    $mineinfo=array(
      'username' =>'咨询人_'.time(),
      'id'=>time(),
      'status'=>'online',
      'sign'=>'客服姐姐',
      'avatar'=>'http://www.weivq.com:88'.'/public/images/'.rand(0,12).'.jpg'
       );
     $group= array(
      "code"=>"0",
      "msg"=>"liveim",
      "data"=>[
        "mine"=>$mineinfo,
        "friend"=>[],
        "group"=>[]
        ]
      );
     exit(json_encode($group));
  }
  
  public function getgroupAction() {
  	 if(!$_SESSION['userinfo']){
      	echo json_encode(array('code' =>0,'msg'=>'token失败！','data'=>'fail'));
        die();
    }
    $userfriend=Z()->query('SELECT b.groupid as id,a.groupname FROM  zys_group a,zys_relationship b WHERE a.groupid=b.groupid AND b.uid="'.$_SESSION['userinfo']['uid'].'"');
    foreach ($userfriend as &$v) {
    	$v['avatar']="http://www.weivq.com:88/public/images/logo.jpg";
    }
    $ufriend=Z()->query('SELECT a.* FROM  zys_user a,zys_friend b WHERE a.uid=b.targetid AND b.is_ori=1 AND b.type=2 AND b.originid="'.$_SESSION['userinfo']['uid'].'"');
    foreach ($ufriend as &$v) {
    	$v['id']=$v['uid'];
      $v['avatar']='http://www.weivq.com:88'.$v['avatar'];
    	switch ($v['status']) {
    		case 0:
    			$v['status']='offline';
    			break;
    		case 1:
    			$v['status']='online';
    			break;
    	}
    	unset($v['uid']);
    	unset($v['passwd']);
    	unset($v['create_at']);
    }
    $friend=array(
    	'groupname' =>'我的好友',
    	'id'=>'110',
    	'list'=>$ufriend
    	 );
    $uufriend[0]=$friend;
   	$mineinfo=array(
   		'username' =>$_SESSION['userinfo']['username'],
   		'id'=>$_SESSION['userinfo']['uid'],
   		'status'=>'online',
   		'sign'=>'我就是我，qieangel2013',
   		'avatar'=>'http://www.weivq.com:88'.$_SESSION['userinfo']['avatar']
   		 );
     $group= array(
      "code"=>"0",
      "msg"=>"livego直播群",
      "data"=>[
        "mine"=>$mineinfo,
        "friend"=>$uufriend,
        "group"=>$userfriend
        ]
      );
     exit(json_encode($group));
  }
  public function getgroupexAction() {
  	if(!$_SESSION['userinfo']){
      echo json_encode(array('code' =>0,'msg'=>'token失败！','data'=>'fail'));
        die();
    }
    $userfriend=Z()->query('SELECT a.* FROM  zys_user a,zys_relationship b WHERE b.groupid="'.$_GET['id'].'" AND a.uid=b.uid');
    foreach ($userfriend as &$v) {
    	$v['id']=$v['uid'];
    	switch ($v['status']) {
    		case 0:
    			$v['status']='offline';
    			break;
    		case 1:
    			$v['status']='online';
    			break;
    	}
    	unset($v['uid']);
    	unset($v['passwd']);
    	unset($v['create_at']);
    }
     $group= array(
      "code"=>"0",
      "msg"=>"群成员",
      "data"=>[
         "list"=>$userfriend
           ]
      );
     exit(json_encode($group));
  }
  public function getuserAction() {
    //$_redis=new phpredis();
    //$mineinfo=$_redis->get("Live:mine");
    //$groupinfo=$_redis->get("Live:group");
    //$group_arr=json_decode($groupinfo);
    //$group_count=count($group_arr);
     $group= array(
      "code"=>"0",
      "msg"=>"livego直播群",
      "data"=>[
        "mine"=>json_decode($mineinfo),
        "friend"=>[
          "groupname"=>"livego直播群",
          "id"=> "101",
          "avatar"=> "/public/images/logo.jpg",
          "online"=> $group_count,
          "list"=>$group_arr,
           ],
           "group"=>[
        [
          "groupname"=>"livego直播群",
          "id"=> "101",
          "avatar"=> "/public/images/logo.jpg"
           ]
        ]
          ]
      );
     exit(json_encode($group));
  }
  private function isMobile()
  { 
      // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
      if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
      {
          return true;
      } 
      // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
      if (isset ($_SERVER['HTTP_VIA']))
      { 
          // 找不到为flase,否则为true
          return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
      } 
      // 脑残法，判断手机发送的客户端标志,兼容性有待提高
      if (isset ($_SERVER['HTTP_USER_AGENT']))
      {
          $clientkeywords = array ('nokia',
              'sony',
              'ericsson',
              'mot',
              'samsung',
              'htc',
              'sgh',
              'lg',
              'sharp',
              'sie-',
              'philips',
              'panasonic',
              'alcatel',
              'lenovo',
              'iphone',
              'ipod',
              'blackberry',
              'meizu',
              'android',
              'netfront',
              'symbian',
              'ucweb',
              'windowsce',
              'palm',
              'operamini',
              'operamobi',
              'openwave',
              'nexusone',
              'cldc',
              'midp',
              'wap',
              'mobile'
          ); 

          // 从HTTP_USER_AGENT中查找手机浏览器的关键字
          if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
          {
              return true;
          } 
    } 
      // 协议法，因为有可能不准确，放到最后判断
      if (isset ($_SERVER['HTTP_ACCEPT']))
      { 
          // 如果只支持wml并且不支持html那一定是移动设备
          // 如果支持wml和html但是wml在html之前则是移动设备
          if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
          {
              return true;
          } 
      } 

      return false;
  }
}
