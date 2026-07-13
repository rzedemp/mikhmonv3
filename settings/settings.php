<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// hide all error
error_reporting(0);

if (!isset($_SESSION["mikhmon"])) {
  header("Location:../admin.php?id=login");
} else {

  if ($id == "settings" && explode("-",$router)[0] == "new") {
    $data = '$data';
    $f = fopen('./include/config.php', 'a');
    fwrite($f, "\n'$'data['".$router."'] = array ('1'=>'".$router."!','".$router."@|@','".$router."#|#','".$router."%','".$router."^','".$router."&Rp','".$router."*10','".$router."(1','".$router.")','".$router."=10','".$router."@!@disable');");
    fclose($f);
    $search = "'$'data";
    $replace = (string)"$data";
    $file = file("./include/config.php");
    $content = file_get_contents("./include/config.php");
    $newcontent = str_replace((string)$search, (string)$replace, "$content");
    file_put_contents("./include/config.php", "$newcontent");
    echo "<script>window.location='./admin.php?id=settings&session=" . $router . "'</script>";
  }

  if (isset($_POST['save'])) {
    csrf_verify();

    $siphost = (preg_replace('/\s+/', '', $_POST['ipmik']));
    $suserhost = ($_POST['usermik']);
    $spasswdhost = encrypt($_POST['passmik']);
    $shotspotname = str_replace("'","",$_POST['hotspotname']);
    $sdnsname = ($_POST['dnsname']);
    $scurrency = ($_POST['currency']);
    $sreload = ($_POST['areload']);
    if ($sreload < 10) {
      $sreload = 10;
    } else {
      $sreload = $sreload;
    }
    $siface = ($_POST['iface']);
    $sinfolp = isset($_POST['infolp']) ? implode(unpack("H*", $_POST['infolp'])) : $infolp;
    $sidleto = ($_POST['idleto']);

    $sesname = (preg_replace('/\s+/', '-', $_POST['sessname']));
    $slivereport = ($_POST['livereport']);
    
    // REST API fields
    $sapi_mode = isset($_POST['api_mode']) ? $_POST['api_mode'] : 'binary';
    $srest_port = isset($_POST['rest_port']) ? (int)$_POST['rest_port'] : ($sapi_mode === 'rest' ? 443 : 8728);
    $srest_ssl = isset($_POST['rest_ssl']) ? (int)$_POST['rest_ssl'] : ($srest_port === 443 ? 1 : 0);

    // Save configuration by replacing the line in config.php
    $configFile = "./include/config.php";
    if (file_exists($configFile)) {
      $lines = file($configFile);
      $updated = false;
      foreach ($lines as $key => $line) {
        if (strpos($line, "\$data['" . $session . "']") !== false || strpos($line, "\$data[\"" . $session . "\"]") !== false) {
          $lines[$key] = "\$data['" . $sesname . "'] = array ('1'=>'" . $sesname . "!" . $siphost . "','" . $sesname . "@|@" . $suserhost . "','" . $sesname . "#|#" . $spasswdhost . "','" . $sesname . "%" . $shotspotname . "','" . $sesname . "^" . $sdnsname . "','" . $sesname . "&" . $scurrency . "','" . $sesname . "*" . $sreload . "','" . $sesname . "(" . $siface . "','" . $sesname . ")" . $sinfolp . "','" . $sesname . "=" . $sidleto . "','" . $sesname . "@|@" . $slivereport . "','" . $sesname . "~" . $sapi_mode . "','" . $sesname . "{" . $srest_port . "','" . $sesname . "}" . $srest_ssl . "');\n";
          $updated = true;
          break;
        }
      }
      if ($updated) {
        file_put_contents($configFile, implode("", $lines));
      }
    }

    write_audit_log($_SESSION['mikhmon'] ?? 'admin', 'UPDATE_SESSION_SETTINGS', 'Updated router settings for session: ' . $sesname);
    $_SESSION["connect"] = "";
    echo "<script>window.location='./admin.php?id=settings&session=" . $sesname . "'</script>";
  }
  if ($currency == "") {
    echo "<script>window.location='./admin.php?id=settings&session=" . $session . "'</script>";
  }
}
?>
<script>
  function PassMk(){
    var x = document.getElementById('passmk');
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
    }}
    function PassAdm(){
    var x = document.getElementById('passadm');
    if (x.type === 'password') {
    x.type = 'text';
    } else {
    x.type = 'password';
  }}
  function toggleApiFields() {
    var mode = document.getElementById("api_mode").value;
    var rowPort = document.getElementById("row_rest_port");
    var rowSsl = document.getElementById("row_rest_ssl");
    if (mode === "rest") {
      rowPort.style.display = "";
      rowSsl.style.display = "";
    } else {
      rowPort.style.display = "none";
      rowSsl.style.display = "none";
    }
  }
</script>

<form autocomplete="off" method="post" action="" name="settings">  
<?= csrf_field(); ?>
<div class="row">
	<div class="col-12">
  		<div class="card" >
  			<div class="card-header">
  				<h3 class="card-title"><i class="fa fa-gear"></i> <?= $_session_settings ?> &nbsp; | &nbsp;&nbsp;<i onclick="location.reload();" class="fa fa-refresh pointer " title="Reload data"></i></h3>
  			</div>
        <div class="card-body">
    	   <div class="row">
			     <div class="col-6">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"><?= $_session ?></h3>
                </div>
                <div class="card-body">
                  <table class="table">
                    <tr>
                      <td><?= $_session_name ?></td>
                      <td><input class="form-control" id="sessname" type="text" name="sessname" title="Session Name" value="<?php if (explode("-",$session)[0] == "new") {
                                                                                                                               echo "";
                                                                                                                             } else {
                                                                                                                               echo $session;
                                                                                                                             } ?>" required="1"/></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-12">
				      <div class="card">
        	     <div class="card-header">
            	   <h3 class="card-title">MikroTik <?= $_SESSION["connect"]; ?></h3>
        	     </div>
        	     <div class="card-body">
				<table class="table table-sm">
					<tr>
	  					<td class="align-middle">IP MikroTik </td><td><input class="form-control" type="text" size="15" name="ipmik" title="IP MikroTik / IP Cloud MikroTik" value="<?= $iphost; ?>" required="1"/></td>
					</tr>
					<tr>
						<td class="align-middle">Username  </td><td><input class="form-control" id="usermk" type="text" size="10" name="usermik" title="User MikroTik" value="<?= $userhost; ?>" required="1"/></td>
					</tr>
					<tr>
						<td class="align-middle">Password  </td><td>
							<div class="input-group">
								<div class="input-group-11 col-box-10">
        						<input class="group-item group-item-l" id="passmk" type="password" name="passmik" title="Password MikroTik" value="<?= decrypt($passwdhost); ?>" required="1"/>
        						</div>
            					<div class="input-group-1 col-box-2">
            						<div class="group-item group-item-r pd-2p5 text-center align-middle">
                						<input title="Show/Hide Password" type="checkbox" onclick="PassMk()">
            						</div>
            					</div>
    						</div>
						</td>
					</tr>
					<tr>
						<td class="align-middle">API Mode</td>
						<td>
							<select class="form-control" name="api_mode" id="api_mode" onchange="toggleApiFields()">
								<option value="binary" <?= $api_mode === 'binary' ? 'selected' : '' ?>>Binary API (Default)</option>
								<option value="rest" <?= $api_mode === 'rest' ? 'selected' : '' ?>>REST API (RouterOS 7.x+)</option>
							</select>
						</td>
					</tr>
					<tr id="row_rest_port" style="<?= $api_mode === 'rest' ? '' : 'display:none;' ?>">
						<td class="align-middle">REST Port</td>
						<td><input class="form-control" type="number" name="rest_port" value="<?= $rest_port ?>" /></td>
					</tr>
					<tr id="row_rest_ssl" style="<?= $api_mode === 'rest' ? '' : 'display:none;' ?>">
						<td class="align-middle">Use SSL</td>
						<td>
							<select class="form-control" name="rest_ssl">
								<option value="0" <?= $rest_ssl === 0 ? 'selected' : '' ?>>No (HTTP)</option>
								<option value="1" <?= $rest_ssl === 1 ? 'selected' : '' ?>>Yes (HTTPS)</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">
								<div class="input-group-4">
									<input class="group-item group-item-md" type="submit" style="cursor: pointer;" name="save" value="Save"/>
								</div>
								<div class="input-group-4">	
                  <span class="connect pointer group-item group-item-md pd-2p5 text-center align-middle" id="<?= $session; ?>&c=settings">Connect</span>
								</div>
								<div class="input-group-3">	
                  <span class="pointer group-item group-item-md pd-2p5 text-center align-middle" id="ping_test">Ping</span>
              	</div>
              	<div class="input-group-1">	
									<div style="cursor: pointer;" class="group-item group-item-r pd-2p5 text-center" onclick="location.reload();" title="Reload Data"><i class="fa fa-refresh"></i></div>
								</div>
            		</div>	
    					</td>
    				</tr>
				</table>
			</div>
    </div>  	
    <div id="ping">
    </div>	
	</div>
</div>
<div class="col-6">
<div class="col-12">
	<div class="card">
        <div class="card-header">
            <h3 class="card-title">Mikhmon Data</h3>
        </div>
    <div class="card-body">    
	<table class="table table-sm">
	<tr>
	<td class="align-middle"><?= $_hotspot_name ?>  </td><td><input class="form-control" type="text" size="15" maxlength="50" name="hotspotname" title="Hotspot Name" value="<?= $hotspotname; ?>" required="1"/></td>
	</tr>
	<tr>
	<td class="align-middle"><?= $_dns_name ?>  </td><td><input class="form-control" type="text" size="15" maxlength="500" name="dnsname" title="DNS Name [IP->Hotspot->Server Profiles->DNS Name]" value="<?= $dnsname; ?>" required="1"/></td>
	</tr>
	<tr>
	<td class="align-middle"><?= $_currency ?>  </td><td><input class="form-control" type="text" size="3" maxlength="4" name="currency" title="currency" value="<?= $currency; ?>" required="1"/></td>
	</tr>
	<tr> 
	<td class="align-middle"><?= $_auto_reload ?></td><td>
	<div class="input-group">
		<div class="input-group-10">
        	<input class="group-item group-item-l" type="number" min="10" max="3600" name="areload" title="Auto Reload in sec [min 10]" value="<?= $areload; ?>" required="1"/>
    	</div>
            <div class="input-group-2">
                <span class="group-item group-item-r pd-2p5 text-center align-middle"><?= $_sec ?></span>
            </div>
        </div>
	</td>
  </tr>
  <tr>
  <td class="align-middle"><?= $_idle_timeout ?></td>
  <td>
  <div class="input-group">
  <div class="input-group-9">
      <select class="group-item group-item-l" name="idleto" required="1">
          <option value="<?= $idleto; ?>"><?= $idleto; ?></option>
				  <option value="5">5</option>
          <option value="10">10</option>
          <option value="30">30</option>
          <option value="60">60</option>
          <option value="disable">disable</option>
      </select>
  </div>
  <div class="input-group-3">
                <span class="group-item group-item-r pd-3p5 text-center align-middle"><?= $_min ?></span>
            </div>
        </div>
    </td>
	</tr>
	<tr>
	<td class="align-middle"><?= $_traffic_interface ?></td><td><input class="form-control" type="number" min="1" max="99" name="iface" title="Traffic Interface" value="<?= $iface; ?>" required="1"/></td>
	</tr>
  <?php if (empty($livereport)) {
  } else { ?>
  <tr>
    <td><?= $_live_report ?></td>
    <td>
      <select class="form-control" name="livereport" >
          <option value="<?= $livereport; ?>"><?= ucfirst($livereport); ?></option>
				  <option value="enable">Enable</option>
				  <option value="disable">Disable</option>
		  </select>
    </td>
  </tr>
  <?php 
} ?>
</table>
</div>
</div>
</div>
</div>
</div>
</form>
<script type="text/javascript">
$(document).ready(function() {
  var hname = window.location.hostname;
  var dom = hname.split('.')[1] + '.' + hname.split('.')[2];
  var domArray = ["", "xban.xyz", "logam.id", "minis.id"];
  var a = domArray.indexOf(hname);
  var b = domArray.indexOf(dom);
  var sessX = document.getElementById("sessname").value;
  
  if (a > 0 || b > 0) {
    function pingTest(session) {
      document.getElementById("ping").innerHTML = '<div id="pingX" class="col-12"><div class="card"><div class="card-header"><h3 class="card-title">Ping Test </h3>\t</div>\t<div class="card-body"><h3>Fitur tidak support.</h3><span class="pointer btn" onclick="closeX()"><i class="fa fa-close text-red "></i> Close</span></div></div></div>';
    }
    document.getElementById("ping_test").onclick = function() {
      pingTest(sessX);
    };
  } else {
    function pingTest(session) {
      $("#ping").load("./status/ping-test.php?ping&session=" + session);
    }
    document.getElementById("ping_test").onclick = function() {
      pingTest(sessX);
    };
  }
});

function closeX() {
  $("#pingX").hide();
}

// Brand protection
if (!(document.getElementById("brand")) || document.getElementById("brand").innerHTML != "MIKHMON" || document.getElementById("brand").style.display == "none") {
  document.getElementsByTagName("body")[0].innerHTML = '<center><h1 style="margin-top:30%;">:(<br>You destroy MIKHMON</h1></center>';
} else {
  document.getElementById("brand").innerHTML = "MIKHMON";
}

// Session name validation
var sesname = document.settings.sessname;
function chksname() {
  if (sesname.value == "mikhmon" || sesname.value == "MIKHMON" || sesname.value == "Mikhmon") {
    alert("You cannot use " + sesname.value + " as a session name.");
    sesname.value = "";
    window.location.reload();
  }
}
sesname.onkeyup = chksname;
sesname.onchange = chksname;
</script>


</script>





