<?php
use \Bitrix\Main\Loader,
\Bitrix\Main\Localization\Loc;

\Bitrix\Main\Loader::IncludeModule('catalog');
\Bitrix\Main\Loader::IncludeModule('sale');
\Bitrix\Main\Loader::IncludeModule('main');

\Bitrix\Main\Loader::includeModule('highloadblock');
use Bitrix\Highloadblock as HL;
use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Sale\BasketItem;
use Bitrix\Main\Data\Cache;

EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleBasketItemRefreshData',
    ['\PriceOverrideHandler', 'ApplyCustomPrice']
);

class PriceOverrideHandler
{
    public static function ApplyCustomPrice(\Bitrix\Main\Event $event)
    {
        $basket = $event->getParameter("ENTITY");
        $basket->setField("PRODUCT_PROVIDER_CLASS", "CustomPrice");
    }
}

class CustomPrice extends CCatalogProductProvider
{
    public static function GetProductData($arParams)
    {
        $arResult = parent::GetProductData($arParams);

        $cache = new \CPHPCache();
        $cacheTime = 1800;
        $cacheId = 'custom_price_data';
        $cachePath = '/custom_price/';

        if ($cache->InitCache($cacheTime, $cacheId, $cachePath)) {
            $vars = $cache->GetVars();
            $customPriceData = $vars['customPriceData'];
        } elseif ($cache->StartDataCache()) {
            $customPriceData = new CustomPriceLoaderHL;
            $cache->EndDataCache(['customPriceData' => $customPriceData->LoadCustomPriceData()]);
        }

        foreach ($customPriceData as $discountItem) {
            if ($arParams["PRODUCT_ID"] == $discountItem["UF_ID_PRODUCT"]) {
                $salePrice = $discountItem['UF_PRICE_PRODUCT']; // Индивидуальная цена
                $arResult = [
                    'BASE_PRICE' => $salePrice,
                ] + $arResult;
                break;
            }
        }

        return $arResult;
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
