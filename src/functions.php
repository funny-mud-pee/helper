<?php
/**
 * 给定的数组中提取数据
 * @param array $param
 * @param array $fields
 * @param bool $keyValue 默认返回key-value格式的数组，否则仅返回由参数值组成的数组
 * @return array
 */
function extract_from_param(array $param, array $fields, bool $keyValue = true): array
{
    if (empty($param)) {
        return [];
    }
    if (empty($fields)) {
        return $param;
    }
    $p = [];
    $i = 0;
    foreach ($fields as $field) {
        if (!is_array($field)) {
            $default = null;
        } else {
            $field = $field[0];
            $default = $field[1] ?? null;
        }
        $p[false === $keyValue ? $i++ : $field] = $param[$field] ?? $default;
    }
    return $p;
}

/**
 * 从一个key-value的数组中，弹出一个或多个value，并从数组删除已弹出的键值
 * @param array $param
 * @param $key
 * @return array|mixed|null
 */
function eject(array &$param, $key)
{
    if (empty($key)) {
        return null;
    }
    if (is_string($key)) {
        if (array_key_exists($key, $param)) {
            $value = $param[$key];
            unset($param[$key]);
            return $value;
        }
        return null;
    } elseif (is_array($key)) {
        $array = [];
        foreach ($key as $keyName) {
            if (array_key_exists($keyName, $param)) {
                array_push($array, $param[$keyName]);
                unset($param[$keyName]);
            } else {
                array_push($array, null);
            }
        }
        return count($array) <= 1 ? end($array) : $array;
    } else {
        return null;
    }
}

/**
 * @param mixed ...$args 索引数组
 * @return array
 */
function make_array(...$args): array
{
    $result = [];
    // 遍历数组
    $i = 0;
    do {
        if (!isset($args[$i]) || !isset($args[$i + 1])) {
            break;
        }
        // 第n个元素
        $param = $args[$i];
        if (is_object($param) && method_exists($param, 'toArray')) {
            $param = $param->toArray();
        }
        // 第n+1个元素
        $field = $args[$i + 1];

        if (is_string($param)) {
            // 若n是字符串,则作为键,而n+1作为键值
            $result[$param] = $field;
        } elseif (is_array($param) && true === $field) {
            // 若n是数组,n+1 === true,则将n与result合并
            $result = array_merge($result, $param);
        } elseif (is_array($field)) {
            // 若n+1是数组,形如[0=>'key1','key2'=>'key3',1=>['key4','key4default_value'],['key5'=>'key5_alias','key5default_value']]
            foreach ($field as $key => $value) {
                // 键是数字时
                if (is_numeric($key)) {
                    if (is_array($value)) {
                        // 如果value为数组,不管有多少个元素,都会将第一个元素作为field,
                        // 最后一个元素作为field的默认值
                        $default = array_pop($value);
                        if (isset($value[0])) {
                            // value 形如 ['key4','key4default_value']
                            $k = $value[0];
                            $result[$k] = $param[$k] ?? $default;
                        } else {
                            // value 形如 ['key5'=>'key5_alias','key5default_value']
                            $k = array_key_first($value);
                            $alias = $value[$k];
                            $result[$alias] = $param[$k] ?? $default;
                        }
                    } elseif (strpos($value, '.')) {
                        // value为点分字符串 ['key5.key5_1','key5default_value']
                        // 目前就做到一级点分吧,不搞太复杂
                        list($k, $sk) = explode('.', $value);
                        $result[$sk] = $param[$k][$sk] ?? null;
                    } else {
                        $result[$value] = $param[$value] ?? null;
                    }
                } else {
                    if (strpos($key, '.')) {
                        list($k, $sk) = explode('.', $key);
                        $result[$value] = $param[$k][$sk] ?? null;
                    } else {
                        $result[$value] = $param[$key] ?? null;
                    }
                }
            }
        } else {
            if (strpos($field, '.')) {
                list($k, $sk) = explode('.', $field);
                $result[$sk] = $param[$k][$sk] ?? null;
            } else {
                $result[$field] = $param[$field] ?? null;
            }
        }
        $i += 2;
    } while (true);
    return $result;
}