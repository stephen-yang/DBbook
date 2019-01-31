<?php

$configs = include './config.php';

function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) {
        return true;
    }
    if (!mkdirs(dirname($dir), $mode)) {
        return false;
    }
    return @mkdir($dir, $mode);
}

$docPathPrefix = './dbbook/';

mkdirs($docPathPrefix);

// gitbook SUMMARY.md 标题
file_put_contents($docPathPrefix . 'SUMMARY.md', '# Summary' . PHP_EOL . PHP_EOL);

// gitbook README.md
file_put_contents($docPathPrefix . 'README.md', '# 数据库文档' . PHP_EOL . PHP_EOL);
file_put_contents($docPathPrefix . 'README.md', '更新时间: ' . date('Y-m-d H:i:s', time()) . PHP_EOL . PHP_EOL,
    FILE_APPEND);

// book.json
$bookJson = '{
  "plugins": [
    "-sharing",
    "-lunr",
    "-search",
    "search-pro",
    "back-to-top-button",
    "expandable-chapters"
  ]
}';

file_put_contents($docPathPrefix . 'book.json', $bookJson);

// 重新设置边栏最大高度 覆盖 gitbook-plugin-expandable-chapters/book/expandable-chapters.css
$websiteCss = <<< EOF
.book .book-summary .chapter.expanded > .articles {
    max-height: 99999px;
}
EOF;

$websiteCssPath = $docPathPrefix . 'styles/';

mkdir($websiteCssPath);

file_put_contents($websiteCssPath . 'website.css', $websiteCss);

foreach ($configs as $config) {
    $host = $config['host'];
    $port = $config['port'];
    $username = $config['username'];
    $password = $config['password'];
    $database = $config['database'];
    $docPath = $docPathPrefix . $database . '/';

    echo PHP_EOL . "数据库 {$database}" . PHP_EOL;

    // 创建文件夹
    mkdirs($docPath);

    $connect = new mysqli($host, $username, $password, $database, $port);
    $connect->set_charset("utf8mb4");

    // 获取表基础信息
    $sql = 'SELECT `table_schema`, `table_name`, `engine`, `table_collation`, `table_comment` FROM `information_schema`.`tables` WHERE `table_schema` = "' . $database . '"';

    $result = $connect->query($sql);
    $tableBaseData = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if (isset($row['table_name'])) {
            $tableBaseData[$row['table_name']] = $row;
        } else {
            $tableBaseData[$row['TABLE_NAME']] = $row;
        }
    }

    $len = count($tableBaseData);

    // 获取表字段信息
    $tableColumnData = [];
    $i = 1;
    foreach ($tableBaseData as $table => $val) {
        echo '获取表信息' . $i . '/' . $len . "\n";
        if (isset($val['table_name'])) {
            $tableName = $val['table_name'];
        } else {
            $tableName = $val['TABLE_NAME'];
        }
        $sql = 'SELECT `column_name`, `column_type`, `column_key`, `column_default`, `column_comment` FROM `information_schema`.`columns` WHERE `table_schema` = "' . $database . '" AND `table_name` = "' . $tableName . '"';

        $result = $connect->query($sql);
        while ($row = mysqli_fetch_assoc($result)) {
            if (isset($val['table_name'])) {
                $tableColumnData[$val['table_name']][] = $row;
            } else {
                $tableColumnData[$val['TABLE_NAME']][] = $row;
            }
        }
        $i++;
    }

    // 处理特殊字符
    $tableColumnDataJson = json_encode($tableColumnData);

    $tableColumnDataJson = str_replace('|', '｜', $tableColumnDataJson);
    $tableColumnDataJson = str_replace('---', '－－－', $tableColumnDataJson);
    $tableColumnDataJson = str_replace('\r\n', '<br>', $tableColumnDataJson);
    $tableColumnDataJson = str_replace('\n', '<br>', $tableColumnDataJson);

    $tableColumnData = json_decode($tableColumnDataJson, true);

    // 生成 markdown
    $tableName = '';
    $baseTitle = '## 基础信息' . PHP_EOL;
    $columnTitle = '## 字段信息' . PHP_EOL;

    $baseThArray = [
        'TABLE_SCHEMA' => 'database',
        'TABLE_NAME' => 'table',
        'ENGINE' => 'engine',
        'TABLE_COLLATION' => 'collation',
        'TABLE_COMMENT' => 'comment',
        //兼容字段名大小写
        'table_schema' => 'database',
        'table_name' => 'table',
        'engine' => 'engine',
        'table_collation' => 'collation',
        'table_comment' => 'comment',
    ];

    $columnThArray = [
        'COLUMN_NAME' => 'column',
        'COLUMN_TYPE' => 'type',
        'COLUMN_KEY' => 'key',
        'COLUMN_DEFAULT' => 'default',
        'COLUMN_COMMENT' => 'comment',
        'column_name' => 'column',
        'column_type' => 'type',
        'column_key' => 'key',
        'column_default' => 'default',
        'column_comment' => 'comment',
    ];

    echo "准备生成 Markdown 文档..." . "\n";

    // 表基础信息
    $partTitleCounter = true;
    foreach ($tableBaseData as $table => $tableBaseInfo) {
        $baseThStr = '';
        $baseThSplit = '';
        $baseTdStr = '';
        foreach ($tableBaseInfo as $cKey => $cValue) {
            $baseThStr .= '|' . $baseThArray[$cKey];
            $baseThSplit .= '|---';
            $baseTdStr .= '|' . $cValue;
        }

        // gitbook SUMMARY.md 目录
        //一级目录
        $chapterTitle = '* [' . $database . '](' . $database . '/' . $table . '.md)';
        //二级目录
        $subChapterTitle = "\t" . '* [' . $table . '](' . $database . '/' . $table . '.md)';

        if ($partTitleCounter) {
            //打印一级目录
            file_put_contents($docPathPrefix . 'SUMMARY.md', $chapterTitle . PHP_EOL, FILE_APPEND);
            $partTitleCounter = false;
        }

        //打印二级目录
        file_put_contents($docPathPrefix . 'SUMMARY.md', $subChapterTitle . PHP_EOL, FILE_APPEND);

        //打印基础信息
        file_put_contents($docPath . $table . '.md', "# {$database}.{$table}" . PHP_EOL . PHP_EOL);
        file_put_contents($docPath . $table . '.md', $baseTitle . PHP_EOL, FILE_APPEND);
        file_put_contents($docPath . $table . '.md', $baseThStr . '|' . PHP_EOL, FILE_APPEND);
        file_put_contents($docPath . $table . '.md', $baseThSplit . '|' . PHP_EOL, FILE_APPEND);
        file_put_contents($docPath . $table . '.md', $baseTdStr . '|' . PHP_EOL . PHP_EOL, FILE_APPEND);
    }

    // 表字段信息
    $j = 1;
    foreach ($tableColumnData as $table => $tableBaseInfo) {
        echo '生成 Markdown 文档 ' . $j . '/' . $len . "\n";
        $columnTdStr = '';
        foreach ($tableBaseInfo as $iKey => $iValue) {
            $columnThStr = '';
            $columnThSplit = '';
            foreach ($iValue as $jKey => $jValue) {
                $columnThStr .= '|' . $columnThArray[$jKey];
                $columnThSplit .= '|---';
                $columnTdStr .= '|' . $jValue;
            }
            $columnTdStr .= '|' . PHP_EOL;
        }

        file_put_contents($docPath . $table . '.md', $columnTitle . PHP_EOL, FILE_APPEND);
        file_put_contents($docPath . $table . '.md', $columnThStr . '|' . PHP_EOL, FILE_APPEND);
        file_put_contents($docPath . $table . '.md', $columnThSplit . '|' . PHP_EOL, FILE_APPEND);
        file_put_contents($docPath . $table . '.md', $columnTdStr . PHP_EOL, FILE_APPEND);
        $j++;
    }
}
