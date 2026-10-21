<?php
declare(strict_types=1);
require __DIR__.'/../src/PilotFlow.php';
function check(bool $v,string $message):void{if(!$v)throw new RuntimeException($message);}
function rejects(callable $fn):void{try{$fn();}catch(DomainException|PDOException $e){return;}throw new RuntimeException('Expected rejection');}
$file=tempnam(sys_get_temp_dir(),'pilot-flow-');
try {
 $db=new PDO('sqlite:'.$file);$db->exec(file_get_contents(__DIR__.'/../migrations/001_pilots.sql'));
 $service=new PilotFlow($db);
 foreach(['ANI','BCU'] as $nation){
  $id='test-'.$nation;$p=$service->register($id,$nation.'Pilot');
  rejects(fn()=>$service->command($id,0,'nation',$nation));
  $p=$service->command($id,0,'gear','I-Gear');
  $p=$service->command($id,1,'nation',$nation);
  rejects(fn()=>$service->command($id,1,'nation',$nation==='ANI'?'BCU':'ANI'));
  rejects(fn()=>$service->command($id,2,'nation',$nation==='ANI'?'BCU':'ANI'));
  $list=PilotFlow::route($nation);
  rejects(fn()=>$service->command($id,2,'warp',$list[0]));
  $p=$service->command($id,2,'briefing');
  rejects(fn()=>$service->command($id,3,'warp',$list[1]));
  $p=$service->command($id,3,'warp',$list[0]);$p=$service->command($id,4,'start',null,100);
  rejects(fn()=>$service->command($id,5,'warp',PilotFlow::home($nation)));
  rejects(fn()=>$service->command($id,5,'complete',null,30099));
  $p=$service->command($id,5,'complete',null,30100);
  check($p['progress']===2&&$p['location']===PilotFlow::home($nation),'Unlock and return home');
  rejects(fn()=>$service->command($id,6,'complete',null,40000));
  $reopened=new PilotFlow(new PDO('sqlite:'.$file));check($reopened->get($id)===$p,'Persists across connections');
  check(count($reopened->cityPilots($nation))===1,'Nation city roster');
  rejects(fn()=>$db->exec("UPDATE pilots SET nation=NULL WHERE id='test-$nation'"));
 }
 echo "PASS: both nations, persistent accounts, stale commands, database nation lock, quest gates, early/replayed completion, city rosters\n";
} finally {unlink($file);}
