### Liveim 部署
    liveim部署很简单，用http访问接口，使用websocket连接服务器
    下面我们来拿nginx+zys举例
        nginx配置：
        server {
        listen       88;
        server_name  localhost;
        location / {
            root   html/www;
            index  index.php index.html index.htm;
            if (!-e $request_filename) {
               rewrite ^/(.*)  /index.php?$1 last;
               }
            }
        }
        php配置：
        php需要安装swoole扩展和yaf扩展，暂时不用redis扩展
        然后执行网站根目录下
            /usr/local/php/bin/php ./server/serverlive.php start|stop|restart
        查看8080端口是否起来 lsof -i:8080;
        由于websocket使用的是8080端口，如需修改，直接在根目录下./server/swoole/SwooleLiveServerIm.php修改
        修改实例：
           $this->server = new swoole_websocket_server('0.0.0.0','8081');
        修改后模板文件都要改相应的端口
         在根目录下/application/modules/Live/views/index下的所有文件端口替换以及
         根目录下 /public/js/live.js的端口相应更改
         如果想要修改访问url，直接修改根目录下/application/modules/Live/views/index下的所有文件的api接口以及根目录下 /public/js/live.js的api接口
         本地访问url：http://localhost/live/index
### liveim 的架构
![image](http://www.weivq.com:88/public/images/jgt.png)
### liveim 开发
        web服务与websocket服务交互是通过websocket客户端建立的
        而websocket服务与web服务交互是通过yaf的cli模式调用自己写的类
        简单的举个例子：
        //添加异步任务
      	$taskdata=array(
      	'uid' =>$_SESSION['userinfo']['uid'],
      	'type'=>'rsynctask',
      	'state'=>'offline',
      	'username' =>$_SESSION['userinfo']['username'],
      	'avatar' =>$_SESSION['userinfo']['avatar']
      	);
      	sendtask(json_encode($taskdata,true));
      	这段代码就是web服务与websocket服务交互
      	sendtask函数的定义在根目录下/application/function/Functions.php里是这样定义的
      	function sendtask($data) {
    		Msg::getInstance()->sendtask($data);
		}
		而Msg的调用在根目录下/application/library/Msg.php里
		class Msg extends Model{
			public static $instance;
			public $client;
			public function __construct($str='zys_message') {
				parent::__construct($str);
				$host = '127.0.0.1';
    			$prot = 8080;
    			$this->client = new WebSocketClient($host, $prot);
    			$this->client->connect();
			}

			public function sendtask($data){
				$this->client->send($data);
				//self::finish();
			}

			public function finish(){
				$this->client->disconnect();
			}
	 		public static function getInstance() {
        		if (!(self::$instance instanceof Msg)) {
            		self::$instance = new Msg;
        		}
        		return self::$instance;
    		}
		}
		很简单，调用了WebSocket客户端连接websocket服务器
		然后把数据通过WebSocket客户端发给websocket服务器
		在来看websocket服务器接受数据怎么处理
		//处理异步任务
			if($framedata['type']=='rsynctask'){
				$obj=$this;
				swoole_timer_after(5000,function() use($obj,$frame){
					$obj->task($frame->data);
				});
			}
		然后websocket服务器做相应处理，同理websocket服务器怎么跟web服务交互的
		下面是例子
		$this->application->execute(array('swoole_group','addfmsg'),$data,1);
		看一下这一个例子，通过调用根目录下/application/library/swoole/group.php文件的addmsg方法
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
		看一下这一个调用方法就知道了怎么把数据mysql里！
		上面说了整个流程，对他进行二次开发或者集成到自己的项目中去，只要了解上面这个流程，就没问题
		具体的前端展示页面的处理需要查看layim以及layui的开发者文档
[文档链接](http://www.layui.com/doc/modules/layim.html)
### 跨域处理
		location  /boss/ {
        rewrite  ^.+boss/?(.*)$ /$1 break;
        include  uwsgi_params;
        proxy_pass   http://localhost:80;
       }


