<?php
declare(strict_types=1);
require __DIR__.'/../src/StrategicPoint.php';
function check(bool $v):void{if(!$v)throw new RuntimeException('Assertion failed');}
function rejects(callable $fn):void{try{$fn();}catch(DomainException $e){return;}throw new RuntimeException('Expected rejection');}
$db=new PDO('sqlite::memory:');
$db->exec(file_get_contents(__DIR__.'/../migrations/001_pilots.sql'));
$db->exec(file_get_contents(__DIR__.'/../migrations/002_strategic_points.sql'));
$flow=new PilotFlow($db);$war=new StrategicPoint($db);
foreach(['ANI','BCU'] as $nation){$flow->register($nation,$nation.'Pilot');$flow->command($nation,0,'gear','I-Gear');$flow->command($nation,1,'nation',$nation);}
$event=$war->state('ANI',900000);check($event['event']['hp']===1000);
rejects(fn()=>$war->order('ANI',1,900000));
$db->exec('UPDATE pilots SET location=3018,progress=74');
rejects(fn()=>$war->order('BCU',1,900000));
$war->order('ANI',1,900000);check($war->state('BCU',900000)['event']['hp']===900);
rejects(fn()=>$war->order('ANI',1,900001));
$war->order('BCU',1,900001);check($war->state('ANI',900001)['event']['hp']===960);
rejects(fn()=>$war->order('ANI',1,914999));
$war->order('ANI',1,915000);check($war->state('ANI',915000)['event']['hp']===860);
check($war->state('BCU',1500000)['result']==='BCU');
rejects(fn()=>$war->order('ANI',1,1500000));
$war->state('ANI',1800000);rejects(fn()=>$war->order('ANI',1,1800000));
$db->exec('UPDATE strategic_events SET hp=10 WHERE id=2');
$war->order('ANI',2,1800000);check($war->state('BCU',1800000)['result']==='ANI');
rejects(fn()=>$war->order('BCU',2,1800001));
echo "PASS: PHP shared events, attack/defense, cooldown, map gates, timeout, fresh event, final blow, no resurrection\n";
