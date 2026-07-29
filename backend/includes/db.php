<?php
// Ipakita ang mga PHP errors habang nagde-debug tayo (pwede nating tanggalin ito mamaya kapag gumagana na)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "aws-0-ap-southeast-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.jefvrwyobieeilwrtqqp";
$password = "WalaAkongMaisip282003";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());
    die('Database connection failed: ' . $e->getMessage());
}