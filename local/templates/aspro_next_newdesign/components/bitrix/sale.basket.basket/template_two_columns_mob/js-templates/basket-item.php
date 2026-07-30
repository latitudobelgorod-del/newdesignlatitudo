<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $mobileColumns
 * @var array $arParams
 * @var string $templateFolder
 */

$usePriceInAdditionalColumn = in_array('PRICE', $arParams['COLUMNS_LIST']) && $arParams['PRICE_DISPLAY_MODE'] === 'Y';
$useSumColumn = in_array('SUM', $arParams['COLUMNS_LIST']);
$useActionColumn = in_array('DELETE', $arParams['COLUMNS_LIST']);

$restoreColSpan = 2 + $usePriceInAdditionalColumn + $useSumColumn + $useActionColumn;

$positionClassMap = array(
	'left' => 'basket-item-label-left',
	'center' => 'basket-item-label-center',
	'right' => 'basket-item-label-right',
	'bottom' => 'basket-item-label-bottom',
	'middle' => 'basket-item-label-middle',
	'top' => 'basket-item-label-top'
);

$discountPositionClass = '';
if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y' && !empty($arParams['DISCOUNT_PERCENT_POSITION']))
{
	foreach (explode('-', $arParams['DISCOUNT_PERCENT_POSITION']) as $pos)
	{
		$discountPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
	}
}

$labelPositionClass = '';
if (!empty($arParams['LABEL_PROP_POSITION']))
{
	foreach (explode('-', $arParams['LABEL_PROP_POSITION']) as $pos)
	{
		$labelPositionClass .= isset($positionClassMap[$pos]) ? ' '.$positionClassMap[$pos] : '';
	}
}
?>
<script id="basket-item-template" type="text/html">
	<tr class="basket-items-list-item-container{{#SHOW_RESTORE}} basket-items-list-item-container-expend{{/SHOW_RESTORE}}"
		id="basket-item-{{ID}}" data-entity="basket-item" data-id="{{ID}}">
		{{#SHOW_RESTORE}}
			<td class="basket-items-list-item-notification" colspan="<?=$restoreColSpan?>">
				<div class="basket-items-list-item-notification-inner basket-items-list-item-notification-removed" id="basket-item-height-aligner-{{ID}}">
					{{#SHOW_LOADING}}
						<div class="basket-items-list-item-overlay"></div>
					{{/SHOW_LOADING}}
					<div class="basket-items-list-item-removed-container">
						<div>
							<?=Loc::getMessage('SBB_GOOD_CAP')?> <strong>{{NAME}}</strong> <?=Loc::getMessage('SBB_BASKET_ITEM_DELETED')?>.
						</div>
						<div class="basket-items-list-item-removed-block">
							<a href="javascript:void(0)" data-entity="basket-item-restore-button">
								<?=Loc::getMessage('SBB_BASKET_ITEM_RESTORE')?>
							</a>
							<span class="basket-items-list-item-clear-btn" data-entity="basket-item-close-restore-button"></span>
						</div>
					</div>
				</div>
			</td>
		{{/SHOW_RESTORE}}
		{{^SHOW_RESTORE}}
			<td class="basket-items-list-item-descriptions">
				<div class="basket-items-list-item-descriptions-inner" id="basket-item-height-aligner-{{ID}}">
			
						<?
					if (in_array('PREVIEW_PICTURE', $arParams['COLUMNS_LIST']))
					{
						?>
										{{#COLUMN_LIST}}
												{{#IS_IMAGE}}
													<div data-event="jqm" class="basket-item-property-custom basket-item-property-custom-photo
														"
														data-entity="basket-item-property">
														
														<div class="basket-item-property-custom-value" >
															{{#VALUE}}
																<span>
																	<img class="gallery basket-item-custom-block-photo-item"
																		src="{{{IMAGE_SRC}}}" data-image-index="{{INDEX}}"
																		data-column-property-code="{{CODE}}">
																</span>
															{{/VALUE}}
														</div>
													</div>
												{{/IS_IMAGE}}

									
											{{/COLUMN_LIST}}
						
						
																	
														
														
			
						<?
					}
					?>
					
			
			
					<div class="basket-item-block-info">
						
				
						
						<?
						if (isset($mobileColumns['DELETE']))
						{
							?>
							<span class="basket-item-actions-remove" data-entity="basket-item-delete"></span>
							<?
						}
						?>
						<h2 class="basket-item-info-name">
							{{#DETAIL_PAGE_URL}}
								<a href="{{DETAIL_PAGE_URL}}" class="basket-item-info-name-link">
							{{/DETAIL_PAGE_URL}}
	
							<span data-entity="basket-item-name">{{NAME}}</span>

							{{#DETAIL_PAGE_URL}}
								</a>
							{{/DETAIL_PAGE_URL}}
						</h2>
						
							<div class="" style="display:flex;justify-content:space-between;" >
									<?/*счетчик*/?>
				<div class="basket-item-block-amount{{#NOT_AVAILABLE}} disabled{{/NOT_AVAILABLE}}"
					data-entity="basket-item-quantity-block">
					<span class="basket-item-amount-btn-minus" data-entity="basket-item-quantity-minus"></span>
					<div class="basket-item-amount-filed-block">
						<input type="text" class="basket-item-amount-filed" value="{{QUANTITY}}"
							{{#NOT_AVAILABLE}} disabled="disabled"{{/NOT_AVAILABLE}}
							data-value="{{QUANTITY}}" data-entity="basket-item-quantity-field"
							id="basket-item-quantity-{{ID}}">
					</div>
					<span class="basket-item-amount-btn-plus" data-entity="basket-item-quantity-plus"></span>
					<div class="basket-item-amount-field-description">
						<?
						if ($arParams['PRICE_DISPLAY_MODE'] === 'Y')
						{
							?>
							<?//{{MEASURE_TEXT}}?>
							<?
						}
						else
						{
							?>
							{{#SHOW_PRICE_FOR}}
								{{MEASURE_RATIO}} {{MEASURE_TEXT}} =
								<span id="basket-item-price-{{ID}}">{{{PRICE_FORMATED}}}</span>
							{{/SHOW_PRICE_FOR}}
							{{^SHOW_PRICE_FOR}}
								{{MEASURE_TEXT}}
							{{/SHOW_PRICE_FOR}}
							<?
						}
						?>
					</div>
					{{#SHOW_LOADING}}
						<div class="basket-items-list-item-overlay"></div>
					{{/SHOW_LOADING}}
				</div>
						<?/*счетчик*/?>
						
						
						
									<div class="basket-item-block-price">
						{{#SHOW_DISCOUNT_PRICE}}
							<div class="basket-item-price-old">
								<span class="basket-item-price-old-text" id="basket-item-sum-price-old-{{ID}}">
									{{{SUM_FULL_PRICE_FORMATED}}}
								</span>
										<?if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y')
	{
	?>
	{{#DISCOUNT_PRICE_PERCENT}}
<div class=" <?=$discountPositionClass?>" style="
    display: inline-block;
    background: #ffd02e;
    color:#1d2029;
    padding: 0 8px;
    line-height: 21px;
    margin: 3px 0;
    border-radius: 3px;
">
									-{{DISCOUNT_PRICE_PERCENT_FORMATED}}
									</div>
								{{/DISCOUNT_PRICE_PERCENT}}
								<?
							}						?>
							</div>
						{{/SHOW_DISCOUNT_PRICE}}

						<div class="basket-item-price-current">
							<span class="basket-item-price-current-text" id="basket-item-sum-price-{{ID}}">
								{{{SUM_PRICE_FORMATED}}}
							</span>
						</div>

				

						
									
						
						
						
						
						
				
						
						
						
						
						{{#SHOW_LOADING}}
							<div class="basket-items-list-item-overlay"></div>
						{{/SHOW_LOADING}}
					</div>
						
						
				
				</div>
						{{#NOT_AVAILABLE}}
							<div class="basket-items-list-item-warning-container">
								<div class="alert alert-warning text-center">
									<?=Loc::getMessage('SBB_BASKET_ITEM_NOT_AVAILABLE')?>.
								</div>
							</div>
						{{/NOT_AVAILABLE}}
						{{#DELAYED}}
							<div class="basket-items-list-item-warning-container">
								<div class="alert alert-warning text-center">
									<?=Loc::getMessage('SBB_BASKET_ITEM_DELAYED')?>.
									<a href="javascript:void(0)" data-entity="basket-item-remove-delayed">
										<?=Loc::getMessage('SBB_BASKET_ITEM_REMOVE_DELAYED')?>
									</a>
								</div>
							</div>
						{{/DELAYED}}
						{{#WARNINGS.length}}
							<div class="basket-items-list-item-warning-container">
								<div class="alert alert-warning alert-dismissable" data-entity="basket-item-warning-node">
									<span class="close" data-entity="basket-item-warning-close">&times;</span>
										{{#WARNINGS}}
											<div data-entity="basket-item-warning-text">{{{.}}}</div>
										{{/WARNINGS}}
								</div>
							</div>
						{{/WARNINGS.length}}
						
					</div>
					{{#SHOW_LOADING}}
						<div class="basket-items-list-item-overlay"></div>
					{{/SHOW_LOADING}}
				</div>
				
			
		
				
			</td>
			<?
			if ($usePriceInAdditionalColumn)
			{
				?>
				<td class="basket-items-list-item-price basket-items-list-item-price-for-one<?=(!isset($mobileColumns['PRICE']) ? ' hidden-xs' : '')?>">
			
				</td>
				<?
			}
			?>

			<?

			if ($useActionColumn)
			{
				?>
				<td class="basket-items-list-item-remove ">
				
				</td>
				<?
			}
			?>
		{{/SHOW_RESTORE}}
	</tr>
</script>