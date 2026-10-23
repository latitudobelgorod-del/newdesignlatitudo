<?php
// Товары, у которых главного фото (DETAIL_PICTURE) нет среди доп. фото (MORE_PHOTO): в слайдере карточки
// его не видно (шаблон main6_newdesign, ADD_DETAIL_TO_SLIDER=N). Картинки сравниваем по виду: 16×16 в сером, средняя разница.
//   php main_photo_check.php <ID бренда|all>  → main_photo_missing_<бренд>.txt (ID<TAB>число доп. фото<TAB>название)
$_SERVER["DOCUMENT_ROOT"]=getenv("HOME")."/www/latitudo.ru";define("NO_KEEP_STATISTIC",true);define("NOT_CHECK_PERMISSIONS",true);
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";\Bitrix\Main\Loader::includeModule("iblock");
function thumb($src){$p=$_SERVER["DOCUMENT_ROOT"].$src;$s=@file_get_contents($p);$im=$s?@imagecreatefromstring($s):false;if(!$im)return null;
 $t=imagecreatetruecolor(16,16);imagecopyresampled($t,$im,0,0,0,0,16,16,imagesx($im),imagesy($im));$v=[];for($y=0;$y<16;$y++)for($x=0;$x<16;$x++){$c=imagecolorat($t,$x,$y);$v[]=(($c>>16&255)+($c>>8&255)+($c&255))/3;}return $v;}
function diff($a,$b){$s=0;foreach($a as $i=>$v)$s+=abs($v-$b[$i]);return $s/count($a);}
$brand=$argv[1]??"28619";$bad=[];$n=0;
$f=["IBLOCK_ID"=>19,"ACTIVE"=>"Y","!DETAIL_PICTURE"=>false];if($brand!=="all")$f["PROPERTY_BRAND"]=(int)$brand;
$r=CIBlockElement::GetList(["ID"=>"ASC"],$f,false,false,["ID","NAME","DETAIL_PICTURE"]);
while($e=$r->Fetch()){$more=[];$p=CIBlockElement::GetProperty(19,$e["ID"],["value_id"=>"asc"],["CODE"=>"MORE_PHOTO"]);while($x=$p->Fetch())if($x["VALUE"])$more[]=$x["VALUE"];
 if(!$more)continue;$n++;$d=thumb(CFile::GetPath($e["DETAIL_PICTURE"]));if(!$d)continue;$best=999;
 foreach($more as $m){$t=thumb(CFile::GetPath($m));if($t)$best=min($best,diff($d,$t));if($best<6)break;}
 if($best>=6)$bad[]=$e["ID"]."\t".count($more)."\t".round($best)."\t".$e["NAME"];}
echo "с доп. фото: $n, главного среди них нет: ",count($bad),"\n",implode("\n",array_slice($bad,0,12)),"\n";
file_put_contents(getenv("HOME")."/4sis-sync/main_photo_missing_$brand.txt",implode("\n",$bad));
