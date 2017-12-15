<?php

$config = include './config.php';

$host = $config['host'];
$port = $config['port'];
$username = $config['username'];
$password = $config['password'];
$database = $config['database'];
$docPathPrefix = './dbbook/';
$docPath = $docPathPrefix . $database . '/';

// 创建文件夹
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

mkdirs($docPath);

$connect = new mysqli($host, $username, $password, $database, $port);

// 获取表基础信息
$sql = 'SELECT `table_schema`, `table_name`, `engine`, `create_time`, `table_collation`, `table_comment` FROM `information_schema`.`tables` WHERE `table_schema` = "' . $database . '"';

$result = $connect->query($sql);
while ($row = mysqli_fetch_assoc($result)) {
    $tableBaseData[$row['table_name']] = $row;
}

//file_put_contents('./table_base_data.json',json_encode($tableBaseData));

$len = count($tableBaseData);

// 获取表字段信息
foreach ($tableBaseData as $key => $val) {
    static $i = 1;
    echo '获取表信息' . $i . '/' . $len . "\n";
    $sql = 'SELECT `column_name`, `column_type`, `column_key`, `column_default`, `is_nullable`, `column_comment` FROM `information_schema`.`columns` WHERE `table_schema` = "' . $database . '" AND `table_name` = "' . $val['table_name'] . '"';
    $result = $connect->query($sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $tableColumnData[$val['table_name']][] = $row;
    }
    $i++;
}

// 处理特殊字符
$tableColumnDataJson = json_encode($tableColumnData);

$tableColumnDataJson = str_replace('|', '｜', $tableColumnDataJson);
$tableColumnDataJson = str_replace('---', '－－－', $tableColumnDataJson);
$tableColumnDataJson = str_replace('\r\n', '<br>', $tableColumnDataJson);
$tableColumnDataJson = str_replace('\n', '<br>', $tableColumnDataJson);

$tableColumnData = json_decode($tableColumnDataJson);

// 生成 markdown
$tableName = '';
$baseTitle = '## 基础信息' . PHP_EOL;
$columnTitle = '## 字段信息' . PHP_EOL;

$baseThArray = [
    'table_schema' => '数据库',
    'table_name' => '表名',
    'engine' => '表引擎',
    'create_time' => '创建时间',
    'table_collation' => '排序规则',
    'table_comment' => '表注释',
];

$columnThArray = [
    'column_name' => '字段',
    'column_type' => '类型',
    'column_key' => '索引',
    'column_default' => '默认值',
    'is_nullable' => '是否为空',
    'column_comment' => '注释',
];

// gitbook README.md
file_put_contents($docPathPrefix . 'README.md', '# 数据库文档' . PHP_EOL . PHP_EOL);
file_put_contents($docPathPrefix . 'README.md', '更新时间: ' . date('Y-m-d H:i:s', time()) . PHP_EOL . PHP_EOL, FILE_APPEND);

// book.json
$bookJson = '{
    "plugins": [
        "-sharing",
        "-lunr",
        "-search",
        "search-pro",
        "back-to-top-button"
    ]
}';

file_put_contents($docPathPrefix . 'book.json', $bookJson);

// gitbook SUMMARY.md 标题
file_put_contents($docPathPrefix . 'SUMMARY.md', '# Summary' . PHP_EOL . PHP_EOL);

echo "准备生成 Markdown 文档..." . "\n";

// 表基础信息
foreach ($tableBaseData as $key => $value) {
    $baseThStr = '';
    $baseThSplit = '';
    $baseTdStr = '';
    $tableName = '# ' . $key . PHP_EOL;
    foreach ($value as $cKey => $cValue) {
        $baseThStr .= '|' . $baseThArray[$cKey];
        $baseThSplit .= '|---';
        $baseTdStr .= '|' . $cValue;
    }

    // gitbook SUMMARY.md 目录
    $summary = '* [' . $key . '](' . $database . '/' . $key . '.md)';
    file_put_contents($docPathPrefix . 'SUMMARY.md', $summary . PHP_EOL, FILE_APPEND);

    file_put_contents($docPath . $key . '.md', $tableName . PHP_EOL);
    file_put_contents($docPath . $key . '.md', $baseTitle . PHP_EOL, FILE_APPEND);
    file_put_contents($docPath . $key . '.md', $baseThStr . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($docPath . $key . '.md', $baseThSplit . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($docPath . $key . '.md', $baseTdStr . '|' . PHP_EOL . PHP_EOL, FILE_APPEND);
}

// 表字段信息
foreach ($tableColumnData as $key => $value) {
    static $j = 1;
    echo '生成 Markdown 文档 ' . $j . '/' . $len . "\n";
    $columnTdStr = '';
    foreach ($value as $iKey => $iValue) {
        $columnThStr = '';
        $columnThSplit = '';
        foreach ($iValue as $jKey => $jValue) {
            $columnThStr .= '|' . $columnThArray[$jKey];
            $columnThSplit .= '|---';
            $columnTdStr .= '|' . $jValue;
        }
        $columnTdStr .= '|' . PHP_EOL;
    }
    file_put_contents($docPath . $key . '.md', $columnTitle . PHP_EOL, FILE_APPEND);
    file_put_contents($docPath . $key . '.md', $columnThStr . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($docPath . $key . '.md', $columnThSplit . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($docPath . $key . '.md', $columnTdStr . PHP_EOL, FILE_APPEND);
    $j++;
}