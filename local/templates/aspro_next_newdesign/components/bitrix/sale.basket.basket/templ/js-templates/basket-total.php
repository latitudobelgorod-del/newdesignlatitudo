<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 */

/* Панель итогов в новом дизайне (макет Figma «Чистовик», десктоп 20496:79858).
   Здесь разметку переписываем, а не подгоняем стилями: в макете другой набор
   строк и другой их порядок, чем печатал штатный шаблон («Товаров на» сверху,
   вес и объём мелочью в середине). Логика компонента завязана не на разметку,
   а на data-entity — сохранены все: basket-checkout-aligner (выравнивание
   блока), basket-total-price (анимация цены при пересчёте),
   basket-checkout-button (переход к оформлению) и секции купона.

   Поля приходят из mutator.php: WEIGHT_FORMATED, VOLUME_FORMATED, COUNT_ITEMS
   (сумма количеств), PRICE_FORMATED, PRICE_WITHOUT_DISCOUNT_FORMATED и
   DISCOUNT_PRICE_FORMATED (последние два — только когда скидка есть). */
?><script id="basket-total-template" type="text/html">
	<div class="basket-checkout-container nd-total" data-entity="basket-checkout-aligner">
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

		<div class="nd-total__card">
			<div class="nd-total__rows">
				{{#WEIGHT_FORMATED}}
					<div class="nd-total__row">
						<span class="nd-total__row-name"><?=Loc::getMessage('SBB_ND_WEIGHT')?></span>
						<span class="nd-total__row-value">{{{WEIGHT_FORMATED}}}</span>
					</div>
				{{/WEIGHT_FORMATED}}

				{{#VOLUME_FORMATED}}
					<div class="nd-total__row">
						<span class="nd-total__row-name"><?=Loc::getMessage('SBB_ND_VOLUME')?></span>
						<span class="nd-total__row-value">{{{VOLUME_FORMATED}}}</span>
					</div>
				{{/VOLUME_FORMATED}}

				<?// Со скидкой в строке «Товары» стоит сумма до скидки, без неё — итоговая.?>
				<div class="nd-total__row">
					<span class="nd-total__row-name"><?=Loc::getMessage('SBB_ND_ITEMS')?> ({{COUNT_ITEMS}})</span>
					<span class="nd-total__row-value">
						{{#DISCOUNT_PRICE_FORMATED}}{{{PRICE_WITHOUT_DISCOUNT_FORMATED}}}{{/DISCOUNT_PRICE_FORMATED}}
						{{^DISCOUNT_PRICE_FORMATED}}{{{PRICE_FORMATED}}}{{/DISCOUNT_PRICE_FORMATED}}
					</span>
				</div>

				{{#DISCOUNT_PRICE_FORMATED}}
					<div class="nd-total__row nd-total__row--discount">
						<span class="nd-total__row-name"><?=Loc::getMessage('SBB_BASKET_ITEM_ECONOMY')?></span>
						<span class="nd-total__row-value">{{{DISCOUNT_PRICE_FORMATED}}}</span>
					</div>
				{{/DISCOUNT_PRICE_FORMATED}}
			</div>

			<div class="nd-total__sum">
				<span class="nd-total__sum-title"><?=Loc::getMessage('SBB_TOTAL')?></span>
				<span class="nd-total__sum-value" data-entity="basket-total-price">{{{PRICE_FORMATED}}}</span>
			</div>

			<div class="basket-checkout-block basket-checkout-block-btn">
				<button class="btn btn-lg btn-default basket-btn-checkout nd-total__btn{{#DISABLE_CHECKOUT}} disabled{{/DISABLE_CHECKOUT}}"
					data-entity="basket-checkout-button">
					<?=Loc::getMessage('SBB_ORDER')?>
				</button>
			</div>

			<?// Чекбокс из макета: пока только помечает интерес к рассрочке визуально,
			   // в заказ ничего не передаёт — отдельной логики под него в компоненте нет.?>
			<label class="nd-total__check">
				<input type="checkbox" class="nd-total__check-input" name="ND_INSTALLMENT" value="Y">
				<span class="nd-total__check-box"></span>
				<span class="nd-total__check-text"><?=Loc::getMessage('SBB_ND_INSTALLMENT')?></span>
			</label>
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
