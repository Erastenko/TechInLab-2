<?php

use \Bitrix\Main\Loader,
\Bitrix\Main\Localization\Loc;

Loader::includeModule('catalog');
Loader::includeModule('sale');

\Bitrix\Main\Loader::includeModule('highloadblock');
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;



class CCatalogProductCustomPrice extends CCatalogProductProvider
{
    public static function GetProductData($arParams)
    {
		//	$i++;
		//   echo 'this'. $i . ' ';
        $arResult = parent::GetProductData($arParams);

        $hlblockId = HL\HighloadBlockTable::getById(4)->fetch(); // Получаем запись из HL блока №4
        $entity = HL\HighloadBlockTable::compileEntity($hlblockId);
        $entity_data_class = $entity->getDataClass();


        $rsDataPrice = $entity_data_class::getList(array(
            "select" => ["UF_PRICE_PRODUCT"],
            "filter" => [
                "UF_ID_PRODUCT" => $arParams["PRODUCT_ID"]
            ]
        ));
        while ($arItemPrice = $rsDataPrice->Fetch()) {
            $curItemPrice = $arItemPrice['UF_PRICE_PRODUCT']; // Индивидуальная цена

        }

        if (!empty($curItemPrice)) {
            $arResult = [
                'BASE_PRICE' => $curItemPrice, 
            ] + $arResult;

        }

        return $arResult;
    }
}


AddEventHandler('sale', 'OnSaleBasketItemRefreshData', 'BeforeBasketAddHandler');

function BeforeBasketAddHandler($BasketItem)
{

    $BasketItem->setField("PRODUCT_PROVIDER_CLASS", "CCatalogProductCustomPrice");
}