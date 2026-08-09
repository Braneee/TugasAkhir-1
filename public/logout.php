<?php
require_once 'api/session.php';
session_destroy();
header('Location: login.php');
exit;
