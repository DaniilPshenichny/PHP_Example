<?php

$templateId = 2;

$value = 1;

 

\Bitrix\Main\Loader::includeModule('crm');

\Bitrix\Main\Loader::includeModule('documentgenerator');

 

/**

 * Returns true if this product should not be added to the document

 *

 * @param \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Product $product

 * @return bool

 */

function isSkipProduct(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Product $product)

{

    return true;

    $typePropertyId = 500;

    $restrictedType = 100;

    return $product->getValue('PROPERTY_'.$typePropertyId) === $restrictedType;

}

 

/**

 * @param \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Product $product

 * @return \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Product

 */

function modifyProduct(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Product $product)

{

    $reflection = new ReflectionObject($product);

    $data = $reflection->getProperty('data');

    $data->setAccessible(true);

    $dataValue = $data->getValue($product);

    $dataValue['NAME'] = 'Test Name';

    $data->setValue($product, $dataValue);

 

    return $product;

}

 

function modifyProducts(\Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider $productsProvider, array $products)

{

    $reflection = new ReflectionObject($productsProvider);

    $data = $reflection->getProperty('data');

    $data->setAccessible(true);

    $data->setValue($productsProvider, $products);

}

 

$template = \Bitrix\DocumentGenerator\Template::loadById($templateId);

if(!$template)

{

    die('template not found');

}

 

$template->setSourceType(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Deal::class);

 

$document = \Bitrix\DocumentGenerator\Document::createByTemplate($template, $value);

if(!$document)

{

    die('could not create document');

}

 

\Bitrix\Main\EventManager::getInstance()->addEventHandler(

    \Bitrix\DocumentGenerator\Driver::MODULE_ID,

    'onBeforeProcessDocument',

    function(\Bitrix\Main\Event $event)

    {

        $productsPlaceholder = 'PRODUCTS';

 

        /** @var \Bitrix\DocumentGenerator\Document $document */

        $document = $event->getParameter('document');

        $provider = $document->getProvider();

 

        $productsProvider = \Bitrix\DocumentGenerator\DataProviderManager::getInstance()->getDataProviderValue($provider, $productsPlaceholder);

        if($productsProvider instanceof \Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider)

        {

            $products = [];

            foreach($productsProvider as $product)

            {

                if(!isSkipProduct($product))

                {

                    $products[] = modifyProduct($product);

                }

            }

 

            modifyProducts($productsProvider, $products);

 

            $productsFieldDescription = $provider->getFields()[$productsPlaceholder];

 

            $document->setValues([$productsPlaceholder => $products]);

            $document->setFields([$productsPlaceholder => $productsFieldDescription]);

        }

    }

);

 

$result = $document->getFile();

 

if($result->isSuccess())

{

    print_r($result->getData());

}

else

{

    print_r($result->getErrorMessages());

}

?>
