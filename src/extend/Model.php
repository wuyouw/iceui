<?php
/**
 * User: Yirius
 * Date: 2018/1/3
 * Time: 14:40
 */

namespace icesui\extend;


use traits\controller\Jump;

class Model extends \think\Model
{
    use Jump;
    protected $autoWriteTimestamp = 'datetime';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected $autoTimeField = [''];
    protected $disabled_field = [];
    protected $disabled_id = [];

    public function AutoTable($field = "*", $where = [], $with = [], $ordersort = "id", $group = "", $where_extra = ""){

        //如果有连边查询的时候就默认吧第一个表设置成a
//        if(!empty($with)){
//            $this->alias('a')->join($with);
//        }

        //开始接受ajax过来的bootstrap-table
        $order = input("post.order", "desc");
        $limit = input("post.length", 10);
        $offset = input("post.start", 0)/$limit + 1;
        //判断sort
        $post_sort = input("post.sort", $ordersort);
        if(!empty($with)){
            if(strpos($post_sort, ".") > -1)
                $sort = $post_sort;
            else{
                /*$sort_table = [
                    "phone" => "a.phone",
                    "realname" => "b.realname"
                ]*/
                if(!empty($sort_table) && !empty($sort_for_table[$post_sort])){
                    $sort = $sort_for_table[$post_sort];
                }else{
                    $sort = $post_sort;
                }
            }
        }
        else
            $sort = $post_sort;

        if($searchtext = input("post.search", 0)){
            if(!empty($search_fields))
                $where[$search_fields] = array("like","%".$searchtext."%");
        }

        if(!empty($with)){
            $list = $this->alias('a')->join($with)->where($where)->where($where_extra)->field($field)->order($sort." ".$order)->page($offset,$limit)->group($group)->select();
        }else{
            $list = $this->where($where)->where($where_extra)->field($field)->order($sort." ".$order)->page($offset,$limit)
                ->group($group)->select();
        }
        if(!empty($with)){
            $this->alias('a')->join($with);
        }
        if(empty($group)){
            $count = $this->where($where)->where($where_extra)->count();
        }else{
            $count = $this->where($where)->where($where_extra)->group($group_by_key)->count();
        }

        //start
        $return = [
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $list
        ];
        return $return;
    }

    public function AutoSave($add, $where = [], $scene = '', $returnmsg = '', $pk = '', $success = ''){
        $pk = empty($pk)?$this->getPk():$pk;//获取主键
        if(is_array($pk))
            $pk = $pk[0];
        //获取到pk的值
        $pk_value = input("param.".$pk, 0);//获取get或者post过来的主键值
        if($pk_value === 0 && isset($add[$pk]))
            $pk_value = $add[$pk];
        elseif($pk_value > 0)
            $add[$pk] = $pk_value;
        //如果where不存在, 就不判定
        if($where == []){
            if(empty($pk_value))
                $where = [];
            else
                $where = [$pk => $pk_value];
        }
        //如果where不为空, 吧add里面的pk置为空
        if(!empty($where) && $where[$pk] == 0){
            $where = [];
            unset($add[$pk]);
        }
        //自动判断是新增还是修改
        if(empty($scene))
            $auto_name = false;
        else
            $auto_name = $this->name.".".$scene;

        $result = $this->save($add, $where);//->auto($auto_name)
        if($result){
            if($success instanceof \Closure) {
                // 执行闭包
                call_user_func_array($success, []);
            }
            $this->success(empty($returnmsg)?"提交成功":str_replace(["{id}", ],[$result],$returnmsg), null, [
                'id' => $result
            ]);
        }
        else{
            if($error = $this->getError()){
                $this->error($error);
            }else{
                $this->error("您尚未进行任何修改,提交无效");
            }
        }
    }

    /**
     * @title 自动删除功能
     * @description 自动删除功能
     * @createtime: 2018/1/25 16:40
     * @param array $ids
     * @param array $notDelete
     * @param string $pkField
     * @return \think\Exception
     */
    public function AutoDelete($notDelete = [], $ids = [], $pkField = "id"){
        if(empty($ids)){
            $ids = input('param.')['ids'];
            if(!empty($ids))
                $ids = explode(",", $ids);
        }
        $errorDelete = [];
        foreach($ids as $i => $v){
            if(!in_array($v, $notDelete)){
                $flag = $this->where($pkField, $v)->delete();
                if(!$flag)
                    $errorDelete[] = $v;
            }
        }
        if(empty($errorDelete))
            $this->success('提交成功');
        else
            $this->error(join(",", $errorDelete)."删除失败");
    }
}
