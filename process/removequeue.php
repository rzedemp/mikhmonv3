<?php
/*
 *  Copyright (C) 2026 lightnet19
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 */
session_start();
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
    header("Location:./admin.php?id=login");
    exit;
}

// remove queue
$API->comm("/queue/simple/remove", array(
    ".id" => "$removequeue",
));

// redirect to simple queue list
echo "<script>window.location='./?qos=queues&session=" . $session . "'</script>";
?>
