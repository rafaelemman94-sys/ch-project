<?php
namespace App\Services;

final class LegislativeContentFormatter
{
    public static function toSafeHtml(string $content): string
    {
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = (string)preg_replace('~<(script|style)\b[^>]*>.*?</\1\s*>~is', '', $content);
        $content = (string)preg_replace('~<!--\s*page[-_ ]?break\s*-->~i', '', $content);
        $content = (string)preg_replace('~<(div|p)\b[^>]*(?:class|style)\s*=\s*["\'][^"\']*(?:page[-_ ]?break|page-break-(?:before|after|inside)\s*:|break-before\s*:\s*page)[^"\']*["\'][^>]*>\s*</\1\s*>~is', '', $content);
        $content = (string)preg_replace('~<\s*br\b[^>]*\/?>~i', '<br>', $content);
        $content = (string)preg_replace('~<\s*\/?\s*(p|div|h[1-6])\b[^>]*>~i', '<br>', $content);
        $content = strip_tags($content, '<br><strong><b><em><i><u><ul><ol><li>');
        $content = (string)preg_replace_callback(
            '~<\s*(\/?)\s*(br|strong|b|em|i|u|ul|ol|li)\b[^>]*>~i',
            static fn(array $match): string => strtolower($match[2]) === 'br' ? '<br>' : '<' . $match[1] . strtolower($match[2]) . '>',
            $content
        );
        $content = (string)preg_replace('/\s+/u', ' ', $content);
        $content = (string)preg_replace('~(?:<br>\s*){3,}~i', '<br><br>', $content);
        $content = (string)preg_replace('~^(?:\s*<br>\s*)+|(?:\s*<br>\s*)+$~i', '', $content);

        return trim($content);
    }

    public static function toPlainText(string $content): string
    {
        return trim(strip_tags(str_ireplace('<br>', "\n", self::toSafeHtml($content))));
    }
}