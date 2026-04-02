<?php
declare (strict_types=1);

namespace app\services\user\channel;

use app\dao\user\channel\ChannelMerchantIdentityDao;
use app\dao\user\UserBillDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\facade\Route as Url;

/**
 * 采购商身份服务
 * Class ChannelMerchantIdentityServices
 * @package app\services\user\channel
 * @mixin ChannelMerchantIdentityDao
 */
class ChannelMerchantIdentityServices extends BaseServices
{
    /**
     * @var ChannelMerchantIdentityDao
     */
    #[Inject]
    protected ChannelMerchantIdentityDao $dao;


    /**
     * 获取采购商身份列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取所有采购商身份
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllIdentities(array $where = [])
    {
        $where['is_del'] = 0;
        return $this->dao->search($where,false)->column('name,id');
    }

    /**
     * 添加采购商身份
     * @param array $data
     * @return bool
     */
    public function add(array $data)
    {
        if ($this->dao->isNameExist($data['name'])) {
            throw new AdminException('该身份名称已存在');
        }
//        if ($this->dao->isLevelExist($data['level'])) {
//            throw new AdminException('该等级已存在');
//        }
        $data['add_time'] = time();
        $res = $this->dao->save($data);
        return $res != false;;
    }

    /**
     * 编辑采购商身份
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function edit(int $id, array $data)
    {
        if ($this->dao->isNameExist($data['name'], $id)) {
            throw new AdminException('该身份名称已存在');
        }
//        if ($this->dao->isLevelExist($data['level'], $id)) {
//            throw new AdminException('该等级已存在');
//        }
        $res = $this->dao->update($id, $data);
        return $res != false;
    }

    /**
     * 删除采购商身份
     * @param int $id
     * @return bool
     */
    public function delete(int $id)
    {
        return $this->dao->delete($id);
    }

    /**
     * 获取采购商身份表单
     * @param array $data
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function getForm(array $data = [])
    {
        $f = [];
        $f[] = Form::input('name', '身份名称', $data['name'] ?? '')->required();
        $f[] = Form::number('discount', '享受折扣', $data['discount'] ?? 100)->info('采购商采购商品的折扣（1-100）例：95折输入95')->min(0)->max(100)->precision(0)->required();
        $f[] = Form::radio('is_show', '是否显示', $data['is_show'] ?? 1)->options([
            ['label' => '显示', 'value' => 1],
            ['label' => '隐藏', 'value' => 0],
        ]);
        return $f;
    }

    /**
     * 获取创建数据表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm(int $id = 0)
    {
        if ($id) {
            $info = $this->dao->get($id);
            $info = $info ? $info->toArray() : [];
            $action = Url::buildUrl('/channel/identity/' . $id)->build();
            $title = '编辑采购商身份';
            $method = 'PUT';
        } else {
            $info = [];
            $action = Url::buildUrl('/channel/identity')->build();
            $title = '添加采购商身份';
            $method = 'POST';
        }
        return create_form($title, $this->getForm($info), $action, $method);
    }

    /**
     * 设置状态
     * @param int $id
     * @param int $status
     * @return \crmeb\basic\BaseModel
     * User: liusl
     * DateTime: 2025/3/27 上午9:59
     */
    public function setStatus(int $id, int $status)
    {
        if (!$id) {
            return $this->fail('参数错误');
        }
        return $this->dao->update($id, ['is_show' => $status]);
    }
}
