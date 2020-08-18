<?php
    // +----------------------------------------------------------------------
    // | builder
    // +----------------------------------------------------------------------
    // | Copyright (c) 2015-2019 http://www.yicmf.com, All rights reserved.
    // +----------------------------------------------------------------------
    // | Author: 微尘 <yicmf@qq.com>
    // +----------------------------------------------------------------------
    namespace yicmf\builder;

    use app\admin\model\Menu as MenuModel;
    use think\Db;
    use think\Exception;
    use think\exception\ValidateException;
    use think\facade\Lang;
    use think\Model;
    use think\facade\Url;

    class Edit extends Builder
    {
        private $_title;


        private $_keyList = [];

        // 提示信息
        private $_explaints = [];
        private $_templets = [];

        /**
         * @var Model|array
         */
        private $_data;

        private $_form_submit;
        private $_form_close;
        private $_form_reset;

        private $_reload = 'true';
        private $_mask = 'true';
        private $_savePostUrl;

        private $_group = [];

        private $_triggers = [];

        // 默认获取主键的字段
        protected $_default_pk = 'id';
        /**
         * 验证失败是否抛出异常
         * @var bool
         */
        protected $failException = false;

        /**
         * 是否批量验证
         * @var bool
         */
        protected $batchValidate = false;

        /**
         * 设置请求表单头
         * @param string $title
         * @return Edit
         */
        public function form($reload = true, $mask = true)
        {
            $this->_mask = $mask;
            $this->_reload = $reload;
            return $this;
        }

        /**
         * 设置标题.
         * @param string $title
         * @return $this
         */
        public function title($title)
        {
            $this->_title = $title;
            return $this;
        }

        /**
         * 页面下面的提示.
         * @param string|array $explain
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/13 7:53
         */
        public function explain($explain)
        {
            if (is_array($explain)) {
                $this->_explaints = array_merge($this->_explaints, $explain);
            } else {
                $this->_explaints[] = $explain;
            }
            return $this;
        }

        /**
         * 设置触发.
         * @param string|array $trigger 需要触发的表单项名，目前支持select（单选类型）、text、radio三种
         * @param string       $values 触发的值
         * @param string       $show 触发后要显示的表单项名，目前不支持普通联动、范围、拖动排序、静态文本
         * @return $this
         */
        public function setTrigger($trigger, $values = '', $show = '')
        {
            if (is_array($trigger)) {
                $this->_triggers = array_merge($this->_triggers, $trigger);
            } else {
                $this->_triggers[$trigger][] = ['value' => $values, 'show' => $show];
                //参与的所有字段合集
                //                if (isset($this->_triggers[$trigger]['field'])) {
                //                    $this->_triggers[$trigger]['field'] .= ',' . $show;
                //                } else {
                //                    $this->_triggers[$trigger]['field'] = $show;
                //                }
            }
            return $this;
        }

        /**
         * 直接显示html.
         * @param string $name
         * @param string $html
         * @param string $title
         * @param string $tips
         * @return $this
         */
        public function keyHtml($name, $html, $title, $tips = null)
        {
            return $this->key($name, $title, $tips, 'html', $html);
        }

        /**
         * 按照item级别显示
         * @param string $name
         * @param string $html
         * @param string $title
         * @param string $tips
         * @return $this
         */
        public function keyItem($name, $html)
        {
            return $this->key($name, $html, null, 'item');
        }

        /**
         * 隐藏表单.
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/12 11:31
         */
        public function keyHidden($name, $default = '')
        {
            return $this->key($name, null, null, 'hidden', [], $default);
        }


        /**
         * 只读文本.
         * @param string      $name
         * @param string      $title
         * @param string|null $tips
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/12 11:31
         */
        public function keyReadOnly($name, $title, $tips = null)
        {
            return $this->key($name, $title, $tips, 'readonly');
        }

        /**
         * 文本输入框.
         * @param string      $name
         * @param string      $title
         * @param string|null $tips
         * @param int         $size
         * @param array|null  $verify
         * @return $this
         */
        public function keyText($name, $title, $tips = null, $default = '', $verify = null)
        {
            return $this->key($name, $title, $tips, 'string', null, $default, $verify);
        }

        /**
         * 显示金额
         * @param string $field 键名
         * @param string $title 标题
         * @param bool   $sort 排序方式，默认是不参与排序
         * @param null   $width
         * @param array  $opt
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/3/28 13:32
         */
        public function keyRmb($field, $title, $tips = null, $default = null, $width = '', $style = '')
        {
            return $this->key($field, $title, $tips, 'rmb', null, $default, '');
        }

        /**
         * 备案信息
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @param int         $size
         * @param array|null  $verify
         * @return $this
         */
        public function keyIcp($field, $title, $tips = null, $default = '', $verify = null)
        {
            return $this->key($field, $title, $tips, 'icp', null, $default, $verify);
        }

        /**
         * 字符串
         * @param        $field
         * @param        $title
         * @param null   $tips
         * @param string $default
         * @param null   $verify
         * @return $this
         * @author 微尘 <yicmf@qq.com>
         * @datetime: 2020/5/29 21:36
         */
        public function keyTextInline($field, $title, $tips = null, $default = '', $verify = null)
        {
            return $this->key($field, $title, $tips, 'inline', null, $default, $verify);
        }

        /**
         * 备安全验证
         * @param      $field
         * @param      $title
         * @param null $tips
         * @param int  $wait_time
         * @return $this
         * @throws Exception
         * @author 微尘 <yicmf@qq.com>
         * @datetime: 2020/5/29 21:36
         */
        public function keySafeCheck($field, $title, $tips = null, $wait_time = 60)
        {
            $this->key($field, $title, $tips, 'safe_check', ['wait_time' => $wait_time]);
            return $this->keyTextInline('check_code', '验证码', '请输入收到的验证码', '', 'required');
        }

        /**
         * 数组输入框，内容以’,‘分隔
         * @param      $field
         * @param      $title
         * @param null $tips
         * @param int  $size
         * @param null $verify
         * @return $this
         * @throws Exception
         * @author 微尘 <yicmf@qq.com>
         * @datetime: 2020/5/29 21:37
         */
        public function keyArray($field, $title, $tips = null, $size = 30, $verify = null)
        {
            return $this->key($field, $title, $tips, 'string', null, $size, $verify);
        }

        /**
         * 文本输入框常用显示title.
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @param int         $size
         * @param array|null  $verify
         * @return $this
         */
        public function keyTitle($field = 'title', $title = '标题', $tips = null, $size = 30, $verify = null)
        {
            return $this->keyText($field, $title, $tips, null, $size, $verify);
        }

        /**
         * 地图选择（需安装高德插件）.
         * @param $title
         * @param $tips
         * @return $this
         */
        public function keyAmap($field, $title, $tips = null, $verify = null)
        {
            return $this->key($field, $title, $tips, 'amap', null, 30, $verify);
        }

        /**
         * 单选按钮.
         * @param string      $field
         * @param string      $title
         * @param array       $options
         * @param string|null $tips
         * @param array|null  $verify
         * @return $this
         */
        public function keyRadio($field, $title, $options, $tips = null, $default = 0, $verify = null, $disabled = null)
        {
            return $this->key($field, $title, $tips, 'radio', $options, $default, $verify, 30, $disabled);
        }

        /**
         * 是与否的单选.
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @param array|null  $verify
         * @return $this
         */
        public function keyBool($field, $title, $tips = null, $default = 0, $verify = null, $disabled = null)
        {
            $options = [
                1 => '是',
                0 => '否',
            ];
            return $this->keyRadio($field, $title, $options, $tips, $default, $verify, $disabled);
        }

        /**
         * 是与否的单选.
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @param array|null  $verify
         * @return $this
         */
        public function keySwitch($field, $title, $tips = null, $verify = null, $disabled = null)
        {
            $options = [
                1 => '是',
                0 => '否',
            ];
            return $this->key($field, $title, $tips, 'switch', $options, 1, $verify, 30, $disabled);
        }

        /**
         * 性别
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @param array|null  $verify
         * @return $this
         */
        public function keySex($field, $title = '性别', $tips = null, $verify = null)
        {
            $options = [
                2 => '女',
                1 => '男',
                0 => '保密',
            ];
            return $this->keyRadio($field, $title, $options, $tips);
        }

        /**
         * 新窗口选择
         * @param string      $field
         * @param string      $title
         * @param array       $options
         * @param string|null $tips
         * @param array|null  $verify
         * @param int         $size
         * @return $this
         */
        public function keyFind($field, $url, $title, $tips = null, $size = 20, $verify = null)
        {
            return $this->key($field, $title, $tips, 'find', ['url' => $url, 'field' => $field, 'num' => 1], $size, $verify);
        }

        /**
         * 新窗口选择多个
         * @param        $field
         * @param        $url
         * @param        $limit
         * @param        $title
         * @param null   $tips
         * @param string $field
         * @param int    $size
         * @param null   $verify
         * @return $this
         */
        public function keyBelongsToMany($field, $title, $column, $tips = null, $size = 20, $verify = null)
        {
            return $this->key($field, $title, $tips, 'belongsToMany', ['column' => $column, 'field' => $field, 'limit' => 0], $size, $verify);
        }

        /**
         * 新窗口选择一个
         * @param        $field
         * @param        $url
         * @param        $limit
         * @param        $title
         * @param null   $tips
         * @param string $field
         * @param int    $size
         * @param null   $verify
         * @return $this
         */
        public function keyBelongTo($field, $url, $title, $tips = null, $size = 20, $verify = null)
        {
            return $this->key($field, $title, $tips, 'belongTo', ['url' => $url, 'field' => $field, 'limit' => 0], $size, $verify);
        }

        /**
         * 下拉列表.
         * @param string      $field
         * @param string      $title
         * @param array       $options
         * @param string|null $tips
         * @param bool        $multiple 是否开启多项选择
         * @param array|null  $verify
         * @param int         $size
         * @return $this
         */
        public function keySelect($field, $title, $options, $tips = null, $default = '', $verify = null)
        {
            return $this->key($field, $title, $tips, 'select', $options, $default, $verify);
        }

        /**
         * 下拉列表多选.
         * @param string      $field
         * @param string      $title
         * @param array       $options
         * @param string|null $tips
         * @param bool        $multiple 是否开启多项选择
         * @param array|null  $verify
         * @param int         $size
         * @return $this
         */
        public function keySelectMultiple($field, $title, $options, $tips = null, $size = 0, $verify = null)
        {
            return $this->key($field, $title, $tips, 'select_multiple', $options, $size, $verify);
        }

        /**
         * 用于状态的下拉选择.
         * @param string     $field
         * @param string     $title
         * @param string     $tips
         * @param number     $size
         * @param array|null $options
         * @param array|null $verify
         * @return $this
         */
        public function keyStatus($field = 'status', $title = '状态', $options = null, $tips = null, $default = '', $verify = null)
        {
            $options = $options ?: [
                -2 => '删除',
                -1 => '禁用',
                1 => '启用',
                0 => '未审核',
                2 => '推荐',
            ];
            return $this->keySelect($field, $title, $options, $tips, $default, $verify);
        }

        /**
         * 复选框.
         * @param string      $field
         * @param string      $title
         * @param array       $options
         * @param string|null $tips
         * @param array|null  $verify
         * @return $this
         */
        public function keyCheckBox($field, $title, $options, $tips = null, $verify = null)
        {
            return $this->key($field, $title, $tips, 'checkbox', $options, 30, $verify);
        }

        /**
         * 关联单选复选框.
         * @param string      $field
         * @param string      $title
         * @param array       $options
         *                                   string $ajax_url 请求的url
         *                                   string $field 关联的字段
         * @param string|null $tips
         * @param array|null  $verify
         * @return $this
         */
        public function keyCheckBoxLinkCheck($field, $title, $options, $tips = null, $verify = null)
        {
            return $this->key($field, $title, $tips, 'checkbox_link_check', $options, 30, $verify);
        }

        /**
         * 用户组选择.
         * @param string     $field
         * @param string     $title
         * @param string     $tips
         * @param number     $size
         * @param array|null $verify
         * @return $this
         */
        public function keyMultiUserGroup($field, $title, $tips = null, $module = 'admin', $verify = null)
        {
            return $this->keyCheckBox($field, $title, $this->readUserGroups($module), $tips, $verify);
        }

        /**
         * TextArea
         * @param      $field
         * @param      $title
         * @param null $tips
         * @param int  $cols 列
         * @param int  $rows 行
         * @param null $verify
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/23 15:11
         */
        public function keyTextArea($field, $title, $tips = null, $default = '', $cols = 50, $rows = 2, $verify = null)
        {
            $size = [$cols, $rows];
            return $this->key($field, $title, $tips, 'textarea', null, $default, $verify, $size);
        }

        /**
         * 使用label显示内容
         * @param      $field
         * @param      $title
         * @param null $tips
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/23 15:12
         */
        public function keyLabel($field, $title, $tips = null)
        {
            return $this->key($field, $title, $tips, 'label');
        }

        /**
         * 闭包函数
         * @param      $field
         * @param      $title
         * @param      $closure
         * @param null $tips
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/6 13:18
         */
        public function keyClosure($field, $title, $closure, $tips = null)
        {
            return $this->key($field, text($title), $tips, $closure);
        }

        /**
         * 输入密码
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @return $this
         */
        public function keyPassword($field, $title, $tips = null)
        {
            return $this->key($field, $title, $tips, 'password');
        }

        /**
         * 实名认证
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @return $this
         */
        public function keyAuth($field, $title = '实名认证', $tips = null, $need_hand = 1)
        {
            return $this->key($field, $title, $tips, 'auth', $need_hand ? $need_hand : 0);
        }

        /**
         * 输入url地址
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @param int         $size
         * @param array|null  $verify
         * @return $this
         */
        public function keyUrl($field, $title, $tips = null, $default = '', $size = 50, $verify = '')
        {
            $tips = empty($tips) ? '需要以http或者https开头' : $tips;
            return $this->key($field, $title, $tips, 'url', null, $default, $verify ? ($verify . '|url') : 'url', $size);
        }

        /**
         * 颜色选择器.
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @return $this
         */
        public function keyColor($field, $title, $tips = null, $default = '')
        {
            return $this->key($field, $title, $tips, 'color');
        }

        /**
         * 可拖动插件列表.
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @return $this
         */
        public function keyDragsortLi($field, $title, $tips = null, $options = null)
        {
            return $this->key($field, $title, $tips, 'dragsort_li', $options);
        }

        /**
         * 带字体颜色选择的输入框，一般为文章标题.
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @return $this
         */
        public function keyHeadline($field, $title, $tips = null, $verify = null)
        {
            return $this->key($field, $title, $tips, 'headline', null, null, $verify);
        }

        /**
         * 自带的标签功能.
         * @param string      $field
         * @param string      $title
         * @param string|null $tips
         * @param array|null  $verify
         * @return $this
         */
        public function keyTags($field, $title, $tips = null, $verify = null)
        {
            return $this->key($field, $title, $tips, 'tags', null, null, $verify);
        }

        /**
         * 重量配置
         * @param string      $field 字段
         * @param string      $title 标题
         * @param array|null  $verify
         *                                   string $tips 为空的时候提示
         *                                   number $min [可选] 最小值。
         *                                   number $max [可选] 最大值。
         *                                   number $step [可选] 步长，每次调整的值大小。
         *                                   number $decimalPlace [可选] 小数位数。
         * @param string|null $url 如果填写url，则用户的每一步操作都会请求该地址验证
         * @return $this
         */
        public function keyWeight($field, $title, $tips = null, $verify = null, $url = null)
        {
            $verify_default = [
                'min' => 0,
                'max' => 50,
                'step' => 1,
                'decimal-place' => 3,
            ];
            $verify = is_array($verify) ? array_merge($verify_default, $verify) : $verify_default;
            return $this->key($field, $title, $tips, 'spinner', $url, strlen($verify['max']) + 10, $verify);
        }

        /**
         * 微调器.
         * @param string      $field 字段
         * @param string      $title 标题
         * @param array|null  $verify
         *                                   string $tips 为空的时候提示
         *                                   number $min [可选] 最小值。
         *                                   number $max [可选] 最大值。
         *                                   number $step [可选] 步长，每次调整的值大小。
         *                                   number $decimalPlace [可选] 小数位数。
         * @param string|null $url 如果填写url，则用户的每一步操作都会请求该地址验证
         * @return $this
         */
        public function keySpinner($field, $title, $tips = null, $verify = null, $url = null)
        {
            $verify_default = [
                'min' => 0,
                'max' => 255,
                'step' => 1,
                'decimal-place' => 0,
            ];
            $verify = is_array($verify) ? array_merge($verify_default, $verify) : $verify_default;
            return $this->key($field, $title, $tips, 'spinner', $url, strlen($verify['max']) + 10, $verify);
        }

        /**
         * 价格
         * @param      $field
         * @param      $title
         * @param null $tips
         * @param null $verify
         * @param int  $size
         * @return $this
         * @throws Exception
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/23 14:57
         */
        public function keyDecimal($field, $title, $tips = null, $verify = null, $size = 15)
        {
            $verify_default = [
                'rule' => 'money',
                'rule-money' => '[/^(?!0+(?:\.0+)?$)(?:[1-9]\d*|0)(?:\.\d{1,2})?$/, \'金额必须大于0并且只能精确到分\']',
                'tip' => '请填写金额',
                'ok' => '',
            ];
            $verify = is_array($verify) ? array_merge($verify_default, $verify) : $verify_default;
            return $this->key($field, $title, $tips, 'number', null, $size, $verify);
        }

        /**
         * 排序
         * @param string $field
         * @param string $title
         * @param string $tips
         * @param null   $verify
         * @param int    $size
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/23 14:57
         */
        public function keySort($field = 'sort', $title = '排序', $tips = '数值越大越靠前，最大255', $default = 0)
        {
            //            $verify = [
            //                'rule' => 'sort',
            //                'rule-sort' => '[/^(\d|[1-9]\d?|1\d{2}?|2[0-4][0-9]?|25[0-5])$/, \'请填写0-255之间的数字\']',
            //                'tip' => '请填写0-255之间的数字',
            //                'ok' => '',
            //            ];
            return $this->key($field, $title, $tips, 'number', null, $default, 'require|number|sort', 30);
        }

        /**
         * 要求验证填写数字.
         * @param string     $field
         * @param string     $title
         * @param string     $tips
         * @param number     $size
         * @param array|null $verify
         * @return $this
         */
        public function keyNumber($field, $title, $tips = null, $default = '', $verify = null)
        {
            return $this->key($field, $title, $tips, 'number', null, $default, $verify);
        }

        /**
         * 邮箱.
         * @return $this
         */
        public function keyEmail($field, $title, $tips = null, $default = '', $verify = 'email')
        {
            return $this->key($field, $title, $tips, 'email', null, $default, $verify);
        }


        /**
         * 手机
         * @param             $field
         * @param             $title
         * @param string|null $tips
         * @param string|null $default
         * @return $this
         * @throws Exception
         * @author 微尘 <yicmf@qq.com>
         * @datetime: 2020/5/29 21:39
         */
        public function keyMobile($field, $title, $tips = null, $default = '')
        {
            return $this->key($field, $title, $tips, 'inline', null, $default, 'mobile');
        }

        /**
         * 评分
         * @param        $field
         * @param        $title
         * @param string $tips
         * @param int    $default
         * @return $this
         * @throws Exception
         * @author 微尘 <yicmf@qq.com>
         * @datetime: 2020/5/30 6:51
         */
        public function keyRate($field, $title, $tips = '', $default = 3)
        {
            return $this->key($field, $title, $tips, 'rate', $default);
        }

        //        /**
        //         * 滑块
        //         * @param string     $field
        //         * @param string     $title
        //         * @param string     $tips
        //         * @param number     $size
        //         * @param array|null $verify
        //         * @return $this
        //         */
        //        public function keySlider($field, $title, $tips, $options)
        //        {
        //            return $this->key($field, $title, $tips, 'sourse', $options);
        //        }
        //
        //        /**
        //         * 选择来源、有待改进.
        //         * @param string     $field
        //         * @param string     $title
        //         * @param string     $tips
        //         * @param number     $size
        //         * @param array|null $verify
        //         * @return $this
        //         */
        //        public function keySourse($field, $title, $tips, $options)
        //        {
        //            return $this->key($field, $title, $tips, 'sourse', $options);
        //        }
        /**
         * 富文本编辑器
         * http://fex.baidu.com/ueditor/#start-start
         * @param string $field
         * @param string $title
         * @param string $tips
         * @param string $config
         * @param array  $style
         * @return $this
         */
        public function keyEditor($field, $title, $tips = null, $default = '', $config = [], $style = ['width' => '500px', 'height' => '400px'])
        {
            $items =
                [
                    'all' => [[
                        'fullscreen', 'source', '|', 'undo', 'redo', '|',
                        'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',
                        'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
                        'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',
                        'directionalityltr', 'directionalityrtl', 'indent', '|',
                        'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'touppercase', 'tolowercase', '|',
                        'link', 'unlink', 'anchor', '|', 'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
                        'simpleupload', 'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'gmap', 'insertframe', 'insertcode', 'webapp', 'pagebreak', 'template', 'background', '|',
                        'horizontal', 'date', 'time', 'spechars', 'snapscreen', 'wordimage', '|',
                        'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', 'charts', '|',
                        'print', 'preview', 'searchreplace', 'drafts', 'help'
                    ]]
                    ,
                    'common' => [
                        [
                            'fullscreen', 'source', 'preview', '|', 'insertcode',
                            'fontfamily', 'fontsize', 'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'forecolor', 'backcolor', 'superscript', 'subscript',
                            'link', '|', 'insertorderedlist', 'insertunorderedlist',
                            'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', 'horizontal', 'spechars', 'cleardoc', 'removeformat', 'selectall', 'pasteplain', 'undo', 'redo'
                        ],
                        [
                            'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', '|',
                            'simpleupload', 'insertimage', 'imagenone', 'imageleft', 'imageright', 'imagecenter', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'background', 'drafts'
                        ]
                    ]
                    ,
                    'simple' => [
                        [
                            'fullscreen', 'preview', 'undo', 'redo', '|', 'fontsize', 'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'forecolor', 'backcolor',
                            'link', '|', 'horizontal', 'spechars', 'emotion', '|', 'simpleupload', 'insertimage', 'attachment', 'removeformat', 'drafts'
                        ]
                    ],
                    'two_line' => [
                        ['fullscreen', 'source', 'undo', 'redo'],
                        ['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc']
                    ]
                ];
            $default_config = [
                'manager' => Url::build('file/Editor/manager', ['module' => $this->request->module()]),
                'upload' => Url::build('file/Editor/upload', ['module' => $this->request->module()])
            ];
            $config = array_merge($default_config, $config);
            if (isset($config['items'])) {
                if (!is_array($config['items']) && isset($items[$config['items']])) {
                    $config['items'] = $items[$config['items']];
                }
            } else {
                $config['items'] = $items['simple'];
            }
            $key = [
                'id_name' => uniqid(),
                'field' => $field,
                'title' => $title,
                'tips' => $tips,
                'type' => 'ueditor',
                'config' => $config,
                'style' => $style,
                'default' => $default,
            ];
            $this->_keyList[] = $key;
            return $this;
        }

        /**
         * 时间选择器
         * @return $this
         */
        public function keyTime($field, $title, $tips = null, $value = null, $min = '', $max = '')
        {
            return $this->keyDate($field, $title, $tips, $value, $min, $max, 'time');
        }

        /**
         * 时间选择器
         * @return $this
         */
        public function keyDateTime($field, $title, $tips = null, $value = null, $min = '', $max = '')
        {
            return $this->keyDate($field, $title, $tips, $value, $min, $max, 'datetime');
        }

        /**
         * 日期选择器.
         * @return $this
         */
        public function keyDate($field, $title, $tips = null, $default = null, $min = '', $max = '', $type = 'date', $range = false, $done = '')
        {
            $formats = [
                'year' => 'yyyy',
                'month' => 'MM',
                'date' => 'MM-dd',
                'time' => 'HH:mm:ss',
                'datetime' => 'yyyy-MM-dd HH:mm:ss',
            ];
            $opiton = [
                'elem' => '#j_builder_' . (strpos($field, '|') ? md5($field) : $field),
                'type' => $type,
                'range' => $range,
                'format ' => $formats[$type],
                'mark ' => [],
                'min' => $min,
                'max' => $max,
                'done' => $done
            ];
            foreach ($opiton as $key => $item) {
                if (!$item) {
                    unset($opiton[$key]);
                }
            }
            return $this->key($field, $title, $tips, 'date', $opiton, $default, null, 50);
        }

        public function keyDateTimeRange($field, $title, $tips = null, $default = null, $min = '', $max = '')
        {
            return $this->keyDate($field, $title, $tips, $default, $min, $max, 'datetime', true);
        }

        public function keyDateRange($field, $title, $tips = null, $default = null, $min = '', $max = '')
        {
            //            $default = '2020-05-01 - 2020-06-30';
            if (is_null($default)) {
                $default = time_format(time(), 'Y-m-d') . ' - ' . time_format('1 month', 'Y-m-d');
            } else if (!is_null($default) && false === strpos($default, '-')) {
                $default = time_format(time(), 'Y-m-d') . ' - ' . time_format($default, 'Y-m-d');
            }
            return $this->keyDate($field, $title, $tips, $default, $min, $max, 'date', true);
        }

        public function keyTimeRange($field, $title, $tips = null, $default = null, $min = '', $max = '')
        {
            return $this->keyDate($field, $title, $tips, $default, $min, $max, 'time', true);
        }

        /**
         * 仅展示一张图片.
         * @param string $field
         * @param string $title
         * @param string $tips
         * @return $this
         */
        public function keyShowImg($field, $title, $tips = '点击图片即可下载')
        {
            return $this->key($field, $title, $tips, 'showImg');
        }

        /**
         * 上传单个音频
         * @param 标题 $title
         * @param 描述 $tips
         * @return $this
         */
        public function keyVoice($field, $title, $remark = null, $size = 50, $verify = null)
        {
            $extensions = 'mp3,wav,wma,amr';
            return $this->key($field, $title, $remark, 'attachment', ['remark' => $remark, 'limit' => 1, 'extensions' => $extensions, 'size' => $size], 30, $verify);
        }

        /**
         * 上传单个视频
         * @param 标题 $title
         * @param 描述 $tips
         * @return $this
         */
        public function keyVideo($field, $title, $remark = null, $size = 300, $verify = null)
        {
            $extensions = 'mp4,avi,rmvb,rm,wmv';
            return $this->key($field, $title, $remark, 'attachment', ['remark' => $remark, 'limit' => 1, 'extensions' => $extensions, 'size' => $size], 30, $verify);
        }

        /**
         * 上传单个图片.
         * @param string $field 需要保存的字段，为URL地址，且必须是以_url结尾的字符串
         * @param string $title
         * @param null   $remark
         * @param null   $verify
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/5/8 11:57
         */
        public function keyPicture($field, $title, $remark = null, $verify = null, $default = 1)
        {
            $max_size = 0;
            $exts = '';
            $mimes = '';
            return $this->key($field, $title, null, 'image',
                ['remark' => $remark, 'limit' => 1, 'max_size' => $max_size, 'mimes' => $mimes, 'exts' => $exts]
                , $default, $verify);
        }

        /**
         * 多图片上传
         * @param string $field 需要保存的字段，为URL地址，且必须是以_url结尾的字符串
         * @param string $title
         * @param null   $remark
         * @param int    $limit
         * @param null   $verify
         * @return $this
         * @throws Exception
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/5/8 11:58
         */
        public function keyImages($field, $title, $remark = null, $limit = 5, $verify = null)
        {
            $max_size = 0;
            $exts = '';
            $mimes = '';
            return $this->key($field, $title, null, 'image',
                ['remark' => $remark, 'limit' => $limit, 'max_size' => $max_size, 'mimes' => $mimes, 'exts' => $exts]
                , 30, $verify);
        }

        /**
         * 上传单个附件
         * @param      $field
         * @param      $title
         * @param null $tips
         * @param int  $size
         * @param null $verify
         * @return $this
         * @throws Exception
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/5/8 11:58
         */
        public function keyAttachment($field, $title, $tips = null, $size = 50, $verify = null)
        {
            $extensions = '*';
            $remark = '';
            return $this->key($field, $title, $tips, 'attachment', ['remark' => $remark, 'limit' => 1, 'extensions' => $extensions, 'size' => $size], 30, $verify);
        }

        /**
         * 添加城市选择（需安装城市联动插件）
         * @param      $field
         * @param      $title
         * @param null $tips
         * @param null $verify
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/5/8 11:58
         */
        public function keyCity($field, $title, $tips = null, $verify = null)
        {
            // 修正在编辑信息时无法正常显示已经保存的地区信息
            return $this->key($field, $title, $tips, 'city', null, 30, $verify);
        }

        /**
         * 批量添加字段信息.
         * @param array $fields
         * @return $this
         * @author 微尘 <yicmf@qq.com>
         */
        public function setKeys($fields = [])
        {
            $this->_keyList = empty($this->_keyList) ? $fields : array_merge($this->_keyList, $fields);
            return $this;
        }

        /**
         * 表单参数组合
         * @param        $field
         * @param        $title
         * @param        $tips
         * @param        $type
         * @param null   $options
         * @param string $default
         * @param null   $verify
         * @return $this
         * @throws Exception
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/12 10:35
         */
        protected function key($field, $title, $tips, $type, $options = null, $default = '', $verify = null, $size = 30, $disabled = null)
        {
            if (is_array($verify)) {
                $verify = implode($verify, '|');
            }
            if (strpos($verify, ',')) {
                $verify = str_replace(',', '|', $verify);
            }
            $key = [
                'field' => $field,
                'title' => $title,
                'tips' => (string)$tips,
                'type' => $type,
                'default' => $default,
                'disabled' => $disabled,
                'placeholder' => '',
                'size' => $size,
                'verify' => $verify,
                'options' => $options,
            ];
            $this->_keyList[] = $key;
            return $this;
        }

        /**
         * 批量配置key
         * @param $keyList
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/4/12 10:25
         */
        public function keys($keyList)
        {
            foreach ($keyList as $index => $item) {
                $this->key($item['field']
                    , isset($item['title']) ? $item['title'] : ''
                    , isset($item['tips']) ? $item['tips'] : ''
                    , $item['type']
                    , isset($item['options']) ? $item['options'] : null
                    , isset($item['default']) ? $item['default'] : ''
                    , isset($item['verify']) ? $item['verify'] : null
                    , isset($item['size']) ? $item['size'] : 30
                    , isset($item['disabled']) ? $item['disabled'] : null
                );
            }
            return $this;
        }

        /**
         * 按钮基础调用，一般内部使用.
         * @param string $title
         * @param array  $attr
         * @return $this
         */
        public function button($title, $attr = [])
        {
            $this->_buttonList[] = [
                'title' => $title,
                'attr' => $attr,
            ];
            return $this;
        }

        /**
         * 提交按钮.
         * @param string $url 提交的url地址，默认当前页
         * @param string $title
         * @return $this
         */
        public function buttonSubmit($url = null, $title = '保存')
        {
            if (!is_null($url)) {
                $this->savePostUrl($url);
            }
            $attr = [
                'class' => 'layui-btn',
                'lay-filter' => "LAY-app-workorder-submit",
                'lay-submit' => '',
            ];
            $this->_form_submit = ['attr' => $attr, 'url' => $url, 'icon' => 'save', 'title' => $title];
            return $this;
        }

        /**
         * 关闭按钮.
         * @param string $title
         * @return $this
         */
        public function buttonReset($title = '重新填写')
        {
            $attr = [];
            $attr = [
                'class' => 'layui-btn',
            ];
            $this->_form_reset = ['attr' => $attr, 'icon' => 'close', 'title' => $title];
            return $this;
        }

        /**
         * 关闭按钮.
         * @param string $title
         * @return $this
         */
        public function buttonClose($title = '关闭')
        {
            $attr = [];
            $attr = [
                'class' => 'layui-btn',
            ];
            $this->_form_close = ['attr' => $attr, 'icon' => 'close', 'title' => $title];
            return $this;
        }

        /**
         * 当前表单值
         * @param array $data
         * @return $this
         */
        public function data($data = [])
        {
            $this->_data = $data;
            return $this;
        }

        /**
         *  //TODO: 内置验证共用
         * @access protected
         * @param string|array $validate 验证器名或者验证规则数组
         * @param array        $message 提示信息
         * @param bool         $batch 是否批量验证
         * @param mixed        $callback 回调方法（闭包）
         * @return array|string|true
         * @throws ValidateException
         */
        public function validate($validate, $message = [], $batch = false, $callback = null)
        {
            if (is_array($validate)) {
                $v = $this->app->validate();
                $v->rule($validate);
            } else {
                if (strpos($validate, '.')) {
                    // 支持场景
                    list($validate, $scene) = explode('.', $validate);
                }
                $v = $this->app->validate($validate);
                if (!empty($scene)) {
                    $v->scene($scene);
                }
            }
            //TODO: 是否批量验证
            if ($batch || $this->batchValidate) {
                $v->batch(true);
            }
            if (is_array($message)) {
                $v->message($message);
            }
            if ($callback && is_callable($callback)) {
                call_user_func_array($callback, [$v, &$data]);
            }
            //            dump($v->getRule());
            //            dump($v->getRegex());
            //            if (!$v->check($data)) {
            //                if ($this->failException) {
            //                    throw new ValidateException($v->getError());
            //                }
            //                return $v->getError();
            //            }
            return $this;
        }

        /**
         * 当前表单提交的地址
         * @param array $data
         * @return $this
         */
        public function savePostUrl($url)
        {
            $this->_savePostUrl = Url::build($url);
        }

        /**
         * input和下拉选择组合
         * @param string $field
         * @param string $title
         * @param string $tips
         * @param array  $config
         * @param string $style
         * @return $this
         */
        public function keyInputAndSelect($field, $title, $tips, $config, $style = 'width:400px;')
        {
            $field = is_array($field) ? $field : explode('|', $field);
            $key = [
                'field' => $field,
                'title' => $title,
                'tips' => $tips,
                'type' => 'ias',
                'config' => $config,
                'style' => $style,
            ];
            $this->_keyList[] = $key;
            return $this;
        }

        /**
         * keyMultiInput 输入组组件.
         * @param string $field
         * @param string $title
         * @param string $tips
         * @param array  $config
         * @param string $style
         * @return $this
         */
        public function keyMultiInput($field, $title, $tips, $config, $style = 'width:400px;')
        {
            $field = is_array($field) ? $field : explode('|', $field);
            $key = [
                'field' => $field,
                'title' => $title,
                'tips' => $tips,
                'type' => 'multiInput',
                'config' => $config,
                'style' => $style,
            ];
            $this->_keyList[] = $key;
            return $this;
        }

        /**
         * 插入配置分组.
         * @param string       $field 组名
         * @param array|string $list 组内字段列表
         * @return $this
         */
        public function group($field, $list = [])
        {
            if (is_array($field)) {
                $this->_group = array_merge($this->_group, $field);
            } else {
                !is_array($list) && $list = explode(',', $list);
                $this->_group[$field] = $list;
            }
            return $this;
        }

        /**
         * 解析加载模版输出
         * @param string $template
         * @param array  $vars
         * @param array  $config
         * @return string
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/3/14 18:23
         */
        public function fetch($template = '', $vars = [], $config = [])
        {
            // 将数据融入到key中
            $this->_formatData();
            // 配置触发
            $this->_formatTrigger();
            // 显示页面
            $this->assign('group', $this->_group);
            // 查询当前菜单
            $menu = MenuModel::where('status', 1)
                ->where('action', $this->request->action())
                ->where('controller', $this->request->controller())
                ->where('module', $this->request->module())
                ->find();
            if ($menu && !$this->_title) {
                $this->_title = $menu['title'];
            }
            if ($menu['group']) {
                $this->assign('menu_group_title', $menu['group']);
            }
            if ($menu['pid']) {
                $p_menu = MenuModel::where('status', 1)
                    ->where('id', $menu['pid'])
                    ->find();
                $this->assign('p_menu_title', $p_menu['title']);
            } else {
                $this->assign('p_menu_title', $menu['title']);
            }
            // 显示页面
            $this->assign('menu_title', $this->_title);
            $this->assign('templets', $this->_templets);
            //            if ($this->request->has('auto_builder', 'get')) {
            //                $this->assign('filter', $this->request->get('auto_builder'));
            //                $this->assign('auto_builder', 1);
            //            } else {
            $this->assign('filter', $this->request->module() . '-' . $this->request->controller() . '-' . $this->request->action());
            //            }
            if (count($this->_keyList)) {
                $this->assign('keyList', $this->_keyList);
            }
            //            dump($this->_keyList);
            if ($this->_form_submit) {
                $this->assign('form_submit', $this->_form_submit);
            }
            if ($this->_form_reset) {
                $this->assign('form_reset', $this->_form_reset);
            }
            if ($this->_form_close) {
                $this->assign('form_close', $this->_form_close);
            }
            // 在有赋值的情况展示
            if (count($this->_explaints) > 0) {
                $this->assign('explaints', $this->_explaints);
            }
            $this->assign('savePostUrl', $this->_savePostUrl ?: Url::build());
            $this->assign('triggers', $this->_triggers);
            //            dump($this->_triggers);
            $this->assign('reload', $this->_reload);
            $this->assign('mask', $this->_mask);
            return parent::_fetch('edit', $vars, $config);
        }

        /**
         * 规范触发数据格式.
         */
        private function _formatTrigger()
        {
            //TODO:考虑在没有配置当前字段默认值的情况，目前设计为不显示
            foreach ($this->_triggers as $field => &$trigger) {
                $fields = [];
                foreach ($trigger as $key => $trigger_detail) {
                    $shows = explode(',', $trigger_detail['show']);
                    $fields = array_merge($shows, $fields);
                }
                foreach ($trigger as $key => $trigger_detail) {
                    $jquery_show = [];
                    $jquery_hide = [];
                    $shows = explode(',', $trigger_detail['show']);
                    foreach ($fields as $_field) {
                        if (in_array($_field, $shows)) {
                            $jquery_show[] = $this->_jquery_md5($_field);
                        } else {
                            $jquery_hide[] = $this->_jquery_md5($_field);
                        }
                    }
                    $trigger[$key]['value'] = json_encode(explode(',', $trigger_detail['value']));
                    $trigger[$key]['show'] = json_encode($shows);
                    $trigger[$key]['jquery_show'] = implode(',', $jquery_show);
                    $trigger[$key]['jquery_hide'] = implode(',', $jquery_hide);
                    foreach ($this->_keyList as $data) {
                        if ($field == $data['field']) {
                            //当前值
                            if (in_array($data['value'], explode(',', $trigger_detail['value']))) {
                                $trigger[$key]['is_show'] = 1;
                            } else {
                                $trigger[$key]['is_show'] = 0;
                            }
                        }
                    }
                }
            }
        }

        private function _jquery_md5($field)
        {
            //            dump($field);
            //            if (strpos($field, '.')) {
            //                $field = md5(json_encode(explode('.', $field)));
            //            } elseif (strpos($field, '|')) {
            //                $field = md5(json_encode(explode('|', $field)));
            //            } else {
            $field = md5(is_array($field) ? implode(',', $field) : $field);
            //            }
            //            dump($field);
            return '#trigger_' . $field;
        }

        /**
         * 输入数据格式化.
         */
        private function _formatData()
        {
            if ('id' !== $this->_default_pk && is_object($this->_data)) {
                $pk = $this->_data->getPk();
            } else {
                $pk = $this->_default_pk;
            }
            $flag = false;
            foreach ($this->_keyList as $key => $e) {
                $pk == $e['field'] && $flag = true;
                $e['data'] = $this->_data;
                if (!isset($this->_data[$e['field']])) {
                    $this->_data[$e['field']] = $e['default'];
                }
                $e['jquery_id'] = md5(is_array($e['field']) ? implode(',', $e['field']) : $e['field']);
                if ($e['type'] instanceof \Closure) {
                    // 闭包
                    $e['value'] = $e['type']($this->_data, $e);
                    if (preg_match('/(.*)\[:(.*)\]/', $e['field'], $matches)) {
                        $e['field'] = $matches[1];
                        $e['type'] = $matches[2];
                    } else {
                        $e['type'] = 'label';
                    }
                } elseif (is_array($e['field'])) {
                    $i = 0;
                    $n = count($e['field']);
                    while ($n > 0) {
                        $e['value'][$i] = isset($this->_data[$e['field'][$i]]) ? $this->_data[$e['field'][$i]] : (isset($e['value'][$i]) ? $$e['value'][$i] : 0); //
                        $i++;
                        $n--;
                    }
                } elseif (strpos($e['field'], '.')) { // 支持点语法
                    $temp = explode('.', $e['field']);
                    $e['relation']['parent'] = $temp[0];
                    $e['relation']['child'] = $temp[1];
                    $e['field'] = $temp[0] . '[' . $temp[1] . ']';
                    $e['value'] = isset($this->_data[$temp[0]][$temp[1]]) ? $this->_data[$temp[0]][$temp[1]] : (isset($e['value']) ? $e['value'] : '');
                } elseif (strpos($e['field'], '|')) { // 使用‘|’代表同级字段
                    if (isset($e['value'])) {
                        $e['value'] = explode('|', $e['field']);
                    }
                    $e['field'] = explode('|', $e['field']);
                    foreach ($e['field'] as $t_key => $t_value) {
                        $e['value'][$t_key] = isset($this->_data[$t_value]) ? $this->_data[$t_value] : (isset($e[$t_key]) ? $e[$t_key] : '');
                    }
                } else {
                    $e['value'] = isset($this->_data[$e['field']]) ? $this->_data[$e['field']] : (isset($e['value']) ? $e['value'] : '');
                }
                $this->_keyList[$key] = $e;
            }
            if (!$flag && isset($this->_data[$pk])) {
                //自动增加隐藏表单用于编辑;
                $edit['field'] = $pk;
                $edit['type'] = 'hidden';
                $edit['value'] = $this->_data[$pk];
                $this->_keyList[] = $edit;
            }
        }

        private function readUserGroups($module)
        {
            return Db::name('AuthGroup')->where('status', 1)->where('module', $module)
                ->order('id ASC')
                ->column('id,title');
        }
    }
