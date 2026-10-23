<?php
// Главное фото — первым в доп. фото (MORE_PHOTO), чтобы оно было в слайдере карточки (шаблон main6_newdesign,
// ADD_DETAIL_TO_SLIDER=N: при непустом MORE_PHOTO главное в слайдер не попадает). Прежние доп. фото — следом, порядок тот же.
//   php main_photo_fix.php <файл со списком (ID в начале строки)> [apply]
// Копии прежних файлов и журнал — ~/4sis-sync/<дата>_mainphoto/ (вне www); откат — rollback.php там же не нужен:
// в журнале у каждого товара пути прежних файлов, их можно вернуть тем же SetPropertyValuesEx.
$_SERVER["DOCUMENT_ROOT"]=getenv("HOME")."/www/latitudo.ru";define("NO_KEEP_STATISTIC",true);define("NOT_CHECK_PERMISSIONS",true);
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";\Bitrix\Main\Loader::includeModule("iblock");
$ids=[];foreach(file($argv[1]) as $l) if(preg_match("/^(\d+)/",$l,$m)) $ids[]=(int)$m[1];
$apply=($argv[2]??"")==="apply";
$dir=getenv("HOME")."/4sis-sync/".date("Y-m-d_His")."_mainphoto";mkdir($dir,0700,true);$log=[];
foreach($ids as $id){$e=CIBlockElement::GetByID($id)->Fetch();$main=CFile::GetPath($e["DETAIL_PICTURE"]);if(!$main){echo "$id без главного фото\n";continue;}
 $old=[];$p=CIBlockElement::GetProperty(19,$id,["value_id"=>"asc"],["CODE"=>"MORE_PHOTO"]);while($x=$p->Fetch())if($x["VALUE"])$old[]=CFile::GetPath($x["VALUE"]);
 echo "$id: главное + ",count($old)," прежних\n";if(!$apply)continue;
 $files=[];$keep=[];foreach(array_merge([$main],$old) as $i=>$src){$dst="$dir/{$id}_{$i}_".basename($src);copy($_SERVER["DOCUMENT_ROOT"].$src,$dst);$keep[]=$dst;$files[]=["VALUE"=>CFile::MakeFileArray($dst)];}
 CIBlockElement::SetPropertyValuesEx($id,19,["MORE_PHOTO"=>$files]);
 $new=[];$p=CIBlockElement::GetProperty(19,$id,["value_id"=>"asc"],["CODE"=>"MORE_PHOTO"]);while($x=$p->Fetch())if($x["VALUE"])$new[]=CFile::GetPath($x["VALUE"]);
 $log[$id]=["old"=>$old,"copies"=>$keep,"new"=>$new];echo "   стало ",count($new),"\n";}
file_put_contents("$dir/log.json",json_encode($log,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));CIBlock::clearIblockTagCache(19);
echo "журнал $dir/log.json\n";
