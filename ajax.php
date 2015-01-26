<?php
/**
 * Created by PhpStorm.
 * User: Nfq
 * Date: 0025 2015-01-25
 * Time: 23:46
 */


    session_start();
    include 'Categories.php';
    $id = (int)$_REQUEST['id'];
    $treeSessionObj = $_SESSION['tree'];
    $treeSessionObj->removeCategory($id);
    echo 1;






