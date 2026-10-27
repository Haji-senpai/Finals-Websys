<?php
require __DIR__.'/../config/db.php';
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function lbl($n){return e(ucwords(str_replace('_',' ',preg_replace('/_id$/','',$n))));}
function me(){return $_SESSION['u']??null;}
function area(){return in_array(me()['role']??'',['student','professor'])?'user':'admin';}
function need(){if(!me()){header('Location: ../auth/login.php');exit;} $now=time(); if(isset($_SESSION['last_activity']) && $now-(int)$_SESSION['last_activity']>7200){$_SESSION=[];session_destroy();header('Location: ../auth/login.php?expired=1');exit;} $_SESSION['last_activity']=$now; $d=basename(dirname($_SERVER['SCRIPT_NAME']));if($d!==area()){header('Location: ../'.area().'/'.basename($_SERVER['SCRIPT_NAME']));exit;}}
function csrf(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function deny($m,$h='Notice'){http_response_code(403);head($h);echo '<p class="note">'.e($m).'</p><p><a class="btn" href="'.(me()?'dashboard.php':'../auth/login.php').'">'.(me()?'Back to dashboard':'Go to sign in').'</a></p>';foot();exit;}
function check_csrf(){if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals(csrf(),(string)($_POST['t']??'')))deny('This page was open too long, so nothing was saved. Sign in again and retry.','Session expired');}
function rows($q,$p=[]){global $db;$s=$db->prepare($q);$s->execute($p);return $s->fetchAll();}
function val($q,$p=[]){global $db;$s=$db->prepare($q);$s->execute($p);return $s->fetchColumn();}
function audit($a,$t,$d=''){global $db;$db->prepare('INSERT INTO audit_logs(user_id,action,target,detail) VALUES(?,?,?,?)')->execute([me()['id']??null,$a,$t,substr($d,0,250)]);}
function btn($a,$f,$l,$c='',$k='link'){return '<form method="post" class="inline"'.($c?' data-confirm="'.e($c).'"':'').'><input type="hidden" name="t" value="'.csrf().'"><input type="hidden" name="a" value="'.$a.'">'.$f.'<button class="'.$k.'">'.$l.'</button></form>';}
function mine($sec){return val('SELECT COUNT(*) FROM sections WHERE id=? AND professor_id=?',[(int)$sec,me()['id']])>0;}

/* CRUD matrix: P(student, professor, dean, registrar, facilities, admin). C/R/U/D letters, "*" = own records only. */
function P(...$a){return array_combine(['student','professor','dean','registrar','facilities','admin'],$a);}
function perm($k,$a){global $M;foreach(explode(' ',$M[$k]['p'][me()['role']]??'') as $t)if($t!==''&&$t[0]===$a)return strlen($t)>1?'own':'all';return false;}
function sc($k,$a='R'){global $M;if(perm($k,$a)!=='own')return '1=1';$o=$M[$k]['own']??'0=1';if(is_array($o))$o=$o[me()['role']]??'0=1';return '('.str_replace(':uid',(int)me()['id'],$o).')';}

/* Module config: f = fields (name:type; "~" = read-only; types text,number,date,time,area,pass,enum.a|b,fk.table.label[.role]),
   own = SQL for "own records", ow = auto-filled owner column, ap = field => roles allowed to write it, lock = lock column. */
$enr="section_id IN (SELECT section_id FROM enrollments WHERE student_id=:uid AND status='approved')";
$fs=['section_id'=>['w'=>['professor'=>"professor_id=:uid AND semester_id=(SELECT id FROM semesters WHERE is_active=1)"]]];$fst=$fs+['student_id'=>['w'=>['professor'=>"id IN (SELECT student_id FROM enrollments WHERE status='approved' AND section_id IN (SELECT id FROM sections WHERE professor_id=:uid))"]]];
$M=[
'users'=>['title'=>'User Accounts','f'=>'username,full_name,email,student_no,program_id:fk.programs.code,role:enum.student|professor|dean|registrar|facilities|admin,status:enum.active|inactive,password:pass','p'=>P('R* U*','R* U*','R* U*','C R U','R* U*','C R U D'),'own'=>'id=:uid','ap'=>['username'=>'registrar,admin','full_name'=>'registrar,admin','status'=>'registrar,admin','role'=>'registrar,admin','student_no'=>'registrar,admin','program_id'=>'registrar,admin']],
'subjects'=>['title'=>'Subjects and Curriculum','f'=>'code,title,units:number,prereq_id:fk.subjects.code','p'=>P('R','R','C R U D','R U','','R')],
'sections'=>['title'=>'Sections and Faculty Load','f'=>'subject_id:fk.subjects.code,semester_id:fk.semesters.name,code,professor_id:fk.users.full_name.professor,slots:number','p'=>P('R','R','C R U D','R U','R','R')],
'schedules'=>['title'=>'Class Schedule','f'=>'section_id:fk.sections.code,room_id:fk.rooms.name,day:enum.Mon|Tue|Wed|Thu|Fri|Sat,start_time:time,end_time:time','p'=>P('R*','R*','C R U D','R','R U','R'),'own'=>['student'=>$enr,'professor'=>'section_id IN (SELECT id FROM sections WHERE professor_id=:uid)']],
'rooms'=>['title'=>'Rooms and Facilities','f'=>'name,type,capacity:number','p'=>P('R','R','R','R','C R U D','R')],
'reservations'=>['title'=>'Room Reservations','f'=>'room_id:fk.rooms.name,date:date,start_time:time,end_time:time,purpose,status:enum.pending|approved|rejected,~user_id:fk.users.full_name','p'=>P('','C R* U* D*','C R U D','','R U','R'),'own'=>'user_id=:uid','ow'=>'user_id','ap'=>['room_id'=>'professor,dean','date'=>'professor,dean','start_time'=>'professor,dean','end_time'=>'professor,dean','purpose'=>'professor,dean','status'=>'dean,facilities']],
'posts'=>['title'=>'Virtual Classroom','f'=>'section_id:fk.sections.code,kind:enum.post|material|assignment,title,body:area,due_date:date,~created_at','p'=>P('R*','C R* U* D*','R','','','R'),'own'=>['student'=>$enr,'professor'=>'author_id=:uid'],'ow'=>'author_id','fk'=>$fs],
'submissions'=>['title'=>'Submissions','f'=>'post_id:fk.posts.title,content:area,feedback:area,~student_id:fk.users.full_name,~submitted_at','p'=>P('C R U*','R* U*','R','','','R'),'own'=>['student'=>'student_id=:uid','professor'=>'post_id IN (SELECT id FROM posts WHERE author_id=:uid)'],'ow'=>'student_id','ap'=>['post_id'=>'student','content'=>'student','feedback'=>'professor'],'fk'=>['post_id'=>['w'=>['student'=>"kind='assignment' AND section_id IN (SELECT section_id FROM enrollments WHERE student_id=:uid AND status='approved')"],'l'=>"CONCAT((SELECT code FROM sections s WHERE s.id=section_id),': ',title,IF(due_date IS NULL,'',CONCAT(' (due ',due_date,')')))"]]],
'attendance'=>['title'=>'Attendance','f'=>'section_id:fk.sections.code,student_id:fk.users.full_name.student,session_date:date,status:enum.present|late|absent','p'=>P('R*','C R U*','R','R','','R'),'own'=>['student'=>'student_id=:uid','professor'=>'section_id IN (SELECT id FROM sections WHERE professor_id=:uid)'],'fk'=>$fst],
'grades'=>['title'=>'Gradebook','f'=>'section_id:fk.sections.code,student_id:fk.users.full_name.student,grade:number,status:enum.draft|submitted|approved|final,locked:enum.0|1','p'=>P('R*','C R U*','R U','R U','','R'),'own'=>['student'=>"student_id=:uid AND status='final'",'professor'=>'section_id IN (SELECT id FROM sections WHERE professor_id=:uid)'],'ap'=>['section_id'=>'professor','student_id'=>'professor','grade'=>'professor','status'=>'professor,registrar','locked'=>'dean'],'lock'=>'locked','fk'=>$fst],
'announcements'=>['title'=>'Announcements','f'=>'scope:enum.class|college|campus,section_id:fk.sections.code,title,body:area,~created_at','p'=>P('R*','C R U* D*','C R U D','C R U D','C R U D','C R U D'),'own'=>['student'=>"scope<>'class' OR section_id IN (SELECT section_id FROM enrollments WHERE student_id=:uid)",'professor'=>'author_id=:uid'],'ow'=>'author_id'],
'programs'=>['title'=>'Programs and Departments','f'=>'code,name,department','p'=>P('R','R','C R U D','C R U D','','C R U D')],
'events'=>['title'=>'Academic Calendar','f'=>'title,kind:enum.enrollment|exam|holiday|deadline|activity,start_date:date,end_date:date,details:area','p'=>P('R','R','C R U D','C R U D','R','C R U D')],
'fees'=>['title'=>'Billing and Fees','f'=>'student_id:fk.users.full_name.student,description:enum.Tuition|Midterm|Finals|Registration|Miscellaneous|Laboratory|Other,amount:number,paid:number,due_date:date,status:enum.unpaid|partial|paid','p'=>P('R*','','R','C R U D','','C R U D'),'own'=>'student_id=:uid'],
'requests'=>['title'=>'Document Requests','f'=>'document:enum.Transcript of Records|Certificate of Enrollment|Certificate of Grades|Good Moral Certificate,purpose,status:enum.pending|processing|ready|released,~student_id:fk.users.full_name,~created_at','p'=>P('C R* U*','','R','R U','','R'),'own'=>'student_id=:uid','ow'=>'student_id','ap'=>['document'=>'student','purpose'=>'student','status'=>'registrar']],
'semesters'=>['title'=>'Semester Setup','f'=>'name,is_active:enum.0|1','p'=>P('','','','','','C R U D')],
'roster'=>['title'=>'Class Roster','t'=>'enrollments','f'=>'~section_id:fk.sections.code,~student_id:fk.users.full_name.student,~status','p'=>P('','R*','R','R','',''),'own'=>['professor'=>'section_id IN (SELECT id FROM sections WHERE professor_id=:uid)']],
'audit'=>['title'=>'Audit Logs','t'=>'audit_logs','f'=>'~user_id:fk.users.full_name,~action,~target,~detail,~created_at','p'=>P('','','','','','R')],
];

/* Business rules (validation hooks run before every create/update). */
function chk_users(&$d,$id){$r=me()['role'];
 if($r==='admin' && $id===me()['id'] && ((isset($d['role'])&&$d['role']!=='admin') || (isset($d['status'])&&$d['status']!=='active')))return 'You cannot change your own administrator role or deactivate your own account.';
 if(!$id&&empty($d['password_hash']))return 'A password is required for new accounts.';
 if($id&&$id==me()['id']&&($d['status']??'active')==='inactive')return 'An account cannot deactivate itself. Ask another admin to do it.';
 if($r==='registrar'){$lim=['admin','dean','registrar','facilities'];if($id==me()['id']){if(($d['role']??'registrar')!=='registrar')return 'Only an Admin can change this role.';}elseif(in_array($d['role']??'',$lim)||($id&&in_array(val('SELECT role FROM users WHERE id=?',[$id]),$lim)))return 'Registrars manage student and professor accounts only.';}
 return '';}
function chk_schedules($d,$id){
 if(empty($d['section_id'])||empty($d['room_id'])||empty($d['day'])||empty($d['start_time'])||empty($d['end_time']))return 'Choose a section, room, day, and start/end time.';
 if($d['end_time']<=$d['start_time'])return 'End time must be after start time.';
 $o='FROM schedules x JOIN sections a ON a.id=x.section_id WHERE x.id<>? AND x.day=? AND x.start_time<? AND ?<x.end_time AND a.semester_id=(SELECT semester_id FROM sections WHERE id=?) AND ';
 $p=[(int)$id,$d['day'],$d['end_time'],$d['start_time'],(int)$d['section_id']];
 if(val("SELECT COUNT(*) $o x.room_id=?",[...$p,$d['room_id']]))return 'Unavailable: that room/time is already taken. Choose another time.';
 if(val("SELECT COUNT(*) $o a.professor_id IS NOT NULL AND a.professor_id=(SELECT professor_id FROM sections WHERE id=?)",[...$p,$d['section_id']]))return 'Unavailable: this faculty member already has a class at that time.';
 if(val("SELECT COUNT(*) $o x.section_id=?",[...$p,(int)$d['section_id']]))return 'Unavailable: this section already has a class at that time.';
 return '';}
function chk_reservations(&$d,$id){global $db;
 if(me()['role']==='professor')$d['status']='pending';
 $x=array_merge($id?$db->query('SELECT * FROM reservations WHERE id='.(int)$id)->fetch():[],$d);
 if($x['end_time']<=$x['start_time'])return 'End time must be after start time.';
 if($x['status']==='approved'&&val("SELECT COUNT(*) FROM reservations WHERE id<>? AND room_id=? AND date=? AND status='approved' AND start_time<? AND ?<end_time",[(int)$id,$x['room_id'],$x['date'],$x['end_time'],$x['start_time']]))return 'That room is already reserved for this time.';
 return '';}
function chk_posts($d){return me()['role']==='professor'&&!mine($d['section_id'])?'You can only post to your own sections.':'';}
function chk_submissions($d){return me()['role']==='student'&&!val("SELECT COUNT(*) FROM posts p JOIN enrollments e ON e.section_id=p.section_id AND e.student_id=? WHERE p.id=? AND p.kind='assignment'",[me()['id'],$d['post_id']])?'Choose an assignment from one of your classes.':'';}
function chk_attendance($d){if(me()['role']!=='professor')return '';if(!mine($d['section_id']))return 'Attendance can be recorded for your own sections only.';return val("SELECT COUNT(*) FROM enrollments WHERE student_id=? AND section_id=? AND status='approved'",[(int)$d['student_id'],(int)$d['section_id']])?'':'That student is not enrolled in this section. Choose a student from the list.';}
function chk_grades(&$d,$id){$r=me()['role'];
 $old=$id?rows('SELECT * FROM grades WHERE id=?',[$id])[0]??null:null;
 if($id&&!$old)return 'This grade record no longer exists. Refresh the page and try again.';
 $status=$d['status']??($old['status']??'draft');
 $grade=array_key_exists('grade',$d)?$d['grade']:($old['grade']??null);
 $section=(int)($d['section_id']??($old['section_id']??0));
 $student=(int)($d['student_id']??($old['student_id']??0));
 if($r==='professor'&&(!mine($section)||in_array($status,['approved','final'])))return 'Professors may encode grades for their own sections as draft or submitted.';
 if($r==='dean'){
  if(!$old)return 'The Dean cannot create grade records.';
  if($old['status']==='final'){
   foreach(['grade','student_id','section_id','status'] as $field){
    if(array_key_exists($field,$d) && (string)($d[$field]??'')!==(string)($old[$field]??''))return 'The Dean may only change the Locked field on a finalized grade.';
   }
  } elseif($status==='final') return 'The Registrar finalizes grades. The Dean may only lock or unlock a finalized grade.';
 }
 if($r==='professor'&&!val("SELECT COUNT(*) FROM enrollments WHERE student_id=? AND section_id=? AND status='approved'",[$student,$section]))return 'That student is not enrolled in this section. Choose a student from the list.';
 if($status!=='draft'&&$grade===null)return 'Enter a grade before moving it past draft.';
 if($grade!==null&&($grade<0||$grade>100))return 'A grade must be between 0 and 100.';
 if($status==='final'&&$r!=='dean')$d['locked']=1;
 return '';}
function chk_announcements($d){
 if($d['scope']==='class'&&!$d['section_id'])return 'Choose a section for a class announcement.';
 return me()['role']==='professor'&&($d['scope']!=='class'||!mine($d['section_id']))?'Professors can announce to their own classes only.':'';}
function chk_semesters($d,$id){if(trim((string)($d['name']??''))==='')return 'Enter a semester name, for example 1st Sem 2027-2028.'; if(($d['is_active']??0)!=1 && $id && val('SELECT is_active FROM semesters WHERE id=?',[$id]))return 'One semester must stay active. Activate another semester before deactivating this one.'; return '';}
function del_sections($id){return val('SELECT (SELECT COUNT(*) FROM enrollments WHERE section_id=?)+(SELECT COUNT(*) FROM grades WHERE section_id=?)+(SELECT COUNT(*) FROM attendance WHERE section_id=?)+(SELECT COUNT(*) FROM posts WHERE section_id=?)',[$id,$id,$id,$id])?'This section has academic history or class posts, so it cannot be deleted. Set its slots to 0 to close it instead.':'';}
function del_users($id){return $id==me()['id']?'An account cannot delete itself. Set it to inactive, or delete it from another admin account.':'';}
function del_posts($id){return val('SELECT COUNT(*) FROM submissions WHERE post_id=?',[$id])?'Students already submitted work for this post, so it cannot be deleted. Edit the post instead.':'';}
function chk_fees($d,$id){
 $amount=(float)($d['amount']??0);$paid=(float)($d['paid']??0);$desc=strtolower(trim((string)($d['description']??'')));$student=(int)($d['student_id']??0);
 if(in_array($desc,['midterm','finals'],true)&&abs($amount-3050)>0.001)return 'Midterm and Finals are fixed at ₱3,050 each.';
 if($amount<0||$amount>10800||$paid<0||$paid>$amount)return 'Each billing item must be within ₱0–₱10,800 and paid cannot exceed its amount.';
 $total=(float)val('SELECT COALESCE(SUM(amount),0) FROM fees WHERE student_id=? AND id<>?',[$student,(int)$id]);
 if($total+$amount>10800.001)return 'Billing total cannot exceed ₱10,800 for a student. Current total: ₱'.number_format($total,2).'.';
 $expected=$paid<=0?'unpaid':($paid>=$amount?'paid':'partial');if(($d['status']??$expected)!==$expected)return 'Payment status must match the amounts: unpaid, partial, or paid.';return '';}

function del_fees($id){return (float)val('SELECT COALESCE(paid,0) FROM fees WHERE id=?',[$id])>0?'A fee with recorded payments cannot be deleted. Correct it through an audited adjustment instead.':'';}


require __DIR__.'/layout.php';
