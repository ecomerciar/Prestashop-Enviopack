<?php

/**
 * Created by IntelliJ IDEA.
 * User: gus
 * Date: 19/08/16
 * Time: 21:06
 */

require_once(dirname(__FILE__).'/EpackConfig.php');

class EpackPackage
{
    public $total_weight = 0;
    public $total_height = 0;
    public $total_depth  = 0;
    public $total_width  = 0;

    public function __construct($product_list)
    {
        $this->product_list = $product_list;
    }

    public function get_sizes()
    {
        $dimensions = array();
        $weight = 0;
        foreach ($this->product_list as $product) {
            $dim = array(
                'depth' => $this->get_depth($product),
                'height' => $this->get_height($product),
                'width' => $this->get_width($product),
            );
            for ($i=0; $i < $product['product_quantity']; $i++) { 
                $dimensions[] = $dim;
                $weight += $this->get_weight($product);
            }
        }

        $dimensions = explode('x', $this->getPacketEstimatedSize($dimensions));

        $return[] = array(
            "alto"  => $dimensions[0],
            "ancho" => $dimensions[1],
            "largo" => $dimensions[2],
            "peso"  => $weight
        );

        return $return;
    }

    private function get_depth($product)
    {
        if ($product['depth'] == 0) {
            $depth = Configuration::get("ENVIOPACK_DEF_DEPTH");
        } else {
            $depth = $product['depth'];
        }

        return $depth;
    }

    private function get_height($product)
    {
        if($product['height'] == 0) {
            $height = Configuration::get("ENVIOPACK_DEF_HEIGHT");
        } else {
            $height = $product['height'];
        }

        return $height;
    }

    private function get_width($product)
    {
        if ($product['width'] == 0) {
            $width = Configuration::get("ENVIOPACK_DEF_WIDTH");
        } else {
            $width = $product['width'];
        }

        return $width;
    }

    private function get_weight($product)
    {
        if ($product['weight'] == 0) {
            $weight = Configuration::get("ENVIOPACK_DEF_WEIGHT");
        } else {
            $weight = $product['weight'];
        }

        return $weight;
    }

    public function getEstimatedPackage(){
        $dimensions = array();
        foreach ($this->product_list as $product) {
            $dim = array(
                'depth' => $this->get_depth($product),
                'height' => $this->get_height($product),
                'width' => $this->get_width($product),
            );
            for ($i=0; $i < $product['product_quantity']; $i++) { 
                $dimensions[] = $dim;
            }
        }

        return $this->getPacketEstimatedSize($dimensions);
    }

    public static function getPacketEstimatedSize($dimensiones)
    {
        $estimation_method = Configuration::get("ENVIOPACK_PACKET_ESTIMATION_METHOD");

        switch ($estimation_method) {
            case EpackConfig::ESTIMATION_SUM_DIMS:
                return self::SumDimEstimation($dimensiones);
                break;
            case EpackConfig::ESTIMATION_MAX_DIMS:
                return self::MaxDimEstimation($dimensiones);
                break;
            case EpackConfig::ESTIMATION_DEFAULT_PACKET:
                return self::DefaultPacketEstimation();
                break;
        }
    }

    private static function SumDimEstimation($dimensiones)
    {
        // Ordeno las dimensiones de los productos para facilitar las comparaciones
        foreach ($dimensiones as &$product_dimensions) {
            sort($product_dimensions);
        }
        // Ordeno los paquetes por tamaño
        array_multisort($dimensiones);

        //- Si el pedido o checkout tiene un solo producto y una sola unidad se crea el paquete con esas dimensiones.
        if (count($dimensiones) == 1) {
            $paquete = implode('x', $dimensiones[0]);
        } else {
            $all_equal_size = true;
            for ($i=0; $i < count($dimensiones)-1 ; $i++) { 
                if ($dimensiones[$i] != $dimensiones[$i+1]) {
                    $all_equal_size = false;
                }
            }

            if ($all_equal_size) {
                //- Si el pedido o checkout tiene un solo producto y 2 unidades se crea el paquete con esas dimensiones. Se calcula 2alto x ancho x largo, donde 2 es la cantidad de productos iguales. En este caso seria bueno no siempre multiplicar la cantidad por el alto sino por la dimension mas chica de las 3: alto o ancho o largo.
                $paquete = ($dimensiones[0][0] * count($dimensiones)).'x'.$dimensiones[0][1].'x'.$dimensiones[0][2];
            } else{
                //- Si el pedido o checkout tiene varios productos distintos, saco el volumen total y estimo un paquete con forma de cubo. Ej. (20x10x30) + (10x5x30) = 7000cm3 = 24x24x24
                $volumen = 0;
                foreach ($dimensiones as $producto_dimension) {
                    $volumen += $producto_dimension[0]*$producto_dimension[1]*$producto_dimension[2];
                }

                $cube_size = ceil( pow($volumen, 1/3) );
                $paquete = $cube_size.'x'.$cube_size.'x'.$cube_size;
            }
        }

        return $paquete;
    }

    private static function MaxDimEstimation($dimensiones)
    {
        /* El paquete se arma estimando sus dimensiones en base a las dimension mas alta de cada producto (aun cuando sean muchos productos)
        Ej: 10x10x20 y 20x5x30. = 20x10x30*/

        $all_dimensions = array();
        foreach ($dimensiones as $producto_dimension) {
            foreach ($producto_dimension as $dimension_name => $value) {
                $all_dimensions[] = $value;
            }
        }
        rsort($all_dimensions);
        return $all_dimensions[0].'x'.$all_dimensions[1].'x'.$all_dimensions[2];
    }

    private static function DefaultPacketEstimation()
    {
        return Configuration::get('ENVIOPACK_PACKET_ESTIMATION_DEFAULT');
    }
}