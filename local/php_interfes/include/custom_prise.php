<?php
use \Bitrix\Main\Loader,
\Bitrix\Main\Localization\Loc;

\CModule::IncludeModule('catalog');
\CModule::IncludeModule('sale');

\Bitrix\Main\Loader::includeModule('highloadblock');
use Bitrix\Highloadblock as HL;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Sale\BasketItem;

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleBasketBeforeSaved',
    ['\PriceOverrideHandler', 'ApplyCustomPrice']
);

class PriceOverrideHandler
{
    public static function ApplyCustomPrice(\Bitrix\Main\Event $event)
    {

        $basket = $event->getParameter("ENTITY");

        $discountPrice = new CustomPriceLoaderHL;
        foreach ($discountPrice->LoadCustomPriceData() as $discountItem) {
            foreach ($basket as $basketItem) {
                if ($basketItem->getProductId() == $discountItem["UF_ID_PRODUCT"]) {
                    $basketItem->setField('BASE_PRICE', $discountItem["UF_PRICE_PRODUCT"]);
                }
            }
        }
    }
}

class CustomPriceLoaderHL
{
    public static function LoadCustomPriceData()
    {
        $priceData = HL\HighloadBlockTable::compileEntity('PriceBasket')->getDataClass();

        $customPriceDataQuery = $priceData::getList([
            "select" => ["UF_ID_PRODUCT", "UF_PRICE_PRODUCT"],
            "order" => ["ID" => "DESC"],
            "filter" => [
                "!=UF_PRICE_PRODUCT" => 0,
            ],
        ]);

        $arCustomPrice = [];

        while ($PriceOne = $customPriceDataQuery->fetch()) {
            $arCustomPrice[] = $PriceOne;
        }

        return $arCustomPrice;
    }
}