<?php
/**
 * Created by IntelliJ IDEA.
 * User: Steven
 * Date: 2019/9/8
 * Time: 4:57 PM
 */

switch ($_REQUEST['s']) {
    case '/healthz':
        {
            echo 'ok';
            break;
        }
    case '/info':
        {
            phpinfo();
            break;
        }
    case '/':
        {
            echo 'Hostname:' . gethostname() . PHP_EOL;
            echo 'PHP version:' . phpversion() . PHP_EOL;
            echo 'Redis:' . extension_loaded('redis') . PHP_EOL;
            echo 'MySQL:' . function_exists('mysqli_connect') . PHP_EOL;
            break;
        }
    default:
        {
            http_response_code(404);
        }
}