<?php
require __DIR__.'/includes/bootstrap.php';
$mode=setting('auth_mode','remember');$error='';
if (isset($_GET['logout'])) {
 unset($_SESSION['driver_id']);
 setcookie('vl_driver','',['expires'=>time()-3600,'path'=>base_url(),'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
 header('Location: '.url());exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  if (isset($_POST['identify'])) {
   $id=(int)($_POST['driver_id']??0);$s=db()->prepare('SELECT * FROM drivers WHERE id=? AND active=1');$s->execute([$id]);$d=$s->fetch();
   if (!$d) throw new RuntimeException('Select a valid driver.');
   if ($mode!=='remember' && (!$d['secret_hash'] || !password_verify((string)($_POST['secret']??''),$d['secret_hash']))) throw new RuntimeException('Invalid credentials.');
   $_SESSION['driver_id']=$id;
   if ($mode==='remember') setcookie('vl_driver',(string)$id,['expires'=>time()+31536000,'path'=>base_url(),'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
   header('Location: '.url());exit;
  }
  if (isset($_POST['trip_action'])) {
   $driver=driver_id();$action=(string)$_POST['trip_action'];
   if (!$driver) throw new RuntimeException('Choose a driver first.');
   $check=db()->prepare('SELECT id FROM drivers WHERE id=? AND active=1');$check->execute([$driver]);if (!$check->fetch()) throw new RuntimeException('Driver unavailable.');
   if ($action==='finish') {
    $tripId=(int)($_POST['trip_id']??0);
    $km=filter_var($_POST['end_odometer']??'',FILTER_VALIDATE_INT);
    if ($tripId<1 || $km===false || $km<0) throw new RuntimeException('Enter a valid finish odometer.');
    $stmt=db()->prepare('UPDATE trips SET end_odometer=?,end_at=? WHERE id=? AND driver_id=? AND end_at IS NULL AND start_odometer<=?');
    $stmt->execute([$km,gmdate('Y-m-d H:i:s'),$tripId,$driver,$km]);
    if ($stmt->rowCount()!==1) throw new RuntimeException('Trip not found, already finished, or finish reading is below the start reading.');
   } elseif ($action==='start' || $action==='complete') {
    $vehicle=(int)($_POST['vehicle_id']??0);
    $start=filter_var($_POST['start_odometer']??'',FILTER_VALIDATE_INT);
    $end=$action==='complete'?filter_var($_POST['end_odometer']??'',FILTER_VALIDATE_INT):null;
    $description=trim((string)($_POST['trip_description']??''));
    if (mb_strlen($description,'UTF-8')>255) throw new RuntimeException('Trip description must be 255 characters or fewer.');
    if ($start===false || $start<0 || ($action==='complete' && ($end===false || $end<0 || $end<$start))) throw new RuntimeException('Enter valid odometer readings. Finish must be at least the start.');
    $check=db()->prepare('SELECT id FROM vehicles WHERE id=? AND active=1');$check->execute([$vehicle]);if (!$check->fetch()) throw new RuntimeException('Select a vehicle.');
    // Serialize writes for this vehicle to avoid two concurrent open trips.
    $lockName='vl_trip_'.substr(sha1((string)cfg()['db']['name']),0,12).'_'.$vehicle;
    $lock=db()->prepare('SELECT GET_LOCK(?, 10)');$lock->execute([$lockName]);
    if ((int)$lock->fetchColumn()!==1) throw new RuntimeException('Vehicle is busy. Please try again.');
    try {
     $open=db()->prepare('SELECT id FROM trips WHERE vehicle_id=? AND end_at IS NULL LIMIT 1');$open->execute([$vehicle]);
     if ($open->fetch()) throw new RuntimeException('This vehicle already has an unfinished trip. Finish it first.');
     $now=gmdate('Y-m-d H:i:s');
     $insert=db()->prepare('INSERT INTO trips (vehicle_id,driver_id,trip_description,start_odometer,start_at,end_odometer,end_at,created_at) VALUES (?,?,?,?,?,?,?,?)');
     $insert->execute([$vehicle,$driver,$description,$start,$now,$end,$action==='complete'?$now:null,$now]);
    } finally {
     $release=db()->prepare('SELECT RELEASE_LOCK(?)');$release->execute([$lockName]);
    }
   } else { throw new RuntimeException('Unknown trip action.'); }
   header('Location: '.url('history.php'));exit;
  }
 } catch(Throwable $e) { $error=$e->getMessage(); }
}
page('Vehicle trips');if ($error) echo '<p class="error">'.h($error).'</p>';
$drivers=db()->query('SELECT id,name FROM drivers WHERE active=1 ORDER BY name')->fetchAll();
$vehicles=db()->query('SELECT id,name FROM vehicles WHERE active=1 ORDER BY name')->fetchAll();
if (!$drivers || !$vehicles) { echo '<p>Ask the administrator to add vehicles and drivers first.</p>'; }
elseif (!driver_id()) {
 echo '<form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="identify" value="1"><label>Driver<select name="driver_id">';foreach ($drivers as $d) echo '<option value="'.(int)$d['id'].'">'.h($d['name']).'</option>';echo '</select></label>';
 if ($mode==='pin') echo '<label>PIN<input name="secret" type="password" inputmode="numeric" required></label>';
 if ($mode==='login') echo '<label>Password<input name="secret" type="password" required></label>';
 echo '<button>Continue</button></form>';
} else {
 $id=driver_id();$s=db()->prepare('SELECT name FROM drivers WHERE id=? AND active=1');$s->execute([$id]);$driver=$s->fetchColumn();
 if (!$driver) { unset($_SESSION['driver_id']);echo '<p>Driver no longer active. <a href="'.h(url('?logout=1')).'">Choose another</a>.</p>'; }
 else {
  echo '<p>Driver: <strong>'.h($driver).'</strong> · <a href="'.h(url('?logout=1')).'">Change driver</a></p>';
  $openTrips=db()->query('SELECT t.*,v.name vehicle,d.name driver FROM trips t JOIN vehicles v ON v.id=t.vehicle_id JOIN drivers d ON d.id=t.driver_id WHERE t.end_at IS NULL ORDER BY t.start_at DESC')->fetchAll();
  $openVehicles=[];
  foreach($openTrips as $t) {
   $openVehicles[(int)$t['vehicle_id']]=true;
   echo '<section class="open-trip"><h2>Trip in progress: '.h($t['vehicle']).'</h2><p>Started by '.h($t['driver']).' · '.number_format((int)$t['start_odometer']).' km</p>';
   if ($t['trip_description']!=='') echo '<p>'.h($t['trip_description']).'</p>';
   if ((int)$t['driver_id']===$id) {
    echo '<form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="trip_action" value="finish"><input type="hidden" name="trip_id" value="'.(int)$t['id'].'"><label>Finish odometer (km)<input name="end_odometer" type="number" inputmode="numeric" min="'.(int)$t['start_odometer'].'" step="1" required></label><button>Finish trip</button></form>';
   } else echo '<p>Only the driver who started this trip can finish it. Change driver to '.h($t['driver']).'.</p>';
   echo '</section>';
  }
  $available=array_values(array_filter($vehicles,static fn($v)=>!isset($openVehicles[(int)$v['id']])));
  if ($available) {
   echo '<h2>Record a trip</h2><form method="post" id="trip-form"><input type="hidden" name="csrf" value="'.h(csrf()).'">';
   echo '<fieldset class="reading-type"><legend>Entry method</legend><label><input type="radio" name="trip_action" value="start" checked> Start trip</label><label><input type="radio" name="trip_action" value="complete"> Enter completed trip</label></fieldset>';
   echo '<label>Vehicle<select name="vehicle_id" id="vehicle_id">';foreach($available as $v) echo '<option value="'.(int)$v['id'].'">'.h($v['name']).'</option>';echo '</select></label><p id="last-reading" class="last-reading" aria-live="polite"></p>';
   echo '<label>Start odometer (km)<input name="start_odometer" type="number" inputmode="numeric" min="0" step="1" required></label>';
   echo '<div id="finish-fields" hidden><label>Finish odometer (km)<input id="completed-end" name="end_odometer" type="number" inputmode="numeric" min="0" step="1" disabled></label></div>';
   echo '<label>Trip description (optional)<input name="trip_description" type="text" maxlength="255" placeholder="e.g. Work, shopping, school pickup"></label><button id="trip-submit">Start trip</button></form>';
   $lastByVehicle=[];
   // A completed trip is the best source; legacy readings are retained as fallback.
   $q=db()->query('SELECT vehicle_id,end_odometer,end_at FROM trips WHERE end_at IS NOT NULL ORDER BY id DESC');
   foreach($q as $r) if (!isset($lastByVehicle[(int)$r['vehicle_id']])) $lastByVehicle[(int)$r['vehicle_id']]=['km'=>(int)$r['end_odometer'],'date'=>$r['end_at']];
   $q=db()->query('SELECT r.vehicle_id,r.odometer,r.recorded_at FROM readings r INNER JOIN (SELECT vehicle_id,MAX(id) last_id FROM readings GROUP BY vehicle_id) last ON last.last_id=r.id');
   foreach($q as $r) if (!isset($lastByVehicle[(int)$r['vehicle_id']])) $lastByVehicle[(int)$r['vehicle_id']]=['km'=>(int)$r['odometer'],'date'=>$r['recorded_at']];
   echo '<script>const lastReadings='.json_encode($lastByVehicle,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';const vehicle=document.getElementById("vehicle_id"),last=document.getElementById("last-reading");function updateLast(){const item=lastReadings[vehicle.value];last.textContent=item?"Last recorded: "+item.km.toLocaleString()+" km ("+item.date+" UTC)":"No previous reading for this vehicle";}vehicle.addEventListener("change",updateLast);updateLast();const modes=document.querySelectorAll("input[name=trip_action]"),finish=document.getElementById("finish-fields"),end=document.getElementById("completed-end"),submit=document.getElementById("trip-submit");function updateMode(){const completed=document.querySelector("input[name=trip_action]:checked").value==="complete";finish.hidden=!completed;end.disabled=!completed;end.required=completed;submit.textContent=completed?"Save completed trip":"Start trip";}modes.forEach(el=>el.addEventListener("change",updateMode));updateMode();</script>';
  } else echo '<p>All active vehicles currently have unfinished trips. Finish one before starting another.</p>';
 }
}
end_page();
