<?php


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

/**
 * 漏桶算法
 */
class LeakyBucketController extends Controller
{
    //桶的容量
    private int $capacity = 10;
    //单位时间(秒)可处理的请求数
    private int $rate = 2;

    /** 漏桶算法限制请求速率
     * @return bool
     * @author Lv
     * @date 2023/5/25
     */
    public function index(): bool
    {
        //上一次请求的时间戳
        $last_timestamp = Cache::get('request_timestamp', time());
        //桶中剩余的请求数
        $request_nums = Cache::get('request_nums', 0);
        $current_timestamp = time();
        //先执行漏水，再判定是否超出桶容量
        $left_request_nums = max(0, $request_nums - ($current_timestamp - $last_timestamp) * $this->rate) + 1;
        if ($left_request_nums > $this->capacity) {
            return false;
        } else {
            Cache::put('request_timestamp', $current_timestamp);
            Cache::put('request_nums', $left_request_nums);
            return true;
        }
    }
}
