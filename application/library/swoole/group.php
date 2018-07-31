<?php
class swoole_group extends Model{
	public static $instance;
	public function __construct($str='zys_message') {
		parent::__construct($str);
	}
	public static function getgroup($groupid){
		$userfriend=self::getInstance()->query('SELECT a.* FROM  zys_user a,zys_relationship b WHERE b.groupid="'.$groupid.'" AND a.uid=b.uid');
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
		    	$v['isonline']=0;
		    	unset($v['uid']);
		    	unset($v['passwd']);
		    	unset($v['create_at']);
		    }
		     echo json_encode($userfriend);
	}
	public static function addmsg($type,$id,$data,$read=0){
		 $msgdata= array(
                    'mesage' =>$data,
                    'lread' =>$read,
                    'create_at'=>date("Y-m-d H:i:s",time())
         );
		switch ($type) {
			case 'group':
				$msgdata['type']=1;
				$msgdata['groupid']=$id;
				break;
			case 'friend':
				$msgdata['type']=0;
				$tmpdata=json_decode($data,true);
				$msgdata['fromid']=$tmpdata['uid'];
				$msgdata['toid']=$id;
				unset($tmpdata);
				break;
			default:
				break;
		}
		$msgres=self::getInstance('zys_message')->add($msgdata);
		return $msgres;
	}

	public static function getlivefield($return,$table,$key,$value){
      $result=self::getInstance()->query('SELECT '.$return.' FROM  '.$table.' WHERE '.$key.'="'.$value.'"');
      echo json_encode($result[0],true);
  }

  public static function setlivefield($table,$key,$value,$where){
      $result=self::getInstance()->execute('UPDATE '.$table.' SET  '.$key.'="'.$value.'" WHERE '.$where);
      echo $result;
  }

	public static function getfriend($uid){
		$userfriend=self::getInstance()->query('SELECT a.* FROM  zys_user a,zys_friend b WHERE a.uid=b.targetid AND b.is_ori=1 AND b.type=2 AND b.originid="'.$uid.'"');
		foreach ($userfriend as &$v) {
		    	unset($v['passwd']);
		    	unset($v['create_at']);
		    }
		 echo json_encode($userfriend);
	}

	public static function getgroupmaster($groupid){
		$groupinfo=self::getInstance()->query('SELECT * FROM  zys_group WHERE groupid="'.$groupid.'"');
		echo json_encode($groupinfo[0],true);
	}

	public static function getname($uid){
		$username=self::getInstance()->query('SELECT username FROM  zys_user WHERE uid="'.$uid.'"');
		 return $username[0]['username'];
	}

	public static function setread($id,$read){
		$upid=self::getInstance('zys_message')->where(array('id' =>$id))->save(array('lread' =>$read));
		 return $upid;
	}

	public static function getmsg($uid){
		$msgdata=self::getInstance()->query('SELECT a.*,b.* FROM zys_user a, zys_message b WHERE a.uid=b.fromid AND b.lread=0 AND b.toid="'.$uid.'"');
		 echo json_encode($msgdata);
	}

	public static function addfmsg($data,$read=0){
		$data=json_decode($data,true);
		switch ($data['state']) {
			case 'addfriend':
				$msgdata= array(
                    'mesage' =>self::getname($data['uid']).'申请添加你为好友',
                    'type' =>2,
                    'fromid'=>$data['uid'],
                    'action'=>1,
                    'toid'=>$data['targetid'],
                    'lread'=>$read,
                    'create_at'=>date("Y-m-d H:i:s",time())
         		);
         		$msgres=self::getInstance('zys_message')->add($msgdata);
				break;
			case 'agreefriend':
				$msgdata= array(
                    'mesage' =>self::getname($data['uid']).'同意你的好友申请',
                    'action'=>2
         		);
         		$msgres=self::getInstance('zys_message')->where(array('fromid' =>$data['targetid'],'type'=>2))->save($msgdata);
         		$msgdata= array(
                    'mesage' =>'您已同意'.self::getname($data['targetid']).'的好友申请',
                    'type' =>2,
                    'fromid'=>$data['uid'],
                    'action'=>2,
                    'toid'=>$data['targetid'],
                    'lread'=>$read,
                    'create_at'=>date("Y-m-d H:i:s",time())
         		);
         		$msgres=self::getInstance('zys_message')->add($msgdata);
				break;
			case 'refusefriend':
				$msgdata= array(
                    'mesage' =>self::getname($data['uid']).'拒绝了你的好友申请',
                    'action'=>3
         		);
         		$msgres=self::getInstance('zys_message')->where(array('fromid' =>$data['targetid'],'type'=>2))->save($msgdata);
         		$msgdata= array(
                    'mesage' =>'您已拒绝'.self::getname($data['targetid']).'的好友申请',
                    'type' =>2,
                    'fromid'=>$data['uid'],
                    'action'=>3,
                    'toid'=>$data['targetid'],
                    'lread'=>$read,
                    'create_at'=>date("Y-m-d H:i:s",time())
         		);
         		$msgres=self::getInstance('zys_message')->add($msgdata);
				break;
			case 'addgroup':
         		$msgdata= array(
                    'mesage' =>self::getname($data['uid']).'申请加入'.$data['groupname'].'群组',
                    'type' =>2,
                    'fromid'=>$data['uid'],
                    'groupid'=>$data['groupid'],
                    'action'=>4,
                    'toid'=>$data['groupmaster'],
                    'lread'=>$read,
                    'create_at'=>date("Y-m-d H:i:s",time())
         		);
         		$msgres=self::getInstance('zys_message')->add($msgdata);
				break;
				case 'agreegroup':
         		$msgdata= array(
                    'mesage' =>'您已同意'.self::getname($data['targetid']).'加入'.$data['groupname'].'群组',
                    'type' =>2,
                    'fromid'=>$data['uid'],
                    'groupid'=>$data['groupid'],
                    'action'=>5,
                    'toid'=>$data['targetid'],
                    'lread'=>$read,
                    'create_at'=>date("Y-m-d H:i:s",time())
         		);
         		$msgres=self::getInstance('zys_message')->add($msgdata);
				break;
		}
		return $msgres;
	}

	 public static function getInstance() {
        if (!(self::$instance instanceof swoole_group)) {
            self::$instance = new swoole_group;
        }
        return self::$instance;
    }




}
