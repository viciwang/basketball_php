<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require 'basketball_php/application/controllers/GameScoreManager.php';

$date = nil;//'2016-3-6';
$date = $_GET['date'];

$manager = new GameScoreManager();
$manager->getGameScore($date);

?>