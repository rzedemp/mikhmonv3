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

$qid = $_GET['id'] ?? '';
if (empty($qid)) {
    echo "<script>window.location='./?qos=queues&session=" . $session . "'</script>";
    exit;
}

// Fetch the specific queue
$getqueue = $API->comm("/queue/simple/print", array("?.id" => $qid));
if (empty($getqueue)) {
    echo "<div class='row'><div class='col-12'><div class='card bg-danger text-white' style='padding:15px;'>Simple Queue not found. <a href='./?qos=queues&session=" . $session . "' class='text-white' style='text-decoration:underline;'>Go back</a></div></div></div>";
    exit;
}

$queue = $getqueue[0];
$qname = $queue['name'];
$qtarget = $queue['target'];
$qparent = $queue['parent'] ?? 'none';
$qcomment = $queue['comment'] ?? '';

// Fetch all queues for the parent dropdown
$allqueues = $API->comm("/queue/simple/print");
$TotalQueues = count($allqueues);

// Parse limit bytes helper
function parse_mikrotik_limit($bytes) {
    $bytes = (float)$bytes;
    if ($bytes <= 0) return '0';
    if ($bytes >= 1000000 && ($bytes % 1000000 == 0)) {
        return ($bytes / 1000000) . 'M';
    }
    if ($bytes >= 1000 && ($bytes % 1000 == 0)) {
        return ($bytes / 1000) . 'k';
    }
    return $bytes;
}

// Parse limits
$max_limit_arr = explode('/', $queue['max-limit'] ?? '0/0');
$limit_at_arr = explode('/', $queue['limit-at'] ?? '0/0');

$curr_max_up = parse_mikrotik_limit($max_limit_arr[0]);
$curr_max_down = parse_mikrotik_limit($max_limit_arr[1]);

$curr_limit_up = parse_mikrotik_limit($limit_at_arr[0]);
$curr_limit_down = parse_mikrotik_limit($limit_at_arr[1]);

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
            ".id" => $qid,
            "name" => $name,
            "target" => $target,
            "max-limit" => $max_limit,
            "limit-at" => $limit_at,
            "comment" => $comment
        );
        
        if ($parent === "none" || empty($parent)) {
            $params["parent"] = "none";
        } else {
            $params["parent"] = $parent;
        }

        $result = $API->comm("/queue/simple/set", $params);

        if (isset($result['!trap'])) {
            $error_msg = "MikroTik Error: " . ($result['!trap'][0]['message'] ?? 'Unknown error');
        } else {
            $success_msg = "Simple Queue updated successfully.";
            
            // Log administrative QoS update
            write_audit_log($_SESSION['mikhmon'], 'QOS_QUEUE_UPDATE', "Updated simple queue '$name' targeting '$target'");

            // Refresh values
            $qname = $name;
            $qtarget = $target;
            $qparent = $parent;
            $qcomment = $comment;
            
            $curr_max_up = $max_limit_up;
            $curr_max_down = $max_limit_down;
            
            $curr_limit_up = $limit_at_up;
            $curr_limit_down = $limit_at_down;
        }
    }
}
?>

<div class="row">
    <div class="col-8 col-box-12">
        <div class="card box-bordered">
            <div class="card-header">
                <h3>
                    <i class="fa fa-edit"></i> Edit Simple Queue: <?= htmlspecialchars($qname); ?>
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
                <?php if (!empty($success_msg)) { ?>
                    <div class="bg-green" style="padding: 10px; border-radius: 5px; margin-bottom: 15px; color: white;">
                        <i class="fa fa-check"></i> <?= htmlspecialchars($success_msg); ?>
                    </div>
                <?php } ?>

                <form autocomplete="off" method="post" action="">
                    <?php echo csrf_field(); ?>
                    <div style="margin-bottom: 15px;">
                        <a class="btn bg-warning" href="./?qos=queues&session=<?= $session; ?>">
                            <i class="fa fa-close"></i> Close
                        </a>
                        <button type="submit" onclick="loader()" class="btn bg-primary" name="save">
                            <i class="fa fa-save"></i> Save Changes
                        </button>
                    </div>

                    <table class="table">
                        <tr>
                            <td class="align-middle" style="width: 200px;">Queue Name</td>
                            <td>
                                <input class="form-control" type="text" autocomplete="off" name="name" value="<?= htmlspecialchars($qname); ?>" required="1" autofocus>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Target</td>
                            <td>
                                <input class="form-control" type="text" autocomplete="off" name="target" value="<?= htmlspecialchars($qtarget); ?>" required="1">
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Max Limit Upload</td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-10 col-box-9">
                                        <input class="group-item group-item-l" type="text" id="max_limit_up" name="max_limit_up" value="<?= htmlspecialchars($curr_max_up); ?>" placeholder="e.g. 1M or 512k" required="1">
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
                                        <input class="group-item group-item-l" type="text" id="max_limit_down" name="max_limit_down" value="<?= htmlspecialchars($curr_max_down); ?>" placeholder="e.g. 2M or 1M" required="1">
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
                            <td class="align-middle">Limit At Upload</td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-10 col-box-9">
                                        <input class="group-item group-item-l" type="text" id="limit_at_up" name="limit_at_up" value="<?= htmlspecialchars($curr_limit_up); ?>" placeholder="e.g. 256k">
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
                            <td class="align-middle">Limit At Download</td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-10 col-box-9">
                                        <input class="group-item group-item-l" type="text" id="limit_at_down" name="limit_at_down" value="<?= htmlspecialchars($curr_limit_down); ?>" placeholder="e.g. 512k">
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
                                    <option value="none" <?= ($qparent === 'none' || empty($qparent)) ? 'selected' : ''; ?>>none (Root)</option>
                                    <?php
                                    for ($i = 0; $i < $TotalQueues; $i++) {
                                        $pqname = $allqueues[$i]['name'];
                                        // Do not allow self to be parent
                                        if ($pqname === $qname) continue;
                                        $selected = ($qparent === $pqname) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($pqname) . "\" $selected>" . htmlspecialchars($pqname) . "</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Comment</td>
                            <td>
                                <input class="form-control" type="text" autocomplete="off" name="comment" value="<?= htmlspecialchars($qcomment); ?>" placeholder="Optional comments">
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
