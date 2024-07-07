<?php
if (!isset($_GET['user'])) {
    echo '<meta http-equiv="refresh" content="1,url=https://gallant-driscoll.198-71-62-113.plesk.page/implementta/login.php">';
}
session_start();
$_SESSION['user']=$_GET['user'];
echo '<meta http-equiv="refresh" content="1,url=https://gallant-driscoll.198-71-62-113.plesk.page/implementta/modulos/Tijuana-p/public/index">';