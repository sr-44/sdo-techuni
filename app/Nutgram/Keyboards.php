<?php

namespace App\Nutgram;

use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardRemove;

class Keyboards
{

    public static function mainMenu(Nutgram $bot): ReplyKeyboardMarkup
    {
        $markup = new ReplyKeyboardMarkup(true);
        $markup->addRow(InlineKeyboardButton::make(
            $bot->__('kbd.login')), InlineKeyboardButton::make(
            $bot->__('kbd.lang'))
        );
        $markup->addRow(InlineKeyboardButton::make(
            $bot->__('kbd.about')
        ), InlineKeyboardButton::make($bot->__('kbd.feedback')));
        return $markup;
    }

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

    /**
     * @throws InvalidArgumentException
     */
    public static function actionsKeyboards(Nutgram $bot): ReplyKeyboardMarkup
    {
        $markup = new ReplyKeyboardMarkup(true);

        $markup->addRow(InlineKeyboardButton::make(
            $bot->__('kbd.show.rating')
        ), InlineKeyboardButton::make(
            $bot->__('kbd.show.info')
        ));
        if ($bot->getUserData('sessions')) {
            $markup->addRow(InlineKeyboardButton::make(
                $bot->__('kbd.show.sessions')
            ));
        }
        $markup->addRow(InlineKeyboardButton::make(
            $bot->__('kbd.logout')
        ));
        return $markup;

    }

    public static function adminDashboard(): InlineKeyboardMarkup
    {
        $markup = new InlineKeyboardMarkup();
        $markup->addRow(InlineKeyboardButton::make('Отправить рассылку', callback_data: 'bulk_message'));
        $markup->addRow(InlineKeyboardButton::make('Отмена', callback_data: 'cancel'));
        return $markup;
    }

    public static function bulkMessage(): InlineKeyboardMarkup
    {
        $markup = new InlineKeyboardMarkup();
        $markup->addRow(InlineKeyboardButton::make('Переслать', callback_data: 'forward'));
        $markup->addRow(InlineKeyboardButton::make('Отправить', callback_data: 'copy'));
        $markup->addRow(InlineKeyboardButton::make('Отмена', callback_data: 'cancel'));
        return $markup;
    }

    public static function cancelButton(Nutgram $bot): ReplyKeyboardMarkup
    {
        $markup = new ReplyKeyboardMarkup(true);
        $markup->addRow(KeyboardButton::make($bot->__('kbd.cancel')));
        return $markup;
    }
}