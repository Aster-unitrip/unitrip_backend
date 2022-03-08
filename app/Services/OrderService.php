<?php

namespace App\Services;

// 目前 for 客製化
class OrderService
{
    // TODO381 比較付款狀態
    public function check_payment_status($validated, $data_before){
        //客製化付款狀態: 0.未付款 -> 1 2 5 / 1.已付訂金 ->2 3 / 2.已付全額 -> 3 / 3.已棄單，待退款 -> 4 / 4.已棄單，已退款 -> X / 5.已棄單，免退款 -> X
        if(array_key_exists('payment_status', $validated)){
            if($data_before['payment_status'] !== $validated['payment_status']){
                switch($data_before['payment_status']){
                    case "未付款":
                        if($validated['payment_status'] !== "已付訂金" && $validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，免退款"){
                            return response()->json(['error' => "只可改到狀態1、2、5"], 400);
                        }
                        break;
                    case "已付訂金":
                        if($validated['payment_status'] !== "已付全額" && $validated['payment_status'] !== "已棄單，待退款"){
                            return response()->json(['error' => "只可改到狀態2、3"], 400);
                        }
                        break;
                    case "已付全額":
                        if($validated['payment_status'] !== "已棄單，待退款"){
                            return response()->json(['error' => "只可改到狀態3"], 400);
                        }
                        break;
                    case "已棄單，待退款":
                        if($validated['payment_status'] !== "已棄單，已退款"){
                            return response()->json(['error' => "只可改到狀態4"], 400);
                        }
                        break;
                }
                return $validated['payment_status'];
            }else{
                return $data_before['payment_status'];
            }
        }else{
            return response()->json(['error' =>'沒有付款狀態欄位', 400]);
        }
    }
}
