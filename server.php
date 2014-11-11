<?php
  
  define('WS_HOST', server);
  define('WS_PORT', port);
  define('WS_SCRIPT', WS_HOST.":".WS_PORT);

  $null = NULL;

  $master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Failed: socket_create()");
  socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1) or die("Failed: socket_option()");
  socket_bind($master, 0, WS_PORT) or die("Failed: socket_bind()");
  socket_listen($master, 10) or die("Failed: socket_listen()");
  echo "Listening on ".WS_HOST.":".WS_PORT."\n-----\n";
  
  $users = array($master);
  
  while (true) {
  
  	$changed = $users;
  	socket_select($changed, $null, $null, 0, 10);
  	
  	if (in_array($master, $changed)) {
  		$new_socket = socket_accept($master);
  		$users[] = $new_socket;
  		
  		$data = socket_read($new_socket, 1024);
  		doHandShake($data, $new_socket);
  
  		$found_socket = array_search($master, $changed);
  		unset($changed[$found_socket]);
  		
  		// do things with the new user
  		socket_getpeername($new_socket, $ip);
      echo " + new $ip\n";
      
  		$response = array("type" => "system", "message" => "$ip is connected");
  		send_message($response);    		
  	}
  	
  	foreach ($changed as $changed_socket) {	

  		//check for any incomming data
  		while(socket_recv($changed_socket, $buffer, 1024, 0) >= 1) {

        // $buffer contain recieve data from client, do some stuff
  			$data = json_decode(unmask($buffer), true);
        if($data['user'] != '') {
    			$response_text = array("type" => "message", "user" => $data['user'], "message" => $data['message']);
          send_message($response_text);
  			}
  			
  			break 2;
  		}
  		  
  		$buffer = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
      
      // if user is deconnected		
  		if ($buffer === false) {
  			$socket = array_search($changed_socket, $users);
  			unset($users[$socket]);
  
        // do things with the old user
  			socket_getpeername($changed_socket, $ip);
        echo " - bye $ip\n";
        $response = array("type" => "system", "message" => "$ip is disconnected");
  			send_message($response);
  			
  			// exit the loop
  			break;
  		}
  		
  	}
  }
  
  // only if we exit the loop
  socket_close($master);
  
  // function to send data to the client, give a php array, send a json array
  function send_message($message) {
  	global $users;
  	$text = mask(json_encode($message));
  	
  	foreach($users as $socket) {
  		@socket_write($socket,$text,strlen($text));
  	}
  	
  	return true;
  }
  
  function unmask($text) {
  	$length = ord($text[1]) & 127;
  	if($length == 126) {
  		$masks = substr($text, 4, 4);
  		$data = substr($text, 8);
  	} elseif($length == 127) {
  		$masks = substr($text, 10, 4);
  		$data = substr($text, 14);
  	} else {
  		$masks = substr($text, 2, 4);
  		$data = substr($text, 6);
  	}
  	
  	$text = "";
  	for ($i = 0; $i < strlen($data); ++$i) {
  		$text .= $data[$i] ^ $masks[$i%4];
  	}
  	return $text;
  }
  
  function mask($text) {
  	$b1 = 0x80 | (0x1 & 0x0f);
  	$length = strlen($text);
  	
  	if($length <= 125)
  		$header = pack('CC', $b1, $length);
  	elseif($length > 125 && $length < 65536)
  		$header = pack('CCn', $b1, 126, $length);
  	elseif($length >= 65536)
  		$header = pack('CCNN', $b1, 127, $length);
  		
  	return $header.$text;
  }
  
  function doHandShake($data, $socket) {
  	$headers = array();
  	$lines = preg_split("/\r\n/", $data);
  	foreach($lines as $line) {
  		$line = rtrim($line);
  		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
  			$headers[$matches[1]] = $matches[2];
  	}
  	
  	$key = base64_encode(pack('H*', sha1($headers['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
  	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n";
  	$upgrade .= "Upgrade: websocket\r\n";
  	$upgrade .= "Connection: Upgrade\r\n";
  	$upgrade .= "WebSocket-Origin: ".WS_HOST."\r\n";
  	$upgrade .= "WebSocket-Location: ws://".WS_SCRIPT."\r\n";
  	$upgrade .= "Sec-WebSocket-Accept:$key\r\n\r\n";
  	
  	socket_write($socket,$upgrade,strlen($upgrade));
  }
