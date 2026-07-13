<?php
/*
 *		Copyright (C) 2018 Laksamadi Guko.
 *
 *		This program is free software; you can redistribute it and/or modify
 *		it under the terms of the GNU General Public License as published by
 *		the Free Software Foundation; either version 2 of the License, or
 *		(at your option) any later version.
 *
 *		This program is distributed in the hope that it will be useful,
 *		but WITHOUT ANY WARRANTY; without even the implied warranty of
 *		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.		See the
 *		GNU General Public License for more details.
 *
 *		You should have received a copy of the GNU General Public License
 *		along with this program.		If not, see <http://www.gnu.org/licenses/>.
 */
session_start();
 // hide all error
error_reporting(0);

// Security Headers
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.highcharts.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self' https://api.github.com https://raw.githubusercontent.com;");
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>MIKHMON <?= $hotspotname; ?></title>
		<meta charset="utf-8">
		<meta http-equiv="cache-control" content="private" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<!-- Tell the browser to be responsive to screen width -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- Theme color -->
		<meta name="theme-color" content="<?= $themecolor ?>" />
		<!-- Font Awesome 6 + v4 shims -->
		<link rel="stylesheet" type="text/css" href="css/font-awesome/css/all.min.css" />
		<link rel="stylesheet" type="text/css" href="css/font-awesome/css/v4-shims.min.css" />
		<!-- Mikhmon UI -->
		<link rel="stylesheet" href="css/mikhmon-ui.<?= $theme; ?>.min.css">
		<!-- Modernized Theme Layer -->
		<link rel="stylesheet" href="css/mikhmon-theme.css">
		<!-- favicon -->
		<link rel="icon" href="./img/favicon.png" />
		<!-- jQuery -->
		<script src="js/jquery.min.js"></script>
		<!-- pace -->
		<link href="css/pace.<?= $theme; ?>.css" rel="stylesheet" />
		<script src="js/pace.min.js"></script>

		
	</head>
	<body>
		<script>
			if (localStorage.getItem('mikhmon_theme') === 'dark') {
				document.body.setAttribute('data-theme', 'dark');
			}
			document.addEventListener("DOMContentLoaded", function() {
				var isDark = document.body.getAttribute('data-theme') === 'dark';
				var icon = document.getElementById('darkModeIcon');
				if (icon) {
					icon.className = isDark ? 'fa fa-sun-o' : 'fa fa-moon-o';
				}
			});
		</script>
		<div class="wrapper">

			
