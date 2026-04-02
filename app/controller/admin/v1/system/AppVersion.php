<?php

namespace app\controller\admin\v1\system;

use app\controller\admin\AuthController;
use app\services\system\AppVersionServices;
use think\annotation\Inject;

class AppVersion extends AuthController
{
    /**
     * @var AppVersionServices
     */
    #[Inject]
    protected AppVersionServices $services;

    /**
     * 版本列表
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/4/2
     */
    public function list()
    {
        $where = $this->request->getMore([
            ['platform', '']
        ]);
        return $this->success($this->services->versionList($where));
    }

    /**
     * 新增版本表单
     * @param $id
     * @return \think\Response
     * @throws \FormBuilder\Exception\FormBuilderException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/4/2
     */
    public function crate($id)
    {
        return $this->success($this->services->createForm($id));
    }

    /**
     * 保存数据
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/4/2
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['version', ''],
            ['platform', 1],
            ['info', ''],
            ['is_force', 1],
            ['url', ''],
            ['is_new', 1],
        ]);
        $id = $data['id'];
        unset($data['id']);
        $this->services->versionSave($id, $data);
        return $this->success(100021);
    }

    /**
     * 删除App版本
     * @param $id
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/4/2
     */
    public function del($id)
    {
        $this->services->delete($id);
        return $this->success('删除成功');
    }
}
