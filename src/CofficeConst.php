<?php

namespace yzyblog\coffice_service;

/*
 *      CCloudStorageConst
 */
class CofficeConst
{
    static public $m_itake = 100;

    static public $arrNoAllowClass = [
                                        '_User',
                                        '_Role',
                                        '_Relation',
                                        '_SetupTables',
                                        '_SetupTablesColumns'
                                     ];
    static public $m_str_Class_User        = '_User';
    static public $m_str_Class_Role        = '_Role';
    static public $m_str_Class_Relation    = '_Relation';
    static public $m_str_SetupTablesName   = '_SetupTables';
    static public $m_str_SetupTablesColumn = '_SetupTablesColumns';
    static public $m_arr_DefaultACL        = array(
            'ACL'       => [
                '*' => [
                    'read'  => true,
                    'write' => true,
                ]
            ],
            'columnACL' => [
                '*' => [
                    'read'  => true,
                    'write' => true,
                ]
            ]
        );
    static public $m_arr_RoleColumnList    = array(
        [
            'className'     =>  '_Role',
            'column'        =>  'name',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  '角色名',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  1,
            'display'       =>  1
        ]
    );

    static public $m_arr_RelationColumnList    = array(
        [
            'className'     =>  '_Relation',
            'column'        =>  'userObjectId',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  '用户ID',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  1,
            'display'       =>  1
        ],
        [
            'className'     =>  '_Relation',
            'column'        =>  'roleObjectId',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  '角色ID',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  2,
            'display'       =>  1
        ]
    );

    static public $m_arr_UserColumnList    = array(
        [
            'className'     =>  '_User',
            'column'        =>  'username',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  '用户名',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  1,
            'display'       =>  0
        ],
        [
            'className'     =>  '_User',
            'column'        =>  'password',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  '密码',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  2,
            'display'       =>  1
        ],
        [
            'className'     =>  '_User',
            'column'        =>  'salt',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  '加密串',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  3,
            'display'       =>  1
        ],
        [
            'className'     =>  '_User',
            'column'        =>  'userToken',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  'token',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  4,
            'display'       =>  0
        ],
        [
            'className'     =>  '_User',
            'column'        =>  'tokenInvalidAt',
            'type'          =>  'str',
            'verify'        =>  ['required'],
            'describe'      =>  'token有效期',
            'default'       =>  '',
            'tag'           =>  '',
            'sort'          =>  5,
            'display'       =>  1
        ]
    );

    static public $m_arr_SetupTablesList   = array(
        'className',  // 表名
        'ACL',        // 列名
        'columnACL',  // 类型
    );
    static public $m_arr_SetupTablesType = array(
        'className'  =>    'str',      // 表名
        'ACL'        =>    'array',    // 列名
        'columnACL'  =>    'array',    // 类型
    );
    static public $m_arr_SetupTablesRule = array(
        'className'  =>    ['required','unique'],  // 表名
        'ACL'        =>    ['required'],           // 列名
        'columnACL'  =>    ['required'],           // 类型
    );
    static public $m_arr_SetupTablesDesc = array(
        'className'  =>    '表名',        // 表名
        'ACL'        =>    '表ACL',       // 列名
        'columnACL'  =>    '数据ACL',     // 类型
    );

    static public $m_arr_SetupTablesColumnList   = array(
        'className',  // 表名
        'column',     // 列名
        'type',       // 类型
        'verify',     // 验证规则
        'describe',   // 描述
        'default',    // 默认值
        'tag',        // 标签
        'sort',       // 排序
        'display',    // 客户端不可见
    );
    static public $m_arr_SetupTablesColumnListType = array(
        'className'  =>    'str',    // 表名
        'column'     =>    'str',    // 列名
        'type'       =>    'str',    // 类型
        'verify'     =>    'array',  // 验证规则
        'describe'   =>    'str',    // 描述
        'default'    =>    'str',    // 默认值
        'tag'        =>    'str',    // 标签
        'sort'       =>    'int',    // 排序
        'display'    =>    'int',    // 客户端不可见
    );
    static public $m_arr_SetupTablesColumnListRule = array(
        'className'  =>    ['required'],           // 表名
        'column'     =>    ['required','unique'],  // 列名
        'type'       =>    ['required'],           // 类型
        'verify'     =>    [],                     // 验证规则
        'describe'   =>    ['required'],           // 描述
        'default'    =>    [],                     // 默认值
        'tag'        =>    [],                     // 标签
        'sort'       =>    ['integer'],            // 排序
        'display'    =>    ['integer'],            // 客户端不可见
    );
    static public $m_arr_SetupTablesColumnListDesc = array(
        'className'  =>    '表名',        // 表名
        'column'     =>    '列名',        // 列名
        'type'       =>    '类型',        // 类型
        'verify'     =>    '验证规则',     // 验证规则
        'describe'   =>    '描述',        // 描述
        'default'    =>    '默认值',      // 默认值
        'tag'        =>    '标签',        // 标签
        'sort'       =>    '排序',        // 排序
        'display'    =>    '客户端不可见',  // 客户端不可见
    );

    // 转换字段类型
    static public $m_arr_StrData = array(
        'str',
        'text'
    );
    static public $m_arr_IntData = array(
        'int'
    );
    static public $m_arr_ArrData = array(
        'array',
        'file'
    );

    //
    //	common error codes
    //
    const ERROR_SUCCESS			            = 0;            //      successfully
    const ERROR_ACCESS_CLASS_NO_ALLOW		= -100001;      //      access class no exist
    const ERROR_ACCESS_EXEC_ERROR		    = -100010;      //      access exec error

//            app('db')->enableQueryLog();
//            print_r(app('db')->getQueryLog());die;
}

?>