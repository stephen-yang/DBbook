# DBbook

生成 Markdown 格式的数据库表结构文档

# Demo

最终生成的文档格式如下所示:

> ```
> # table1
> 
> ## 基础信息
> 
> | 数据库      | 表名   | 表引擎  | 创建时间             | 排序规则         | 表注释 |
> |------------|--------|--------|---------------------|-----------------|-----|
> | mydatabase | table1 | InnoDB | 2017-08-11 02:16:27 | utf8_general_ci | 表注释 |
> 
> ## 字段信息
> 
> | 字段    | 类型     | 索引 | 默认值| 是否为空 | 注释     |
> |---------|---------|-----|-------|---------|---------|
> | id      | int(11) | PRI |       | NO      | 字段注释1 |
> | column1 | int(11) |     |       | NO      | 字段注释2 |
> | column2 | int(11) |     |       | NO      | 字段注释3 |
> | column3 | int(11) |     |       | NO      | 字段注释4 |
> ```


# 目录结构

```
|-  index.php              // 主程序
|-  dbbook                  // 文档存放目录
|-  |-  README.md           // Gitbook Readme 文件
|-  |-  SUMMARY.md          // Gitbook 目录文件
|   |-  database_name       // 用数据库名命名的文件夹
|       |-  table1.md       // 表结构文档
|       |-  table2.md
|       |-  table3.md
|       |-  table4.md
|       |-  table5.md
```

# 使用方法

0. 生成配置文件
   ```bash
   cp config.php.example config.php
   ```

1. 打开 `config.php`, 填入你的数据库信息。

    ```
    $dbhost = '120.0.0.1';
    $dbname = 'username';
    $dbpass = 'password';
    $dbdatabase = 'database';
    ```

1. 运行脚本

    ```bash
    chmod +x build.sh && ./build.sh
    ```

1. Enjoy!
    ```
    http://localhost:4000
    ```

# TODO

- [ ] ~改造成纯 JS 项目~
- [ ] ~PHP 替换成 Node.js~
- [ ] PHP 替换成 Golang, Node.js 是什么？Golang 拯救世界！
- [ ] ~npm run dev && npm run build~
- [ ] Dockerfile
