#### 安装流程

#####  1.拉取代码

```
git clone git@github.com:wfanxin/match-api.git
```

##### 2.进入目录

```
cd match-api
```

##### 3.安装依赖

```
composer install
```

##### 4.复制.env.example文件为.env，并配置.env文件，需要先创建数据库

```
cp .env.example .env
```

##### 5.创建目录（访问接口域名，可以根据错误提示创建）

```
mkdir storage && mkdir storage/logs
```

##### 6.所有者设为www（访问接口域名，可以根据错误提示创建）

```
chown www -R ../match-api
```

##### 7.生成.env文件中的APP_KEY值

```
php artisan key:generate
```

##### 8.生成数据库表

```
php artisan migrate
```

