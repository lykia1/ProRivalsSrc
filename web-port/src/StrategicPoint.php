<?php
declare(strict_types=1);
require_once __DIR__.'/PilotFlow.php';
/** Shared timed event; authenticated account IDs and server time only. */
final class StrategicPoint {
    public const INTERVAL=900000, DURATION=600000, COOLDOWN=15000;
    private const ORDER_SQL=<<<'SQL'
INSERT INTO strategic_orders (id,event_id,pilot_id,nation,at,delta)
SELECT ?, e.id, p.id, p.nation, ?, CASE WHEN p.nation='ANI' THEN -MIN(100,e.hp) ELSE MIN(60,1000-e.hp) END
FROM strategic_events e JOIN pilots p ON p.id=?
WHERE e.id=? AND e.starts<=? AND e.ends>? AND e.hp>0
AND p.nation IN ('ANI','BCU') AND p.location=3018 AND p.mission_ends IS NULL
AND p.progress>=? AND (p.nation='ANI' OR e.hp<1000)
AND NOT EXISTS (SELECT 1 FROM strategic_orders o WHERE o.event_id=e.id AND o.pilot_id=p.id AND o.at>?)
SQL;
    private const APPLY_SQL=<<<'SQL'
UPDATE strategic_events SET hp=hp+(SELECT delta FROM strategic_orders WHERE id=?) WHERE id=? AND EXISTS (SELECT 1 FROM strategic_orders WHERE id=?)
SQL;
    public function __construct(private PDO $db){$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);}
    public function state(string $accountId,?int $now=null):array {
        $p=(new PilotFlow($this->db))->get($accountId);
        if(!$p['nation'])throw new DomainException('Choose nation first');
        $now??=(int)floor(microtime(true)*1000);$id=intdiv($now,self::INTERVAL);$starts=$id*self::INTERVAL;
        $this->db->prepare('INSERT OR IGNORE INTO strategic_events(id,starts,ends,hp) VALUES (?,?,?,1000)')->execute([$id,$starts,$starts+self::DURATION]);
        $q=$this->db->prepare('SELECT * FROM strategic_events WHERE id=?');$q->execute([$id]);$e=$q->fetch();
        $q=$this->db->prepare('SELECT MAX(at) FROM strategic_orders WHERE event_id=? AND pilot_id=?');$q->execute([$id,$accountId]);$last=$q->fetchColumn();
        $q=$this->db->prepare('SELECT p.callsign,o.nation,o.at,o.delta FROM strategic_orders o JOIN pilots p ON p.id=o.pilot_id WHERE o.event_id=? ORDER BY o.at DESC,o.id DESC LIMIT 12');$q->execute([$id]);
        return ['event'=>$e,'result'=>$e['hp']<=0?'ANI':($now>=$e['ends']?'BCU':null),'nextStarts'=>$starts+self::INTERVAL,'lastOrder'=>$last,'orders'=>$q->fetchAll(),'serverTime'=>$now];
    }
    public function order(string $accountId,int $eventId,?int $now=null):void {
        $now??=(int)floor(microtime(true)*1000);
        if($eventId!==intdiv($now,self::INTERVAL))throw new DomainException('Event expired');
        $p=(new PilotFlow($this->db))->get($accountId);
        if(!$p['nation'])throw new DomainException('Choose nation first');
        $threshold=array_search(3018,PilotFlow::route($p['nation']),true)+1;
        $id=bin2hex(random_bytes(16));
        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare(self::ORDER_SQL);$q->execute([$id,$now,$accountId,$eventId,$now,$now,$threshold,$now-self::COOLDOWN]);
            if($q->rowCount()!==1)throw new DomainException('Location, unlock, cooldown or active event requirement not met');
            $this->db->prepare(self::APPLY_SQL)->execute([$id,$eventId,$id]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
}
