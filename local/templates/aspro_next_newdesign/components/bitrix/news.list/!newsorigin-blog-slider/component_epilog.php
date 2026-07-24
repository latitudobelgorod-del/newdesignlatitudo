<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arExtensions = ['swiper', 'line_block'];
if ($arExtensions) {
    \Aspro\Next\Functions\Extensions::init($arExtensions);
}