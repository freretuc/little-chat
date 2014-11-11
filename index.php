<!DOCTYPE>
<html>
  <head>
    <meta charset='UTF-8' />
    <style>
      * {
        margin: 0;
        padding: 0;
      }
      
      html {
        margin: 10px;
        font-family: "Tahoma";
        font-size: 12px;
        line-height: 20px;
      }
      
      .time {
        margin-right: 10px;
        font-style: italic;
      }
      
      .system {
        color: #ccc;
      }
      
      strong {
        margin-right: 10px;
      }
      
      input[type=text] {
        padding: 3px;
      }
      
    </style>
    <title>chat</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
    <script>
    var ws;

    $(document).ready(function(){
      ws = new WebSocket("ws://server:port");
      
      ws.onopen = function(ev) {
        $("div").append('<p class="system info">Connected !</p>');
      }

      ws.onerror = function(ev) {
        $("div").append('<p class="system error">Error ! ' + ev.data + '</p>');
      }

      ws.onclose = function(ev) {
        $("div").append('<p class="system info">Connection closed !</p>');
      }

      ws.onmessage = function(ev) {
        var data = JSON.parse(ev.data);
        var d = new Date();
        var time = d.toLocaleTimeString();
        
        if(data.type == "system") {
          $("div").append('<p class="' + data.type + '"><span class="time">' + time + '</span>' + data.message + '</p>');
        } else {
          $("div").append('<p class="' + data.type + '"><span class="time">' + time + '</span><strong>' + data.user + '</strong>' + data.message + '</p>'); 
        }      
      }

      $("form").submit(function() {
        
        event.preventDefault();
        
        var user = $("#user").val();
        var message = $("#message").val();
        
        if(user == "") {
          alert("You must provide a username !");
          return;
        }
        
        if(message == "") {
          alert("Message can't be empty !");
          return;
        }
        
        $("#message").val("");
        
        var data = { user: user , message: message }
        ws.send(JSON.stringify(data));
      });
      
    });
    </script>
  </head>
  <body>
    <form>
      <input type="text" name="user" id="user" placeholder="username" />
      <input type="text" name="message" id="message" placeholder="message" />
      <input type="submit" value="send" />
    </form>
    <div>  
    </div>
  </body>
</html>