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
$sql = 'SELECT TABLE_SCHEMA, TABLE_NAME, `ENGINE`, CREATE_TIME, TABLE_COLLATION,TABLE_COMMENT FROM information_schema.`TABLES` WHERE TABLE_SCHEMA = "' . $database . '"';

$result = $connect->query($sql);
while ($row = mysqli_fetch_assoc($result)) {
    $tableBaseData[$row['TABLE_NAME']] = $row;
}

//file_put_contents('./table_base_data.json',json_encode($tableBaseData));

$len = count($tableBaseData);

// 获取表字段信息
foreach ($tableBaseData as $key => $val) {
    static $i = 1;
    echo '获取表信息' . $i . '/' . $len . "\n";
    $sql = 'SELECT COLUMN_NAME,COLUMN_TYPE,COLUMN_KEY,COLUMN_DEFAULT,IS_NULLABLE,COLUMN_COMMENT FROM INFORMATION_SCHEMA. COLUMNS WHERE `TABLE_SCHEMA` = "' . $database . '" AND `TABLE_NAME` = "' . $val['TABLE_NAME'] . '"';
    $result = $connect->query($sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $tableColumnData[$val['TABLE_NAME']][] = $row;
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
    'TABLE_SCHEMA' => '数据库',
    'TABLE_NAME' => '表名',
    'ENGINE' => '表引擎',
    'CREATE_TIME' => '创建时间',
    'TABLE_COLLATION' => '排序规则',
    'TABLE_COMMENT' => '表注释',
];

$columnThArray = [
    'COLUMN_NAME' => '字段',
    'COLUMN_TYPE' => '类型',
    'COLUMN_KEY' => '索引',
    'COLUMN_DEFAULT' => '默认值',
    'IS_NULLABLE' => '是否为空',
    'COLUMN_COMMENT' => '注释',
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
        "search-pro"
    ]
}';

file_put_contents($docPathPrefix . 'book.json', $bookJson);

// gitbook SUMMARY.md 标题
file_put_contents($docPathPrefix . 'SUMMARY.md', '# Summary' . PHP_EOL . PHP_EOL);

echo "准备生成 markdown 文档...";

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
    echo '生成 markdown 文档 ' . $j . '/' . $len . "\n";
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
    file_put_contents($docPath . $key . '.md', '***' . PHP_EOL . PHP_EOL, FILE_APPEND);
    $j++;
}