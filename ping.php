<?php

require_once('lib/config.php');

if ($fp = fsockopen(XMPP_HTTP_HOST, 80, $errCode, $errStr, 1)) {   
   print 1;
} else {
   print 0; 
} 
fclose($fp);
?>