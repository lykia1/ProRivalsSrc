<?php
declare(strict_types=1);
/** PHP 8.1+ / PDO SQLite domain service. $accountId MUST come from trusted authentication, never request input. */
final class PilotFlow
{
    public function __construct(private PDO $db) {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    public static function maps(): array {return json_decode(file_get_contents(__DIR__.'/../data/maps.json'),true,512,JSON_THROW_ON_ERROR);}
    public static function home(string $nation): int {return match($nation){'ANI'=>2002,'BCU'=>2001,default=>throw new DomainException('Invalid nation')};}
    public static function route(string $nation): array {
        self::home($nation);
        $first=$nation==='BCU'?[3002,3003,3067,3004,3005,3006,3064,3063,3068,3008]:[3018,3014,3013,4006,3009,3007,3069,3008];
        foreach(self::maps() as $m)if(!in_array($m['id'],[2001,2002,...$first],true))$first[]=$m['id'];
        return $first;
    }
    public function register(string $accountId,string $callsign): array {
        if($accountId===''||!preg_match('/^[a-zA-Z0-9_ÇĞİÖŞÜçğıöşü-]{3,20}$/u',$callsign))throw new DomainException('Invalid account or callsign');
        $this->db->prepare('INSERT INTO pilots(id,callsign) VALUES (?,?)')->execute([$accountId,$callsign]);
        return $this->get($accountId);
    }
    public function get(string $accountId): array {
        $q=$this->db->prepare('SELECT * FROM pilots WHERE id=?');$q->execute([$accountId]);
        return $q->fetch() ?: throw new DomainException('Register first');
    }
    public function cityPilots(string $nation): array {
        $q=$this->db->prepare('SELECT callsign,gear FROM pilots WHERE nation=? AND location=? ORDER BY callsign LIMIT 100');
        $q->execute([$nation,self::home($nation)]);return $q->fetchAll();
    }
    public function command(string $accountId,int $revision,string $action,mixed $value=null,?int $now=null): array {
        $p=$this->get($accountId);
        if((int)$p['revision']!==$revision)throw new DomainException('Stale state');
        $next=self::transition($p,$action,$value,$now??(int)floor(microtime(true)*1000));
        $q=$this->db->prepare('UPDATE pilots SET gear=?,nation=?,location=?,progress=?,mission_ends=?,revision=revision+1 WHERE id=? AND revision=?');
        $q->execute([$next['gear'],$next['nation'],$next['location'],$next['progress'],$next['mission_ends'],$accountId,$revision]);
        if($q->rowCount()!==1)throw new DomainException('Concurrent command; reload');
        return $this->get($accountId);
    }
    private static function check(bool $condition,string $message): void {if(!$condition)throw new DomainException($message);}
    public static function transition(array $p,string $action,mixed $value,int $now): array {
        if($action==='gear'){
            self::check(!$p['gear']&&!$p['nation'],'Character already created');
            self::check(in_array($value,['B-Gear','I-Gear','A-Gear','M-Gear'],true),'Invalid gear');
            $p['gear']=$value;return $p;
        }
        if($action==='nation'){
            self::check((bool)$p['gear'],'Choose gear first');self::check(!$p['nation'],'Nation is permanent');
            self::check(in_array($value,['ANI','BCU'],true),'Invalid nation');
            $p['nation']=$value;$p['location']=self::home($value);return $p;
        }
        self::check((bool)$p['nation'],'Choose nation first');
        $home=self::home($p['nation']);$list=self::route($p['nation']);$target=$list[$p['progress']-1]??null;
        switch($action){
            case 'briefing':
                self::check($p['progress']===0&&$p['location']===$home,'Briefing already completed');$p['progress']=1;return $p;
            case 'warp':
                self::check(!$p['mission_ends'],'Mission active');
                if($value===$home){$p['location']=$home;return $p;}
                self::check($p['location']===$home,'Return to city first');
                self::check(is_int($value)&&in_array($value,array_slice($list,0,$p['progress']),true),'Map locked');
                $p['location']=$value;return $p;
            case 'start':
                self::check(!$p['mission_ends']&&$target&&$p['location']===$target,'Invalid mission location or mission active');
                $p['mission_ends']=$now+30000;return $p;
            case 'complete':
                self::check($p['mission_ends']&&$now>=$p['mission_ends']&&$p['location']===$target,'Mission not complete');
                $p['progress']++;$p['mission_ends']=null;$p['location']=$home;return $p;
            default:throw new DomainException('Invalid command');
        }
    }
}
