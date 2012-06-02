<?php
/*
######################################################################
# __      __          __         ___                                 #
#/\ \  __/\ \        /\ \      /'___\                                #
#\ \ \/\ \ \ \     __\ \ \____/\ \__/  ___   _ __   ___     __       #
# \ \ \ \ \ \ \  /'__`\ \ '__`\ \ ,__\/ __`\/\`'__\/'___\ /'__`\     #
#  \ \ \_/ \_\ \/\  __/\ \ \L\ \ \ \_/\ \L\ \ \ \//\ \__//\  __/     #
#   \ `\___x___/\ \____\\ \_,__/\ \_\\ \____/\ \_\\ \____\ \____\    #
#    '\/__//__/  \/____/ \/___/  \/_/ \/___/  \/_/ \/____/\/____/    #
#                                                                    #
#     )   ___                                                        #
#    (__/_____)                      Webforce Cart v.1.5             #
#      /       _   __ _/_            (c) 2004-2005 Webforce Ltd, NZ  #
#     /       (_(_/ (_(__            webforce.co.nz/cart             #
#    (______)                        all rights reserved             #
#                                                                    #
#  Session based, Object Oriented Shopping Cart Component for PHP    #
#                                                                    #
######################################################################
# Ver 1.6 - Bugfix // Thanks James
# Ver 1.5 - Demo updated, Licence changed to LGPL
# Ver 1.4 - demo included
# Ver 1.3 - bugfix with total 
# Ver 1.2 - added empty_cart()
# Ver 1.0 - initial release
You are allowed to use this script in websites you create. 
Licence: LGPL - http://www.gnu.org/copyleft/lesser.txt
*** Instructions at http://www.webforce.co.nz/cart/php-cart.php ***
*** READ THEM!                                                 ***

BUGS/PATCHES

Please email eaden@webforce.co.nz with any bugs/fixes/patches/comments etc.
See http://www.webforce.co.nz/cart/ for updates to this script

*/
/**
 * GK Cart v0.01
 * 01/01/2010
 * @author Gavin Kimpson
 * @copyright
 * Based on Webforce Cart - kudos to the original developer 
 */
class gkCart {
    var $final_total = 0;
    var $total = 0;

    var $itemcount = 0;
    var $basketcount = 0;
	var $items = array();
	var $itemprices = array();
	var $itemqtys = array();
	var $iteminfo = array();
    var $itemunits = array();
    var $shipping = 0;
    var $shipping_type = NULL;
    
    var $products_vat = 0;
    var $shipping_vat = 0;
    var $combined_vat = 0;
    var $net = 0;
    var $apply_vat;

	function gkCart() {

	} // constructor function

    
    /**
     * @param string $string
     * @return string $slug e.g. 30-WhiteLarge (product id 30 - Options are White & Large)
     */
    function make_slug($string) {
        $slug = preg_replace("/[^a-zA-Z0-9 ]/", "", $string);
        return str_replace(" ", "-", $slug);
    } // end of make_slug


    /**
     * get contents of the cart
     */
    function get_contents() {
        $items = array();
		foreach($this->items as $key => $item_id) {
			$item = FALSE;
            
            $item['item']       = $key;
            $item['item_id']    = $item_id;
            $item['qty']        = $this->itemqtys[$key];
            $item['price']      = $this->itemprices[$key];
            $item['info']       = $this->iteminfo[$key];
            $item['units']      = $this->itemunits[$key];
            $item['options']    = $this->itemoptions[$key];
            $item['subtotal']   = ($item['qty'] * $item['price']); 
            $item['slug']       = $this->itemslugs[$key];

   			$items[] = $item;
		}
		return $items;        
    } // end of get_contents


	/**
	 * @param int $itemid e.g. '30-WhiteLarge'
     * @param int $qty
     * @param array $options
     * @param int $price
     * @param string $info
	 */
	function add_item($itemid, $qty = 1, $options = array(), $price = FALSE, $info = FALSE, $units = 1) { // adds an item to cart
/*
 		if(!$price)
  			$price = get_price($itemid, $qty);

        if(!$info)
            $info = get_info($itemid);
*/
         
        $string = $itemid . ' ';
        if ($options) {
            foreach($options as $key => $value)
                $string.= $value;
        }

        $item = $this->make_slug($string);
        $arrItems[$item] = array(
                            'itemid' => $itemid,
                            'qty' => $options,
                            'price' => $price,
                            'info' => $info,
                            'units' => $units
                           );
        
        if(!$item)
            return false;

        if ($this->itemqtys[$item] > 0) {
            $this->itemqtys[$item] = $qty + $this->itemqtys[$item];         
        } else {
            $this->items[$item] = $itemid;
            $this->itemqtys[$item] = $qty;
            $this->itemoptions[$item] = $options;
            $this->itemprices[$item] = $price;
            $this->iteminfo[$item] = $info;
            $this->itemunits[$item] = $units;
            $this->itemslugs[$item] = $this->make_slug($info, $itemid);        
        }
        
        $this->_update_total();
	} // end of add_item
    

    /**
     * edit item quantity in cart
     * @param string $item e.g. '30-WhiteLarge'
     * @param int $qty
     */
    function edit_item($item, $qty) {
		if ($qty < 1)
			$this->del_item($item);
		else
			$this->itemqtys[$item] = $qty;             
        $this->_update_total();
    }
    
    
    /**
     * delete item from cart
     * @param string $item e.g. '30-WhiteExtra-Large'
     */
    function del_item($item) {
        unset($this->items[$item]);
        unset($this->itemprices[$item]);
        unset($this->itemoptions[$item]);
        unset($this->itemqtys[$item]);
        unset($this->iteminfo[$item]);
        unset($this->itemunits[$item]);
        unset($this->itemslugs[$item]);

		$this->_update_total();        
    } // end of del_item
    
    
    /**
     * empties / resets the entire cart
     */
	function empty_cart() {
	    $this->final_total = 0;
        $this->total = 0;
        $this->itemcount = 0;
        $this->items = array();
        $this->itemprices = array();
        $this->itemoptions = array();
        $this->itemqtys = array();
        $this->iteminfo = array();
        $this->itemunits = array();
        $this->itemslugs = array();
        $this->basketcount = 0;
        $this->shipping = 0;
        $this->shipping_type = NULL;
        
        $this->products_vat = 0;
        $this->shipping_vat = 0;
        $this->combined_vat = 0;
        $this->net = 0;
        $this->apply_vat = NULL;
        
        unset($_SESSION['shipping']);       // empty session value for shipping
        unset($_SESSION['instructions']);   // empty session value for additional instructions        
	} // end of empty cart
        
     
    /**
     * internal function to update the total in the cart 
     */
    function _update_total() {
 		$this->itemcount = 0;
		$this->total = 0;        
        
  		if(sizeof($this->items > 0)) {
			foreach($this->items as $item => $itemid) {                
                $this->total = $this->total + ($this->itemprices[$item]) * $this->itemqtys[$item];
				$this->itemcount++;
			}
		}            
        $this->_get_basket_count();
    } // end of _update_total
    
    
    /**
     * set shipping fee
     * @param float $shipping 
     */
    function set_shipping($shipping) {
        $this->shipping = $shipping;
        $this->add_shipping();
    }
    
    /**
     * set shipping type
     */
     function set_shipping_type($shipping_type) {
        $this->shipping_type = $shipping_type;
     } //
    
    /**
     * add shipping fee to total
     */
     function add_shipping() {
        $this->_update_total();
        $this->total = ($this->total + $this->shipping);        
     } // end of add_shipping

	/**
	 * get count of number of items in basket 
	 */
	function _get_basket_count() {	
		$count = 0;
		foreach ($this->itemqtys as $k => $v)
			$count = ($count + $v);
		$this->basketcount = $count;
	} // end of _getBasketCount    

    /**
     * manually force update on cart (if required)
     * @return void 
     */
    function force_update() {
        $this->_update_total();
        $this->set_net_amount();
        $this->set_vat_amount();        
        $this->set_final_total();        
    }       

    /**
     * @param $vat boolean true|false
     */
    function set_apply_vat($vat = TRUE) {
        $this->apply_vat = $vat;
        $this->force_update();
    }
    
    /**
     * set the vat amount (calculated from net amount & shipping)
     *
     * ( [VAT Rate] / 100 ) * [Original Price] = [Amount of VAT Payable]
     * for example ( 17.5 / 100 ) * 72.33 = 12.66 (rounded)      
     */
    function set_vat_amount() {
        if ($this->apply_vat == TRUE) {
            $total = ($this->net + $this->shipping);
            $this->combined_vat = (APP_VAT / 100) * $total;
        }
     }    
    
    function set_net_amount() {
        $this->net = $this->total;
    }
    
    function set_final_total() {
        if ($this->apply_vat == TRUE)
            $final_total = ($this->net + $this->shipping) + $this->combined_vat;
        else
            $final_total = ($this->net + $this->shipping);
        $this->final_total = $final_total;
    }
    
    //TODO:add logic for price lists (eg for different customer groups.. eg trade, customer)
    function get_price() {
        
    } //
    
    
    function get_info() {
        
    } //

}
?>