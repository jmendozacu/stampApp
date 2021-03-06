<?php
/**
 * * NOTICE OF LICENSE
 * * This source file is subject to the Open Software License (OSL 3.0).
 *
 * Author: Ivan Deng
 * QQ: 300883
 * Email: 300883@qq.com
 *
 * @copyright  Copyright (c) 2008-2015 Sunpop Ltd. (http://www.sunpop.cn)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
header('Access-Control-Allow-Origin: *');
header('P3P: CP=CAO PSA OUR');
class Sunpop_RestConnect_CartController extends Mage_Core_Controller_Front_Action
{
    const MODE_CUSTOMER = Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER;
    const MODE_REGISTER = Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER;
    const MODE_GUEST = Mage_Checkout_Model_Type_Onepage::METHOD_GUEST;

    public function testAction()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $totalItemsInCart = Mage::helper('checkout/cart')->getItemsCount(); // total items in cart
        $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals(); // Total object
        $oldCouponCode = $cart->getQuote()->getCouponCode();
        $oCoupon = Mage::getModel('salesrule/coupon')->load($oldCouponCode, 'code');
        $oRule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());

        $subtotal = $totals ['subtotal']->getValue(); // Subtotal value
        $grandtotal = $totals ['grand_total']->getValue(); // Grandtotal value
        echo var_dump(get_class_methods(Mage::getSingleton('checkout/session')->getQuote()->collectTotals()));;
        if (isset($totals ['discount'])) { // $totals['discount']->getValue()) {
            $discount = round($totals ['discount']->getValue()); // Discount value if applied
        } else {
            $discount = '';
        }
        if (isset($totals ['tax'])) { // $totals['tax']->getValue()) {
            $tax = round($totals ['tax']->getValue()); // Tax value if present
        } else {
            $tax = '';
        };
    }
    //返回微信app支付参数
    protected function _paymentWeixin($order_id, $paymentcode)
    {
        try {
          if (isset($order_id) && $order_id > '') {
              $orderId = $order_id;
          } else {
              $session = Mage::getSingleton('checkout/session');;
              $orderId = $session->getLastRealOrderId();
          }

          $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

          if (!$order->getId()) {
              Mage::throwException(Mage::helper($paymentcode)->__('No order for processing'));
          }

          $payment = Mage::getModel($paymentcode .DS. 'payment');
          $config = $payment->prepareConfig();
          $total_fee = $order->getGrandTotal();

          $config['body'] = '订单#'.$orderId.'-执业印章之家';
          $config['order_id'] = $orderId;
          $config['total_fee'] = $total_fee*100;

          $app = Mage::getModel('weixinapp/app');
          return $app->payment($config);
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }

    protected $_attributesMap = array(
        'global' => array(),
    );
    protected $_ignoredAttributeCodes = array(
        'global' => array('entity_id', 'attribute_set_id', 'entity_type_id'),
    );

    public function getaddurlAction()
    {
        $productid = $this->getRequest()->getParam('productid');
        $product = Mage::getModel('catalog/product')->load($productid);
        $url = Mage::helper('checkout/cart')->getAddUrl($product);
        echo "{'url':'".$url."'}";
        $cart = Mage::helper('checkout/cart')->getCart();
        $item_qty = $cart->getItemsQty();
        echo "{'item_qty':'".$item_qty."'}";
        $summarycount = $cart->getSummaryCount();
        echo "{'summarycount':'".$summarycount."'}";
    }

    public function getQtyAction()
    {
        $items_qty = floor(Mage::getModel('checkout/cart')->getQuote()->getItemsQty());
        $result = '{"items_qty": "'.$items_qty.'"}';

        echo $result;
    }

    public function getCartInfoAction()
    {
        echo json_encode($this->_getCartInformation());
    }

    public function addAction()
    {
        try {
            $product_id = $this->getRequest()->getPost('product');
            if (!$product_id) {
              $product_id = $this->getRequest()->getParam('product');
              $params = $this->getRequest()->getParams();
              //echo  json_encode($params);
              //return;
            } else  {
              $params = $this->getRequest()->getPost();
              //echo  json_encode($params);
              //return;
            }

            if (isset($params ['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(array(
                        'locale' => Mage::app()->getLocale()->getLocaleCode(),
                ));
                $params ['qty'] = $filter->filter($params ['qty']);
            } elseif ($product_id == '') {
                $session->addError("Product Not Added.The SKU you entered ($sku) was not found.");
            }
            $request = Mage::app()->getRequest()->getPost();  //后续多数用 getPost来接收表单的 formdata
            $product = Mage::getModel('catalog/product')->load($product_id);
            $session = Mage::getSingleton('core/session', array(
                    'name' => 'frontend',
            ));
            //图片上传
            //end 图片上传
            $cart = Mage::helper('checkout/cart')->getCart();
            $cart->addProduct($product, $params);
            $session->setLastAddedProductId($product->getId());
            $session->setCartWasUpdated(true);
            $cart->save();
            $rcart = $this->_getCartInformation();
            $result = array(
                'status' => true,
                'code' => 0,
                'items_qty' => $rcart['items_qty'],
                'restult' => 'success',
                'param' => json_encode($params),
                'message' => '加入购物车成功。'
            );
            echo json_encode($result);//json_encode($this->_getCartInformation());
        } catch (Exception $e) {
            $result = array(
                'status' => false,
                'code' => 1,
                'restult' => 'error',
                'message' => str_replace('"', '||', $this->__($e->getMessage()))
            );
            echo json_encode($result);
        }
    }
    public function removeAction()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $id = (int) $this->getRequest()->getParam('cart_item_id', 0);
        if ($id) {
            try {
                $cart->removeItem($id)->save();
                echo json_encode($this->_getCartInformation());
            } catch (Mage_Core_Exception $e) {
                echo json_encode($e->getMessage());
            } catch (Exception $e) {
                echo json_encode($e->getMessage());
            }
        } else {
            echo json_encode(array(
                'status' => false,
                'code' => '0x5002',
                'restult' => 'error',
                    'message' => 'Param cart_item_id is empty.',
            ));
        }
    }
    public function updateAction()
    {
        $itemId = (int) $this->getRequest()->getParam('cart_item_id', 0);
        $qty = (int) $this->getRequest()->getParam('qty', 0);
        $oldQty = 0;
        $item = null;
        try {
            if ($itemId && $qty > 0) {
                $cartData = array();
                $cartData [$itemId] ['qty'] = $qty;
                $cart = Mage::getSingleton('checkout/cart');
                /* * ****** if update fail rollback ********* */
                if ($cart->getQuote()->getItemById($itemId)) {
                    $item = $cart->getQuote()->getItemById($itemId);
                } else {
                    echo json_encode(array(
                  'status' => false,
                        'code' => '0x0001',
                        'message' => 'a wrong cart_item_id was given.',
                    ));

                    return false;
                }
                $oldQty = $item->getQty();
                if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }
                $cart->updateItems($cartData)->save();
                if ($cart->getQuote()->getHasError()) { // apply for 1.7.0.2
                    $mesg = current($cart->getQuote()->getErrors());
                    Mage::throwException($mesg->getText());

                    return false;
                }
            }
            $session = Mage::getSingleton('checkout/session');
            $session->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) { // rollback $quote->collectTotals()->save();
            $item && $item->setData('qty', $oldQty);
            $cart->getQuote()->setTotalsCollectedFlag(false); // reflash price
            echo json_encode($e->getMessage());

            return false;
        } catch (Exception $e) {
            echo json_encode($e->getMessage());

            return false;
        }
        echo json_encode($this->_getCartInformation());
    }
    public function getTotalAction()
    {
        echo json_encode($this->_getCartTotal());
    }
    public function postCouponAction()
    {
        $couponCode = (string) Mage::app()->getRequest()->getParam('coupon_code');
        $cart = Mage::helper('checkout/cart')->getCart();
        if (!$cart->getItemsCount()) {
            echo json_encode(array(
                'status' => false,
                    'code' => '0X0001',
                    'message' => "You can't use coupon code with an empty shopping cart",
            ));

            return false;
        }
        if (Mage::app()->getRequest()->getParam('remove') == 1) {
            $couponCode = '';
        }
        $oldCouponCode = $cart->getQuote()->getCouponCode();
        if (!strlen($couponCode) && !strlen($oldCouponCode)) {
            echo json_encode(array(
                'status' => false,
                    'code' => '0X0002',
                    'message' => 'Emptyed.',
            ));

            return false;
        }
        try {
            $codeLength = strlen($couponCode);
            $isCodeLengthValid = $codeLength && $codeLength <= Mage_Checkout_Helper_Cart::COUPON_CODE_MAX_LENGTH;

            $cart->getQuote()->getShippingAddress()->setCollectShippingRates(true);
            $cart->getQuote()->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals()->save();

            if ($codeLength) {
                if ($isCodeLengthValid && $couponCode == $cart->getQuote()->getCouponCode()) {
                    $messages = array(
                    'status' => true,
                            'code' => '0x0000',
                            'message' => $this->__('Coupon code "%s" was applied.', Mage::helper('core')->escapeHtml($couponCode)),
                    );
                } else {
                    $messages = array(
                    'status' => false,
                            'code' => '0x0001',
                            'message' => $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->escapeHtml($couponCode)),
                    );
                }
            } else {
                $messages = array(
                  'status' => false,
                        'code' => '0x0002',
                        'message' => $this->__('Coupon code was canceled.'),
                );
            }
        } catch (Mage_Core_Exception $e) {
            $messages = array(
                'status' => false,
                    'code' => '0x0003',
                    'message' => $e->getMessage(),
            );
        } catch (Exception $e) {
            $messages = array(
                'status' => false,
                    'code' => '0x0004',
                    'message' => $this->__('Cannot apply the coupon code.'),
            );
        }
        echo json_encode(array_merge($messages, $this->_getCartTotal()));
    }

    public function cartInfoAction()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        if ($quote->getGiftMessageId() > 0) {
            $quote->setGiftMessage(
                Mage::getSingleton('giftmessage/message')->load($quote->getGiftMessageId())->getMessage()
            );
        }

        $result = $this->_getAttributes($quote, 'quote');
        $result['shipping_address'] = $this->_getAttributes($quote->getShippingAddress(), 'quote_address');
        $result['billing_address'] = $this->_getAttributes($quote->getBillingAddress(), 'quote_address');
        $result ['products'] = $this->_getCartProducts();
        $result['payment'] = $this->_getAttributes($quote->getPayment(), 'quote_payment');
        $result = Mage::helper('core')->jsonEncode($result);
        $this->getResponse()->setBody(urldecode($result));
    }

    protected function _getCartInformation()
    {
        $cart = Mage::getSingleton('checkout/cart');
        if ($cart->getQuote()->getItemsCount()) {
            $cart->init();
            $cart->save();
        }
        $cart->getQuote()->collectTotals()->save();
        $cartInfo = array();
        $cartInfo ['status'] = sizeof($this->errors) ? false : true;
        $cartInfo ['error'] = 0;
        $cartInfo ['is_virtual'] = Mage::helper('checkout/cart')->getIsVirtualQuote();
        $cartInfo ['products'] = $this->_getCartProducts();
        $cartInfo ['messages'] = sizeof($this->errors) ? $this->errors : $this->_getMessage();
        $cartInfo ['items_qty'] = Mage::helper('checkout/cart')->getSummaryCount();
        $cartInfo ['cart_items_count'] = $cartInfo ['items_qty'];
        $cartInfo ['payment_methods'] = $this->_getPaymentInfo();
        $cartInfo ['allow_guest_checkout'] = Mage::helper('checkout')->isAllowedGuestCheckout($cart->getQuote());
        $cartInfo ['total'] = $this->_getCartTotal();

        return $cartInfo;
    }
    protected function _getCartTotal()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $totalItemsInCart = Mage::helper('checkout/cart')->getItemsCount(); // total items in cart
        $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals(); // Total object
        $oldCouponCode = $cart->getQuote()->getCouponCode();
        $oCoupon = Mage::getModel('salesrule/coupon')->load($oldCouponCode, 'code');
        $oRule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());

        $grand_total = number_format ($totals ['grand_total']->getValue(),2,'.','');
        $subtotal = number_format ($totals ['subtotal']->getValue(),2,'.','');
        $shippingtotal = number_format ($totals ['grand_total']->getValue() - $totals ['subtotal']->getValue(),2,'.',''); // Subtotal value

        if (isset($totals ['discount'])) { // $totals['discount']->getValue()) {
            $discount = round($totals ['discount']->getValue()); // Discount value if applied
        } else {
            $discount = '';
        }
        if (isset($totals ['tax'])) { // $totals['tax']->getValue()) {
            $tax = round($totals ['tax']->getValue()); // Tax value if present
        } else {
            $tax = '';
        }

        return array(
                'subtotal' => $subtotal,
                'shipping_amount' => $shippingtotal,
                'grand_total' => $grand_total,
                'shippingtotal' => $shippingtotal,
                'grandtotal' => $grand_total,
                'discount' => $discount,
                'tax' => $tax,
                'coupon_code' => $oldCouponCode,
                'coupon_rule' => $oRule->getData(),
        );
    }
    protected function _getMessage()
    {
        $cart = Mage::getSingleton('checkout/cart');
        if (!Mage::getSingleton('checkout/type_onepage')->getQuote()->hasItems()) {
            $this->errors [] = 'Cart is empty!';

            return $this->errors;
        }
        if (!$cart->getQuote()->validateMinimumAmount()) {
            $warning = Mage::getStoreConfig('sales/minimum_order/description');
            $this->errors [] = $warning;
        }

        if (($messages = $cart->getQuote()->getErrors())) {
            foreach ($messages as $message) {
                if ($message) {
                    $message = str_replace('"', '||', $message);
                    $this->errors [] = $message->getText();
                }
            }
        }

        return $this->errors;
    }
    private function _getPaymentInfo()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $methods = $cart->getAvailablePayment();
        foreach ($methods as $method) {
            if ($method->getCode() == 'paypal_express') {
                return array(
                        'paypalec',
                );
            }
        }

        return array();
    }
    protected function _getCartProducts()
    {
        $cartItemsArr = array();
        $cart = Mage::getSingleton('checkout/cart');
        $quote = $cart->getQuote();
        $currency = $quote->getquote_currency_code();
        $displayCartPriceInclTax = Mage::helper('tax')->displayCartPriceInclTax();
        $displayCartPriceExclTax = Mage::helper('tax')->displayCartPriceExclTax();
        $displayCartBothPrices = Mage::helper('tax')->displayCartBothPrices();
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $productid = $item->getProduct()->getId();
            $product = Mage::getModel ( "catalog/product" )->load ( $productid );
            $cartItemArr = array();
            $cartItemArr ['cart_item_id'] = $item->getId();
            $cartItemArr ['currency'] = $currency;
            $cartItemArr ['entity_type'] = $item->getProductType();
            $cartItemArr ['product_id'] = $item->getProduct()->getId();
            $cartItemArr ['item_title'] = strip_tags($item->getProduct()->getName());
            $cartItemArr ['item_iconfont'] = $product ->getAIconfont();
            $cartItemArr ['qty'] = $item->getQty();
            $cartItemArr ['image_thumbnail_url'] = (string) Mage::helper('catalog/image')->init($item->getProduct(), 'thumbnail')->resize(75);
            $cartItemArr ['custom_option'] = $this->_getCustomOptions($item);
            if ($displayCartPriceExclTax || $displayCartBothPrices) {
                if (Mage::helper('weee')->typeOfDisplay($item, array(
                        0,
                        1,
                        4,
                ), 'sales') && $item->getWeeeTaxAppliedAmount()) {
                    $exclPrice = $item->getCalculationPrice() + $item->getWeeeTaxAppliedAmount() + $item->getWeeeTaxDisposition();
                } else {
                    $exclPrice = $item->getCalculationPrice();
                }
            }

            if ($displayCartPriceInclTax || $displayCartBothPrices) {
                $_incl = Mage::helper('checkout')->getPriceInclTax($item);
                if (Mage::helper('weee')->typeOfDisplay($item, array(
                        0,
                        1,
                        4,
                ), 'sales') && $item->getWeeeTaxAppliedAmount()) {
                    $inclPrice = $_incl + $item->getWeeeTaxAppliedAmount();
                } else {
                    $inclPrice = $_incl - $item->getWeeeTaxDisposition();
                }
            }

            $cartItemArr ['item_price'] = max($exclPrice, $inclPrice); // only display one

            array_push($cartItemsArr, $cartItemArr);
        }

        return $cartItemsArr;
    }
    protected function _getCustomOptions($item)
    {
        $session = Mage::getSingleton('checkout/session');
        $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
        $result = array();
        if ($options) {
            if (isset($options ['options'])) {
                $result = array_merge($result, $options ['options']);
            }
            if (isset($options ['additional_options'])) {
                $result = array_merge($result, $options ['additional_options']);
            }
            if (!empty($options ['attributes_info'])) {
                $result = array_merge($result, $options ['attributes_info']);
            }
            foreach ($result as $j => $option) {
                //对文件类型的处理，清除字段减少流量
                    if ($result[$j]['option_type'] = 'file') {
                        $result[$j]['option_value'] = '';
                    }
            }
        }

        return $result;
    }

    public function customerSetAction()
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $quote_customer = array('customer_id' => 'entity_id');
        $customerData = $this->getRequest()->getParams();
        if (!isset($customerData['mode'])) {
            echo json_encode(array(
                'status' => false,
                    'code' => '0x0002',
                    'message' => 'customer_mode_is_unknown',
            ));
        }

        switch ($customerData['mode']) {
        case self::MODE_CUSTOMER:
            /* @var $customer Mage_Customer_Model_Customer */
            $customer->setMode(self::MODE_CUSTOMER);
            break;

        case self::MODE_REGISTER:
        case self::MODE_GUEST:
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer')
                ->setData($customerData);

            if ($customer->getMode() == self::MODE_GUEST) {
                $password = $customer->generatePassword();

                $customer
                    ->setPassword($password)
                    ->setPasswordConfirmation($password);
            }

            $isCustomerValid = $customer->validate();
            if ($isCustomerValid !== true && is_array($isCustomerValid)) {
                echo json_encode(array(
                'status' => false,
                    'code' => '0x0001',
                    'message' => 'customer_mode_is_unknown',
                ));
            }
            break;
        }

        try {
            $quote
                ->setCustomer($customer)
                ->setCheckoutMethod($customer->getMode())
                ->setPasswordHash($customer->encryptPassword($customer->getPassword()))
                ->save();
            echo json_encode(array(
                    'status' => true,
                    'code' => 0,
                    'message' => 'set successfully',
                ));

            return;
        } catch (Mage_Core_Exception $e) {
            echo json_encode(array(
                'status' => false,
                    'code' => '0x0001',
                    'message' => 'customer_not_set',
                ));

            return;
        }

        return true;
    }

    public function customerAddressesAction()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $customerAddressData = $this->getRequest()->getParams();
        $customerarray = array();
        foreach ($customerAddressData as $c) {
            $customerarray[] = $c;
        }

        if (!is_array($customerAddressData) || !is_array($customerarray[0])) {
            echo json_encode(array(
                'status' => false,
                    'code' => '0x0001',
                    'message' => 'empty',
                    'test' => json_encode($customerAddressData),
                    't2' => json_encode($customerarray),
                ));

            return;
        }

        $dataAddresses = array();
        $attributesMap = array('address_id' => 'entity_id');
        foreach ($customerAddressData as $addressItem) {
            foreach ($attributesMap as $attributeAlias => $attributeCode) {
                if (isset($addressItem[$attributeAlias])) {
                    $addressItem[$attributeCode] = $addressItem[$attributeAlias];
                    unset($addressItem[$attributeAlias]);
                }
            }
            $dataAddresses[] = $addressItem;
        }
        $customerAddressData = $dataAddresses;

        if (is_null($customerAddressData)) {
            echo json_encode(array(
                'status' => false,
                    'code' => '0x0002',
                    'message' => 'customer_address_data_empty',
                ));

            return;
        }

        foreach ($customerAddressData as $addressItem) {
            //            switch($addressItem['mode']) {
//            case self::ADDRESS_BILLING:
                /** @var $address Mage_Sales_Model_Quote_Address */
                $address = Mage::getModel('sales/quote_address');
//                break;
//            case self::ADDRESS_SHIPPING:
//                /** @var $address Mage_Sales_Model_Quote_Address */
//                $address = Mage::getModel("sales/quote_address");
//                break;
//            }
            $addressMode = $addressItem['mode'];
            unset($addressItem['mode']);

            if (!empty($addressItem['entity_id'])) {
                $addressid = (int) $addressItem['entity_id'];
                $addresses = Mage::getModel('customer/address')->load($addressid);
                if (is_null($addresses->getId())) {
                    echo json_encode(array(
                'status' => false,
                    'code' => '0x0003',
                    'message' => 'invalid_address_id',
                    ));

                    return;
                }

                $addresses->explodeStreetAddress();
                if ($addresses->getRegionId()) {
                    $addresses->setRegion($addresses->getRegionId());
                }
                $customerAddress = $addresses;

                if ($customerAddress->getCustomerId() != $quote->getCustomerId()) {
                    echo json_encode(array(
                  'status' => false,
                        'code' => '0x0004',
                        'message' => 'address_not_belong_customer',
                    ));

                    return;
                }
                $address->importCustomerAddress($customerAddress);
            } else {
                $address->setData($addressItem);
            }

            $address->implodeStreetAddress();

            if (($validateRes = $address->validate()) !== true) {
                echo json_encode(array(
                  'status' => false,
                        'code' => '0x0005',
                        'message' => implode(PHP_EOL, $validateRes),
                    ));

                return;
            }

            switch ($addressMode) {
            case  Mage_Sales_Model_Quote_Address::TYPE_BILLING:
                $address->setEmail($quote->getCustomer()->getEmail());

                if (!$quote->isVirtual()) {
                    $usingCase = isset($addressItem['use_for_shipping']) ? (int) $addressItem['use_for_shipping'] : 0;
                    switch ($usingCase) {
                    case 0:
                        $shippingAddress = $quote->getShippingAddress();
                        $shippingAddress->setSameAsBilling(0);
                        break;
                    case 1:
                        $billingAddress = clone $address;
                        $billingAddress->unsAddressId()->unsAddressType();

                        $shippingAddress = $quote->getShippingAddress();
                        $shippingMethod = $shippingAddress->getShippingMethod();
                        $shippingAddress->addData($billingAddress->getData())
                            ->setSameAsBilling(1)
                            ->setShippingMethod($shippingMethod)
                            ->setCollectShippingRates(true);
                        break;
                    }
                }
                $quote->setBillingAddress($address);
                break;

            case Mage_Sales_Model_Quote_Address::TYPE_SHIPPING:
                $address->setCollectShippingRates(true)
                        ->setSameAsBilling(0);
                $quote->setShippingAddress($address);
                break;
            }
        }

        try {
            $quote
                ->collectTotals()
                ->save();
            echo json_encode(array(
                    'status' => true,
                    'code' => 0,
                    'message' => 'customer Addresses set successfully',
                ));

            return;
        } catch (Exception $e) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0005',
                    'message' => $e->getMessage(),
                ));

            return;
        }

        return true;
    }

    public function paymentMethodAction()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $store = $quote->getStoreId();
        $paymentDat = $this->getRequest()->getParams();
        $paymentDat = $this->_preparePaymentData($paymentDat);
        if (empty($paymentDat)) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0002',
                    'message' => 'payment_method_empty',
                ));

            return;
        }
        $paymentData = array();
        foreach ($paymentDat as $index => $p) {
            $paymentData[$index] = $p;
        }
        if ($quote->isVirtual()) {
            // check if billing address is set
            if (is_null($quote->getBillingAddress()->getId())) {
                echo json_encode(array(
                    'status' => false,
                    'code' => '0x0003',
                    'message' => 'billing_address_is_not_set',
                ));

                return;
            }
            $quote->getBillingAddress()->setPaymentMethod(
                isset($paymentData['method']) ? $paymentData['method'] : null
            );
        } else {
            // check if shipping address is set
            if (is_null($quote->getShippingAddress()->getId())) {
                echo json_encode(array(
                    'status' => false,
                    'code' => '0x0004',
                    'message' => 'shipping_address_is_not_set',
                ));

                return;
            }
            $quote->getShippingAddress()->setPaymentMethod(
                isset($paymentData['method']) ? $paymentData['method'] : null
            );
        }

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        $total = $quote->getBaseSubtotal();
        $methods = Mage::helper('payment')->getStoreMethods($store, $quote);

        foreach ($methods as $method) {
            if ($method->getCode() == $paymentData['method']) {
                // @var $method Mage_Payment_Model_Method_Abstract
                if (!($this->_canUsePaymentMethod($method, $quote)
                    && ($total != 0
                        || $method->getCode() == 'free'
                        || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles())))
                ) {
                    echo json_encode(array(
                  'status' => false,
                  'code' => '0x0005',
                  'message' => 'method_not_allowed',
                  ));

                    return;
                }
            }
        }

        try {
            $payment = $quote->getPayment();
            $payment->importData($paymentData);
            $quote->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
            echo json_encode(array(
                    'status' => true,
                    'code' => 0,
                    'message' => 'payment method set successfully',
                    ));

            return;
        } catch (Mage_Core_Exception $e) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0006',
                    'message' => $e->getMessage(),
                    ));

            return;
        }

        return true;
    }

    protected function _preparePaymentData($data)
    {
        if (!(is_array($data) && is_null($data[0]))) {
            return array();
        }

        return $data;
    }

    public function paymentListAction()
    {
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        $methods = array();

        foreach ($payments as $paymentCode => $paymentModel) {
            if (($paymentCode != 'free') && ($paymentCode != 'paypal_billing_agreement')) {
                $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
                $methods[$paymentCode] = array(
                    'title' => $paymentTitle,
                    'code' => $paymentCode,
                    'method' => $paymentCode,
                    'cc_types' => $this->_getPaymentMethodAvailableCcTypes($paymentModel),
                );
                //设置默认值
                if ($paymentCode == "weixinapp")  {
                  $methods[$paymentCode]['default'] = true;
                  }
            }
        }
        //不显示微信扫码和公众号支付
        unset ($methods['weixin']);
        //如果是在公众号里访问，只显示公众号支付
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
          unset ($methods['weixinapp']);
        }
        else {
          unset ($methods['weixinmobile']);
        }
        echo json_encode($methods);
    }

    protected function _canUsePaymentMethod($method, $quote)
    {
        /*此处会返回错误，待研究，先屏蔽
        if (!($method->isGateway() || $method->canUseInternal())) {
            echo 'gateway inter';
            return false;
        }*/

        if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry())) {
            echo 'country';

            return false;
        }

        if (!$method->canUseForCurrency(Mage::app()->getStore($quote->getStoreId())->getBaseCurrencyCode())) {
            echo 'currency';

            return false;
        }

        /*
         * Checking for min/max order total for assigned payment method
         */
        $total = $quote->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            echo 'min max';

            return false;
        }

        return true;
    }

    protected function _getPaymentMethodAvailableCcTypes($method)
    {
        $ccTypes = Mage::getSingleton('payment/config')->getCcTypes();
        $methodCcTypes = explode(',', $method->getConfigData('cctypes'));
        foreach ($ccTypes as $code => $title) {
            if (!in_array($code, $methodCcTypes)) {
                unset($ccTypes[$code]);
            }
        }
        if (empty($ccTypes)) {
            return;
        }

        return $ccTypes;
    }

    public function shippingRatesListAction()
    {

    }

    public function shippingListAction()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();

        try {
            $ratesResult = array();
            foreach ($methods as $carrierCode => $rates) {
                $carrierName = $carrierCode;

                if (!is_null(Mage::getStoreConfig('carriers/'.$carrierCode.'/name'))) {
                    $carrierName = Mage::getStoreConfig('carriers/'.$carrierCode.'/name');
                }
                if (!is_null(Mage::getStoreConfig('carriers/'.$carrierCode.'/title'))) {
                    $carrierTitle = Mage::getStoreConfig('carriers/'.$carrierCode.'/title');
                }

                if ($_methods = $rates->getAllowedMethods()) {
                    foreach ($_methods as $_mcode => $_method) {
                        $_code = $carrierCode.'_'.$_mcode;
                    }
                }

                $rateItem['code'] = $_code;
                $rateItem['carrierName'] = $carrierName;
                $rateItem['carrierTitle'] = $carrierTitle;
                //设置默认值
                if ($_code == "flatrate_flatrate")  {
                  $rateItem['default'] = true;
                  }
                $ratesResult[] = $rateItem;
                unset($rateItem);
            }
        } catch (Mage_Core_Exception $e) {
            echo json_encode(array(
                'status' => false,
                    'code' => '0x0003',
                    'message' => $e->getMessage(),
                    ));

            return;
        }
        echo json_encode($ratesResult);
    }

    protected function _getAttributes($object, $type, array $attributes = null)
    {
        $result = array();

        if (!is_object($object)) {
            return $result;
        }

        foreach ($object->getData() as $attribute => $value) {
            if (is_object($value)) {
                continue;
            }

            if ($this->_isAllowedAttribute($attribute, $type, $attributes)) {
                $result[$attribute] = $value;
            }
        }

        foreach ($this->_attributesMap['global'] as $alias => $attributeCode) {
            $result[$alias] = $object->getData($attributeCode);
        }

        if (isset($this->_attributesMap[$type])) {
            foreach ($this->_attributesMap[$type] as $alias => $attributeCode) {
                $result[$alias] = $object->getData($attributeCode);
            }
        }

        return $result;
    }

    protected function _isAllowedAttribute($attributeCode, $type, array $attributes = null)
    {
        if (!empty($attributes)
            && !(in_array($attributeCode, $attributes))) {
            return false;
        }

        if (in_array($attributeCode, $this->_ignoredAttributeCodes['global'])) {
            return false;
        }

        if (isset($this->_ignoredAttributeCodes[$type])
            && in_array($attributeCode, $this->_ignoredAttributeCodes[$type])) {
            return false;
        }

        return true;
    }

    public function shippingMethodAction()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $quoteShippingAddress = $quote->getShippingAddress();

        //$shippingMethod = $this->getRequest()->getParam ( 'code' );
        $shippingMethod = $this->getRequest()->getParam('shippingmethod') ? $this->getRequest()->getParam('shippingmethod') : $this->getRequest()->getParam('code');

        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
        $ratesResult = array();
        foreach ($methods as $carrierCode => $rates) {
            if ($_methods = $rates->getAllowedMethods()) {
                foreach ($_methods as $_mcode => $_method) {
                    $_code = $carrierCode.'_'.$_mcode;
                }
            }

            $ratesResult[] = $_code;
        }

        if (!in_array($shippingMethod, $ratesResult)) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0003',
                    'message' => 'shipping_method_is_not_available',
                ));

            return;
        }

        try {
            $quote->getShippingAddress()->setShippingMethod($shippingMethod);
            $quote->collectTotals()->save();
            //$total = $quote->getTotals();
            $totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals(); // Total object
            $grand_total = number_format ($totals ['grand_total']->getValue(),2,'.','');
            $subtotal = number_format ($totals ['subtotal']->getValue(),2,'.','');
            $shipping_amount = number_format ($totals ['grand_total']->getValue() - $totals ['subtotal']->getValue(),2,'.',''); // Subtotal value
            echo json_encode(array(
                    'status' => true,
                    'code' => 0,
                    'grand_total' => $grand_total,
                    'subtotal' => $subtotal,
                    'shippingtotal' => $shipping_amount,
                    'shipping_amount' => $shipping_amount,
                    'message' => 'set shipping method sucessfully'
                ));

            return;
        } catch (Mage_Core_Exception $e) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0004',
                    'message' => $e->getMessage()
                ));

            return;
        }

        return true;
    }

    public function orderAction()
    {
        $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
        if (!empty($requiredAgreements)) {
            $diff = array_diff($agreements, $requiredAgreements);
            if (!empty($diff)) {
                $this->_fault('required_agreements_are_not_all');
                echo json_encode(array(
                    'status' => false,
                    'code' => '0x0002',
                    'message' => 'required_agreements_are_not_all',
                    'order_id' => $order->getId(),
                ));

                return;
            }
        }

        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        if ($quote->getIsMultiShipping()) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0003',
                    'message' => 'invalid_checkout_type',
                ));

            return;
        }
        if ($quote->getCheckoutMethod() == Mage_Checkout_Model_Api_Resource_Customer::MODE_GUEST
                && !Mage::helper('checkout')->isAllowedGuestCheckout($quote, $quote->getStoreId())) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0004',
                    'message' => 'guest_checkout_is_not_enabled',
                ));

            return;
        }

        /** @var $customerResource Mage_Checkout_Model_Api_Resource_Customer */
        $customerResource = Mage::getModel('checkout/api_resource_customer');
        $isNewCustomer = $customerResource->prepareCustomerForQuote($quote);

        try {
            $quote->collectTotals();
            /** @var $service Mage_Sales_Model_Service_Quote */
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();

            if ($isNewCustomer) {
                try {
                    $customerResource->involveNewCustomer($quote);
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            $order = $service->getOrder();
            if ($order) {
                Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                    array('order' => $order, 'quote' => $quote));

                try {
                    $order->queueNewOrderEmail();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            Mage::dispatchEvent(
                'checkout_submit_all_after',
                array('order' => $order, 'quote' => $quote)
            );

            $paymentcode = $order->getPayment()->getMethodInstance()->getCode();
            $payconfig = null;
            $message = null;
            //生成订单，只有货到付款是生成已处理的订单。其它支付方式生成 pending的订单
            if ($paymentcode == 'cashondelivery') {
              $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PROCESSING, $statusMessage, false);
              $order_id =  $order->getIncrementId();
              $message = '生成订单 #'.$order_id.' 成功，感谢您的购买。';
            } else if ($paymentcode == 'weixinapp') {
              $order->save();
              $order_id =  $order->getIncrementId();
              $payconfig = $this->_paymentWeixin($order_id, $paymentcode);
              $message = '生成订单 #'.$order_id.' 成功。';
              //$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING,Mage_Sales_Model_Order::STATE_PENDING,$statusMessage, false);
              //$order->setState(Mage_Sales_Model_Order::STATE_PENDING,Mage_Sales_Model_Order::STATE_PENDING,$statusMessage, false);
            } else if ($paymentcode == 'weixinmobile') {
               $order->save();
               $order_id =  $order->getIncrementId();
               $payment = Mage::getModel('weixinmobile/payment');
               $wx_repay_url = $payment->getRepayUrl($order);
               $payconfig = array (
                 'wx_repay_url' => $wx_repay_url
               );
               $message = '生成订单 #'.$order_id.' 成功。';
               //$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING,Mage_Sales_Model_Order::STATE_PENDING,$statusMessage, false);
               //$order->setState(Mage_Sales_Model_Order::STATE_PENDING,Mage_Sales_Model_Order::STATE_PENDING,$statusMessage, false);
             }
            $statusMessage = 'Order success '.$paymentcode;

            echo json_encode(array(
                    'status' => true,
                    'code' => 0,
                    'message' => $message,
                    'statusMessage' => $statusMessage,
                    'order_id' => $order->getIncrementId(),
                    'payconfig' => $payconfig
                ));
            foreach ($quote->getItemsCollection() as $item) {
                Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
            }

            return;
        } catch (Mage_Core_Exception $e) {
            echo json_encode(array(
                    'status' => false,
                    'code' => '0x0005',
                    'message' => $e->getMessage(),
                ));

            return;
        }
    }

    public function licenseAction()
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $storeId = $quote->getStoreId();

        $agreements = array();
        if (Mage::getStoreConfigFlag('checkout/options/enable_agreements')) {
            $agreementsCollection = Mage::getModel('checkout/agreement')->getCollection()
                    ->addStoreFilter($storeId)
                    ->addFieldToFilter('is_active', 1);

            foreach ($agreementsCollection as $_a) {
                /* @var $_a  Mage_Checkout_Model_Agreement */
                $agreements[] = $this->_getAttributes($_a, 'quote_agreement');
            }
        }
        echo json_encode($agreements);
    }

    protected function _escapeQuotes($str)
    {
        if ($str && is_string($str)) {
            $str = str_replace("'",  "\'", $str);
            $str = str_replace('"',  '\"', $str);
        }

        return $str;
    }
}
