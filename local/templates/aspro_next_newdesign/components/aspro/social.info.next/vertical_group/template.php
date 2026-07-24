<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<div class="social-icons-vertical">
	<!-- noindex -->
	<ul>

<?if(!empty($arResult['SOCIAL_VK'])):?>
			<li class="vk">
				<a  onclick="ym(62259859,'reachGoal','VK'); return true;" href="<?=$arResult['SOCIAL_VK']?>" target="_blank" rel="nofollow" title="Группа Вконтакте">
					<span class="icon-soc"></span>Группа Вконтакте
				</a>
			</li>
	
		<?endif;?>
	<?if(!empty($arResult['SOCIAL_TELEGRAM'])):?>
			<li class="telegram">
				<a onclick="ym(62259859,'reachGoal','TELEGRAM_CANAL'); return true;" href="<?=$arResult['SOCIAL_TELEGRAM']?>" target="_blank" rel="nofollow" title="Канал в Telegram">
						<span class="icon-soc"></span>Канал в Telegram
				</a>
			</li>
		<?endif;?>
		
			<?if(!empty($arResult['SOCIAL_YOUTUBE'])):?>
			<li class="ytb">
				<a href="<?=$arResult['SOCIAL_YOUTUBE']?>" target="_blank" rel="nofollow" title="Канал в YouTube">
						<span class="icon-soc"></span>Канал в YouTube
				</a>
			</li>
		<?endif;?>
		
			<li class="rutube">
				<a href="https://rutube.ru/channel/41631334/" target="_blank" rel="nofollow" title="Канал в RUTUBE">
						<span class="icon-soc"></span>Канал в RUTUBE
				</a>
			</li>
	</ul>
	<!-- /noindex -->
</div>