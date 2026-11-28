<?php
/** Real Sodium compatibility regression without optional mbstring; memory streams only. */
require __DIR__.'/bootstrap.php';
use FileWarden\BackupArchive;
if(($argv[1]??'')==='--without-sodium'){
 if(PHP_SAPI!=='cli'||function_exists('sodium_crypto_pwhash'))exit(1);
 try{BackupArchive::key(str_repeat('a',16),str_repeat('s',16));throw new RuntimeException('Missing Sodium accepted');}
 catch(RuntimeException $error){if(FileWarden\BackupErrors::code($error)!=='FWB_SODIUM_UNAVAILABLE')throw $error;}
 echo "PASS: missing native Sodium rejected with its safe specific reference.\n";exit(0);
}
if(PHP_SAPI!=='cli'||extension_loaded('mbstring')||!function_exists('sodium_crypto_pwhash'))exit(1);
$salt=random_bytes(16);$phrase=str_repeat('é',16);$key=BackupArchive::key($phrase,$salt);
$stream=fopen('php://memory','w+b');$writer=BackupArchive::create($stream,$key,$salt);
BackupArchive::push($stream,$writer,'M','{"local":"fixture"}');BackupArchive::push($stream,$writer,'Z');
rewind($stream);$reader=BackupArchive::open($stream,$phrase);
if(BackupArchive::pull($stream,$reader)!==['M','{"local":"fixture"}']||BackupArchive::pull($stream,$reader)!==['Z',''])throw new RuntimeException('No-mbstring round trip failed');
foreach([str_repeat('é',15),str_repeat('a',1025),str_repeat('a',16)."\xff"]as$bad){
    try{BackupArchive::key($bad,$salt);throw new RuntimeException('Invalid phrase accepted');}
    catch(InvalidArgumentException $error){}
}
fclose($stream);
echo "PASS: native Sodium round trip without mbstring; Unicode length, invalid UTF-8 and byte limit enforced.\n";
