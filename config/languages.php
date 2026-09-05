<?php

/*
|--------------------------------------------------------------------------
| Language Registry
|--------------------------------------------------------------------------
|
| Every locale selectable from Business Settings -> Localization. Adding a
| new language is a config-only change: register it here, then (whenever
| someone wants real content instead of the automatic English fallback)
| add a lang/{code}/*.php file per module. No other code changes needed.
|
| - name          English display name
| - native_name   Name in the language's own script
| - direction     'ltr' or 'rtl'
| - fallback      Always 'en' (Laravel's own fallback_locale mechanism
|                  resolves any missing key against this automatically)
|
*/

return [
    'en' => ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'fallback' => 'en'],
    'ur' => ['name' => 'Urdu', 'native_name' => 'اردو', 'direction' => 'rtl', 'fallback' => 'en'],
    'ar' => ['name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'fallback' => 'en'],
    'fa' => ['name' => 'Persian', 'native_name' => 'فارسی', 'direction' => 'rtl', 'fallback' => 'en'],
    'he' => ['name' => 'Hebrew', 'native_name' => 'עברית', 'direction' => 'rtl', 'fallback' => 'en'],
    'ps' => ['name' => 'Pashto', 'native_name' => 'پښتو', 'direction' => 'rtl', 'fallback' => 'en'],
    'sd' => ['name' => 'Sindhi', 'native_name' => 'سنڌي', 'direction' => 'rtl', 'fallback' => 'en'],

    'bal' => ['name' => 'Balochi', 'native_name' => 'بلوچی', 'direction' => 'rtl', 'fallback' => 'en'],

    'hi' => ['name' => 'Hindi', 'native_name' => 'हिन्दी', 'direction' => 'ltr', 'fallback' => 'en'],
    'bn' => ['name' => 'Bengali', 'native_name' => 'বাংলা', 'direction' => 'ltr', 'fallback' => 'en'],
    'pa-IN' => ['name' => 'Punjabi (India)', 'native_name' => 'ਪੰਜਾਬੀ', 'direction' => 'ltr', 'fallback' => 'en'],
    'pa-PK' => ['name' => 'Punjabi (Pakistan)', 'native_name' => 'پنجابی', 'direction' => 'rtl', 'fallback' => 'en'],
    'ta' => ['name' => 'Tamil', 'native_name' => 'தமிழ்', 'direction' => 'ltr', 'fallback' => 'en'],
    'te' => ['name' => 'Telugu', 'native_name' => 'తెలుగు', 'direction' => 'ltr', 'fallback' => 'en'],
    'mr' => ['name' => 'Marathi', 'native_name' => 'मराठी', 'direction' => 'ltr', 'fallback' => 'en'],
    'gu' => ['name' => 'Gujarati', 'native_name' => 'ગુજરાતી', 'direction' => 'ltr', 'fallback' => 'en'],
    'kn' => ['name' => 'Kannada', 'native_name' => 'ಕನ್ನಡ', 'direction' => 'ltr', 'fallback' => 'en'],
    'ml' => ['name' => 'Malayalam', 'native_name' => 'മലയാളം', 'direction' => 'ltr', 'fallback' => 'en'],
    'ne' => ['name' => 'Nepali', 'native_name' => 'नेपाली', 'direction' => 'ltr', 'fallback' => 'en'],
    'si' => ['name' => 'Sinhala', 'native_name' => 'සිංහල', 'direction' => 'ltr', 'fallback' => 'en'],

    'tr' => ['name' => 'Turkish', 'native_name' => 'Türkçe', 'direction' => 'ltr', 'fallback' => 'en'],
    'fr' => ['name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'fallback' => 'en'],
    'de' => ['name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'fallback' => 'en'],
    'es' => ['name' => 'Spanish', 'native_name' => 'Español', 'direction' => 'ltr', 'fallback' => 'en'],
    'pt' => ['name' => 'Portuguese', 'native_name' => 'Português', 'direction' => 'ltr', 'fallback' => 'en'],
    'it' => ['name' => 'Italian', 'native_name' => 'Italiano', 'direction' => 'ltr', 'fallback' => 'en'],
    'nl' => ['name' => 'Dutch', 'native_name' => 'Nederlands', 'direction' => 'ltr', 'fallback' => 'en'],
    'ru' => ['name' => 'Russian', 'native_name' => 'Русский', 'direction' => 'ltr', 'fallback' => 'en'],
    'uk' => ['name' => 'Ukrainian', 'native_name' => 'Українська', 'direction' => 'ltr', 'fallback' => 'en'],
    'el' => ['name' => 'Greek', 'native_name' => 'Ελληνικά', 'direction' => 'ltr', 'fallback' => 'en'],
    'pl' => ['name' => 'Polish', 'native_name' => 'Polski', 'direction' => 'ltr', 'fallback' => 'en'],
    'ro' => ['name' => 'Romanian', 'native_name' => 'Română', 'direction' => 'ltr', 'fallback' => 'en'],
    'cs' => ['name' => 'Czech', 'native_name' => 'Čeština', 'direction' => 'ltr', 'fallback' => 'en'],
    'hu' => ['name' => 'Hungarian', 'native_name' => 'Magyar', 'direction' => 'ltr', 'fallback' => 'en'],
    'sv' => ['name' => 'Swedish', 'native_name' => 'Svenska', 'direction' => 'ltr', 'fallback' => 'en'],
    'da' => ['name' => 'Danish', 'native_name' => 'Dansk', 'direction' => 'ltr', 'fallback' => 'en'],
    'no' => ['name' => 'Norwegian', 'native_name' => 'Norsk', 'direction' => 'ltr', 'fallback' => 'en'],
    'fi' => ['name' => 'Finnish', 'native_name' => 'Suomi', 'direction' => 'ltr', 'fallback' => 'en'],

    'zh-CN' => ['name' => 'Chinese (Simplified)', 'native_name' => '简体中文', 'direction' => 'ltr', 'fallback' => 'en'],
    'zh-TW' => ['name' => 'Chinese (Traditional)', 'native_name' => '繁體中文', 'direction' => 'ltr', 'fallback' => 'en'],
    'ja' => ['name' => 'Japanese', 'native_name' => '日本語', 'direction' => 'ltr', 'fallback' => 'en'],
    'ko' => ['name' => 'Korean', 'native_name' => '한국어', 'direction' => 'ltr', 'fallback' => 'en'],
    'th' => ['name' => 'Thai', 'native_name' => 'ไทย', 'direction' => 'ltr', 'fallback' => 'en'],
    'vi' => ['name' => 'Vietnamese', 'native_name' => 'Tiếng Việt', 'direction' => 'ltr', 'fallback' => 'en'],
    'id' => ['name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia', 'direction' => 'ltr', 'fallback' => 'en'],
    'ms' => ['name' => 'Malay', 'native_name' => 'Bahasa Melayu', 'direction' => 'ltr', 'fallback' => 'en'],
];
