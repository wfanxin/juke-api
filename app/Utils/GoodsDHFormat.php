<?php

namespace App\Utils;

//use App\Console\Commands\DHAPI\Libs\Curl;
//use App\Console\Commands\DHAPI\Libs\Token;
//use App\Model\Admin\Collection\CollectionType;
//use App\Model\Admin\Collection\GoodsCategoryCommission;
//use App\Model\Admin\Collection\GoodsCollectionList;
//use App\Model\Admin\Shop\Shop;
use App\Common\Dhgate\Curl;
use App\Common\Dhgate\Token;
use App\Console\Commands\Dhgate\Base\CategoryBase;
use App\Console\Commands\Dhgate\Base\GoodsBase;
use App\Model\Member\Shop\Shop;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 产品数据格式敦煌接口格式
 * Class GoodsFormat
 * @package App\Utils
 */
class GoodsDHFormat
{
    public static $debug = [];
    public static $imgtypeUtil;

    public function __construct()
    {
    }

    /**
     * 线上的商品 覆盖 线上的商品
     * @param $goodsInfo
     * @param $shopInfo
     * @param string $itemCode
     * @return array
     */
    public static function DHGoodsFormat($itemCodeInfo, $shopInfo, $itemCode = '')
    {
        $return = [
            'code' => 0,
            'data' => [],
            'errorMsg' => '',
            'debug' => '',
        ];
        $goodsInfo = self::getGoodsInfoUtil($itemCodeInfo['sourceItemCode'], $itemCodeInfo['sourceShopId']);
        if (isset($goodsInfo['error']) && $goodsInfo['error']) {
            $return['code'] = $goodsInfo['error'];
            $return['errorMsg'] = $goodsInfo['message'];
            self::$debug[] = "获取产品信息失败";
            $return['debug'] = self::$debug;
            return $return;
        }
//        @file_put_contents(storage_path("logs/")."goodsCopyImageError.log", date("Y-m-d H:i:s").json_encode($goodsInfo)."\n", FILE_APPEND);

        $itemBase = $goodsInfo['info']['itemBase'];
        // 处理商品标题防止重复
        $itemBase['itemName'] = preg_replace("/[ \x{00a0}\x{1680}\x{2000}-\x{200a}\x{2028}\x{2029}\x{202f}\x{205f}\x{3000}\x{feff}\x{2060}]/u", " ", $itemBase['itemName']);
        $itemBase['itemName'] = str_replace("&#039;", "'", $itemBase['itemName']);
        $itemBase['keyWord1'] = isset($itemBase['keyWord1']) && $itemBase['keyWord1'] ? trim($itemBase['keyWord1']) : '';
        $itemBase['keyWord1'] = isset($itemBase['keyWord2']) && $itemBase['keyWord2'] ? trim($itemBase['keyWord2']) : '';
        $itemBase['keyWord1'] = isset($itemBase['keyWord3']) && $itemBase['keyWord3'] ? trim($itemBase['keyWord3']) : '';
        $itemBase['htmlContent'] = $goodsInfo['html']['htmlContent'];
        // 主图处理
        $shopData = [
            'method' => 'refresh_token',
            'refresh_token' => $shopInfo['refresh_token'],
            'shop_id'=>$shopInfo['id'],
            'platform_id'=>$shopInfo['platform_id']
        ];
        self::$debug[] = "access token获取开始";
        $libTokenUtil = new Token($shopInfo['platform_id']);
        $accessToken = $libTokenUtil->getAccessToken($shopData);
        if (isset($accessToken['code']) && $accessToken['code'] == false) {
//            @file_put_contents(storage_path("logs/")."goodsAddCommonError.log", date("Y-m-d H:i:s")."===店铺->{$shopInfo['shop']},token过期，请更新后重试".json_encode($accessToken)."\n", FILE_APPEND);
            $return['code'] = 1;
            $return['errorMsg'] = "{$shopInfo['shop']}的token过期，请更新";
            self::$debug[] = "{$shopInfo['shop']}的token过期，请更新";
            $return['debug'] = self::$debug;
            return $return;
        }

        $eidtImageNumber = config('global.goods_copy_edit_Image_number');
        $tempImgList = $goodsInfo['info']['itemImgList'];
        $itemImgList = [];
        foreach ($tempImgList as $k => $v) {
            if ($k < $eidtImageNumber) {
                unset($tempImgList[$k]);

                // 下载编辑并上传图片
                $imrPre = config("admin.dh_api.resource");
                $newImageInfo = self::imageEditUtil($imrPre."/".$goodsInfo['info']['itemImgList'][$k]['imgUrl']);
                if (isset($newImageInfo['error']) && $newImageInfo['error']) {
                    if (is_file($newImageInfo)) {
                        @unlink($newImageInfo);
                    }
                    self::$debug[] = "产品主图编辑失败";
                    $return['code'] = $newImageInfo['error'];
                    $return['errorMsg'] = $newImageInfo['message'];
                    $return['debug'] = self::$debug;
                    return $return;
                }
                $uploadResult = self::imageFormatUploadDh($itemCodeInfo['memId'], $newImageInfo, $accessToken, [$shopInfo['shop']]);
                if ((isset($uploadResult['error']) && $uploadResult['error']) ||
                    (isset($uploadResult['status']) && isset($uploadResult['status']['code']) && $uploadResult['status']['code'] != "00000000") ||
                    (!isset($uploadResult['productImg']))) {
                    if (is_file($newImageInfo)) {
                        @unlink($newImageInfo);
                    }
                    if (isset($uploadResult['error']) && $uploadResult['error']) {
                        self::$debug[] = "产品主图编辑失败1".$uploadResult['message'];
                        $return['code'] = $uploadResult['error'];
                        $return['errorMsg'] = $uploadResult['message'];
                        $return['debug'] = self::$debug;
                        return $return;
                    } else if ((isset($uploadResult['status']) && isset($uploadResult['status']['code']) && $uploadResult['status']['code'] != "00000000")) {
                        self::$debug[] = "产品主图编辑失败2".json_encode($uploadResult);
                        $return['code'] = $uploadResult['status']['code'];
                        $return['errorMsg'] = json_encode($uploadResult);
                        $return['debug'] = self::$debug;
                        return $return;
                    } else {
                        self::$debug[] = "产品主图编辑失败3".json_encode($uploadResult);
                        $return['code'] = '7777';
                        $return['errorMsg'] = json_encode($uploadResult);
                        $return['debug'] = self::$debug;
                        return $return;
                    }
                }
                if (is_file($newImageInfo)) {
                    @unlink($newImageInfo);
                }
                // 添加主图
                $itemImgList[] = [
                    "imgUrl" => $uploadResult['productImg']['l_imgurl'],
                    "imgMd5" => $uploadResult['productImg']['l_imgmd5'],
                    "type" => 1
                ]; // 主图

                if ($k == 0) {
                    if (!$itemCode) { // 非编辑时添加广告图
                        $itemImgList[] = [
                            "imgUrl" => $uploadResult['productImg']['l_imgurl'],
                            "imgMd5" => $uploadResult['productImg']['l_imgmd5'],
                            "type" => 3
                        ]; // 广告图
                    }
                }
            } else {
                break;
            }
        }
        if ($tempImgList) { // 添加未编辑部分主图
            foreach($tempImgList as $vvvv){
                if ($vvvv['type'] != 3) { // 删除广告图
                    $itemImgList[] = $vvvv;
                }
            }
        }

        unset($goodsInfo['info']['itemSaleSetting']['minWholesaleQty']);
        unset($goodsInfo['info']['itemPackage']['itemWeigthRange']);

        // 最大购买数量不能大于10000
        if ($goodsInfo['info']['itemSaleSetting']['maxSaleQty'] > 10000){
            $goodsInfo['info']['itemSaleSetting']['maxSaleQty'] = 10000;
        }

        // sku处理
        $itemSkuListTemp = $goodsInfo['info']['itemSkuList'];
        $itemSkuList = [];
        foreach($itemSkuListTemp as $skuK =>$sku) {
            unset($sku['skuId']);
            unset($sku['skuMD5']);
            unset($sku['itemSkuInventoryList']);
            $itemSkuAttrvalList = [];
            if (isset($sku['itemSkuAttrValueList'])) {
                $itemSkuAttrvalList = $sku['itemSkuAttrValueList'];
            }
            if (isset($sku['itemAttrValList'])) {
                $itemSkuAttrvalList = $sku['itemAttrValList'];
            }
            unset($sku['itemSkuAttrValueList']);
            unset($sku['itemAttrValList']);
            if ($itemCode) { // 编辑
                $sku['itemSkuAttrValueList'] = $itemSkuAttrvalList;
            } else { // 新增
                $sku['itemSkuAttrvalList'] = $itemSkuAttrvalList;
            }
            $itemSkuList[] = $sku;
        }
        $jsonItemSkuList = json_encode($itemSkuList);

        $itemSpecSelfDefList = $goodsInfo['info']['itemSpecSelfDefList'];
        $slefDefId = 1000; // 自定义属性第一个id必须为1000
        foreach($itemSpecSelfDefList as $skuK =>$sku) {
            unset($sku['specAttrName']);
            $oldSelfDefId = $sku['attrValId'];
            $sku['attrValId'] = $slefDefId;
            $itemSpecSelfDefList[$skuK] = $sku;
            $jsonItemSkuList = str_replace('"attrValId":'.$oldSelfDefId.',', '"attrValId":'.$slefDefId.',', $jsonItemSkuList);
            $slefDefId++;
        }

        // 自定义属性值不能大于10
        $itemAttrList = $goodsInfo['info']['itemAttrList'];
        $cateInfo = self::getCategoryUtil($goodsInfo['info']['catePubId'], $accessToken);
        if ($itemAttrList) {
            if ($cateInfo){
                foreach($itemAttrList as $k=>$v) {
                    if (! in_array($v['attrId'], $cateInfo)) {
                        if (count($v['itemAttrValList']) > 10) { // 大于10的自定义属性丢弃
                            $tempVal = [];
                            for($i = 0; $i < 10; $i++) {
                                $tempVal[] = $v['itemAttrValList'][$i];
                            }
                            $itemAttrList[$k]['itemAttrValList'] = $tempVal;
                        }
                    }
                }
            }
            $itemAttrList = json_encode($itemAttrList);
        } else {
            $itemAttrList = '';
        }

        $itemBase['itemName'] = self::titleSolt($itemBase['itemName']);

        $goodsData = [
            'catePubId' => $goodsInfo['info']['catePubId'],
            'itemAttrGroupList' => $goodsInfo['info']['itemAttrGroupList'] ? json_encode($goodsInfo['info']['itemAttrGroupList']) : '',
            'itemAttrList' => $itemAttrList,
            'itemBase' => json_encode($itemBase),
            'itemImgList' => json_encode($itemImgList),
            'itemPackage' => json_encode($goodsInfo['info']['itemPackage']),
            'itemSaleSetting' => json_encode($goodsInfo['info']['itemSaleSetting']),
            'itemSkuList' => $jsonItemSkuList,
            'itemSpecSelfDefList' => json_encode($itemSpecSelfDefList),
            'itemWholesaleRangeList' => json_encode($goodsInfo['info']['itemWholesaleRangeList']),
            'vaildDay' => $goodsInfo['info']['vaildDay'],
            'siteId' => $goodsInfo['info']['siteId'],
//            'sizeTemplateId' => $goodsInfo['info']['sizeTemplateId'] ? $goodsInfo['info']['sizeTemplateId'] : '',
//                            'itemInventory' => $goodsInfo['info']['itemInventory'],
            'itemGroupId' => $goodsInfo['info']['itemGroupId'] ? $goodsInfo['info']['itemGroupId'] : '',
            'issample' => $goodsInfo['info']['issample'] ? $goodsInfo['info']['issample'] : '',
        ];
        if ($itemCode) {
            $goodsData['itemCode'] = $itemCode;
        }

        $return['data'] = $goodsData;
        return $return;
    }

    /**
     * 获取并缓存商品信息
     */
    protected static function getGoodsInfoUtil($goodsId, $shopId){
        $goodsInfoRedisKey = config('redisKey.goods_info');
        $goodsInfo = Redis::get(sprintf($goodsInfoRedisKey['key'], $goodsId));
        if(!$goodsInfo || true) {
            $shopUtil = new Shop();
            $shopInfo = $shopUtil->where('id', $shopId)->first();
            $shopData = [
                'method' => 'refresh_token',
                'refresh_token' => $shopInfo['refresh_token'],
                'shop_id'=>$shopInfo['id'],
                'platform_id'=>$shopInfo['platform_id']
            ];
            $libTokenUtil = new Token($shopInfo['platform_id']);
            $accessToken = $libTokenUtil->getAccessToken($shopData);
            if (isset($accessToken['code']) && $accessToken['code'] == false) {
                return [
                    'error' => $accessToken['code'],
                    'message' => "店铺->{$shopInfo['shop']},token过期",
                ];
            }

            $params = [];
            $params['itemcode'] = $goodsId;
            $params['access_token'] = $accessToken;
            $params['isWait'] = 0;
            $comGoodsBase = new GoodsBase();
            $productInfoTmp = $comGoodsBase->goodsGet($params);
            $productInfo = $productInfoTmp['data'];
            if ( (isset($productInfo['status']) && isset($productInfo['status']['code']) && $productInfo['status']['code'] != "00000000") ||
                (isset($productInfo['code']) && $productInfo['code'])
            ) {
                if (isset($productInfo['error']) && $productInfo['error'] == "00000001") {
                    return [
                        'error' => $productInfo['error'],
                        'message' => '商品已删除',
                    ];
                }
                $errorCode = "777";
                if (isset($productInfo['code']) && $productInfo['code']) {
                    $errorCode = $productInfo['code'];
                }
                if (isset($productInfo['status']) && isset($productInfo['status']['code']) && $productInfo['status']['code'] != "00000000") {
                    $errorCode = $productInfo['status']['code'];
                }
//                @file_put_contents(storage_path("logs/")."copyError.log", date("Y-m-d H:i:s")."===店铺->{$shopName},商品->{$goodsId},获取详情异常".json_encode($productInfo)."\n", FILE_APPEND);
//                throw new \Exception("店铺->{$shopName},商品->{$goodsId},获取详情异常", $errorCode);
                return [
                    'error' => $errorCode,
                    'message' => $productInfo,
                ];
            }

            $productHtmlInfoTmp = $comGoodsBase->goodsHtmlGet($params);
            $productHtmlInfo = $productHtmlInfoTmp['data'];
            if ( (isset($productHtmlInfo['status']) && isset($productHtmlInfo['status']['code']) && $productHtmlInfo['status']['code'] != "00000000") ||
                (isset($productHtmlInfo['code']) && $productHtmlInfo['code'])
            ) {
                $errorCode = "777";
                if (isset($productHtmlInfo['code']) && $productHtmlInfo['code']) {
                    $errorCode = $productHtmlInfo['code'];
                }
                if (isset($productHtmlInfo['status']) && isset($productHtmlInfo['status']['code']) && $productHtmlInfo['status']['code'] != "00000000") {
                    $errorCode = $productHtmlInfo['status']['code'];
                }
//                @file_put_contents(storage_path("logs/")."copyError.log", date("Y-m-d H:i:s")."===店铺->{$shopName},商品->{$goodsId},获取html详情异常".json_encode($productHtmlInfo)."\n", FILE_APPEND);
//                throw new \Exception("店铺->{$shopName},商品->{$goodsId},获取html详情异常", $errorCode);
                return [
                    'error' => $errorCode,
                    'message' => $productHtmlInfo,
                ];
            }
            $return = [
                'info' => $productInfo,
                'html' => $productHtmlInfo,
            ];
            Redis::set(sprintf($goodsInfoRedisKey['key'], $goodsId), json_encode($return));
            Redis::expire(sprintf($goodsInfoRedisKey['key'], $goodsId), $goodsInfoRedisKey['ttl']);
        } else {
            $return = json_decode($goodsInfo, true);
        }
        return $return;
    }

    /**
     * 获取缓存分类信息
     */
    protected static function getCategoryUtil($catePubId, $accessToken){
        $categoryInfoKey = config("redisKey.category_info.key");
        $categoryInfo = $goodsInfo = Redis::hget($categoryInfoKey, $catePubId);
        if(!$categoryInfo) {
            $mCategoryBase = new CategoryBase();
            $params['catePubId'] = $catePubId;
            $params['access_token'] = $accessToken;
            $params['isWait'] = 0;
            $categoryGetRes = $mCategoryBase->getCategoryInfo($params);
            $categoryGet = $categoryGetRes['data'];

            $returnTemp = [];
            if ( (isset($categoryGet['status']) && isset($categoryGet['status']['code']) && $categoryGet['status']['code'] != "00000000") ||
                (isset($categoryGet['code']) && $categoryGet['code'])
            ) {
                $errorCode = "775";
                if (isset($categoryGet['code']) && $categoryGet['code']) {
                    $errorCode = $categoryGet['code'];
                }
                if (isset($categoryGet['status']) && isset($categoryGet['status']['code']) && $categoryGet['status']['code'] != "00000000") {
                    $errorCode = $categoryGet['status']['code'];
                }
                // return $errorCode;
                // throw new \Exception("=店铺->{$shopName},获取分类信息异常", $errorCode);
            } else {
                $returnTemp = $categoryGet['categoryPubAttrList'];
                Redis::hset($categoryInfoKey, $catePubId, json_encode($categoryGet));
            }
        } else {
            $returnTemp = json_decode($categoryInfo, true)['categoryPubAttrList'];
        }
        $return = [];
        if ($returnTemp) {
            foreach ($returnTemp as $v) {
                $return[] = $v['attrId'];
            }
        }
        return $return;
    }

    /**
     * 图片上传到敦煌
     */
    protected static function imageFormatUploadDh($memId, $src, $accessToken, $shopName){
        $mGoodsBase = new GoodsBase();
        $res = $mGoodsBase->imageUploadDh($memId, $src, $accessToken, true);
        if ($res['code'] > 0) {
            return [
                'error' => 1,
                'message' => $res['data'],
            ];
        }
        $newImageInfo = $res['data'];
        return $newImageInfo;

//        $saveFileName = basename($src);
//        $image_data = @file_get_contents($src);
//        $libCurlUtil = new Curl();
//        $dhRouterUtil = config('admin.dh_api.router');
//        if (!$image_data) {
//            return [
//                'error' => 1,
//                'message' => "原图获取异常".$src,
//            ];
//        }
//        $base64_image = chunk_split(base64_encode($image_data));
//        // 处理之后上传
//        $timestamp = time();
//        $updateInfo = [
//            'access_token' => $accessToken,
//            'method' => 'dh.album.img.upload',
//            'timestamp' => $timestamp . "000",
//            'v' => '2.0',
//
//            'funType' => 'albu',
//            'imageBannerName' => '',
//            'imageName' => $saveFileName,
//            'imageBase64' => $base64_image
//        ];
//
//        $newImageInfo = $libCurlUtil->post($dhRouterUtil, $updateInfo);
//
//        $tryTime = 0;
//        while( (isset($newImageInfo['status']) && isset($newImageInfo['status']['code']) && $newImageInfo['status']['code'] != "00000000")  ||
//            (isset($newImageInfo['code']) && $newImageInfo['code'])
//        ){
//            $allReTry = ["1"]; // 错误码1为超时,一直重试
//            if ($tryTime >= 3) {
//                $errorCode = "776";
//                if (isset($newImageInfo['code']) && $newImageInfo['code']) {
//                    $errorCode = $newImageInfo['code'];
//                }
//                if (isset($newImageInfo['status']) && isset($newImageInfo['status']['code']) && $newImageInfo['status']['code'] != "00000000") {
//                    $errorCode = $newImageInfo['status']['code'];
//                }
//                return [
//                    'error' => 1,
//                    'message' => "店铺->{$shopName},上传图片异常", $errorCode . "原图地址：" .$src,
//                ];
////                break;
//            }
//            // 其他错误，失败次数递增超过三次返回失败
//            if (!isset($newImageInfo['status']['code']) || !in_array($newImageInfo['status']['code'], $allReTry)) {
//                $tryTime++;
//            }
//            @file_put_contents(storage_path("logs/")."goodsUploadError.log", date("Y-m-d H:i:s")."===店铺->{$shopName},上传图片异常{$tryTime}次".json_encode($newImageInfo)."\n", FILE_APPEND);
//            $newImageInfo = $libCurlUtil->post($dhRouterUtil, $updateInfo);
//        }
//        if (isset($newImageInfo['status']) && isset($newImageInfo['status']['code']) && $newImageInfo['status']['code'] == "00000000") {
////            @unlink($fileName);
//        }
//        return $newImageInfo;
    }

    /**
     * 编辑图片
     */
    protected static function imageEditUtil($src, $legnth = 15){
        // 下载第一图
        try{
//            @file_put_contents(storage_path("logs/")."goodsCopyImageError.log", date("Y-m-d H:i:s").$src."\n", FILE_APPEND);
            $path = storage_path("logs/copyGoodsCacheImg/");
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            $baseName = basename($src);
            if (is_file($path.$baseName)) {
                $imageInfo = file_get_contents($path.$baseName);
            } else {
                $imageInfo = file_get_contents($src);
                file_put_contents($path.$baseName, $imageInfo);
            }

            self::$debug[] = "获取图片成功";
            $fileExtension = pathinfo($src)['extension'];
            $a = rand(10000, 99999) . time();
            $saveFileName = $a . "." . $fileExtension;
            $fileName = $path . $saveFileName;
//            @file_put_contents(storage_path("logs/")."goodsCopyImageError.log", date("Y-m-d H:i:s").$fileName."\n", FILE_APPEND);
            file_put_contents($fileName, $imageInfo);
            // 打开图片
            $img = self::imagesourcesUtil($fileName);
            if (!is_resource($img)) {
                self::$debug[] = "不是图片资源".json_encode($img);
                return [
                    'error' => 1,
                    'message' => "不是图片资源".json_encode($img),
                ];
            }
            $maxX = imagesx($img) - 1;
            $maxY = imagesy($img) - 1;

            $temp = floor($maxX * 0.5);
            if ($temp > $legnth) {
                $legnth = $temp;
            }
            for ($yNum = 0; $yNum <= $maxY; $yNum++) {
                for ($ii = 0; $ii < $legnth; $ii++) {
                    $xNum = rand(0, $maxX);
                    // var_dump("x:".$xNum.",y:".$ii);
                    $rgb = imagecolorat($img, $xNum, $yNum);
                    $subNumR = rand(1, 20) - 10;
                    $r = ($rgb >> 16) & 0xFF;
                    if ($r+$subNumR > 255) {
                        $R = 255;
                    } else if ($r+$subNumR < 0) {
                        $R = 0;
                    } else {
                        $R = $r+$subNumR;
                    }
                    $subNumG = rand(1, 20) - 10;
                    $g = ($rgb >> 8) & 0xFF;
                    if ($g+$subNumG > 255) {
                        $G = 255;
                    } else if ($g+$subNumG < 0) {
                        $G = 0;
                    } else {
                        $G = $g+$subNumG;
                    }
                    $subNumB = rand(1, 20) - 10;
                    $b = $rgb & 0xFF;
                    if ($b+$subNumB > 255) {
                        $B = 255;
                    } else if ($b+$subNumB < 0) {
                        $B = 0;
                    } else {
                        $B = $b+$subNumB;
                    }
                    $col = imageColorAllocate($img, $R, $G, $B);
                    imagesetpixel($img, $xNum, $yNum, $col);
                }
            }
            self::outputUtil($img, $fileName);
            return $fileName;
        }catch (\Exception $e){
            self::$debug[] = date("Y-m-d H:i:s") . "=>ErrorMsg:" . $e->getMessage() . "ErrorFile:" . $e->getFile() . "ErrorLine:" . $e->getLine();
            return [
                'error' => 1,
                'message' => "图片编辑错误：文件:{$e->getFile()};行号：{$e->getLine()}；信息：{$e->getMessage()}",
            ];
        }
    }

    /**
     * 获取图片类型并打开图像资源
     */
    protected static function imagesourcesUtil($imgad){
        $imagearray=getimagesize($imgad);
        switch($imagearray[2]) {
            case 1://gif
                self::$imgtypeUtil = 1;
                $img = imagecreatefromgif($imgad);
                break;
            case 2://jpeg
                self::$imgtypeUtil = 2;
                $img = imagecreatefromjpeg($imgad);
                break;
            case 3://png
                self::$imgtypeUtil = 3;
                $img = imagecreatefrompng($imgad);
                break;
            default:
                $img = '';
        }
        return $img;
    }

    /**
     * 输出图像
     */
    protected static function outputUtil($image, $fileName){
        switch(self::$imgtypeUtil){
            case 1:
                imagegif($image, $fileName);
                break;
            case 2:
                imagejpeg($image, $fileName, 100);
                break;
            case 3:
                imagepng($image, $fileName);
                break;
            default:
                return false;
        }
    }

    /**
     * 修改标题防止重复
     */
    protected static function titleSolt($name){
        $str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $titleFix = '';
        for($i=0; $i<4; $i++){
            if($i == 1){
                $titleFix.=rand(0,9);
            }else{
                $k = rand(0,61);
                $titleFix .= $str[$k];
            }
        }
        $titleFix .= "#";

        if (preg_match("/#$/", $name)){
            $title = preg_replace("/.{5}#$/", "", $name);
            $returnTitle = $title . " " . $titleFix;
        } else {
//            if ( strlen($name) < 135){ // 以前包含各种字符计算长度，现在只计算字符长度
            if ( self::countStrLength($name) < 136){
                $returnTitle = $name . " " . $titleFix;
            } else {
//                $title = substr($name, 0, 134); // 以前包含各种字符计算长度，现在只计算字符长度
                $title = self::countStrLength($name, 0, 135);
                $returnTitle = $title . " " . $titleFix;
            }
        }
        return $returnTitle;
    }

    /**
     * 计算字符串长度，和截取非空白字符串长度
     */
    protected static function countStrLength($str, $isCount = 1, $getLength = 135){
        if ($isCount) {
            $str = preg_replace("/[^A-Za-z0-9]/", "", str_replace(" ", "", $str));
            $return = strlen($str);
        } else {
            $strArr = explode(" ", $str);
            $addLength = 0;
            $returnStr = [];
            foreach ($strArr as $v) {
                if (empty($v)) {
                    continue;
                }
                $nowLength = strlen(preg_replace("/[^A-Za-z0-9]/", "", $v));
                $addLength += $nowLength;
                if ($addLength >= $getLength) {
                    if ($addLength == $getLength) {
                        $returnStr[] = $v;
                        break;
                    } else {
                        if ($nowLength - ($addLength-$getLength) > 0) {
                            $v = substr($v, 0, $nowLength - ($addLength-$getLength));
                            $returnStr[] = $v;
                        }
                        break;
                    }
                }
                $returnStr[] = $v;
            }
            if ($returnStr) {
                $return = join(" ", $returnStr);
            }
        }
        return $return;
    }
}
