<?php

function latitudoRedirectProjectToPrimarySection(array $element, string $detailTemplate): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        return;
    }

    // Aspro keeps the primary section separately when it returns all section IDs.
    $element['IBLOCK_SECTION_ID'] = $element['IBLOCK_SECTION_ID_SELECTED']
        ?? $element['IBLOCK_SECTION_ID'];
    if (empty($element['CODE']) || empty($element['IBLOCK_SECTION_ID']) || is_array($element['IBLOCK_SECTION_ID'])) {
        return;
    }

    $target = CIBlock::ReplaceDetailUrl($detailTemplate, $element, false, 'E');
    $request = parse_url($_SERVER['REQUEST_URI'] ?? '');
    if (!preg_match('~^/projects/(?:[^/?#]+/){2,}$~D', $target)
        || !isset($request['path'])
        || rawurldecode($request['path']) === rawurldecode($target)
    ) {
        return;
    }

    // Keep the current regional host and any campaign or design parameters.
    $query = $request['query'] ?? '';
    LocalRedirect($target . ($query !== '' ? '?' . $query : ''), false, '301 Moved Permanently');
}
