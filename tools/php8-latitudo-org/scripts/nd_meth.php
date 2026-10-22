<?php
// печать: метод, static?, использует $this?
[$file,$class]=[$argv[1],$argv[2]];
$t=token_get_all(file_get_contents($file));$n=count($t);$inClass=false;$depth=0;$cd=-1;
for($i=0;$i<$n;$i++){$x=$t[$i];
 if(is_array($x)&&$x[0]==T_CLASS){for($j=$i+1;$j<$n;$j++)if(is_array($t[$j])&&$t[$j][0]==T_STRING){$inClass=($t[$j][1]===$class);break;} if($inClass)$cd=$depth;}
 if($x==="{"||(is_array($x)&&in_array($x[0],[T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES])))$depth++;
 if($x==="}"){$depth--; if($inClass&&$depth==$cd){$inClass=false;}}
 if($inClass&&is_array($x)&&$x[0]==T_FUNCTION&&$depth==$cd+1){
  $st=false;for($k=$i-1;$k>$i-8;$k--){if(is_array($t[$k])&&$t[$k][0]==T_STATIC)$st=true;}
  for($j=$i+1;$j<$n;$j++)if(is_array($t[$j])&&$t[$j][0]==T_STRING){$name=$t[$j][1];break;}
  // тело
  for(;$j<$n&&$t[$j]!=="{"&&$t[$j]!==";";$j++);$d=0;$this_=false;$line=$x[2];
  if($t[$j]==="{"){for(;$j<$n;$j++){$y=$t[$j];if($y==="{"||(is_array($y)&&in_array($y[0],[T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES])))$d++;if($y==="}"){$d--;if(!$d)break;}if(is_array($y)&&$y[0]==T_VARIABLE&&$y[1]==="\$this")$this_=true;}}
  echo "$line\t$name\t".($st?"static":"-")."\t".($this_?"THIS":"")."\n";
 }
}
