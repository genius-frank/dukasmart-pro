<?php
// logout.php
session_start();
session_destroy();
require_once __DIR__ . '/db.php';
redirect_to('login.php');
