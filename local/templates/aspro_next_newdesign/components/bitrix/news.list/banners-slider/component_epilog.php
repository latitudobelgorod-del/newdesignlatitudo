<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arExtensions = ['swiper'];
if ($arExtensions) {
    \Aspro\Next\Functions\Extensions::init($arExtensions);
}