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
//declare (strict_types=1);
namespace app\services\user;

use app\jobs\user\UserFriendsJob;
use app\jobs\user\UserJob;
use app\jobs\user\UserLevelJob;
use app\jobs\user\UserSpreadJob;
use app\jobs\user\UserSvipJob;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\newcomer\StoreNewcomerServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\agent\AgentLevelServices;
use app\services\agent\DivisionApplyServices;
use app\services\agent\PromoterApplyServices;
use app\services\BaseServices;
use app\dao\user\UserDao;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\diy\DiyServices;
use app\services\message\service\StoreServiceRecordServices;
use app\services\message\service\StoreServiceServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderTakeServices;
use app\services\other\QrcodeServices;
use app\services\product\product\StoreProductLogServices;
use app\services\other\queue\QueueServices;
use app\services\message\SystemMessageServices;
use app\services\user\channel\ChannelMerchantServices;
use app\services\user\group\UserGroupServices;
use app\services\user\label\UserLabelServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\level\UserLevelServices;
use app\services\wechat\WechatUserServices;
use app\services\work\WorkClientServices;
use app\services\work\WorkMemberServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\FormBuilder as Form;
use crmeb\services\FormBuilder;
use crmeb\services\HttpService;
use crmeb\services\SystemConfigService;
use crmeb\services\wechat\OfficialAccount;
use FormBuilder\UI\Iview\Components\Group;
use think\annotation\Inject;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Log;
use think\facade\Route as Url;
use think\Model;

/**
 *
 * Class UserServices
 * @package app\services\user
 * @mixin UserDao
 */
class UserServices extends BaseServices
{
    /**
     * 性别
     * @var array
     */
    public $sex = [
        '男' => 1,
        '女' => 2,
        '保密' => 0,
        0 => 1,//男
        1 => 2,//女
        2 => 0,//保密
    ];

    /**
     * 逆转数据
     * @var int[]
     */
    public $rSex = [
        0 => 2,
        1 => 0,
        2 => 1,
    ];

    /**
     * 用户默认补充信息
     * @var array
     */
    public array $defaultExtendInfo = [
        ['info' => '姓名', 'tip' => '请填写真实姓名', 'format' => 'text', 'label' => '文本', 'param' => 'real_name', 'single' => '', 'singlearr' => [], 'required' => 0, 'use' => 0, 'user_show' => 0, 'sort' => 1],
        ['info' => '性别', 'tip' => '请选择性别', 'format' => 'radio', 'label' => '单选项', 'param' => 'sex', 'single' => '', 'singlearr' => ['男', '女', '保密'], 'required' => 0, 'use' => 0, 'user_show' => 0, 'sort' => 2],
        ['info' => '生日', 'tip' => '请选择出生日期', 'format' => 'date', 'label' => '日期', 'param' => 'birthday', 'single' => '', 'singlearr' => [], 'required' => 0, 'use' => 0, 'user_show' => 0, 'sort' => 3],
        ['info' => '身份证', 'tip' => '请填写身份证', 'format' => 'id', 'label' => '身份证', 'param' => 'card_id', 'single' => '', 'singlearr' => [], 'required' => 0, 'use' => 0, 'user_show' => 0, 'sort' => 4],
        ['info' => '地址', 'tip' => '请填写地址', 'format' => 'address', 'label' => '地址', 'param' => 'address', 'single' => '', 'singlearr' => [], 'required' => 0, 'use' => 0, 'user_show' => 0, 'sort' => 5],
        ['info' => '备注', 'tip' => '请填写补充备注内容', 'format' => 'text', 'label' => '文本', 'param' => 'mark', 'single' => '', 'singlearr' => [], 'required' => 0, 'use' => 0, 'user_show' => 0, 'sort' => 6],
    ];

    /**
     * @var UserDao
     */
    #[Inject]
    protected UserDao $dao;

    /**
     * 获取用户信息
     * @param int $uid
     * @param string $field
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserInfo(int $uid, $field = '*')
    {
        if (is_string($field)) $field = explode(',', $field);
        return $this->dao->get($uid, $field);
    }

    /**
     * 是否存在
     * @param int $uid
     * @return bool
     */
    public function userExist(int $uid)
    {
        return $this->dao->exist($uid);
    }

    /**
     * 获取用户缓存信息
     * @param int $uid
     * @param string $field
     * @param int $expire
     * @return bool|mixed|null
     */
    public function getUserCacheInfo(int $uid, int $expire = 60)
    {
        return $this->cacheTag()->remember('user_info_' . $uid, function () use ($uid) {
            return $this->dao->get($uid);
        }, $expire);
    }

    /**
     * 检测用户身份
     * @param int $uid
     * @return
     */
    public function checkUserTag(int $uid)
    {
        $data = [];
        $userInfo = $this->getUserCacheInfo($uid);
        //1等级会员,2付费会员,3推广员,4采购商
        if ($userInfo) {
            //等级
            $is_open_level = (int)sys_config('member_func_status', 0);
            if ($is_open_level && $userInfo['level'] > 0) {
                $data[] = 1;
            }
            //付费会员
            if ($this->checkUserIsSvip($uid)) {
                $data[] = 2;
            }
            //推广员
            if ($this->checkUserPromoter($uid)) {
                $data[] = 3;
            }
            //采购商
            if (app()->make(ChannelMerchantServices::class)->isChannel($uid)) {
                $data[] = 4;
            }
        }
        return $data;
    }

    /**
     * 检测用户是否是svip
     * @param int $uid
     * @return bool
     */
    public function checkUserIsSvip(int $uid)
    {
        if ($uid && sys_config('member_card_status', 1)) {
            $userInfo = $this->getUserCacheInfo($uid);
            return $userInfo && (($userInfo['is_money_level'] > 0 && $userInfo['overdue_time'] > time()) || $userInfo['is_ever_level'] > 0);
        } else {
            return false;
        }
    }

    /**
     * 判断用户是否会员等级和付费会员
     * @param array $uid
     * @return array
     * User: liusl
     * DateTime: 2024/8/27 16:45
     */
    public function checkIsVip(array $uid): array
    {
        // 输入验证：确保 $uid 是一个非空数组且所有元素为整数
        if (empty($uid) || !array_reduce($uid, function ($carry, $item) {
                return $carry && is_int($item);
            }, true)) {
            return [];
        }

        try {
            /** @var SystemUserLevelServices $levelServices */
            $levelServices = app()->make(SystemUserLevelServices::class);

            // 获取会员等级列表
            $levelList = $levelServices->getColumn(['is_show' => 1, 'is_del' => 0], 'grade', 'id');
            if (!is_array($levelList)) {
                return [];
            }

            // 获取系统配置
            $is_open_member = (int)sys_config('member_card_status', 0); // 转换为整数以确保类型安全
            $is_open_level = (int)sys_config('member_func_status', 0); // 转换为整数以确保类型安全

            // 查询用户信息
            $userList = $this->dao->getList(['uid' => $uid], 'uid,is_money_level,overdue_time,level');
            if (!$userList) {
                throw new AdminException('获取用户信息失败');
            }

            $data = [];
            foreach ($userList as $item) {
                // 初始化变量
                $level_name = '';
                $vip_status = 0;

                // 判断 VIP 状态
                if ($is_open_member && isset($item['is_money_level']) && $item['is_money_level'] > 0 && isset($item['overdue_time']) && $item['overdue_time'] > time()) {
                    $vip_status = 1;
                }

                // 判断会员等级名称
                if ($is_open_level && isset($item['level']) && $item['level'] && isset($levelList[$item['level']])) {
                    $level_name = $levelList[$item['level']];
                }

                // 构建结果
                $data[$item['uid']] = [
                    'level_name' => $level_name,
                    'vip_status' => $vip_status,
                ];
            }

            return $data;
        } catch (\Throwable $e) {
            // 异常处理：记录错误日志并抛出中文错误提示
            Log::error("Error in checkIsVip: " . $e->getMessage());
            throw new AdminException('检查 VIP 状态时发生错误：' . $e->getMessage());
        }
    }


    /**
     * 获取用户列表
     * @param array $where
     * @param string $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserList(array $where, string $field = '*', $order = ''): array
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $page, $limit, $order);
        $count = $this->count($where);
        return compact('list', 'count');
    }

    /**
     * 列表条数
     * @param array $where
     * @return int
     */
    public function getCount(array $where, bool $is_list = false)
    {
        if ($is_list) {
            return $this->dao->getCountList($where);
        } else {
            return $this->dao->getCount($where);
        }
    }

    /**
     * 保存用户信息
     * @param $user
     * @param int $spreadUid
     * @param string $userType
     * @return \crmeb\basic\BaseModel|Model
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \Random\RandomException
     * User: liusl
     * DateTime: 2025/4/3 下午2:35
     */
    public function setUserInfo($user, int $spreadUid = 0, string $userType = 'wechat')
    {
        // 生成安全的随机数种子
        mt_srand(crc32(uniqid()));

        // 获取当前时间戳
        $currentTime = time();

        // 获取用户 IP 地址，并处理可能的空值情况
        $ip = app()->request->ip() ?: '127.0.0.1';

        // 生成默认账户名
        $defaultAccount = 'wx' . random_int(1, 9999) . $currentTime;

        // 生成默认密码哈希值
        $defaultPasswordHash = md5('123456');

        // 构建用户数据
        $data = [
            'account' => $user['account'] ?? $defaultAccount,
            'pwd' => $user['pwd'] ?? $defaultPasswordHash,
            'nickname' => $user['nickname'] ?? '',
            'avatar' => !empty($user['headimgurl']) ? $user['headimgurl'] : sys_config('h5_avatar'),
            'phone' => $user['phone'] ?? '',
            'birthday' => $user['birthday'] ?? '',
            'add_time' => $currentTime,
            'add_ip' => $ip,
            'last_time' => $currentTime,
            'last_ip' => $ip,
            'user_type' => $userType
        ];

        // 处理推广用户 ID
        if ($spreadUid && $spreadInfo = $this->dao->get((int)$spreadUid)) {
            //123
            $data['spread_uid'] = $spreadUid;
            $data['spread_time'] = $currentTime;
            $data['division_id'] = $spreadInfo['division_id'];
            $data['agent_id'] = $spreadInfo['agent_id'];
            $data['staff_id'] = $spreadInfo['staff_id'];
            switch ($spreadInfo['division_type']) {
                case 1:
                    $data['division_id'] = $spreadInfo['uid'];
                    $data['agent_id'] = 0;
                    $data['staff_id'] = 0;
                    break;
                case 2:
                    $data['division_id'] = $spreadInfo['division_id'];
                    $data['agent_id'] = $spreadInfo['uid'];
                    $data['staff_id'] = 0;
                    break;
                case 3:
                    $data['division_id'] = $spreadInfo['division_id'];
                    $data['agent_id'] = $spreadInfo['agent_id'];
                    $data['staff_id'] = $spreadInfo['uid'];
                    break;
            }
        }

        // 保存用户数据
        $res = $this->dao->save($data);
        if (!$res) {
            throw new AdminException('保存用户信息失败: 数据库操作异常');
        }

        // 用户注册成功事件
        $userInfo = array_merge($res->toArray(), [
            'unionid' => $user['unionid'] ?? ''
        ]);
        event('user.register', [$userInfo, true, $spreadUid]);

        return $res;
    }


    /**
     * 某些条件用户佣金总和
     * @param array $where
     * @return mixed
     */
    public function getSumBrokerage(array $where)
    {
        return $this->dao->getWhereSumField($where, 'brokerage_price');
    }

    /**
     * 根据条件获取用户指定字段列表
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getColumn(array $where, string $field = '*', string $key = '')
    {
        return $this->dao->getColumn($where, $field, $key);
    }

    /**
     * 获取分销用户
     *
     * 该函数用于根据给定的条件获取分销用户的信息它可以被用来检索特定的用户数据，
     * 比如用户名、分销等级等通过传入不同的参数，可以控制返回的用户信息字段以及是否需要分页
     *
     * @param array $where 查询条件数组，用于指定检索用户的具体条件
     * @param string $field 需要检索的字段列表，用逗号分隔默认为全部字段
     * @param bool $is_page 是否需要分页，通常是一个布尔值，但具体类型未指定
     *
     * @return array 返回一个包含分销用户信息的数组如果未找到匹配的用户，将返回一个空数组
     *
     * @throws DataNotFoundException 当查询条件不符合任何数据时抛出此异常
     * @throws DbException 当数据库操作失败时抛出此异常
     * @throws ModelNotFoundException 当模型（用户）未找到时抛出此异常
     *
     * User: liusl
     * DateTime: 2025/4/3 下午2:42
     */

    public function getAgentUserList(array $where = [], string $field = '*', bool $is_page = true)
    {
        // 初始化查询条件
        $where_data = [];
        $where_data['status'] = 1;

        // 根据系统配置动态设置 is_promoter 字段
        $brokerageStatus = sys_config('store_brokerage_statu');
        if (!is_numeric($brokerageStatus) || $brokerageStatus != 2) {
            $where_data['is_promoter'] = 1;
        }

        // 设置 spread_open 字段
        $where_data['spread_open'] = 1;

        // 处理 nickname 字段
        if (isset($where['nickname']) && $where['nickname'] !== '') {
            $where_data['like'] = trim($where['nickname']);
        }

        // 处理 data 字段
        if (isset($where['data']) && $where['data']) {
            $where_data['time'] = trim($where['data']); // 去除多余空格
        }

        // 获取分页参数
        [$page, $limit] = $this->getPageValue($is_page);

        // 查询数据列表和总记录数
        $list = $this->dao->getAgentUserList($where_data, $field, $page, $limit);
        $count = $this->dao->count($where_data);

        // 返回结果
        return compact('count', 'list');
    }

    /**
     * 获取分销员ids
     * @param array $where
     * @return array
     * User: liusl
     * DateTime: 2025/4/3 下午2:49
     */
    public function getAgentUserIds(array $where)
    {
        // 初始化默认条件
        $where['status'] = 1;
        $where['spread_open'] = 1;

        // 校验并设置 is_promoter 条件
        if (sys_config('store_brokerage_statu') != 2) {
            $where['is_promoter'] = 1;
        }

        // 处理 nickname 条件
        if (isset($where['nickname']) && !empty(trim($where['nickname']))) {
            $where['like'] = trim($where['nickname']);
            unset($where['nickname']); // 清除原始键，避免冗余
        }

        // 处理 data 条件
        if (isset($where['data']) && !empty($where['data'])) {
            $where['time'] = $where['data'];
            unset($where['data']); // 清除原始键，避免冗余
        }

        return $this->dao->getAgentUserIds($where);
    }


    /**
     * 获取推广人列表
     * @param array $where
     * @return array
     * User: liusl
     * DateTime: 2025/4/3 下午2:53
     */
    public function getSairList(array $where)
    {
        // 初始化查询条件
        $where_data = [];

        // 验证和处理输入参数
        if (isset($where['uid']) && is_numeric($where['uid'])) {
            $uid = (int)$where['uid'];
//            if (isset($where['type']) && in_array((int)$where['type'], [1, 2])) {
            $type = (int)$where['type'];
            $uids = $this->getUserSpredadUids($uid, $type);
            if (!empty($uids)) {
                $where_data['uid'] = $uids;
            } else {
                // 如果没有匹配的 uid，直接返回空结果
                return ['list' => [], 'count' => 0];
            }
//            }

            if (isset($where['data']) && !empty(trim($where['data']))) {
                $where_data['time'] = trim($where['data']);
            }

            if (isset($where['nickname']) && !empty(trim($where['nickname']))) {
                $where_data['like'] = trim($where['nickname']);
            }

            $where_data['status'] = 1;
        }

        // 获取分页参数
        [$page, $limit] = $this->getPageValue();

        // 查询数据
        $list = $this->dao->getSairList($where_data, '*', $page, $limit);

        // 查询总数
        $count = $this->dao->count($where_data);

        return compact('list', 'count');

    }


    /**
     * 获取推广人统计
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSairCount(array $where)
    {
        // 输入参数校验
        if (empty($where) || !isset($where['uid'])) {
            return 0; // 如果缺少必要参数，直接返回0
        }

        $where_data = [];
        $uids = [];

        // 获取初始UID列表
        if (isset($where['uid'])) {
            $uids = $this->getColumn(['spread_uid' => $where['uid']], 'uid');
            if (!is_array($uids)) {
                $uids = []; // 确保 $uids 是数组
            }
        }

        // 根据 type 处理 UID 列表
        if (isset($where['type'])) {
            switch ((int)$where['type']) {
                case 1:
                    $where_data['uid'] = $uids ?: []; // 如果 $uids 为空，返回空数组
                    break;

                case 2:
                    if ($uids) {
                        $spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid');
                        if (is_array($spread_uid_two)) {
                            $where_data['uid'] = $spread_uid_two ?: []; // 如果 $spread_uid_two 为空，返回空数组
                        } else {
                            $where_data['uid'] = []; // 确保返回值为数组
                        }
                    } else {
                        $where_data['uid'] = []; // 如果 $uids 为空，直接返回空数组
                    }
                    break;

                default:
                    if ($uids) {
                        $spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid');
                        if (is_array($spread_uid_two)) {
                            $uids = array_unique(array_merge($uids, $spread_uid_two)); // 合并并去重
                        } else {
                            $uids = []; // 确保返回值为数组
                        }
                    }
                    $where_data['uid'] = $uids ?: []; // 如果 $uids 为空，返回空数组
                    break;
            }
        }

        // 处理其他条件
        if (isset($where['data']) && is_string($where['data']) && trim($where['data']) !== '') {
            $where_data['time'] = $where['data'];
        }

        if (isset($where['nickname']) && is_string($where['nickname']) && trim($where['nickname']) !== '') {
            $where_data['like'] = $where['nickname'];
        }

        $where_data['status'] = 1;

        // 返回最终计数
        return $this->dao->count($where_data);
    }


    /**
     * 写入用户信息
     * @param array $data
     * @return bool
     */
    public function create(array $data)
    {
        if (!$this->dao->save($data))
            throw new AdminException('写入失败');
        return true;
    }

    /**
     * 设置用户登录类型
     * @param int $uid
     * @param string $type
     * @return bool
     * @throws Exception
     */
    public function setLoginType(int $uid, string $type = 'h5')
    {
        if (!$this->dao->update($uid, ['login_type' => $type]))
            throw new Exception('设置登录类型失败');
        return true;
    }

    /**
     * 设置用户分组
     * @param $uids
     * @param int $group_id
     */
    public function setUserGroup($uids, int $group_id)
    {
        return $this->dao->batchUpdate($uids, ['group_id' => $group_id], 'uid');
    }

    /**
     * 获取用户标签
     * @param array $uids
     * @param int $store_id
     * @return array|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserLablel(array $uids, int $store_id = 0)
    {
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);

        // 获取用户标签列表
        $userlabels = $services->getUserLabelList($uids, $store_id);

        // 预处理：按 uid 对 userlabels 进行分组
        $groupedLabels = [];
        foreach ($userlabels as $item) {
            if (!empty($item['uid']) && !empty($item['label_name'])) {
                $groupedLabels[$item['uid']][] = $item['label_name'];
            }
        }

        // 构建结果数据
        $data = [];
        foreach ($uids as $uid) {
            if (isset($groupedLabels[$uid])) {
                $data[$uid] = implode(',', $groupedLabels[$uid]);
            } else {
                $data[$uid] = ''; // 如果没有匹配的标签，返回空字符串
            }
        }

        return $data;
    }

    /**
     * 显示资源列表头部
     * @return array[]
     */
    public function typeHead()
    {
        // 定义用户类型及其名称的映射关系
        $userTypes = [
            '' => '全部会员',
            'routine' => '小程序会员',
            'wechat' => '公众号会员',
            'h5' => 'H5会员',
            'pc' => 'PC会员',
            'app' => 'APP会员',
        ];

        // 初始化结果数组
        $result = [];

        // 获取全部会员数量
        $allCount = $this->getCount([]);

        // 将全部会员信息加入结果
        $result[] = ['user_type' => '', 'name' => $userTypes[''], 'count' => $allCount];

        /** @var UserWechatuserServices $userWechatUser */
        $userWechatUser = app()->make(UserWechatuserServices::class);

        foreach (array_keys($userTypes) as $type) {
            if ($type === '') {
                continue; // 跳过 "全部会员" 类型
            }

            // 根据用户类型构造查询条件
            $conditions = [];
            if ($type === 'h5' || $type === 'pc') {
                $conditions = ['w.openid' => '', "u.user_type" => $type];
            } else {
                $conditions = ["w.user_type" => $type];
            }

            try {
                // 获取该类型会员的数量
                $count = $userWechatUser->getCount($conditions);
                $result[] = ['user_type' => $type, 'name' => $userTypes[$type], 'count' => $count];
            } catch (\Exception $e) {
                // 如果获取数量失败，记录错误日志并跳过该类型
                Log::error("Failed to get count for user type {$type}: " . $e->getMessage());
            }
        }
        return $result;
    }

    /**
     * 会员列表
     * @param array $where
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(array $where)
    {
        try {
            // 添加过滤条件
            $where['is_filter_del'] = 1;

            /** @var UserWechatuserServices $userWechatUser */
            $userWechatUser = app()->make(UserWechatuserServices::class);
            $fields = 'u.*,w.country,w.province,w.city,w.sex,w.unionid,w.openid,w.user_type as w_user_type,w.groupid,w.tagid_list,w.subscribe,w.subscribe_time';

            // 获取用户列表
            [$list, $count] = $userWechatUser->getWhereUserList($where, $fields);

            if ($list) {
                // 提取唯一 UID 列表
                $uids = array_unique(array_column($list, 'uid'));

                // 获取相关服务数据
                $userlabel = $this->getUserLablel($uids);
                $groupIds = array_unique(array_column($list, 'group_id'));
                $userGroupService = app()->make(UserGroupServices::class);
                $userGroup = $userGroupService->getUsersGroupName($groupIds);
                $userExtractService = app()->make(UserExtractServices::class);
                $userExtract = $userExtractService->getUsersSumList($uids);
                $levels = array_unique(array_column($list, 'level'));
                $systemUserLevelService = app()->make(SystemUserLevelServices::class);
                $levelName = $systemUserLevelService->getUsersLevel($levels);
                $userLevelService = app()->make(UserLevelServices::class);
                $userLevel = $userLevelService->getUsersLevelInfo($uids);
                $spreadUids = array_unique(array_column($list, 'spread_uid'));
                $spread_names = $this->dao->getColumn([['uid', 'in', $spreadUids]], 'nickname', 'uid');
                $workClientService = app()->make(WorkClientServices::class);
                $clientData = $workClientService->getList(['uid' => $uids], ['id', 'uid', 'name', 'external_userid', 'corp_id', 'unionid'], false);
                $clientlist = $clientData['list'] ?? [];

                // 补充信息
                $extendInfo = SystemConfigService::get('user_extend_info', []);
                $is_extend_info = false;
                if ($extendInfo) {
                    foreach ($extendInfo as $item) {
                        if (isset($item['use']) && $item['use']) {
                            $is_extend_info = true;
                            break;
                        }
                    }
                }

                // 用户类型映射
                $userTypeMap = [
                    'routine' => '小程序',
                    'wechat' => '公众号',
                    'h5' => 'H5',
                    'pc' => 'PC',
                    'app' => 'APP',
                    'import' => '外部导入',
                ];

                // 性别映射
                $genderMap = [1 => '男', 2 => '女'];

                // 补充每个用户的详细信息
                foreach ($list as &$item) {
                    // 地址补充
                    if (empty($item['addres'])) {
                        if (!empty($item['country']) || !empty($item['province']) || !empty($item['city'])) {
                            $item['addres'] = implode('', [$item['country'], $item['province'], $item['city']]);
                        }
                    }

                    // 状态映射
                    $item['status'] = ($item['status'] == 1) ? '正常' : '禁止';

                    // 生日格式化
                    $item['birthday'] = $item['birthday'] ? date('Y-m-d', (int)$item['birthday']) : '';

                    // 累计提现金额
                    $item['extract_count_price'] = $userExtract[$item['uid']] ?? 0;

                    // 推广人昵称
                    $item['spread_uid_nickname'] = $item['spread_uid'] ? (($spread_names[$item['spread_uid']] ?? '') . '/' . $item['spread_uid']) : '无';

                    // 用户类型映射
                    $item['user_type'] = $userTypeMap[$item['user_type']] ?? '其他';

                    // 性别映射
                    $item['sex'] = $genderMap[$item['sex']] ?? '保密';

                    // 等级名称
                    $item['level'] = $levelName[$item['level']] ?? '无';

                    // 分组名称
                    $item['group_id'] = $userGroup[$item['group_id']] ?? '无';

                    // 用户等级信息
                    $levelinfo = $userLevel[$item['uid']] ?? null;
                    if ($levelinfo && ($levelinfo['is_forever'] || time() < $levelinfo['valid_time'])) {
                        $item['vip_name'] = $item['level'] != '无' ? $item['level'] : false;
                        $item['level_grade'] = $item['level'] != '无' ? $levelinfo['grade'] : false;
                    } else {
                        $item['vip_name'] = false;
                        $item['level_grade'] = '';
                    }

                    // SVIP 剩余天数
                    if ($item['is_ever_level'] == 1) {
                        $item['svip_overdue_time'] = $item['svip_over_day'] = '永久';
                    } elseif ($item['is_money_level'] > 0 && $item['overdue_time'] > 0) {
                        $item['svip_over_day'] = ceil(($item['overdue_time'] - time()) / 86400);
                        $item['svip_overdue_time'] = date('Y-m-d', $item['overdue_time']);
                    } else {
                        $item['svip_overdue_time'] = '';
                        $item['svip_over_day'] = 0;
                    }

                    // 标签
                    $item['labels'] = $userlabel[$item['uid']] ?? '';

                    // 是否会员
                    $item['isMember'] = $item['is_money_level'] > 0 ? 1 : 0;

                    // 关注列表
                    $item['follow_list'] = [];
                    foreach ($clientlist as $value) {
                        if (!empty($value['followOne']) && $value['uid'] == $item['uid']) {
                            $item['follow_list'][] = $value['followOne'];
                        }
                    }

                    // 扩展信息标志
                    $item['is_extend_info'] = $is_extend_info;
                }
            }

            return compact('count', 'list');
        } catch (\Exception $e) {
            // 异常处理
            Log::error('Error in user index: ' . $e->getMessage());
            return ['count' => 0, 'list' => []];
        }
    }


    /**
     * 获取修改页面数据
     * @param int $id
     * @return array
     */
    public function edit(int $id)
    {
        $user = $this->getUserInfo($id);
        if (!$user)
            throw new AdminException('数据不存在');
        $f = array();
        $f[] = Form::input('uid', '用户编号', $user['uid'])->disabled(true);
        $f[] = Form::input('real_name', '真实姓名', $user['real_name'])->col(12);
        $f[] = Form::input('phone', '手机号码', $user['phone'])->col(12);
        $f[] = Form::date('birthday', '生日', $user['birthday'] ? date('Y-m-d', $user['birthday']) : '')->col(12);
        $f[] = Form::input('card_id', '身份证号', $user['card_id'])->col(12);
        $f[] = Form::input('addres', '用户地址', $user['addres']);
        $f[] = Form::textarea('mark', '用户备注', $user['mark']);
        $f[] = Form::input('pwd', '登录密码')->type('password')->col(12)->placeholder('不改密码请留空');
        $f[] = Form::input('true_pwd', '确认密码')->type('password')->col(12)->placeholder('不改密码请留空');
        //查询高于当前会员的所有会员等级
//        $grade = app()->make(UserLevelServices::class)->getUerLevelInfoByUid($id, 'grade');
        $systemLevelList = app()->make(SystemUserLevelServices::class)->getWhereLevelList([], 'id,name');
        $setOptionLevel = function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        };
        $f[] = Form::select('level', '用户等级', (int)$user->getData('level'))->col(12)->setOptions(FormBuilder::setOptions($setOptionLevel))->filterable(true);
        $systemGroupList = app()->make(UserGroupServices::class)->getGroupList();
        $setOptionGroup = function () use ($systemGroupList) {
            $menus = [];
            foreach ($systemGroupList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
            }
            return $menus;
        };
        $f[] = Form::select('group_id', '用户分组', $user->getData('group_id'))->col(12)->setOptions(FormBuilder::setOptions($setOptionGroup))->filterable(true);
        /** @var UserLabelServices $userlabelServices */
        $userlabelServices = app()->make(UserLabelServices::class);
        $systemLabelList = $userlabelServices->getLabelList(['type' => 0]);
        $labels = app()->make(UserLabelRelationServices::class)->getUserLabels($user['uid']);
        $setOptionLabel = function () use ($systemLabelList) {
            $menus = [];
            foreach ($systemLabelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
            }
            return $menus;
        };
        $f[] = Form::select('label_id', '用户标签', $labels)->setOptions(FormBuilder::setOptions($setOptionLabel))->filterable(true)->multiple(true);
        $f[] = Form::radio('spread_open', '推广资格', $user->getData('spread_open'))->info('禁用用户的推广资格后，在任何分销模式下该用户都无分销权限')->options([['value' => 1, 'label' => '启用'], ['value' => 0, 'label' => '禁用']]);
        //分销模式  人人分销
        $storeBrokerageStatus = sys_config('store_brokerage_statu', 1);
        if (in_array($storeBrokerageStatus, [1, 3])) {
            $f[] = Form::radio('is_promoter', '推广员权限', $user->getData('is_promoter'))->info('指定分销模式下，开启或关闭用户的推广权限')->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        }
        $f[] = Form::radio('status', '用户状态', $user->getData('status'))->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '锁定']]);
        return create_form('编辑', $f, Url::buildUrl('/user/user/' . $id), 'PUT');
    }

    /**
     * 添加用户表单
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function saveForm()
    {
        $f = array();
        $f[] = Form::input('real_name', '真实姓名', '')->col(12)->placeholder('请输入真实姓名');
        $f[] = Form::input('phone', '手机号码', '')->col(12)->placeholder('请输入手机号码')->required();
        $f[] = Form::date('birthday', '生日', '')->col(12)->placeholder('请选择生日');
        $f[] = Form::input('card_id', '身份证号', '')->col(12)->placeholder('请输入身份证号');
        $f[] = Form::input('addres', '用户地址', '')->placeholder('请输入用户地址');
        $f[] = Form::textarea('mark', '用户备注', '')->placeholder('请输入用户备注');
        $f[] = Form::input('pwd', '登录密码')->col(12)->placeholder('请输入登录密码');
        $f[] = Form::input('true_pwd', '确认密码')->col(12)->placeholder('请再次确认密码');
        /** @var SystemUserLevelServices $systemUserLevelServices */
        $systemUserLevelServices = app()->make(SystemUserLevelServices::class);
        $systemLevelList = $systemUserLevelServices->getWhereLevelList([], 'id,name');
        $setOptionLevel = function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        };
        $f[] = Form::select('level', '用户等级', '')->col(12)->setOptions(FormBuilder::setOptions($setOptionLevel))->filterable(true);
        $systemGroupList = app()->make(UserGroupServices::class)->getGroupList();
        $setOptionGroup = function () use ($systemGroupList) {
            $menus = [];
            foreach ($systemGroupList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
            }
            return $menus;
        };
        $f[] = Form::select('group_id', '用户分组', '')->col(12)->setOptions(FormBuilder::setOptions($setOptionGroup))->filterable(true);
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $systemLabelList = $userLabelServices->getLabelList(['type' => 0, 'label_type' => 1]);
        $setOptionLabel = function () use ($systemLabelList) {
            $menus = [];
            foreach ($systemLabelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
            }
            return $menus;
        };
        $f[] = Form::select('label_id', '用户标签', '')->setOptions(FormBuilder::setOptions($setOptionLabel))->filterable(true)->multiple(true);
        $f[] = Form::radio('spread_open', '推广资格', 1)->info('禁用用户的推广资格后，在任何分销模式下该用户都无分销权限')->options([['value' => 1, 'label' => '启用'], ['value' => 0, 'label' => '禁用']]);
        //分销模式  人人分销
        $storeBrokerageStatus = sys_config('store_brokerage_statu', 1);
        if (in_array($storeBrokerageStatus, [1, 3])) {
            $f[] = Form::radio('is_promoter', '推广员权限', 0)->info('指定分销模式下，开启或关闭用户的推广权限')->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        }
        $f[] = Form::radio('status', '用户状态', 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '锁定']]);
        return create_form('添加用户', $f, $this->url('/user/user'), 'POST');
    }

    /**
     * 获取追加信息
     * @param int $uid
     * @return Group
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function extendInfoForm(int $uid = 0)
    {
        $f = [];
        if ($uid) {
            $userInfo = $this->getUserInfo($uid);
        }
        $extendInfo = SystemConfigService::get('user_extend_info', []);
        $f = [];
        if ($extendInfo) {
            $userExtendInfo = $userInfo['extend_info'] ?? [];
            if ($userExtendInfo) $userExtendInfo = array_combine(array_column($userExtendInfo, 'info'), $userExtendInfo);
            foreach ($extendInfo as $item) {
                //没选择使用 跳过
                if (!isset($item['use']) || !$item['use']) continue;
                $format = $item['format'] ?? '';
                $field = $item['info'];
                switch ($format) {
                    case 'num'://'数字'
                        $form = Form::number($field, $item['info'], $userExtendInfo[$item['info']]['value'] ?? 0);
                        break;
                    case 'date'://'日期'
                        $form = Form::date($field, $item['info'], $userExtendInfo[$item['info']]['value'] ?? '');
                        break;
                    case 'radio'://'单选项'
                        $options = [];
                        if (isset($item['singlearr']) && $item['singlearr']) {
                            foreach ($item['singlearr'] as $key => $value) {
                                $options[] = ['value' => $value, 'label' => $value];
                            }
                        }
                        $form = Form::radio($field, $item['info'], (int)($userExtendInfo[$item['info']]['value'] ?? 0))->options($options);
                        break;
                    case 'text'://'文本'
                    case 'id'://'身份证'
                    case 'mail'://'邮件'
                    case 'phone'://'手机号'
                    case 'address'://'地址'
                        $form = Form::input($field, $item['info'], $userExtendInfo[$item['info']]['value'] ?? '')->placeholder($item['tip'] ?? $item['info']);
                        break;
                }
                if ($item['required']) {
                    $f[] = $form->required($item['tip'] ?? '');
                } else {
                    $f[] = $form;
                }

            }
        }
        if (!$f) {
            throw new ValidateException('请先去用户设置：设置用户信息');
        }
        return create_form('用户补充信息', $f, $this->url('/user/user/extend_info/' . $uid), 'POST');
    }

    /**
     * 处理用户补充信息
     * @param array $inputExtendInfo
     * @param bool $is_all
     * @return array
     */
    public function handelExtendInfo(array $inputExtendInfo, bool $is_all = false)
    {
        $extendInfo = SystemConfigService::get('user_extend_info', []);
        if ($inputExtendInfo && $extendInfo) {
            if ($is_all) {//移动端全数据 处理
                $inputExtendInfo = array_combine(array_column($inputExtendInfo, 'info'), $inputExtendInfo);
            } else {//后台key=>value类型数据
                $inputExtendInfo = $inputExtendInfo[0] ?? $inputExtendInfo;
            }
            foreach ($extendInfo as &$item) {
                $value = $is_all ? ($inputExtendInfo[$item['info'] ?? '']['value'] ?? '') : ($inputExtendInfo[$item['info'] ?? ''] ?? '');
                if ($value) {
                    //验证手机号
                    if ($item['format'] == 'phone') {
                        if (!check_phone($value)) throw new AdminException('请填写正确的手机号');
                    }
                    //验证邮箱
                    if ($item['format'] == 'mail') {
                        if (!check_mail($value)) throw new AdminException('请填写正确的邮箱');
                    }
                    //验证身份证号
                    if ($item['format'] == 'id') {
                        try {
                            if (!check_card($value)) throw new AdminException('请填写正确的身份证号码');
                        } catch (\Throwable $e) {
//							throw new AdminException( '请填写正确的身份证号码');
                        }
                    }
                }
                $item['value'] = $value;
            }
        }
        return $extendInfo;
    }

    /**
     * 保存用户补充信息
     * @param int $uid
     * @param array $extend_info 补充信息
     * @param array $update 原本需要修改字段
     * @param bool $is_all
     * @return bool
     */
    public function saveExtendForm(int $uid, array $extend_info, array $update = [], bool $is_all = false)
    {
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('用户不存在');
        }
        $extend_info = $this->handelExtendInfo($extend_info, $is_all) ?: [];
        $userExtendServices = app()->make(UserExtendServices::class);
        $extendData = [];
        if ($extend_info) {
            $default = $this->defaultExtendInfo;
            $params = array_column($default, 'param');
            $sex = $this->sex;
            $update['extend_info'] = $extend_info;
            foreach ($extend_info as $info) {
                if (isset($info['is_only']) && $info['is_only'] == 1) {
                    $field_name = $info['param'] ?? $info['info'];
                    if ($userExtendServices->isExist($field_name, $info['value'], $uid)) {
                        throw new ValidateException($info['info'] . '该字段已存在');
                        continue;
                    }
                    //验证唯一性
                }

                $extendData[] = [
                    'field_name' => $info['param'] ?? $info['info'],
                    'field_value' => $info['value'] ?? '',
                ];
                if (isset($info['param']) && in_array($info['param'], $params) && isset($info['value'])) {
                    if ($info['param'] == 'sex') {
                        $update['sex'] = $sex[$info['value']] ?? 0;
                    } elseif ($info['param'] == 'birthday') {
                        $update['birthday'] = strtotime($info['value']);
                    } elseif ($info['param'] == 'address') {
                        $update['addres'] = $info['value'];
                    } else {
                        $update[$info['param']] = $info['value'];
                    }
                }
            }
        }
        $userExtendServices->deleteUserExtendData($uid);
        if (count($extendData) > 0) {
            $userExtendServices->setBatchUserExtendData($uid, $extendData);
        }
        if ($update) {
            $this->dao->update($uid, $update);
            $this->dao->cacheTag()->clear();
        }
        return true;
    }

    /**
     * 修改提交处理
     * @param $id
     * @return mixed
     */
    public function updateInfo(int $id, array $data)
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new AdminException('数据不存在!');
        }
        $res1 = false;
        $res2 = false;
        $edit = [];
        $outPush = [];
        if ($data['money_status'] && $data['money']) {//余额增加或者减少
            $data['money'] = sprintf("%.2f", $data['money']);
            /** @var UserMoneyServices $userMoneyServices */
            $userMoneyServices = app()->make(UserMoneyServices::class);
            if ($data['money_status'] == 1) {//增加
                $edit['now_money'] = bcadd($user['now_money'], $data['money'], 2);
                $res1 = $userMoneyServices->income('system_add', $user['uid'], $data['money'], $edit['now_money'], $data['adminId'] ?? 0, '', $data['money_mark'] ?? '');
            } else if ($data['money_status'] == 2) {//减少
                if ($user['now_money'] > $data['money']) {
                    $edit['now_money'] = bcsub($user['now_money'], $data['money'], 2);
                } else {
                    $edit['now_money'] = 0;
                    $data['money'] = $user['now_money'];
                }
                $res1 = $userMoneyServices->income('system_sub', $user['uid'], $data['money'], $edit['now_money'], $data['adminId'] ?? 0, '', $data['money_mark'] ?? '');
            }
            $outPush = ['uid' => $id, 'type' => 'money', 'value' => $data['money_status'] == 2 ? -floatval($data['money']) : $data['money']];
        } else {
            $res1 = true;
        }
        if ($data['integration_status'] && $data['integration']) {//积分增加或者减少
            /** @var UserBillServices $userBill */
            $userBill = app()->make(UserBillServices::class);
            $balance = $user['integral'];
            if ($data['integration_status'] == 1) {//增加
                $balance = bcadd((string)$user['integral'], (string)$data['integration'], 0);
                $res2 = $userBill->income('system_add_integral', $id, (int)$data['integration'], (int)$balance, $data['adminId'] ?? 0, 0, $data['integral_mark'] ?? '');
            } else if ($data['integration_status'] == 2) {//减少
                $balance = max(bcsub((string)$user['integral'], (string)$data['integration'], 0), 0);
                $res2 = $userBill->income('system_sub_integral', $id, (int)$data['integration'], (int)$balance, $data['adminId'] ?? 0, 0, $data['integral_mark'] ?? '');
            }
            $edit['integral'] = $balance;
            $outPush = ['uid' => $id, 'type' => 'point', 'value' => $data['integration_status'] == 2 ? -intval($data['integration']) : $data['integration']];
        } else {
            $res2 = true;
        }
        //修改基本信息
        if (!isset($data['is_other']) || !$data['is_other']) {
            if ($data['phone']) {
                $otherUser = $this->getOne(['phone' => $data['phone']], 'uid,phone');
                if ($otherUser && $otherUser['uid'] != $id) {
                    throw new AdminException('该手机号码已被注册');
                }
            }
            /** @var UserLabelRelationServices $userLabel */
            $userLabel = app()->make(UserLabelRelationServices::class);
            if (is_string($data['label_id'])) {
                $data['label_id'] = [$data['label_id']];
            }
            $userLabel->setUserLable([$id], $data['label_id'], 0, 0, true);
            if (isset($data['pwd']) && $data['pwd'] && $data['pwd'] != $user['pwd']) {
                $edit['pwd'] = $data['pwd'];
            }
            if (isset($data['spread_open'])) {
                $edit['spread_open'] = $data['spread_open'];
            }
            $edit['status'] = $data['status'];
            $edit['real_name'] = $data['real_name'];
            $edit['card_id'] = $data['card_id'];
            $edit['birthday'] = $data['birthday'] ?? '';
            $edit['mark'] = $data['mark'];
            $edit['is_promoter'] = $data['is_promoter'];
            $edit['level'] = $data['level'];
            $edit['phone'] = $data['phone'];
            $edit['addres'] = $data['addres'];
            $edit['group_id'] = $data['group_id'];
            if ($data['spread_uid'] != -1) {
                $edit['spread_uid'] = $data['spread_uid'];
                $edit['spread_time'] = $data['spread_uid'] ? time() : 0;
                if ($data['spread_uid'] > 0) {
                    $spreadInfo = $this->getUserInfo($edit['spread_uid']);
                    $edit['division_id'] = $spreadInfo['division_id'];
                    $edit['agent_id'] = $spreadInfo['agent_id'];
                    $edit['staff_id'] = $spreadInfo['staff_id'];
                    switch ($spreadInfo['division_type']) {
                        case 1:
                            $edit['division_id'] = $spreadInfo['uid'];
                            $edit['agent_id'] = 0;
                            $edit['staff_id'] = 0;
                            break;
                        case 2:
                            $edit['division_id'] = $spreadInfo['division_id'];
                            $edit['agent_id'] = $spreadInfo['uid'];
                            $edit['staff_id'] = 0;
                            break;
                        case 3:
                            $edit['division_id'] = $spreadInfo['division_id'];
                            $edit['agent_id'] = $spreadInfo['agent_id'];
                            $edit['staff_id'] = $spreadInfo['uid'];
                            break;
                    }

                    if ($data['spread_uid'] != $user['spread_uid']) {
                        //记录推广绑定关系
                        UserSpreadJob::dispatch([$id, (int)$data['spread_uid'], 0, (int)$data['adminId']]);
                        //记录好友关系
                        UserFriendsJob::dispatch([$id, (int)$data['spread_uid']]);
                    }
                } else {
                    if ($user['division_status'] != 1) {
                        $edit['division_id'] = 0;
                    }
                    $edit['distinct_id'] = 0;
                    $edit['agent_id'] = 0;
                    $edit['staff_id'] = 0;
                }
            }
            $edit['sex'] = $data['sex'];
            $edit['provincials'] = $data['provincials'];
            $edit['province'] = $data['province'];
            $edit['city'] = $data['city'];
            $edit['area'] = $data['area'];
            $edit['street'] = $data['street'];
            if (isset($data['extend_info']) && $data['extend_info']) $edit['extend_info'] = $data['extend_info'];
            if ($user['level'] != $data['level']) {
                /** @var UserLevelServices $userLevelService */
                $userLevelService = app()->make(UserLevelServices::class);
                $userLevelService->setUserLevel((int)$user['uid'], (int)$data['level']);
            }
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            $wechatUser->update(['uid' => $id], ['sex' => $data['sex']]);
        }
        if ($outPush) event('out.outPush', ['user_update_push', $outPush]);
        if ($edit) $res3 = $this->dao->update($id, $edit);
        else $res3 = true;
        if ($res1 && $res2 && $res3) {
            $this->dao->cacheTag()->clear();
            return true;
        } else throw new AdminException('修改失败');
    }

    /**
     * 编辑其他
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function editOther($id, $type = 0)
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new AdminException('数据不存在!');
        }
        $f = array();
        if ($type == 1) {
            $f[] = Form::input('now_money', '当前余额', (string)$user['now_money'])->disabled(true)->style(['width' => '120px']);;
            $f[] = Form::radio('money_status', '修改余额', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
            $f[] = Form::input('money', '余额', 0)->style(['width' => '100px']);
            $f[] = Form::textarea('money_mark', '备注')->rows(5);
            $title = '修改余额';
        } elseif ($type == 2) {
            $f[] = Form::input('integral', '当前积分', (string)$user['integral'])->disabled(true)->style(['width' => '120px']);;
            $f[] = Form::radio('integration_status', '修改积分', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
            $f[] = Form::number('integration', '积分', 0)->max(999999)->min(0)->precision(0)->style(['width' => '100px']);
            $f[] = Form::textarea('integral_mark', '备注')->rows(5);
            $title = '修改积分';
        } else {
            $f[] = Form::input('now_money', '当前余额', (string)$user['now_money'])->disabled(true)->style(['width' => '120px']);;
            $f[] = Form::radio('money_status', '修改余额', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
            $f[] = Form::input('money', '余额', 0)->style(['width' => '100px']);
            $f[] = Form::textarea('money_mark', '备注')->rows(5);
            $f[] = Form::input('integral', '当前积分', (string)$user['integral'])->disabled(true)->style(['width' => '120px']);;
            $f[] = Form::radio('integration_status', '修改积分', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
            $f[] = Form::number('integration', '积分', 0)->max(999999)->min(0)->precision(0)->style(['width' => '100px']);
            $f[] = Form::textarea('integral_mark', '备注')->rows(5);
            $title = '修改积分余额';
        }
        return create_form($title, $f, Url::buildUrl('/user/update_other/' . $id), 'PUT');
    }

    /**
     * 设置会员分组
     * @param $id
     * @return mixed
     */
    public function setGroup($uids, $all, $where)
    {
        $userGroup = app()->make(UserGroupServices::class)->getGroupList();
        if (count($uids) == 1) {
            $user = $this->getUserInfo($uids[0], ['group_id']);
            $setOptionUserGroup = function () use ($userGroup) {
                $menus = [];
                foreach ($userGroup as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
                }
                return $menus;
            };
            $field[] = Form::select('group_id', '用户分组', $user->getData('group_id'))->setOptions(FormBuilder::setOptions($setOptionUserGroup))->filterable(true);
        } else {
            $setOptionUserGroup = function () use ($userGroup) {
                $menus = [];
                foreach ($userGroup as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
                }
                return $menus;
            };
            $field[] = Form::select('group_id', '用户分组')->setOptions(FormBuilder::setOptions($setOptionUserGroup))->filterable(true);
        }
        $field[] = Form::hidden('uids', implode(',', $uids));
        $field[] = Form::hidden('all', $all);
        $field[] = Form::hidden('where', $where ? json_encode($where) : "");
        return create_form('设置用户分组', $field, Url::buildUrl('/user/save_set_group'), 'PUT');
    }

    /**
     * 保存会员分组
     * @param $id
     * @return mixed
     */
    public function saveSetGroup($uids, int $group_id, $redisKey, $queueId)
    {
        /** @var UserGroupServices $userGroup */
        $userGroup = app()->make(UserGroupServices::class);
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        if (!$userGroup->getGroup($group_id)) {
            throw new AdminException('该分组不存在');
        }
        if (!$this->setUserGroup($uids, $group_id)) {
            $queueService->addQueueFail($queueId['id'], $redisKey);
            throw new AdminException('设置分组失败或无改动');
        } else {
            $queueService->doSuccessSremRedis($uids, $redisKey, $queueId['type']);
        }
        return true;
    }

    /**
     * 设置用户标签
     * @param $uids
     * @param $all
     * @param $where
     * @param int $type
     * @param int $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setLabel($uids, $all, $where, int $type = 0, int $relation_id = 0)
    {
        /** @var UserLabelServices $userlabelServices */
        $userlabelServices = app()->make(UserLabelServices::class);
        $userLabel = $userlabelServices->getLabelList(['type' => $type, 'relation_id' => $relation_id]);
        if (count($uids) == 1) {
            /** @var UserLabelRelationServices $userLabeLRelation */
            $userLabeLRelation = app()->make(UserLabelRelationServices::class);
            $lids = $userLabeLRelation->getUserLabels($uids[0], $type, $relation_id);
            $setOptionUserLabel = function () use ($userLabel) {
                $menus = [];
                foreach ($userLabel as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
                }
                return $menus;
            };
            $field[] = Form::select('label_id', '用户标签', $lids)->setOptions(FormBuilder::setOptions($setOptionUserLabel))->filterable(true)->multiple(true);
        } else {
            $setOptionUserLabel = function () use ($userLabel) {
                $menus = [];
                foreach ($userLabel as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
                }
                return $menus;
            };
            $field[] = Form::select('label_id', '用户标签')->setOptions(FormBuilder::setOptions($setOptionUserLabel))->filterable(true)->multiple(true);
        }
        $field[] = Form::hidden('uids', implode(',', $uids));
        $field[] = Form::hidden('all', $all);
        $field[] = Form::hidden('where', $where ? json_encode($where) : "");
        return create_form('设置用户标签', $field, Url::buildUrl('/user/save_set_label'), 'PUT');
    }

    /**
     * 保存用户标签
     * @param $uids
     * @param $label_id
     * @return bool
     */
    public function saveSetLabel($uids, $label_id)
    {
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);
        if ($label_id) {
            /** @var UserLabelServices $userlabel */
            $userlabel = app()->make(UserLabelServices::class);
            if (count($label_id) != $userlabel->getCount([['id', 'in', $label_id]])) {
                throw new AdminException('用户标签不存在或被删除');
            }
            if (!$services->setUserLable($uids, $label_id)) {
                throw new AdminException('设置标签失败');
            }
        } else {//没传入标签 默认清空
            if (!is_array($uids)) {
                $uids = [$uids];
            }
            foreach ($uids as $uid) {
                $services->unUserLabel((int)$uid);
            }
        }

        return true;
    }

    /**
     * 批量队列设置标签
     * @param $uids
     * @param $lable_id
     * @param $redisKey
     * @param $queueId
     * @return bool
     */
    public function saveBatchSetLabel($uids, $lable_id, $redisKey, $queueId)
    {
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        foreach ($lable_id as $id) {
            if (!app()->make(UserLabelServices::class)->getLable((int)$id)) {
                throw new AdminException('用户标签不存在或被删除');
            }
        }
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);
        if (!$services->setUserLable($uids, $lable_id)) {
            $queueService->addQueueFail($queueId['id'], $redisKey);
            throw new AdminException('设置标签失败');
        } else {
            $queueService->doSuccessSremRedis($uids, $redisKey, $queueId['type']);
        }
        return true;
    }

    /**
     * 赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function giveLevel($id)
    {
        if (!$this->userExist($id)) {
            throw new AdminException('用户不存在');
        }
        //查询高于当前会员的所有会员等级
        $grade = app()->make(UserLevelServices::class)->getUerLevelInfoByUid($id, 'grade');
        $systemLevelList = app()->make(SystemUserLevelServices::class)->getWhereLevelList(['grade', '>', $grade ?? 0], 'id,name');
        $setOptionlevel = function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        };
        $field[] = Form::select('level_id', '用户等级')->setOptions(FormBuilder::setOptions($setOptionlevel))->filterable(true);
        return create_form('赠送等级', $field, Url::buildUrl('/user/save_give_level/' . $id), 'PUT');
    }

    /**
     * 执行赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function saveGiveLevel(int $id, int $level_id)
    {
        if (!$this->userExist($id)) {
            throw new AdminException('用户不存在');
        }
        /** @var SystemUserLevelServices $systemLevelServices */
        $systemLevelServices = app()->make(SystemUserLevelServices::class);
        /** @var UserLevelServices $userLevelServices */
        $userLevelServices = app()->make(UserLevelServices::class);
        //查询当前选择的会员等级
        $systemLevel = $systemLevelServices->getLevel($level_id);
        if (!$systemLevel) throw new AdminException('您选择赠送的用户等级不存在！');
        //检查是否拥有此会员等级
        $level = $userLevelServices->getWhereLevel(['uid' => $id, 'level_id' => $level_id], 'valid_time,is_forever');
        if ($level && $level['status'] == 1 && $level['is_del'] == 0) {
            throw new AdminException('此用户已有该用户等级，无法再次赠送');
        }
        //保存会员信息
        if (!$userLevelServices->setUserLevel($id, $level_id, $systemLevel)) {
            throw new AdminException('赠送失败');
        }
        return true;
    }

    /**
     * 赠送付费会员时长
     * @param int $uid
     * @return mixed
     * */
    public function giveLevelTime($id)
    {
        $userInfo = $this->getUserCacheInfo($id);
        if (!$userInfo) {
            throw new AdminException('用户不存在');
        }
        $overdue_time = '';
        if ($userInfo['is_ever_level'] == 1) {
            $overdue_time = '永久';
        } else {
            if ($userInfo['is_money_level'] > 0 && $userInfo['overdue_time'] > 0) {
                $overdue_time = date('Y-m-d H:i:s', $userInfo['overdue_time']);
            } else {
                $overdue_time = '已过期/暂未开通';
            }
        }
        $field[] = Form::input('overdue_time', '会员到期时间', $overdue_time)->disabled(true);
        $field[] = Form::radio('days_status', '修改付费会员', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
        $field[] = Form::number('days', '调整时长(天)')->min(0)->max(999999)->precision(0)->placeholder('请输入');
        return create_form('赠送付费会员时长', $field, Url::buildUrl('/user/save_give_level_time/' . $id), 'PUT');
    }

    /**
     * 执行赠送付费会员时长
     * @param int $id
     * @param int $days
     * @param int $days_status 1：增加 2：减少
     * @return bool
     * @throws \Exception
     */
    public function saveGiveLevelTime(int $id, int $days, int $days_status = 1)
    {
        $userInfo = $this->getUserInfo($id);
        if (!$userInfo) {
            throw new AdminException('用户不存在');
        }
        if ($days <= 0) throw new AdminException('赠送天数不能小于1天');
        if ($userInfo['is_ever_level'] == 1) {
            throw new AdminException('永久会员无需操作');
        }
        $update = [];
        $days_time = bcmul((string)$days, '86400');
        if ($userInfo['is_money_level'] == 0) {
            $update['is_money_level'] = 3;
            $time = time();
        } else {
            $time = $userInfo['overdue_time'];
        }
        if ($days_status == 1) {//增加
            if ($time < time()) {
                $time = (int)bcadd((string)time(), (string)$days_time);
            } else {
                $time = (int)bcadd((string)$time, (string)$days_time);
            }
        } else {//减少
            $time = (int)bcsub((string)$time, (string)$days_time);
            $time = max($time, time());
        }
        $update['overdue_time'] = $time;
        if ($time <= time()) {//已经过期
            $update['is_money_level'] = 0;
        }
        $this->dao->update($id, $update);
        $userInfo->save();
        /** @var StoreOrderCreateServices $storeOrderCreateService */
        $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
        $orderInfo = [
            'uid' => $id,
            'order_id' => $storeOrderCreateService->getNewOrderId(),
            'type' => 4,
            'member_type' => 'admin',
            'pay_type' => 'admin',
            'paid' => 1,
            'pay_time' => time(),
            'is_free' => 1,
            'overdue_time' => $time,
            'vip_day' => $days_status == 1 ? $days : bcsub('0', $days),
            'add_time' => time()
        ];
        /** @var OtherOrderServices $otherOrder */
        $otherOrder = app()->make(OtherOrderServices::class);
        $otherOrder->save($orderInfo);
        return true;
    }

    /**
     * 清除会员等级
     * @paran int $uid
     * @paran boolean
     * */
    public function cleanUpLevel($uid)
    {
        if (!$this->userExist($uid))
            throw new AdminException('用户不存在');
        /** @var UserLevelServices $services */
        $services = app()->make(UserLevelServices::class);
        return $this->transaction(function () use ($uid, $services) {
            $res = $services->delUserLevel($uid);
            $res1 = $this->dao->update($uid, ['clean_time' => time(), 'level' => 0, 'exp' => 0], 'uid');
            if (!$res && !$res1)
                throw new AdminException('清除失败');
            return true;
        });
    }

    /**
     * 获取用户详情里面的用户消费能力和用户余额积分等
     * @param $uid
     * @return array[]
     */
    public function getHeaderList(int $uid, $userInfo = [])
    {
        if (!$userInfo) {
            $userInfo = $this->getUserInfo($uid);
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $where = ['pid' => 0, 'uid' => $uid, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];
        return [
            [
                'title' => '余额',
                'value' => $userInfo['now_money'] ?? 0,
                'key' => '元',
            ],
            [
                'title' => '总计订单',
                'value' => $orderServices->count($where),
                'key' => '笔',
            ],
            [
                'title' => '总消费金额',
                'value' => $orderServices->together($where, 'pay_price'),
                'key' => '元',
            ],
            [
                'title' => '积分',
                'value' => $userInfo['integral'] ?? 0,
                'key' => '',
            ],
            [
                'title' => '用户经验',
                'value' => $userInfo['exp'] ?? 0,
                'key' => '',
            ],
            [
                'title' => '消费次数',
                'value' => $orderServices->count(['uid' => $uid, 'pid' => [0, -1], 'paid' => 1, 'is_del' => 0, 'is_system_del' => 0]),
                'key' => '',
            ],
//            [
//                'title' => '本月订单',
//                'value' => $orderServices->count($where + ['time' => 'month']),
//                'key' => '笔',
//            ],
//            [
//                'title' => '本月消费金额',
//                'value' => $orderServices->together($where + ['time' => 'month'], 'pay_price'),
//                'key' => '元',
//            ]
        ];
    }

    /**
     * 用户详情
     * @param int $uid
     * @return array
     */
    public function read(int $uid)
    {
        $userInfo = $this->dao->getUserWithTrashedInfo($uid);
        if (!$userInfo) {
            throw new AdminException('数据不存在');
        }
        $userInfo = $userInfo->toArray();
        $spread_uid_nickname = '';
        if ($userInfo['spread_uid']) {
            $spread_uid_nickname = $this->dao->value(['uid' => $userInfo['spread_uid']], 'nickname');
        }
        if ($userInfo['is_ever_level'] == 1) {
            $userInfo['overdue_time'] = '永久';
        } else {
            if ($userInfo['is_money_level'] > 0 && $userInfo['overdue_time'] > 0) {
                $userInfo['overdue_time'] = date('Y-m-d H:i:s', $userInfo['overdue_time']);
            }
        }
        $userInfo['spread_uid_nickname'] = $userInfo['spread_uid'] ? $spread_uid_nickname . '/' . $userInfo['spread_uid'] : '';
        $userInfo['_add_time'] = date('Y-m-d H:i:s', $userInfo['add_time']);
        $userInfo['_last_time'] = date('Y-m-d H:i:s', $userInfo['last_time']);
//        $userInfo['birthday'] = $userInfo['birthday'] ? date('Y-m-d', $userInfo['birthday']) : '';
        /** @var UserLabelRelationServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelRelationServices::class);
        $label_list = $userLabelServices->getUserLabelList([$uid]);
        $label_id = [];
        $userInfo['label_list'] = '';
        if ($label_list) {
            $userInfo['label_list'] = implode(',', array_column($label_list, 'label_name'));
            foreach ($label_list as $item) {
                $label_id[] = [
                    'id' => $item['label_id'],
                    'label_name' => $item['label_name'],
                    'label_type' => $item['label_type'],
                ];
            }
        }
        $userInfo['vip_name'] = '';
        if ($userInfo['level']) {
            /** @var SystemUserLevelServices $levelServices */
            $levelServices = app()->make(SystemUserLevelServices::class);
            $levelInfo = $levelServices->getOne(['id' => $userInfo['level']], 'id,name');
            $userInfo['vip_name'] = $levelInfo['name'] ?? '';
        }
        $userInfo['label_id'] = $label_id;
        $userInfo['group_name'] = '';
        if($userInfo['group_id']){
            $userInfo['group_name'] = app()->make(UserGroupServices::class)->value(['id' => $userInfo['group_id']], 'group_name');
        }
        $workClientInfo = $workMemberInfo = null;
        if ($userInfo['phone']) {
            /** @var WorkMemberServices $workMemberService */
            $workMemberService = app()->make(WorkMemberServices::class);
            $workMemberInfo = $workMemberService->get(['mobile' => $userInfo['phone']]);
            if ($workMemberInfo) {
                if (!$workMemberInfo->uid) {
                    $workMemberInfo->uid = $userInfo['uid'];
                    $workMemberInfo->save();
                }
                $workMemberInfo = $workMemberInfo->toArray();
            }
        }
        if (!$workMemberInfo) {
            /** @var WorkClientServices $workClientService */
            $workClientService = app()->make(WorkClientServices::class);
            $workClientInfo = $workClientService->get(['uid' => $userInfo['uid']]);
            if ($workClientInfo) {
                $workClientInfo = $workClientInfo->toArray();
            }
        }
        $info = [
            'uid' => $uid,
            'userinfo' => [],
            'headerList' => $this->getHeaderList($uid, $userInfo),
            'count' => [0, 0, 0],
            'ps_info' => $userInfo,
            'workClientInfo' => $workClientInfo,
            'workMemberInfo' => $workMemberInfo,
        ];
        return $info;
    }

    /**
     * 移动端管理用户详情
     * @param int $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * User: liusl
     * DateTime: 2024/1/29 11:37
     */
    public function manageRead(int $uid)
    {
        $userInfo = $this->dao->getUserWithTrashedInfo($uid);
        if (!$userInfo) {
            throw new AdminException('数据不存在');
        }
        $userInfo = $userInfo->toArray();
        $userInfo['birthday'] = $userInfo['birthday'] ? date('Y-m-d', $userInfo['birthday']) : '';
        $userInfo['_add_time'] = date('Y-m-d H:i:s', $userInfo['add_time']);

        //订单
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $userInfo['order_total_count'] = $orderServices->count(['uid' => $uid, 'pid' => [0, -1], 'paid' => 1, 'is_del' => 0, 'is_system_del' => 0]);
        $userInfo['order_total_price'] = $orderServices->together(['pid' => 0, 'uid' => $uid, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0], 'pay_price');

        //标签
        /** @var UserLabelRelationServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelRelationServices::class);
        $label_list = $userLabelServices->getUserLabelList([$uid]);
        $label_id = [];
        $userInfo['label_list'] = '';
        if ($label_list) {
            $userInfo['label_list'] = implode(',', array_column($label_list, 'label_name'));
            foreach ($label_list as $item) {
                $label_id[] = [
                    'id' => $item['label_id'],
                    'label_name' => $item['label_name']
                ];
            }
        }
        $userInfo['label_id'] = $label_id;

        //会员
        $userInfo['isMember'] = $userInfo['is_money_level'] > 0 ? 1 : 0;
        if ($userInfo['is_ever_level'] == 1) {
            $userInfo['svip_overdue_time'] = $userInfo['svip_over_day'] = '永久';
        } else {
            if ($userInfo['is_money_level'] > 0 && $userInfo['overdue_time'] > 0) {
                $userInfo['svip_over_day'] = ceil(($userInfo['overdue_time'] - time()) / 86400);
                $userInfo['svip_overdue_time'] = date('Y-m-d', $userInfo['overdue_time']);
            }
        }

        //等级
        $userInfo['level_grade'] = '';
        if ($userInfo['level']) {
            /** @var SystemUserLevelServices $levelServices */
            $levelServices = app()->make(SystemUserLevelServices::class);
            $levelInfo = $levelServices->getOne(['id' => $userInfo['level']], 'id,name,grade');
            $userInfo['level_grade'] = $levelInfo['grade'] ?? '';
        }

        //优惠券
        /** @var StoreCouponUserServices $storeCoupon */
        $storeCoupon = app()->make(StoreCouponUserServices::class);
        $userInfo['coupon_num'] = $storeCoupon->getUserValidCouponCount((int)$uid);

        return $userInfo;
    }

    /**
     * 获取单个用户信息
     * @param $id 用户id
     * @return mixed
     */
    public function oneUserInfo(int $id, string $type)
    {
        switch ($type) {
            case 'spread':
//                /** @var UserSpreadServices $services */
//                $services = app()->make(UserSpreadServices::class);
                /** @var UserFriendsServices $services */
                $services = app()->make(UserFriendsServices::class);
                return $services->getFriendList($id);
                break;
            case 'order':
                /** @var StoreOrderServices $services */
                $services = app()->make(StoreOrderServices::class);
                return $services->getUserOrderList($id);
                break;
            case 'integral':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getIntegralList($id, [], 'title,number,balance,mark,add_time,link_id,type');
                break;
            case 'sign':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getSignList($id, [], 'title,number,mark,add_time');
                break;
            case 'coupon':
                /** @var StoreCouponUserServices $services */
                $services = app()->make(StoreCouponUserServices::class);
                return $services->getUserCouponList($id, 0);
                break;
            case 'balance_change':
                /** @var UserMoneyServices $services */
                $services = app()->make(UserMoneyServices::class);
                return $services->getUserMoneyList($id, [], 'title,type,number,balance,mark,pm,status,add_time,link_id');
                break;
            case 'visit':
                /** @var StoreProductLogServices $services */
                $services = app()->make(StoreProductLogServices::class);
                return $services->getList(['uid' => $id, 'type' => 'visit'], 'product_id', 'id,product_id,max(add_time) as add_time');
                break;
            case 'spread_change':
                /** @var UserSpreadServices $services */
                $services = app()->make(UserSpreadServices::class);
                return $services->getSpreadList(['uid' => $id], '*', ['spreadUser', 'admin'], false);
                break;
            default:
                throw new AdminException('type参数错误');
        }
    }

    /**
     * 用户图表
     * @return array
     */
    public function userChart()
    {
        [$starday, $yesterday, $timeType, $timeKey] = $this->timeHandle('thirtyday', true);
        $user_list = $this->dao->userList($starday, $yesterday);
        $chartdata = [];
        $chartdata['legend'] = ['用户数'];//分类
        $chartdata['yAxis']['maxnum'] = 0;//最大值数量
        $chartdata['xAxis'] = $timeKey;//X轴值
        $chartdata['series'] = [];//分类1值
        if (!empty($user_list)) {
            $user_list = array_combine(array_column($user_list, 'day'), $user_list);
            $chartdata['yAxis']['maxnum'] = max(array_column($user_list, 'count'));
            foreach ($timeKey as $day) {
                if (isset($user_list[$day])) {
                    $chartdata['series'][] = $user_list[$day]['count'] ?? 0;
                } else {
                    $chartdata['series'][] = 0;
                }
            }
        }
        $chartdata['bing_xdata'] = ['未消费用户', '消费一次用户', '留存客户', '回流客户'];
        $color = ['#5cadff', '#b37feb', '#19be6b', '#ff9900'];
        $pay[0] = $this->dao->count(['pay_count' => 0]);
        $pay[1] = $this->dao->count(['pay_count' => 1]);
        $pay[2] = $this->dao->userCount(1);
        $pay[3] = $this->dao->userCount(2);
        foreach ($pay as $key => $item) {
            $bing_data[] = ['name' => $chartdata['bing_xdata'][$key], 'value' => $pay[$key], 'itemStyle' => ['color' => $color[$key]]];
        }
        $chartdata['bing_data'] = $bing_data;
        return $chartdata;
    }

    /***********************************************/
    /************ 前端api services *****************/
    /***********************************************/
    /**
     * 用户信息
     * @param $info
     * @return mixed
     */
    public function userInfo($info)
    {
        $uid = (int)$info['uid'];
        $broken_time = intval(sys_config('extract_time'));
        $search_time = time() - 86400 * $broken_time;
        //改造时间
        $search_time = '1970-01-01' . ' - ' . date('Y-m-d H:i:s', $search_time);
        //可提现佣金
        //返佣 +
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $brokerage_commission = (string)$userBrokerageServices->getUsersBokerageSum(['uid' => $uid, 'pm' => 1], $search_time);
        //退款退的佣金 -
        $refund_commission = (string)$userBrokerageServices->getUsersBokerageSum(['uid' => $uid, 'pm' => 0], $search_time);
        $info['broken_commission'] = bcsub($brokerage_commission, $refund_commission, 2);
        if ($info['broken_commission'] < 0)
            $info['broken_commission'] = 0;
        $info['commissionCount'] = bcsub($info['brokerage_price'], $info['broken_commission'], 2);
        if ($info['commissionCount'] < 0)
            $info['commissionCount'] = 0;
        return $info;
    }

    /**
     * 个人中心
     * @param array $user
     * @throws DbException
     */
    public function personalHome(array $user, $tokenData)
    {
        $uid = (int)$user['uid'];
        /** @var StoreCouponUserServices $storeCoupon */
        $storeCoupon = app()->make(StoreCouponUserServices::class);
        /** @var UserMoneyServices $userMoneyServices */
        $userMoneyServices = app()->make(UserMoneyServices::class);
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        /** @var UserLevelServices $userLevel */
        $userLevel = app()->make(UserLevelServices::class);
        /** @var StoreServiceServices $storeService */
        $storeService = app()->make(StoreServiceServices::class);
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);
        /** @var UserRelationServices $productRelation */
        $productRelation = app()->make(UserRelationServices::class);
        /** @var SystemMessageServices $systemMessageServices */
        $systemMessageServices = app()->make(SystemMessageServices::class);
        /** @var DiyServices $diyServices */
        $diyServices = app()->make(DiyServices::class);
        /** @var AgentLevelServices $agentLevelServices */
        $agentLevelServices = app()->make(AgentLevelServices::class);
        /** @var StoreProductLogServices $storeProductLogServices */
        $storeProductLogServices = app()->make(StoreProductLogServices::class);
        //是否存在核销码
        if (!$user['bar_code']) {
            $bar_code = $this->getBarCode();
            $this->dao->update($uid, ['bar_code' => $bar_code], 'uid');
            $user['bar_code'] = $bar_code;
        }
        //获取配置参数
        $configData = SystemConfigService::more([
            'member_card_status',
            'brokerage_func_status',
            'store_brokerage_statu',
            'store_brokerage_price',
            'member_func_status',
            'recharge_switch',
            'extract_time',
            'balance_func_status',
            'invoice_func_status',
            'special_invoice_status',
            'user_extract_bank_status',
            'user_extract_wechat_status',
            'user_extract_alipay_status',
            'user_extract_balance_status',
            'level_activate_status'
        ]);
        //看付费会员是否开启
        $user['is_open_member'] = $user['svip_open'] = !!($configData['member_card_status'] ?? 0);
        $user['agent_level_name'] = '';
        //分销等级信息
        if ($user['agent_level']) {
            $levelInfo = $agentLevelServices->getLevelInfo((int)$user['agent_level'], 'id,name');
            $user['agent_level_name'] = $levelInfo && $levelInfo['name'] ? $levelInfo['name'] : '';
        }
        $wechatUserInfo = $wechatUser->getOne(['uid' => $uid, 'user_type' => $tokenData['type']], 'uid,is_complete');
        $user['is_complete'] = $wechatUserInfo['is_complete'] ?? 0;
        $user['couponCount'] = $storeCoupon->getUserValidCouponCount((int)$uid);
        $user['like'] = $productRelation->getUserCount($uid, 0, 'like');
        $user['collectProductCount'] = $productRelation->getUserCount($uid, 0, 'collect', 'product');
        $user['collectVideoCount'] = 0;
        if (sys_config('video_func_status', 1)) {
            $user['collectVideoCount'] = $productRelation->getUserCount($uid, 0, 'collect', 'video');
        }
        $user['orderStatusNum'] = $storeOrder->getOrderData($uid, ['channel' => $user['identity']]);
        $user['notice'] = 0;
        $user['recharge'] = $userMoneyServices->getRechargeSum($uid);//累计充值
        $user['orderStatusSum'] = (float)$userMoneyServices->sum(['uid' => $uid, 'pm' => 0, 'status' => 1], 'number', true);
        $user['extractTotalPrice'] = $userExtract->getExtractSum(['uid' => $uid, 'status' => 1]);//累计提现
        $user['extractPrice'] = $user['brokerage_price'];//可提现
        $user['statu'] = (int)($configData['store_brokerage_statu'] ?? 0);
        $orderStatusSum = (float)$storeOrder->sum(['pid' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'uid' => $user['uid'], 'is_del' => 0], 'pay_price', true);//累计有效消费
        $user['spread_status'] = ($configData['brokerage_func_status'] ?? 1) && $this->checkUserPromoter($user['uid'], $user, $orderStatusSum);
        if (!$user['is_promoter'] && $user['spread_status']) {
            $this->dao->update($uid, ['is_promoter' => 1], 'uid');
            $user['is_promoter'] = 1;
        }
        if ($user['statu'] == 3) {
            $storeBrokeragePrice = $configData['store_brokerage_price'] ?? 0;
            $user['promoter_price'] = bcsub((string)$storeBrokeragePrice, (string)$user['orderStatusSum'], 2);
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $user['broken_commission'] = max($userBrokerageServices->getUserFrozenPrice($uid), 0);
        $user['commissionCount'] = max(bcsub((string)$user['brokerage_price'], (string)$user['broken_commission'], 2), 0);
        $user['spread_user_count'] = $this->dao->count(['spread_uid' => $uid]);
        $user['spread_order_count'] = $storeOrder->count(['type' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0, 'spread_or_uid' => $uid]);
        //用户等级信息
        $userLevelInfo = $userLevel->homeGetUserLevel((int)$user['uid'], $user);
        $user = array_merge($user, $userLevelInfo);
        $user['yesterDay'] = $userBrokerageServices->getUsersBokerageSum(['uid' => $uid, 'pm' => 1], 'yesterday');
        $user['recharge_switch'] = (int)($configData['recharge_switch'] ?? 0);//充值开关
        $user['adminid'] = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1, 'customer' => 1]);
        $user['broken_day'] = (int)($configData['extract_time'] ?? 0);//佣金冻结时间
        $user['balance_func_status'] = (int)($configData['balance_func_status'] ?? 0);
        $user['invioce_func'] = !!($configData['invoice_func_status'] ?? 0);
        $user['special_invoice'] = $user['invioce_func'] && ($configData['special_invoice_status'] ?? 0);
        $user['pay_vip_status'] = $user['is_ever_level'] || ($user['is_money_level'] && $user['overdue_time'] > time());
        $user['member_style'] = (int)$diyServices->getColorChange('member');
        if ($user['is_ever_level']) {
            $user['vip_status'] = 1;//永久会员
        } else {
            if (!$user['is_money_level'] && $user['overdue_time'] && $user['overdue_time'] < time()) {
                $user['vip_status'] = -1;//开通过已过期
            } else if (!$user['overdue_time'] && !$user['is_money_level']) {
                $user['vip_status'] = 2;//没有开通过
            } else if ($user['is_money_level'] && $user['overdue_time'] && $user['overdue_time'] > time()) {
                $user['vip_status'] = 3;//开通了，没有到期
            }
        }
        $user['service_num'] = $systemMessageServices->getUserMessageNum($uid);
        $user['is_agent_level'] = ($configData['brokerage_func_status'] ?? 1) && $agentLevelServices->count(['status' => 1, 'is_del' => 0]);
        $user['visit_num'] = $storeProductLogServices->getDistinctCount(['uid' => $uid, 'type' => 'visit'], 'product_id');
        $user['user_extract_bank_status'] = (int)($configData['user_extract_bank_status'] ?? 1);
        $user['user_extract_wechat_status'] = (int)($configData['user_extract_wechat_status'] ?? 1);
        $user['user_extract_alipay_status'] = (int)($configData['user_extract_alipay_status'] ?? 1);
        $user['user_extract_balance_status'] = (int)($configData['user_extract_balance_status'] ?? 1);
        //是否享受新人专享
        /** @var StoreNewcomerServices $newcomerServices */
        $newcomerServices = app()->make(StoreNewcomerServices::class);
        $user['newcomer_status'] = $newcomerServices->checkUserNewcomer($uid);
        $user['level_activate_status'] = $configData['level_activate_status'];
        $user['member_func_status'] = $configData['member_func_status'];
        $extendInfo = SystemConfigService::get('user_extend_info', []);
        $user['register_extend_info'] = [];
        if (!$user['level_activate_status']) {//不需要激活，用户激活状态默认为1
            $user['level_status'] = 1;
        }

        if ($extendInfo) {
            foreach ($extendInfo as &$item) {
                if (isset($item['use']) && $item['use'] && isset($item['user_show']) && $item['user_show']) {
                    if (!isset($item['param']) && $item['format'] == 'radio') {
                        $singlearr = [];
                        foreach ($item['singlearr'] as $singlear) {
                            $singlearr[$singlear] = $singlear;
                        }
                        $item['singlearr'] = $singlearr;
                    }

                    $user['register_extend_info'][] = $item;
                }
            }
            unset($item);
        }
        if (isset($user['extend_info']) && $user['extend_info']) {
            $default = $this->defaultExtendInfo;
            $params = array_column($default, 'param');
            $sex = $this->rSex;
            foreach ($user['extend_info'] as &$info) {
                if (isset($info['param']) && in_array($info['param'], $params)) {
                    if ($info['param'] == 'sex') {
                        $info['value'] = $sex[$user['sex']] ?? 0;
                    } elseif ($info['param'] == 'birthday') {
                        $info['value'] = $user['birthday'] ?? '';
                    } elseif ($info['param'] == 'address') {
                        $info['value'] = $user['addres'] ?? '';
                    } else {
                        $info['value'] = $user[$info['param']] ?? '';
                    }
                }
            }
        }

        $user['is_default_avatar'] = $user['avatar'] == sys_config('h5_avatar') ? 1 : 0;
        $user['division_status'] = app()->make(DivisionApplyServices::class)->value(['uid' => $user['uid'], 'is_del' => 0], 'status') ?? -1;
        $user['promoter_status'] = app()->make(PromoterApplyServices::class)->value(['uid' => $user['uid'], 'is_del' => 0], 'status') ?? -1;

        //采购商身份
        $is_channel = app()->make(ChannelMerchantServices::class)->isChannel($uid, true);
        $user['is_channel'] = $is_channel !== false ? 1 : 0;
        $discount = $is_channel['discount'] ?? 100;
        $user['channel_iden_name'] = $is_channel['iden_name'] ?? '';
        $user['channel_discount'] = $discount ? bcmul((string)$discount, '10', 1) : 0;
        if ($user['is_channel']) {
            $channel_order = $storeOrder->search(['pid' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'uid' => $user['uid'], 'is_del' => 0, 'channel' => 1])->column('channel_price,pay_price');
            $channel_discount_price = $channel_pay_price = 0;
            foreach ($channel_order as $item) {
                $channel_pay_price = bcadd((string)$item['pay_price'], (string)$channel_pay_price, 2);
                $channel_discount_price = bcadd((string)$item['channel_price'], (string)$channel_discount_price, 2);
            }
            $user['channel_pay_price'] = $channel_pay_price;
            $user['channel_discount_price'] = $channel_discount_price;
        }
        //管理员身份
        $user['is_service'] = app()->make(StoreServiceServices::class)->checkoutIsService(['uid' => $uid, 'account_status' => 1, 'customer' => 1]);
        $user['gift_cart_count'] = app()->make(StoreCartServices::class)->count(['uid' => $uid, 'is_send_gift' => 1]);
        //检测会员等级
        UserLevelJob::dispatch([$uid]);
        return $user;
    }

    /**
     * 用户资金统计
     * @param int $uid `
     */
    public function balance(int $uid)
    {
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserMoneyServices $userMoneyServices */
        $userMoneyServices = app()->make(UserMoneyServices::class);
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        $user['now_money'] = $userInfo['now_money'];//当前总资金
        $user['recharge'] = $userMoneyServices->getRechargeSum($uid);//累计充值
        $user['orderStatusSum'] = $storeOrder->sum(['pid' => 0, 'uid' => $uid, 'paid' => 1, 'is_del' => 0, 'refund_status' => [0, 3]], 'pay_price', true);//累计消费
        return $user;
    }

    /**
     * 用户修改信息
     * @param Request $request
     * @return mixed
     */
    public function eidtNickname(int $uid, array $data)
    {
        if (!$this->userExist($uid)) {
            throw new ValidateException('用户不存在');
        }
        if (!$this->dao->update($uid, $data, 'uid')) {
            throw new ValidateException('修改失败');
        }
        return true;
    }

    /**
     * 获取推广人排行
     * @param $data 查询条件
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRankList(array $data)
    {
        $startTime = $endTime = 0;
        switch ($data['type']) {
            case 'week':
                $startTime = strtotime('this week Monday');
                break;
            case 'month':
                $startTime = strtotime('first day of ' . date('F Y'));
                break;
        }
        $endTime = time();
        [$page, $limit] = $this->getPageValue();
        $field = 'spread_uid,count(uid) AS count,spread_time';
        /** @var UserSpreadServices $userSpreadServices */
        $userSpreadServices = app()->make(UserSpreadServices::class);
        $list = $userSpreadServices->getAgentRankList([$startTime, $endTime], $field, $page, $limit);
        $rank = 0;
        foreach ($list as $key => &$item) {
            if ($item['spread_uid'] == $data['uid']) $rank = $key + 1;
            $item['nickname'] = $item['nickname'] ?: '神秘人';
            $item['avatar'] = $item['avatar'] ?: sys_config('h5_avatar');
        }
        $week = $userSpreadServices->count(['spread_uid' => $data['uid'], 'time' => [strtotime('this week Monday'), time()], 'timeKey' => 'spread_time']);
        $month = $userSpreadServices->count(['spread_uid' => $data['uid'], 'time' => [strtotime('last month'), time()], 'timeKey' => 'spread_time']);
        $start = date('Y-m-d H:i', $startTime);
        $end = date('Y-m-d H:i', time());
        return compact('list', 'rank', 'week', 'month', 'start', 'end');
    }

    /**
     * 静默绑定推广人
     * @param Request $request
     * @return mixed
     */
    public function spread(int $uid, int $spreadUid, $code)
    {
        if ($uid == $spreadUid) {
            return true;
        }
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        if ($code && !$spreadUid) {
            /** @var QrcodeServices $qrCode */
            $qrCode = app()->make(QrcodeServices::class);
            if ($info = $qrCode->getOne(['id' => $code, 'status' => 1])) {
                $spreadUid = $info['third_id'];
            }
        }
        //记录好友关系
        if ($spreadUid && $uid && $spreadUid != $uid) {
            /** @var UserFriendsServices $serviceFriend */
            $serviceFriend = app()->make(UserFriendsServices::class);
            $serviceFriend->saveFriend($uid, $spreadUid);
        }
        $data = [];
        //永久绑定
        $store_brokergae_binding_status = sys_config('store_brokerage_binding_status', 1);
        if ($userInfo->spread_uid && $store_brokergae_binding_status == 1) {
            return true;
        } else {
            //绑定分销关系 = 所有用户
            if (sys_config('brokerage_bindind', 1) == 1) {
                //分销绑定类型为时间段且过期 ｜｜临时
                $store_brokerage_binding_time = sys_config('store_brokerage_binding_time', 30);
                if (!$userInfo['spread_uid'] || $store_brokergae_binding_status == 3 || ($store_brokergae_binding_status == 2 && ($userInfo['spread_time'] + $store_brokerage_binding_time * 24 * 3600) < time())) {
                    if ($spreadUid && ($userInfo['uid'] == $spreadUid || $userInfo->uid == $this->dao->value(['uid' => $spreadUid], 'spread_uid'))) {
                        $spreadUid = 0;
                    }
                    if ($spreadUid && $spreadInfo = $this->dao->get((int)$spreadUid)) {
                        //as
                        $data['spread_uid'] = $spreadUid;
                        $data['spread_time'] = time();

                        $data['division_id'] = $spreadInfo['division_id'];
                        $data['agent_id'] = $spreadInfo['agent_id'];
                        $data['staff_id'] = $spreadInfo['staff_id'];
                        switch ($spreadInfo['division_type']) {
                            case 1:
                                $data['division_id'] = $spreadInfo['uid'];
                                $data['agent_id'] = 0;
                                $data['staff_id'] = 0;
                                break;
                            case 2:
                                $data['division_id'] = $spreadInfo['division_id'];
                                $data['agent_id'] = $spreadInfo['uid'];
                                $data['staff_id'] = 0;
                                break;
                            case 3:
                                $data['division_id'] = $spreadInfo['division_id'];
                                $data['agent_id'] = $spreadInfo['agent_id'];
                                $data['staff_id'] = $spreadInfo['uid'];
                                break;
                        }


                    }
                }
            }
        }
        if ($data && !$this->dao->update($userInfo['uid'], $data, 'uid')) {
            throw new ValidateException('绑定失败');
        }
        if (isset($data['spread_uid']) && $data['spread_uid']) {
            /** @var UserBillServices $userBill */
            $userBill = app()->make(UserBillServices::class);
            //邀请新用户增加经验
            $userBill->inviteUserIncExp((int)$spreadUid);
        }
        return true;
    }

    /**
     * 添加访问记录
     * @param Request $request
     * @return mixed
     */
    public function setVisit(array $data)
    {
        $userInfo = $this->getUserInfo($data['uid']);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        if (isset($data['ip']) && $data['ip']) {
            $addressArr = $this->addressHandle($this->convertIp($data['ip']));
            $data['province'] = $addressArr['province'] ?? '';
        }
        $data['channel_type'] = $userInfo['user_type'];
        $data['add_time'] = time();
        /** @var UserVisitServices $userVisit */
        $userVisit = app()->make(UserVisitServices::class);
        if ($userVisit->save($data)) {
            return true;
        } else {
            throw new ValidateException('添加访问记录失败');
        }
    }

    /**
     * 获取活动状态
     * @return mixed
     */
    public function activity()
    {
        /** @var StoreBargainServices $storeBragain */
        $storeBragain = app()->make(StoreBargainServices::class);
        /** @var StoreCombinationServices $storeCombinaion */
        $storeCombinaion = app()->make(StoreCombinationServices::class);
        /** @var StoreSeckillServices $storeSeckill */
        $storeSeckill = app()->make(StoreSeckillServices::class);
        $data['is_bargin'] = (bool)$storeBragain->validBargain();
        $data['is_pink'] = (bool)$storeCombinaion->validCombination();
        $data['is_seckill'] = (bool)$storeSeckill->getSeckillCount();
        return $data;
    }

    /**
     * 获取用户下级推广人
     * @param int $uid 当前用户
     * @param int $grade 等级  0  一级 1 二级
     * @param string $orderBy 排序
     * @param string $keyword
     * @return array|bool
     */
    public function getUserSpreadGrade(int $uid = 0, $grade = 0, $orderBy = '', $keyword = '', $time = [], $type = 0)
    {
        $user = $this->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        $spread_one_ids = $this->getUserSpredadUids($uid, 1);
        $spread_two_ids = $this->getUserSpredadUids($uid, 2);
        $data = [
            'total' => count($spread_one_ids),
            'totalLevel' => count($spread_two_ids),
            'list' => []
        ];
        /** @var UserStoreOrderServices $userStoreOrder */
        $userStoreOrder = app()->make(UserStoreOrderServices::class);
        $list = [];
        $where = ['pid' => 0, 'type' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];
        if ($grade == 0) {
            if ($spread_one_ids) $list = $userStoreOrder->getUserSpreadCountList($spread_one_ids, $orderBy, $keyword, $time);
            $where = $where + ['spread_uid' => $uid];
        } else {
            if ($spread_two_ids) $list = $userStoreOrder->getUserSpreadCountList($spread_two_ids, $orderBy, $keyword, $time);
            $where = $where + ['spread_two_uid' => $uid];
        }
        foreach ($list as &$item) {
            if (isset($item['spread_time']) && $item['spread_time']) {
                $item['time'] = date('Y-m-d', $item['spread_time']);
            }
        }
        $data['list'] = $list;
        $data['brokerage_level'] = (int)sys_config('brokerage_level', 2);
        $data['count'] = 0;
        $data['price'] = 0;
        $data['order_count'] = 0;
        if ($list) {
            $uids = array_column($list, 'uid');
            $data['count'] = count($uids);
            /** @var StoreOrderServices $storeOrder */
            $storeOrder = app()->make(StoreOrderServices::class);
            if ($user['division_type'] == 1) {
                if ($grade == 0) unset($where['spread_uid']);
                if ($grade == 1) unset($where['spread_two_uid']);
                $data['price'] = $storeOrder->sum($where + ['division_id' => $uid], 'division_brokerage');
                $data['order_count'] = $storeOrder->count($where + ['division_id' => $uid]);
            } else {
                $data['price'] = $storeOrder->sum($where, $grade == 0 ? 'one_brokerage' : 'two_brokerage');
                $data['order_count'] = $storeOrder->count($where);
            }
        }
//
        return $data;
    }

    /**
     * 获取推广人uids
     * @param int $uid
     * @param bool $one
     * @return array
     */
    public function getUserSpredadUids(int $uid, int $type = 0)
    {
        $uids = $this->dao->getColumn(['spread_uid' => $uid], 'uid');
        if ($type === 1) {
            return $uids;
        }
        if ($uids) {
            $uidsTwo = $this->dao->getColumn([['spread_uid', 'in', $uids]], 'uid');
            if ($type === 2) {
                return $uidsTwo;
            }
            $brokerage_level = sys_config('brokerage_level');
            if ($uidsTwo && $brokerage_level == 2) {
                $uids = array_merge($uids, $uidsTwo);
            }
        }
        return $uids;
    }

    /**
     * 检测用户是否是推广员
     * @param int $uid
     * @param array $user
     * @param float $sumPrice
     * @return bool
     */
    public function checkUserPromoter(int $uid, $user = [], float $sumPrice = 0.00)
    {
        if (!$user) {
            $user = $this->getUserCacheInfo($uid);
        }
        if (!$user) {
            return false;
        }
        //分销是否开启
        if (!sys_config('brokerage_func_status')) {
            return false;
        }
        //用户分校推广资格是否开启4.0.32
        if (isset($user['spread_open']) && !$user['spread_open']) {
            return false;
        }
        $store_brokerage_statu = sys_config('store_brokerage_statu');
        if ($user['is_promoter'] || $store_brokerage_statu == 2) {
            return true;
        }
        if ($store_brokerage_statu == 3) {
            if (!$sumPrice) {
                /** @var StoreOrderServices $storeOrder */
                $storeOrder = app()->make(StoreOrderServices::class);
                $sumPrice = $storeOrder->sum(['pid' => 0, 'uid' => $uid, 'paid' => 1, 'is_del' => 0, 'refund_status' => [0, 3]], 'pay_price');//累计消费
            }
            $store_brokerage_price = sys_config('store_brokerage_price');
            if ($sumPrice > $store_brokerage_price) {
                $this->dao->update($uid, ['is_promoter' => 1]);
                return true;
            }
        }
        return false;
    }

    /**
     * 同步微信粉丝用户(后台接口)
     * @return bool
     */
    public function syncWechatUsers()
    {
        $key = md5('sync_wechat_users');
        //一天点击一次
        if (CacheService::get($key)) {
            return true;
        }
        $next_openid = null;
        do {
            $result = OfficialAccount::instance()->user()->list($next_openid);
            $userOpenids = $result['data'];
            //拆分大数组
            $opemidArr = array_chunk($userOpenids, 100);
            foreach ($opemidArr as $openids) {
                //加入同步|更新用户队列
                UserJob::dispatch([$openids]);
            }
            $next_openid = $result['next_openid'];
        } while ($next_openid != null);
        CacheService::set($key, 1, 3600 * 24);
        return true;
    }

    /**
     * 导入微信粉丝用户
     * @param array $openids
     * @return bool
     */
    public function importUser(array $noBeOpenids)
    {
        if (!$noBeOpenids) {
            return true;
        }
        $dataAll = $data = [];
        $time = time();
        foreach ($noBeOpenids as $openid) {
            try {
                $info = OfficialAccount::instance()->user()->get($openid);
                $info = is_object($info) ? $info->toArray() : $info;
            } catch (\Throwable $e) {
                $info = [];
            }
            if (!$info) continue;
            if (($info['subscribe'] ?? 1) == 1) {
                $data['nickname'] = $info['nickname'] ?? '';
                $data['headimgurl'] = $info['headimgurl'] ?? '';
                $userInfoData = $this->setUserInfo($data);
                if (!$userInfoData) {
                    throw new AdminException('用户信息储存失败!');
                }
                $data['uid'] = $userInfoData['uid'];
                $data['subscribe'] = $info['subscribe'];
                $data['unionid'] = $info['unionid'] ?? '';
                $data['openid'] = $info['openid'] ?? '';
                $data['sex'] = $info['sex'] ?? 0;
                $data['language'] = $info['language'] ?? '';
                $data['city'] = $info['city'] ?? '';
                $data['province'] = $info['province'] ?? '';
                $data['country'] = $info['country'] ?? '';
                $data['subscribe_time'] = $info['subscribe_time'] ?? '';
                $data['groupid'] = $info['groupid'] ?? 0;
                $data['remark'] = $info['remark'] ?? '';
                $data['tagid_list'] = isset($info['tagid_list']) && $info['tagid_list'] ? implode(',', $info['tagid_list']) : '';
                $data['add_time'] = $time;
                $data['is_complete'] = 1;
                $dataAll[] = $data;
            }
        }
        if ($dataAll) {
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            if (!$wechatUser->saveAll($dataAll)) {
                throw new ValidateException('保存用户信息失败');
            }
        }
        return true;
    }

    /**
     * 修改会员的时间及是否会员状态
     * @param int $vip_day 会员天数
     * @param array $user_id 用户id
     * @param int $is_money_level 会员来源途径
     * @param bool $member_type 会员卡类型
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function setMemberOverdueTime($vip_day, int $user_id, int $is_money_level, $member_type = false)
    {
        if ($vip_day == 0) throw new ValidateException('天数不能为0');
        $user_info = $this->getUserInfo($user_id);
        if (!$user_info) throw new ValidateException('用户数据不存在');
        if (!$member_type) $member_type = "month";
        if ($member_type == 'ever') {
            $overdue_time = 0;
            $is_ever_level = 1;
        } else {
            if ($user_info['is_money_level'] == 0) {
                $overdue_time = bcadd(bcmul($vip_day, 86400, 0), time(), 0);
            } else {
                $overdue_time = bcadd(bcmul($vip_day, 86400, 0), $user_info['overdue_time'], 0);
            }
            $is_ever_level = 0;
        }
        $setData['overdue_time'] = $overdue_time;
        $setData['is_ever_level'] = $is_ever_level;
        $setData['is_money_level'] = $is_money_level ? $is_money_level : 0;
        // if ($user_info['level'] == 0) $setData['level'] = 1;
        return $this->dao->update(['uid' => $user_id], $setData);
    }

    /**
     * 清空到期svip（分批加入队列）
     * @return bool
     */
    public function offUserSvip()
    {
        $users = $this->dao->getColumn([['is_ever_level', '=', 0], ['is_money_level', '>', 0], ['overdue_time', '<', time()]], 'uid');
        if ($users) {
            //拆分大数组
            $uidsArr = array_chunk($users, 100);
            foreach ($uidsArr as $uids) {
                //加入同步|更新用户队列
                UserSvipJob::dispatch([$uids]);
            }
        }
        return true;
    }

    /**
     * 会员过期改变状态，变为普通会员
     * @param $uid
     * @param null $userInfo
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function offMemberLevel($uid, $userInfo = null)
    {
        if (!$uid) return false;
        $userInfo = $userInfo ?: $this->dao->get($uid);
        if (!$userInfo) return false;
        if ($userInfo['is_ever_level'] == 0 && $userInfo['is_money_level'] > 0 && $userInfo['overdue_time'] < time()) {
            $this->dao->update(['uid' => $uid], ['is_money_level' => 0]);
            return false;
        }
        return true;
    }

    /**
     * @param array $where
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserInfoList(array $where, $field = "*")
    {
        return $this->dao->getUserInfoList($where, $field);
    }

    /**
     * 保存用户上级推广人
     * @param int $uid
     * @param int $spread_uid
     * @return bool
     */
    public function saveUserSpreadUid(int $uid, int $spread_uid)
    {
        if (!$uid || !$spread_uid) {
            return false;
        }
        if ($uid == $spread_uid) {
            throw new ValidateException('上级推广人不能为自己');
        }
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('用户不存在');
        }
        //上级已经是这个uid
        if ($userInfo['spread_uid'] == $spread_uid) {
            return true;
        }
        $spreadInfo = $this->getUserInfo($spread_uid);
        if (!$spreadInfo) {
            throw new ValidateException('上级用户不存在');
        }
        if ($spreadInfo['spread_uid'] == $uid) {
            throw new ValidateException('上级推广人不能为自己下级');
        }
        $data = [
            'spread_uid' => $spread_uid,
            'spread_time' => time(),
            'division_id' => $spreadInfo['division_id'],
            'agent_id' => $spreadInfo['agent_id'],
            'staff_id' => $spreadInfo['staff_id'],
        ];
        switch ($spreadInfo['division_type']) {
            case 1:
                $data['division_id'] = $spreadInfo['uid'];
                $data['agent_id'] = 0;
                $data['staff_id'] = 0;
                break;
            case 2:
                $data['division_id'] = $spreadInfo['division_id'];
                $data['agent_id'] = $spreadInfo['uid'];
                $data['staff_id'] = 0;
                break;
            case 3:
                $data['division_id'] = $spreadInfo['division_id'];
                $data['agent_id'] = $spreadInfo['agent_id'];
                $data['staff_id'] = $spreadInfo['uid'];
                break;
        }

        $this->dao->update($uid, $data);
        //记录推广绑定关系
        UserSpreadJob::dispatch([$uid, $spread_uid]);
        //记录好友关系
        UserFriendsJob::dispatch([$uid, $spread_uid]);
        return true;
    }

    /**
     * 增加推广用户佣金
     * @param int $uid
     * @param int $spread_uid
     * @param array $userInfo
     * @param array $spread_user
     * @return bool|mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function addBrokeragePrice(int $uid, int $spread_uid, array $userInfo = [], array $spread_user = [])
    {
        if (!$uid || !$spread_uid) {
            return false;
        }
        //商城分销功能是否开启 0关闭1开启
        if (!sys_config('brokerage_func_status')) return true;
        //获取设置推广佣金单价
        $brokerage_price = sys_config('uni_brokerage_price', 0);
        //推广佣金是否开启
        if (!sys_config('brokerage_user_status', 0)) {
            return true;
        }
        //获取推广佣金当日限额
        $day_brokerage_price_upper = sys_config('day_brokerage_price_upper', 0);
        if (!floatval($brokerage_price) || !floatval($day_brokerage_price_upper)) {
            return true;
        }
        if (!$userInfo) {
            $userInfo = $this->getUserInfo($uid);
        }
        if (!$userInfo) {
            return false;
        }
        if (!$spread_user) {
            $spread_user = $this->dao->getOne(['uid' => $spread_uid, 'status' => 1]);
        }
        if (!$spread_user) {
            return false;
        }
        //判断是否是推广员
        if (!$this->checkUserPromoter($spread_uid, $spread_user)) {
            return false;
        }
        //判断用户是否被删除
        if (!$this->getUserOnlyTrashedCount($uid)) {
            return false;
        }

        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        //当日限额是否为-1 不限制金额，否则判断今日佣金总和是否超过上限
        if ($day_brokerage_price_upper != -1) {
            if ($day_brokerage_price_upper <= 0) {
                return true;
            } else {
                //获取上级用户今日获取推广用户佣金
                $spread_day_brokerage = $userBrokerageServices->getUserBillBrokerageSum($spread_uid, ['brokerage_user'], 'today');
                //超过上限
                if (($spread_day_brokerage + $brokerage_price) > $day_brokerage_price_upper) {
                    return true;
                }
            }
        }

        $spreadPrice = $spread_user['brokerage_price'];
        // 上级推广员返佣之后的金额
        $balance = bcadd($spreadPrice, $brokerage_price, 2);
        return $this->transaction(function () use ($uid, $spread_uid, $brokerage_price, $userInfo, $balance, $userBrokerageServices) {
            // 添加返佣记录
            $res1 = $userBrokerageServices->income('get_user_brokerage', $spread_uid, [
                'nickname' => $userInfo['nickname'],
                'number' => floatval($brokerage_price)
            ], $balance, $uid);
            // 添加用户余额
            $res2 = $this->dao->bcInc($spread_uid, 'brokerage_price', $brokerage_price, 'uid');
            //给上级发送获得佣金的模板消息
            /** @var StoreOrderTakeServices $storeOrderTakeServices */
            $storeOrderTakeServices = app()->make(StoreOrderTakeServices::class);
            $storeOrderTakeServices->sendBackOrderBrokerage([], $spread_uid, $brokerage_price, 'user');
            return $res1 && $res2;
        });
    }

    /**
     * 查询用户是否注销重新注册
     * @param int $uid
     * @return bool
     * User: liusl
     * DateTime: 2025/3/21 下午5:23
     */
    public function getUserOnlyTrashedCount(int $uid)
    {
        $userInfo = $this->getUserCacheInfo($uid);
        if ($userInfo['phone'] != '' && $this->dao->getUserOnlyTrashedCount(['phone' => $userInfo['phone']])) {
            return false;
        }
        //根据openid查询此用户注销过，不反推广佣金
        $wechatUserServices = app()->make(WechatUserServices::class);
        $openidArray = $wechatUserServices->getColumn(['uid' => $uid], 'openid', 'id');
        if ($wechatUserServices->getCount([['openid', 'in', $openidArray], ['is_del', '=', 1]])) {
            return false;
        }
        return true;
    }

    /**
     * 获取上级uid
     * @param int $uid
     * @param array $userInfo
     * @param bool $is_spread
     * @return int|mixed
     */
    public function getSpreadUid(int $uid, $userInfo = [], $is_spread = true)
    {
        if (!$uid) {
            return 0;
        }
        //商城分销功能是否开启 0关闭1开启
        if (!sys_config('brokerage_func_status')) return -1;
        if (!$userInfo) {
            $userInfo = $this->getUserCacheInfo($uid);
        }
        if (!$userInfo) {
            return 0;
        }
        //上级的上级不需要检测自购
        if ($is_spread) {
            //开启自购
            $is_self_brokerage = sys_config('is_self_brokerage', 0);
            if ($is_self_brokerage && $this->checkUserPromoter($uid)) {
                return $uid;
            }
        }
        //绑定类型
        $store_brokergae_binding_status = sys_config('store_brokerage_binding_status', 1);
        if ($store_brokergae_binding_status == 1 || $store_brokergae_binding_status == 3) {
            return $userInfo['spread_uid'];
        }
        //分销绑定类型为时间段且没过期
        $store_brokerage_binding_time = sys_config('store_brokerage_binding_time', 30);
        if ($store_brokergae_binding_status == 2 && ($userInfo['spread_time'] + $store_brokerage_binding_time * 24 * 3600) > time()) {
            return $userInfo['spread_uid'];
        }
        return -1;
    }

    /**
     * 用户付款code
     * @param int $uid
     * @return bool|mixed|null
     */
    public function getRandCode(int $uid)
    {
        $key = 'user_rand_code' . $uid;
        return CacheService::redisHandler()->remember($key, function () {
            return substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 9), 1))), 0, 3) . str_pad((string)mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        }, 600);
    }

    /**
     * 获取barcode
     * @return bool|int|mixed|null
     */
    public function getBarCode()
    {
        mt_srand();
        $code = substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 9), 1))), 0, 4) . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        if (!$this->dao->getOne(['bar_code' => $code])) {
            return $code;
        } else {
            return $this->getBarCode();
        }
    }

    /**
     * 获取用户推广用户列表
     * @param $uid
     * @param $type
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function agentUserList($uid, $type)
    {
        $where['spread_uid'] = $uid;
        if ($type == 1) {
            $where['pay_count'] = -1;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, 'uid,nickname,avatar,FROM_UNIXTIME(spread_time, \'%Y.%m.%d %H:%m\') as spread_time', $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 推送用户信息
     * @param $data
     * @param $pushUrl
     * @return bool
     */
    public function userUpdate($data, $pushUrl)
    {
        return $this->outPush($pushUrl, $data, '更新用户信息');
    }

    /**
     * 默认数据推送
     * @param string $pushUrl
     * @param array $data
     * @param string $tip
     * @return bool
     */
    function outPush(string $pushUrl, array $data, string $tip = ''): bool
    {
        $param = json_encode($data, JSON_UNESCAPED_UNICODE);
        $res = HttpService::postRequest($pushUrl, $param, ['Content-Type:application/json', 'Content-Length:' . strlen($param)]);
        $res = $res ? json_decode($res, true) : [];
        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            \think\facade\Log::error(
                json_encode(['msg' => $tip . '推送失败', 'data' => $res], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
            return false;
        }
        return true;
    }

    /**
     * 获取用户可用积分
     * @param $uid
     * @return mixed
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2025/1/3 下午2:45
     */
    public function getUserIntegral($uid): mixed
    {
        $userBillServices = app()->make(UserBillServices::class);
        $integral = $this->dao->value(['uid' => $uid], 'integral');
        $frozen = $userBillServices->getBillSum(['uid' => $uid, 'category' => 'integral', 'pm' => 1, 'is_frozen' => 1]);
        $frozen = $frozen ?: 0;
        $userIntegral = bcsub($integral, $frozen, 0);
        return max($userIntegral, 0);
    }

    /**
     * 切换身份
     * @param $uid
     * @param $identity 0普通,1采购商,2 客服
     * @return bool
     * User: liusl
     * DateTime: 2025/4/8 下午3:12
     */
    public function switchIdentity(int $uid, int $identity)
    {
        // 校验 identity 是否合法
        if (!in_array($identity, [0, 1, 2], true)) {
            throw new ValidateException('非法的身份参数: ' . $identity);
        }
        $auth = true;
        $mag = '';
        switch ($identity) {
            case 1:
                $auth = app()->make(ChannelMerchantServices::class)->isChannel($uid);
                $mag = '采购商';
                break;
            case 2:
                $auth = app()->make(StoreServiceServices::class)->checkoutIsService(['uid' => $uid, 'account_status' => 1, 'customer' => 1]);
                $mag = '客服';
                break;
        }
        if (!$auth) {
            throw new ValidateException('身份切换失败,您不是' . $mag . '身份');
        }
        $this->dao->update($uid, ['identity' => $identity]);
        return true;
    }

    /**
     * 获取所有有锁定积分的用户
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUsersWithLockedIntegral(): array
    {
        return $this->dao->getList(['integral_lock' => ['>', 0]], 'uid,integral,integral_lock,nickname,phone');
    }

    /**
     * 释放用户锁定积分
     * @param int $uid
     * @param float $amount
     * @return bool
     */
    public function releaseLockedIntegral(int $uid, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $user = $this->getUserInfo($uid);
        if (!$user) {
            return false;
        }

        $lockedIntegral = $user['integral_lock'] ?? 0;
        if ($lockedIntegral < $amount) {
            $amount = $lockedIntegral;
        }

        if ($amount <= 0) {
            return false;
        }

        return $this->transaction(function () use ($uid, $amount) {
            // 减少锁定积分
            $this->dao->decField($uid, 'integral_lock', $amount);
            // 增加可用积分
            $this->dao->incField($uid, 'integral', $amount);
            return true;
        });
    }

}
