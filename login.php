<?php require __DIR__.'/../includes/functions.php';
if(me()){header('Location: ../'.area().'/dashboard.php');exit;}
$R=['student'=>['Student','Classes, grades and enrollment'],'professor'=>['Professor','Classroom, attendance and gradebook'],'dean'=>['Dean','Approvals and academic oversight'],'registrar'=>['Registrar','Records, enrollment and final grades'],'facilities'=>['Facilities','Rooms and reservations'],'admin'=>['Administrator','Accounts and audit logs']];
$role=$_GET['role']??'';$role=isset($R[$role])?$role:'';$err='';
if($role&&$_SERVER['REQUEST_METHOD']==='POST'){check_csrf();
 $un=trim($_POST['username']??'');$ak=substr($un.' @'.($_SERVER['REMOTE_ADDR']??''),0,250);$x=rows('SELECT * FROM users WHERE username=?',[$un])[0]??null;
 if(val("SELECT COUNT(*) FROM audit_logs WHERE action='login_failed' AND detail=? AND created_at>NOW()-INTERVAL 10 MINUTE",[$ak])>=5)$err='Too many sign-in attempts. Wait 10 minutes, or ask the Registrar or Admin for help.';
 elseif(!$x||!password_verify($_POST['password']??'',$x['password_hash'])){$err='Wrong username or password. Check both and try again.';audit('login_failed','session',$ak);}
 elseif(($x['status']??'active')!=='active')$err='This account is inactive. Ask the Registrar or Admin to reactivate it.';
 elseif($x['role']!==$role)$err='This account is not a '.$R[$role][0].' account. Go back and choose your own role.';
 else{session_regenerate_id(true);$_SESSION['u']=['id'=>$x['id'],'full_name'=>$x['full_name'],'role'=>$x['role']];$_SESSION['last_activity']=time();audit('login','session');header('Location: ../'.area().'/dashboard.php');exit;}}
if(isset($_GET['expired']))$err='Your session expired for security. Please sign in again; unsaved changes may need to be re-entered.';
head('GRC Campus');
if(!$role){echo '<p class="sub">Choose how you will sign in.</p><div class="roles">';
 foreach($R as $k=>$v)echo '<a class="role" href="login.php?role='.$k.'"><b>'.e($v[0]).'</b><small>'.e($v[1]).'</small></a>';
 echo '</div>';}
else{echo '<p class="sub">Signing in as <b>'.e($R[$role][0]).'</b>. <a href="login.php">Change role</a></p><form method="post" action="login.php?role='.$role.'" class="card form one"><input type="hidden" name="t" value="'.csrf().'">'.($err?'<p class="note">'.e($err).'</p>':'').'<label>Username<input name="username" required autofocus autocomplete="username"></label><label>Password<input name="password" type="password" required autocomplete="current-password"></label><button class="btn">Sign in</button><p class="muted">Accounts are created by the Registrar or System Admin. If you forgot your password, ask them to reset it.</p></form>';}
foot();
