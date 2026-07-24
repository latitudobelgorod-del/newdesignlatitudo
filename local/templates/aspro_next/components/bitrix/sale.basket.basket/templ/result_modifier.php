<?
global $USER;

if($USER->IsAdmin()){
//    $totalVal = 0;
//    foreach ($arResult['BASKET_ITEM_RENDER_DATA'] as &$item){
//        $val = false;
//        $existVal = true;
//        $codes = array_column($item['COLUMN_LIST'], 'CODE');
//        if(in_array('PROPERTY_IT_6_VALUE', $codes) && in_array('PROPERTY_IT_8_VALUE', $codes) && in_array('PROPERTY_DLINA_VALUE', $codes)){
//            foreach ($item['COLUMN_LIST'] as $pr){
//                if($pr['CODE'] == 'PROPERTY_IT_6_VALUE' || $pr['CODE'] == 'PROPERTY_IT_8_VALUE' || $pr['CODE'] == 'PROPERTY_DLINA_VALUE'){
//                    if($pr['VALUE'])
//                    if(!$val){
//                        $val = $pr['VALUE']/1000;
//                    }else{
//                        $val=$val*$pr['VALUE']/1000;
//                    }
//                }
//            }
//            $totalVal += $val*$item['QUANTITY'];
//            $item['COLUMN_LIST'][] = array('CODE'=>'CUST_VAL','NAME'=>'Объем, м3','IS_TEXT'=>true,'VALUE'=>$val*$item['QUANTITY'],'HIDE_MOBILE'=>true);
//        }
//    }
////    echo printPre($totalVal);
//    if($totalVal>0){
//        $arResult['TOTAL_RENDER_DATA']['VOLUME_FORMATED'] = $totalVal.' м3';
//    }
}
?>