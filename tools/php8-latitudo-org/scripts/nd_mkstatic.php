<?php
// ставит static всем методам класса без static и без $this
[$file,$class]=[$argv[1],$argv[2]];
ob_start(); $argv=[0,$file,$class]; include __DIR__."/nd_meth.php"; $out=ob_get_clean();
$lines=file($file); $c=0;
foreach(explode("\n",trim($out)) as $r){ if($r==="")continue; [$ln,$name,$st,$th]=array_pad(explode("\t",$r),4,"");
 if($st==="static"||$th==="THIS")continue;
 $new=preg_replace("/\bfunction\s+(&?\s*".preg_quote($name)."\s*\()/i","static function $1",$lines[$ln-1],1,$k);
 if($k){$lines[$ln-1]=$new;$c++;} else fwrite(STDERR,"не нашёл $name в строке $ln\n");}
file_put_contents($file,implode("",$lines)); echo "$class: $c\n";
