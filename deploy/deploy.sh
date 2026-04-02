#!/bin/bash
# ==============================================================================
# Linux 下 ThinkPHP 8 (CRMEB) 一键部署脚本
# 说明：该脚本适用于标准 Linux 环境（Ubuntu/CentOS 等）
# 依赖：需提前安装 PHP 8.0+、Composer、Nginx
# ==============================================================================

# 设置发生错误时立即退出
set -e

# 获取脚本所在目录并切换到项目根目录
PROJECT_DIR=$(cd $(dirname $0)/.. && pwd)
cd "$PROJECT_DIR"

echo "=================================================="
echo "开始部署项目，项目路径: $PROJECT_DIR"
echo "=================================================="

# 1. 检查必要环境
if ! command -v composer &> /dev/null; then
    echo "❌ 错误: 未找到 Composer，请先安装 Composer。"
    exit 1
fi

if ! command -v php &> /dev/null; then
    echo "❌ 错误: 未找到 PHP 命令行工具，请先安装 PHP 8.0+。"
    exit 1
fi

# 2. 拉取最新代码 (如果有 Git 环境则执行)
if [ -d ".git" ]; then
    echo "-> 正在拉取 Git 最新代码..."
    git pull origin master
else
    echo "-> 当前目录不是 Git 仓库，跳过代码拉取。"
fi

# 3. 安装/更新依赖
echo "-> 正在安装 Composer 依赖 (不包含 dev 依赖，优化自动加载，忽略平台环境检查)..."
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 4. 创建必要的目录并设置权限
echo "-> 正在创建目录并设置权限 (runtime, public/uploads)..."
mkdir -p runtime
mkdir -p public/uploads

# 赋予 775 权限，确保 Web 服务器可以写入
chmod -R 775 runtime
chmod -R 775 public/uploads

# （可选）如果你的 Web 用户是 www 或 www-data，可以解除下方注释并修改用户名
# chown -R www:www "$PROJECT_DIR"

# 5. 清理项目缓存
echo "-> 正在清理 ThinkPHP 缓存..."
php think clear

# （可选）如果项目使用 Swoole 并需要重启，可取消注释并配置对应的重启命令
echo "-> 正在重启 Swoole 服务..."
php think swoole restart

echo "=================================================="
echo "✅ 部署脚本执行完毕！"
echo ""
echo "-> 下一步操作建议："
echo "1. 确保已将 Nginx 配置文件 (deploy/nginx.conf) 放置到 /etc/nginx/conf.d/ 或对应目录"
echo "2. 根据实际情况修改 nginx.conf 中的 root 路径，将其指向 ${PROJECT_DIR}/public"
echo "3. 检查 nginx 配置是否正确：nginx -t"
echo "4. 重启 Nginx 使配置生效：systemctl restart nginx 或 service nginx restart"
echo "=================================================="
