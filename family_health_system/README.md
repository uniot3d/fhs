# 家庭人员健康管理系统

这是一个基于PHP的家庭健康管理系统，可以帮助用户管理家庭成员的健康记录、病历和用药情况。系统支持移动设备访问，让用户随时随地管理家人的健康数据。
在线测试版本地址 http://153.3.126.191:81/family_health_system/
开发者：南京巨烽软件科技有限公司


## 主要功能

- **用户认证系统**：注册、登录、退出
- **家庭成员管理**：添加、编辑、查看和删除家庭成员
- **健康记录**：记录身高、体重、血压等健康指标
- **病历管理**：记录就医信息、诊断结果和处方
- **用药管理**：记录用药情况，包括药物名称、剂量、频率和时间段
- **健康数据可视化**：通过图表展示健康指标的变化趋势

## 技术栈

- **后端**：PHP
- **数据库**：MySQL
- **前端**：HTML, CSS, JavaScript
- **UI框架**：Bootstrap 5
- **图表库**：Chart.js

## 安装步骤

1. 确保您的服务器已安装PHP 7.0+和MySQL 5.7+
2. 将项目文件上传到Web服务器目录（如Apache的htdocs或Nginx的html目录）
3. 在浏览器中访问项目根目录，例如：`http://localhost/family_health_system/`
4. 系统会自动创建数据库和表结构，并创建默认管理员账户
5. 使用默认管理员账户登录（用户名：admin，密码：admin123）

## 初始化数据库

如果系统没有自动创建数据库，您可以手动初始化数据库：

1. 访问 `http://localhost/family_health_system/config/init_db.php`
2. 这将创建必要的数据库表和默认管理员账户

## 系统要求

- PHP 7.0+
- MySQL 5.7+
- Web服务器（Apache/Nginx）
- 支持现代浏览器（Chrome、Firefox、Safari、Edge等）

## 默认账户

- **用户名**：admin
- **密码**：123456
- **角色**：管理员

## 文件结构

```
family_health_system/
├── config/                  # 配置文件
│   ├── database.php         # 数据库连接配置
│   └── init_db.php          # 数据库初始化脚本
├── css/                     # CSS样式文件
│   └── style.css            # 自定义样式
├── includes/                # 包含文件
│   ├── functions.php        # 通用函数
│   ├── header.php           # 页面头部
│   └── footer.php           # 页面底部
├── js/                      # JavaScript文件
│   └── main.js              # 主要脚本
├── images/                  # 图片资源
├── index.php                # 首页
├── login.php                # 登录页面
├── register.php             # 注册页面
├── dashboard.php            # 仪表盘
├── family_members.php       # 家庭成员管理
├── health_records.php       # 健康记录管理
├── medical_records.php      # 病历管理
├── medication_records.php   # 用药记录管理
└── README.md                # 项目说明文档
```

## 移动端支持

本系统采用响应式设计，支持在各种设备上使用，包括桌面电脑、平板和手机。用户可以通过手机随时记录和查看健康数据。

## 安全性

- 所有用户密码通过PHP的`password_hash()`函数加密存储
- 使用预处理语句防止SQL注入攻击
- 输入数据经过过滤和验证
- 基于会话的用户认证和授权

## 自动安装说明

1. 上传项目到Web服务器目录（如htdocs）。
2. 在浏览器访问 `http://你的域名或IP/family_health_system/`，系统会自动跳转到 `install.php`。
3. 按提示填写数据库主机、用户名、密码、数据库名，点击“安装系统”。
4. 安装完成后自动跳转到首页，使用默认管理员账户登录（用户名：admin，密码：admin123）。
5. 后续如需重新安装，请删除 `install.lock` 文件。

## 其他说明
Deepseek你们自己续费啊，不要用我的号啊，我的号是用来进行开发测试的啦。

## 联系我们
邮箱：593790175@qq.com
微信：unity3ds
官网：www.uniot3d.com

## 项目起因
我们是一家搞数字孪生的公司，做这个软件纯粹是因为看到今年的新闻，很多年轻人年纪轻轻就莫名其妙的嘎了，希望可以通过IT技术可以改变这个现状。
当然今年与往年也不太一样，没啥项目，出去跑啥客户也跑不出来什么名堂，拿点时间做点对世界有益的事儿吧。




