<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/* Подсказки на странице выдачи те же, что в шапке. Держим один файл, а не
   копию: три расходящиеся копии одной разметки — верный способ поправить
   что-то в одной и забыть про остальные. Скоуп ($arResult, $arParams, $this)
   include передаёт как есть. */
include __DIR__.'/../newdesign/ajax.php';
