<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<? $this->setFrameMode(true); ?>

<? global $arRegion, $APPLICATION;
static $email_call;
$regionID = ($arRegion ? $arRegion['ID'] : '');
?>
<div class="item-views-wrapper <?= $templateName; ?>">

    <? if ($arResult['SECTIONS']): ?>
        <div class="maxwidth-theme">
            <div class="row">
                <div class="col-md-12">
                    <table class="contacts-stores no-border shops list">
                        <? foreach ($arResult['SECTIONS'] as $si => $arSection): ?>
                            <? $bHasSection = (isset($arSection['SECTION']) && $arSection['SECTION']) ?>
                  
                            <? foreach ($arSection['ITEMS'] as $i => $arItem): ?>
                                <?
                                // edit/add/delete buttons for edit mode
                                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
                                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                                // use detail link?
                                $bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
                                // preview picture
                                $bImage = strlen($arItem['FIELDS']['PREVIEW_PICTURE']['SRC']);
                                $imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : false);
                                $imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
                                $address = ($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? " " . $arItem['PROPERTIES']['ADDRESS']['VALUE'] : "");
                                ?>
 <? if ($address): ?>
                                <tr class="item" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
                                	<?if($imageSrc):?>
										<td class="img">
											<a href="<?=$arItem["DETAIL_PAGE_URL"];?>">
												<img src="<?=$imageSrc;?>" alt="<?=$arItem['NAME'];?>" title="<?=$arItem['NAME'];?>" class="img-responsive"/>
											</a>
										</td>
									<?endif;?>
                                    <td class="" <?= (($arResult['ITEMS_HAS_IMG'] && !$imageSrc) ? 'colspan=2' : ''); ?>>
                                        <? if (!empty($arItem['PROPERTIES']['PHONE']['VALUE'])) :?>
                                        <span itemscope itemtype="https://schema.org/LocalBusiness" itemprop="department">
                                            <meta itemprop="name" content="<?= $arItem['NAME'] ?>"/>
                                            <link itemprop="url"
                                                  href="https://<?= SITE_SERVER_NAME . $arItem['PROPERTIES']['LINK_CONTACT']['VALUE']; ?>"/>
                                            <span style="display: none">
                                                <?= html_entity_decode($address) ?>
                                                <?=!empty($arItem['PROPERTIES']['SCHEDULE']['VALUE'])?html_entity_decode($arItem['PROPERTIES']['SCHEDULE']['~VALUE']['TEXT']):""?>
                                            </span>
                                            <?foreach ($arItem['PROPERTIES']['PHONE']['VALUE'] as $phone):?>
                                                <meta itemprop="telephone" content="<?=$phone?>">
                                            <?endforeach;?>
                                            <? if (!empty($arItem['PROPERTIES']['EMAIL']['VALUE'])):?><meta itemprop="email" content="<?=$arItem['PROPERTIES']['EMAIL']['VALUE']?>"><?endif;?>

                                        </span>
                                        <?endif;?>

                                        <div class="title">
                                            <a href="<?= $arItem['PROPERTIES']['LINK_CONTACT']['VALUE']; ?>"
                                               class="dark_link"><?= $arItem['NAME']; ?></a>
                                        </div>
                                        <div></div>
                                        <span class="icon-text schedule grey s25"><i class="fa fa-map-marker"></i> <span
                                                    class="text"><?= strip_tags(html_entity_decode($address)); ?></span></span>


                                        <? if ($arItem['PROPERTIES']['METRO']['VALUE']): ?>
                                            <? foreach ($arItem['PROPERTIES']['METRO']['VALUE'] as $metro): ?>
                                                <div class="metro">
                                                    <i></i><?= $metro; ?>
                                                </div>
                                            <? endforeach; ?>
                                        <? endif; ?>
                                                                           </td>
                                    <td class="phone">
                                        <? if ($arItem['PROPERTIES']['PHONE']['VALUE']) {
                                            foreach ($arItem['PROPERTIES']['PHONE']['VALUE'] as $phone):?>
                                                <div><span class="fa fa-phone"></span><a
                                                            href="tel:<?= str_replace(array(' ', ',', '-', '(', ')'), '', $phone); ?>"
                                                            class="black"><?= $phone; ?></a></div>
                                            <?endforeach;
                                        } ?>

                                        <? if ($arItem['PROPERTIES']['EMAIL']['VALUE']): ?>
                                            <div><span class="fa fa-envelope-o"></span> <a class="black"
                                                                                           href="mailto:<?= $arItem['PROPERTIES']['EMAIL']['VALUE'] ?>"><?= $arItem['PROPERTIES']['EMAIL']['VALUE'] ?></a>
                                            </div>
                                        <? endif; ?>

                                    </td>
                      
                                </tr>
								
								 <? endif; ?>
                            <? endforeach; ?>
                        <? endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    <? endif; ?>
</div>













<div class="item-views-wrapper <?= $templateName; ?>">

    <? if ($arResult['SECTIONS']): ?>
        <div class="maxwidth-theme">
            <div class="row">
                <div class="col-md-12">
                    <div class="contacts-stores no-border shops list">
                        <? foreach ($arResult['SECTIONS'] as $si => $arSection): ?>
                            <? $bHasSection = (isset($arSection['SECTION']) && $arSection['SECTION']) ?>
                  
                            <? foreach ($arSection['ITEMS'] as $i => $arItem): ?>
                                <?
                                // edit/add/delete buttons for edit mode
                                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
                                $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                                // use detail link?
                                $bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
                                // preview picture
                                $bImage = strlen($arItem['FIELDS']['PREVIEW_PICTURE']['SRC']);
                                $imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : false);
                                $imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
                                $address = ($arItem['PROPERTIES']['ADDRESS']['VALUE'] ? " " . $arItem['PROPERTIES']['ADDRESS']['VALUE'] : "");
                                ?>
 <? if ($address): ?>
                                <div class="item" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
                                	<?if($imageSrc):?>
										<div class="img col-md-2">
											<a href="<?=$arItem["DETAIL_PAGE_URL"];?>">
												<img src="<?=$imageSrc;?>" alt="<?=$arItem['NAME'];?>" title="<?=$arItem['NAME'];?>" class="img-responsive"/>
											</a>
										</div>
									<?endif;?>
                                    <div class=" <?= (($arResult['ITEMS_HAS_IMG'] && !$imageSrc) ? 'col-md-8' : 'col-md-4'); ?>">
                                        <? if (!empty($arItem['PROPERTIES']['PHONE']['VALUE'])) :?>
                                        <span itemscope itemtype="https://schema.org/LocalBusiness" itemprop="department">
                                            <meta itemprop="name" content="<?= $arItem['NAME'] ?>"/>
                                            <link itemprop="url"
                                                  href="https://<?= SITE_SERVER_NAME . $arItem['PROPERTIES']['LINK_CONTACT']['VALUE']; ?>"/>
                                            <span style="display: none">
                                                <?= html_entity_decode($address) ?>
                                                <?=!empty($arItem['PROPERTIES']['SCHEDULE']['VALUE'])?html_entity_decode($arItem['PROPERTIES']['SCHEDULE']['~VALUE']['TEXT']):""?>
                                            </span>
                                            <?foreach ($arItem['PROPERTIES']['PHONE']['VALUE'] as $phone):?>
                                                <meta itemprop="telephone" content="<?=$phone?>">
                                            <?endforeach;?>
                                            <? if (!empty($arItem['PROPERTIES']['EMAIL']['VALUE'])):?><meta itemprop="email" content="<?=$arItem['PROPERTIES']['EMAIL']['VALUE']?>"><?endif;?>

                                        </span>
                                        <?endif;?>

                                        <div class="title">
                                            <a href="<?= $arItem['PROPERTIES']['LINK_CONTACT']['VALUE']; ?>"
                                               class="dark_link"><?= $arItem['NAME']; ?></a>
                                        </div>
                                        <div></div>
                                        <span class="icon-text schedule grey s25"><i class="fa fa-map-marker"></i> <span
                                                    class="text"><?= strip_tags(html_entity_decode($address)); ?></span></span>


                                        <? if ($arItem['PROPERTIES']['METRO']['VALUE']): ?>
                                            <? foreach ($arItem['PROPERTIES']['METRO']['VALUE'] as $metro): ?>
                                                <div class="metro">
                                                    <i></i><?= $metro; ?>
                                                </div>
                                            <? endforeach; ?>
                                        <? endif; ?>
                                                                           </div>
                                    <div class="phone col-md-4">
                                        <? if ($arItem['PROPERTIES']['PHONE']['VALUE']) {
                                            foreach ($arItem['PROPERTIES']['PHONE']['VALUE'] as $phone):?>
                                                <div><span class="fa fa-phone"></span><a
                                                            href="tel:<?= str_replace(array(' ', ',', '-', '(', ')'), '', $phone); ?>"
                                                            class="black"><?= $phone; ?></a></div>
                                            <?endforeach;
                                        } ?>

                                        <? if ($arItem['PROPERTIES']['EMAIL']['VALUE']): ?>
                                            <div><span class="fa fa-envelope-o"></span> <a class="black"
                                                                                           href="mailto:<?= $arItem['PROPERTIES']['EMAIL']['VALUE'] ?>"><?= $arItem['PROPERTIES']['EMAIL']['VALUE'] ?></a>
                                            </div>
                                        <? endif; ?>

                                    </div>
                      
                                </div>
								
								 <? endif; ?>
                            <? endforeach; ?>
                        <? endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <? endif; ?>
</div>

