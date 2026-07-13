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

$color = array('1' => 'bg-blue', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-red', 'bg-yellow', 'bg-green', 'bg-teal', 'bg-cyan', 'bg-grey', 'bg-light-blue');

// Collect all session names
$sessions = [];
foreach (file('./include/config.php') as $line) {
    $value = explode("'", $line)[1];
    if ($value != "" && $value != "mikhmon") {
        $sessions[] = $value;
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-server"></i> NOC Dashboard (Multi-Router) &nbsp; | &nbsp;
                    <span id="noc-countdown" style="font-size: 14px; font-weight: normal; color: var(--text-muted);">Auto-refresh in 10s</span>
                    <button class="btn btn-sm text-white" id="btn-refresh-all" style="background: transparent; border: none; cursor: pointer;" title="Refresh all now">
                        <i class="fa fa-refresh" id="icon-refresh-all"></i>
                    </button>
                    <select id="select-refresh-interval" class="ses pd-5" style="font-size: 12px; margin-left: 10px;">
                        <option value="10">Auto: 10s</option>
                        <option value="30">Auto: 30s</option>
                        <option value="60">Auto: 60s</option>
                        <option value="0">Auto: Off</option>
                    </select>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($sessions)) { ?>
                        <div class="col-12 text-center" style="padding: 40px;">
                            <i class="fa fa-info-circle fa-2x" style="color: #ccc;"></i>
                            <p style="margin-top: 10px;">No routers configured. Go to <a href="./admin.php?id=settings&router=new" class="text-blue">Add Router</a> to configure one.</p>
                        </div>
                    <?php } else {
                        foreach ($sessions as $index => $sess) {
                            $themeClass = $color[($index % 11) + 1];
                            // Parse name from config data
                            $hsName = explode('%', $data[$sess][4])[1] ?? $sess;
                            ?>
                            <div class="col-4 col-box-12" style="margin-bottom: 20px;">
                                <div class="card" style="box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e1e4e8; border-radius: 6px;">
                                    <div class="card-header <?= $themeClass; ?>" style="color: white; border-top-left-radius: 5px; border-top-right-radius: 5px; padding: 10px 15px;">
                                        <h4 class="card-title" style="margin: 0; display: flex; align-items: center; justify-content: space-between; font-weight: 600;">
                                            <span><i class="fa fa-server mr-2"></i> <?= htmlspecialchars($sess); ?></span>
                                            <span class="badge" id="status-badge-<?= htmlspecialchars($sess); ?>" style="background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 4px; font-size: 11px;">
                                                <i class="fa fa-circle-o-notch fa-spin mr-1"></i> Loading
                                            </span>
                                        </h4>
                                    </div>
                                    <div class="card-body" style="padding: 15px;">
                                        <table class="table table-sm" style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                                            <tr>
                                                <td style="padding: 5px 0; font-weight: 500; border-bottom: 1px solid #f1f1f1;">IP Address</td>
                                                <td style="padding: 5px 0; text-align: right; border-bottom: 1px solid #f1f1f1;" id="ip-<?= htmlspecialchars($sess); ?>">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-weight: 500; border-bottom: 1px solid #f1f1f1;">Hotspot Name</td>
                                                <td style="padding: 5px 0; text-align: right; border-bottom: 1px solid #f1f1f1; font-weight: bold;"><?= htmlspecialchars($hsName); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-weight: 500; border-bottom: 1px solid #f1f1f1;">Identity</td>
                                                <td style="padding: 5px 0; text-align: right; border-bottom: 1px solid #f1f1f1;" id="identity-<?= htmlspecialchars($sess); ?>">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-weight: 500; border-bottom: 1px solid #f1f1f1;">Uptime</td>
                                                <td style="padding: 5px 0; text-align: right; border-bottom: 1px solid #f1f1f1;" id="uptime-<?= htmlspecialchars($sess); ?>">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-weight: 500; border-bottom: 1px solid #f1f1f1;">RouterOS Version</td>
                                                <td style="padding: 5px 0; text-align: right; border-bottom: 1px solid #f1f1f1;" id="version-<?= htmlspecialchars($sess); ?>">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-weight: 500; border-bottom: 1px solid #f1f1f1;">Active Users</td>
                                                <td style="padding: 5px 0; text-align: right; border-bottom: 1px solid #f1f1f1;" id="active-<?= htmlspecialchars($sess); ?>">-</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0 4px 0; font-weight: 500;" colspan="2">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                        <span>CPU Load</span>
                                                        <span id="cpu-text-<?= htmlspecialchars($sess); ?>">-</span>
                                                    </div>
                                                    <div style="background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                                                        <div id="cpu-bar-<?= htmlspecialchars($sess); ?>" style="width: 0%; height: 100%; background: #007bff; transition: width 0.4s ease;"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0 4px 0; font-weight: 500;" colspan="2">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                        <span>Memory Usage</span>
                                                        <span id="mem-text-<?= htmlspecialchars($sess); ?>">-</span>
                                                    </div>
                                                    <div style="background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                                                        <div id="mem-bar-<?= htmlspecialchars($sess); ?>" style="width: 0%; height: 100%; background: #28a745; transition: width 0.4s ease;"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                            <a href="./admin.php?id=settings&session=<?= htmlspecialchars($sess); ?>" class="btn btn-sm btn-bordered" style="padding: 5px 12px; font-size: 12px; border: 1px solid #ccc; border-radius: 4px; text-decoration: none; color: #333;"><i class="fa fa-edit"></i> Edit Settings</a>
                                            <button class="connect pointer btn btn-sm btn-blue" id="<?= htmlspecialchars($sess); ?>" style="padding: 5px 12px; font-size: 12px; border-radius: 4px; border: none; background: #007bff; color: white; cursor: pointer;"><i class="fa fa-external-link"></i> Open Session</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var sessions = <?php echo json_encode($sessions); ?>;
    var refreshTimer = null;
    var countdownValue = 10;
    var countdownTimer = null;

    function fetchRouterStatus(session) {
        var badge = $("#status-badge-" + session);
        // Show spinner inside badge
        badge.html('<i class="fa fa-circle-o-notch fa-spin mr-1"></i> Checking');
        badge.removeClass('bg-green bg-red').addClass('bg-grey');

        $.getJSON('./dashboard/noc_fetch.php', { session: session })
            .done(function(data) {
                if (data.online) {
                    badge.html('<i class="fa fa-check-circle mr-1"></i> Online').removeClass('bg-grey bg-red').addClass('bg-green');
                    $("#ip-" + session).text(data.ip);
                    $("#identity-" + session).text(data.name);
                    $("#uptime-" + session).text(data.uptime);
                    $("#version-" + session).text(data.version);
                    $("#active-" + session).html('<span class="badge bg-blue" style="font-size:12px; padding:3px 8px; border-radius:3px;">' + data.active_users + ' active</span>');
                    
                    // CPU Load UI
                    $("#cpu-text-" + session).text(data.cpu + "%");
                    var cpuBar = $("#cpu-bar-" + session);
                    cpuBar.css('width', data.cpu + '%');
                    if (data.cpu > 85) {
                        cpuBar.css('background-color', '#dc3545'); // red
                    } else if (data.cpu > 50) {
                        cpuBar.css('background-color', '#ffc107'); // yellow
                    } else {
                        cpuBar.css('background-color', '#007bff'); // blue
                    }

                    // Memory UI
                    $("#mem-text-" + session).text(data.memory_percent + "%");
                    var memBar = $("#mem-bar-" + session);
                    memBar.css('width', data.memory_percent + '%');
                    if (data.memory_percent > 85) {
                        memBar.css('background-color', '#dc3545');
                    } else if (data.memory_percent > 60) {
                        memBar.css('background-color', '#ffc107');
                    } else {
                        memBar.css('background-color', '#28a745'); // green
                    }
                } else {
                    badge.html('<i class="fa fa-ban mr-1"></i> Offline').removeClass('bg-grey bg-green').addClass('bg-red');
                    $("#ip-" + session).text(data.ip || '-');
                    $("#identity-" + session).text('-');
                    $("#uptime-" + session).text('-');
                    $("#version-" + session).text('-');
                    $("#active-" + session).text('-');
                    $("#cpu-text-" + session).text('-');
                    $("#cpu-bar-" + session).css('width', '0%');
                    $("#mem-text-" + session).text('-');
                    $("#mem-bar-" + session).css('width', '0%');
                }
            })
            .fail(function() {
                badge.html('<i class="fa fa-warning mr-1"></i> Error').removeClass('bg-grey bg-green').addClass('bg-red');
            });
    }

    function refreshAll() {
        $("#icon-refresh-all").addClass("fa-spin");
        var completed = 0;
        if (sessions.length === 0) {
            $("#icon-refresh-all").removeClass("fa-spin");
            return;
        }
        sessions.forEach(function(sess) {
            fetchRouterStatus(sess);
        });
        // Remove spin after short delay
        setTimeout(function() {
            $("#icon-refresh-all").removeClass("fa-spin");
        }, 1000);
    }

    function resetCountdown() {
        var interval = parseInt($("#select-refresh-interval").val());
        if (interval > 0) {
            countdownValue = interval;
            $("#noc-countdown").text("Auto-refresh in " + countdownValue + "s").show();
        } else {
            $("#noc-countdown").hide();
        }
    }

    function startAutoRefresh() {
        stopAutoRefresh();
        var interval = parseInt($("#select-refresh-interval").val());
        if (interval > 0) {
            countdownValue = interval;
            $("#noc-countdown").text("Auto-refresh in " + countdownValue + "s").show();
            
            countdownTimer = setInterval(function() {
                countdownValue--;
                if (countdownValue <= 0) {
                    refreshAll();
                    countdownValue = interval;
                }
                $("#noc-countdown").text("Auto-refresh in " + countdownValue + "s");
            }, 1000);
        }
    }

    function stopAutoRefresh() {
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    // Initial load
    refreshAll();
    startAutoRefresh();

    // Event listeners
    $("#btn-refresh-all").click(function() {
        refreshAll();
        resetCountdown();
    });

    $("#select-refresh-interval").change(function() {
        startAutoRefresh();
    });
});
</script>
