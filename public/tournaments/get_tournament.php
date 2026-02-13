<?php
$_SERVER['REQUEST_URI'] = '/tournaments/api/get' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
require dirname(__DIR__) . '/index.php';
