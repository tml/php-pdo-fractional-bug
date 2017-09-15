## Description
A docker-compose bundle to demonstrate [bug #67122](https://bugs.php.net/bug.php?id=67122) exists in the latest stable PHP 7.1.

> When the PDO attribute ATTR_EMULATE_PREPARES is false, any microseconds are dropped from timestamp columns.

## Instructions
This bundle includes a script to read a fractional timestamp from the database: The assertions in
index.php are expected to pass and the string "No errors" should be printed at the end of
execution. The bug exists if "Fractional seconds missing" is printed.


```
docker-compose build
docker-compose run --rm app -f /opt/project/index.php
```

This is caused by fractional seconds being truncated when PDO::ATTR_EMULATE_PREPARES is false:

```
Expected result:
----------------
Emulated prepares 1:
Array
(
    [name] => php_version
    [value] => 7.1.9
    [created_at] => 2017-09-15 16:27:14.061754
    [updated_at] => 2017-09-15 16:27:14.061754
)

Emulated prepares 0:
Array
(
    [name] => php_version
    [value] => 7.1.9
    [created_at] => 2017-09-15 16:27:14.521487
    [updated_at] => 2017-09-15 16:27:14.521487
)

No errors

Actual result:
--------------
Emulated prepares 1:
Array
(
    [name] => php_version
    [value] => 7.1.9
    [created_at] => 2017-09-15 16:27:14.061754
    [updated_at] => 2017-09-15 16:27:14.061754
)

Emulated prepares 0:
Array
(
    [name] => php_version
    [value] => 7.1.9
    [created_at] => 2017-09-15 16:27:14
    [updated_at] => 2017-09-15 16:27:14
)

Warning: assert(): assert(preg_match('/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}\\.\\d{6}$/', $result['updated_at'])) failed in /opt/project/index.php on line 68
Fractional seconds missing
```



## Requirements
- docker-compose with support for version >= 2.0
