<?php
/**
 * Сервис единственного сквозного баннера сайта.
 *
 * На сайте всегда ровно один текущий баннер: контент-менеджер не заводит новую
 * запись, а правит существующую. Поэтому здесь нет ни выбора из нескольких, ни
 * приоритетов, ни ротации — только «показывать ли сейчас вот этот».
 *
 * Хранилище — инфоблок `nd_banner` (создаётся local/tools/nd_banner_install.php).
 * Поля: LINK, HOME_BANNER, CATALOG_BANNER, CATALOG_SECTIONS.
 *
 * ДАТЫ ПОКАЗА — штатные «Начало/Окончание активности» элемента, а не свои
 * свойства. Так требует условие про кеш: у своих свойств Битрикс не знает, что
 * баннер протух, и просроченный продолжал бы висеть из кеша. У штатных дат
 * движок сам чистит теги, когда активность меняется по времени, а выборка идёт
 * с ACTIVE_DATE => 'Y' — то есть отбор по датам делает SQL, а не мы.
 *
 * Подключается из local/init.php. См. также latitudo_brand_weight.php — тот же
 * приём: статический класс + свой кеш с тегом инфоблока.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;

class LatitudoBanner
{
    /** Символьный код инфоблока с единственной записью баннера. */
    public const IBLOCK_CODE = 'nd_banner';

    /** Инфоблок каталога — к его разделам привязан баннер каталога. */
    public const CATALOG_IBLOCK = 19;

    /**
     * Кеш короткий: он лишь снимает нагрузку на повторные хиты. Правку баннера
     * ловит тег инфоблока, а срок показа — штатная активность, но между
     * моментом «дата прошла» и сбросом тега агентом есть зазор, и пять минут —
     * приемлемая его величина.
     */
    private const CACHE_TTL = 300;
    private const CACHE_DIR = '/nd/banner';

    /** @var array<string,mixed>|null|false Память процесса: false — ещё не читали. */
    private static $record = false;

    /** @var int|null */
    private static $iblockId = null;

    public static function iblockId(): int
    {
        if (self::$iblockId !== null) {
            return self::$iblockId;
        }

        self::$iblockId = 0;
        if (!\CModule::IncludeModule('iblock')) {
            return 0;
        }

        $row = \CIBlock::GetList([], [
            'CODE' => self::IBLOCK_CODE,
            'CHECK_PERMISSIONS' => 'N',
        ])->Fetch();

        self::$iblockId = $row ? (int) $row['ID'] : 0;

        return self::$iblockId;
    }

    /**
     * Текущая запись баннера или null.
     *
     * Отбор с ACTIVE => 'Y' и ACTIVE_DATE => 'Y': и выключенный, и не наступивший,
     * и просроченный баннер отсекаются на уровне запроса.
     *
     * @return array<string,mixed>|null
     */
    public static function record(): ?array
    {
        if (self::$record !== false) {
            return self::$record;
        }

        self::$record = null;

        $iblockId = self::iblockId();
        if (!$iblockId) {
            return null;
        }

        $cache = Cache::createInstance();
        $key = 'nd_banner_' . $iblockId;

        if ($cache->initCache(self::CACHE_TTL, $key, self::CACHE_DIR)) {
            self::$record = $cache->getVars()['RECORD'] ?? null;

            return self::$record;
        }

        $cache->startDataCache();

        $taggedCache = Application::getInstance()->getTaggedCache();
        $taggedCache->startTagCache(self::CACHE_DIR);
        $taggedCache->registerTag('iblock_id_' . $iblockId);

        $record = self::load($iblockId);

        $taggedCache->endTagCache();
        $cache->endDataCache(['RECORD' => $record]);

        self::$record = $record;

        return self::$record;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function load(int $iblockId): ?array
    {
        $row = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID', 'NAME']
        )->Fetch();

        if (!$row) {
            return null;
        }

        $record = [
            'ID' => (int) $row['ID'],
            'NAME' => (string) $row['NAME'],
            'LINK' => '',
            'HOME_FILE' => 0,
            'CATALOG_FILE' => 0,
            'SECTIONS' => [],
        ];

        /* Свойства читаем через GetProperty, а не GetPropertyValues: последний
           в обычном режиме отдаёт значения, ПРОНУМЕРОВАННЫЕ ID свойства, а не
           его кодом — обращение по 'LINK' молча возвращало пусто. Здесь один
           элемент, так что перебор строк ничего не стоит, зато множественное
           CATALOG_SECTIONS приходит естественно, отдельной строкой на значение. */
        $rs = \CIBlockElement::GetProperty(
            $iblockId,
            $record['ID'],
            'sort',
            'asc',
            ['ACTIVE' => 'Y']
        );

        while ($p = $rs->Fetch()) {
            $value = $p['VALUE'];
            if ($value === null || $value === '' || $value === false) {
                continue;
            }

            switch ($p['CODE']) {
                case 'LINK':
                    $record['LINK'] = trim((string) $value);
                    break;
                case 'HOME_BANNER':
                    $record['HOME_FILE'] = (int) $value;
                    break;
                case 'CATALOG_BANNER':
                    $record['CATALOG_FILE'] = (int) $value;
                    break;
                case 'CATALOG_SECTIONS':
                    $sectionId = (int) $value;
                    if ($sectionId > 0) {
                        $record['SECTIONS'][] = $sectionId;
                    }
                    break;
            }
        }

        $record['SECTIONS'] = array_values(array_unique($record['SECTIONS']));

        return $record;
    }

    /**
     * Баннер главной страницы или null, если показывать нечего.
     *
     * @return array<string,mixed>|null
     */
    public static function homeBanner(): ?array
    {
        $record = self::record();
        if (!$record || !$record['HOME_FILE']) {
            return null;
        }

        return self::view($record, $record['HOME_FILE']);
    }

    /**
     * Баннер каталога для текущего раздела или null.
     *
     * Пустой список разделов означает «во всех разделах» — так проще запускать
     * сквозной баннер: не нужно отмечать все разделы руками (просьба Ирины,
     * 3 сентября 2026). Если разделы выбраны, баннер показывается в них и в их
     * подразделах, а на корне каталога — нет.
     *
     * @return array<string,mixed>|null
     */
    public static function catalogBanner($sectionId): ?array
    {
        $record = self::record();
        if (!$record || !$record['CATALOG_FILE']) {
            return null;
        }

        if (!self::sectionMatches($record['SECTIONS'], (int) $sectionId)) {
            return null;
        }

        return self::view($record, $record['CATALOG_FILE']);
    }

    /**
     * Подходит ли текущий раздел. Наследование вниз считаем по цепочке
     * родителей текущего раздела: если среди неё (или сам раздел) есть
     * отмеченный — показываем. GetNavChain даёт именно её и учитывает любую
     * глубину вложенности.
     *
     * @param int[] $selected
     */
    private static function sectionMatches(array $selected, int $sectionId): bool
    {
        if (!$selected) {
            return true;                     // разделы не выбраны — значит везде
        }
        if ($sectionId <= 0) {
            return false;                    // корень каталога, а разделы заданы
        }
        if (in_array($sectionId, $selected, true)) {
            return true;
        }

        $chain = \CIBlockSection::GetNavChain(self::CATALOG_IBLOCK, $sectionId, ['ID'], true);
        foreach ($chain as $parent) {
            if (in_array((int) $parent['ID'], $selected, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Данные для шаблона. Ничего не экранируем здесь — это делает разметка,
     * иначе экранированное значение легко экранировать второй раз.
     *
     * @param array<string,mixed> $record
     * @return array<string,mixed>|null
     */
    private static function view(array $record, int $fileId): ?array
    {
        $file = \CFile::GetFileArray($fileId);
        if (!$file || empty($file['SRC'])) {
            return null;                     // файл удалили — баннера нет
        }

        return [
            'ID' => $record['ID'],
            'NAME' => $record['NAME'],
            'LINK' => self::safeLink($record['LINK']),
            'IMAGE_ID' => $fileId,
            'IMAGE_SRC' => $file['SRC'],
            'IMAGE_WIDTH' => (int) $file['WIDTH'],
            'IMAGE_HEIGHT' => (int) $file['HEIGHT'],
        ];
    }

    /**
     * Пропускаем только внутренние адреса и http(s). Схемы вроде javascript:
     * и data: отбрасываем: ссылку правят из админки, но XSS через неё нам не
     * нужен, а рабочих сценариев с такими схемами у баннера нет.
     */
    private static function safeLink(string $link): string
    {
        if ($link === '') {
            return '';
        }
        if ($link[0] === '/' || $link[0] === '#') {
            return $link;
        }

        $scheme = strtolower((string) parse_url($link, PHP_URL_SCHEME));

        return ($scheme === 'http' || $scheme === 'https') ? $link : '';
    }

    /**
     * Разметка баннера. Держим её здесь, а не в двух шаблонах: правила «есть
     * ссылка — оборачиваем в <a>, нет — только картинка» и экранирование
     * одинаковы и на главной, и в каталоге.
     *
     * @param array<string,mixed> $banner
     */
    public static function render(array $banner, string $class): string
    {
        $img = '<img src="' . htmlspecialcharsbx($banner['IMAGE_SRC']) . '"'
            . ' alt="' . htmlspecialcharsbx($banner['NAME']) . '"'
            . ($banner['IMAGE_WIDTH'] ? ' width="' . $banner['IMAGE_WIDTH'] . '"' : '')
            . ($banner['IMAGE_HEIGHT'] ? ' height="' . $banner['IMAGE_HEIGHT'] . '"' : '')
            . ' loading="lazy">';

        $inner = $banner['LINK'] !== ''
            ? '<a href="' . htmlspecialcharsbx($banner['LINK']) . '">' . $img . '</a>'
            : $img;

        return '<div class="' . htmlspecialcharsbx($class) . '">' . $inner . '</div>';
    }

    /**
     * Тег кеша инфоблока баннера. Шаблоны, которые печатают баннер внутри
     * своего кеша (список товаров), должны его зарегистрировать — иначе
     * правка баннера не долетит до выдачи, пока не истечёт кеш компонента.
     */
    public static function registerCacheTag(): void
    {
        $iblockId = self::iblockId();
        if ($iblockId && defined('BX_COMP_MANAGED_CACHE')) {
            Application::getInstance()->getTaggedCache()->registerTag('iblock_id_' . $iblockId);
        }
    }
}
