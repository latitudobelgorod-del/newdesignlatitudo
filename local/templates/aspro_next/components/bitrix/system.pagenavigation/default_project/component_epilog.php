<?use Bitrix\Main\Web\Uri;
$context = \Bitrix\Main\Application::getInstance()->getContext();
$uri = new Uri($context->getRequest()->getRequestUri());
$uri->deleteParams(['PAGEN_' . $arResult['NavNum']]);

$protocol = $context->getRequest()->isHttps() ? 'https://' : 'http://';
$href = $protocol . $context->getServer()->getHttpHost() . $uri->getUri();

$APPLICATION->SetPageProperty('canonical', $href);?>