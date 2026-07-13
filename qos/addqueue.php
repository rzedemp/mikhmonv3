<?php
/*
 *  Copyright (C) 2026 lightnet19
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 */
error_reporting(0);
if (!isset($_SESSION["mikhmon"])) {
    header("Location:./admin.php?id=login");
    exit;
}

$getqueues = $API->comm("/queue/simple/print");
$TotalQueues = count($getqueues);

$error_msg = "";
$success_msg = "";

if (isset($_POST['save'])) {
    csrf_verify();
    
    $name = trim($_POST['name'] ?? '');
    $target = trim($_POST['target'] ?? '');
    $max_limit_up = trim($_POST['max_limit_up'] ?? '0');
    $max_limit_down = trim($_POST['max_limit_down'] ?? '0');
    $limit_at_up = trim($_POST['limit_at_up'] ?? '0');
    $limit_at_down = trim($_POST['limit_at_down'] ?? '0');
    $parent = trim($_POST['parent'] ?? 'none');
    $comment = trim($_POST['comment'] ?? '');

    if (empty($name) || empty($target)) {
        $error_msg = "Name and Target are required fields.";
    } else {
        $max_limit = $max_limit_up . "/" . $max_limit_down;
        $limit_at = $limit_at_up . "/" . $limit_at_down;
        
        $params = array(
            "name" => $name,
            "target" => $target,
            "max-limit" => $max_limit
        );
        
        if ($limit_at !== "0/0" && $limit_at !== "/") {
            $params["limit-at"] = $limit_at;
        }
        
        if ($parent !== "none") {
            $params["parent"] = $parent;
        }
        
        if ($comment !== "") {
            $params["comment"] = $comment;
        }

        $result = $API->comm("/queue/simple/add", $params);

        if (isset($result['!trap'])) {
            $error_msg = "MikroTik Error: " . ($result['!trap'][0]['message'] ?? 'Unknown error');
        } else {
            // Find newly added queue to get its ID for redirection
            $getnew = $API->comm("/queue/simple/print", array("?name" => $name));
            $qid = $getnew[0]['.id'] ?? '';
            
            // Log administrative QoS creation
            write_audit_log($_SESSION['mikhmon'], 'QOS_QUEUE_CREATE', "Created simple queue '$name' targeting '$target'");

            if ($qid !== '') {
                echo "<script>window.location='./?qos=edit&id=" . urlencode($qid) . "&session=" . $session . "'</script>";
                exit;
            } else {
                echo "<script>window.location='./?qos=queues&session=" . $session . "'</script>";
                exit;
            }
        }
    }
}
?>

<div class="row">
    <div class="col-8 col-box-12">
        <div class="card box-bordered">
            <div class="card-header">
                <h3>
                    <i class="fa fa-plus-circle"></i> Add Simple Queue
                    <small id="loader" style="display: none;">
                        <i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?></i>
                    </small>
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($error_msg)) { ?>
                    <div class="bg-danger" style="padding: 10px; border-radius: 5px; margin-bottom: 15px; color: white;">
                        <i class="fa fa-warning"></i> <?= htmlspecialchars($error_msg); ?>
                    </div>
                <?php } ?>

                <form autocomplete="off" method="post" action="">
                    <?php echo csrf_field(); ?>
                    <div style="margin-bottom: 15px;">
                        <a class="btn bg-warning" href="./?qos=queues&session=<?= $session; ?>">
                            <i class="fa fa-close"></i> Close
                        </a>
                        <button type="submit" onclick="loader()" class="btn bg-primary" name="save">
                            <i class="fa fa-save"></i> Save
                        </button>
                    </div>

                    <table class="table">
                        <tr>
                            <td class="align-middle" style="width: 200px;">Queue Name</td>
                            <td>
                                <input class="form-control" type="text" autocomplete="off" name="name" required="1" autofocus placeholder="e.g. client-queue">
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Target</td>
                            <td>
                                <input class="form-control" type="text" autocomplete="off" name="target" required="1" placeholder="e.g. 192.168.88.0/24 or ether2">
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Max Limit Upload</td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-10 col-box-9">
                                        <input class="group-item group-item-l" type="text" id="max_limit_up" name="max_limit_up" placeholder="e.g. 1M or 512k" required="1">
                                    </div>
                                    <div class="input-group-2 col-box-3">
                                        <select class="group-item group-item-r" onchange="document.getElementById('max_limit_up').value = this.value;">
                                            <option value="">Presets</option>
                                            <option value="512k">512k</option>
                                            <option value="1M">1M</option>
                                            <option value="2M">2M</option>
                                            <option value="3M">3M</option>
                                            <option value="5M">5M</option>
                                            <option value="10M">10M</option>
                                            <option value="20M">20M</option>
                                            <option value="50M">50M</option>
                                            <option value="100M">100M</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Max Limit Download</td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-10 col-box-9">
                                        <input class="group-item group-item-l" type="text" id="max_limit_down" name="max_limit_down" placeholder="e.g. 2M or 1M" required="1">
                                    </div>
                                    <div class="input-group-2 col-box-3">
                                        <select class="group-item group-item-r" onchange="document.getElementById('max_limit_down').value = this.value;">
                                            <option value="">Presets</option>
                                            <option value="512k">512k</option>
                                            <option value="1M">1M</option>
                                            <option value="2M">2M</option>
                                            <option value="3M">3M</option>
                                            <option value="5M">5M</option>
                                            <option value="10M">10M</option>
                                            <option value="20M">20M</option>
                                            <option value="50M">50M</option>
                                            <option value="100M">100M</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Limit At Upload (Optional)</td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-10 col-box-9">
                                        <input class="group-item group-item-l" type="text" id="limit_at_up" name="limit_at_up" placeholder="e.g. 256k" value="0">
                                    </div>
                                    <div class="input-group-2 col-box-3">
                                        <select class="group-item group-item-r" onchange="document.getElementById('limit_at_up').value = this.value;">
                                            <option value="0">None</option>
                                            <option value="256k">256k</option>
                                            <option value="512k">512k</option>
                                            <option value="1M">1M</option>
                                            <option value="2M">2M</option>
                                            <option value="5M">5M</option>
                                            <option value="10M">10M</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Limit At Download (Optional)</td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-10 col-box-9">
                                        <input class="group-item group-item-l" type="text" id="limit_at_down" name="limit_at_down" placeholder="e.g. 512k" value="0">
                                    </div>
                                    <div class="input-group-2 col-box-3">
                                        <select class="group-item group-item-r" onchange="document.getElementById('limit_at_down').value = this.value;">
                                            <option value="0">None</option>
                                            <option value="256k">256k</option>
                                            <option value="512k">512k</option>
                                            <option value="1M">1M</option>
                                            <option value="2M">2M</option>
                                            <option value="5M">5M</option>
                                            <option value="10M">10M</option>
                                        </select>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Parent Queue</td>
                            <td>
                                <select class="form-control" name="parent">
                                    <option value="none">none (Root)</option>
                                    <?php
                                    for ($i = 0; $i < $TotalQueues; $i++) {
                                        $pqname = $getqueues[$i]['name'];
                                        echo "<option value=\"" . htmlspecialchars($pqname) . "\">" . htmlspecialchars($pqname) . "</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Comment</td>
                            <td>
                                <input class="form-control" type="text" autocomplete="off" name="comment" placeholder="Optional comments">
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-4 col-box-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-book"></i> Readme</h3>
            </div>
            <div class="card-body">
                <p><b>Target:</b> Specify the target IP address, subnet, IP range, or router interface name to limit traffic for. (e.g. <code>192.168.88.20/32</code> or <code>192.168.1.0/24</code>).</p>
                <p><b>Max Limit:</b> The maximum upload/download speed limits allowed (e.g. <code>1M</code> for 1 Mbps, <code>512k</code> for 512 Kbps).</p>
                <p><b>Limit At:</b> The guaranteed bandwidth allocated to this queue when parent limits are saturated.</p>
                <p><b>Parent:</b> Nest queues hierarchically to share parent bandwidth among multiple sub-queues.</p>
            </div>
        </div>
    </div>
</div>
