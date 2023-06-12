<?php
namespace App\Http\Controllers\Limit;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

/**
 * 令牌桶限制速率
 */
class TokenBucketController extends Controller
{
    //桶的容量
    private int $capacity = 150;
    //加入Token的速率(qps)
    private int $rate = 100;

    public function index(): bool
    {
        $last_timestamp = Cache::get('last_timestamp', time());
        $token_num = Cache::get('token_num', $this->capacity);
        $current_timestamp = time();
        //先计算投入令牌数，再消耗令牌
        $current_token_num = min(($current_timestamp - $last_timestamp) * $this->rate + $token_num, $this->capacity) ;
        if ($current_token_num < 1) {
            return false;
        } else {
            Cache::put('token_num', $current_token_num - 1);
            Cache::put('last_timestamp', $current_timestamp);
            return true;
        }
    }
}
