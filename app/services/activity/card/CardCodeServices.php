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
declare (strict_types=1);

namespace app\services\activity\card;

use app\dao\activity\card\CardCodeDao;
use app\jobs\activity\card\CardJob;
use app\services\BaseServices;
use app\services\order\StoreCartServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserServices;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Route as Url;


/**
 * 卡密
 * Class CardCodeServices
 * @package app\services\activity\card
 * @mixin CardCodeDao
 */
class CardCodeServices extends BaseServices
{

    /**
     * @var CardCodeDao
     */
    #[Inject]
    protected CardCodeDao $dao;

    public function getList($where)
    {
        [$page, $limit] = $this->getPageValue();
        $count = $this->dao->count($where);
        $list = $this->dao->getList($where, $page, $limit);
        if (!$list) {
            return [];
        }
        $uids = array_unique(array_column($list, 'uid'));
        $card_ids = array_unique(array_column($list, 'card_id'));
        $batch_ids = array_unique(array_column($list, 'batch_id'));
        $userList = app()->make(UserServices::class)->search(['uid' => $uids])->column('nickname', 'uid');
        $cardList = app()->make(CardGiftServices::class)->search(['id' => $card_ids])->column('name', 'id');
        $batchList = app()->make(CardBatchServices::class)->search(['id' => $batch_ids])->column('name', 'id');

        foreach ($list as &$item) {
            $item['nickname'] = $userList[$item['uid']] ?? '';
            $item['card_name'] = $cardList[$item['card_id']] ?? '';
            $item['batch_name'] = $batchList[$item['batch_id']] ?? '';
        }
        return compact('list', 'count');
    }

    /**
     * 添加卡信息
     *
     * 该方法用于批量添加卡号和密码信息到数据库中。它接受一个包含批次ID、卡的数量、卡号前缀和后缀、
     * 以及密码类型和长度的数组作为参数。方法首先校验输入数据的合法性，然后生成指定数量的卡号和密码，
     * 并将它们批量插入到数据库中。
     * @param array $data 包含批次ID、总数量、卡号前缀和后缀、密码类型和长度的数据数组
     * @return true
     * @throws \Random\RandomException
     * User: liusl
     * DateTime: 2025/5/9 11:30
     */
    public function addCard(array $data): bool
    {
        // 参数校验优化：明确判断是否存在且大于0
        if (!isset($data['batch_id']) || (int)$data['batch_id'] <= 0) {
            throw new ValidateException('批次ID必须为正整数');
        }
        if (!isset($data['total_num']) || (int)$data['total_num'] <= 0) {
            throw new ValidateException('数量必须为正整数');
        }

        // 校验 card_prefix 和 card_suffix 是否存在
        if (!isset($data['card_prefix'])) {
            $data['card_prefix'] = '';
        }
        if (!isset($data['card_suffix'])) {
            $data['card_suffix'] = '';
        }

//        // 控制最大生成数量，防止内存溢出
//        $maxGenerateLimit = 100000; // 示例限制为10万张卡

//        if ($length > 8 || pow(10, $length) > $maxGenerateLimit) {
//            throw new ValidateException("生成数量超出限制");
//        }

        // 密码相关参数校验
        if (!isset($data['pwd_type']) || !isset($data['pwd_num'])) {
            throw new ValidateException('密码类型或长度缺失');
        }

        $max = (int)$data['total_num'];
        $length = (int)strlen((string)$data['total_num']);
        $insertData = [];

        for ($i = 1; $i <= $max; $i++) {
            $num = str_pad((string)$i, $length, '0', STR_PAD_LEFT);
            $insertData[] = [
                'card_number' => $data['card_prefix'] . $num . $data['card_suffix'],
                'card_pwd' => $this->generatePassword((array)$data['pwd_type'], (int)$data['pwd_num']),
                'batch_id' => $data['batch_id'],
                'add_time' => time(),
            ];

            // 每满100条插入一次并清空缓存
            if (count($insertData) >= 1000) {
                $this->dao->saveAll($insertData);
                $insertData = [];
            }
        }

        // 插入剩余数据
        if (!empty($insertData)) {
            $this->dao->saveAll($insertData);
        }

        return true;
    }

    /**
     * 生成指定类型的随机密码
     *
     * @param array $types 密码包含的字符类型，1代表数字，2代表小写字母，3代表大写字母，默认包含所有类型
     * @param int $num 密码的长度，默认为10
     * @return string 生成的密码字符串
     * @throws ValidateException 如果密码长度小于等于0或密码类型不在允许范围内，则抛出异常
     */
    public function generatePassword(array $types = [1, 2, 3], int $num = 10): string
    {
        // 参数校验
        if ($num <= 0) {
            throw new ValidateException('密码长度必须大于0');
        }

        $validTypes = [1, 2, 3];
        foreach ($types as $type) {
            if (!is_int($type) || !in_array($type, $validTypes, true)) {
                throw new ValidateException('密码类型必须是 1、2 或 3');
            }
        }

        // 定义每种类型的字符集合
        $typeMap = [
            1 => '0123456789',
            2 => 'abcdefghijklmnopqrstuvwxyz',
            3 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];

        // 根据选择的类型构建字符池
        $charPool = '';
        foreach ($types as $type) {
            $charPool .= $typeMap[$type];
        }

        // 确保字符池不为空
        if ($charPool === '') {
            throw new ValidateException('至少选择一种密码类型');
        }

        // 初始化密码字符串和字符池长度
        $password = '';
        $poolLength = strlen($charPool) - 1;

        // 生成指定长度的密码
        for ($i = 0; $i < $num; $i++) {
            $randomIndex = random_int(0, $poolLength);
            $password .= $charPool[$randomIndex];
        }

        return $password;
    }

    /**
     * 备注
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/6/5 16:48
     */
    public function formRemark(int $id)
    {
        $card = $this->dao->get($id);
        if (!$card) {
            throw new ValidateException('数据不存在');
        }
        $field[] = Form::input('remark', '备注', $card['remark'] ?? '')->required();
        return create_form('备注', $field, Url::buildUrl('/marketing/card/code/remark/' . $id), 'POST');
    }

    /**
     * 验证礼品卡
     * @param string $card_number
     * @param string $card_pwd
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/6/5 16:49
     */
    public function verifyGiftCode($card_number, $card_pwd)
    {
        $info = $this->dao->get(['card_number' => $card_number, 'card_pwd' => $card_pwd]);
        if (!$info) {
            throw new ValidateException('礼品卡卡号或密码错误');
        }
        if ($info['status'] != 2) {
            throw new ValidateException('礼品卡已被使用');
        }
        if (!$info['card_id']) {
            throw new ValidateException('礼品卡未绑定');
        }
        $giftInfo = app()->make(CardGiftServices::class)->getInfo($info['card_id']);
        if (!$giftInfo) {
            throw new ValidateException('礼品卡不存在');
        }
        if ($giftInfo['status'] != 1) {
            throw new ValidateException('礼品卡已失效');
        }
        if ($giftInfo['valid_type'] == 2 && ($giftInfo['start_time'] > time() || $giftInfo['end_time'] < time())) {
            throw new ValidateException('请在有效期内激活~');
        }
        return $giftInfo;
    }

    /**
     * 使用
     * @param string $card_number
     * @param string $card_pwd
     * @param array $product
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/6/5 16:49
     */
    public function receiveGiftCode(string $card_number, string $card_pwd, array $product, int $uid)
    {
        $giftInfo = $this->verifyGiftCode($card_number, $card_pwd);
        $info = $this->dao->get(['card_number' => $card_number, 'card_pwd' => $card_pwd]);
        if ($giftInfo['type'] == 1) {
            $data = $this->transaction(function () use ($card_number, $card_pwd, $uid, $giftInfo, $info) {
                //兑换余额
                $userMoneyServices = app()->make(UserMoneyServices::class);
                $userServices = app()->make(UserServices::class);
                $user = $userServices->get($uid);
                $edit['now_money'] = bcadd($user['now_money'], $giftInfo['balance'], 2);
                $res = $userServices->update($uid, $edit);
                $res = $res && $userMoneyServices->income('card_add_money', $user['uid'], $giftInfo['balance'], $edit['now_money'], $info['id']);
                //修改卡密状态
                if ($res) {
                    $info->uid = $uid;
                    $info->status = 1;
                    $info->active_time = time();
                    $res = $info->save();
                }
                return !!$res;
            });
        } else {
            //验证商品
            if (!$giftInfo['product']) {
                throw new ValidateException('礼品卡未绑定商品');
            }
            if ($giftInfo['exchange_type'] == 1) {
                //固定
                $product_list = $giftInfo['product'];
            } else {
                //任选
                $uniques = array_column($giftInfo['product'], 'unique');
                foreach ($product as $item) {
                    if (!in_array($item['unique'], $uniques)) {
                        throw new ValidateException('礼品卡商品不匹配');
                    }
                }
                $product_list = $product;
            }
            $storeCartServices = app()->make(StoreCartServices::class);
            $cartList = [];
            foreach ($product_list as $item) {
                if ($item['limit_num'] > 0) {
                    [$cartIds, $cartNum] = $storeCartServices->setCart($uid, $item['product_id'], $item['limit_num'], $item['unique'], 9, true, $info['id']);
                    $cartList[] = ['cartId' => $cartIds, 'cartNum' => $cartNum];
                }
            }
            $data = $cartList;
        }
        //校验卡密使用数量
        CardJob::dispatchDo('allocationCardGift', [$giftInfo['id']]);
        return ['type' => $giftInfo['type'], 'data' => $data];
    }

    /**
     * 卡密使用
     * @param array $order
     * @return true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/24 10:57
     */
    public function useCode(array $order)
    {
        $info = $this->dao->get($order['activity_id']);
        if (!$info) {
            throw new ValidateException('礼品卡数据不存在');
        }
        if ($info['status'] != 2) {
            throw new ValidateException('礼品卡已被使用');
        }
        //修改卡密状态
        $info->uid = $order['uid'];
        $info->status = 1;
        $info->active_time = time();
        $res = $info->save();
        return true;
    }

    /**
     * 过期
     * @return true
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/6/5 17:14
     */
    public function expireCode()
    {
        $list = $this->dao->search(['status' => 2])->where('end_time', '<>', 0)->where('end_time', '<', time())->order('id asc')->select()->toArray();
        if (!$list) {
            return true;
        }
        $updateData = [
            'status' => 3,
        ];
        foreach ($list as $k => $item) {
            $ids[] = $item['id'];
            // 每满100条插入一次并清空缓存
            if (count($ids) >= 500) {
                $this->dao->batchUpdate($ids, $updateData);
                $ids = [];
            }
        }
        if (count($ids)) {
            $this->dao->batchUpdate($ids, $updateData);
        }
        return true;
    }

}
