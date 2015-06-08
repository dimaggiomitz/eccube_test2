<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2014 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * APIの基本クラス
 *
 * @package Api
 * @author bitmop, Inc.
 * @version $Id$
 */
require_once CLASS_EX_REALDIR . 'api_extends/SC_Api_Abstract_Ex.php';

class API_SyncStock extends SC_Api_Abstract_Ex
{
    protected $operation_name = 'SyncStock';
    protected $operation_description = 'Synchronize stocks between gtn and gtn-en';
    protected $default_auth_types = self::API_AUTH_TYPE_OPEN;
    protected $default_enable = '1';
    protected $default_is_log = '0';
    protected $default_sub_data = '';

    public function doAction($arrParam)
    {

#GC_Utils::gfPrintLog(print_r($_SERVER, true));
        $this->arrResponse = array(
            'CurrentStock' => $this->sfSyncStocks($arrParam['product_code'], $arrParam['stock'], $arrParam['quantity']));
        return true;
    }

    public function getRequestValidate()
    {
        return;
    }

    protected function lfInitParam(&$objFormParam)
    {
    }

    public function getResponseGroupName()
    {
        return 'OperationResponse';
    }

    // 在庫数の同期処理
    /**
     * @param string $product_code
     * @param string $latest_stock
     */
    public static function sfSyncStocks($product_code, $latest_stock, $order_quantity)
    {
        if ($product_code && $latest_stock && $order_quantity) {
        	GC_Utils::gfPrintLog('SYNC_ALLOW_IP_ADDRESS is ' . SYNC_ALLOW_IP_ADDRESS);
			if ($_SERVER['REMOTE_ADDR'] == SYNC_ALLOW_IP_ADDRESS) {

	            $objQuery =& SC_Query_Ex::getSingletonInstance();
	            $col    = '*';
	            $from   = 'dtb_products_class';
	            $where  = 'product_code = ?';
	            $arrVal = array($product_code);
	            $arrProductsClasses = $objQuery->select($col, $from, $where, $arrVal);

	            if ($arrProductsClasses[0]['stock'] > ($latest_stock + $order_quantity)) {
	                // あまり想定できないが、売れてる方が在庫数が少ないケース
	                $new_stock = $latest_stock;

	            } elseif ($arrProductsClasses[0]['stock'] == ($latest_stock + $order_quantity)) {
	                // 通常はこのケース
	                $new_stock = $latest_stock;

	            } elseif ($arrProductsClasses[0]['stock'] < ($latest_stock + $order_quantity)) {
	                // 相手側の購入フローの最中にこっちでも売れたケース？
	                // 相手側の受注で売れた数をこっちの在庫数から減算した上で、相手側に最新の在庫数を伝える
	                $new_stock = $arrProductsClasses[0]['stock'] - $order_quantity;
	                if ($new_stock < 0) {
	                    $new_stock = 0;
	                }
	            }

	            $table = 'dtb_products_class';
	            $sqlval = array('stock' => $new_stock);
	            $where = 'product_code = ?';
	            $arrWhereVal = array($product_code);
	            $objQuery->update($table, $sqlval, $where, $arrWhereVal);

	            return $new_stock;

	        } else {

	        	return 'Error-01';

	        }
        } else {

        	return 'Error-02';

        }
    }
}