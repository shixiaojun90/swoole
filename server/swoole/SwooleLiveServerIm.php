<?php
/*
|---------------------------------------------------------------
|  Copyright (c) 2017
|---------------------------------------------------------------
| 作者：qieangel2013
| 联系：qieangel2013@gmail.com
| 版本：V1.0
| 日期：2017/6/25
|---------------------------------------------------------------
*/
class SwooleLiveServer
{
	public static $instance;
	private $table;
	private $server;
	private $userinfo;
	private $redis_con;
	private $tmparr;
	public function __construct() {
		$this->table = new swoole_table(1024);
		$this->table->column('id', swoole_table::TYPE_INT, 8);      //1,2,4,8
		$this->table->column('uid', swoole_table::TYPE_INT, 8); 
		$this->table->column('status', swoole_table::TYPE_STRING,16);
		$this->table->column('groupid', swoole_table::TYPE_INT, 8);      //1,2,4,8
		$this->table->column('kfid', swoole_table::TYPE_STRING,16); 
		$this->table->column('data', swoole_table::TYPE_STRING,800);
		$this->table->create();
		define('APPLICATION_PATH', dirname(dirname(__DIR__)) . "/application");
        define('MYPATH', dirname(APPLICATION_PATH));
        $this->application = new Yaf_Application(dirname(APPLICATION_PATH) . "/conf/application.ini");
        $this->application->bootstrap();
        $this->tmparr=array('您好，客服姐姐现在不在线,工作时间(09:00:00-18:00:00)周六日除外,请联系904208360@qq.com！','您好，客服姐姐有事不在,工作时间(09:00:00-18:00:00)周六日除外,请联系904208360@qq.com！','您好，客服姐姐工作时间(09:00:00-18:00:00)周六日除外，请联系904208360@qq.com！');
		$this->server = new swoole_websocket_server('0.0.0.0','8080');
		// if(isset($live_config['logfile'])){
		// 	$server->set(
		// 	array(
		// 		'daemonize' => true,
		// 		'log_file' => $live_config['logfile']
		// 	)
		// 	);
		// }else{
			$this->server->set(
			array(
				'daemonize' => true
			)
			);
		// }
		
		$this->server->on('Open',array($this , 'onOpen'));
		$this->server->on('Message',array($this , 'onMessage'));
		$this->server->on('Close',array($this , 'onClose'));
		$this->server->start();
	}
	public function onOpen($server, $req) {
		// $tmpuser=array(
		// 	'id' =>$req->fd
		// 	);
		// $this->table->set($req->fd,$tmpuser);
	}
	public function onMessage($server, $frame) {
		$framedata=json_decode($frame->data,true);
		if($framedata['type']=='close'){
			$this->server->Close($frame->fd);
		}else{
			if($framedata['type']=='login'){
				$this->adduser($frame->fd,$framedata['uid']);
			}
			if($framedata['type']=='group'){
				$this->getgroup($framedata['Id']);
				$group=$this->table->get($framedata['Id']);
				$gdata=json_decode($group['data'],true);
				if($group['groupid']){
					$this->addmsg('group',$framedata['Id'],$frame->data,1);
					foreach ($gdata as $v) {
						if($v['isonline']==1 && $v['sid']!=$framedata['uid']){
							$this->server->push($this->getonline($v['sid'],'uid','id'),json_encode($framedata,true));
						}
					}
				}
			}
			if($framedata['type']=='friend'){
				if($framedata['Mtype']=='kf'){
					if(!$this->getkffd($framedata['uid'])){
						$tmpuser=array(
						'id' =>$frame->fd,
						'kfid'=>$framedata['uid']
						);
						$this->table->set($frame->fd,$tmpuser);
					}
				}
				$onlineid=$this->getonline($framedata['Id'],'uid','id');
				$kfid=$this->getkffd($framedata['Id']);
				if($framedata['Data']=='kflogin'){
					$kfresponse=array(
								'Username' =>'904208360@qq.com',
								'Data'=>'请问有什么能帮助您的吗？',
								'Mtype'=>'mess',
								'Img'=>'http://www.weivq.com:88/public/images/12.jpg',
								'Id'=>$framedata['uid'],
								'type'=>'friend',
								'uid'=>1,
								'Timestamp'=>$this->getMillisecond()
								 );
							$kfobj=$this;
							swoole_timer_after(1000,function() use($kfobj,$frame,$kfresponse){
								$kfobj->server->push($frame->fd,json_encode($kfresponse,true));
							});
				}else{
					if($onlineid){
						$this->addmsg('friend',$framedata['Id'],$frame->data,1);
						$this->server->push($onlineid,json_encode($framedata,true));
					}else if($kfid){
						$this->addmsg('friend',$framedata['Id'],$frame->data,1);
						$this->server->push($kfid,json_encode($framedata,true));
					}else{
						$this->addmsg('friend',$framedata['Id'],$frame->data);
						if($framedata['Mtype']=='kf'){
							$kfresponse=array(
								'Username' =>'904208360@qq.com',
								'Data'=>$this->getrobot($framedata['Data']),
								'Mtype'=>'mess',
								'Img'=>'http://www.weivq.com:88/public/images/12.jpg',
								'Id'=>$framedata['uid'],
								'type'=>'friend',
								'uid'=>1,
								'Timestamp'=>$this->getMillisecond()
								 );
							$kfobj=$this;
							swoole_timer_after(1000,function() use($kfobj,$frame,$kfresponse){
								$kfobj->server->push($frame->fd,json_encode($kfresponse,true));
							});
						}
					}

				}

				
			}

			//处理异步任务
			if($framedata['type']=='rsynctask'){
				$obj=$this;
				swoole_timer_after(5000,function() use($obj,$frame){
					$obj->task($frame->data);
				});
			}
			
		}
	}
	public function onClose($server, $fd) {
		foreach($this->table as $k=>$row)
		{
			if($row['id']==$fd){
				$uid=$row['uid'];
			}
		}
		$this->table->del($fd);
		if(isset($uid)){
			$this->setfield('zys_user','status','0','uid="'.$uid.'"');
		}
		unset($uid);
	}

	private function getrobot($data){
			$ch = curl_init("http://api.qingyunke.com/api.php?key=free&appid=0&msg=".$data);  
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1) ;
			curl_setopt($ch, CURLOPT_TIMEOUT,5); 
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;  
			$output = curl_exec($ch);  
			curl_close($ch);
			 $result=json_decode($output,true);
			 if($result['result']=='0'){
			 	if($result['content']){
			 		return $result['content'];
			 	}else{
			 		return $this->tmparr[rand(0,2)];
			 	}
			 }else{
			 	return $this->tmparr[rand(0,2)];
			 }
	}

	private function getMillisecond(){
	    list($t1, $t2) = explode(' ', microtime());
	    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
	}
	private function updateuserinfo($str,$type,$fd){
		foreach($this->table as $k=>$row)
		{
			if($row['id']==$fd){
				$row[$type]=$str;
				$this->table->set($k,$row);
				break;
			}
		}
	}

	private function setonline($uid){
		foreach($this->table as $k=>$row)
		{
			if($row['uid']==$uid){
				$lmparr=array('status' =>'online');
				$this->table->set($row['status'],$lmparr);
				$groupinfo=$this->table->get($row['groupid']);
				$gdata=json_decode($groupinfo['data'],true);
				if($groupinfo){
					foreach ($gdata as &$v) {
						if($v['sid']==$uid){
							$v['isonline']=1;
						}
					}
					$this->table->set($row['groupid'],$gdata);
				}
			}
		}
		return false;
	}

	private function setoffline($uid){
		foreach($this->table as $k=>$row)
		{
			if($row['uid']==$uid){
				$lmparr=array('status' =>'online');
				$this->table->set($row['status'],$lmparr);
				$groupinfo=$this->table->get($row['groupid']);
				$gdata=json_decode($groupinfo['data'],true);
				if($groupinfo){
					foreach ($gdata as &$v) {
						if($v['sid']==$uid){
							$v['isonline']=0;
						}
					}
					$this->table->set($row['groupid'],$gdata);
				}
			}
		}
		return false;
	}

	private function task($data){
		$result=json_decode($data,true);
		$tmparr=array(
			'type' =>'system'
			 );
		switch ($result['state']) {
			case 'online':
				$this->setonline($result['uid']);
				$data=json_decode($this->getfriend($result['uid']),true);
				foreach ($data as &$v) {
					if($v['status']){
						$fd=$this->getfd($v['uid']);
						if($fd){
							$tmparr['uid']=$result['uid'];
							$tmparr['status']='online';
							$this->server->push($fd,json_encode($tmparr,true));
						}
					}
				}
				$msgdata=$this->getmsg($result['uid']);
				$msgdata=json_decode($msgdata,true);
				if($msgdata){
					$fd=$this->getfd($result['uid']);
					foreach ($msgdata as &$v) {
						switch ($v['type']) {
							case 0:
								$this->server->push($fd,$v['mesage']);
								$this->setread($v['id'],1);
								break;
							case 1:
								$this->server->push($fd,$v['mesage']);
								$this->setread($v['id'],1);
								break;
							case 2:
							$tmparr['uid']=$v['uid'];
							switch ($v['action']) {
								case 1:
									$tmparr['status']='addfriend';
									$tmparr['username']=$v['username'];
									$tmparr['avatar']=$v['avatar'];
									break;
								case 2:
									$tmparr['status']='agreefriend';
									$tmparr['username']=$v['username'];
									$tmparr['avatar']=$v['avatar'];
									break;
								case 3:
									$tmparr['status']='refusefriend';
									$tmparr['username']=$v['username'];
									$tmparr['avatar']=$v['avatar'];
									break;
								case 4:
									$data=json_decode($this->getfield('groupname,avatar','zys_group','groupid',$v['groupid']),true);
									$username=json_decode($this->getfield('username','zys_user','uid',$v['toid']),true);
									$tmparr['uid']=$v['toid'];
									$tmparr['status']='addgroup';
									$tmparr['username']=$username['username'];
									$tmparr['groupname']=$data['groupname'];
									$tmparr['groupid']=$v['groupid'];
									$tmparr['avatar']=$data['avatar'];
									break;
								case 5:
									$data=json_decode($this->getfield('groupname,avatar','zys_group','groupid',$v['groupid']),true);
									$username=json_decode($this->getfield('username','zys_user','uid',$v['toid']),true);
									$tmparr['uid']=$v['toid'];
									$tmparr['status']='agreegroup';
									$tmparr['username']=$username['username'];
									$tmparr['groupname']=$data['groupname'];
									$tmparr['groupid']=$v['groupid'];
									$tmparr['avatar']=$data['avatar'];
									break;
							}
							$this->server->push($fd,json_encode($tmparr,true));
							$this->setread($v['id'],1);
							break;
						}
					}
				}
				break;
			case 'offline':
				$this->setoffline($result['uid']);
				$data=json_decode($this->getfriend($result['uid']),true);
				foreach ($data as &$v) {
					if($v['status']){
						$fd=$this->getfd($v['uid']);
						if($fd){
							$tmparr['uid']=$result['uid'];
							$tmparr['status']='offline';
							$this->server->push($fd,json_encode($tmparr,true));
						}
					}
				}
				break;
			case 'addfriend':
						$fd=$this->getfd($result['targetid']);
						if($fd){
							$tmparr['uid']=$result['uid'];
							$tmparr['status']='addfriend';
							$tmparr['username']=$result['username'];
							$tmparr['avatar']=$result['avatar'];
							$this->server->push($fd,json_encode($tmparr,true));
							$this->application->execute(array('swoole_group','addfmsg'),$data,1);
						}else{
							$this->application->execute(array('swoole_group','addfmsg'),$data);
						}
				break;
			case 'agreefriend':
						$fd=$this->getfd($result['targetid']);
						if($fd){
							$tmparr['uid']=$result['uid'];
							$tmparr['status']='agreefriend';
							$tmparr['username']=$result['username'];
							$tmparr['avatar']=$result['avatar'];
							$this->server->push($fd,json_encode($tmparr,true));
							$this->application->execute(array('swoole_group','addfmsg'),$data,1);
						}else{
							$this->application->execute(array('swoole_group','addfmsg'),$data);
						}
				break;
			case 'refusefriend':
						$fd=$this->getfd($result['targetid']);
						if($fd){
							$tmparr['uid']=$result['uid'];
							$tmparr['status']='refusefriend';
							$tmparr['username']=$result['username'];
							$tmparr['avatar']=$result['avatar'];
							$this->server->push($fd,json_encode($tmparr,true));
							$this->application->execute(array('swoole_group','addfmsg'),$data,1);
						}else{
							$this->application->execute(array('swoole_group','addfmsg'),$data);
						}
				break;
			case 'addgroup':
						$gdata=json_decode($this->getgroupmaster($result['groupid']),true);
						if($gdata){
							$fd=$this->getfd($gdata['groupmaster']);
							$result['groupname']=$gdata['groupname'];
							$result['groupid']=$gdata['groupid'];
							$result['groupmaster']=$gdata['groupmaster'];
							$data=json_encode($result,true);
							if($fd){
								$tmparr['groupid']=$gdata['groupid'];
								$tmparr['status']='addgroup';
								$tmparr['groupname']=$gdata['groupname'];
								$tmparr['avatar']=$gdata['avatar'];
								$tmparr['uid']=$result['uid'];
								$this->server->push($fd,json_encode($tmparr,true));
								$this->application->execute(array('swoole_group','addfmsg'),$data,1);
							}else{
								$this->application->execute(array('swoole_group','addfmsg'),$data);
							}
						}
						
				break;
			case 'agreegroup':
						$fd=$this->getfd($result['targetid']);
						if($fd){
							$tmparr['uid']=$result['uid'];
							$tmparr['status']='agreegroup';
							$tmparr['groupid']=$result['groupid'];
							$tmparr['groupname']=$result['groupname'];
							$tmparr['avatar']=$result['avatar'];
							$tmparr['username']=$result['username'];
							$this->server->push($fd,json_encode($tmparr,true));
							$this->application->execute(array('swoole_group','addfmsg'),$data,1);
						}else{
							$this->application->execute(array('swoole_group','addfmsg'),$data);
						}
				break;
			default:
				break;
		}
		
	}

	private function getfd($uid){
		foreach($this->table as $k=>$row)
		{
			if($row['uid']==$uid){
				return $row['id'];
			}
		}
		return false;
	}

	private function getkffd($uid){
		foreach($this->table as $k=>$row)
		{
			if($row['kfid']==$uid){
				return $row['id'];
			}
		}
		return false;
	}

	private function setread($id,$read=0){
		$this->application->execute(array('swoole_group','setread'),$id,$read);
	}

	private function setfield($table,$key,$value,$where){
		$this->application->execute(array('swoole_group','setlivefield'),$table,$key,$value,$where);
	}

	private function getfriend($uid){
		ob_start();
		$this->application->execute(array('swoole_group','getfriend'),$uid);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	private function adduser($fd,$uid){
		$tmpuser=array(
			'id' =>$fd,
			'uid'=>$uid
			);
		$this->table->set($fd,$tmpuser);
	}
	private function addmsg($type,$id,$data,$read=0){
		$this->application->execute(array('swoole_group','addmsg'),$type,$id,$data,$read);
	}

	private function getmsg($uid){
		ob_start();
		$this->application->execute(array('swoole_group','getmsg'),$uid);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	private function getgroupmaster($groupid){
		ob_start();
		$this->application->execute(array('swoole_group','getgroupmaster'),$groupid);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	private function getfield($return,$table,$key,$value){
		ob_start();
		$this->application->execute(array('swoole_group','getlivefield'),$return,$table,$key,$value);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	private function getonline($id,$type='uid',$res='id'){
		foreach($this->table as $k=>$row)
		{
			if($row[$type]==$id){
				return $row[$res];
			}
		}
		return false;
	}
	private function getgroup($groupid){
		$group_arr=$this->table->get($groupid);
		//if(!$group_arr['groupid']){
			ob_start();
			$this->application->execute(array('swoole_group','getgroup'),$groupid);
			$result = ob_get_contents();
			ob_end_clean();
			$result=json_decode($result,true);
			foreach ($result as &$v) {
				if($this->getonline($v['id'],'uid','id')){
					$v['isonline']=1;
				}
				$v['sid']=$v['id'];
				unset($v['id']);
			}
			$tableinfo=array(
				'groupid' =>$groupid,
				'data'=>json_encode($result,true)
				 );
			$this->table->set($groupid,$tableinfo);
		//}
	}
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new SwooleLiveServer;
		}
		return self::$instance;
	}
}
SwooleLiveServer::getInstance();
