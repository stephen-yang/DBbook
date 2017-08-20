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
|-  dbbook.php              // 主程序
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

1. 打开 `dbbook.php`, 填入你的数据库信息

    ```
    $dbhost = "120.0.0.1";
    $dbname = "username";
    $dbpass = "password";
    $dbdatabase = "database";
    ```

1. 运行脚本

    ```
    php -f ./dbbook.php
    ```

1. 生成的文档会被存放在 `dbbook` 目录中, 并预留了 Gitbook 配置信息, 可以方便地制作 Gitbook 电子书.
