
<?php
include_once 'TransferUsers.php';

$file_arr=['borek','europa_centralna','galardia','nowy_rynek','alfa'];
$tu=new TransferUsers($file_arr);
$tu->start();


?>