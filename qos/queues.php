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
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fa fa-tasks"></i> QoS / Simple Queues
                    <span style="font-size: 14px">
                        &nbsp; | &nbsp; 
                        <a href="./?qos=add&session=<?= $session; ?>" title="Add Simple Queue">
                            <i class="fa fa-plus-circle"></i> Add Queue
                        </a>
                    </span>
                    <small id="loader" style="display: none;">
                        <i><i class='fa fa-circle-o-notch fa-spin'></i> <?= $_processing ?></i>
                    </small>
                </h3>
            </div>
            <div class="card-body">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-6">
                        <div class="input-group">
                            <input id="filterTable" type="text" style="padding: 6px; width: 100%; max-width: 300px;" class="group-item" placeholder="<?= $_search ?>">
                        </div>
                    </div>
                </div>
                
                <div class="overflow mr-t-10 box-bordered" style="max-height: 75vh">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th>Name</th>
                                <th>Target</th>
                                <th class="text-right">Max Limit (Up/Down)</th>
                                <th class="text-right">Limit At (Up/Down)</th>
                                <th>Parent</th>
                                <th>Comment</th>
                                <th style="width: 100px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($TotalQueues == 0) {
                                echo "<tr><td colspan='8' class='text-center'>No simple queues found.</td></tr>";
                            } else {
                                for ($i = 0; $i < $TotalQueues; $i++) {
                                    $queue = $getqueues[$i];
                                    $qid = $queue['.id'];
                                    $qname = $queue['name'];
                                    $qtarget = $queue['target'];
                                    $qparent = $queue['parent'] ?? 'none';
                                    $qcomment = $queue['comment'] ?? '';
                                    
                                    // Parse max-limit
                                    $max_limit = explode('/', $queue['max-limit'] ?? '0/0');
                                    $max_up = formatBites((float)$max_limit[0]);
                                    $max_down = formatBites((float)$max_limit[1]);
                                    
                                    // Parse limit-at
                                    $limit_at = explode('/', $queue['limit-at'] ?? '0/0');
                                    $limit_up = formatBites((float)$limit_at[0]);
                                    $limit_down = formatBites((float)$limit_at[1]);
                                    
                                    $disabled = $queue['disabled'] == 'true';
                                    $rowClass = $disabled ? 'text-muted bg-light' : '';
                                    ?>
                                    <tr class="<?= $rowClass; ?>">
                                        <td style="text-align: center;"><?= $i + 1; ?></td>
                                        <td>
                                            <a href="./?qos=edit&id=<?= urlencode($qid); ?>&session=<?= $session; ?>" title="Edit Queue">
                                                <i class="fa fa-edit"></i> <?= htmlspecialchars($qname); ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($qtarget); ?></td>
                                        <td class="text-right"><?= $max_up; ?> / <?= $max_down; ?></td>
                                        <td class="text-right"><?= $limit_up; ?> / <?= $limit_down; ?></td>
                                        <td><?= htmlspecialchars($qparent); ?></td>
                                        <td><?= htmlspecialchars($qcomment); ?></td>
                                        <td style="text-align: center;">
                                            <a href="./?qos=edit&id=<?= urlencode($qid); ?>&session=<?= $session; ?>" class="text-blue" title="Edit" style="margin-right: 10px;">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" class="text-red" title="Delete" onclick="if(confirm('Are you sure you want to delete Simple Queue \'<?= htmlspecialchars($qname, ENT_QUOTES); ?>\'?')){ window.location.href='./?remove-queue=<?= urlencode($qid); ?>&session=<?= $session; ?>'; }">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
