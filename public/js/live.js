    var pako = window.pako;
  //   // var image = document.getElementById('avatar-img');
  //   // var audio = document.querySelector('audio');
  //   var autoname = [
  //   'qieangel2013', 
  //   '曹超',
  //   '林心如',
  //   '邓超',
  //   '赵丽颖',
  //   '奶茶妹',
  //   'QQ',
  //   '乾坤大罗伊',
  //   '张无忌'
  // ];
  // var LiveName=window.localStorage.getItem("LiveName");
  //   if(LiveName==undefined){
  //     console.log('livename');
  //     window.location.href='/Live/index/index';
  //   }
    //     window.initPlayer = function(obj){        
    //     obj.playlist = []; //播放列表        
    //     obj.position = 0; //当前播放位置
    //     //播放音乐(循环播放)
    //     obj.start = function(){
    //         if(jQuery.isArray(obj.playlist) && obj.playlist.length>=1){
    //             $(obj).attr("src", obj.playlist[obj.position % obj.playlist.length]);    
    //             obj.play();
    //         } 
    //     };
    //     //播放一个列表
    //     obj.playList = function(arr){
    //         if(jQuery.isArray(arr) && arr.length>=1){
    //             obj.playlist = arr;
    //             obj.position = 0;
    //             obj.start();  
    //         }
    //     };
    //     //播放下一首
    //     $(obj).on("ended",function(e){
    //          obj.playlist.shift();
    //         //obj.position++;
    //         obj.start();           
    //     });
    // };
    // initPlayer(audio);
function gzip(string) {  
      var charData    = string.split('').map(function(x){return x.charCodeAt(0);});  
      var binData     = new Uint8Array(charData);  
      var data= pako.gzip(binData);  
      var strData= String.fromCharCode.apply(null, new Uint16Array(data));  
      return btoa(strData);  
}  
function ungzip(string)  
{  
      var strData     = atob(string);  
      var charData    = strData.split('').map(function(x){return x.charCodeAt(0);});  
      var binData     = new Uint8Array(charData);  
      var data= pako.ungzip(binData);  
      var strData= String.fromCharCode.apply(null, new Uint16Array(data));  
      return strData;  
}  
layui.use(['layer', 'form','upload','layim'], function(){
  var layer = layui.layer
  ,form = layui.form()
  ,upload=layui.upload
  ,layim = layui.layim; 
  var userinfo=JSON.parse(sinfo);
  //演示自动回复
  /*var autoReplay = [
    '您好，我现在有事不在，一会再和您联系。', 
    '你没发错吧？face[微笑] ',
    '洗澡中，请勿打扰，偷窥请购票，个体四十，团体八折，订票电话：一般人我不告诉他！face[哈哈] ',
    '你好，我是主人的美女秘书，有什么事就跟我说吧，等他回来我会转告他的。face[心] face[心] face[心] ',
    'face[威武] face[威武] face[威武] face[威武] ',
    '<（@￣︶￣@）>',
    '你要和我说话？你真的要和我说话？你确定自己想说吗？你一定非说不可吗？那你说吧，这是自动回复。',
    'face[黑线]  你慢慢说，别急……',
    '(*^__^*) face[嘻嘻] ，是贤心吗？'
  ];*/
//   var ltitle_index=layer.open({
//   type:1,
//   offset:'l',
//   content:$('#avatar-img'),
//   zIndex: layer.zIndex,
//   success: function(layero){
//     layer.setTop(layero);
//   }
// });    
// layer.title('livego直播群',ltitle_index);

  layim.config({
    //brief: true //是否简约模式（如果true则不显示主面板）
    init: {
      url: 'getgroup'
      ,data: {}
    }
    ,members: {
      url: 'getgroupex' //接口地址（返回的数据格式见下文）
      ,data: {} //额外参数
    }
    ,isAudio: true //开启聊天工具栏音频
    ,isVideo: true //开启聊天工具栏视频
    ,tool: [{
      alias: 'code'
      ,title: '代码'
      ,icon: '&#xe64e;'
    }]
    ,uploadImage: {
      url: 'uploadimg' //（返回的数据格式见下文）
      ,type: 'post' //默认post
    }
    ,uploadFile: {
      url: 'uploadfile' //（返回的数据格式见下文）
      ,type: 'post' //默认post
    }
    ,isfriend: true //是否开启好友（默认true，即开启）
    ,copyright: true
    ,notice:true
    ,msgbox: '/live/index/msgbox'//消息盒子页面地址，若不开启，剔除该项即可
    ,find: '/live/index/addfriend' //发现页面地址，若不开启，剔除该项即可
    ,chatLog: '/live/index/chatlog' //聊天记录页面地址，若不开启，剔除该项即可
  });
 
// //创建一个会话
//   layim.chat({
//     id: 101
//     ,name: 'livego直播群'
//     ,type: 'group' //friend、group等字符，如果是group，则创建的是群聊
//     ,avatar: '/public/images/logo.jpg'
//   }); 

layim.on('members', function(data){
  console.log(data);
});
//监听在线状态的切换事件
  layim.on('online', function(status){
    layer.msg(status);
  });
  
  //监听签名修改
  layim.on('sign', function(value){
    layer.msg(value);
  });
  //监听自定义工具栏点击，以添加代码为例
  layim.on('tool(code)', function(insert){
    layer.prompt({
      title: '插入代码'
      ,formType: 2
      ,shade: 0
    }, function(text, index){
      layer.close(index);
      insert('[pre class=layui-code]' + text + '[/pre]'); //将内容插入到编辑器
    });
  });
  
  //监听layim建立就绪
  layim.on('ready', function(res){
    //console.log(res.mine);
    $.get('/live/index/getmsgcount', {
    }, function(res){
      res=JSON.parse(res);
      if(res.data != 0){
        layim.msgbox(res.data); //模拟消息盒子有新消息，实际使用时，一般是动态获得
      }
    });
  });
  var receiver_socket = new WebSocket("ws://"+document.domain+":8080/");
  //var receiver_socket = new WebSocket("wss://"+document.domain+"/chat");
  receiver_socket.onopen = function (event) {
     receiver_socket.send(JSON.stringify({Username:userinfo['username'],Data:"亲！我连上啦！",Mtype:"mess",Img:userinfo['avatar'],type:'login',uid:userinfo['uid'],Timestamp:Date.parse(new Date())}));
    // receiver_socket.send(JSON.stringify({Username:LiveName,Data:"亲！我连上啦！",Mtype:"mess",Img:'',Fromid:'',Timestamp:Date.parse(new Date())}));  
  };
   receiver_socket.onclose = function(event) { 
    layer.msg('聊天连接已断开，请重新登录！',{icon: 1,shade: 0.5,time:1500},function(){
                        location.href = "/live/index/login";
                    });
  }; 
  receiver_socket.onmessage = function(data)
        {
        zqfdata= JSON.parse(data.data);
        if(zqfdata.type=='group'){
            if(zqfdata.Mtype=='video'){
                image.src=ungzip(zqfdata.Data);
            }else if(zqfdata.Mtype=='mess'){
                if(zqfdata.Data=='亲！我连上啦！'){
                    layim.getMessage({
                    system: true
                    ,id: zqfdata.Id
                    ,type: "group"
                    ,content: zqfdata.Username + '加入群聊'
                    });
                }else{
                layim.getMessage({
                username: zqfdata.Username //消息来源用户名
                ,avatar: zqfdata.Img //消息来源用户头像
                ,id: zqfdata.Id //聊天窗口来源ID（如果是私聊，则是用户id，如果是群聊，则是群组id）
                ,type: "group" //聊天窗口来源类型，从发送消息传递的to里面获取
                ,content: zqfdata.Data //消息内容
                //,cid: 0 //消息id，可不传。除非你要对消息进行一些操作（如撤回）
                ,mine:false //是否我发送的消息，如果为true，则会显示在右方
                //,fromid: zqfdata.Fromid //消息来源者的id，可用于自动解决浏览器多窗口时的一些问题
                ,timestamp: zqfdata.Timestamp  //服务端动态时间戳
                });
                }
                //$('#chatLog').append('<br/>'+zqfdata.data);
                //$('#zystmp').text(zqfdata.Data);
                //$('#livegotmp').attr('src',zqfdata.Img);
                //$('#zystmp').trigger('click');
            }else if(zqfdata.Mtype=='mic'){
               // var blob=dataURLtoBlob(zqfdata.data);
                //console.log(zqfdata.data);
                //console.log(window.URL.createObjectURL(blob));
                //$('#audio').attr('src',zqfdata.data);
                //$('#audio').append('<source src="'+zqfdata.Data+'"/>')
                audio.playlist.push(zqfdata.Data);
                //audio.src =zqfdata.Data;
                if(audio.paused){ 
                    audio.start();
                } 
                /*g_audio.elems["id"] = '';
                g_audio.push({
                song_id: '',
                song_fileUrl: window.URL.createObjectURL(zqfdata.data)
                //audio.src = window.URL.createObjectURL(zqfdata.data);
            });  */
        }
        }else if(zqfdata.type=='friend'){
        	layim.getMessage({
  				username: zqfdata.Username //消息来源用户名
  				,avatar: zqfdata.Img //消息来源用户头像
  				,id: zqfdata.uid //消息的来源ID（如果是私聊，则是用户id，如果是群聊，则是群组id）
  				,type: "friend" //聊天窗口来源类型，从发送消息传递的to里面获取
  				,content: zqfdata.Data //消息内容
  				,mine: false //是否我发送的消息，如果为true，则会显示在右方
  				//,fromid: zqfdata.Id //消息的发送者id（比如群组中的某个消息发送者），可用于自动解决浏览器多窗口时的一些问题
  				,timestamp: zqfdata.Timestamp //服务端动态时间戳
			});
        }else if(zqfdata.type=='system'){
          switch (zqfdata.status)
              {
              case 'online':
                layim.setFriendStatus(zqfdata.uid,zqfdata.status);
                break;
              case 'offline':
                layim.setFriendStatus(zqfdata.uid,zqfdata.status);
                break;
              case 'addfriend':
                layim.setFriendGroup({
                        type: 'friend'
                        ,username: zqfdata.username
                        ,avatar: zqfdata.avatar
                        ,group:layim.cache().friend
                        ,submit: function(group,index){ 
                            $.post('/live/index/setsta', {
                                  uid: zqfdata.uid //对方用户ID
                                  ,type:2
                                }, function(res){
                                  res=JSON.parse(res);
                                  if(res.code != 0){
                                    return layer.msg(res.msg);
                                  }
                                  var data={
                                    type: 'friend' 
                                    ,avatar: zqfdata.avatar 
                                    ,username: zqfdata.username 
                                    ,groupid: group 
                                    ,id: zqfdata.uid 
                                    ,sign: zqfdata.sign
                                  }
                                  layim.addList(data);
                                  layer.close(index);
                                });
                        }
                });
                break;
              case 'agreefriend':
                layer.msg(zqfdata.username+'同意你的好友申请', {
                                        icon: 1
                                        ,shade: 0.5
                                        ,time: 5000
                                    }, function(){
                                        //layer.close(index);
                                    });
                var data={
                                    type: 'friend' 
                                    ,avatar: zqfdata.avatar 
                                    ,username: zqfdata.username 
                                    ,groupid:'110' 
                                    ,id: zqfdata.uid 
                                    ,sign: zqfdata.sign
                                  }
                layim.addList(data);
                break;
              case 'refusefriend':
                layer.msg(zqfdata.username+'拒绝了你的好友申请', {
                                        icon: 1
                                        ,shade: 0.5
                                        ,time: 5000
                                    }, function(){
                                        //layer.close(index);
                                    });
                break;
              case 'addgroup':
                layim.setFriendGroup({
                        type: 'group'
                        ,groupname:zqfdata.groupname
                        ,avatar: zqfdata.avatar
                        ,group:layim.cache().friend
                        ,submit: function(group,index){ 
                            $.post('/live/index/setgrp', {
                                  uid: zqfdata.uid
                                  ,groupid:zqfdata.groupid
                                  ,groupname:zqfdata.groupname
                                  ,type:2
                                }, function(res){
                                  res=JSON.parse(res);
                                  if(res.code != 0){
                                    return layer.msg(res.msg);
                                  }
                                  var data={
                                    type: 'group' 
                                    ,avatar: zqfdata.avatar 
                                    ,groupname: zqfdata.groupname 
                                    ,id: zqfdata.groupid
                                  }
                                  layim.addList(data);
                                  layer.close(index);
                                });
                        }
                });
                break;
                 case 'agreegroup':
                layer.msg(zqfdata.username+'同意你的加入群'+zqfdata.groupname+'的申请', {
                                        icon: 1
                                        ,shade: 0.5
                                        ,time: 5000
                                    }, function(){
                                        //layer.close(index);
                                    });
                var data={
                                    type: 'group' 
                                    ,avatar: zqfdata.avatar 
                                    ,groupname: zqfdata.groupname 
                                    ,id:zqfdata.groupid
                                  }
                layim.addList(data);
                break;
              }



        }
    }

//监听聊天窗口的切换
  layim.on('chatChange', function(res){
    var type = res.data.type;
    if(type === 'friend'){
      //模拟标注好友状态
      //layim.setChatStatus('<span style="color:#FF5722;">在线</span>');
    } else if(type === 'group'){
      //模拟系统消息
      /*layim.getMessage({
        system: true
        ,id: res.data.id
        ,type: "group"
        ,content: '模拟群员'+(Math.random()*100|0) + '加入群聊'
      });*/
    }
  });


  //监听发送消息
  layim.on('sendMessage', function(data){
    var To = data.to; 
    if(To.type === 'friend'){
      //layim.setChatStatus('<span style="color:#FF5722;">对方正在输入。。。</span>');
      // if(To.fromid!=undefined && To.fromid!=To.id){
      // 	To.id=To.fromid;
      // 	To.fromid=livego_result.data.mine.id;
      // }
      obj = {
          Username: data.mine.username
          ,Data: data.mine.content
          ,Mtype:'mess'
          ,Img: data.mine.avatar
          ,Id:To.id
          ,type: To.type
          ,uid:userinfo['uid']
          ,Timestamp:Date.parse(new Date())
                
        }
        receiver_socket.send(JSON.stringify(obj));
    }
    if(To.type === 'group'){
         obj = {
          Username: data.mine.username
          ,Data: data.mine.content
          ,Mtype: 'mess'
          ,Img: data.mine.avatar
          ,Id:To.id
          ,type: To.type
          ,uid:userinfo['uid']
          ,Timestamp:Date.parse(new Date())
                
        }
        receiver_socket.send(JSON.stringify(obj));
      }

    //演示自动回复
    /*setTimeout(function(){
      var obj = {};
      if(To.type === 'group'){
        obj = {
          username: '模拟群员'+(Math.random()*100|0)
          ,avatar: layui.cache.dir + 'images/face/'+ (Math.random()*72|0) + '.gif'
          ,id: To.id
          ,type: To.type
          ,content: autoReplay[Math.random()*9|0]
        }
      } else {
        obj = {
          username: To.name
          ,avatar: To.avatar
          ,id: To.id
          ,type: To.type
          ,content: autoReplay[Math.random()*9|0]
        }
        layim.setChatStatus('<span style="color:#FF5722;">在线</span>');
      }
      layim.getMessage(obj);
    }, 1000);*/
  });

  //监听聊天窗口的切换
  /*layim.on('chatChange', function(res){
    console.log(res)
    var type = res.data.type;
    console.log(res.data.id)
    if(type === 'friend'){
      //模拟标注好友状态
      //layim.setChatStatus('<span style="color:#FF5722;">在线</span>');
    } else if(type === 'group'){
      //模拟系统消息
      layim.getMessage({
        system: true
        ,id: res.data.id
        ,type: "group"
        ,content: '模拟群员'+(Math.random()*100|0) + '加入群聊'
      });
    }
  });*/
  });
  
