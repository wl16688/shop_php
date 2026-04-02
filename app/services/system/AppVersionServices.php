<?php

namespace app\services\system;

use app\dao\system\AppVersionDao;
use app\services\BaseServices;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\facade\Route as Url;

class AppVersionServices extends BaseServices
{
    /**
     * @var AppVersionDao
     */
    #[Inject]
    protected AppVersionDao $dao;

    public function versionList($where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->versionList($where, $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        $count = $this->dao->versionCount($where);
        return compact('list', 'count');
    }

    public function createForm($id = 0)
    {
        if ($id) {
            $info = $this->dao->get($id);
        }
        $field[] = Form::hidden('id', $info['id'] ?? 0);
        $field[] = Form::input('version', '版本号', $info['version'] ?? '')->col(24);
        $field[] = Form::radio('platform', '平台类型', $info['platform'] ?? 1)->options([['label' => 'Android', 'value' => 1], ['label' => 'IOS', 'value' => 2]]);
        $field[] = Form::input('info', '版本介绍', $info['info'] ?? '')->type('textarea');
        $field[] = Form::input('url', '下载链接', $info['url'] ?? '')->appendRule('suffix', [
            'type' => 'div',
            'class' => 'tips-info',
            'domProps' => ['innerHTML' => '填写下载链接，Android的为压缩包的url地址，点击升级会自动下载压缩包替换安装，例如：域名/xxx.zip；IOS的为应用商店链接地址，直接跳转AppStore，例如：itms-apps://itunes.apple.com/cn/app/id1234567890']
        ]);
        $field[] = Form::radio('is_force', '强制', $info['is_force'] ?? 1)->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
        $field[] = Form::radio('is_new', '是否最新', $info['is_new'] ?? 1)->options([['label' => '是', 'value' => 1], ['label' => '否', 'value' => 0]]);
        return create_form('添加版本信息', $field, Url::buildUrl('/app_version/version_save'), 'POST');
    }

    public function versionSave($id, $data)
    {
        if ($id) {
            return $this->transaction(function () use ($data, $id) {
                if ($data['is_new']) {
                    $this->dao->update(['platform' => $data['platform']], ['is_new' => 0]);
                }
                return $this->dao->update($id, $data);
            });
        } else {
            $data['is_del'] = 0;
            $data['add_time'] = time();
            return $this->transaction(function () use ($data) {
                $this->dao->update(['platform' => $data['platform']], ['is_new' => 0]);
                return $this->dao->save($data);
            });
        }
    }

    public function getNewInfo($platform)
    {
        $res = $this->dao->get(['platform' => $platform, 'is_new' => 1]);
        if ($res) {
            $res = $res->toArray();
            $res['time'] = date('Y-m-d H:i:s', $res['add_time']);
            return $res;
        } else {
            return [];
        }
    }
}
