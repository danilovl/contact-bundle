<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Service\VCard;

final class VCardLabelMap
{
    public const array TYPE_TO_LABEL = [
        'CELL' => 'mobile',
        'IPHONE' => 'mobile',
        'MOBILE' => 'mobile',
        'HOME' => 'home',
        'WORK' => 'work',
        'FAX' => 'fax',
        'MAIN' => 'main',
        'PAGER' => 'pager',
        'OTHER' => 'other',
        'SCHOOL' => 'school'
    ];

    public const array LABEL_TO_TYPE = [
        'mobile' => 'CELL',
        'home' => 'HOME',
        'work' => 'WORK',
        'fax' => 'FAX',
        'main' => 'MAIN',
        'pager' => 'PAGER',
        'other' => 'OTHER'
    ];

    public const array IGNORED_TYPES = ['INTERNET', 'PREF', 'VOICE', 'X400', 'MSG', 'POSTAL', 'PARCEL', 'DOM', 'INTL', 'TEXT'];

    public const array MESSENGER_PROPERTIES = [
        'X-SKYPE' => 'skype',
        'X-SKYPE-USERNAME' => 'skype',
        'X-TELEGRAM' => 'telegram',
        'X-WHATSAPP' => 'whatsapp',
        'X-VIBER' => 'viber',
        'X-SIGNAL' => 'signal',
        'X-JABBER' => 'jabber',
        'X-ICQ' => 'icq',
        'X-AIM' => 'aim',
        'X-MSN' => 'msn',
        'X-YAHOO' => 'yahoo',
        'X-QQ' => 'qq',
        'X-GOOGLE-TALK' => 'google talk',
        'X-GTALK' => 'google talk'
    ];

    public static function normalizeCustomLabel(string $label): string
    {
        $matches = [];
        $isAppleLabel = preg_match('~^_\$!<(.+)>!\$_$~', $label, $matches) === 1;
        if ($isAppleLabel) {
            $label = $matches[1];
        }

        $label = mb_trim($label);
        $lowerLabel = mb_strtolower($label);

        return match ($lowerLabel) {
            'homepage' => 'website',
            'anniversary', 'spouse', 'child', 'mother', 'father', 'parent', 'brother', 'sister', 'friend', 'partner', 'manager', 'assistant', 'other', 'home', 'work' => $lowerLabel,
            default => $label
        };
    }

    /**
     * @param list<string> $types
     */
    public static function labelFromTypes(array $types): ?string
    {
        foreach ($types as $type) {
            $upperType = mb_strtoupper($type);
            $label = self::TYPE_TO_LABEL[$upperType] ?? null;
            if ($label !== null) {
                return $label;
            }
        }

        foreach ($types as $type) {
            $upperType = mb_strtoupper($type);
            $isIgnored = in_array($upperType, self::IGNORED_TYPES, true) || str_starts_with($upperType, 'X-');
            if (!$isIgnored) {
                return mb_strtolower($type);
            }
        }

        return null;
    }
}
