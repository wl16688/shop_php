<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\system;


use app\services\BaseServices;
use app\services\community\CommunityRelevanceServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductCouponServices;
use app\services\product\product\StoreProductRelationServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\user\UserRelationServices;
use crmeb\exceptions\AdminException;
use think\facade\Config;
use think\facade\Db;

/**
 * 清除数据
 * Class SystemClearServices
 * @package app\services\system
 */
class SystemClearServices extends BaseServices
{
    /**
     * 清除表数据
     * @param string|array $table_name
     * @param $status
     */
    public function clearData($table_name, bool $status)
    {
        $prefix = config('database.connections.' . config('database.default'))['prefix'];
        if (is_string($table_name)) {
            $clearData = [$table_name];
        } else {
            $clearData = $table_name;
        }
        foreach ($clearData as $name) {
            if ($status) {
                Db::execute('TRUNCATE TABLE ' . $prefix . $name);
            } else {
                Db::execute('DELETE FROM' . $prefix . $name);
            }
        }
    }

    /**
     * 递归删除文件,只能删除 public/uploads下的文件
     * @param $dirName
     * @param bool $subdir
     */
    public function delDirAndFile(string $dirName, $subdir = true)
    {
        if (strstr($dirName, 'public/uploads') === false) {
            return true;
        }
        if ($handle = @opendir("$dirName")) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dirName/$item"))
                        $this->delDirAndFile("$dirName/$item", false);
                    else
                        @unlink("$dirName/$item");
                }
            }
            closedir($handle);
            if (!$subdir) @rmdir($dirName);
        }
    }

    /**
     * 替换域名
     * @param string $url
     * @return mixed
     */
    public function replaceSiteUrl(string $url)
    {
        $siteUrl = sys_config('site_url');
        $siteUrlJosn = str_replace('://', ':\\\/\\\/', $siteUrl);
        $valueJosn = str_replace('://', ':\\\/\\\/', $url);
        $prefix = Config::get('database.connections.' . Config::get('database.default') . '.prefix');
        $sql = [
            "UPDATE `{$prefix}system_attachment` SET `att_dir` = replace(att_dir ,'{$siteUrl}','{$url}'),`satt_dir` = replace(satt_dir ,'{$siteUrl}','{$url}')",
            "UPDATE `{$prefix}store_product` SET `image` = replace(image ,'{$siteUrl}','{$url}'),`slider_image` = replace(slider_image ,'{$siteUrlJosn}','{$valueJosn}')",
            "UPDATE `{$prefix}store_product_attr_value` SET `image` = replace(image ,'{$siteUrl}','{$url}')",
            "UPDATE `{$prefix}store_seckill` SET `image` = replace(image ,'{$siteUrl}','{$url}'),`images` = replace(images,'{$siteUrlJosn}','{$valueJosn}')",
            "UPDATE `{$prefix}store_combination` SET `image` = replace(image ,'{$siteUrl}','{$url}'),`images` = replace(images,'{$siteUrlJosn}','{$valueJosn}')",
            "UPDATE `{$prefix}store_bargain` SET `image` = replace(image ,'{$siteUrl}','{$url}'),`images` = replace(images,'{$siteUrlJosn}','{$valueJosn}')",
            "UPDATE `{$prefix}system_config` SET `value` = replace(value ,'{$siteUrlJosn}','{$valueJosn}')",
            "UPDATE `{$prefix}article_category` SET `image` = replace(`image` ,'{$siteUrl}','{$url}')",
            "UPDATE `{$prefix}article` SET `image_input` = replace(`image_input` ,'{$siteUrl}','{$url}')",
            "UPDATE `{$prefix}article_content` SET `content` = replace(`content` ,'{$siteUrl}','{$url}')",
            "UPDATE `{$prefix}store_product_category` SET `pic` = replace(`pic` ,'{$siteUrl}','{$url}')",
            "UPDATE `{$prefix}system_group_data` SET `value` = replace(value ,'{$siteUrlJosn}','{$valueJosn}')",
            "UPDATE `{$prefix}store_product_description` SET `description`= replace(description,'{$siteUrl}','{$url}')"
        ];
        return $this->transaction(function () use ($sql) {
            try {
                foreach ($sql as $item) {
                    Db::execute($item);
                }
            } catch (\Throwable $e) {
                throw new AdminException('替换失败,失败原因:' . $e->getMessage());
            }
        });
    }

    public function recycleProduct(array $ids)
    {
        $product = app()->make(StoreProductServices::class);
        //清除规格表数据
        /** @var StoreProductAttrServices $ProductAttr */
        $productAttr = app()->make(StoreProductAttrServices::class);
        $productAttr->delete([['product_id', 'in', $ids], ['type', '=', '0']]);

        /** @var StoreProductAttrResultServices $productAttrResult */
        $productAttrResult = app()->make(StoreProductAttrResultServices::class);
        $productAttrResult->delete([['product_id', 'in', $ids], ['type', '=', '0']]);

        /** @var StoreProductAttrValueServices $productAttrValue */
        $productAttrValue = app()->make(StoreProductAttrValueServices::class);
        $productAttrValue->delete([['product_id', 'in', $ids], ['type', '=', '0']]);

        //删除商品详情
        /** @var StoreDescriptionServices $productDescription */
        $productDescription = app()->make(StoreDescriptionServices::class);
        $productDescription->delete([['product_id', 'in', $ids], ['type', '=', '0']]);

        //删除商品关联数据
        /** @var StoreProductRelationServices $productRelation */
        $productRelation = app()->make(StoreProductRelationServices::class);
        $productRelation->delete([['product_id', 'in', $ids]]);

        //删除商品关联优惠券数据
        /** @var StoreProductCouponServices $productCoupon */
        $productCoupon = app()->make(StoreProductCouponServices::class);
        $productCoupon->delete([['product_id', 'in', $ids]]);

        //删除商品收藏记录
        /** @var UserRelationServices $productRelation */
        $productRelation = app()->make(UserRelationServices::class);
        $productRelation->delete([['relation_id', 'in', $ids], ['category', '=', UserRelationServices::CATEGORY_PRODUCT]]);

        //删除商品的评论
        /** @var StoreProductReplyServices $productReply */
        $productReply = app()->make(StoreProductReplyServices::class);
        $productReply->delete([['product_id', 'in', $ids]]);

        //删除帖子里对应商品
        $communityRelevanceServices = app()->make(CommunityRelevanceServices::class);
        $communityRelevanceServices->search(['right_id' => $ids, 'type' => CommunityRelevanceServices::TYPE_COMMUNITY_PRODUCT])->delete();

        $product->search(['id' => $ids])->delete();
        return true;
    }
}
