<?php
$dbhost = "192.168.170.24";
$dbname = "devpadmin";
$dbpass = "1234568";
$dbdatabase = "dadaabc";
$dbdoc_path_perfix = './dbbook/';
$dbdoc_path = $dbdoc_path_perfix . $dbdatabase . '/';

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

mkdirs($dbdoc_path);

$db_connect = new mysqli($dbhost, $dbname, $dbpass, $dbdatabase);

// 获取表基础信息
$sql = 'SELECT TABLE_SCHEMA, TABLE_NAME, `ENGINE`, CREATE_TIME, TABLE_COLLATION,TABLE_COMMENT FROM information_schema.`TABLES` WHERE TABLE_SCHEMA = "' . $dbdatabase . '"';

$result = $db_connect->query($sql);
while ($row = mysqli_fetch_assoc($result)) {
    $table_base_data[$row['TABLE_NAME']] = $row;
}

//file_put_contents('./table_base_data.json',json_encode($table_base_data));

$len = count($table_base_data);

// 获取表字段信息
foreach ($table_base_data as $key => $val) {
    static $i = 1;
    echo '获取表信息' . $i . '/' . $len . "\n";
    $sql = 'SELECT COLUMN_NAME,COLUMN_TYPE,COLUMN_KEY,COLUMN_DEFAULT,IS_NULLABLE,COLUMN_COMMENT FROM INFORMATION_SCHEMA. COLUMNS WHERE `TABLE_SCHEMA` = "' . $dbdatabase . '" AND `TABLE_NAME` = "' . $val['TABLE_NAME'] . '"';
    $result = $db_connect->query($sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $table_column_data[$val['TABLE_NAME']][] = $row;
    }
    $i++;
}

// 处理特殊字符
$table_column_data_json = json_encode($table_column_data);

$table_column_data_json = str_replace('|', '｜', $table_column_data_json);
$table_column_data_json = str_replace('---', '－－－', $table_column_data_json);
$table_column_data_json = str_replace('\r\n', '<br>', $table_column_data_json);
$table_column_data_json = str_replace('\n', '<br>', $table_column_data_json);

$table_column_data = json_decode($table_column_data_json);

// 生成 markdown
$table_name = '';
$base_title = '## 基础信息' . PHP_EOL;
$column_title = '## 字段信息' . PHP_EOL;

$base_th_array = [
    'TABLE_SCHEMA' => '数据库',
    'TABLE_NAME' => '表名',
    'ENGINE' => '表引擎',
    'CREATE_TIME' => '创建时间',
    'TABLE_COLLATION' => '排序规则',
    'TABLE_COMMENT' => '表注释',
];

$column_th_array = [
    'COLUMN_NAME' => '字段',
    'COLUMN_TYPE' => '类型',
    'COLUMN_KEY' => '索引',
    'COLUMN_DEFAULT' => '默认值',
    'IS_NULLABLE' => '是否为空',
    'COLUMN_COMMENT' => '注释',
];

// gitbook README.md
file_put_contents($dbdoc_path_perfix . 'README.md', '# 数据库文档' . PHP_EOL . PHP_EOL);
file_put_contents($dbdoc_path_perfix . 'README.md', '更新时间: ' . date('Y-m-d H:i:s', time()) . PHP_EOL . PHP_EOL, FILE_APPEND);

// book.json
$book_json = '{
    "plugins": [
        "-sharing",
        "-lunr",
        "-search",
        "search-pro"
    ]
}';

file_put_contents($dbdoc_path_perfix . 'book.json', $book_json);

// gitbook SUMMARY.md 标题
file_put_contents($dbdoc_path_perfix . 'SUMMARY.md', '# Summary' . PHP_EOL . PHP_EOL);

echo "准备生成 markdown 文档...";

// 表基础信息
foreach ($table_base_data as $key => $value) {
    $base_th_str = '';
    $base_th_split = '';
    $base_td_str = '';
    $table_name = '# ' . $key . PHP_EOL;
    foreach ($value as $c_key => $c_value) {
        $base_th_str .= '|' . $base_th_array[$c_key];
        $base_th_split .= '|---';
        $base_td_str .= '|' . $c_value;
    }

    // gitbook SUMMARY.md 目录
    $summary = '* [' . $key . '](' . $dbdatabase . '/' . $key . '.md)';
    file_put_contents($dbdoc_path_perfix . 'SUMMARY.md', $summary . PHP_EOL, FILE_APPEND);

    file_put_contents($dbdoc_path . $key . '.md', $table_name . PHP_EOL);
    file_put_contents($dbdoc_path . $key . '.md', $base_title . PHP_EOL, FILE_APPEND);
    file_put_contents($dbdoc_path . $key . '.md', $base_th_str . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($dbdoc_path . $key . '.md', $base_th_split . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($dbdoc_path . $key . '.md', $base_td_str . '|' . PHP_EOL . PHP_EOL, FILE_APPEND);
}

// 表字段信息
foreach ($table_column_data as $key => $value) {
    static $j = 1;
    echo '生成 markdown 文档 ' . $j . '/' . $len . "\n";
    $column_td_str = '';
    foreach ($value as $i_key => $i_value) {
        $column_th_str = '';
        $column_th_split = '';
        foreach ($i_value as $j_key => $j_value) {
            $column_th_str .= '|' . $column_th_array[$j_key];
            $column_th_split .= '|---';
            $column_td_str .= '|' . $j_value;
        }
        $column_td_str .= '|' . PHP_EOL;
    }
    file_put_contents($dbdoc_path . $key . '.md', $column_title . PHP_EOL, FILE_APPEND);
    file_put_contents($dbdoc_path . $key . '.md', $column_th_str . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($dbdoc_path . $key . '.md', $column_th_split . '|' . PHP_EOL, FILE_APPEND);
    file_put_contents($dbdoc_path . $key . '.md', $column_td_str . PHP_EOL, FILE_APPEND);
    file_put_contents($dbdoc_path . $key . '.md', '***' . PHP_EOL . PHP_EOL, FILE_APPEND);
    $j++;
}