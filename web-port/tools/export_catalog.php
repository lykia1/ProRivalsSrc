<?php
declare(strict_types=1);
// Run locally with a read-only SQL Server login. Never deploy this CLI script as an endpoint.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$dsn = getenv('ACETR_SOURCE_DSN');
if (!$dsn) { fwrite(STDERR,"Set ACETR_SOURCE_DSN (sqlsrv PDO DSN); optional ACETR_SOURCE_USER and ACETR_SOURCE_PASSWORD.\n"); exit(1); }
$tables=['ti_ItemInfo','ti_EnchantInfo','ti_RareItemInfo','ti_Monster','ti_MonsterItem','ti_MapInfo','ti_Shop'];
try {
    $db=new PDO($dsn, getenv('ACETR_SOURCE_USER') ?: null, getenv('ACETR_SOURCE_PASSWORD') ?: null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $result=['exportedAt'=>gmdate('c'),'tables'=>[]];
    foreach($tables as $table){
        $query=$db->query('SELECT * FROM dbo.['.$table.']');
        $result['tables'][$table]=$query->fetchAll(PDO::FETCH_ASSOC);
    }
    $output=$argv[1] ?? __DIR__.'/../data/catalog.json';
    $json=json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    if(file_put_contents($output,$json,LOCK_EX)===false)throw new RuntimeException('Cannot write export.');
    echo "Exported 7 game-definition tables. No account or character records included.\n";
} catch(Throwable $e){fwrite(STDERR,"Catalog export failed. Check read permission, driver, DSN and output location locally.\n");exit(1);}
