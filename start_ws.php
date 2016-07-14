<?php
use Workerman\Worker;
require_once __DIR__.'/Workerman/Autoloader.php';
$ws = new Worker("websocket://0.0.0.0:2345");
$ws->count = 4;
//增加已连接客户端
$ws->hasConnections = array();

$ws->onMessage = function ($connection, $data) use ($ws) {

	$data = json_decode($data,true);

	$ip = $connection->getRemoteIp();
	$content = '';
	switch ($data['type']) {
		case 'login':
			$ws->hasConnections[$connection->getRemoteIp()] = array('name' => $data['name'], 'id' => $connection->id);
			$content = '<b style="color:red">系统：</b>欢迎 <i>'.$data['name'].'</i> 加入聊天室！    '.date('Y-m-d H:i:s');
			//拼装返回的数据结构
			$back_data = array('content' => $content, 'client_id' => $connection->id, 'client_name' => $data['name'], 'type' => 'login', 'clients' => $ws->hasConnections);
			
			break;
		case 'prisay':
			$content = '<i style="color:green">'. $data['name'] .'</i>对你 说：'.$data['content'].'    '.date('Y-m-d H:i:s');
			$mycontent = '你对<i style="color:green">'. $ws->hasConnections[$data['to_client']]['name'] .'</i> 说：'.$data['content'].'    '.date('Y-m-d H:i:s');
			//拼装返回的数据结构
			$back_data = array('content' => $content, 'type' => 'say');
			$mycontent = array('content' => $mycontent, 'type' => 'say');
			break;
		default:
			$content = '<i style="color:green">'. $data['name'] .'</i> 说：'.$data['content'].'    '.date('Y-m-d H:i:s');
			//拼装返回的数据结构
			$back_data = array('content' => $content, 'type' => 'say');
			
			break;
	}
	$back_data = json_encode($back_data);
	$mycontent = json_encode($mycontent);
	//判断是否是私聊
	if ($data['to_client'] == 'all') {
		foreach ($ws->connections as $con)
        {
       		$con->send($back_data);
           
        }
	} else {
		$connection->send($mycontent);
		$ws->connections[$data['to_client']['id']]->send($back_data);
	}
   	
	
};

$ws->onClose = function ($connection) use ($ws){
	$ip = $connection->getRemoteIp();
	$name = $ws->hasConnections[$ip]['name'];
	unset($ws->hasConnections[$ip]);
   	foreach ($ws->connections as $connection1)
        {
       		$connection1->send(json_encode(array('content' => '<b style="color:red">系统：</b> <i>'.$name.'走了', 'type' => 'login', 'clients' => $ws->hasConnections)));
           
        }
}
//Worker::runAll();
?>