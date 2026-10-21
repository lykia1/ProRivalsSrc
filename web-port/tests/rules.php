<?php
declare(strict_types=1);
require __DIR__.'/../src/Rules.php';
function check(bool $ok,string $message):void { if(!$ok)throw new RuntimeException($message); }
$r=new AceTR\Rules();
check(count($r->data()['stats'])===342,'complete stat table');
check($r->data()['gears']['IGEAR']===[4,2,3,3,2,4],'I-Gear increments');
$a=$r->calculate('BGEAR',1,[3,3,3,3,3,3]);
check($a['hp']===600 && $a['shield']===99 && $a['sp']===24 && $a['fuel']===30,'base resources / C++ short truncation');
check($a['pierce']===1,'C++ INT Pierce truncation');
check(abs($r->weaponDamage(100,0,3,1)-102.73)<0.0001,'GetAttackC formula');
check($r->initialExperience(1)==0 && $r->initialExperience(2)==91,'level experience off-by-one');
check($r->calculate('IGEAR',100,[340,340,340,340,340,340])['cap']===300,'level 100 cap');
check($r->calculate('IGEAR',101,[340,340,340,340,340,340])['cap']===340,'level 101 cap');
$b=$r->allocate('IGEAR',5,[3,3,3,3,3,3],1,5);check($b['stats'][5]===7 && $b['available']===0,'class increment');
try{$r->allocate('IGEAR',5,[3,3,3,3,3,3],0,5);throw new RuntimeException('zero points accepted');}catch(InvalidArgumentException $expected){}
echo "Rules tests passed.\n";
