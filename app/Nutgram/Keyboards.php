<?php

namespace App\Nutgram;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;

class Keyboards
{
    public static function selectLanguage(): InlineKeyboardMarkup
    {
        $markup = new InlineKeyboardMarkup();

        $markup->addRow(InlineKeyboardButton::make('Русский🇷🇺', callback_data: 'lang_ru'),
            InlineKeyboardButton::make('Тоҷикӣ🇹🇯', callback_data: 'lang_tg'),
            InlineKeyboardButton::make('English🇬🇧', callback_data: 'lang_en'));
        return $markup;
    }

    public static function removeKeyboards(): ReplyKeyboardRemove
    {
        return new ReplyKeyboardRemove(true);
    }

    public static function actionsKeyboards(Nutgram $bot): ReplyKeyboardMarkup
    {
        $markup = new ReplyKeyboardMarkup(true);

        $markup->addRow(InlineKeyboardButton::make(
            $bot->__('kbd.show.rating')
        ));
        $markup->addRow(InlineKeyboardButton::make(
            $bot->__('kbd.show.info')
        ));
        $markup->addRow(InlineKeyboardButton::make(
            $bot->__('kbd.logout')
        ));
        return $markup;

    }
}