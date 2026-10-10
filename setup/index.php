<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';
if (is_file(APP_ROOT.'/dontlookhere/config.php')) { header('Location: '.url());exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 check_csrf();
 try {
  $host=trim((string)($_POST['host']??''));$port=(int)($_POST['port']??3306);$name=trim((string)($_POST['name']??''));$user=trim((string)($_POST['user']??''));$pass=(string)($_POST['password']??'');$adminUser=trim((string)($_POST['admin_user']??''));$adminPass=(string)($_POST['admin_password']??'');
  if (!$host || !$name || !$user || !$adminUser || strlen($adminPass)<12 || $port<1 || $port>65535) throw new RuntimeException('Fill all required fields. Admin password must be at least 12 characters.');
  $pdo=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  foreach (glob(APP_ROOT.'/migrations/*.sql') as $file) { $sql=file_get_contents($file); foreach (array_filter(array_map('trim',explode(';',$sql))) as $statement) $pdo->exec($statement); }
  $q=$pdo->prepare('INSERT INTO admins (username,password_hash) VALUES (?,?)');$q->execute([$adminUser,password_hash($adminPass,PASSWORD_DEFAULT)]);
  $q=$pdo->prepare('INSERT INTO settings (name,value) VALUES (?,?)');$q->execute(['auth_mode','remember']);
  $config='<?php'."\nreturn ".var_export(['db'=>['host'=>$host,'port'=>$port,'name'=>$name,'user'=>$user,'password'=>$pass]],true).";\n";
  $target=APP_ROOT.'/dontlookhere/config.php';
  if (file_put_contents($target,$config,LOCK_EX)===false) throw new RuntimeException('Unable to save config.php. Check folder permissions.');
  @chmod($target,0600);
  header('Location: '.url('admin/'));exit;
 } catch(Throwable $e) { $error='Setup failed: '.$e->getMessage(); }
}
page('First-run setup');
echo '<p>Requires PHP 8.1+, PDO MySQL, and an existing empty MySQL database. Use HTTPS.</p>';
if ($error) echo '<p class="error">'.h($error).'</p>';
echo '<form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><label>Database host<input name="host" value="localhost" required></label><label>Database port<input name="port" type="number" value="3306" required></label><label>Database name<input name="name" required></label><label>Database user<input name="user" required></label><label>Database password<input type="password" name="password"></label><label>Admin username<input name="admin_user" required></label><label>Admin password (12+ characters)<input type="password" name="admin_password" minlength="12" required></label><button>Install Vehicle Log</button></form>';
end_page();
