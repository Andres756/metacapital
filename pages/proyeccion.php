<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$pageTitle   = 'Proyección';
$pageSection = 'Proyeccion';
require __DIR__ . '/proximamente.php';