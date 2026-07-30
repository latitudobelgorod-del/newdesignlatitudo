<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 */
?><script id="basket-total-template" type="text/html">
	<div class="basket-checkout-container" data-entity="basket-checkout-aligner">
		<?
		if ($arParams['HIDE_COUPON'] !== 'Y')
		{
			?>
			<div class="basket-coupon-section">
				<div class="basket-coupon-block-field">
					<div class="basket-coupon-block-field-description">
						<?=Loc::getMessage('SBB_COUPON_ENTER')?>:
					</div>
					<div class="form">
						<div class="form-group" style="position: relative;">
							<input type="text" class="form-control" id="" placeholder="" data-entity="basket-coupon-input">
							<span class="basket-coupon-block-coupon-btn"></span>
						</div>
					</div>
				</div>
			</div>
			<?
		}
		?>
		

<div class="basket-checkout-section">
						<div class="basket-checkout-block-total-description">
							{{#LENGTH_FORMATED}}
								<?=Loc::getMessage('SBB_LENGTH')?>: {{{LENGTH_FORMATED}}}
								{{#SHOW_VAT}}<br>{{/SHOW_VAT}}
							{{/LENGTH_FORMATED}}
							
							{{#HEIGHT_FORMATED}}
								<?=Loc::getMessage('SBB_HEIGHT')?>: {{{HEIGHT_FORMATED}}}
								{{#SHOW_VAT}}<br>{{/SHOW_VAT}}
							{{/HEIGHT_FORMATED}}

						</div>

	
	

<div class="block_total_bottom">	

<div style="font-size: 12px;padding: 10px 7px;">
				<div class="basket-checkout-block basket-checkout-block-total" >
					<div class="basket-checkout-block-total-inner">
						<div class="basket-checkout-block-total-title"><?=Loc::getMessage('SBB_TOTAL')?>:</div>
						
								{{#DISCOUNT_PRICE_FORMATED}}
							<?/*
							<div class="basket-coupon-block-total-price-old">
								{{{PRICE_WITHOUT_DISCOUNT_FORMATED}}}
							</div>*/
							?>
						{{/DISCOUNT_PRICE_FORMATED}}
						<div class="basket-coupon-block-total-price-current" data-entity="basket-total-price">
							{{{PRICE_FORMATED}}}
						</div>

						
						
					</div>
							<div style="font-size: 12px;/*padding: 0px 7px;*/text-align:left;">
							{{#WEIGHT_FORMATED}}
							<span><?=Loc::getMessage('SBB_WEIGHT')?>:</span> 
							<span style="float:right;">{{{WEIGHT_FORMATED}}}{{#SHOW_VAT}}{{/SHOW_VAT}}</span>
							{{/WEIGHT_FORMATED}}
		</div>
<div style="font-size: 12px;/*padding: 0px 7px;*/text-align:left;">
                            {{#VOLUME_FORMATED}}
                            <span><?=Loc::getMessage('SBB_VOLUME')?>:</span> <span style="float:right;">{{{VOLUME_FORMATED}}}
                            {{/VOLUME_FORMATED}} </span>
							</div>
					
				</div>	
						
</div>
<div class="basket-checkout-block basket-checkout-block-btn">
					<button style="padding:15px 0;" class="btn btn-lg btn-default basket-btn-checkout{{#DISABLE_CHECKOUT}} disabled{{/DISABLE_CHECKOUT}}"
						data-entity="basket-checkout-button">
						<?=Loc::getMessage('SBB_ORDER')?>
					</button>
			</div>
			
<div class="basket-checkout-section-inner">
</div>	
</div>
	
		</div>
		
		
		
		

		
		
		
		
	
		
		
	
		<?
		if ($arParams['HIDE_COUPON'] !== 'Y')
		{
		?>
			<div class="basket-coupon-alert-section">
				<div class="basket-coupon-alert-inner">
					{{#COUPON_LIST}}
					<div class="basket-coupon-alert text-{{CLASS}}">
						<span class="basket-coupon-text">
							<strong>{{COUPON}}</strong> - <?=Loc::getMessage('SBB_COUPON')?> {{JS_CHECK_CODE}}
							{{#DISCOUNT_NAME}}({{{DISCOUNT_NAME}}}){{/DISCOUNT_NAME}}
						</span>
						<span class="close-link" data-entity="basket-coupon-delete" data-coupon="{{COUPON}}">
							<?=Loc::getMessage('SBB_DELETE')?>
						</span>
					</div>
					{{/COUPON_LIST}}
				</div>
			</div>
			<?
		}
		?>
	</div>
</script>