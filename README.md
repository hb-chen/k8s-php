# k8s-php
PHP应用容器化，nginx + php-fpm + mysql + redis

**TODO**
- [x] 代码与Pod分开部署时依赖问题
    - Readiness
    - Liveness
- [x] 共享存储
- git-sync
	- [ ] [`PULL`代码时`404`](#PULL代码时404)
    - [ ] 同步失败、异常等
    - [ ] git-sync权限问题，当前`fsGroup`、`runAsUser`以`0`运行
    - webhook
        - *git-sync的webhook是拉取到更新时触发webhook，并非webhook触发sync*
        - [ ] initContainers模式
        - [ ] DaemonSet模式
        - [ ] StatefulSet模式
 

## Docker镜像

- [Alpine Dockerfile](/Dockerfile)
- [CentOS Dockerfile](/Dockerfile.CentOS)
    - 参考:[docker-library/php](https://github.com/docker-library/php)
    - 镜像很大，但对`OS`有需求的考虑，比如：~~阿里云要挂载共享存储，插件只支持`CentOS7`镜像~~
    (这里被阿里的工单回复误导了，测试`Alpine`镜像使用`Flexvolume`挂载`NAS`卷可以使用)
- [已打包镜像](https://hub.docker.com/r/hbchen/php)

```bash
# Alpine
docker build -t hbchen/php:5.6.40-fpm-alpine-mysql-redis .

# CentOS
docker build -f Dockerfile.CentOS -t hbchen/php:5.6.40-fpm-centos-mysql-redis .
```

## 本地Docker测试

```bash
# PHP app
docker run --rm --name php-app \
-v {pwd}/app:/var/www/src/app:ro \
hbchen/php:5.6.40-fpm-alpine-mysql-redis

# Nginx
docker run --rm --name nginx -p 8080:80 \
-v {pwd}/nginx/conf.d:/etc/nginx/conf.d:ro \
--link php-app:php-app \
nginx:1.17.3-alpine
```

### 调试
```bash
curl -HHost:hbchen.com 'http://localhost:8080'
curl -HHost:hbchen.com 'http://localhost:8080' > app/index.html

docker exec -it php-app sh

# php-fpm master pid
ps -ax | grep php-fpm

# php-fpm 关闭：
kill -INT {master pid}
# php-fpm 重启：
kill -USR2 {master pid}
```

## K8S

> 依赖`k8s`环境已有`ingress` [nginx ingress](https://github.com/nginxinc/kubernetes-ingress)

[部署yaml](/k8s)

```bash
curl -HHost:hbchen.com 'http://192.168.39.147:32134'

kubectl config set-context --current --namespace=app-ns

kubectl logs php-app-6d96948494-bgtpj php-app

kubectl exec -it php-app-6d96948494-bgtpj -c nginx -- /bin/sh

kubectl describe pod php-app-6d96948494-bgtpj

```

## 代码部署
 
- [ ] 镜像发布
- [ ] pod拉取
    - initContainers
- [x] node拉取
    - [DaemonSet](/k8s/git-sync-daemonset.yaml)
    - [git-sync](#git-sync)
- [ ] 共享存储
    - StatefulSet

### git-sync

[giy-sync ssh](https://github.com/kubernetes/git-sync/blob/master/docs/ssh.md)

**私有仓库-SSH秘钥**
> 生成秘钥时不使用密码
```bash
ssh-keyscan github.com > /tmp/known_hosts

kubectl create secret generic git-creds \
--from-file=ssh=$HOME/.ssh/id_rsa \
--from-file=known_hosts=/tmp/known_hosts -n app-ns
```

**git-sync参数说明**
```bash
Usage of /git-sync:
  -alsologtostderr
    	log to standard error as well as files
  -branch string
    	the git branch to check out (default "master")
  -change-permissions int
    	the file permissions to apply to the checked-out files
  -cookie-file
    	use git cookiefile
  -depth int
    	use a shallow clone with a history truncated to the specified number of commits
  -dest string
    	the name at which to publish the checked-out files under --root (defaults to leaf dir of --repo)
  -git string
    	the git command to run (subject to PATH search) (default "git")
  -http-bind string
    	the bind address (including port) for git-sync's HTTP endpoint
  -http-metrics
    	enable metrics on git-sync's HTTP endpoint (default true)
  -http-pprof
    	enable the pprof debug endpoints on git-sync's HTTP endpoint
  -log_backtrace_at value
    	when logging hits line file:N, emit a stack trace
  -log_dir string
    	If non-empty, write log files in this directory
  -logtostderr
    	log to standard error instead of files
  -max-sync-failures int
    	the number of consecutive failures allowed before aborting (the first pull must succeed, -1 disables aborting for any number of failures after the initial sync)
  -one-time
    	exit after the initial checkout
  -password string
    	the password to use
  -repo string
    	the git repository to clone
  -rev string
    	the git revision (tag or hash) to check out (default "HEAD")
  -root string
    	the root directory for git operations (default "/tmp/git")
  -ssh
    	use SSH for git operations
  -ssh-key-file string
    	the ssh key to use (default "/etc/git-secret/ssh")
  -ssh-known-hosts
    	enable SSH known_hosts verification (default true)
  -ssh-known-hosts-file string
    	the known hosts file to use (default "/etc/git-secret/known_hosts")
  -stderrthreshold value
    	logs at or above this threshold go to stderr
  -timeout int
    	the max number of seconds for a complete sync (default 120)
  -username string
    	the username to use
  -v value
    	log level for V logs
  -vmodule value
    	comma-separated list of pattern=N settings for file-filtered logging
  -wait float
    	the number of seconds between syncs
  -webhook-backoff duration
    	if a webhook call fails (dependant on webhook-success-status) this defines how much time to wait before retrying the call (default 3s)
  -webhook-method string
    	the method for the webook to send with (default "POST")
  -webhook-success-status int
    	the status code which indicates a successful webhook call. A value of -1 disables success checks to make webhooks fire-and-forget (default 200)
  -webhook-timeout duration
    	the timeout used when communicating with the webhook target (default 1s)
  -webhook-url string
    	the URL for the webook to send to. Default is "" which disables the webook.
```

#### PULL代码时404
```bash
➜  yypapa_ios git:(master) ✗ curl -HHost:hbchen.com 'http://121.41.19.167'
No input file specified.
➜  yypapa_ios git:(master) ✗ curl -HHost:hbchen.com 'http://121.41.19.167'
No input file specified.
➜  yypapa_ios git:(master) ✗ curl -HHost:hbchen.com 'http://121.41.19.167'
No input file specified.
```

```bash
127.0.0.1 -  27/Sep/2019:06:24:43 +0000 "GET /index.php" 404
[27-Sep-2019 06:24:43] WARNING: [pool www] child 7 said into stderr: "ERROR: Unable to open primary script: /var/www/src/k8s-php/app/index.php (No such file or directory)"
127.0.0.1 -  27/Sep/2019:06:24:48 +0000 "GET /index.php" 404
[27-Sep-2019 06:24:48] WARNING: [pool www] child 6 said into stderr: "ERROR: Unable to open primary script: /var/www/src/k8s-php/app/index.php (No such file or directory)"
127.0.0.1 -  27/Sep/2019:06:24:53 +0000 "GET /index.php" 404
[27-Sep-2019 06:24:53] WARNING: [pool www] child 7 said into stderr: "ERROR: Unable to open primary script: /var/www/src/k8s-php/app/index.php (No such file or directory)"
```