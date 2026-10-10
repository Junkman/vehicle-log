<?php
require __DIR__.'/includes/bootstrap.php';
$mode=setting('auth_mode','remember');$error='';
if (isset($_GET['logout'])) { unset($_SESSION['driver_id']);setcookie('vl_driver','',time()-3600,base_url(),'',['secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);header('Location: '.url());exit; }
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
  if (isset($_POST['save'])) {
   $driver=driver_id();$vehicle=(int)($_POST['vehicle_id']??0);$km=filter_var($_POST['odometer']??'',FILTER_VALIDATE_INT);$type=(string)($_POST['reading_type']??'');
   if (!$driver || $km===false || $km<0 || !in_array($type,['start','end'],true)) throw new RuntimeException('Invalid reading.');
   $v=db()->prepare('SELECT id FROM vehicles WHERE id=? AND active=1');$v->execute([$vehicle]);if (!$v->fetch()) throw new RuntimeException('Select a vehicle.');
   $d=db()->prepare('SELECT id FROM drivers WHERE id=? AND active=1');$d->execute([$driver]);if (!$d->fetch()) throw new RuntimeException('Driver unavailable.');
   $s=db()->prepare('INSERT INTO readings (vehicle_id,driver_id,reading_type,odometer,recorded_at,created_at) VALUES (?,?,?,?,?,?)');$now=gmdate('Y-m-d H:i:s');$s->execute([$vehicle,$driver,$type,$km,$now,$now]);
   header('Location: '.url('history.php'));exit;
  }
 } catch(Throwable $e) { $error=$e->getMessage(); }
}
page('Record a reading');if ($error) echo '<p class="error">'.h($error).'</p>';
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
 else { echo '<p>Driver: <strong>'.h($driver).'</strong> · <a href="'.h(url('?logout=1')).'">Change driver</a></p><form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="save" value="1"><label>Vehicle<select name="vehicle_id">';foreach ($vehicles as $v) echo '<option value="'.(int)$v['id'].'">'.h($v['name']).'</option>';echo '</select></label><label>Reading type<select name="reading_type"><option value="start">Start</option><option value="end">End</option></select></label><label>Odometer (km)<input name="odometer" type="number" min="0" step="1" required></label><button>Save reading</button></form>'; }
}
end_page();
