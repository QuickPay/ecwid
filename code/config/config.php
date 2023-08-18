<?php 
	$host = 'localhost';
	$username = 'nirvir_mavendev';
	$password = '{Q9eG5pzVD_M';
	$database = 'nirvir_quickwid';
	global $con;
	$con = mysqli_connect($host,$username,$password,$database);
	// Check connection
	if (mysqli_connect_errno()){
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
?>