<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/virtex.php');

$virtex = new virtex();
echo $virtex->execPayment($cart);
include_once(dirname(__FILE__).'/../../footer.php');

?>