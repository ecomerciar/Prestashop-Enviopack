<?php

/**
* 
*/
class EpackCartHelper
{
	
 	public static function getCartWeight($cart, $id_carrier)
    {
        $defWeight = Configuration::get("ENVIOPACK_DEF_WEIGHT");

        $products = $cart->getProducts();
        $weight = 0;

        switch (Configuration::get('PS_WEIGHT_UNIT')) {
            case 'lb':
                $multiplier = 0.453592;
                break;
            case 'g':
                $multiplier = 0.001;
                break;
            case 'kg':
            default:
                $multiplier = 1;
                break;
        }
        foreach ($products as $product) {
            $productObj = new Product($product['id_product']);
            $carriers = $productObj->getCarriers();
            $isProductCarrier = false;

            foreach ($carriers as $carrier) {
                if (!$id_carrier || $carrier['id_carrier'] == $id_carrier) {
                    $isProductCarrier = true;
                    continue;
                }
            }

            if ($product['is_virtual'] or (count($carriers) && !$isProductCarrier))
                continue;

            $weight += ($product['weight'] > 0 ? ($product['weight'] * $multiplier) : $defWeight) * $product['cart_quantity'];
        }

        return $weight;
    }

    /**
     * Devuelve un array con las dimensiones de cada productos del carrito
     * @param  [Cart ] $cart
     * @return [array]
     */
    public static function getCartDimensions($cart)
    {
        $products = $cart->getProducts();
        $dimensions = array();
        foreach ($products as $product) {
            for ($i=0; $i < $product['cart_quantity']; $i++) { 
                if (min($product['width'], $product['height'], $product['depth']) <= 0) {
                    $dimensions[] = array(
                        'width'  =>  Configuration::get("ENVIOPACK_DEF_WIDTH"), 
                        'height' =>  Configuration::get("ENVIOPACK_DEF_HEIGHT"), 
                        'depth'  =>  Configuration::get("ENVIOPACK_DEF_DEPTH"),
                    );
                } else {
                    $dimensions[] = array(
                        'width'  => $product['width'], 
                        'height' => $product['height'], 
                        'depth'  => $product['depth'],
                    );
                }

            }
        }

        return $dimensions;
    }

}