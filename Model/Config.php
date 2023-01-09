<?php

namespace Simpl\Splitpay\Model;

class Config
{
    /**
     *
     */
    const KEY_ACTIVE = 'active';
    /**
     *
     */
    const KEY_TITLE = 'title';
    /**
     *
     */
    const KEY_TEST_MODE = 'test_mode';
    /**
     *
     */
    const KEY_CLIENT_ID = 'client_id';
    /**
     *
     */
    const KEY_CLIENT_KEY = 'client_key';
    /**
     *
     */
    const KEY_ALLOW_SPECIFIC = 'allowspecific';
    /**
     *
     */
    const KEY_SPECIFIC_COUNTRY = 'specificcountry';
    /**
     *
     */
    const KEY_POPUP = 'popup';
    /**
     *
     */
    const KEY_ENABLE_POPUP_PRODUCT_PAGE = 'enable_popup_product_page';
    /**
     *
     */
    const KEY_ENABLE_POPUP_CART_PAGE = 'enable_popup_cart_page';
    /**
     *
     */
    const KEY_ENABLE_POPUP_CHECKOUT_PAGE = 'enable_popup_checkout_page';
    /**
     *
     */
    const KEY_DESCRIPTION = 'description';
    /**
     *
     */
    const KEY_ENABLED_FOR = 'enabled_for';
    /**
     *
     */
    const KEY_PRODUCT_PAGE_FONT_WEIGHT = 'product_page_font_weight';
    /**
     *
     */
    const KEY_PRODUCT_PAGE_FONT_SIZE = 'product_page_font_size';
    /**
     *
     */
    const KEY_CART_PAGE_FONT_WEIGHT = 'cart_page_font_weight';
    /**
     *
     */
    const KEY_CART_PAGE_FONT_SIZE = 'cart_page_font_size';
    /**
     *
     */
    const KEY_CHECKOUT_PAGE_FONT_WEIGHT = 'checkout_page_font_weight';
    /**
     *
     */
    const KEY_CHECKOUT_PAGE_FONT_SIZE = 'checkout_page_font_size';

    /**
     * @var string
     */
    protected $methodCode = 'simplpayin3';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var null
     */
    protected $storeId = null;
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;
    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Catalog\Model\Session
     */
    protected $catalogSession;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Action\Context                 $context,
        \Magento\Checkout\Model\Cart                          $cart,
        \Magento\Store\Model\StoreManagerInterface            $storeManager,
        \Magento\Catalog\Model\Session                        $catalogSession,
        \Magento\Framework\HTTP\Client\Curl                   $curl

    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->context = $context;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->catalogSession = $catalogSession;
        $this->curl = $curl;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool)(int)$this->getConfigData(self::KEY_ACTIVE, $this->storeId);
    }

    /**
     * @return mixed|string
     */
    public function getTitle()
    {
        $title = $this->getConfigData(self::KEY_TITLE);
        if (empty($title)) {
            $title = "Simpl";
        }
        return $title;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->getConfigData(self::KEY_TEST_MODE, $this->storeId);
    }

    /**
     * @return string
     */
    public function getApiDomain()
    {
        $testMode = $this->isTestMode();
        $url = 'https://splitpay-api.getsimpl.com';
        if ($testMode != 'production') {
            $url = 'https://sandbox-splitpay-api.getsimpl.com/';   // sandbox url.
        }
        return $url;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->getConfigData(self::KEY_CLIENT_ID);
    }

    /**
     * @return mixed
     */
    public function getClientKey()
    {
        return $this->getConfigData(self::KEY_CLIENT_KEY);
    }

    /**
     * @param $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @param $field
     * @param $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->storeId;
        }

        $code = $this->methodCode;

        $path = 'payment/' . $code . '/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $country
     * @return bool
     */
    public function canUseForCountry($country)
    {
        if ($this->getConfigData(self::KEY_ALLOW_SPECIFIC) == 1) {
            $availableCountries = ['IN'];
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function getPopupUrl()
    {
        return $this->getConfigData(self::KEY_POPUP);
    }

    /**
     * @return mixed
     */
    public function isEnableAtProductPage()
    {
        return $this->getConfigData(self::KEY_ENABLE_POPUP_PRODUCT_PAGE);
    }

    /**
     * @return mixed
     */
    public function isEnableAtCartPage()
    {
        return $this->getConfigData(self::KEY_ENABLE_POPUP_CART_PAGE);
    }

    /**
     * @return mixed
     */
    public function getEnabledFor()
    {
        return $this->getConfigData(self::KEY_ENABLED_FOR);
    }

    /**
     * @return mixed
     */
    public function isEnableAtCheckoutPage()
    {
        return $this->getConfigData(self::KEY_ENABLE_POPUP_CHECKOUT_PAGE);
    }

    /**
     * @return mixed|void
     */
    public function getFontWeight()
    {
        $request = $this->context->getRequest();
        if ($request->getFullActionName() == 'catalog_product_view') {
            return $this->getConfigData(self::KEY_PRODUCT_PAGE_FONT_WEIGHT);
        } elseif ($request->getFullActionName() == 'checkout_cart_index') {
            return $this->getConfigData(self::KEY_CART_PAGE_FONT_WEIGHT);
        } elseif ($request->getFullActionName() == 'checkout_index_index') {
            return $this->getConfigData(self::KEY_CHECKOUT_PAGE_FONT_WEIGHT);
        }
    }

    /**
     * @return mixed|void
     */
    public function getFontSize()
    {
        $request = $this->context->getRequest();
        if ($request->getFullActionName() == 'catalog_product_view') {
            return $this->getConfigData(self::KEY_PRODUCT_PAGE_FONT_SIZE);
        } elseif ($request->getFullActionName() == 'checkout_cart_index') {
            return $this->getConfigData(self::KEY_CART_PAGE_FONT_SIZE);
        } elseif ($request->getFullActionName() == 'checkout_index_index') {
            return $this->getConfigData(self::KEY_CHECKOUT_PAGE_FONT_SIZE);
        }
    }

    /**
     * @return mixed|string
     */
    public function getDescription()
    {
        $description = $this->getConfigData(self::KEY_DESCRIPTION);
        if (empty($description)) {
            $description = "Interest Free. Always.";
        }
        return $description;
    }

    /**
     * @return string
     */
    public function validateCartItems()
    {
        $items = $this->cart->getQuote()->getAllVisibleItems();

        $totalItems = count($items);
        $specialItems = $normalItems = 0;
        foreach ($items as $item) {
            $oldPrice = $finalPrice = 0;
            $product = $item->getProduct();
            if ($product->getTypeId() == 'configurable') {
                $basePrice = $product->getPriceInfo()->getPrice('regular_price');
                $oldPrice = $basePrice->getMinRegularAmount()->getValue();
                $finalPrice = $product->getFinalPrice();
            } elseif ($product->getTypeId() == 'bundle') {
                $oldPrice = $product->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue();
                $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
            } elseif ($product->getTypeId() == 'grouped') {
                $usedProds = $product->getTypeInstance(true)->getAssociatedProducts($product);
                foreach ($usedProds as $child) {
                    if ($child->getId() != $product->getId()) {
                        $oldPrice += $child->getPrice();
                        $finalPrice += $child->getFinalPrice();
                    }
                }
            } else {
                $oldPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
                $finalPrice = $product->getPriceInfo()->getPrice('special_price')->getValue();
            }

            if ($oldPrice != $finalPrice) {
                $specialItems++;
            } else {
                $normalItems++;
            }
        }

        return ($totalItems == $specialItems) ? "0" : "1";
    }

    /**
     * @param $formattedPrice
     * @return array|string|string[]
     */
    public function getPopupDescription($formattedPrice = "")
    {
        $identifyConfiguration = $this->identifyConfiguration();
        if ($identifyConfiguration['widgetVersion']['widget_version'] == 'v2') {
            return '';
        }
        $plainPrice = preg_replace('/[^0-9-.]+/', '', $formattedPrice);
        $linkURL = 'https://d19ud5ez64hf3q.cloudfront.net/popup/index.html?price=' . $plainPrice;
        $logo = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 130 40"><path fill="#28C9C9" d="M23.237 8.727l3.38-3.453-.723-.738c-5.92-6.048-15.545-6.048-21.464 0-5.663 5.787-5.897 15.074-.7 21.17L.328 29.135l.722.738c5.92 6.048 15.545 6.048 21.464 0 5.687-5.787 5.92-15.074.723-21.146zM5.875 6.037c4.847-4.977 12.63-5.215 17.758-.739L21.7 7.275c-4.102-3.43-10.138-3.191-13.96.714-3.822 3.905-4.055 10.097-.7 14.264l-1.887 1.929C.771 18.919 1.004 10.99 5.875 6.037zm15.171 22.336c-4.87 4.977-12.631 5.215-17.782.714L5.2 27.111c4.101 3.429 10.137 3.19 13.96-.715 3.822-3.905 4.055-10.096.699-14.264l1.887-1.928c4.405 5.286 4.172 13.216-.699 18.169zM66.325 11.443h4.546v3.573c2.309-4.752 10.002-5.788 11.716-.036h.07c1.573-3.18 5.455-4.716 8.742-3.68 3.043.965 3.917 4.11 3.917 7.04v12.434h-4.721V19.268c0-2-.525-4.037-2.903-3.859-2.308.143-3.776 2.251-4.266 4.395-.175.75-.21 1.608-.21 2.502v8.504H78.46V19.268c0-2.143-.594-4.145-3.112-3.823-2.239.286-3.602 2.323-4.092 4.395-.175.715-.21 1.608-.21 2.466v8.504h-4.72V11.443zM129.236 26.703c-1.223 0-1.923-.536-1.923-2.645V3.87l-4.686 4.788v16.115c0 5.539 3.217 6.218 5.84 6.218.769 0 1.469-.108 1.469-.108v-4.216s-.35.036-.7.036zM117.067 14.845c-1.644-2.752-4.651-4.11-7.799-3.788-1.993.215-4.126 1.287-5.14 3.145v-2.716h-4.302v28.515l4.721-4.824v-4.86-1.68c1.574 2.359 4.721 3.038 7.309 2.466 6.819-1.536 8.498-10.827 5.211-16.258zm-7.939 12.363c-2.902 0-4.511-2.715-4.686-5.395-.14-2.252.455-4.896 2.518-6.075 1.714-.965 3.952-.714 5.316.715 3.007 3.108 1.923 10.755-3.148 10.755zM61.778 11.448h-4.721v19.367h4.72V11.448zM52.825 21.739c-.734-3.359-3.847-5.003-6.714-6.218-1.889-.821-5.63-1.822-5.525-4.502.104-2.68 3.147-3.359 5.245-3.001 1.19.214 2.343.679 3.357 1.357l3.148-3.215c-2.833-2.788-7.764-3.467-11.33-2.144-3.848 1.429-6.505 5.824-4.722 9.898 1.294 3.001 4.651 4.252 7.379 5.467 2.098.929 5.805 2.572 4.126 5.61-1.189 2.18-4.231 1.93-6.19 1.215-1.083-.394-2.168-1.001-3.112-1.716l-3.147 3.216c2.762 2.859 7.274 4.038 11.05 3.323 4.302-.786 7.414-4.788 6.435-9.29z"/></svg>';
        $info_icon = '<a href="' . $linkURL . '" class="simpl-popup-link " data-featherlight="iframe"  data-featherlight-iframe-allowfullscreen="true" data-featherlight-iframe-width="350" data-featherlight-iframe-height="662"><svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><path d="M256,0C114.497,0,0,114.507,0,256c0,141.503,114.507,256,256,256c141.503,0,256-114.507,256-256C512,114.497,397.492,0,256,0z M256,472c-119.393,0-216-96.615-216-216c0-119.393,96.615-216,216-216c119.393,0,216,96.615,216,216C472,375.393,375.384,472,256,472z"/><path d="M256,214.33c-11.046,0-20,8.954-20,20v128.793c0,11.046,8.954,20,20,20s20-8.955,20-20.001V234.33C276,223.284,267.046,214.33,256,214.33z"/><circle cx="256" cy="162.84" r="27"/></svg></a>';

        $description = "<span style = '" . (($this->getFontSize()) ? "font-size:" . $this->getFontSize() . "px;" : "") . (($this->getFontWeight()) ? "font-weight:" . $this->getFontWeight() : "") . "'>Or 3 interest free payments of <span id='simplprice'>{{ amount }}</span> with {{ logo }} {{ info_icon }}</span>";
        if (!empty($formattedPrice)) {
            $description = str_replace("{{ amount }}", $formattedPrice, $description);
        }
        $description = str_replace("{{ logo }}", $logo, $description);
        $description = str_replace("{{ info_icon }}", $info_icon, $description);

        return $description;
    }

    /**
     * @return float|string
     */
    public function getMinPriceConfig()
    {
        return !empty($this->getConfigData('min_price_limit')) ? (float)$this->getConfigData('min_price_limit') : '';
    }

    /**
     * @return float
     */
    public function getMaxPriceValue()
    {
        return !empty($this->getConfigData('max_price_limit')) ? (float)$this->getConfigData('max_price_limit') : '';
    }

    /**
     * @return array|string[]
     */
    public function getSupportingCurrencies()
    {
        return ['INR'];
    }

    /**
     * @return array|string[]
     */
    public function getSupportingCountries()
    {
        return ['IN'];
    }


    /**
     * @param $countryCode
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkCountryCurrencyCriteria($countryCode = null)
    {
        $currentStorecurrencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCurrencyCode();
        $supportingCurrencies = $this->getSupportingCurrencies();
        $supportingCountries = $this->getSupportingCountries();

        $enableSplitPay = true;
        if (!in_array($currentStorecurrencyCode, $supportingCurrencies)) {
            $enableSplitPay = false;
        } else if (!empty($countryCode) && !in_array($countryCode, $supportingCountries)) {
            $enableSplitPay = false;
        }
        return $enableSplitPay;
    }

    /**
     * @return array
     */
    public function identifyConfiguration()
    {
        $minutes = 5;
        $duration = ($minutes * 60);
        $wcSession = $this->catalogSession;
        $getSession = $wcSession->getSessionTimer();
        if (!empty($getSession)) {
            $time = ($duration - (time() - $getSession));
            if ($time <= 0) {
                $wcSession->setSessionTimer(time());   // Resetting Session timer
                $checkDomainConfig = $this->getJavascriptSdkConfig();
                if ($checkDomainConfig['success'] || $checkDomainConfig['success'] == 1) {
                    $wcSession->setWidgetVersion('v2');
                } else {
                    $wcSession->setWidgetVersion('v1');
                }
            } else {
                $wcSession->setWidgetVersion($wcSession->getWidgetVersion());
            }
        } else {
            $checkDomainConfig = $this->getJavascriptSdkConfig();
            if ($checkDomainConfig['success'] || $checkDomainConfig['success'] == 1) {
                $wcSession->setWidgetVersion('v2');
            } else {
                $wcSession->setWidgetVersion('v1');
            }
            $wcSession->setSessionTimer(time());
        }

        $getWidgetVersion = $wcSession->get('simp_widget_version');

        $configurations = [
            'widgetVersion' => $getWidgetVersion,
            'simpl_session_time' => $wcSession->getSessionTimer(),
            'session_set_at' => date("m/d/Y h:i:s A T", $getSession),
            'current_time' => date("m/d/Y h:i:s A T", time())
        ];
        return $configurations;
    }

    /**
     * @return string
     */
    public function getInitiateUrlForWidget()
    {
        $siteDomain = $this->getSiteDomain();
        $prepareUrl = $this->urlByEnvironment();
        return $prepareUrl . 'api/v1/shop/config?shop=' . $siteDomain;

    }

    public function urlByEnvironment()
    {
        $testMode = $this->isTestMode();
        $envUrl = 'https://pi3-backend.getsimpl.com/';
        if ($testMode != 'production') {
            $envUrl = 'https://pi3-backend.stagingsimpl.com/';   // sandbox url.
        }
        return $envUrl;
    }

    /**
     * @return array|string|string[]|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSiteDomain()
    {
        $url = $this->storeManager->getStore()->getBaseUrl();
        $url = preg_replace("#^[^:/.]*[:/]+#i", "", preg_replace("{/$}", "", urldecode($url)));
        return $url;
    }

    /**
     * @return array
     */
    public function getJavascriptSdkConfig()
    {

        $initiate_url = $this->getInitiateUrlForWidget();
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $initiate_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $encode_response_body = curl_exec($curl);

        curl_close($curl);
        $responseBody = json_decode($encode_response_body, true);
        $returnData = ['apiResponse' => $responseBody, 'success' => true];

        if (empty($responseBody)) {
            $returnData['success'] = false;
            return $returnData;
        }

        if (!isset($responseBody['error'])) {

            if (!empty($responseBody['product']) && !empty($responseBody['product']['minPrice']) && !empty(!empty($responseBody['product']['maxPrice']))) {
                $minPricePath = 'payment/' . $this->methodCode . '/min_price_limit';
                $maxPricePath = 'payment/' . $this->methodCode . '/max_price_limit';

                $minPricePaisaToAmount = $responseBody['product']['minPrice'] / 100;
                $maxPricePaisaToAmount = $responseBody['product']['maxPrice'] / 100;

                $this->configWriter->save($minPricePath, $minPricePaisaToAmount);
                $this->configWriter->save($maxPricePath, $maxPricePaisaToAmount);

            }
            return $returnData;
        }
        $returnData['success'] = false;

        return $returnData;
    }

    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequestData()
    {
        return $this->context->getRequest();
    }

    /**
     * @return array|string[]
     */
    public function getPaymentMethodCode()
    {
        return $this->methodCode;
    }

}
